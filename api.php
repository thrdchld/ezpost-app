<?php
// TAHAP 1: Cegah server membocorkan teks error PHP yang bisa merusak format JSON
ob_start();
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
}

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
                    ob_clean(); echo json_encode(['status' => 'success']);
                } else { ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Password salah.']); }
            } else { ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Email tidak ditemukan.']); }
            break;

        case 'logout':
            session_destroy();
            ob_clean(); echo json_encode(['status' => 'success']);
            break;

        case 'check_accounts':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
            $stmt = $pdo->prepare("SELECT provider FROM social_accounts WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $accounts = $stmt->fetchAll(PDO::FETCH_COLUMN);
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $accounts]);
            break;

        case 'get_media':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
            $upload_dir = 'uploads/';
            $files = [];
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
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $files]);
            break;

        case 'delete_media':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');
            $itemsRaw = $_POST['files'] ?? '';
            $itemsArray = json_decode($itemsRaw, true);
            if(!is_array($itemsArray)) { $itemsArray = isset($_POST['files']) && is_array($_POST['files']) ? $_POST['files'] : []; }
            
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
            ob_clean(); echo json_encode(['status' => 'success', 'message' => "$deleted file dihapus." . ($skipped > 0 ? " ($skipped dilindungi karena dipakai jadwal)" : "")]);
            break;

        case 'get_progress':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error']); exit; }
            $file = "uploads/progress_" . $_SESSION['user_id'] . ".txt";
            $text = file_exists($file) ? file_get_contents($file) : 'Memproses...';
            ob_clean(); echo json_encode(['status' => 'success', 'progress' => $text]);
            exit;

        case 'save_post':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');

            $progress_file = "uploads/progress_" . $_SESSION['user_id'] . ".txt";
            @file_put_contents($progress_file, "Menyiapkan data...");

            try {
                $platform = $_POST['platform'] ?? 'facebook';
                $content = $_POST['content'] ?? ''; 
                $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
                $status = $scheduled_at ? 'scheduled' : 'published'; 

                $stmt = $pdo->prepare("SELECT access_token, page_id FROM social_accounts WHERE user_id = ? AND provider = ?");
                $stmt->execute([$_SESSION['user_id'], $platform]);
                $account = $stmt->fetch();
                if (!$account) throw new Exception("Akun {$platform} belum terhubung di Database.");

                @file_put_contents($progress_file, "Menyimpan ke Database...");
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, platform, content, status, scheduled_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $platform, $content, $status, $scheduled_at]);
                $post_id = $pdo->lastInsertId();

                $uploaded_media = [];
                if (!empty($_FILES['media']['name']) && is_array($_FILES['media']['name']) && !empty($_FILES['media']['name'][0])) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                    $total_files = count($_FILES['media']['name']);
                    foreach ($_FILES['media']['name'] as $key => $name) {
                        @file_put_contents($progress_file, "Upload Media (" . ($key+1) . "/$total_files)...");
                        $tmp_name = $_FILES['media']['tmp_name'][$key];
                        if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime = finfo_file($finfo, $tmp_name);
                            finfo_close($finfo);

                            if ($_FILES['media']['size'][$key] > 19 * 1024 * 1024) continue; 
                            
                            $media_type = strpos($mime, 'video') !== false ? 'video' : 'image';
                            $ext = pathinfo($name, PATHINFO_EXTENSION);
                            $new_name = uniqid() . '.' . $ext;
                            $destination = $upload_dir . $new_name;

                            if (move_uploaded_file($tmp_name, $destination)) {
                                $stmtMedia = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
                                $stmtMedia->execute([$post_id, $destination, $media_type]);
                                
                                $uploaded_media[] = [
                                    'path' => realpath($destination),    
                                    'url'  => $base_url . $destination,  
                                    'type' => $media_type,
                                    'mime' => $mime
                                ];
                            }
                        }
                    }
                }

                if (!$scheduled_at) {
                    if ($platform === 'facebook') {
                        @file_put_contents($progress_file, "Publishing ke FB...");
                        $result = publish_to_fb_direct($content, $account['access_token'], $account['page_id'], $uploaded_media);
                    } elseif ($platform === 'threads') {
                        $result = publish_to_threads_direct($content, $account['access_token'], $uploaded_media, $progress_file);
                    }
                    
                    if (isset($result['error'])) {
                        $update = $pdo->prepare("UPDATE posts SET status = 'failed', error_log = ? WHERE id = ?");
                        $update->execute([$result['error'], $post_id]);
                        throw new Exception("Ditolak Meta: " . $result['error']);
                    }
                }

                @unlink($progress_file);
                ob_clean(); echo json_encode(['status' => 'success', 'post_id' => $post_id, 'message' => $scheduled_at ? 'Berhasil masuk antrian jadwal.' : 'Dipublikasikan!']);
            } catch (Exception $e) {
                @unlink($progress_file);
                throw $e;
            }
            break;
            
        case 'get_posts':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
            $stmt = $pdo->prepare("SELECT id, platform, content, status, scheduled_at, error_log, created_at FROM posts WHERE user_id = ? ORDER BY scheduled_at ASC, created_at DESC");
            $stmt->execute([$_SESSION['user_id']]);
            $posts = $stmt->fetchAll();
            
            // Format data untuk mempermudah grouping di frontend
            foreach($posts as &$p) {
                $dateToUse = $p['scheduled_at'] ? $p['scheduled_at'] : $p['created_at'];
                $p['date_raw'] = date('Y-m-d', strtotime($dateToUse));
                $p['time_formatted'] = date('H:i', strtotime($dateToUse));
            }
            
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $posts]);
            break;

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
            ob_clean(); echo json_encode(['status' => 'success', 'message' => 'Jadwal dan medianya berhasil dihapus.']);
            break;

        case 'force_publish':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');
            $post_id = intval($_POST['post_id'] ?? 0);
            
            $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = NOW(), status = 'scheduled' WHERE id = ? AND user_id = ?");
            $stmt->execute([$post_id, $_SESSION['user_id']]);
            
            ob_clean(); echo json_encode(['status' => 'success', 'message' => 'Jadwal diperbarui. Sedang memproses pengiriman...']);
            break;

        case 'edit_schedule':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');
            $post_id = intval($_POST['post_id'] ?? 0);
            $new_date = $_POST['new_date'] ?? '';
            $new_time = $_POST['new_time'] ?? '';
            
            if (empty($new_date) || empty($new_time)) throw new Exception('Tanggal dan Waktu tidak boleh kosong.');
            $scheduled_at = date('Y-m-d H:i:s', strtotime("$new_date $new_time"));

            $stmt = $pdo->prepare("UPDATE posts SET scheduled_at = ?, status = 'scheduled' WHERE id = ? AND user_id = ?");
            $stmt->execute([$scheduled_at, $post_id, $_SESSION['user_id']]);
            ob_clean(); echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil diperbarui.']);
            break;

        default: ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Aksi invalid.']); break;
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

// =================== FUNGSI FACEBOOK ===================
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

// =================== FUNGSI THREADS ===================
function publish_to_threads_direct($content, $token, $media_files, $progress_file = null) {
    $threadsArray = split_threads($content);
    $reply_to_id = null; $totalParts = count($threadsArray);

    foreach ($threadsArray as $index => $textChunk) {
        $currentPart = $index + 1;
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

        if ($index > 0) sleep(6); 
        if ($progress_file) @file_put_contents($progress_file, "Menyiapkan Part ($currentPart/$totalParts)...");

        $containerId = null; $lastError = "";
        for ($retry = 0; $retry < 3; $retry++) {
            $ch1 = curl_init($url1); curl_setopt($ch1, CURLOPT_POST, true); curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($data1));
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
            $res1 = curl_exec($ch1); curl_close($ch1);
            $json1 = json_decode($res1, true);
            if (!empty($json1['id'])) { $containerId = $json1['id']; break; }
            $lastError = $json1['error']['message'] ?? $res1;
            
            if (stripos($lastError, 'permission') !== false || stripos($lastError, 'OAuthException') !== false) {
                if(stripos($lastError, 'threads_manage_replies') !== false || $index > 0){
                    return ['error' => "DITOLAK. Wajib centang 'threads_manage_replies' di pengaturan Token!"];
                }
            }
            if ($progress_file) @file_put_contents($progress_file, "Retry Part ($currentPart/$totalParts)...");
            sleep(4);
        }
        if (!$containerId) return ['error' => "Gagal buat container (Part $currentPart): " . $lastError];

        if ($isVideo) {
            $is_ready = false;
            for ($i = 0; $i < 4; $i++) { 
                if ($progress_file) @file_put_contents($progress_file, "Render Meta ($currentPart/$totalParts)... Tunggu " . ($i*8) . "s");
                sleep(8); 
                $checkUrl = "https://graph.threads.net/v1.0/{$containerId}?fields=status,error_message&access_token={$token}";
                $chCheck = curl_init($checkUrl); curl_setopt($chCheck, CURLOPT_RETURNTRANSFER, true); curl_setopt($chCheck, CURLOPT_SSL_VERIFYPEER, false);
                $checkRes = curl_exec($chCheck); curl_close($chCheck);
                $checkData = json_decode($checkRes, true);
                if (isset($checkData['status']) && $checkData['status'] === 'FINISHED') { $is_ready = true; break; }
            }
            if (!$is_ready) return ['error' => "Video gagal diproses oleh Meta (Timeout)."];
        }

        if ($progress_file) @file_put_contents($progress_file, "Publish Part ($currentPart/$totalParts)...");

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

        if (!$publishedId) return ['error' => "Gagal publish container (Part $currentPart): " . $lastErrorPublish];
        
        $reply_to_id = $publishedId;
        sleep(4); 
    }
    
    return ['success' => true];
}
?>