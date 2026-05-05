<?php

require_once("Database.php");
require_once("helpers.php");
header("Content-Type: application/json");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
function sendMailQuick($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = "ssl";
        $mail->Port       = 465;

        $mail->setFrom('zenith-system@hcmut.edu.vn', 'ZENITH System');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
    } catch (Exception $e) {
        // Fail silently — transaction must not break because of email
    }
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Database::connect();

    switch ($method) {

        // 🟢 GET — Show transaction history from the VIEW
        case 'GET':
            $sql = "SELECT id, user_id, equipment_id, borrow_time, due_time, return_time, status 
                    FROM transaction_history";
            
            if (isset($_GET['user_id'])) {
                $stmt = $pdo->prepare($sql . " WHERE user_id = :user_id ORDER BY borrow_time DESC");
                $stmt->bindParam(':user_id', $_GET['user_id']);
            } else if (isset($_GET['equipment_id'])) {
                $stmt = $pdo->prepare($sql . " WHERE equipment_id = :equipment_id ORDER BY borrow_time DESC");
                $stmt->bindParam(':equipment_id', $_GET['equipment_id']);
            } else if (isset($_GET['status'])) {
                $stmt = $pdo->prepare($sql . " WHERE status = :status ORDER BY borrow_time DESC");
                $stmt->bindParam(':status', $_GET['status']);
            } else {
                $stmt = $pdo->prepare($sql . " ORDER BY borrow_time DESC");
            }
        
            $stmt->execute();
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            successResponse("Transactions retrieved successfully", [
                "data" => $transactions
            ]);
        break;


        // 🟡 POST — Create new borrow record(s)
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            requireFields($data, ['user_id', 'equipment_ids']);  
        
            if (!is_array($data['equipment_ids']) || count($data['equipment_ids']) === 0) {
                errorResponse("equipment_ids must be a non-empty array");
            }
        
            $pdo->beginTransaction();
        
            try {
                $user_id = $data['user_id'];
                $borrow_time = $data['borrow_time'] ?? date('Y-m-d H:i:s');  // ← Add this line
                $due_time = $data['due_time'] ?? date('Y-m-d H:i:s', strtotime('+7 days'));
        
                $insertStmt = $pdo->prepare("
                    INSERT INTO transaction_history (
                        user_id,
                        equipment_id,
                        borrow_time,
                        due_time,
                        status
                    )
                    VALUES (
                        :user_id,
                        :equipment_id,
                        :borrow_time,  
                        :due_time,
                        'approving'
                    )
                    RETURNING id
                ");
        
                $updateEquip = $pdo->prepare("
                    UPDATE equipment 
                    SET status = 'borrowed' 
                    WHERE equipment_id = :equipment_id
                ");
        
                $created_transactions = [];
        
                foreach ($data['equipment_ids'] as $equip_id) {
                    $insertStmt->execute([
                        ':user_id' => $user_id,
                        ':equipment_id' => $equip_id,
                        ':borrow_time' => $borrow_time, 
                        ':due_time' => $due_time
                    ]);
        
                    $row = $insertStmt->fetch(PDO::FETCH_ASSOC);
                    $created_transactions[] = $row['id'];
        
                    $updateEquip->execute([':equipment_id' => $equip_id]);
                    if ($updateEquip->rowCount() === 0) {
                        throw new Exception("Equipment is not available");
                    }
                }
        
                $pdo->commit();
        
                successResponse("Borrow transaction recorded successfully", [
                    'ids' => $created_transactions,
                    'borrowed_equipment' => $data['equipment_ids'],
                    'due_time' => $due_time,
                    'borrow_time' => $borrow_time
                ]);
        
            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse("Borrow failed: " . $e->getMessage());
            }
            break;

        // 🔵 PUT — Mark equipment as returned
        case 'PUT':
            $data = json_decode(file_get_contents("php://input"), true);

            // Determine transaction ID
            if (isset($_GET['id'])) {
                $transaction_id = $_GET['id'];
            } elseif (isset($data['id'])) {
                $transaction_id = $data['id'];
            } else {
                errorResponse("Missing transaction ID. Provide it in URL (?id=) or request body", 422);
            }

            $pdo->beginTransaction();

            try {
                // If transaction_id is provided, get the transaction normally
                $getStmt = $pdo->prepare("
                SELECT id, equipment_id, status
                FROM transaction_history
                WHERE id = :id
            ");
            $getStmt->execute([':id' => $transaction_id]);
            $transaction = $getStmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$transaction) {
                $pdo->rollBack();
                errorResponse("Transaction not found with ID: " . $transaction_id, 404);
            }
            
            /* =====================================================
            ADMIN APPROVE TRANSACTION
            ===================================================== */
            if (
                isset($data['action']) &&
                $data['action'] === 'approve'
            ) {
                if ($transaction['status'] !== 'approving') {
                    $pdo->rollBack();
                    errorResponse("Only approving transactions can be approved", 400);
                }

                $approveStmt = $pdo->prepare("
                    UPDATE transaction_history
                    SET status = 'borrowed'
                    WHERE id = :id
                    AND status = 'approving'
                ");
                $approveStmt->execute([':id' => $transaction_id]);

                if ($approveStmt->rowCount() === 0) {
                    $pdo->rollBack();
                    errorResponse("Transaction already processed", 400);
                }
                
                $mailStmt = $pdo->prepare("
                    SELECT u.mail, e.name AS equipment_name
                    FROM transaction_history t
                    JOIN users u ON t.user_id = u.user_id
                    JOIN equipment e ON t.equipment_id = e.equipment_id
                    WHERE t.id = ?
                ");
                $mailStmt->execute([$transaction_id]);
                $mailData = $mailStmt->fetch(PDO::FETCH_ASSOC);
                
                $pdo->commit(); 
                
                if ($mailData && !empty($mailData['mail'])) {
                    sendMailQuick(
                        $mailData['mail'],
                        "Borrow Request Approved",
                        "<p>Your request to borrow <strong>{$mailData['equipment_name']}</strong> has been approved.</p>"
                    );
                }             

                successResponse("Transaction approved successfully", [
                    'transaction_id' => $transaction_id
                ]);
                return;
            }

            /* =====================================================
                RETURN TRANSACTION
            ===================================================== */

            if ($transaction['status'] === 'returned') {
                $pdo->rollBack();
                errorResponse("Equipment already returned", 400);
            }
    
            // Update transaction: set return_time and status
            $updateTrans = $pdo->prepare("
                UPDATE transaction_history
                SET return_time = COALESCE(:return_time, CURRENT_TIMESTAMP),
                    status = 'returned'
                WHERE id = :id
            ");
            $updateTrans->execute([
                ':id' => $transaction_id,
                ':return_time' => $data['return_time'] ?? null
            ]);
    
            // Update equipment status to available
            $updateEquip = $pdo->prepare("
                UPDATE equipment
                SET status = 'available'
                WHERE equipment_id = :equipment_id
            ");
            $updateEquip->execute([':equipment_id' => $transaction['equipment_id']]);
    
            $pdo->commit();
    
            successResponse("Equipment returned successfully", [
                'transaction_id' => $transaction_id,
                'equipment_id' => $transaction['equipment_id'],
                'return_time' => $data['return_time'] ?? date('Y-m-d H:i:s')
            ]);
            
            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse("Return failed: " . $e->getMessage());
            }
            break;

        // 🔴 DELETE — Delete a transaction and free the equipment
        case 'DELETE':
            $data = json_decode(file_get_contents("php://input"), true);

            // Determine transaction ID
            if (isset($_GET['id'])) {
                $transaction_id = $_GET['id'];
            } elseif (isset($data['id'])) {
                $transaction_id = $data['id'];
            } else {
                errorResponse("Missing transaction ID. Provide it in URL (?id=) or request body", 422);
            }

            $pdo->beginTransaction();

            try {
                // 1. Get transaction info
                $getStmt = $pdo->prepare("
                    SELECT id, equipment_id
                    FROM transaction_history
                    WHERE id = :id
                ");
                $getStmt->execute([':id' => $transaction_id]);
                $transaction = $getStmt->fetch(PDO::FETCH_ASSOC);

                if (!$transaction) {
                    $pdo->rollBack();
                    errorResponse("Transaction not found with ID: " . $transaction_id, 404);
                }
                
                $mailStmt = $pdo->prepare("
                    SELECT u.mail, e.name AS equipment_name
                    FROM transaction_history t
                    JOIN users u ON t.user_id = u.user_id
                    JOIN equipment e ON t.equipment_id = e.equipment_id
                    WHERE t.id = ?
                ");
                $mailStmt->execute([$transaction_id]);
                $mailData = $mailStmt->fetch(PDO::FETCH_ASSOC);               

                // 2. Delete transaction
                $deleteStmt = $pdo->prepare("
                    DELETE FROM transaction_history
                    WHERE id = :id
                ");
                $deleteStmt->execute([':id' => $transaction_id]);

                // 3. Update equipment status to available
                $updateEquip = $pdo->prepare("
                    UPDATE equipment
                    SET status = 'available'
                    WHERE equipment_id = :equipment_id
                ");
                $updateEquip->execute([
                    ':equipment_id' => $transaction['equipment_id']
                ]);

                $pdo->commit();

                if ($mailData && !empty($mailData['mail'])) {
                    sendMailQuick(
                        $mailData['mail'],
                        "Borrow Request Rejected",
                        "<p>Your request to borrow <strong>{$mailData['equipment_name']}</strong> was rejected by the lab manager.</p>"
                    );
                }                
                
                successResponse("Transaction deleted successfully", [
                    'transaction_id' => $transaction_id,
                    'equipment_id' => $transaction['equipment_id'],
                    'equipment_status' => 'available'
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                errorResponse("Delete failed: " . $e->getMessage());
            }
            break;

        default:
            errorResponse("Unsupported request method", 405);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    errorResponse($e->getMessage());
}
?>
