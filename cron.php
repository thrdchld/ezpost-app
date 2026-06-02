<?php
ignore_user_abort(true);
set_time_limit(0);

// Script Eksekusi Jadwal EZPost
require_once 'config.php';

$secret_key = 'EZPost1995';
if (!isset($_GET['secret']) || $_GET['secret'] !== $secret_key) {
    die("Akses Ditolak. Secret Key tidak valid.");
}

$current_time = date('Y-m-d H:i:s');

$stmt = $pdo->prepare("SELECT p.*, s.access_token, s.page_id 
                       FROM posts p 
                       JOIN social_accounts s ON p.user_id = s.user_id AND p.platform = s.provider
                       WHERE p.status = 'scheduled' AND p.scheduled_at <= ?");
$stmt->execute([$current_time]);
$posts = $stmt->fetchAll();

$isSecure = false;
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') { $isSecure = true; }
elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') { $isSecure = true; }
$protocol = $isSecure ? 'https' : 'http';

$domain = $_SERVER['HTTP_HOST'];
$path_dir = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$base_url = $protocol . "://" . $domain . $path_dir . "/";

// =========================================================================
// FUNGSI MESIN INDEPENDEN KHUSUS CRON
// =========================================================================

function cron_split_threads($text) {
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
                } else {
                    $currentChunk .= $word . ' ';
                }
            }
            if (trim($currentChunk)) $threads[] = trim($currentChunk);
        } else {
            $threads[] = $trimmed;
        }
    }
    return empty($threads) ? [""] : $threads;
}

function cron_publish_to_fb_direct($text, $token, $page_id, $media_files) {
    $target_id = !empty($page_id) ? $page_id : 'me';
    
    if (empty($media_files)) {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/feed";
        $postData = ['message' => $text, 'access_token' => $token];
    } 
    else if (count($media_files) === 1 && $media_files[0]['type'] === 'image') {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/photos";
        $postData = [
            'message' => $text,
            'source' => new CURLFile($media_files[0]['path'], $media_files[0]['mime']),
            'access_token' => $token
        ];
    }
    else if ($media_files[0]['type'] === 'video') {
        $url = "https://graph.facebook.com/v19.0/{$target_id}/videos";
        $postData = [
            'description' => $text, 
            'source' => new CURLFile($media_files[0]['path'], $media_files[0]['mime']),
            'access_token' => $token
        ];
    }
    else {
        $attached_media = [];
        foreach ($media_files as $m) {
            if ($m['type'] === 'video') continue;
            $upUrl = "https://graph.facebook.com/v19.0/{$target_id}/photos";
            $upData = [
                'published' => 'false',
                'source' => new CURLFile($m['path'], $m['mime']),
                'access_token' => $token
            ];
            $ch = curl_init($upUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $upData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $res = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if (isset($res['id'])) $attached_media[] = '{"media_fbid":"'.$res['id'].'"}';
        }
        
        $url = "https://graph.facebook.com/v19.0/{$target_id}/feed";
        $postData = ['message' => $text, 'access_token' => $token];
        foreach ($attached_media as $k => $m) {
            $postData["attached_media[$k]"] = $m;
        }
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);
    if ($httpCode !== 200 || isset($resData['error'])) {
        return ['error' => $resData['error']['message'] ?? 'Unknown FB Error'];
    }
    return ['success' => true, 'id' => $resData['id'] ?? null];
}

function cron_publish_to_threads_direct($content, $token, $media_files) {
    $threadsArray = cron_split_threads($content);
    $reply_to_id = null; 

    foreach ($threadsArray as $index => $textChunk) {
        $url1 = "https://graph.threads.net/v1.0/me/threads";
        $data1 = ['text' => $textChunk, 'access_token' => $token];
        if ($reply_to_id) $data1['reply_to_id'] = $reply_to_id;

        $isVideo = false;
        if ($index === 0 && !empty($media_files)) {
            $firstMedia = $media_files[0];
            if ($firstMedia['type'] === 'image') {
                $data1['media_type'] = 'IMAGE';
                $data1['image_url'] = $firstMedia['url'];
            } else if ($firstMedia['type'] === 'video') {
                $data1['media_type'] = 'VIDEO';
                $data1['video_url'] = $firstMedia['url'];
                $isVideo = true;
            }
        } else {
            $data1['media_type'] = 'TEXT';
        }

        if ($index > 0) sleep(4); 

        $containerId = null;
        $lastError = "";
        for ($retry = 0; $retry < 3; $retry++) {
            $ch1 = curl_init($url1);
            curl_setopt($ch1, CURLOPT_POST, true);
            curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($data1));
            curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch1, CURLOPT_SSL_VERIFYPEER, false);
            $res1 = curl_exec($ch1);
            curl_close($ch1);
            
            $json1 = json_decode($res1, true);
            if (!empty($json1['id'])) {
                $containerId = $json1['id'];
                break;
            }
            $lastError = $json1['error']['message'] ?? $res1;
            
            if (stripos($lastError, 'permission') !== false || stripos($lastError, 'OAuthException') !== false) {
                if(stripos($lastError, 'threads_manage_replies') !== false || $index > 0){
                    return ['error' => "Gagal Threads (Part ".($index+1)."): Token Anda DITOLAK. Wajib centang 'threads_manage_replies' saat buat token!"];
                }
            }
            sleep(3);
        }

        if (!$containerId) return ['error' => "Gagal buat container (Part ".($index+1)."): " . $lastError];

        // JEDA EMAS (GOLDEN DELAY)
        if (!$isVideo) { 
            sleep(2); 
        } else {
            $is_ready = false;
            for ($i = 0; $i < 8; $i++) { 
                sleep(8); 
                $checkUrl = "https://graph.threads.net/v1.0/{$containerId}?fields=status,error_message&access_token={$token}";
                $chCheck = curl_init($checkUrl);
                curl_setopt($chCheck, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($chCheck, CURLOPT_SSL_VERIFYPEER, false);
                $checkRes = curl_exec($chCheck);
                curl_close($chCheck);
                
                $checkData = json_decode($checkRes, true);
                if (isset($checkData['status']) && $checkData['status'] === 'FINISHED') {
                    $is_ready = true;
                    break;
                }
            }
            if (!$is_ready) return ['error' => "Video gagal diproses Meta."];
        }

        $url2 = "https://graph.threads.net/v1.0/me/threads_publish";
        $data2 = ['creation_id' => $containerId, 'access_token' => $token];

        $publishedId = null;
        $lastErrorPublish = "";
        for ($retry = 0; $retry < 3; $retry++) {
            $ch2 = curl_init($url2);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, http_build_query($data2));
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            $res2 = curl_exec($ch2);
            curl_close($ch2);
            
            $json2 = json_decode($res2, true);
            if (!empty($json2['id'])) {
                $publishedId = $json2['id'];
                break;
            }
            $lastErrorPublish = $json2['error']['message'] ?? $res2;
            sleep(3);
        }

        if (!$publishedId) return ['error' => "Gagal publish container (Part ".($index+1)."): " . $lastErrorPublish];
        
        $reply_to_id = $publishedId;
        sleep(3); 
    }
    
    return ['success' => true];
}


// =========================================================================
// SIKLUS EKSEKUSI DATA
// =========================================================================

$processed_count = 0;
$success_count = 0;
$failed_count = 0;

foreach ($posts as $post) {
    $processed_count++;
    
    $stmtMedia = $pdo->prepare("SELECT file_path, media_type FROM post_media WHERE post_id = ?");
    $stmtMedia->execute([$post['id']]);
    $media_rows = $stmtMedia->fetchAll();

    $media_files = [];
    foreach ($media_rows as $mr) {
        $abs_path = realpath(__DIR__ . '/' . $mr['file_path']);
        if ($abs_path && file_exists($abs_path)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $abs_path);
            finfo_close($finfo);

            $media_files[] = [
                'path' => $abs_path,
                'url'  => $base_url . $mr['file_path'],
                'type' => $mr['media_type'],
                'mime' => $mime
            ];
        }
    }

    $result = ['error' => 'API Eksekusi Gagal di Cron'];
    
    try {
        if ($post['platform'] === 'facebook') {
            $result = cron_publish_to_fb_direct($post['content'], $post['access_token'], $post['page_id'], $media_files);
        } else if ($post['platform'] === 'threads') {
            $result = cron_publish_to_threads_direct($post['content'], $post['access_token'], $media_files);
        }

        // RECONNECT DATABASE UNTUK MENCEGAH ERROR 2006
        global $db_host, $db_name, $db_user, $db_pass;
        try {
            $pdo = new PDO("mysql:host=$db_host;port=4000;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
            ]);
        } catch (Exception $e) {}

        if (isset($result['success'])) {
            $pdo->prepare("UPDATE posts SET status = 'published', error_log = NULL WHERE id = ?")->execute([$post['id']]);
            $success_count++;
        } else {
            $pdo->prepare("UPDATE posts SET status = 'failed', error_log = ? WHERE id = ?")->execute([$result['error'] ?? 'Unknown Error', $post['id']]);
            $failed_count++;
        }
    } catch (Exception $e) {
        $pdo->prepare("UPDATE posts SET status = 'failed', error_log = ? WHERE id = ?")->execute([$e->getMessage(), $post['id']]);
        $failed_count++;
    }
}

echo "<div style='font-family: monospace; padding: 20px; background: #111; color: #0f0;'>";
echo "<h2>[ EZPOST CRON-JOB EXECUTED ]</h2>";
echo "Waktu Server: {$current_time}<br>";
echo "-------------------------------------<br>";
echo "Total Antrian Diproses : {$processed_count}<br>";
echo "Berhasil Dipublish     : {$success_count}<br>";
echo "Gagal (Error API)      : {$failed_count}<br>";
echo "-------------------------------------<br>";
echo "Cron berjalan dengan lancar. Anda bisa menutup tab ini.";
echo "</div>";
?>