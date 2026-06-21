<?php
// db.php - Database connection and migration helper for MySQL

// Load environment configuration from .env file
function load_env($path) {
    if (!file_exists($path)) {
        return false;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments or empty lines
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            // Strip surrounding quotes if present
            $value = trim($value, '"\'');
            
            // Set variables
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
    return true;
}

// Execute environment loader
load_env(dirname(__DIR__) . '/.env');

// Retrieve settings from environment variables with safe defaults
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'byc_church';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root';
$charset = getenv('DB_CHARSET') ?: 'utf8mb4';
$port = getenv('DB_PORT') ?: '3306';

$app_env = getenv('APP_ENV') ?: 'local';
$is_dev = ($app_env === 'local' || $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

// Configure PHP error reporting dynamically
if ($is_dev) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/php_errors.log');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

try {
    // 1. Connect to MySQL server (without specifying DB name to auto-create it if absent)
    $pdo = new PDO("mysql:host=$host;port=$port;charset=$charset", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); // Allow running multiple statements in schema.sql
    
    // Auto-create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE {$charset}_general_ci");
    
    // 2. Select the database
    $pdo->exec("USE `$dbname`");
    
    // Check if tables already exist by verifying one of the core tables
    $tables_exist = false;
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'members'");
        if ($stmt->rowCount() > 0) {
            $tables_exist = true;
        }
    } catch (PDOException $e) {
        $tables_exist = false;
    }

    if (!$tables_exist) {
        // Load and execute schema.sql
        $schema_file = dirname(__DIR__) . '/database/schema.sql';
        if (file_exists($schema_file)) {
            $sql = file_get_contents($schema_file);
            $pdo->exec($sql);
            
            // Seed default members
            $pdo->exec("INSERT INTO members (name, phone, email, address, birthday, gender, join_date, department_id) VALUES 
                ('Alesha Cole', '0241112222', 'alesha@byc.org', 'Accra, Ghana', '1998-05-12', 'Female', '2024-01-10', 1),
                ('David Boateng', '0243334444', 'david@byc.org', 'Kumasi, Ghana', '1995-11-20', 'Male', '2023-06-15', 2),
                ('Emmanuel Mensah', '0245556666', 'emmanuel@byc.org', 'Tema, Ghana', '2000-03-08', 'Male', '2025-02-01', 3),
                ('Sampson Tetteh', '0207778888', 'sampson@byc.org', 'Accra, Ghana', '1993-02-14', 'Male', '2022-04-10', 5),
                ('Rebecca Larbi', '0209990000', 'rebecca@byc.org', 'Cape Coast, Ghana', '1997-09-22', 'Female', '2021-08-11', 5);");

            // Seed an extra service type that isn't in default list (Youth Rally)
            $pdo->exec("INSERT INTO services (name, description) VALUES ('Youth Mega Rally', 'Annual large scale youth cell assembly');");

            // Fetch service IDs to assign historical sessions correctly
            $sunday_service_id = $pdo->query("SELECT id FROM services WHERE name = 'Sunday Worship Service'")->fetchColumn();
            $mega_rally_id = $pdo->query("SELECT id FROM services WHERE name = 'Youth Mega Rally'")->fetchColumn();

            // Insert historical attendance sessions (using MySQL DATE_SUB)
            $pdo->exec("INSERT INTO attendance (service_id, date) VALUES ($sunday_service_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY));"); // ID: 1
            $pdo->exec("INSERT INTO attendance (service_id, date) VALUES ($mega_rally_id, DATE_SUB(CURDATE(), INTERVAL 8 MONTH));"); // ID: 2

            // Map attendances:
            $pdo->exec("INSERT INTO member_attendance (member_id, attendance_id, status) VALUES 
                (1, 1, 'Present'),
                (2, 1, 'Present'),
                (3, 1, 'Present'),
                (4, 1, 'Absent'),
                (5, 1, 'Absent');");

            $pdo->exec("INSERT INTO member_attendance (member_id, attendance_id, status) VALUES 
                (1, 2, 'Present'),
                (2, 2, 'Present'),
                (3, 2, 'Present'),
                (4, 2, 'Present'),
                (5, 2, 'Present');");
        }
    }
    
    // Seed default admin account if users table is empty
    $stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt_users->fetchColumn() == 0) {
        $admin_user = getenv('ADMIN_USER') ?: 'admin';
        $admin_pass = getenv('ADMIN_PASS') ?: 'admin';
        $admin_name = getenv('ADMIN_NAME') ?: 'BYC Administrator';
        
        $admin_pass_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt_insert = $pdo->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
        $stmt_insert->execute([$admin_user, $admin_pass_hash, $admin_name]);
    }
    
} catch (PDOException $e) {
    $error_msg = $is_dev ? $e->getMessage() : "Please contact the system administrator.";
    die("Database Connection Error: " . $error_msg);
}

// 3. Establish Session and Authentication Redirect Guard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page !== 'login.php' && $current_page !== 'logout.php') {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login");
        exit;
    }
}
?>
