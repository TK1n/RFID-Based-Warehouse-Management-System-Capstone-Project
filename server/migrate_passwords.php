<?php
// migrate_passwords.php
require_once("api/Database.php");
require_once("api/helpers.php");

try {
    $pdo = Database::connect();
    
    echo "Starting password migration...\n";
    
    // Get all users with plain text passwords
    $stmt = $pdo->query("SELECT user_id, passwords FROM users WHERE passwords IS NOT NULL");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $migrated_count = 0;
    $error_count = 0;
    
    foreach ($users as $user) {
        $plain_password = $user['passwords'];
        $user_id = $user['user_id'];
        
        // Check if password is already hashed (password_hash creates strings starting with $2y$)
        if (strpos($plain_password, '$2y$') === 0) {
            echo "User ID $user_id: Password already hashed\n";
            continue;
        }
        
        // Hash the plain text password
        $hashed_password = hashPassword($plain_password);
        
        // Update the user record
        $update_stmt = $pdo->prepare("UPDATE users SET passwords = :hashed_password WHERE user_id = :user_id");
        $update_stmt->bindParam(':hashed_password', $hashed_password);
        $update_stmt->bindParam(':user_id', $user_id);
        
        if ($update_stmt->execute()) {
            echo "User ID $user_id: Password successfully hashed\n";
            $migrated_count++;
        } else {
            echo "User ID $user_id: ERROR migrating password\n";
            $error_count++;
        }
    }
    
    echo "\nMigration completed:\n";
    echo "Successfully migrated: $migrated_count users\n";
    echo "Errors: $error_count users\n";
    echo "Total users processed: " . count($users) . "\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>