<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZPost Enterprise</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class', theme: { extend: { colors: { darkbg: '#0f1115', darkcard: '#161b22', darkborder: '#30363d' } } } }
    </script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Flatpickr (Untuk Pemilihan Waktu yg Bagus) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }
        body { background-color: #0f1115; color: #c9d1d9; }
        
        /* Kalender Mini CSS */
        .cal-day { aspect-ratio: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; cursor: pointer; border-radius: 0.5rem; position: relative; transition: all 0.2s; }
        .cal-day:hover { background-color: #1f2937; }
        .cal-day.active { background-color: #2563eb; color: white; font-weight: bold; }
        .cal-day.today { border: 1px solid #2563eb; }
        .cal-dot { position: absolute; bottom: 4px; width: 6px; height: 6px; border-radius: 50%; background-color: #3b82f6; }
    </style>
</head>
<body class="antialiased font-sans">

<!-- ICONS (Aman Tidak Akan Hilang) -->
<svg style="display: none;">
    <symbol id="icon-fb" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></symbol>
    <symbol id="icon-threads" viewBox="0 0 24 24"><path d="M16.711 13.333c-0.198 1.401-1.037 3.237-2.934 3.237-1.498 0-2.483-1.077-2.483-2.616v-2.071c0-1.579 0.999-2.665 2.528-2.665 1.758 0 2.639 1.589 2.766 2.871h1.996c-0.218-2.222-1.905-4.571-4.762-4.571-2.645 0-4.582 1.872-4.582 4.417v2.019c0 2.502 1.87 4.364 4.509 4.364 2.876 0 4.607-2.235 4.887-4.985h-1.925z M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.666 12.06c0 3.398-2.158 5.76-5.59 5.76-3.415 0-5.632-2.316-5.632-5.717v-1.993c0-3.428 2.22-5.761 5.645-5.761 3.266 0 5.485 2.193 5.568 5.43h-2.029c-0.106-2.122-1.512-3.593-3.539-3.593-2.195 0-3.593 1.487-3.593 3.924v1.993c0 2.392 1.385 3.882 3.58 3.882 2.217 0 3.6-1.554 3.6-4.004h1.99z"/></symbol>
</svg>

<!-- TOAST -->
<div id="toast" class="fixed top-5 right-5 z-50 transform transition-all duration-300 translate-x-full opacity-0">
    <div class="bg-gray-800 text-white border-l-4 border-blue-500 shadow-2xl p-4 rounded flex items-center gap-3">
        <span id="toastIcon">💡</span><span id="toastMsg" class="font-medium">Notifikasi</span>
    </div>
</div>

<div class="container mx-auto px-4 py-6 max-w-[1400px]">
    
    <!-- HEADER -->
    <?php if (is_logged_in()): ?>
    <div class="flex justify-between items-center mb-6 border-b border-darkborder pb-4">
        <h1 class="text-2xl font-bold text-white tracking-wide">EZPOST PLANNER</h1>
        <div class="flex gap-4">
            <button onclick="switchTab('post')" id="tabBtn-post" class="px-5 py-2 font-semibold text-sm rounded-lg bg-blue-600 text-white transition-all">Tulis Baru</button>
            <button onclick="switchTab('history')" id="tabBtn-history" class="px-5 py-2 font-semibold text-sm rounded-lg text-gray-400 hover:text-white transition-all">Planner Jadwal</button>
            <button onclick="logout()" class="px-4 py-2 font-semibold text-sm text-red-400 border border-red-900 rounded-lg">Keluar</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- 1. AREA LOGIN -->
    <?php if (!is_logged_in()): ?>
    <div class="flex justify-center items-center min-h-[70vh]">
        <div class="w-full max-w-sm bg-darkcard border border-darkborder rounded-xl p-8 shadow-2xl">
            <h3 class="text-xl font-bold text-center text-white mb-6">Login ke Sistem</h3>
            <form id="loginForm" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <div><label class="text-sm text-gray-400">Email</label><input type="email" name="email" required class="w-full mt-1 px-4 py-2 bg-[#0d1117] border border-gray-600 rounded text-white outline-none focus:border-blue-500"></div>
                <div><label class="text-sm text-gray-400">Password</label><input type="password" name="password" required class="w-full mt-1 px-4 py-2 bg-[#0d1117] border border-gray-600 rounded text-white outline-none focus:border-blue-500"></div>
                <button type="submit" id="btnLogin" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2.5 rounded mt-4">Masuk</button>
            </form>
        </div>
    </div>
    
    <!-- 2. AREA DASHBOARD (TULIS & PLANNER) -->
    <?php else: ?>
    
    <!-- TAMPILAN BUAT POSTINGAN -->
    <div id="tab-post" class="block animate-fade-in">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- KOLOM KIRI: FORM & JADWAL -->
            <div class="lg:col-span-7 space-y-6">
                <div class="bg-darkcard border border-darkborder rounded-xl p-6 shadow-sm">
                    <form id="postForm" enctype="multipart/form-data" class="space-y-5">
                        <input type="hidden" name="action" value="save_post">
                        <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Target Publish</label>
                            <select name="platform" id="input_platform" class="w-full px-4 py-3 bg-[#0d1117] border border-darkborder rounded-lg text-white font-medium focus:border-blue-500 outline-none">
                                <option value="facebook">Facebook saja</option>
                                <option value="threads">Threads saja</option>
                                <option value="fb_threads_media">Facebook + Threads</option>
                                <option value="fb_threads_nomedia" selected>Facebook + Threads (Threads tanpa media) - [Default]</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2 flex justify-between">
                                <span>Tulis konten...</span>
                                <button type="button" onclick="clearDraft()" class="text-xs text-red-400 hover:underline font-normal">Hapus Draft</button>
                            </label>
                            <textarea name="content" id="input_content" rows="6" placeholder="Ketik ide brilian Anda di sini..." required class="w-full px-4 py-3 bg-[#0d1117] border border-darkborder rounded-lg text-white resize-none outline-none focus:border-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Lampiran Media</label>
                            <input type="file" name="media[]" id="input_media" multiple accept="image/*,video/*" class="w-full px-4 py-2 bg-[#0d1117] border border-darkborder rounded-lg text-gray-300 cursor-pointer">
                        </div>

                        <!-- FLATPICKR JADWAL TAYANG -->
                        <div class="pt-5 border-t border-darkborder">
                            <h4 class="text-sm font-semibold text-gray-300 mb-3">Jadwal Tayang (Klik & Pilih)</h4>
                            <input type="text" id="input_datetime" name="scheduled_at" placeholder="Kosongkan untuk publish Detik Ini Juga" class="w-full px-4 py-3 bg-[#0d1117] border border-gray-600 rounded-lg text-white outline-none focus:border-blue-500 cursor-pointer text-center font-bold">
                        </div>
                    </form>
                </div>
            </div>

            <!-- KOLOM KANAN: SMART BUTTON & LIVE PREVIEW -->
            <div class="lg:col-span-5 space-y-6">
                <!-- SMART BUTTON -->
                <div class="bg-darkcard border border-darkborder rounded-xl p-5 shadow-sm">
                    <button type="submit" form="postForm" id="btnSubmitPost" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 rounded-xl transition-all shadow-[0_0_15px_rgba(16,185,129,0.2)] text-lg">
                        🚀 Simpan & Publish
                    </button>
                </div>

                <!-- LIVE PREVIEW TABS -->
                <div class="bg-darkcard border border-darkborder rounded-xl shadow-sm overflow-hidden flex flex-col h-[500px]">
                    <div class="bg-[#0d1117] px-4 pt-3 flex gap-2 border-b border-darkborder" id="previewTabsContainer"></div>
                    <div class="p-4 flex-1 overflow-y-auto bg-[#161b22]" id="previewContentContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- TAMPILAN PUBLISH PLANNER (KALENDER & TIMELINE) -->
    <div id="tab-history" class="hidden animate-fade-in">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- PANEL KIRI: KALENDER -->
            <div class="lg:col-span-4 space-y-4">
                <div class="bg-darkcard border border-darkborder rounded-xl p-5 shadow-sm sticky top-4">
                    <div class="flex justify-between items-center mb-4">
                        <button onclick="changeMonth(-1)" class="p-2 hover:bg-gray-800 rounded text-gray-400">&larr;</button>
                        <div class="flex gap-2">
                            <select id="cal-month" class="bg-[#0d1117] border border-darkborder text-white text-sm rounded px-2 py-1 outline-none" onchange="renderCalendar()">
                                <option value="0">Januari</option><option value="1">Februari</option><option value="2">Maret</option><option value="3">April</option><option value="4">Mei</option><option value="5">Juni</option><option value="6">Juli</option><option value="7">Agustus</option><option value="8">September</option><option value="9">Oktober</option><option value="10">November</option><option value="11">Desember</option>
                            </select>
                            <select id="cal-year" class="bg-[#0d1117] border border-darkborder text-white text-sm rounded px-2 py-1 outline-none" onchange="renderCalendar()"></select>
                        </div>
                        <button onclick="changeMonth(1)" class="p-2 hover:bg-gray-800 rounded text-gray-400">&rarr;</button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center mb-2 text-xs font-bold text-gray-500">
                        <div>Mi</div><div>Se</div><div>Sl</div><div>Ra</div><div>Ka</div><div>Ju</div><div>Sa</div>
                    </div>
                    <div id="calendar-grid" class="grid grid-cols-7 gap-1 text-sm"></div>
                    
                    <div class="mt-4 pt-4 border-t border-darkborder flex justify-between items-center text-xs">
                        <button onclick="goToToday()" class="text-blue-400 hover:underline">Kembali ke Hari Ini</button>
                        <span class="flex items-center gap-1 text-gray-400"><div class="w-2 h-2 rounded-full bg-blue-500"></div> Ada Jadwal</span>
                    </div>
                </div>
            </div>

            <!-- PANEL KANAN: TIMELINE -->
            <div class="lg:col-span-8">
                <!-- FILTER BAR -->
                <div class="bg-darkcard border border-darkborder rounded-xl p-4 mb-4 flex flex-wrap gap-x-6 gap-y-3 text-sm">
                    <div class="flex items-center gap-2"><span class="text-gray-500 font-medium">Status:</span><select id="flt-status" class="bg-[#0d1117] border border-darkborder text-white rounded px-3 py-1 outline-none" onchange="renderTimeline()"><option value="all">Semua</option><option value="scheduled">Scheduled (Antrean)</option><option value="published">Published (Sukses)</option><option value="failed">Failed (Gagal)</option></select></div>
                    <div class="flex items-center gap-2"><span class="text-gray-500 font-medium">Platform:</span><select id="flt-platform" class="bg-[#0d1117] border border-darkborder text-white rounded px-3 py-1 outline-none" onchange="renderTimeline()"><option value="all">Semua</option><option value="facebook">Facebook</option><option value="threads">Threads</option></select></div>
                </div>

                <h2 id="timeline-header" class="text-xl font-bold text-white mb-4 border-b border-darkborder pb-2">Jadwal: Hari Ini</h2>
                <div id="timeline-container" class="space-y-4"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- MODAL EDIT JADWAL -->
<div id="editModal" class="fixed inset-0 bg-black/80 z-50 hidden flex justify-center items-center opacity-0 transition-opacity duration-300">
    <div class="bg-darkcard border border-darkborder w-full max-w-sm rounded-xl p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4">Ubah Waktu Tayang</h3>
        <form id="editScheduleForm" class="space-y-4">
            <input type="hidden" id="edit_post_id">
            <input type="text" id="edit_datetime" required class="w-full px-4 py-3 bg-[#0d1117] border border-gray-600 rounded text-white text-center font-bold outline-none focus:border-blue-500">
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded font-medium">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded font-medium">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
// ==========================================
// INISIALISASI UTAMA & SCRIPT LOGIN KEBUL
// ==========================================
const csrfToken = $('#csrf_token').val() || '';

// Script Login dipisah di atas agar kebal dari error UI lain
$(document).ready(function() {
    if ($('#loginForm').length > 0) {
        $('#loginForm').submit(function(e) {
            e.preventDefault();
            $('#btnLogin').prop('disabled', true).text('Memverifikasi...');
            $.ajax({
                url: 'api.php', type: 'POST', data: $(this).serialize(), dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') { window.location.reload(); } 
                    else { alert(res.message); $('#btnLogin').prop('disabled', false).text('Masuk'); }
                },
                error: function() { alert("Koneksi ke server terputus."); $('#btnLogin').prop('disabled', false).text('Masuk'); }
            });
        });
    }

    // Jika user sudah login, jalankan fitur UI canggih
    if ($('#postForm').length > 0) {
        initUI();
    }
});

// ==========================================
// FUNGSI DASHBOARD (Dijalankan hanya jika login)
// ==========================================
let allPosts = []; 
let currentCalDate = new Date();
let selectedDateStr = "";

function initUI() {
    // 1. Inisialisasi Flatpickr
    flatpickr("#input_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true });
    
    // 2. Draft Auto-Save
    if(localStorage.getItem('ezpost_draft')) $('#input_content').val(localStorage.getItem('ezpost_draft'));
    $('#input_content').on('input', function() { localStorage.setItem('ezpost_draft', $(this).val()); });

    // 3. Live Preview Setup
    $('#input_platform, #input_content, #input_media').on('input change', updatePreview);
    updatePreview();
    
    // 4. Planner Setup
    let ySel = $('#cal-year');
    let currYr = new Date().getFullYear();
    for(let i = currYr - 1; i <= currYr + 2; i++) ySel.append(`<option value="${i}">${i}</option>`);
    goToToday();

    // 5. Submit Form
    $('#postForm').submit(async function(e) {
        e.preventDefault();
        let btn = $('#btnSubmitPost'); let ori = btn.html();
        btn.prop('disabled', true).html('⏳ Memproses Upload...');
        
        try {
            const res = await $.ajax({ url: 'api.php', type: 'POST', data: new FormData(this), contentType: false, processData: false, dataType: 'json' });
            showToast(res.message, res.status);
            if(res.status === 'success') {
                $('#postForm')[0].reset(); clearDraft();
                $('#input_platform').val('fb_threads_nomedia').trigger('change');
                flatpickr("#input_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true }).clear();
                updatePreview(); loadAllHistory();
            }
        } catch(e) { showToast("Gagal terhubung ke API.", "error"); }
        finally { btn.prop('disabled', false).html(ori); }
    });

    // 6. Action Buttons Timeline (Kirim, Edit, Hapus)
    $(document).on('click', '.timeline-action', async function() {
        let btn = $(this); let action = btn.data('act'); let pid = btn.data('id');
        
        if (action === 'delete') {
            if(!confirm("Yakin ingin menghapus postingan ini selamanya?")) return;
            btn.html('⏳').prop('disabled', true);
            $.post('api.php', { action: 'delete_post', post_id: pid, csrf_token: csrfToken }, function(res) {
                showToast(res.message, res.status); loadAllHistory();
            }, 'json');
            return;
        }

        if (action === 'force') {
            btn.html('⏳').prop('disabled', true);
            $.post('api.php', { action: 'force_publish', post_id: pid, csrf_token: csrfToken }, function(res) {
                showToast("Memaksa dikirim detik ini...", "success");
                $.get('cron.php?secret=EZPost1995').always(() => loadAllHistory());
            }, 'json');
            return;
        }
    });

    // 7. Edit Schedule Submit
    $('#editScheduleForm').submit(function(e) {
        e.preventDefault();
        let pid = $('#edit_post_id').val(); let dt = $('#edit_datetime').val();
        $.post('api.php', { action: 'edit_schedule', post_id: pid, new_datetime: dt, csrf_token: csrfToken }, function(res) {
            showToast(res.message, res.status);
            if(res.status === 'success') { closeEditModal(); loadAllHistory(); }
        }, 'json');
    });
}

// ==========================================
// LIVE PREVIEW ASLI (FB & THREADS)
// ==========================================
function updatePreview() {
    const plat = $('#input_platform').val();
    const content = $('#input_content').val() || '<span class="text-gray-500 italic">Mulai mengetik...</span>';
    const hasMedia = $('#input_media')[0].files.length > 0;
    
    let isFb = (plat === 'facebook' || plat.startsWith('fb_threads'));
    let isTh = (plat === 'threads' || plat.startsWith('fb_threads'));
    
    let tabs = '';
    if(isFb) tabs += `<button class="prev-tab-btn px-4 py-2 font-bold text-sm border-b-2 border-blue-500 text-blue-500" onclick="showTab(this, 'fb')">Facebook</button>`;
    if(isTh) tabs += `<button class="prev-tab-btn px-4 py-2 font-bold text-sm border-b-2 ${!isFb ? 'border-white text-white' : 'border-transparent text-gray-500'}" onclick="showTab(this, 'th')">Threads</button>`;
    $('#previewTabsContainer').html(tabs);

    let mb = hasMedia ? `<div class="w-full h-32 bg-gray-800 rounded mt-3 flex justify-center items-center text-xs text-gray-400">📸 Media Terlampir</div>` : '';
    let thMb = (hasMedia && plat !== 'fb_threads_nomedia') ? mb : '';

    // UI Facebook Asli
    let fbHtml = `<div id="prev-fb" class="prev-content ${isFb ? 'block' : 'hidden'}">
        <div class="flex items-center gap-3 mb-2"><div class="w-10 h-10 rounded-full bg-blue-600"></div><div><div class="font-bold text-sm text-gray-200">Halaman Facebook Anda</div><div class="text-xs text-gray-500">Baru saja · 🌎</div></div></div>
        <div class="text-sm text-gray-100 whitespace-pre-wrap">${content}</div>${mb}
        <div class="flex justify-between border-t border-gray-700 mt-4 pt-2 text-gray-400 text-xs font-bold px-4"><span>👍 Suka</span><span>💬 Komentar</span><span>↪️ Bagikan</span></div>
    </div>`;

    // UI Threads Asli
    let thHtml = `<div id="prev-th" class="prev-content ${!isFb ? 'block' : 'hidden'}">
        <div class="flex gap-3"><div class="flex flex-col items-center"><div class="w-10 h-10 rounded-full bg-gray-600 z-10"></div><div class="w-0.5 bg-gray-700 h-full mt-2"></div></div>
        <div class="pb-4 w-full"><div class="font-bold text-sm text-gray-200">@username_threads</div><div class="text-sm text-gray-100 mt-1 whitespace-pre-wrap">${content}</div>${thMb}
        <div class="flex gap-4 mt-4 text-gray-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 8 9 8z"></path></svg><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg></div></div></div>
    </div>`;

    $('#previewContentContainer').html(fbHtml + thHtml);
}

function showTab(btn, target) {
    $('.prev-content').addClass('hidden'); $(`#prev-${target}`).removeClass('hidden');
    $('.prev-tab-btn').removeClass('border-blue-500 border-white text-blue-500 text-white').addClass('border-transparent text-gray-500');
    $(btn).removeClass('border-transparent text-gray-500').addClass(target === 'fb' ? 'border-blue-500 text-blue-500' : 'border-white text-white');
}

// ==========================================
// KALENDER & TIMELINE
// ==========================================
function loadAllHistory() {
    $.post('api.php', { action: 'get_history', csrf_token: csrfToken }, function(res) {
        if(res.status === 'success') { allPosts = res.data; renderCalendar(); renderTimeline(); }
    }, 'json');
}

function changeMonth(dir) {
    currentCalDate.setMonth(currentCalDate.getMonth() + dir);
    $('#cal-month').val(currentCalDate.getMonth()); $('#cal-year').val(currentCalDate.getFullYear());
    renderCalendar();
}

function goToToday() {
    currentCalDate = new Date();
    $('#cal-month').val(currentCalDate.getMonth()); $('#cal-year').val(currentCalDate.getFullYear());
    let y = currentCalDate.getFullYear(); let m = String(currentCalDate.getMonth() + 1).padStart(2, '0'); let d = String(currentCalDate.getDate()).padStart(2, '0');
    selectedDateStr = `${y}-${m}-${d}`;
    loadAllHistory();
}

function selectDate(dateStr) { selectedDateStr = dateStr; renderCalendar(); renderTimeline(); }

function renderCalendar() {
    let year = parseInt($('#cal-year').val()); let month = parseInt($('#cal-month').val());
    currentCalDate = new Date(year, month, 1);
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    let gridHtml = '';
    for(let i=0; i<firstDay; i++) gridHtml += `<div></div>`;
    
    const todayStr = new Date().toISOString().split('T')[0];
    
    for(let d=1; d<=daysInMonth; d++) {
        let loopDate = `${year}-${String(month+1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        let isToday = (loopDate === todayStr) ? 'today' : '';
        let isActive = (loopDate === selectedDateStr) ? 'active' : 'text-gray-300';
        let hasPost = allPosts.some(p => p.scheduled_date_raw === loopDate);
        let dotHtml = hasPost ? `<div class="cal-dot"></div>` : '';
        gridHtml += `<div class="cal-day ${isToday} ${isActive}" onclick="selectDate('${loopDate}')">${d}${dotHtml}</div>`;
    }
    $('#calendar-grid').html(gridHtml);
}

function renderTimeline() {
    let fStat = $('#flt-status').val(); let fPlat = $('#flt-platform').val();
    let dObj = new Date(selectedDateStr);
    $('#timeline-header').html(`Jadwal: ${dObj.toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})}`);

    let filtered = allPosts.filter(p => {
        return (p.scheduled_date_raw === selectedDateStr) && 
               (fStat === 'all' || p.status === fStat) && 
               (fPlat === 'all' || p.platform === fPlat);
    });

    let html = '';
    if(filtered.length === 0) {
        html = `<div class="text-center py-10 bg-[#0d1117] rounded border border-darkborder border-dashed text-gray-500">Tidak ada jadwal yang cocok.</div>`;
    } else {
        filtered.forEach(post => {
            // Deteksi Status
            let bInfo = { bg: 'bg-gray-800', text: 'UNKNOWN' };
            if (post.status === 'published') bInfo = { bg: 'bg-emerald-900/40 text-emerald-400 border border-emerald-800', text: 'PUBLISHED' };
            else if (post.status === 'failed') bInfo = { bg: 'bg-red-900/40 text-red-400 border border-red-800', text: 'FAILED' };
            else if (post.status === 'scheduled') bInfo = { bg: 'bg-blue-900/40 text-blue-400 border border-blue-800', text: 'SCHEDULED' };

            let pIcon = post.platform === 'facebook' ? '#icon-fb' : '#icon-threads';
            let errHtml = post.error_log ? `<div class="mt-3 p-2 text-xs bg-red-950 text-red-300 rounded font-mono">${post.error_log}</div>` : '';
            
            // Render 3 Tombol Aksi jika Gagal/Antre
            let btnHtml = '';
            if(post.status === 'scheduled' || post.status === 'failed') {
                btnHtml = `<div class="mt-4 flex gap-2">
                    <button class="timeline-action px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white text-xs rounded" data-act="force" data-id="${post.id}">🚀 Publish Sekarang</button>
                    <button class="px-3 py-1.5 bg-gray-800 hover:bg-gray-700 text-white text-xs rounded border border-gray-600" onclick="openEditModal(${post.id}, '${post.scheduled_at}')">✏️ Ubah Waktu</button>
                    <button class="timeline-action px-3 py-1.5 bg-red-900/30 hover:bg-red-900 text-red-400 hover:text-white text-xs rounded border border-red-900/50" data-act="delete" data-id="${post.id}">🗑️ Hapus</button>
                </div>`;
            }

            html += `
            <div class="bg-[#0d1117] border border-darkborder rounded-xl p-4 flex gap-4">
                <div class="text-right flex-shrink-0 w-16">
                    <div class="text-lg font-bold text-white">${post.scheduled_time_raw}</div>
                    <div class="text-[10px] text-gray-500">WIB</div>
                </div>
                <div class="border-l border-darkborder pl-4 w-full">
                    <div class="flex justify-between items-start mb-2">
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded ${bInfo.bg}">${bInfo.text}</span>
                        <svg class="w-5 h-5 text-gray-400 fill-current"><use href="${pIcon}"></use></svg>
                    </div>
                    <p class="text-sm text-gray-300 whitespace-pre-wrap">${post.content}</p>
                    ${errHtml}
                    ${btnHtml}
                </div>
            </div>`;
        });
    }
    $('#timeline-container').html(html);
}

// ==========================================
// UTILITIES (Tab, Modal, Toast)
// ==========================================
function switchTab(tabId) {
    $('#tab-post, #tab-history').addClass('hidden');
    $('#tabBtn-post, #tabBtn-history').removeClass('bg-blue-600 text-white').addClass('text-gray-400 bg-transparent');
    $('#tab-' + tabId).removeClass('hidden');
    $('#tabBtn-' + tabId).removeClass('text-gray-400 bg-transparent').addClass('bg-blue-600 text-white');
    if(tabId === 'history') loadAllHistory();
}

function clearDraft() { localStorage.removeItem('ezpost_draft'); $('#input_content').val(''); updatePreview(); }

function openEditModal(pid, fulltime) {
    $('#edit_post_id').val(pid);
    let fp = flatpickr("#edit_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true, defaultDate: fulltime });
    $('#editModal').removeClass('hidden').removeClass('opacity-0');
}
function closeEditModal() { $('#editModal').addClass('hidden opacity-0'); }

function showToast(msg, type='info') {
    let t = $('#toast'); let b = t.find('div'); let i = $('#toastIcon');
    $('#toastMsg').text(msg);
    b.removeClass('border-blue-500 border-red-500 border-emerald-500');
    if(type==='success') { b.addClass('border-emerald-500'); i.text('✅'); }
    else if(type==='error') { b.addClass('border-red-500'); i.text('❌'); }
    else { b.addClass('border-blue-500'); i.text('💡'); }
    t.removeClass('translate-x-full opacity-0');
    setTimeout(() => t.addClass('translate-x-full opacity-0'), 4000);
}

function logout() { document.cookie.split(";").forEach(c => document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/")); location.reload(); }
</script>
</body>
</html>