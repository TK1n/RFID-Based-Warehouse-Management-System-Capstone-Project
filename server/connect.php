<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer/src/Exception.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require_once(__DIR__ . "/api/helpers.php");
require_once(__DIR__ . "/api/Database.php");

header("Content-Type: application/json");

// Start session to access user data
session_start();

$API_BASE = "http://localhost/RFID2.3/server/api/";

//ADMIN
$ADMIN_EMAIL = ""; // Admin email for notifications
$APPROVAL_BASE_URL = "http://localhost/RFID2.3/server/approval.php"; // Base URL for approval links in admin emails

$method = $_SERVER['REQUEST_METHOD'];

try {

    // Only allow POST requests from your form
    if ($method !== "POST" || !isset($_POST['save'])) {
        header("Location: ../index.php");
        exit;
    }

   // Check if user is logged in
   if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        header("Location: ../index.php?error=" . urlencode("Please login to register for equipment borrowing"));
        exit;
    }

    // ----------------------------------------------------------
    // 1️⃣ GET USER DATA FROM SESSION (instead of form)
    // ----------------------------------------------------------
    $name        = $_SESSION['name'];
    $student_id  = $_SESSION['username']; // This is the ID number from login
    $user_id     = $_SESSION['user_id'];
    
    // Get email from database using user_id
    try {
        $pdo = Database::connect();
        $stmt = $pdo->prepare("SELECT mail FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && !empty($user['mail'])) {
            $email = $user['mail'];
        } else {
            // Fallback to ID number + domain if email not found
            $email = $student_id . '@hcmut.edu.vn';
        }
    } catch (Exception $e) {
        // Fallback if database query fails
        $email = $student_id . '@hcmut.edu.vn';
    }

    // ----------------------------------------------------------
    // 2️⃣ GET FORM INPUTS (device ID, transaction type, and date)
    // ----------------------------------------------------------
    $device_id        = trim($_POST['device_id']);
    $transaction_type = trim($_POST['transaction_type']);
    $transaction_date = trim($_POST['transaction-date']);

    // Basic validation
    $errors = [];
    if (!$device_id)        $errors[] = "Device ID required";
    if (!$transaction_type) $errors[] = "Transaction type required";
    if (!$transaction_date) $errors[] = "Date required";

    if (!empty($errors)) {
        header("Location: ../index.php?error=" . urlencode(implode(", ", $errors)));
        exit;
    }

    // Validate transaction type
    if (!in_array($transaction_type, ['borrow', 'return'])) {
        header("Location: ../index.php?error=" . urlencode("Invalid transaction type"));
        exit;
    }
    
    $getEquip = callAPI("GET", $API_BASE . "devices.php?rfid_tag_id=" . urlencode($device_id));

    if (!$getEquip["success"] || empty($getEquip["data"])) {
        header("Location: ../index.php?error=" . urlencode("Equipment not found with ID: " . $device_id));
        exit;
    }

    $equipment_id = $getEquip["data"]["equipment_id"]; 
    $device_name  = $getEquip["data"]["name"];
    // ----------------------------------------------------------
    // 4️⃣ HANDLE TRANSACTION BASED ON TYPE
    // ----------------------------------------------------------
    if ($transaction_type === 'borrow') {
        // CREATE BORROW TRANSACTION (via API POST)
        $borrow = callAPI("POST", $API_BASE . "transactions.php", [
            "user_id"       => $user_id,
            "equipment_ids" => [$equipment_id],
            "due_time"      => date('Y-m-d', strtotime($transaction_date . ' +7 days')), // Default 7 days from transaction date
            "borrow_time"   => $transaction_date
        ]);

        if (!$borrow["success"]) {
            header("Location: ../index.php?error=" . urlencode("Borrow request failed: " . ($borrow["error"] ?? "Unknown error")));
            exit;
        }

        $transaction_message = "borrow request submitted and pending approval";
        $email_subject = "Equipment Borrow Request Submitted";

    } else {
        // ----------------------------------------------------------
        // RETURN TRANSACTION
        // ----------------------------------------------------------    
        try {
            $pdo = Database::connect();
            
            $stmt = $pdo->prepare("
                SELECT id 
                FROM transaction_history 
                WHERE equipment_id = ? 
                AND status = 'borrowed' 
                ORDER BY borrow_time DESC
                LIMIT 1
            ");
            $stmt->execute([$equipment_id]);
            $activeTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$activeTransaction) {
                header("Location: ../index.php?error=" . urlencode("No active borrow transaction found for this equipment"));
                exit;    
            }
            
            $transaction_id = $activeTransaction['id'];

            $return = callAPI("PUT", $API_BASE . "transactions.php", [
                "id" => $transaction_id,
                "return_time" => $transaction_date
            ]);
    
            if (!$return["success"]) {
                header("Location: ../index.php?error=" . urlencode("Return failed: " . ($return["error"] ?? "Unknown error")));
                exit;
            }
    
            $transaction_message = "returned successfully";
            $email_subject = "Equipment Return Confirmation";

        } catch (Exception $e) {
            header("Location: ../index.php?error=" . urlencode("Error processing return: " . $e->getMessage()));
            exit;
        }
    }

    // ----------------------------------------------------------
    // 5️⃣ SEND EMAIL CONFIRMATION
    // ----------------------------------------------------------
    try {
        $mail = new PHPMailer(true);

        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = "";
        $mail->Port       = 465;

        $mail->setFrom('zenith-system@hcmut.edu.vn', 'ZENITH System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $email_subject;

        $action_text = $transaction_type === 'borrow' ? 'borrow' : 'return';

        $mail->Body = "
            <h2>ZENITH FEEDBACK</h2>
            <p>Hello <strong>$name</strong>,</p>
            <p>You have successfully " . ($transaction_type === 'borrow' ? "resgistered to borrow" : "returned") . " an equipment from Zenith.</p>
            <p><strong>Student Name:</strong> $name</p>
            <p><strong>Student ID:</strong> $student_id</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Device $action_text:</strong> $device_name</p>
            <p><strong>Device ID:</strong> $device_id</p>
            <p><strong>Date:</strong> $transaction_date</p>
            <p><strong>Transaction Type:</strong> " . ucfirst($transaction_type) . "</p>
            <hr>
            " . ($transaction_type === 'borrow' ? 
                "<p>Please return the equipment by the due date to avoid penalties.</p>" : 
                "<p>Thank you for returning the equipment on time.</p>") . "
            <p>If you didn't perform this transaction, please contact us immediately.</p>
            <p>--</p>
            <p>Data and Information Technology Center Ho Chi Minh City University of Technology 268 Ly Thuong Kiet St., Dist. 10, Ho Chi Minh City, VIETNAM</p>
            <p>Tel: +84 838 647 265 Ext 5200</p>
            <p>--</p>
        ";

        $mail->send();

    } catch (Exception $e) {
        error_log("Email send failed: " . $mail->ErrorInfo);
        // Don't redirect on email failure - the borrow was still successful
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = "";
        $mail->Port       = 465;

        $mail->setFrom('zenith-system@hcmut.edu.vn', 'ZENITH System');
        $mail->addAddress($ADMIN_EMAIL);
        $mail->isHTML(true);
        
        $mail->Subject = "New Equipment Borrow Request - Requires Approval";
        $mail->Body = "
            <h2>ZENITH SYSTEM - NEW BORROW REQUEST</h2>
            <p>A new equipment borrow request requires your approval.</p>
            <p><strong>Request Details:</strong></p>
            <ul>
                <li><strong>Student Name:</strong> $name</li>
                <li><strong>Student ID:</strong> $student_id</li>
                <li><strong>Student Email:</strong> $email</li>
                <li><strong>Device:</strong>$device_name</li>
                <li><strong>Device ID:</strong>$device_id</li>
                <li><strong>Request Date:</strong>$transaction_date</li>
            </ul>
            <hr>
            <p>ZENITH Equipment Management System</p>
        ";
        
        $mail->send();
        
    } catch (Exception $e) {
        error_log("Email send failed to admin: " . $mail->ErrorInfo);
    }

    // ----------------------------------------------------------
    // 6️⃣ REDIRECT TO SUCCESS
    // ----------------------------------------------------------
    header("Location: ../index.php?success=1");
    exit();

} catch (Exception $e) {
    header("Location: ../index.php?error=" . urlencode($e->getMessage()));
    exit();
}



?>