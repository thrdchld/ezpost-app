<?php
// PROTEKSI JSON DARI IKLAN/INJEKSI HOSTING
ob_start();
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$domain = $_SERVER['HTTP_HOST'];
$path_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url = $protocol . "://" . $domain . $path_dir . "/";

try {
    switch ($action) {
        case 'login':
            $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
            $password = $_POST['password'] ?? '';
            $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $is_valid = false; $needs_rehash = false;
                if (password_verify($password, $user['password_hash'])) { $is_valid = true; } 
                elseif ($user['password_hash'] === $password) { $is_valid = true; $needs_rehash = true; }
                if ($is_valid) {
                    if ($needs_rehash) {
                        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                        $update->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
                    }
                    $_SESSION['user_id'] = $user['id'];
                    ob_clean(); echo json_encode(['status' => 'success']); exit;
                }
            }
            ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Email atau Password salah.']); exit;

        case 'logout':
            session_destroy(); ob_clean(); echo json_encode(['status' => 'success']); exit;

        case 'check_accounts':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error']); exit; }
            $stmt = $pdo->prepare("SELECT provider FROM social_accounts WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $accounts = $stmt->fetchAll(PDO::FETCH_COLUMN);
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $accounts]); exit;

        case 'get_media':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error']); exit; }
            $upload_dir = 'uploads/'; $files = [];
            if (is_dir($upload_dir)) {
                $scanned = scandir($upload_dir);
                foreach ($scanned as $f) {
                    if ($f === '.' || $f === '..') continue;
                    $path = $upload_dir . $f;
                    if (is_file($path)) {
                        $files[] = [
                            'name' => $f, 'path' => $path, 'url' => $base_url . $path,
                            'size' => filesize($path), 'date' => filemtime($path),
                            'is_video' => strpos(mime_content_type($path), 'video') !== false
                        ];
                    }
                }
            }
            $stmt = $pdo->query("SELECT pm.file_path FROM post_media pm JOIN posts p ON pm.post_id = p.id WHERE p.status = 'scheduled'");
            $scheduled_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $protected = array_map(function($path) { return basename($path); }, $scheduled_files);
            foreach ($files as &$file) { $file['is_protected'] = in_array($file['name'], $protected); }
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $files]); exit;

        case 'delete_media':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');
            $itemsRaw = $_POST['files'] ?? '';
            $itemsArray = json_decode($itemsRaw, true);
            if(!is_array($itemsArray)) $itemsArray = isset($_POST['files']) && is_array($_POST['files']) ? $_POST['files'] : [];
            $deleted = 0; $skipped = 0;
            $stmt = $pdo->query("SELECT pm.file_path FROM post_media pm JOIN posts p ON pm.post_id = p.id WHERE p.status = 'scheduled'");
            $scheduled_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $protected = array_map(function($path) { return basename($path); }, $scheduled_files);
            foreach ($itemsArray as $item) {
                $path = 'uploads/' . basename($item);
                if (in_array(basename($item), $protected)) { $skipped++; continue; }
                if (file_exists($path) && is_file($path)) {
                    unlink($path);
                    $pdo->prepare("DELETE FROM post_media WHERE file_path = ?")->execute([$path]);
                    $deleted++;
                }
            }
            ob_clean(); echo json_encode(['status' => 'success', 'message' => "$deleted file dihapus." . ($skipped > 0 ? " ($skipped dilindungi jadwal)" : "")]); exit;

        case 'save_post':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');

            $platform_mode = $_POST['platform'] ?? 'fb_threads_nomedia';
            $content = $_POST['content'] ?? '';
            $scheduled_at = $_POST['scheduled_at'] ?? null;
            
            if (empty(trim($scheduled_at))) {
                $scheduled_at = date('Y-m-d H:i:s');
                $is_scheduled = false;
            } else {
                $scheduled_at = date('Y-m-d H:i:s', strtotime($scheduled_at));
                $is_scheduled = true;
            }

            // Multiplexing Logika Platform
            $targets = [];
            if ($platform_mode === 'facebook') $targets[] = ['plat' => 'facebook', 'media' => true];
            elseif ($platform_mode === 'threads') $targets[] = ['plat' => 'threads', 'media' => true];
            elseif ($platform_mode === 'fb_threads_media') {
                $targets[] = ['plat' => 'facebook', 'media' => true];
                $targets[] = ['plat' => 'threads', 'media' => true];
            } elseif ($platform_mode === 'fb_threads_nomedia') {
                $targets[] = ['plat' => 'facebook', 'media' => true];
                $targets[] = ['plat' => 'threads', 'media' => false]; // Threads tanpa media
            }

            $uploaded_files = [];
            if (!empty($_FILES['media']['name'][0])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                foreach ($_FILES['media']['name'] as $key => $name) {
                    $tmp_name = $_FILES['media']['tmp_name'][$key];
                    if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                        if ($_FILES['media']['size'][$key] > 19 * 1024 * 1024) continue; 
                        $mime = mime_content_type($tmp_name);
                        $media_type = strpos($mime, 'video') !== false ? 'video' : 'image';
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $new_name = uniqid() . '.' . $ext;
                        $destination = $upload_dir . $new_name;
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $uploaded_files[] = [
                                'path' => realpath($destination),    
                                'url'  => $base_url . $destination,  
                                'type' => $media_type,
                                'mime' => $mime
                            ];
                        }
                    }
                }
            }

            $pdo->beginTransaction();
            $created_ids = [];
            try {
                foreach ($targets as $target) {
                    $stmt = $pdo->prepare("SELECT access_token, page_id FROM social_accounts WHERE user_id = ? AND provider = ?");
                    $stmt->execute([$_SESSION['user_id'], $target['plat']]);
                    $account = $stmt->fetch();
                    if (!$account) throw new Exception("Akun {$target['plat']} belum terhubung.");

                    $status = $is_scheduled ? 'scheduled' : 'published';
                    $stmt = $pdo->prepare("INSERT INTO posts (user_id, platform, content, status, scheduled_at) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $target['plat'], $content, $status, $scheduled_at]);
                    $post_id = $pdo->lastInsertId();
                    $created_ids[] = ['id' => $post_id, 'plat' => $target['plat'], 'token' => $account['access_token'], 'page_id' => $account['page_id'], 'allow_media' => $target['media']];

                    if ($target['media'] && !empty($uploaded_files)) {
                        foreach ($uploaded_files as $file) {
                            $stmtMedia = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
                            $stmtMedia->execute([$post_id, 'uploads/'.basename($file['path']), $file['type']]);
                        }
                    }
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            // DIRECT PUBLISH JIKA BUKAN JADWAL
            if (!$is_scheduled) {
                $errors = [];
                foreach ($created_ids as $item) {
                    $media_to_send = $item['allow_media'] ? $uploaded_files : [];
                    if ($item['plat'] === 'facebook') {
                        $res = publish_to_fb_direct($content, $item['token'], $item['page_id'], $media_to_send);
                    } else {
                        $res = publish_to_threads_direct($content, $item['token'], $media_to_send);
                    }
                    if (isset($res['error'])) {
                        $pdo->prepare("UPDATE posts SET status = 'failed', error_log = ? WHERE id = ?")->execute([$res['error'], $item['id']]);
                        $errors[] = strtoupper($item['plat']) . ": " . $res['error'];
                    }
                }
                if (!empty($errors)) throw new Exception(implode("\n", $errors));
            }

            ob_clean(); echo json_encode(['status' => 'success', 'message' => $is_scheduled ? 'Berhasil masuk antrian.' : 'Dipublikasikan!']); exit;

        case 'get_history':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error']); exit; }
            // Mengambil semua status, diurutkan mutlak dari yang terbaru
            $stmt = $pdo->prepare("SELECT p.*, 
                                   DATE_FORMAT(p.scheduled_at, '%Y-%m-%d') as date_only, 
                                   DATE_FORMAT(p.scheduled_at, '%H:%i') as time_only 
                                   FROM posts p WHERE user_id = ? ORDER BY scheduled_at DESC, id DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $posts = $stmt->fetchAll();
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $posts]); exit;

        case 'force_publish':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');
            $post_id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = NOW(), status = 'scheduled' WHERE id = ? AND user_id = ?");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            ob_clean(); echo json_encode(['status' => 'success', 'message' => 'Memproses pengiriman...']); exit;

        case 'edit_schedule':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');
            $post_id = (int)$_POST['id'];
            $new_datetime = $_POST['datetime'] ?? '';
            if(empty($new_datetime)) throw new Exception("Waktu tidak boleh kosong.");
            $formatted = date('Y-m-d H:i:s', strtotime($new_datetime));
            
            $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = ?, status = 'scheduled' WHERE id = ? AND user_id = ?");
            $stmt->execute([$formatted, $post_id, $_SESSION['user_id']]);
            ob_clean(); echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diubah!']); exit;

        case 'delete_post':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');
            $post_id = intval($_POST['id'] ?? 0);
            $stmtMedia = $pdo->prepare("SELECT file_path FROM post_media WHERE post_id = ?");
            $stmtMedia->execute([$post_id]);
            while($media = $stmtMedia->fetch()) {
                if(file_exists($media['file_path'])) @unlink($media['file_path']);
            }
            $pdo->prepare("DELETE FROM post_media WHERE post_id = ?")->execute([$post_id]);
            $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?")->execute([$post_id, $_SESSION['user_id']]);
            ob_clean(); echo json_encode(['status' => 'success', 'message' => 'Jadwal dihapus.']); exit;
            
        default: ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Aksi invalid.']); exit;
    }
} catch (Throwable $e) { 
    ob_clean(); echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit; 
}

// =================== FUNGSI BANTUAN ===================
function split_threads($text) {
    $paragraphs = preg_split('/\n\s*\n/', $text);
    $threads = [];
    foreach ($paragraphs as $p) {
        $trimmed = trim($p);
        if (empty($trimmed)) continue;
        if (mb_strlen($trimmed) > 490) {
            $words = explode(' ', $trimmed); $currentChunk = '';
            foreach ($words as $word) {
                if (mb_strlen($currentChunk . $word) > 490) {
                    $threads[] = trim($currentChunk); $currentChunk = $word . ' ';
                } else { $currentChunk .= $word . ' '; }
            }
            if (trim($currentChunk)) $threads[] = trim($currentChunk);
        } else { $threads[] = $trimmed; }
    }
    return empty($threads) ? [""] : $threads;
}

function publish_to_fb_direct($text, $token, $page_id, $media_files) {
    $target_id = !empty($page_id) ? $page_id : 'me';
    if (empty($media_files)) {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/feed";
        $postData = ['message' => $text, 'access_token' => $token];
    } else if (count($media_files) === 1 && $media_files[0]['type'] === 'image') {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/photos";
        $postData = ['message' => $text, 'source' => new CURLFile($media_files[0]['path'], $media_files[0]['mime']), 'access_token' => $token];
    } else if ($media_files[0]['type'] === 'video') {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/videos";
        $postData = ['description' => $text, 'source' => new CURLFile($media_files[0]['path'], $media_files[0]['mime']), 'access_token' => $token];
    } else {
        $attached_media = [];
        foreach ($media_files as $m) {
            if ($m['type'] === 'video') continue;
            $upUrl = "https://graph.facebook.com/v19.0/{$target_id}/photos";
            $upData = ['published' => 'false', 'source' => new CURLFile($m['path'], $m['mime']), 'access_token' => $token];
            $ch = curl_init($upUrl); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $upData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = json_decode(curl_exec($ch), true); curl_close($ch);
            if (isset($res['id'])) $attached_media[] = '{"media_fbid":"'.$res['id'].'"}';
        }
        $url = "https://graph.facebook.com/v19.0/{$target_id}/feed";
        $postData = ['message' => $text, 'access_token' => $token];
        foreach ($attached_media as $k => $m) { $postData["attached_media[$k]"] = $m; }
    }

    $ch = curl_init($url); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch); $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);

    $resData = json_decode($response, true);
    if ($httpCode !== 200 || isset($resData['error'])) return ['error' => $resData['error']['message'] ?? 'Unknown FB Error'];
    return ['success' => true, 'id' => $resData['id'] ?? null];
}

function publish_to_threads_direct($content, $token, $media_files) {
    $threadsArray = split_threads($content);
    $reply_to_id = null; 

    foreach ($threadsArray as $index => $textChunk) {
        $url1 = "https://graph.threads.net/v1.0/me/threads";
        $data1 = ['text' => $textChunk, 'access_token' => $token];
        if ($reply_to_id) $data1['reply_to_id'] = $reply_to_id;

        $isVideo = false;
        if ($index === 0 && !empty($media_files)) {
            $firstMedia = $media_files[0];
            if ($firstMedia['type'] === 'image') {
                $data1['media_type'] = 'IMAGE'; $data1['image_url'] = $firstMedia['url'];
            } else if ($firstMedia['type'] === 'video') {
                $data1['media_type'] = 'VIDEO'; $data1['video_url'] = $firstMedia['url']; $isVideo = true;
            }
        } else { $data1['media_type'] = 'TEXT'; }

        if ($index > 0) sleep(4); 
        $containerId = null; $lastError = "";
        for ($retry = 0; $retry < 3; $retry++) {
            $ch1 = curl_init($url1); curl_setopt($ch1, CURLOPT_POST, true); curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($data1));
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
            $res1 = curl_exec($ch1); curl_close($ch1);
            $json1 = json_decode($res1, true);
            if (!empty($json1['id'])) { $containerId = $json1['id']; break; }
            $lastError = $json1['error']['message'] ?? $res1;
            if (stripos($lastError, 'permission') !== false || stripos($lastError, 'OAuthException') !== false) {
                if(stripos($lastError, 'threads_manage_replies') !== false || $index > 0) return ['error' => "Ditolak: Wajib centang 'threads_manage_replies' di Token!"];
            }
            sleep(4);
        }
        if (!$containerId) return ['error' => "Gagal buat container: " . $lastError];

        if ($isVideo) {
            $is_ready = false;
            for ($i = 0; $i < 6; $i++) { 
                sleep(8); 
                $checkUrl = "https://graph.threads.net/v1.0/{$containerId}?fields=status,error_message&access_token={$token}";
                $chCheck = curl_init($checkUrl); curl_setopt($chCheck, CURLOPT_RETURNTRANSFER, true); curl_setopt($chCheck, CURLOPT_SSL_VERIFYPEER, false);
                $checkRes = curl_exec($chCheck); curl_close($chCheck);
                $checkData = json_decode($checkRes, true);
                if (isset($checkData['status']) && $checkData['status'] === 'FINISHED') { $is_ready = true; break; }
            }
            if (!$is_ready) return ['error' => "Video gagal diproses Meta (Timeout)."];
        }

        $url2 = "https://graph.threads.net/v1.0/me/threads_publish";
        $data2 = ['creation_id' => $containerId, 'access_token' => $token];
        $publishedId = null; $lastErrorPublish = "";
        for ($retry = 0; $retry < 3; $retry++) {
            $ch2 = curl_init($url2); curl_setopt($ch2, CURLOPT_POST, true); curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($data2));
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            $res2 = curl_exec($ch2); curl_close($ch2);
            $json2 = json_decode($res2, true);
            if (!empty($json2['id'])) { $publishedId = $json2['id']; break; }
            $lastErrorPublish = $json2['error']['message'] ?? $res2;
            sleep(3);
        }
        if (!$publishedId) return ['error' => "Gagal publish container: " . $lastErrorPublish];
        $reply_to_id = $publishedId;
        sleep(2); 
    }
    return ['success' => true];
}
?>