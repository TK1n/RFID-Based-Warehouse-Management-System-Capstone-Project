<?php
/**
 * Password hashing and verification functions
 */
function hashPassword($password) {
    $options = [
        'cost' => 12,
    ];
    return password_hash($password, PASSWORD_DEFAULT, $options);
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}

function passwordNeedsRehash($hashedPassword) {
    return password_needs_rehash($hashedPassword, PASSWORD_DEFAULT);
}
 
/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $min_length = 8;
    
    if (strlen($password) < $min_length) {
        return "Password must be at least $min_length characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        return "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        return "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        return "Password must contain at least one number";
    }
    
    return true;
}

/**
 * Common utility functions for API responses and error handling
 */
function sendJSON($data, $status = 200) {
    http_response_code($status);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data);
    exit;
}
/**
 * Simplify success responses
 */
function successResponse($message, $extra = []) {
    $response = array_merge(["success" => true, "message" => $message], $extra);
    sendJSON($response, 200);
}
/**
 * Simplify error responses
 */
function errorResponse($message, $code = 400, $extra = []) {
    $response = array_merge(["success" => false, "error" => $message], $extra);
    sendJSON($response, $code);
}
/**
 * Validate required fields in input JSON
 */
function requireFields($data, $fields = []) {
    foreach ($fields as $field) {
        if (!isset($data[$field]) || $data[$field] === '') {
            errorResponse("Missing required field: $field", 422);
        }
    }
}
/**
 * Get JSON input from request
 */
function getJSON() {
    $input = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        errorResponse("Invalid JSON input: " . json_last_error_msg());
    }
    return $input;
}

/**
 * cURL request function for API calls
 */
function callAPI($method, $url, $data = false) {
    $curl = curl_init();

    $payload = $data ? json_encode($data) : null;

    $headers = [
        "Content-Type: application/json",
        "Accept: application/json"
    ];

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
            break;

        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
            break;

        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            break;

        default:
            // GET
            if ($data) {
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}

?>

