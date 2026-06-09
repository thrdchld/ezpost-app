<?php
// Pastikan session dimulai dengan aman
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        echo json_encode(['status' => 'error', 'message' => 'Sesi habis, silakan refresh halaman untuk login kembali.']);
        exit;
    }
}

try {
    // ==========================================
    // 1. FUNGSI LOGIN
    // ==========================================
    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
                $_SESSION['user_id'] = $user['id'];
                
                // Ubah plain text ke hash jika masih plain (Untuk instalasi awal)
                if ($password === $user['password_hash']) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
                }
                
                echo json_encode(['status' => 'success', 'message' => 'Login Berhasil']);
                exit;
            }
        }
        echo json_encode(['status' => 'error', 'message' => 'Email atau Password salah!']);
        exit;
    }

    // ==========================================
    // 2. FUNGSI CEK STATUS AKUN
    // ==========================================
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

    // ==========================================
    // 3. FUNGSI JADWALKAN POSTINGAN
    // ==========================================
    if ($action === 'save_post') {
        $platform_mode = $_POST['platform'] ?? 'fb_threads_nomedia';
        $content = $_POST['content'] ?? '';
        $scheduled_at = $_POST['scheduled_at'] ?? null;
        
        if (empty(trim($scheduled_at))) {
            $scheduled_at = date('Y-m-d H:i:s');
        } else {
            $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
        }

        $targets = [];
        if ($platform_mode === 'facebook') {
            $targets[] = ['plat' => 'facebook', 'media' => true];
        } elseif ($platform_mode === 'threads') {
            $targets[] = ['plat' => 'threads', 'media' => true];
        } elseif ($platform_mode === 'fb_threads_media') {
            $targets[] = ['plat' => 'facebook', 'media' => true];
            $targets[] = ['plat' => 'threads', 'media' => true];
        } elseif ($platform_mode === 'fb_threads_nomedia') {
            $targets[] = ['plat' => 'facebook', 'media' => true];
            $targets[] = ['plat' => 'threads', 'media' => false];
        }

        $uploaded_files = [];
        $upload_dir = __DIR__ . '/uploads/'; // Path relatif aman
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

        if (!empty($_FILES['media']['name'][0])) {
            foreach ($_FILES['media']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = time() . '_' . basename($_FILES['media']['name'][$key]);
                    $file_path = $upload_dir . $file_name;
                    $db_path = 'uploads/' . $file_name; // Simpan path relatif untuk frontend
                    
                    if (move_uploaded_file($tmp_name, $file_path)) {
                        $mime = mime_content_type($file_path);
                        $media_type = (strpos($mime, 'video') !== false) ? 'video' : 'image';
                        $uploaded_files[] = ['path' => $db_path, 'type' => $media_type];
                    }
                }
            }
        }

        $pdo->beginTransaction();
        try {
            foreach ($targets as $target) {
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, platform, content, status, scheduled_at) VALUES (?, ?, ?, 'scheduled', ?)");
                $stmt->execute([$_SESSION['user_id'], $target['plat'], $content, $scheduled_at]);
                $post_id = $pdo->lastInsertId();

                if ($target['media'] && !empty($uploaded_files)) {
                    foreach ($uploaded_files as $file) {
                        $stmt_media = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
                        $stmt_media->execute([$post_id, $file['path'], $file['type']]);
                    }
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Postingan berhasil disimpan & masuk antrean!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $e->getMessage()]);
        }
        exit;
    }

    // ==========================================
    // 4. FUNGSI AMBIL RIWAYAT & JADWAL
    // ==========================================
    if ($action === 'get_history') {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY scheduled_at DESC, created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $posts = $stmt->fetchAll();
        
        $valid_posts = [];
        foreach($posts as $post) {
            $time = strtotime($post['scheduled_at']);
            if (!$time || $time < 0) $time = time(); 
            
            $post['scheduled_date_raw'] = date('Y-m-d', $time);
            $post['scheduled_time_raw'] = date('H:i', $time);
            $post['scheduled_at'] = date('Y-m-d H:i', $time); // Format standar Flatpickr
            $valid_posts[] = $post;
        }

        echo json_encode(['status' => 'success', 'data' => $valid_posts]);
        exit;
    }

    // ==========================================
    // 5. FUNGSI PAKSA TAYANG (KIRIM SEKARANG)
    // ==========================================
    if ($action === 'force_publish') {
        $post_id = (int)$_POST['post_id'];
        $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = NOW(), status = 'scheduled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        echo json_encode(['status' => 'success', 'message' => 'Jadwal dipercepat ke detik ini!']);
        exit;
    }

    // ==========================================
    // 6. FUNGSI EDIT JADWAL
    // ==========================================
    if ($action === 'edit_schedule') {
        $post_id = (int)$_POST['post_id'];
        $new_datetime = $_POST['new_datetime'] ?? '';
        
        if (empty($new_datetime)) {
            echo json_encode(['status' => 'error', 'message' => 'Waktu tidak boleh kosong!']);
            exit;
        }

        $scheduled_at = date('Y-m-d H:i:s', strtotime($new_datetime));
        $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = ?, status = 'scheduled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$scheduled_at, $post_id, $_SESSION['user_id']]);

        echo json_encode(['status' => 'success', 'message' => 'Waktu tayang berhasil diperbarui!']);
        exit;
    }

    // ==========================================
    // 7. FUNGSI HAPUS POSTINGAN
    // ==========================================
    if ($action === 'delete_post') {
        $post_id = (int)$_POST['post_id'];
        
        $pdo->prepare("DELETE FROM post_media WHERE post_id = ?")->execute([$post_id]);
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        
        echo json_encode(['status' => 'success', 'message' => 'Postingan berhasil dihapus permanen.']);
        exit;
    }

    // ==========================================
    // 8. FUNGSI AMBIL GALERI MEDIA
    // ==========================================
    if ($action === 'get_gallery') {
        $upload_dir = __DIR__ . '/uploads/';
        $files_data = [];
        
        if (is_dir($upload_dir)) {
            $files = array_diff(scandir($upload_dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $upload_dir . $file;
                if (is_file($path)) {
                    $size = filesize($path);
                    $date = filemtime($path);
                    $mime = mime_content_type($path);
                    $type = (strpos($mime, 'video')