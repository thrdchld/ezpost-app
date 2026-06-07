<?php
ob_start();
require_once 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

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
                } else { ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Password salah.']); exit; }
            } else { ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Email tidak ditemukan.']); exit; }
            break;

        case 'logout':
            session_destroy(); ob_clean(); echo json_encode(['status' => 'success']); exit;

        case 'check_accounts':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
            $stmt = $pdo->prepare("SELECT provider FROM social_accounts WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $accounts = $stmt->fetchAll(PDO::FETCH_COLUMN);
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $accounts]); exit;

        case 'save_post':
            verify_csrf();
            if (!is_logged_in()) throw new Exception('Sesi habis.');

            $platform_opt = $_POST['platform'] ?? 'fb_threads_no_media';
            $content = $_POST['content'] ?? ''; 
            $scheduled_at = !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null;
            $status = $scheduled_at ? 'scheduled' : 'published'; 

            // 1. Tentukan Platform Tujuan
            $targets = [];
            if ($platform_opt === 'facebook') $targets[] = 'facebook';
            if ($platform_opt === 'threads') $targets[] = 'threads';
            if ($platform_opt === 'fb_threads' || $platform_opt === 'fb_threads_no_media') {
                $targets[] = 'facebook';
                $targets[] = 'threads';
            }

            // 2. Verifikasi Akun Terkoneksi
            $accounts = [];
            foreach ($targets as $tgt) {
                $stmt = $pdo->prepare("SELECT access_token, page_id FROM social_accounts WHERE user_id = ? AND provider = ?");
                $stmt->execute([$_SESSION['user_id'], $tgt]);
                $acc = $stmt->fetch();
                if (!$acc) throw new Exception("Akun " . ucfirst($tgt) . " belum terhubung di Sistem.");
                $accounts[$tgt] = $acc;
            }

            // 3. Proses Upload Media (Hanya dilakukan 1x ke server)
            $saved_media = [];
            if (!empty($_FILES['media']['name']) && is_array($_FILES['media']['name']) && !empty($_FILES['media']['name'][0])) {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                foreach ($_FILES['media']['name'] as $key => $name) {
                    $tmp_name = $_FILES['media']['tmp_name'][$key];
                    if ($_FILES['media']['error'][$key] === UPLOAD_ERR_OK) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mime = finfo_file($finfo, $tmp_name);
                        finfo_close($finfo);

                        if ($_FILES['media']['size'][$key] > 20 * 1024 * 1024) continue; 
                        
                        $media_type = strpos($mime, 'video') !== false ? 'video' : 'image';
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $new_name = uniqid() . '.' . $ext;
                        $destination = $upload_dir . $new_name;

                        if (move_uploaded_file($tmp_name, $destination)) {
                            $saved_media[] = [
                                'path' => realpath($destination),    
                                'url'  => $base_url . $destination,  
                                'type' => $media_type,
                                'mime' => $mime,
                                'local_path' => $destination
                            ];
                        }
                    }
                }
            }

            // 4. Simpan ke Database & Eksekusi Per Platform
            $success_msgs = [];
            $error_msgs = [];

            foreach ($targets as $tgt) {
                $stmt = $pdo->prepare("INSERT INTO posts (user_id, platform, content, status, scheduled_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $tgt, $content, $status, $scheduled_at]);
                $post_id = $pdo->lastInsertId();

                // Cek Aturan Media
                $attach_media = true;
                if ($tgt === 'threads' && $platform_opt === 'fb_threads_no_media') {
                    $attach_media = false;
                }

                $target_media = [];
                if ($attach_media && !empty($saved_media)) {
                    foreach ($saved_media as $m) {
                        $stmtM = $pdo->prepare("INSERT INTO post_media (post_id, file_path, media_type) VALUES (?, ?, ?)");
                        $stmtM->execute([$post_id, $m['local_path'], $m['type']]);
                        $target_media[] = $m;
                    }
                }

                // Jika Tidak Dijadwalkan, Langsung Tembak API Meta
                if (!$scheduled_at) {
                    $res = [];
                    if ($tgt === 'facebook') {
                        $res = publish_to_fb_direct($content, $accounts[$tgt]['access_token'], $accounts[$tgt]['page_id'], $target_media);
                    } elseif ($tgt === 'threads') {
                        $res = publish_to_threads_direct($content, $accounts[$tgt]['access_token'], $target_media);
                    }
                    
                    if (isset($res['error'])) {
                        $update = $pdo->prepare("UPDATE posts SET status = 'failed', error_log = ? WHERE id = ?");
                        $update->execute([$res['error'], $post_id]);
                        $error_msgs[] = ucfirst($tgt) . " Error: " . $res['error'];
                    } else {
                        $success_msgs[] = ucfirst($tgt);
                    }
                }
            }

            ob_clean();
            if (!$scheduled_at) {
                if (!empty($error_msgs) && empty($success_msgs)) {
                    // Semua Gagal
                    echo json_encode(['status' => 'error', 'message' => "Publish Gagal:\n" . implode("\n", $error_msgs)]);
                } else if (!empty($error_msgs) && !empty($success_msgs)) {
                    // Sebagian Gagal (Misal: FB sukses, Threads gagal)
                    echo json_encode(['status' => 'warning', 'message' => "Sukses di " . implode(" & ", $success_msgs) . ".\nTAPI Gagal di: " . implode(" | ", $error_msgs)]);
                } else {
                    // Semua Sukses
                    echo json_encode(['status' => 'success', 'message' => 'Berhasil dipublikasikan ke ' . implode(" & ", $success_msgs) . '!']);
                }
            } else {
                echo json_encode(['status' => 'success', 'message' => 'Berhasil masuk antrean jadwal.']);
            }
            exit;
            
        case 'get_posts':
            if (!is_logged_in()) { ob_clean(); echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
            // Sortir mutlak: Waktu Jadwal/Buat terbaru di atas (Descending Order)
            $stmt = $pdo->prepare("SELECT id, platform, content, status, scheduled_at, created_at, error_log 
                                   FROM posts 
                                   WHERE user_id = ? 
                                   ORDER BY COALESCE(scheduled_at, created_at) DESC, created_at DESC 
                                   LIMIT 100");
            $stmt->execute([$_SESSION['user_id']]);
            ob_clean(); echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]); exit;

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
            
            ob_clean(); echo json_encode(['status' => 'success', 'message' => 'Data berhasil dibatalkan dan dihapus.']); exit;
            
        default: ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Aksi invalid.']); exit;
    }
} catch (Throwable $e) { 
    ob_clean(); echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); exit; 
}

// =================== FUNGSI BANTUAN SERVER-SIDE ===================
function split_threads($text) {
    $paragraphs = preg_split('/\n\s*\n/', $text);
    $threads = [];
    foreach ($paragraphs as $p) {
        $trimmed = trim($p);
        if (empty($trimmed)) continue;
        if (mb_strlen($trimmed) > 490) {
            $words = explode(' ', $trimmed);
            $currentChunk = '';
            foreach ($words as $word) {
                if (mb_strlen($currentChunk . $word) > 490) {
                    $threads[] = trim($currentChunk);
                    $currentChunk = $word . ' ';
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
    } 
    else if (count($media_files) === 1 && $media_files[0]['type'] === 'image') {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/photos";
        $postData = ['message' => $text, 'source' => new CURLFile($media_files[0]['path'], $media_files[0]['mime']), 'access_token' => $token];
    }
    else if ($media_files[0]['type'] === 'video') {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/videos";
        $postData = ['description' => $text, 'source' => new CURLFile($media_files[0]['path'], $media_files[0]['mime']), 'access_token' => $token];
    }
    else {
        $attached_media = [];
        foreach ($media_files as $m) {
            if ($m['type'] === 'video') continue;
            $upUrl = "https://graph.facebook.com/v19.0/{$target_id}/photos";
            $upData = ['published' => 'false', 'source' => new CURLFile($m['path'], $m['mime']), 'access_token' => $token];
            $ch = curl_init($upUrl);
            curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $upData);
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
    if ($httpCode !== 200 || isset($resData['error'])) {
        return ['error' => $resData['error']['message'] ?? 'Unknown FB Error'];
    }
    return ['success' => true, 'id' => $resData['id'] ?? null];
}

// =================== FUNGSI THREADS ===================
function publish_to_threads_direct($content, $token, $media_files) {
    // Pemotongan thread otomatis di sisi PHP
    $threadsArray = split_threads($content);
    $reply_to_id = null; 

    foreach ($threadsArray as $index => $textChunk) {
        $url1 = "https://graph.threads.net/v1.0/me/threads";
        $data1 = ['text' => $textChunk, 'access_token' => $token];
        if ($reply_to_id) $data1['reply_to_id'] = $reply_to_id;

        $isVideo = false;
        // Hanya tempelkan media di thread PERTAMA
        if ($index === 0 && !empty($media_files)) {
            $firstMedia = $media_files[0];
            if ($firstMedia['type'] === 'image') {
                $data1['media_type'] = 'IMAGE'; $data1['image_url'] = $firstMedia['url'];
            } else if ($firstMedia['type'] === 'video') {
                $data1['media_type'] = 'VIDEO'; $data1['video_url'] = $firstMedia['url']; $isVideo = true;
            }
        } else {
            $data1['media_type'] = 'TEXT';
        }

        // Jeda Container
        if ($index > 0) sleep(6); 

        $ch1 = curl_init($url1); curl_setopt($ch1, CURLOPT_POST, true); curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($data1));
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
        $res1 = curl_exec($ch1); curl_close($ch1);
        
        $json1 = json_decode($res1, true);
        if (empty($json1['id'])) return ['error' => "Gagal buat container: " . ($json1['error']['message'] ?? $res1)];
        $containerId = $json1['id'];

        // Video Polling
        if ($isVideo) {
            $is_ready = false;
            for ($i = 0; $i < 6; $i++) { // Tunggu hingga 30 detik untuk render
                sleep(5); 
                $checkUrl = "https://graph.threads.net/v1.0/{$containerId}?fields=status,error_message&access_token={$token}";
                $chCheck = curl_init($checkUrl); curl_setopt($chCheck, CURLOPT_RETURNTRANSFER, true); curl_setopt($chCheck, CURLOPT_SSL_VERIFYPEER, false);
                $checkRes = curl_exec($chCheck); curl_close($chCheck);
                
                $checkData = json_decode($checkRes, true);
                if (isset($checkData['status']) && $checkData['status'] === 'FINISHED') {
                    $is_ready = true; break;
                }
            }
        }

        $url2 = "https://graph.threads.net/v1.0/me/threads_publish";
        $data2 = ['creation_id' => $containerId, 'access_token' => $token];

        $ch2 = curl_init($url2); curl_setopt($ch2, CURLOPT_POST, true); curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($data2));
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
        $res2 = curl_exec($ch2); curl_close($ch2);
        
        $json2 = json_decode($res2, true);
        if (empty($json2['id'])) return ['error' => "Gagal publish container: " . ($json2['error']['message'] ?? $res2)];
        
        $reply_to_id = $json2['id'];
        sleep(2); 
    }
    
    return ['success' => true];
}
?>