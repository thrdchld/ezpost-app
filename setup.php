<?php
// Script Instalasi Otomatis Tabel EZPost ke TiDB
require_once 'config.php';

echo "<div style='font-family: monospace; padding: 20px; background: #111; color: #0f0;'>";
echo "<h2>[ EZPOST DATABASE INSTALLER ]</h2>";

try {
    $sql = file_get_contents('schema.sql');
    if (!$sql) {
        die("❌ Gagal: File schema.sql tidak ditemukan!");
    }
    
    // Eksekusi semua query di schema.sql
    $pdo->exec($sql);
    
    echo "✅ SUKSES: Semua tabel (users, social_accounts, posts, post_media) berhasil dibuat di TiDB!<br><br>";
    echo "Anda sekarang bisa login menggunakan email dan password yang Anda berikan.<br><br>";
    echo "<a href='index.php' style='color: #0ff;'>=> KLIK DI SINI UNTUK MASUK KE APLIKASI EZPOST</a>";
    
} catch (PDOException $e) {
    echo "❌ ERROR DATABASE: " . $e->getMessage();
}
echo "</div>";
?>