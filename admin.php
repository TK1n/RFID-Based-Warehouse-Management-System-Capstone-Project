<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

/* =======================
   AUTH CHECK
======================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

/* =======================
   DB CONNECTION
======================= */
$databaseFile = __DIR__ . '/server/api/Database.php';
require_once $databaseFile;

try {
    $pdo = Database::connect();

    /* =======================
       DELETE EQUIPMENT
    ======================= */
    if (isset($_GET['delete_id'])) {
        $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
        $stmt->execute([$_GET['delete_id']]);

        $_SESSION['message'] = "Equipment deleted successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: admin.php");
        exit();
    }

    /* =======================
       FETCH DATA
    ======================= */
    $equipment = $pdo->query(
        "SELECT * FROM equipment ORDER BY equipment_id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $transactions = $pdo->query(
        "SELECT * FROM transaction_details ORDER BY transaction_id DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

/* =======================
   LOGOUT
======================= */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>

<link rel="stylesheet" href="assets/css/styles_admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
<div class="container">

<!-- =======================
     HEADER
======================= -->
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
                <i class="fas fa-plus"></i> Add Equipment
            </a>

            <a href="?logout=1" class="logout-btn">
                <i class="fas fa-power-off"></i>
            </a>
        </div>
    </div>
</div>

<!-- =======================
     FLASH MESSAGE
======================= -->
<?php if (isset($_SESSION['message'])): ?>
<div class="message <?= $_SESSION['message_type']; ?>">
    <?= $_SESSION['message']; unset($_SESSION['message'], $_SESSION['message_type']); ?>
</div>
<?php endif; ?>

<!-- =======================
     TABS
======================= -->
<div class="tab-container">
    <div class="tab-buttons">
        <button class="tab-button active" onclick="showTab('equipment')">
            <i class="fas fa-tools"></i> Equipment
        </button>
        <button class="tab-button" onclick="showTab('transactions')">
            <i class="fas fa-history"></i> Transactions
        </button>
    </div>
</div>
<!-- =======================
     EQUIPMENT TAB
======================= -->
<div id="equipment-tab" class="tab-content active">
    <div class="table-container">
        <h2>All Equipment</h2>

        <table class="equipment-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>RFID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($equipment as $i => $e): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($e['rfid_tag_id']) ?></td>
                    <td><?= htmlspecialchars($e['name']) ?></td>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td><?= htmlspecialchars($e['location']) ?></td>
                    <td>
                        <?php
                            $statusClass = $e['status'] === 'borrowed'
                                ? 'status-borrowed'
                                : 'status-available';
                        ?>
                        <span class="<?= $statusClass ?>">
                            <?= ucfirst($e['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y g:i A', strtotime($e['created_at'])) ?></td>
                    <td class="action-buttons">
                        <button class="btn-edit"
                            onclick="location.href='edit_equipment.php?id=<?= $e['equipment_id'] ?>'">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-delete"
                            onclick="location.href='admin.php?delete_id=<?= $e['equipment_id'] ?>'">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =======================
     TRANSACTIONS TAB
======================= -->
<div id="transactions-tab" class="tab-content">
    <div class="table-container">
        <h2>Transaction History</h2>

        <table class="transaction-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>User</th>
                    <th>Equipment</th>
                    <th>RFID</th>
                    <th>Borrow Time</th>
                    <th>Due Time</th>
                    <th>Return Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $i => $t): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($t['student_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($t['equipment_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($t['equipment_tag'] ?? 'N/A') ?></td>
                    <td><?= $t['borrow_time'] ? date('M j, Y g:i A', strtotime($t['borrow_time'])) : 'N/A' ?></td>
                    <td><?= $t['due_time'] ? date('M j, Y g:i A', strtotime($t['due_time'])) : 'N/A' ?></td>
                    <td><?= $t['return_time'] ? date('M j, Y g:i A', strtotime($t['return_time'])) : 'Not returned' ?></td>
                    <td>
                        <?php
                            $statusClass = 'status-pending';
                            if ($t['status'] === 'borrowed') $statusClass = 'status-borrowed';
                            if ($t['status'] === 'returned') $statusClass = 'status-returned';
                        ?>
                        <span class="<?= $statusClass ?>">
                            <?= ucfirst($t['status']) ?>
                        </span>
                    </td>
                    <td class="action-buttons">
                    <?php if ($t['status'] === 'approving'): ?>
                        <button class="btn-accept"
                            onclick="acceptTransaction(<?= $t['transaction_id'] ?>)">
                            <i class="fas fa-check"></i> Accept
                        </button>

                        <button class="btn-delete"
                            onclick="deleteTransaction(<?= $t['transaction_id'] ?>)">
                            <i class="fas fa-trash"></i> Reject
                        </button>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div>

<!-- =======================
     JS
======================= -->
<script>
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.getElementById(name + '-tab').classList.add('active');

    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
}

function deleteTransaction(id) {
    if (!confirm("Reject this request and delete the transaction?")) return;

    fetch("server/api/transactions.php?id=" + id, { method: "DELETE" })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert("Transaction rejected.");
                location.reload();
            } else {
                alert(d.message || "Delete failed.");
            }
        })
        .catch(() => alert("Server error."));
}

function acceptTransaction(id) {
    if (!confirm("Approve this borrow request?")) return;

    fetch("server/api/transactions.php", {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: id, action: "approve" })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            alert("Transaction approved.");
            location.reload();
        } else {
            alert(d.message || "Approve failed.");
        }
    })
    .catch(() => alert("Server error."));
}
</script>

</body>
</html>
