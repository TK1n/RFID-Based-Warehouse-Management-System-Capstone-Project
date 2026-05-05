<?php
// =================================================================
// 0. AUTHENTICATION & SESSION MANAGEMENT
// =================================================================
session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

require_once(__DIR__. "/server/api/Database.php"); 
require_once(__DIR__. "/server/api/helpers.php"); 

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

if(isset($_POST['username']) && isset($_POST['password'])) {
    if (!checkLoginRateLimit()) {
        $_SESSION['login_error'] = "Too many login attempts. Please try again later.";
        header("Location: index.php");
        exit;
    }
    
    try {
        // Connect to PostgreSQL database for authentication
        $pdo = Database::connect();
        
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password))
        if (!preg_match('/^\d+$/', $username))

        if (empty($username) || empty($password)) {
            $_SESSION['login_error'] = "ID number and password are required";
            header("Location: index.php");
            exit;
        }

        if (!preg_match('/^\d+$/', $username)) {
            $_SESSION['login_error'] = "Invalid ID number format";
            header("Location: index.php");
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT user_id, name, id_number, role, passwords 
            FROM users 
            WHERE id_number = :username
            LIMIT 1
        ");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $login_successful = false;

        if ($user) {
            if (strpos($user['passwords'], '$2y$') === 0) {
                $login_successful = verifyPassword($password, $user['passwords']);
            
                if ($login_successful && passwordNeedsRehash($user['passwords'])) {
                    $newHash = hashPassword($password);
                    $updateStmt = $pdo->prepare("UPDATE users SET passwords = :newHash WHERE user_id = :user_id");
                    $updateStmt->bindParam(':newHash', $newHash);
                    $updateStmt->bindParam(':user_id', $user['user_id']);
                    $updateStmt->execute();
            }
        } else {
            if ($user['passwords'] === $password) {
                    $login_successful = true;
                    $newHash = hashPassword($password);
                    $updateStmt = $pdo->prepare("UPDATE users SET passwords = :newHash WHERE user_id = :user_id");
                    $updateStmt->bindParam(':newHash', $newHash);
                    $updateStmt->bindParam(':user_id', $user['user_id']);
                    $updateStmt->execute();
            }
        }
        } else {
            // User doesn't exist - still verify a dummy password to prevent timing attacks
            $dummyHash = '$2y$10$dummyhashforsecurityprevention.dummy';
            verifyPassword($password, $dummyHash);
            $login_successful = false;
            // Simulate similar processing time
            usleep(rand(100000, 300000)); // 100-300ms delay
        }
        if ($user && $login_successful) {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user['id_number'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['session_id'] = session_id();
            
            //Redirect base on user type
            // if ($user['role'] == 'admin') {
            //     header("Location: admin.php");
            // exit();
            // } elseif ($user['role'] == 'student') {
            //     header("Location: index.php");
            //     exit();
            // }
            
            // Clear any failed login attempts
            clearFailedLoginAttempts();
            
            // Redirect based on role
            if($user['role'] == 'admin' || $user['role'] == 'manager') {
                header("Location: index.php");
                exit();
            }
            // Redirect to prevent form resubmission
            header("Location: index.php");
            exit;

        } else {
            // Log failed attempt
            logFailedLoginAttempt();
            $_SESSION['login_error'] = "Wrong ID number or password";
            header("Location: index.php");
            exit;
        }


    } catch (Exception $e) {
        $login_error = "Database connection error: " . $e->getMessage();
        header('Location: index.php'); 
        exit;
    }
}

function checkLoginRateLimit() {
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes in seconds
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['first_attempt_time'] = time();
        $_SESSION['last_attempt_time'] = time();
    }
    
    $current_time = time();
    $time_since_first_attempt = $current_time - $_SESSION['first_attempt_time'];
    $time_since_last_attempt = $current_time - $_SESSION['last_attempt_time'];
    
    // Reset attempts if lockout time has passed since first attempt
    if ($time_since_first_attempt > $lockout_time) {
        $_SESSION['login_attempts'] = 1;
        $_SESSION['first_attempt_time'] = $current_time;
        $_SESSION['last_attempt_time'] = $current_time;
        return true;
    }
    
    // Check if exceeded maximum attempts
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        return false;
    }
    
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt_time'] = $current_time;
    return true;
}

function logFailedLoginAttempt() {
    // Just increment the counter - actual logging happens in checkLoginRateLimit
    error_log("Failed login attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
}

function clearFailedLoginAttempts() {
    if (isset($_SESSION['login_attempts'])) {
        unset($_SESSION['login_attempts']);
        unset($_SESSION['first_attempt_time']);
        unset($_SESSION['last_attempt_time']);
    }
}

function validateSession() {
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

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    if (!validateSession()) {
        // Session is invalid, force logout
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    // Update last activity time (optional - for session extension)
    $_SESSION['last_activity'] = time();
} 

// =================================================================
// 1. KHAI BÁO KẾT NỐI DATABASE VÀ CÁC HÀM XỬ LÝ
// =================================================================

/**
 * Function to display login form
 */
function showLoginModal($error = null) {
    ?>
    <!-- Login Modal -->
    <div id="loginModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: white; margin: 10% auto; padding: 30px; border-radius: 10px; width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <span class="close" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #aaa;">&times;</span>
            <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Login to Zenith RFID</h2>
            
            <?php if ($error): ?>
                <div class="error-message" style="color: red; background: #ffe6e6; padding: 10px; border: 1px solid red; border-radius: 5px; margin-bottom: 15px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="post" id="loginForm">
                <input type="text" name="username" placeholder="Student/Staff ID Number" required 
                       style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                <input type="password" name="password" placeholder="Password" required 
                       style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box;">
                <button type="submit" style="width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;">
                    Login
                </button>
            </form>
            <div style="text-align: center; color: #666; margin-top: 20px; font-size: 14px;">
                Use your HCMUT ID number and password
            </div>
        </div>
    </div>

    <script>
        // Modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('loginModal');
            const closeBtn = document.querySelector('.close');
            const loginBtn = document.getElementById('loginBtn');
            
            // Show modal when login button is clicked
            if (loginBtn) {
                loginBtn.addEventListener('click', function() {
                    modal.style.display = 'block';
                });
            }
            
            // Close modal when X is clicked
            if (closeBtn) {
                closeBtn.addEventListener('click', function() {
                    modal.style.display = 'none';
                });
            }
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Auto-show modal if there's an error
            <?php if (isset($_SESSION['login_error'])): ?>
                modal.style.display = 'block';
            <?php 
                unset($_SESSION['login_error']); // Clear error after showing
            endif; ?>
        });
    </script>
    <?php
}

// =================================================================
/**
 * Hàm lấy toàn bộ thiết bị và tạo Map (bảng ánh xạ) theo RFID Tag ID
 */

function getEquipmentMap($pdo) {
    try {
        $stmt = $pdo->query("SELECT rfid_tag_id, name, description, status, location FROM equipment");
        $all_equipment = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $equipment_map = [];
        foreach ($all_equipment as $equip) {
            $equipment_map[trim($equip['rfid_tag_id'])] = $equip;
        }
        return $equipment_map;

    } catch (PDOException $e) {
        error_log("getEquipmentMap Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Hàm quét thư mục scan_logs, tìm file mới nhất
 */
function getScannedDeviceIDs() {
    $log_dir = 'scan_logs/';
    $latest_file = '';
    $latest_time = 0;
    $device_ids = [];

    if (is_dir($log_dir)) {
        $files = glob($log_dir . 'tags_scan_*.txt');
        foreach ($files as $file) {
            $file_time = filemtime($file);
            if ($file_time > $latest_time) {
                $latest_time = $file_time;
                $latest_file = $file;
            }
        }
    }

    if ($latest_file && file_exists($latest_file)) {
        $raw_data = file($latest_file);
        if ($raw_data !== false) {
            $device_ids = array_map('trim', $raw_data);
            $device_ids = array_filter($device_ids, 'strlen');
            $device_ids = array_unique($device_ids);
        }
    }
    
    return ['ids' => $device_ids, 'file' => $latest_file];
}

/**
 * HÀM MỚI: Xóa các RFID tag ID khỏi file scan nếu thiết bị đang borrowed
 */
function removeBorrowedTagsFromScanFile($scan_file, $equipmentMap) {
    if (!$scan_file || !file_exists($scan_file)) {
        return false;
    }
    
    try {
        // Đọc toàn bộ nội dung file
        $lines = file($scan_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }
        
        // Lọc ra chỉ các dòng không phải thiết bị borrowed
        $filtered_lines = [];
        foreach ($lines as $line) {
            $tag_id = trim($line);
            if (empty($tag_id)) continue;
            
            $device_info = $equipmentMap[$tag_id] ?? null;
            // CHỈ giữ lại nếu thiết bị không tồn tại hoặc không phải borrowed
            if (!$device_info || strtolower($device_info['status']) !== 'borrowed') {
                $filtered_lines[] = $tag_id;
            }
        }
        
        // Ghi lại file với các dòng đã lọc
        $result = file_put_contents($scan_file, implode(PHP_EOL, $filtered_lines) . PHP_EOL);
        return $result !== false;
        
    } catch (Exception $e) {
        error_log("removeBorrowedTagsFromScanFile Error: " . $e->getMessage());
        return false;
    }
}

/**
 * HÀM SỬA ĐỔI: Cập nhật trạng thái của tất cả thiết bị được quét
 * - Nếu thiết bị đang borrowed: KHÔNG làm gì cả (giữ nguyên trạng thái borrowed)
 * - Nếu thiết bị đang available/maintenance: Cập nhật thành available (để đảm bảo consistency)
 */
function updateScannedDevicesStatus($pdo, $tag_ids, $equipmentMap) {
    if (empty($tag_ids)) {
        return 0;
    }
    try {
        $pdo->beginTransaction();
        
        $rows_affected = 0;
        
        foreach ($tag_ids as $tag_id) {
            $device_info = $equipmentMap[$tag_id] ?? null;
            
            if ($device_info) {
                // Nếu thiết bị đang borrowed, KHÔNG cập nhật - giữ nguyên trạng thái
                if (strtolower($device_info['status']) === 'borrowed') {
                    continue;
                }
                
                // Nếu thiết bị đang available hoặc maintenance, cập nhật thành available
                $stmt = $pdo->prepare("
                    UPDATE equipment SET status = 'available' 
                    WHERE rfid_tag_id = ? 
                    AND status != 'borrowed'
                ");
                $stmt->execute([$tag_id]);
                $rows_affected += $stmt->rowCount();
            } else {
                // Thiết bị chưa tồn tại trong database, có thể tạo mới hoặc bỏ qua
                // Ở đây chúng ta bỏ qua để tránh tạo thiết bị không mong muốn
            }
        }

        $pdo->commit();
        return $rows_affected;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("updateScannedDevicesStatus Error: " . $e->getMessage());
        return -1;
    }
}

// =========================================================
// 5. THỰC THI LOGIC TRÊN MÁY CHỦ (Backend)
// =========================================================
$pdo = Database::connect();
$update_message = "";
$scanned_ids = [];
$latest_scan_file = "";

// 5.1. Lấy ID từ file scan
$scanData = getScannedDeviceIDs();
$scanned_ids = $scanData['ids'];
$latest_scan_file = $scanData['file'];

// 5.2. Lấy map thiết bị (TRƯỚC KHI cập nhật)
$equipmentMap = getEquipmentMap($pdo);

// 5.3. XÓA CÁC THIẾT BỊ BORROWED KHỎI FILE SCAN
$file_cleaned = false;
if (!empty($scanned_ids) && $latest_scan_file) {
    $file_cleaned = removeBorrowedTagsFromScanFile($latest_scan_file, $equipmentMap);
}

// 5.4. CẬP NHẬT TRẠNG THÁI THIẾT BỊ (chỉ các thiết bị không phải borrowed)
if (!empty($scanned_ids)) {
    // Gọi hàm cập nhật - CHỈ cập nhật thiết bị không phải 'borrowed'
    $rows_updated = updateScannedDevicesStatus($pdo, $scanned_ids, $equipmentMap);
    
    // Tạo thông báo cho người dùng
    if ($rows_updated > 0) {
        $update_message = "Đã tự động cập nhật trạng thái của $rows_updated thiết bị thành 'available'.";
        if ($file_cleaned) {
            $update_message .= " Đã loại bỏ các thiết bị đang được mượn khỏi danh sách quét.";
        }
    } else if ($rows_updated === -1) {
        $update_message = "LỖI: Không thể cập nhật trạng thái thiết bị. Vui lòng kiểm tra log.";
    } else {
        $update_message = "Không có thiết bị nào cần cập nhật (Tất cả thiết bị được quét đã ở trạng thái 'available' hoặc đang được mượn).";
        if ($file_cleaned) {
            $update_message .= " Đã loại bỏ các thiết bị đang được mượn khỏi danh sách quét.";
        }
    }
}

// 5.5. Lấy lại map thiết bị (SAU KHI ĐÃ CẬP NHẬT) để có dữ liệu mới nhất
$equipmentMap = getEquipmentMap($pdo);

// 5.6. Lấy lại danh sách scan SAU KHI đã xóa các thiết bị borrowed
$scanData = getScannedDeviceIDs();
$scanned_ids = $scanData['ids'];
$latest_scan_file = $scanData['file'];

// 5.7. LỌC CHỈ HIỂN THỊ THIẾT BỊ CÓ SẴN (available)
$available_devices = [];
$unique_types = [];     // Mảng chứa tên các loại thiết bị (để làm filter)
$unique_locations = []; // Mảng chứa các vị trí (để làm filter)

if (!empty($scanned_ids)) {
    foreach ($scanned_ids as $device_id) {
        $device_info = $equipmentMap[$device_id] ?? null;

        if ($device_info && strtolower($device_info['status']) === 'available') {
            $available_devices[$device_id] = $device_info;

            // --- Code Mới Thêm ---
            // Thu thập dữ liệu cho Filter
            $d_name = $device_info['name'] ?? 'Unknown';
            $d_loc = $device_info['location'] ?? 'Unknown';

            // Nếu chưa có trong mảng thì thêm vào
            if (!in_array($d_name, $unique_types)) $unique_types[] = $d_name;
            if (!in_array($d_loc, $unique_locations)) $unique_locations[] = $d_loc;
            // ---------------------
        }
    }
}
// Sắp xếp danh sách filter theo ABC để đẹp hơn
sort($unique_types);
sort($unique_locations);

?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="x-icon" href="images/rfid.jpg">
    <link rel="stylesheet" href="assets/css/styles.css">

    <link href='https://cdn.jsdelivr.net/npm/boxicons@2.0.5/css/boxicons.min.css' rel='stylesheet'>

    <title>Zenith</title>

    <style>
        .nav__logo {
            display: flex;         /* Sắp xếp logo và chữ "Zenith" theo hàng ngang */
            align-items: center;   /* Căn giữa theo chiều dọc */
            gap: 0.5rem;           /* Khoảng cách giữa logo và chữ */
            font-weight: var(--font-semi);
        }
        .nav__logo-img {
            height: 35px;          /* Đặt chiều cao logo cho vừa vặn */
            width: auto;
            border-radius: 4px;    /* Bo góc (tùy chọn) */
        }
        .nav__logo-text {
            color: var(--first-color); /* Màu xanh, đã định nghĩa trong CSS */
            font-size: 1.2rem;       /* Cỡ chữ "Zenith" */
        }
    </style>
</head>

<body>
    <!--===== HEADER =====-->
    <header class="l-header">
        <nav class="nav bd-grid">
            <div>
                <a href="#" class="nav__logo">
                    <img src="images/rfid.jpg" alt="RFID Logo" class="nav__logo-img">
                    <span class="nav__logo-text">Zenith</span>
                </a>
            </div>

            <div class="nav__menu" id="nav-menu">
                <ul class="nav__list">
                    <li class="nav__item"><a href="#home" class="nav__link active-link">Home</a></li>
                    <li class="nav__item"><a href="#about" class="nav__link">About</a></li>
                    <li class="nav__item"><a href="#devices" class="nav__link">Device List</a></li>
                    <li class="nav__item"><a href="#work" class="nav__link">Responsibility</a></li>
                    <li class="nav__item"><a href="#contact" class="nav__link">Registration</a></li>
                    <?php if (isset($_SESSION['loggedin'])): ?>
                        <li class="nav__item user-menu">
                            <a href="#" id="userWelcome" class="user-welcome" zz> 
                                Welcome, <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong> 
                            </a>
                            <div id="logoutDropdown" class="logout-dropdown">
                                <a href="?logout=1" class="logout-link">
                                    <i class='bx bx-log-out'></i> Logout
                                </a>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') : ?>
                                    <a href="admin.php" class="logout-link">
                                        <i class='bx bxs-data'></i> Admin
                                    </a>
                                <?php endif ?>
                            </div>
                        </li>
                    <?php else: ?>  <!-- Login button in menu -->
                        <li class="nav__item">
                            <button id="loginBtn" style="
                                background: transparent; 
                                color: black; 
                                border: 1px solid white; 
                                border-radius: 3px;
                                margin: -0.16rem 0 0 0; 
                                cursor: pointer;
                                font-size: .938rem;
                                font-weight: 600;
                                font-family: 'Poppins', sans-serif;
                            ">
                                Login
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="nav__toggle" id="nav-toggle">
                <i class='bx bx-menu'></i>
            </div>
        </nav>
    </header>
    <?php 
        $login_error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : null;
        showLoginModal($login_error); 
    ?>

    <main class="l-main">
        <!--===== HOME =====-->
        <section class="home bd-grid" id="home">
            <div class="home__data">
                <h1 class="home__title">This is<br> <span class="home__title-color">Zenith</span>, The RFID<br> Tracking System</h1>

                <a href="https://mybk.hcmut.edu.vn/my/index.action" class="button">Contact</a>
            </div>

            <div class="home__social">
                <a href="https://mybk.hcmut.edu.vn/my/index.action" class="home__social-icon"><i class='bx bxs-component'></i></a>
                <a href="" class="home__social-icon"><i class='bx bxl-twitter'></i></a>
                <a href="" class="home__social-icon"><i class='bx bxl-github'></i></a>
            </div>

            <div class="home__img">
                <svg class="home__blob" viewBox="0 0 479 467" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <mask id="mask0" mask-type="alpha">
                        <rect x="0" y="0" width="479" height="467" fill="white" />

                        <!-- 10 diagonal stripes -->
                        <g transform="rotate(45 239.5 233.5)">
                            <rect x="-300" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="-180" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="-60" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="60" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="180" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="300" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="420" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="540" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="660" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="780" y="-233.5" width="60" height="700" fill="black" />
                        </g>
                        <!-- <path d="M9.19024 145.964C34.0253 76.5814 114.865 54.7299 184.111 29.4823C245.804 6.98884 311.86 -14.9503 370.735 14.143C431.207 44.026 467.948 107.508 477.191 174.311C485.897 237.229 454.931 294.377 416.506 344.954C373.74 401.245 326.068 462.801 255.442 466.189C179.416 469.835 111.552 422.137 65.1576 361.805C17.4835 299.81 -17.1617 219.583 9.19024 145.964Z" /> -->
                    </mask>
                    <g mask="url(#mask0)">
                        <rect x="0" y="0" width="479" height="467" fill="white" />

                        <!-- 10 diagonal stripes -->
                        <g transform="rotate(45 239.5 233.5)">
                            <rect x="-300" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="-180" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="-60" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="60" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="180" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="300" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="420" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="540" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="660" y="-233.5" width="60" height="700" fill="black" />
                            <rect x="780" y="-233.5" width="60" height="700" fill="black" />
                        </g>
                        <!-- <path d="M9.19024 145.964C34.0253 76.5814 114.865 54.7299 184.111 29.4823C245.804 6.98884 311.86 -14.9503 370.735 14.143C431.207 44.026 467.948 107.508 477.191 174.311C485.897 237.229 454.931 294.377 416.506 344.954C373.74 401.245 326.068 462.801 255.442 466.189C179.416 469.835 111.552 422.137 65.1576 361.805C17.4835 299.81 -17.1617 219.583 9.19024 145.964Z" /> -->
                        <image class="home__blob-img" x="50" y="60" href="images/rfid.jpg" />
                    </g>
                </svg>
            </div>
        </section>

        <!--===== ABOUT =====-->
        <section class="about section " id="about">
            <h2 class="section-title" style="margin-bottom: 0px">ABOUT</h2>

            <div class="about__container bd-grid" style="padding-top: -2rem;">
                <div class="about__img">
                    <img src="images/rfid-system.png" alt="">
                    <img src="images/map.png" alt="">
                </div>

                <div>
                    <h2 class="about__subtitle">Introducing RFID Management System</h2>
                    <p class="about__text">An RFID (Radio-Frequency Identification) Management System is a transformative solution designed to bring unparalleled efficiency, accuracy, and security to laboratory environments. By using small, wireless tags and strategic readers, it automates the tracking and management of critical assets, samples, and personnel.</p>
                    <br>
                    <h2 class="about__subtitle">Lab equipment locations: A3, B9, C5</h2>
                    <p class="about__text">To ensure lab equipment is available, used safely, and returned in good condition for everyone.</p>
                </div>
            </div>
        </section>

        <!--===== DEVICES =====-->
        <section class="devices section" id="devices">
            <h2 class="section-title">DEVICES LIST</h2>

            <div class="devices__container bd-grid">
                <div class="devices__list-content" style="width: 100%;">
                    <!-- <h2 class="devices__subtitle">Available Devices in C5-201</h2> -->

                    <!-- Search and Sort Controls -->
                    <div class="device-controls" style="
                        display: flex;
                        flex-wrap: wrap;
                        gap: 15px;
                        margin-bottom: 20px;
                        align-items: center;
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 8px;
                        border: 1px solid #e9ecef;
                    ">
                        <!-- Search Input -->
                        <div class="search-container" style="flex: 1; min-width: 250px;">
                            <input
                                type="text"
                                id="deviceSearch"
                                placeholder="Search by device name or location..."
                                style="
                                    width: 100%;
                                    padding: 10px 15px;
                                    border: 1px solid #ddd;
                                    border-radius: 5px;
                                    font-size: 14px;
                                    box-sizing: border-box;
                                ">
                        </div>

                        <!-- Sort Dropdown -->
                        <div class="filter-container" style="flex: 1; min-width: 150px;">
                            <select id="filterType" style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; background: white; cursor: pointer;">
                                <option value="">All Device Types</option>
                                <?php foreach ($unique_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars(strtolower($type)); ?>">
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-container" style="flex: 1; min-width: 150px;">
                            <select id="filterLocation" style="width: 100%; padding: 10px 15px; border: 1px solid #ddd; border-radius: 5px; background: white; cursor: pointer;">
                                <option value="">All Locations</option>
                                <?php foreach ($unique_locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars(strtolower($loc)); ?>">
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Results Counter -->
                        <div class="results-counter" style="
                            color: #6c757d;
                            font-size: 14px;
                            font-weight: 500;
                        ">
                            <span id="resultsCount">0</span> devices found
                        </div>
                    </div>

                    <div class="device-grid" style="
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
                        gap: 20px;
                        margin-top: 30px;
                    ">
                        <?php
                        // Hiển thị chỉ các thiết bị có sẵn (available)
                        if (!empty($available_devices)) {
                            // Lặp qua chỉ các thiết bị available
                            $index = 0;
                            foreach ($available_devices as $device_id => $device_info) {
                                $index++;

                                // Định dạng thông tin hiển thị
                                $device_name = $device_info['name'] ?? '⚠️ CHƯA ĐĂNG KÝ';
                                $description = $device_info['description'] ?? 'Không có mô tả.';
                                $location_text = $device_info['location'] ?? 'N/A';
                                $status_text = $device_info['status'] ?? 'N/A';
                                $google_search_url = "webpage/device_information.php";
                                // Thiết kế Box hiển thị
                                echo '
                                    <div class="device-box" style="
                                        background-color: #ffffff;
                                        border: 1px solid #e0e0e0;
                                        border-radius: 10px;
                                        padding: 20px;
                                        box-shadow: 0 4px 10px rgba(0,0,0,0.08);
                                        display: flex;
                                        flex-direction: column;
                                        justify-content: space-between;
                                        min-height: 250px;
                                        position: relative;
                                        " data-name="' . htmlspecialchars(strtolower($device_name)) . '" data-location="' . htmlspecialchars(strtolower($location_text)) . '">
                                        <div class="device-info" style="text-align: center;">
                                            <h4 style="margin-top: 0; color: #333; font-size: 1.1em;">Device #' . $index . '</h4>
                                            <p class="device-name" style="font-weight: bold; font-size: 1.2em; color: #1e83f0; word-break: break-all; margin: 10px 0;">' . htmlspecialchars($device_name) . '</p>
                                            
                                            <div 
                                                onclick="copyToClipboard(\'' . htmlspecialchars($device_id) . '\', this)" 
                                                style="
                                                    cursor: pointer; 
                                                    background-color: #f1f3f4; 
                                                    padding: 8px 12px; 
                                                    border-radius: 5px; 
                                                    display: inline-block; 
                                                    transition: background-color 0.2s;
                                                    border: 1px dashed #ccc;
                                                "
                                                title="Click to copy ID"
                                                onmouseover="this.style.backgroundColor=\'#e0e0e0\'"
                                                onmouseout="this.style.backgroundColor=\'#f1f3f4\'"
                                            >
                                                <p style="font-size: 0.85em; color: #555; margin: 0;">
                                                    ID: <strong>' . htmlspecialchars($device_id) . '</strong> <i class="bx bx-copy" style="margin-left: 5px;"></i>
                                                </p>
                                            </div>
                                            <span class="copy-feedback" style="
                                                display: none; 
                                                color: green; 
                                                font-size: 0.8em; 
                                                margin-top: 5px; 
                                                font-weight: bold;
                                            ">Copied!</span>
                                            
                                        <div style="margin-top: 12px;">
                                            <button onclick="quickBorrow(\'' . htmlspecialchars($device_id) . '\')" 
                                                style="
                                                    background-color: #007bff; 
                                                    color: white; 
                                                    border: none; 
                                                    padding: 8px 15px; 
                                                    border-radius: 5px; 
                                                    cursor: pointer; 
                                                    font-size: 0.9em;
                                                    display: inline-flex;
                                                    align-items: center;
                                                    gap: 5px;
                                                    transition: background-color 0.2s;
                                                "
                                                onmouseover="this.style.backgroundColor=\'#0056b3\'"
                                                onmouseout="this.style.backgroundColor=\'#007bff\'"
                                            >
                                                <i class="bx bx-paper-plane"></i> Quick Borrow
                                            </button>
                                        </div>    
                                    </div>
                                        
                                    <div class="device-details" style="width: 100%; text-align: left; margin-top: 15px;">
                                        <p class="device-location" style="font-size: 0.9em; margin-bottom: 5px; color: #555;"><strong>Location: </strong> ' . htmlspecialchars($location_text) . '</p>
                                        <p style="font-size: 0.9em; margin-bottom: 5px; color: #555;"><strong>Description:</strong> ' . htmlspecialchars(substr($description, 0, 50)) . (strlen($description) > 50 ? '...' : '') . '</p>
                                        <p style="font-size: 0.9em; margin-bottom: 0px;"><strong>Status:</strong> <span style="font-weight: bold; color: green;">' . strtoupper(htmlspecialchars($status_text)) . '</span></p>
                                    </div>
                                </div>
                                ';
                            }
                        } else {
                            echo '<p style="grid-column: 1 / -1; color: #555;">Không có thiết bị nào có sẵn để mượn trong lần quét gần nhất. Vui lòng thực hiện quét lại hoặc kiểm tra trạng thái thiết bị.</p>';
                        }
                        ?>
                    </div>

                    <!-- No Results Message -->
                    <div id="noResults" style="
                        display: none;
                        text-align: center;
                        padding: 40px;
                        color: #6c757d;
                        font-size: 16px;
                        grid-column: 1 / -1;
                    ">
                        No devices found matching your search criteria.
                    </div>
                </div>
            </div>
        </section>

        <!--===== RESPONSIBILITY =====-->
        <section class="work section" id="work">
            <h2 class="section-title">RESPONSIBILITY</h2>
            <div class="container">
                <div class="dashboard">
                    <div class="sidebar">
                        <ul class="nav-menu">
                            <li class="nav-item">
                                <a href="index.php#contact" class="nav-link active">
                                    <span class="nav-icon">📋</span>
                                    <span>Borrow Equipment</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="https://sso.hcmut.edu.vn/cas/login?service=https%3A%2F%2Flms.hcmut.edu.vn%2Flogin%2Findex.php%3FauthCAS%3DCAS" class="nav-link">
                                    <span class="nav-icon">📦</span>
                                    <span>MyBK-BK-elearing</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="webpage/device_information.php" class="nav-link">
                                    <span class="nav-icon">🔍</span>
                                    <span>Equipment Catalog</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#" class="nav-link">
                                    <span class="nav-icon">⚙️</span>
                                    <span>Training</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="webpage/lab_policies.php" class="nav-link">
                                    <span class="nav-icon">📊</span>
                                    <span>Lab Policies</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="main-content">
                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Borrow Lab Equipment</h2>
                            </div>

                            <div class="step-indicator">
                                <div class="step completed">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Check Availability</div>
                                </div>
                                <div class="step active">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Fill Registration Form</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Submit</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">4</div>
                                    <div class="step-label">Receive Comfirmation</div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h2 class="card-title">Student Responsibilities</h2>
                            </div>

                            <ul class="responsibility-list">
                                <li class="responsibility-item">
                                    <span class="checkmark">✓</span>
                                    <span>Get Trained & Authorized</span>
                                </li>
                                <li class="responsibility-item">
                                    <span class="checkmark">✓</span>
                                    <span>Reserve in Advance</span>
                                </li>
                                <li class="responsibility-item">
                                    <span class="checkmark">✓</span>
                                    <span>Inspect & Note Condition</span>
                                </li>
                                <li class="responsibility-item">
                                    <span class="checkmark">✓</span>
                                    <span>Use Safely & Correctly</span>
                                </li>
                                <li class="responsibility-item">
                                    <span class="checkmark">✓</span>
                                    <span>Return On Time, Clean & Complete</span>
                                </li>
                                <li class="responsibility-item">
                                    <span class="checkmark">✓</span>
                                    <span>Report Problems Immediately</span>
                                </li>
                            </ul>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!--===== CONTACT =====-->
        <!-- Custom Popup -->
        <div class="popup-overlay" id="popupOverlay">
            <div class="popup" id="popup">
                <div id="popupContent">
                    <!-- Popup content will be inserted here -->
                </div>
                <button class="popup-btn" onclick="closePopup()">OK</button>
            </div>
        </div>

        <section class="contact section" id="contact">
            <h2 class="section-title">REGISTRATION</h2>
            
            <?php if (isset($_SESSION['loggedin'])): ?>
            <div class="contact__container bd-grid">
                <form action="server/connect.php" method="POST" id="registrationForm" class="contact__form">
                    <!-- Device ID input -->
                    <input type="text" placeholder="Device ID" class="contact__input" id="device_id" name="device_id" required>
                    
                    <!-- Transaction Type Dropdown -->
                    <label for="transaction_type" class="form-label">Transaction Type</label>
                    <select id="transaction_type" name="transaction_type" class="contact__input" required>
                        <option value="">Select Type</option>
                        <option value="borrow">Borrow</option>
                        <option value="return">Return</option>
                    </select>

                    <!-- Single Date input -->
                    <label for="transaction-date" class="form-label">Date</label>
                    <input type="date" id="transaction-date" name="transaction-date" class="contact__input" value="<?php echo date('Y-m-d'); ?>" required>
                    
                    <!-- Terms agreement -->
                    <label for="terms" class="required">
                        <input type="checkbox" id="terms" required>
                        I agree to the <a href="#">terms and conditions</a> of device borrowing
                    </label>
                    <button type="submit" name="save" class="contact__button button">Send</button>
                </form>
            </div>
            <?php else: ?>
                <!-- ===== LOGIN PROMPT SECTION ===== -->
                <div class="contact__container bd-grid" style="display: flex; justify-content: center;">
                <div class="login-prompt" style="width: 100%; text-align: center;">
                        <h3>Please Login to Access Registration</h3>
                        <p>You need to be logged in to register for equipment borrowing.</p>

                        <button id="loginBtnRegistration" class="button" style="background: #007bff; color: white; border: none; padding: 12px 30px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 36px;">
                            Login Now
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Lấy các phần tử DOM mới
                const searchInput = document.getElementById('deviceSearch');
                const filterType = document.getElementById('filterType'); // Menu Filter Type
                const filterLocation = document.getElementById('filterLocation'); // Menu Filter Location
                const deviceBoxes = document.querySelectorAll('.device-box');
                const noResults = document.getElementById('noResults');
                const resultsCount = document.getElementById('resultsCount');

                // Khởi tạo bộ đếm
                updateResultsCount(deviceBoxes.length);

                // Lắng nghe sự kiện thay đổi trên cả 3 ô input
                searchInput.addEventListener('input', filterDevices);
                filterType.addEventListener('change', filterDevices);
                filterLocation.addEventListener('change', filterDevices);

                function filterDevices() {
                    // Lấy giá trị hiện tại của 3 bộ lọc
                    const searchTerm = searchInput.value.toLowerCase().trim();
                    const typeValue = filterType.value.toLowerCase();
                    const locationValue = filterLocation.value.toLowerCase();

                    let visibleCount = 0;

                    deviceBoxes.forEach(box => {
                        // Lấy dữ liệu từ thẻ HTML (data-name và data-location)
                        const name = box.getAttribute('data-name');
                        const location = box.getAttribute('data-location');

                        // 1. Kiểm tra Search (Nếu khớp hoặc ô tìm kiếm rỗng)
                        const matchesSearch = (searchTerm === '') ||
                            name.includes(searchTerm) ||
                            location.includes(searchTerm);

                        // 2. Kiểm tra Type Filter (Nếu khớp hoặc chọn "All")
                        const matchesType = (typeValue === '') || (name === typeValue);

                        // 3. Kiểm tra Location Filter (Nếu khớp hoặc chọn "All")
                        const matchesLocation = (locationValue === '') || (location === locationValue);

                        // Chỉ hiển thị nếu thỏa mãn TẤT CẢ 3 điều kiện (AND)
                        if (matchesSearch && matchesType && matchesLocation) {
                            box.style.display = "flex";
                            visibleCount++;
                        } else {
                            box.style.display = "none";
                        }
                    });

                    // Cập nhật giao diện
                    updateResultsCount(visibleCount);

                    if (visibleCount === 0) {
                        noResults.style.display = 'block';
                    } else {
                        noResults.style.display = 'none';
                    }
                }

                function updateResultsCount(count) {
                    resultsCount.textContent = count;
                }
            });
        </script>

        <script>
            // Add event listener for the login button in registration section
            document.addEventListener('DOMContentLoaded', function() {
                const loginBtnRegistration = document.getElementById('loginBtnRegistration');
                const loginBtn = document.getElementById('loginBtn');
                
                if (loginBtnRegistration) {
                    loginBtnRegistration.addEventListener('click', function() {
                        // Trigger the existing login modal
                        if (loginBtn) {
                            loginBtn.click();
                        }
                    });
                }
            });
        </script>

        <script>
            // Check URL parameters and show popup
            document.addEventListener('DOMContentLoaded', function() {
                const urlParams = new URLSearchParams(window.location.search);
                const success = urlParams.get('success');
                const error = urlParams.get('error');

                if (success === '1') {
                    showPopup('success', 'Registration submitted successfully!');
                    // Clear URL parameters
                    window.history.replaceState({}, document.title, window.location.pathname);
                }

                if (error) {
                    showPopup('error', 'Error: ' + decodeURIComponent(error));
                    // Clear URL parameters
                    window.history.replaceState({}, document.title, window.location.pathname);
                }
                // Update the date validation for the single date field
                const today = new Date().toISOString().split('T')[0];
                const transactionDateInput = document.getElementById('transaction-date');

                if (transactionDateInput) {
                    transactionDateInput.min = today;
                }

            });

            function showPopup(type, message) {
                const popupOverlay = document.getElementById('popupOverlay');
                const popup = document.getElementById('popup');
                const popupContent = document.getElementById('popupContent');

                // Set popup content and style
                popupContent.innerHTML = '<h3>' + (type === 'success' ? 'Success!' : 'Error') + '</h3><p>' + message + '</p>';
                popup.className = 'popup ' + type;

                // Show popup
                popupOverlay.style.display = 'block';
            }

            function closePopup() {
                document.getElementById('popupOverlay').style.display = 'none';
            }

            // Close popup when clicking outside
            document.getElementById('popupOverlay').addEventListener('click', function(e) {
                if (e.target === this) {
                    closePopup();
                }
            });
        </script>
    </main>

    <!--===== FOOTER =====-->
    <footer class="footer">
        <p class="footer__title">Zenith, RFID Based Lab Management Solution</p>
        <div class="footer__social">
            <a href="#" class="footer__icon"><i class='bx bxl-facebook'></i></a>
            <a href="#" class="footer__icon"><i class='bx bxl-instagram'></i></a>
            <a href="#" class="footer__icon"><i class='bx bxl-twitter'></i></a>
        </div>
        <p class="footer__copy">&#169; CapstoneProject HK251. All rigths reserved</p>
    </footer>

    <!--===== SCROLL REVEAL =====-->
    <script src="https://unpkg.com/scrollreveal"></script>

    <!--===== MAIN JS =====-->
    <script src="assets/js/main.js"></script>
    
    <!--===== COPY ID =====-->
    <script>
        // Hàm copy ID vào clipboard
        function copyToClipboard(text, element) {
            // Sử dụng Clipboard API
            navigator.clipboard.writeText(text).then(function() {
                // Hiệu ứng phản hồi người dùng
                const feedback = element.nextElementSibling; // Lấy thẻ span.copy-feedback ngay sau div click
                
                if (feedback) {
                    feedback.style.display = 'block';
                    // Ẩn thông báo sau 1.5 giây
                    setTimeout(function() {
                        feedback.style.display = 'none';
                    }, 1500);
                }
                
                // (Tùy chọn) Đổi màu nền tạm thời để báo hiệu
                const originalBg = element.style.backgroundColor;
                element.style.backgroundColor = '#d4edda'; // Màu xanh nhạt
                element.style.borderColor = '#28a745';
                
                setTimeout(function() {
                    element.style.backgroundColor = originalBg;
                    element.style.borderColor = '#ccc';
                }, 300);

            }, function(err) {
                console.error('Could not copy text: ', err);
                showPopup('error', 'Không thể copy ID. Vui lòng thử thủ công.');
            });
        }
    </script>
    <script>
    // Logout dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        const userWelcome = document.getElementById('userWelcome');
        const logoutDropdown = document.getElementById('logoutDropdown');
        
        if (userWelcome && logoutDropdown) {
            // Toggle dropdown when clicking on user welcome
            userWelcome.addEventListener('click', function(e) {
                e.preventDefault();
                const isVisible = logoutDropdown.style.display === 'block';
                logoutDropdown.style.display = isVisible ? 'none' : 'block';
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userWelcome.contains(e.target) && !logoutDropdown.contains(e.target)) {
                    logoutDropdown.style.display = 'none';
                }
            });
        }
    });
    </script>
    <script>
        function quickBorrow(deviceId) {
            // 1. Kiểm tra xem form đăng ký có tồn tại không (người dùng đã login chưa?)
            const form = document.getElementById('registrationForm');
            
            if (!form) {
                // Nếu form không tồn tại nghĩa là chưa login
                // Gọi hàm showPopup báo lỗi hoặc kích hoạt nút login
                showPopup('error', 'Please Login to use Quick Borrow function!');
                
                // Tự động mở modal login (nếu nút login tồn tại)
                const loginBtn = document.getElementById('loginBtn');
                if (loginBtn) {
                    setTimeout(() => loginBtn.click(), 1500); // Đợi 1.5s để người dùng đọc thông báo rồi mở login
                }
                return;
            }

            // 2. Tự động điền thông tin
            // Device ID
            const deviceInput = document.getElementById('device_id');
            if (deviceInput) deviceInput.value = deviceId;

            // Transaction Type -> Borrow
            const typeSelect = document.getElementById('transaction_type');
            if (typeSelect) typeSelect.value = 'borrow';

            // Date -> Today
            const dateInput = document.getElementById('transaction-date');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0]; // Format YYYY-MM-DD
                dateInput.value = today;
            }

            // Terms -> Checked
            const termsCheckbox = document.getElementById('terms');
            if (termsCheckbox) termsCheckbox.checked = true;

            // 3. Hiệu ứng cuộn xuống form để người dùng thấy điều gì xảy ra (Optional)
            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });

            // 4. Submit form ngay lập tức
            // Dùng setTimeout nhỏ để đảm bảo các giá trị đã được gán và UI cập nhật kịp
            setTimeout(() => {
                if(confirm('Confirm borrowing device ' + deviceId + ' today?')) {
                    // Tạo một input hidden giả lập nút bấm 'save' để backend nhận diện được
                    // Vì hàm submit() thuần của JS không gửi kèm value của nút submit
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'save';
                    hiddenInput.value = '1';
                    form.appendChild(hiddenInput);
                    
                    form.submit();
                }
            }, 500);
        }
    </script>
</body>
</html>