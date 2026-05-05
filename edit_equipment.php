<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit();
}

// Include your Database class
$databaseFile = __DIR__ . '/server/api/Database.php';
if (!file_exists($databaseFile)) {
    die("❌ Error: Database.php file not found at: " . realpath($databaseFile));
}
require_once $databaseFile;

if (!class_exists('Database')) {
    die("❌ Error: Database class not found after including the file");
}

$equipment = null;
$message = '';
$message_type = '';

try {
    $pdo = Database::connect();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $equipment_id = $_POST['equipment_id'];
        $rfid_tag_id = trim($_POST['rfid_tag_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $location = trim($_POST['location']);
        $status = $_POST['status'];
        
        // Validate required fields
        if (empty($rfid_tag_id) || empty($name)) {
            $message = "RFID Tag ID and Name are required fields!";
            $message_type = "error";
        } else {
            // Check if RFID tag already exists (excluding current equipment)
            $check_stmt = $pdo->prepare("SELECT equipment_id FROM equipment WHERE rfid_tag_id = ? AND equipment_id != ?");
            $check_stmt->execute([$rfid_tag_id, $equipment_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = "RFID Tag ID already exists! Please use a different one.";
                $message_type = "error";
            } else {
                // Update equipment
                $update_stmt = $pdo->prepare("UPDATE equipment SET rfid_tag_id = ?, name = ?, description = ?, location = ?, status = ? WHERE equipment_id = ?");
                $update_stmt->execute([$rfid_tag_id, $name, $description, $location, $status, $equipment_id]);
                
                if ($update_stmt->rowCount() > 0) {
                    $_SESSION['message'] = "Equipment updated successfully!";
                    $_SESSION['message_type'] = "success";
                    header("Location: admin.php");
                    exit();
                } else {
                    $message = "No changes were made or equipment not found.";
                    $message_type = "warning";
                }
            }
        }
    }
    
    // Fetch equipment data for editing
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
        $stmt->execute([$_GET['id']]);
        $equipment = $stmt->fetch();
        
        if (!$equipment) {
            $message = "Equipment not found!";
            $message_type = "error";
        }
    } else {
        $message = "No equipment ID specified!";
        $message_type = "error";
    }
    
} catch (PDOException $e) {
    $message = "Database error: " . $e->getMessage();
    $message_type = "error";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Edit Equipment - Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .welcome {
            color: #2c3e50;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .back-btn, .logout {
            background-color: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .back-btn:hover, .logout:hover {
            background-color: #545b62;
            transform: translateY(-1px);
        }
        .logout {
            background-color: #e74c3c;
        }
        .logout:hover {
            background-color: #c0392b;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .form-container {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn-primary {
            background-color: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        .btn-secondary:hover {
            background-color: #545b62;
            transform: translateY(-2px);
        }
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }
        .message {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .message.warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .required {
            color: #e74c3c;
        }
        .form-help {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        .equipment-id-display {
            background-color: #e9ecef;
            padding: 12px;
            border-radius: 6px;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="welcome">
                <i class="fas fa-edit"></i>
                Edit Equipment
            </h1>
            <div class="header-actions">
                <a href="admin.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
                <a href="?logout=1" class="logout">
                    <i class="fas fa-power-off"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : ($message_type == 'error' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($equipment): ?>
            <div class="form-container">
                <form method="POST" action="">
                    <input type="hidden" name="equipment_id" value="<?php echo htmlspecialchars($equipment['equipment_id']); ?>">
                    
                    <div class="form-group">
                        <label for="equipment_id">Equipment ID <span class="required">*</span></label>
                        <div class="equipment-id-display">
                            <?php echo htmlspecialchars($equipment['equipment_id']); ?>
                        </div>
                        <div class="form-help">Equipment ID cannot be changed</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="rfid_tag_id">RFID Tag ID <span class="required">*</span></label>
                        <input type="text" id="rfid_tag_id" name="rfid_tag_id" value="<?php echo htmlspecialchars($equipment['rfid_tag_id']); ?>" required>
                        <div class="form-help">Unique identifier for the RFID tag</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="name">Equipment Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($equipment['name']); ?>" required>
                        <div class="form-help">Enter a descriptive name for the equipment</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($equipment['description']); ?></textarea>
                        <div class="form-help">Additional details about the equipment</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($equipment['location']); ?>">
                        <div class="form-help">Current location of the equipment</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status <span class="required">*</span></label>
                        <select id="status" name="status" required>
                            <option type="text" value="available" <?php echo $equipment['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                            <option type="text" value="borrowed" <?php echo $equipment['status'] == 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                            <option type="text" value="maintenance" <?php echo $equipment['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                        <div class="form-help">Current status of the equipment</div>
                    </div>
                    
                    <div class="form-actions">
                        <a href="admin.php" class="btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i>
                            Update Equipment
                        </button>
                    </div>
                </form>
            </div>
        <?php elseif (empty($message)): ?>
            <div class="message error">
                <i class="fas fa-exclamation-circle"></i>
                Equipment not found or no ID specified.
            </div>
            <div class="form-actions">
                <a href="admin.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Add real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const rfidInput = document.getElementById('rfid_tag_id');
            const nameInput = document.getElementById('name');
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Clear previous error styles
                rfidInput.style.borderColor = '';
                nameInput.style.borderColor = '';
                
                // Validate RFID Tag ID
                if (!rfidInput.value.trim()) {
                    rfidInput.style.borderColor = '#e74c3c';
                    valid = false;
                }
                
                // Validate Name
                if (!nameInput.value.trim()) {
                    nameInput.style.borderColor = '#e74c3c';
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        });
    </script>
</body>
</html>