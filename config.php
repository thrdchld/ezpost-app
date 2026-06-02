<?php
// Konfigurasi Database (TiDB Serverless)
$db_host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$db_port = '4000';
$db_name = 'sys';
$db_user = '43zndLs5D8mdMVi.root';
$db_pass = 'axvj3vCLdkpSHdtD';

try {
    // WAJIB SSL UNTUK TIDB SERVERLESS
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        // Parameter keamanan wajib TiDB
        PDO::MYSQL_ATTR_SSL_CA       => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("Koneksi Database TiDB Gagal: " . $e->getMessage());
}

// Pengaturan Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function verify_csrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die(json_encode(['status' => 'error', 'message' => 'CSRF token invalid.']));
    }
}
function is_logged_in() { return isset($_SESSION['user_id']); }

// Konfigurasi API Sosial Media
$fb_app_id = '1555139352893005';
$fb_app_secret = 'b4c9d8726daf37526baa27982d3b840b';
$threads_app_id = '981254994699916';
$threads_app_secret = '1ac5ada24ef08e79c4024f93447559bb';
?>