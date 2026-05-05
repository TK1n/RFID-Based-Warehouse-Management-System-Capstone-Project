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

$databaseFile = __DIR__ . '/server/api/Database.php';
if (!file_exists($databaseFile)) {
    die("❌ Error: Database.php file not found at: " . realpath($databaseFile) . 
        "<br>Current directory: " . __DIR__);
}
require_once $databaseFile;

if (!class_exists('Database')) {
    die("❌ Error: Database class not found after including the file");
}

// Get database connection
try {
    $pdo = Database::connect();
    
    if (!$pdo) {
        die("❌ Error: Database::connect() returned null");
    }
    
    // Delete equipment
    if (isset($_GET['delete_id'])) {
        $delete_id = $_GET['delete_id'];
        
        // Prepare delete statement
        $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
        $stmt->execute([$delete_id]);
        
        // Set success message
        $_SESSION['message'] = "Equipment deleted successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect to avoid resubmission
        header("Location: admin.php");
        exit();
    }
    
    // Test the connection
    $pdo->query("SELECT 1");
    
    // Fetch all equipment data
    $stmt = $pdo->prepare("SELECT * FROM equipment ORDER BY equipment_id ASC");
    $stmt->execute();
    $equipment = $stmt->fetchAll();
    
    // Fetch all transaction history data
    $stmt = $pdo->prepare("SELECT * FROM transaction_history ORDER BY equipment_id ASC");
    $stmt->execute();
    $transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("❌ Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("❌ General error: " . $e->getMessage());
}

function validateSession()
{
    if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
        return false;
    }

    // Optional: Add session timeout (e.g., 2 hours)
    $session_timeout = 7200; // 2 hours in seconds
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
        session_destroy();
        return false;
    }

    // Optional: Validate session ID matches
    if (isset($_SESSION['session_id']) && $_SESSION['session_id'] !== session_id()) {
        session_destroy();
        return false;
    }

    return true;
}

if (isset($_GET['logout'])) {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Admin Dashboard - Equipment Management</title>
    <link rel="stylesheet" href="assets/css/styles_admin.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="brand-section">
                    <div class="brand-logo">
                        <i class="fas fa-microscope"></i>
                    </div>
                    <div class="brand-text">
                        <h1>Equipment Management System</h1>
                        <p>Laboratory Asset Tracking</p>
                    </div>
                </div>
                
                <div class="user-section">
                    <a href="index.php" class="main-pg">
                        <i class="fas fa-arrow-left"></i> Main Page
                    </a>
                    <a href="add_equipment.php" class="btn-add">
                        <i class="fas fa-plus"></i> Add New Equipment
                    </a>
                    <div class="user-profile">
                        <div class="avatar">
                            <i class="fas fa-user-cog"></i>
                        </div>
                        <div class="user-meta">
                            <strong>ADMINISTRATOR</strong>
                            <span>Super User</span>
                        </div>
                    </div>
                    <a href="?logout=1" class="logout-btn">
                        <i class="fas fa-power-off"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?php echo $_SESSION['message_type']; ?>">
                <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="showTab('equipment')">
                    <i class="fas fa-tools"></i> Equipment
                </button>
                <button class="tab-button" onclick="showTab('transactions')">
                    <i class="fas fa-history"></i> Transaction History
                </button>
            </div>
        </div>

        <!-- Equipment Tab Content -->
        <div id="equipment-tab" class="tab-content active">
            <div class="table-container">
                <h2>All Equipment</h2>
                <?php if (count($equipment) > 0): ?>
                    <table class="equipment-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>RFID Tag ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $rowNumber = 1;
                                foreach ($equipment as $item): ?>
                                <tr>
                                    <td class="row-number"><?php echo $rowNumber++; ?></td>
                                    <td><?php echo htmlspecialchars($item['rfid_tag_id']); ?></td>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                                    <td><?php echo htmlspecialchars($item['location']); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = '';
                                        switch($item['status']) {
                                            case 'available':
                                                $statusClass = 'status-available';
                                                break;
                                            case 'in-use':
                                                $statusClass = 'status-in-use';
                                                break;
                                            case 'maintenance':
                                                $statusClass = 'status-maintenance';
                                                break;
                                            default:
                                                $statusClass = 'status-available';
                                        }
                                        ?>
                                        <span class="<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars(ucfirst($item['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($item['created_at'])); ?></td>
                                    <td class="action-buttons">
                                        <button class="btn-edit" onclick="editEquipment(<?php echo $item['equipment_id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button class="btn-delete" onclick="showDeleteModal(<?php echo $item['equipment_id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No equipment found in the database.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Transactions Tab Content -->
        <div id="transactions-tab" class="tab-content">
            <div class="table-container">
                <h2>Transaction History</h2>
                <?php if (count($transactions) > 0): ?>
                    <table class="transaction-table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>User</th>
                                <th>Equipment</th>
                                <th>RFID Tag</th>
                                <th>Borrow Time</th>
                                <th>Due Time</th>
                                <th>Return Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                $transRowNumber = 1;
                                foreach ($transactions as $transaction): 
                                    // Determine status class
                                    $statusClass = '';
                                    $statusText = $transaction['status'];
                                    
                                    switch($transaction['status']) {
                                        case 'borrowed':
                                            $statusClass = 'status-borrowed';
                                            // Check if overdue
                                            if (!empty($transaction['due_time']) && strtotime($transaction['due_time']) < time() && empty($transaction['return_time'])) {
                                                $statusClass = 'status-overdue';
                                                $statusText = 'overdue';
                                            }
                                            break;
                                        case 'returned':
                                            $statusClass = 'status-returned';
                                            break;
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            break;
                                        default:
                                            $statusClass = 'status-pending';
                                    }
                            ?>
                                <tr>
                                    <td><?php echo $transRowNumber++; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['user_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['equipment_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['rfid_tag_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($transaction['borrow_time'])) {
                                            echo date('M j, Y g:i A', strtotime($transaction['borrow_time']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($transaction['due_time'])) {
                                            echo date('M j, Y g:i A', strtotime($transaction['due_time']));
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($transaction['return_time'])) {
                                            echo date('M j, Y g:i A', strtotime($transaction['return_time']));
                                        } else {
                                            echo 'Not returned';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars(ucfirst($statusText)); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No transaction history found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3 style="margin: 0;">Confirm Deletion</h3>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the following equipment?</p>
                <p><strong>Equipment:</strong> <span id="equipmentName"></span></p>
                <p class="message warning">
                    <i class="fas fa-exclamation-circle"></i>
                    This action cannot be undone. All data associated with this equipment will be permanently removed.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button class="btn btn-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>

    <script>
        let equipmentToDelete = null;
        let equipmentNameToDelete = null;

        function showDeleteModal(equipmentId, equipmentName) {
            equipmentToDelete = equipmentId;
            equipmentNameToDelete = equipmentName;
            
            document.getElementById('equipmentName').textContent = equipmentName;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            equipmentToDelete = null;
            equipmentNameToDelete = null;
        }

        function confirmDelete() {
            if (equipmentToDelete) {
                window.location.href = 'admin.php?delete_id=' + equipmentToDelete;
            }
        }

        function editEquipment(equipmentId) {
            window.location.href = 'edit_equipment.php?id=' + equipmentId;
        }

        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activate selected button
            event.target.classList.add('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>