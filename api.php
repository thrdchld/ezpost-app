<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';

// Bypass CSRF token khusus untuk fungsi login
if ($action !== 'login') {
    verify_csrf();
    if (!is_logged_in()) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized. Sesi habis, silakan refresh.']);
        exit;
    }
}

try {
    // --- FUNGSI LOGIN ---
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Cek password hash atau plain text (saat instalasi pertama)
            if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
                $_SESSION['user_id'] = $user['id'];
                
                // Ubah plain text password jadi hash agar aman di database
                if ($password === $user['password_hash']) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                }
                
                echo json_encode(['status' => 'success']);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Email atau Password salah']);
        exit;
    }

    // --- FUNGSI CEK STATUS AKUN ---
    if ($action === 'check_status') {
        $stmt = $pdo->prepare("SELECT provider FROM social_accounts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $accounts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'status' => 'success', 
            'facebook' => in_array('facebook', $accounts),
            'threads' => in_array('threads', $accounts)
        ]);
        exit;
    }

    // --- FUNGSI JADWALKAN POSTINGAN ---
    if ($action === 'save_post') {
        $platform = $_POST['platform'] ?? 'facebook';
        $content = $_POST['content'] ?? '';
        $scheduled_at = $_POST['scheduled_at'] ?? null;
        
        if (empty($scheduled_at)) {
            $scheduled_at = date('Y-m-d H:i:s'); // Jam sekarang
        } else {
            // Pastikan format aman masuk ke database
            $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
        }

        // Simpan ke tabel posts
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, platform, content, status, scheduled_at) VALUES (?, ?, ?, 'scheduled', ?)");
        $stmt->execute([$_SESSION['user_id'], $platform, $content, $scheduled_at]);
        $post_id = $pdo->lastInsertId();

        // Proses File Upload (Gambar/Video)
        if (!empty($_FILES['media']['name'][0])) {
            $upload_dir = '/var/www/html/uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = time() . '_' . basename($_FILES['media']['name'][$key]);
                    $file_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $mime = mime_content_type($file_path);
                        $media_type = (strpos($mime, 'video') !== false) ? 'video' : 'image';
                        
                        $stmt = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
                        $stmt->execute([$post_id, $file_path, $media_type]);
                    }
                }
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Postingan berhasil dijadwalkan!']);
        exit;
    }

    // --- FUNGSI AMBIL RIWAYAT (SEMUA STATUS) ---
    if ($action === 'get_history') {
        // Ambil urut dari yang paling baru jadwalnya
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY scheduled_at DESC, created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $posts = $stmt->fetchAll();
        
        foreach($posts as &$post) {
            $post['scheduled_at_formatted'] = date('H:i', strtotime($post['scheduled_at']));
            $post['scheduled_date_raw'] = date('Y-m-d', strtotime($post['scheduled_at']));
            $post['scheduled_time_raw'] = date('H:i', strtotime($post['scheduled_at']));
        }

        echo json_encode(['status' => 'success', 'data' => $posts]);
        exit;
    }

    // --- FUNGSI PAKSA TAYANG SEKARANG (MANUAL) ---
    if ($action === 'force_publish') {
        $post_id = (int)$_POST['post_id'];
        
        $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = NOW(), status = 'scheduled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        
        echo json_encode(['status' => 'success', 'message' => 'Status diubah. Memproses pengiriman...']);
        exit;
    }

    // --- FUNGSI EDIT JADWAL (UPDATE TANGGAL & WAKTU) ---
    if ($action === 'edit_schedule') {
        $post_id = (int)$_POST['post_id'];
        $new_date = $_POST['new_date'] ?? '';
        $new_time = $_POST['new_time'] ?? '';
        
        if (empty($new_date) || empty($new_time)) {
            echo json_encode(['status' => 'error', 'message' => 'Tanggal dan Waktu tidak boleh kosong.']);
            exit;
        }

        $scheduled_at = date('Y-m-d H:i:s', strtotime("$new_date $new_time"));

        $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = ?, status = 'scheduled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$scheduled_at, $post_id, $_SESSION['user_id']]);

        echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diperbarui!']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System Error: ' . $e->getMessage()]);
}
?>