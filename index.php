<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZPost Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { background-color: #121212; color: #fff; }
        .card { background-color: #1e1e1e; border: 1px solid #333; }
        .hidden { display: none; }
        .error-msg { background: #331111; color: #ff6b6b; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 0.85rem;}
    </style>
</head>
<body>

<div class="container mt-5">
    <!-- FORM LOGIN -->
    <?php if (!is_logged_in()): ?>
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 shadow">
                <h3 class="text-center mb-4">EZPost Login</h3>
                <form id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk Aplikasi</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- DASHBOARD UTAMA -->
    <?php else: ?>
    <div class="row">
        <div class="col-12 mb-4 d-flex justify-content-between align-items-center">
            <h2>🚀 EZPost Dashboard</h2>
            <div>
                <span class="text-muted me-3">Server Time: <?php echo date('H:i T'); ?></span>
                <a href="#" onclick="alert('Untuk Logout, silakan tutup browser.');" class="btn btn-sm btn-outline-danger">Logout</a>
            </div>
        </div>

        <div class="col-md-8">
            <ul class="nav nav-tabs mb-4" id="mainTabs">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#post-tab">Buat Postingan</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#history-tab" onclick="loadHistory()">Riwayat & Antrean</a></li>
            </ul>

            <div class="tab-content">
                <!-- TAB BUAT POSTINGAN -->
                <div class="tab-pane fade show active" id="post-tab">
                    <div class="card p-4 shadow-sm">
                        <form id="postForm" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="save_post">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="mb-3">
                                <label>Pilih Platform</label>
                                <select name="platform" class="form-select">
                                    <option value="threads">Threads</option>
                                    <option value="facebook">Facebook Fanspage</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label>Isi Konten / Thread Utama</label>
                                <textarea name="content" class="form-control" rows="4" placeholder="Ketik status Anda di sini..." required></textarea>
                            </div>

                            <div class="mb-3">
                                <label>Lampirkan Media (Foto/Video Maks 10 file)</label>
                                <input type="file" name="media[]" class="form-control bg-dark text-light" multiple accept="image/*,video/*">
                                <small class="text-muted">Proses upload video mungkin butuh beberapa detik, jangan tutup layar.</small>
                            </div>

                            <div class="mb-4">
                                <label>Waktu Tayang (Kosongkan jika ingin dijadwalkan otomatis / tayang sekarang)</label>
                                <input type="datetime-local" name="scheduled_at" class="form-control bg-dark text-light">
                            </div>

                            <button type="submit" id="btnSubmitPost" class="btn btn-success btn-lg w-100">Jadwalkan Postingan</button>
                        </form>
                    </div>
                </div>

                <!-- TAB RIWAYAT -->
                <div class="tab-pane fade" id="history-tab">
                    <div id="history-container">
                        <div class="text-center text-muted p-5">Memuat Data...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <!-- WIDGET STATUS AKUN -->
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-dark fw-bold">Status API & Akun</div>
                <div class="card-body">
                    <button class="btn btn-sm btn-outline-info w-100 mb-3" onclick="checkStatus()">Cek Ulang Status Koneksi</button>
                    <ul class="list-group list-group-flush bg-transparent">
                        <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                            Facebook Page
                            <span id="status-fb" class="badge bg-secondary">Mengecek...</span>
                        </li>
                        <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                            Threads
                            <span id="status-th" class="badge bg-secondary">Mengecek...</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const csrfToken = '<?php echo $_SESSION['csrf_token'] ?? ""; ?>';

$(document).ready(function() {
    <?php if (is_logged_in()): ?>
        checkStatus();
    <?php endif; ?>

    // Form Login
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        $.post('api.php', $(this).serialize(), function(res) {
            if(res.status === 'success') location.reload();
            else alert(res.message);
        }, 'json');
    });

    // Form Submit Postingan Baru
    $('#postForm').submit(function(e) {
        e.preventDefault();
        let btn = $('#btnSubmitPost');
        btn.prop('disabled', true).html('Mengunggah Media... Mohon Tunggu ⏳');
        
        let formData = new FormData(this);
        $.ajax({
            url: 'api.php', type: 'POST', data: formData,
            contentType: false, processData: false,
            success: function(res) {
                alert(res.message);
                if(res.status === 'success') {
                    $('#postForm')[0].reset();
                    $('.nav-tabs a[href="#history-tab"]').tab('show');
                    loadHistory();
                }
            },
            complete: function() { btn.prop('disabled', false).html('Jadwalkan Postingan'); }
        });
    });
    
    // Fitur: Paksa Tayang (Kirim Sekarang)
    $(document).on('click', '.force-post-btn', function() {
        let btn = $(this);
        let postId = btn.data('id');
        
        btn.prop('disabled', true).removeClass('btn-primary').addClass('btn-warning').html('⏳ Eksekusi API...');
        
        $.post('api.php', { action: 'force_publish', post_id: postId, csrf_token: csrfToken }, function(res) {
            if(res.status === 'success') {
                // Tembak Cron Diam-diam
                $.get('cron.php?secret=EZPost1995', function() {
                    alert("✅ Sukses terkirim ke Meta!");
                    loadHistory(); // Segarkan tampilan list
                }).fail(function() {
                    alert("Jadwal sudah diperbarui ke waktu sekarang, tapi cron gagal ditembak. Tunggu cron otomatis berjalan.");
                    loadHistory();
                });
            } else {
                alert(res.message);
                btn.prop('disabled', false).html('🚀 Kirim Sekarang');
            }
        }, 'json');
    });
});

function checkStatus() {
    $.post('api.php', { action: 'check_status', csrf_token: csrfToken }, function(res) {
        if(res.status === 'success') {
            $('#status-fb').attr('class', res.facebook ? 'badge bg-success' : 'badge bg-danger').text(res.facebook ? '✔ OK' : 'Belum Terhubung');
            $('#status-th').attr('class', res.threads ? 'badge bg-success' : 'badge bg-danger').text(res.threads ? '✔ OK' : 'Belum Terhubung');
        }
    }, 'json');
}

function loadHistory() {
    $('#history-container').html('<div class="text-center p-3">Memuat riwayat...</div>');
    $.post('api.php', { action: 'get_history', csrf_token: csrfToken }, function(res) {
        let html = '';
        if(res.data.length === 0) {
            html = '<div class="text-center text-muted p-5">Belum ada postingan.</div>';
        } else {
            res.data.forEach(post => {
                let badgeClass = 'bg-secondary';
                let btnHtml = '';
                let statusLabel = post.status.toUpperCase();
                
                // Variasi Warna Badge & Label
                if(post.status === 'published') {
                    badgeClass = 'bg-success';
                } else if(post.status === 'failed') {
                    badgeClass = 'bg-danger';
                } else if(post.status === 'scheduled') {
                    badgeClass = 'bg-warning text-dark';
                    statusLabel = 'ANTREAN / SCHEDULED';
                }

                // Jika status masih Antre atau Gagal, munculkan tombol manual!
                if(post.status === 'scheduled' || post.status === 'failed') {
                    btnHtml = `<button class="btn btn-sm btn-primary force-post-btn mt-2" data-id="${post.id}">🚀 Kirim Sekarang</button>`;
                }
                
                // Menampilkan Log Error jika Gagal
                let errorHtml = '';
                if(post.status === 'failed' && post.error_log) {
                    errorHtml = `<div class="error-msg mt-2"><b>Alasan Gagal:</b><br>${post.error_log}</div>`;
                }

                html += `
                <div class="card mb-3 border-secondary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="badge ${badgeClass}">${statusLabel}</span>
                            <small class="text-info">${post.platform.toUpperCase()}</small>
                        </div>
                        <p class="mb-2" style="white-space: pre-wrap;">${post.content}</p>
                        <small class="text-muted">Jadwal Tayang: ${post.scheduled_at_formatted}</small>
                        ${errorHtml}
                        <br>${btnHtml}
                    </div>
                </div>`;
            });
        }
        $('#history-container').html(html);
    }, 'json');
}
</script>
</body>
</html>