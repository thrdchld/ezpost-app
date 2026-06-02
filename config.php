<?php
// Pengaturan Zona Waktu Server
date_default_timezone_set('Asia/Jakarta');

session_start();

// Koneksi ke TiDB Cloud Serverless
$db_host = 'gateway01.ap-southeast-1.prod.alicloud.tidbcloud.com';
$db_port = '4000'; // Port khusus TiDB
$db_name = 'sys';
$db_user = '43zndLs5D8mdMVi.root';
$db_pass = 'axvj3vCLdkpSHdtD';

try {
    // Menambahkan opsi bypass SSL yang sering diminta oleh ekosistem cloud
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    // Perhatikan tambahan parameter port=$db_port
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);
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