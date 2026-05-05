<?php
require_once("Database.php");
require_once("helpers.php");
header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = Database::connect();

    switch ($method) {

        // ---------------------------------------------------------
        // GET — Retrieve equipment
        // ---------------------------------------------------------
        case 'GET':

            if (isset($_GET['rfid_tag_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM equipment WHERE rfid_tag_id = :rfid");
                $stmt->execute([':rfid' => $_GET['rfid_tag_id']]);
                successResponse("Equipment retrieved", ["data" => $stmt->fetch(PDO::FETCH_ASSOC)]);
            }

            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = :id");
                $stmt->execute([':id' => $_GET['id']]);
                successResponse("Equipment retrieved", ["data" => $stmt->fetch(PDO::FETCH_ASSOC)]);
            }

            // Default — get all
            $stmt = $pdo->query("SELECT * FROM equipment ORDER BY equipment_id ASC");
            successResponse("Equipment list retrieved", ["data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        // ---------------------------------------------------------
        // POST — Add new equipment
        // ---------------------------------------------------------
        case 'POST':
            $data = getJSON();

            requireFields($data, ['rfid_tag_id', 'name']);

            $insert = $pdo->prepare("
                INSERT INTO equipment (rfid_tag_id, name, description, location, status)
                VALUES (:rfid, :name, :desc, :loc, COALESCE(:status, 'available'))
                RETURNING equipment_id
            ");

            $insert->execute([
                ':rfid' => $data['rfid_tag_id'],
                ':name' => $data['name'],
                ':desc' => $data['description'] ?? null,
                ':loc'  => $data['location'] ?? null,
                ':status' => $data['status'] ?? null
            ]);

            successResponse("Equipment added", [
                "equipment_id" => $insert->fetchColumn()
            ]);
            break;


        // ---------------------------------------------------------
        // PUT — Update equipment information
        // ---------------------------------------------------------
        case 'PUT':
            $data = getJSON();
            requireFields($data, ['equipment_id']);

            $fields = [];
            $params = [':equipment_id' => $data['equipment_id']];

            foreach (['rfid_tag_id', 'name', 'description', 'location', 'status'] as $col) {
                if (isset($data[$col])) {
                    $fields[] = "$col = :$col";
                    $params[":$col"] = $data[$col];
                }
            }

            if (empty($fields)) errorResponse("No fields to update");

            $sql = "UPDATE equipment SET " . implode(", ", $fields) . " WHERE equipment_id = :equipment_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            successResponse("Equipment updated");
            break;


        // ---------------------------------------------------------
        // DELETE — Remove equipment
        // ---------------------------------------------------------
        case 'DELETE':
            parse_str(file_get_contents("php://input"), $data);
            requireFields($data, ['equipment_id']);

            $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = :id");
            $stmt->execute([':id' => $data['equipment_id']]);

            successResponse("Equipment deleted");
            break;

        default:
            errorResponse("Unsupported request method", 405);
    }

} catch (Exception $e) {
    errorResponse($e->getMessage());
}
?>
