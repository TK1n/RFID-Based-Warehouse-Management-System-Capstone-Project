<?php
require_once("Database.php");
require_once("helpers.php");
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Database::connect();

    switch ($method) {
        // ------------------- READ -------------------
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :id");
                $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    successResponse("User retrieved successfully", ["data" => $user]); // FIX: changed "user" to "data"
                } else {
                    errorResponse("User not found", 404);
                }
            } 
            // ADD THIS: Search by id_number
            else if (isset($_GET['id_number'])) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id_number = :id_number");
                $stmt->bindParam(':id_number', $_GET['id_number'], PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    successResponse("User retrieved successfully", ["data" => $user]); // FIX: changed "user" to "data"
                } else {
                    errorResponse("User not found", 404);
                }
            } 
            else {
                $stmt = $pdo->query("SELECT * FROM users ORDER BY user_id");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                successResponse("Users retrieved successfully", ["data" => $users]); // FIX: changed "users" to "data"
            }
            break;

        // ------------------- CREATE -------------------
        case 'POST':
            $data = json_decode(file_get_contents("php://input"), true);
            requireFields($data, ['name', 'role', 'id_number', 'mail']);
        
            $stmt = $pdo->prepare("
                INSERT INTO users (name, id_number, mail, role)
                VALUES (:name, :id_number, :mail, :role)
            ");
            $stmt->bindParam(':name', $data['name'], PDO::PARAM_STR);
            $stmt->bindParam(':id_number', $data['id_number'], PDO::PARAM_STR); // FIX: Changed to PARAM_STR since your DB shows character varying
            $stmt->bindParam(':mail', $data['mail'], PDO::PARAM_STR);
            $stmt->bindParam(':role', $data['role'], PDO::PARAM_STR);
            $stmt->execute();
        
            $user_id = $pdo->lastInsertId();
        
            successResponse("User created successfully", ["data" => ["user_id" => $user_id]]); // FIX: Wrap in "data"
            break;
            
        // ------------------- UPDATE -------------------
        case 'PUT':
            if (!isset($_GET['id'])) {
                errorResponse("Missing user ID", 422);
            }

            $id = $_GET['id'];
            $data = json_decode(file_get_contents("php://input"), true);

            $stmt = $pdo->prepare("
                UPDATE users
                SET name = COALESCE(:name, name),
                    role = COALESCE(:role, role),
                    phone = COALESCE(:phone, phone)
                WHERE user_id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':name' => $data['name'] ?? null,
                ':role' => $data['role'] ?? null,
                ':phone' => $data['phone'] ?? null
            ]);

            successResponse("User updated successfully");
            break;

        // ------------------- DELETE -------------------
        case 'DELETE':
            if (!isset($_GET['id'])) {
                errorResponse("Missing user ID", 422);
            }

            $id = $_GET['id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            successResponse("User deleted successfully");
            break;

        default:
            errorResponse("Unsupported request method", 405);
    }

} catch (Exception $e) {
    errorResponse($e->getMessage());
}
?>