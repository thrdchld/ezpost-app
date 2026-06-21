<?php
// Pengaturan Zona Waktu Server PHP ke WIB
date_default_timezone_set('Asia/Jakarta');

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

session_start();

$db_host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$db_port = '4000';
$db_name = 'test';
$db_user = '43zndLs5D8mdMVi.root';
$db_pass = 'axvj3vCLdkpSHdtD';

try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_SSL_CA       => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    
    // Sinkronisasi Zona Waktu Database TiDB ke WIB (UTC+7)
    $pdo->exec("SET time_zone = '+07:00';");
    
} catch (PDOException $e) {
    die("Koneksi Database TiDB Gagal: " . $e->getMessage());
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf() {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        header('Content-Type: application/json');
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'CSRF Token Invalid']);
        exit;
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}
?>