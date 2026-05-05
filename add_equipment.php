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

$message = '';
$message_type = '';

try {
    $pdo = Database::connect();
     $stmt = $pdo->query("SELECT MAX(equipment_id) FROM equipment");
     $max_id = $stmt->fetchColumn();
     $stmt = $pdo->query("SELECT pg_get_serial_sequence('equipment', 'equipment_id')");
     $seq_name = $stmt->fetchColumn();
     $reset_sql = "SELECT setval('$seq_name', $max_id, true)";
    $stmt = $pdo->query($reset_sql);
    $new_val = $stmt->fetchColumn();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
            // Check if RFID tag already exists
            $check_stmt = $pdo->prepare("SELECT equipment_id FROM equipment WHERE rfid_tag_id = ?");
            $check_stmt->execute([$rfid_tag_id]);
            
            if ($check_stmt->rowCount() > 0) {
                $message = "RFID Tag ID already exists! Please use a different one.";
                $message_type = "error";
            } else {
                // Insert new equipment
                $insert_stmt = $pdo->prepare("INSERT INTO equipment (rfid_tag_id, name, description, location, status) VALUES (?, ?, ?, ?, ?)");
                $insert_stmt->execute([$rfid_tag_id, $name, $description, $location, $status]);
                
                if ($insert_stmt->rowCount() > 0) {
                    $_SESSION['message'] = "Equipment added successfully!";
                    $_SESSION['message_type'] = "success";
                    header("Location: admin.php");
                    exit();
                } else {
                    $message = "Failed to add equipment. Please try again.";
                    $message_type = "error";
                }
            }
        }
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
    <title>Add New Equipment - Admin Dashboard</title>
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
            border-color: #2ecc71;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
        }
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .btn-primary {
            background-color: #2ecc71;
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
            background-color: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
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
        .form-title {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2ecc71;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="welcome">
                <i class="fas fa-plus-circle"></i>
                Add New Equipment
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

        <div class="form-container">
            <h2 class="form-title">
                <i class="fas fa-tools"></i>
                Equipment Information
            </h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="rfid_tag_id">RFID Tag ID <span class="required">*</span></label>
                    <input type="text" id="rfid_tag_id" name="rfid_tag_id" value="<?php echo isset($_POST['rfid_tag_id']) ? htmlspecialchars($_POST['rfid_tag_id']) : ''; ?>" required placeholder="e.g., C50201010001">
                    <div class="form-help">Unique identifier for the RFID tag. This cannot be changed later.</div>
                </div>
                
                <div class="form-group">
                    <label for="name">Equipment Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required placeholder="e.g., Digital Multimeter">
                    <div class="form-help">Enter a descriptive name for the equipment</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Enter equipment specifications, model number, or any additional details..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <div class="form-help">Additional details about the equipment (optional)</div>
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" placeholder="e.g., Lab C5-201">
                    <div class="form-help">Current location of the equipment (optional)</div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status <span class="required">*</span></label>
                    <select id="status" name="status" required>
                        <option type="text" value="available" <?php echo (isset($_POST['status']) && $_POST['status'] == 'available') ? 'selected' : ''; ?>>Available</option>
                        <option type="text" value="borrowed" <?php echo (isset($_POST['status']) && $_POST['status'] == 'borrowed') ? 'selected' : ''; ?>>Borrowed</option>
                        <option type="text" value="maintenance" <?php echo (isset($_POST['status']) && $_POST['status'] == 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                    <div class="form-help">Initial status of the equipment</div>
                </div>
                
                <div class="form-actions">
                    <a href="admin.php" class="btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        Add Equipment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add real-time validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const rfidInput = document.getElementById('rfid_tag_id');
            const nameInput = document.getElementById('name');
            
            // Auto-uppercase for RFID Tag ID
            rfidInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
            
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Clear previous error styles
                rfidInput.style.borderColor = '';
                nameInput.style.borderColor = '';
                
                // Validate RFID Tag ID
                if (!rfidInput.value.trim()) {
                    rfidInput.style.borderColor = '#e74c3c';
                    valid = false;
                } else if (rfidInput.value.length < 3) {
                    rfidInput.style.borderColor = '#e74c3c';
                    valid = false;
                    alert('RFID Tag ID must be at least 3 characters long.');
                }
                
                // Validate Name
                if (!nameInput.value.trim()) {
                    nameInput.style.borderColor = '#e74c3c';
                    valid = false;
                }
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>