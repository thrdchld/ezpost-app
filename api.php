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
            if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
                $_SESSION['user_id'] = $user['id'];
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

    // --- FUNGSI JADWALKAN POSTINGAN (MULTIPLEXING) ---
    if ($action === 'save_post') {
        $platform_mode = $_POST['platform'] ?? 'fb_threads_nomedia'; // Default Option
        $content = $_POST['content'] ?? '';
        $scheduled_at = $_POST['scheduled_at'] ?? null;
        
        // Handling Tanggal (Aman & Tervalidasi)
        if (empty(trim($scheduled_at))) {
            $scheduled_at = date('Y-m-d H:i:s');
        } else {
            $parsed_time = strtotime($scheduled_at);
            if (!$parsed_time) {
                echo json_encode(['status' => 'error', 'message' => 'Format Tanggal/Waktu tidak valid!']);
                exit;
            }
            $scheduled_at = date('Y-m-d H:i:s', $parsed_time);
        }

        // Tentukan Target Berdasarkan Mode
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

        // Proses Upload Media Fisik Sekali Saja
        $uploaded_files = [];
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
                        $uploaded_files[] = ['path' => $file_path, 'type' => $media_type];
                    }
                }
            }
        }

        // Eksekusi Pembuatan Post per Target
        $pdo->beginTransaction();
        try {
            foreach ($targets as $target) {
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, platform, content, status, scheduled_at) VALUES (?, ?, ?, 'scheduled', ?)");
                $stmt->execute([$_SESSION['user_id'], $target['plat'], $content, $scheduled_at]);
                $post_id = $pdo->lastInsertId();

                // Tempelkan media hanya jika target mengizinkan media
                if ($target['media'] && !empty($uploaded_files)) {
                    foreach ($uploaded_files as $file) {
                        $stmt_media = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
                        $stmt_media->execute([$post_id, $file['path'], $file['type']]);
                    }
                }
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Berhasil! Postingan telah masuk antrean.']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
        exit;
    }

    // --- FUNGSI AMBIL RIWAYAT (DI-PARSING OLEH BACKEND) ---
    if ($action === 'get_history') {
        // Query ambil mutlak dari waktu terbaru ke terlama (descending)
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY scheduled_at DESC, created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $posts = $stmt->fetchAll();
        
        $valid_posts = [];
        foreach($posts as $post) {
            // Mencegah Invalid Date, menggunakan Backend Timestamp
            $time = strtotime($post['scheduled_at']);
            if (!$time || $time < 0) {
                $time = time(); // Fallback aman
            }
            $post['scheduled_date_raw'] = date('Y-m-d', $time);
            $post['scheduled_time_raw'] = date('H:i', $time);
            // Tambahkan flag untuk logic kalender
            $post['year'] = date('Y', $time);
            $post['month'] = date('m', $time);
            
            $valid_posts[] = $post;
        }

        echo json_encode(['status' => 'success', 'data' => $valid_posts]);
        exit;
    }

    // --- FUNGSI PAKSA TAYANG SEKARANG ---
    if ($action === 'force_publish') {
        $post_id = (int)$_POST['post_id'];
        $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = NOW(), status = 'scheduled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$post_id, $_SESSION['user_id']]);
        echo json_encode(['status' => 'success', 'message' => 'Jadwal dipercepat! Mengeksekusi API...']);
        exit;
    }

    // --- FUNGSI EDIT JADWAL ---
    if ($action === 'edit_schedule') {
        $post_id = (int)$_POST['post_id'];
        $new_date = $_POST['new_date'] ?? '';
        $new_time = $_POST['new_time'] ?? '';
        
        if (empty($new_date) || empty($new_time)) {
            echo json_encode(['status' => 'error', 'message' => 'Tanggal dan Waktu wajib diisi!']);
            exit;
        }

        $scheduled_at = date('Y-m-d H:i:s', strtotime("$new_date $new_time"));
        $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = ?, status = 'scheduled' WHERE id = ? AND user_id = ?");
        $stmt->execute([$scheduled_at, $post_id, $_SESSION['user_id']]);

        echo json_encode(['status' => 'success', 'message' => 'Waktu jadwal berhasil diperbarui.']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak ditemukan.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'System DB Error: ' . $e->getMessage()]);
}
?>