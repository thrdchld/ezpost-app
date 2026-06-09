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
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { darkbg: '#0f1115', darkcard: '#161b22', darkborder: '#30363d', primary: '#2563eb' } } }
        }
    </script>
    
    <!-- jQuery & Flatpickr -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }
        body { background-color: #0f1115; color: #c9d1d9; font-family: system-ui, sans-serif; }
        
        .sidebar-item.active { background-color: #1f2937; color: #fff; font-weight: bold; border-left: 3px solid #3b82f6; }
        .view-section { display: none; }
        .view-section.active { display: block; animation: fadeIn 0.3s ease-in-out; }
        
        /* Calendar UI */
        .cal-day { aspect-ratio: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; cursor: pointer; border-radius: 0.5rem; position: relative; transition: all 0.2s; }
        .cal-day:hover { background-color: #1f2937; }
        .cal-day.active { background-color: #2563eb; color: white; font-weight: bold; }
        .cal-day.today { border: 1px solid #2563eb; }
        .cal-dot { position: absolute; bottom: 4px; width: 6px; height: 6px; border-radius: 50%; background-color: #3b82f6; }
        
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="antialiased flex h-screen overflow-hidden">

<!-- ICONS DICTIONARY -->
<svg style="display: none;">
    <symbol id="icon-fb" viewBox="0 0 24 24"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></symbol>
    <symbol id="icon-threads" viewBox="0 0 24 24"><path fill="currentColor" d="M16.711 13.333c-0.198 1.401-1.037 3.237-2.934 3.237-1.498 0-2.483-1.077-2.483-2.616v-2.071c0-1.579 0.999-2.665 2.528-2.665 1.758 0 2.639 1.589 2.766 2.871h1.996c-0.218-2.222-1.905-4.571-4.762-4.571-2.645 0-4.582 1.872-4.582 4.417v2.019c0 2.502 1.87 4.364 4.509 4.364 2.876 0 4.607-2.235 4.887-4.985h-1.925z M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.666 12.06c0 3.398-2.158 5.76-5.59 5.76-3.415 0-5.632-2.316-5.632-5.717v-1.993c0-3.428 2.22-5.761 5.645-5.761 3.266 0 5.485 2.193 5.568 5.43h-2.029c-0.106-2.122-1.512-3.593-3.539-3.593-2.195 0-3.593 1.487-3.593 3.924v1.993c0 2.392 1.385 3.882 3.58 3.882 2.217 0 3.6-1.554 3.6-4.004h1.99z"/></symbol>
</svg>

<!-- TOAST & MODAL -->
<div id="toast" class="fixed top-5 right-5 z-50 transform transition-transform duration-300 translate-x-full opacity-0">
    <div class="bg-gray-800 text-white border-l-4 shadow-xl p-4 rounded flex items-center gap-3" id="toastBox">
        <span id="toastMsg" class="font-medium text-sm">Notifikasi</span>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black/80 z-50 hidden flex justify-center items-center">
    <div class="bg-darkcard border border-darkborder w-full max-w-sm rounded-xl p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4">Ubah Jadwal Tayang</h3>
        <form id="editScheduleForm" class="space-y-4">
            <input type="hidden" id="edit_post_id">
            <input type="text" id="edit_datetime" required class="w-full px-4 py-3 bg-[#0d1117] border border-gray-600 rounded text-white text-center font-bold outline-none cursor-pointer">
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded font-bold text-sm">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded font-bold text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. LOGIN SCREEN -->
<!-- ========================================== -->
<?php if (!is_logged_in()): ?>
<div class="w-full h-full flex justify-center items-center bg-darkbg">
    <div class="w-full max-w-sm bg-darkcard border border-darkborder rounded-xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black text-white tracking-widest">[EZ] POST</h1>
            <p class="text-xs text-gray-500 mt-2">Masuk ke Ruang Kerja</p>
        </div>
        <form id="loginForm" class="space-y-5">
            <input type="hidden" name="action" value="login">
            <div>
                <label class="text-xs font-bold text-gray-400 uppercase">Email</label>
                <input type="email" name="email" required class="w-full mt-1 px-4 py-3 bg-[#0d1117] border border-darkborder rounded-lg text-white outline-none focus:border-blue-500 transition-colors">
            </div>
            <div>
                <label class="text-xs font-bold text-gray-400 uppercase">Password</label>
                <input type="password" name="password" required class="w-full mt-1 px-4 py-3 bg-[#0d1117] border border-darkborder rounded-lg text-white outline-none focus:border-blue-500 transition-colors">
            </div>
            <button type="submit" id="btnLogin" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-lg shadow-lg transition-all mt-4">
                Masuk Sistem
            </button>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. DASHBOARD (Sesuai Screenshot) -->
<!-- ========================================== -->
<?php else: ?>
<!-- SIDEBAR -->
<aside class="w-64 bg-darkcard border-r border-darkborder flex flex-col z-10 flex-shrink-0">
    <div class="p-6 border-b border-darkborder flex items-center gap-3">
        <div class="w-8 h-8 bg-blue-600 rounded text-white font-bold flex justify-center items-center text-sm">[EZ]</div>
        <h1 class="text-xl font-bold text-white tracking-wide">EZPost</h1>
    </div>
    
    <nav class="flex-1 py-6 px-3 flex flex-col gap-2">
        <button onclick="switchTab('post')" id="nav-post" class="sidebar-item active w-full text-left px-4 py-3 rounded-lg text-sm text-gray-400 hover:text-white transition-all flex items-center gap-3">
            <span class="font-mono text-blue-400">[*]</span> Buat Post
        </button>
        <button onclick="switchTab('planner')" id="nav-planner" class="sidebar-item w-full text-left px-4 py-3 rounded-lg text-sm text-gray-400 hover:text-white transition-all flex items-center gap-3">
            <span class="font-mono text-emerald-400">[@]</span> Jadwal Planner
        </button>
        <button onclick="switchTab('gallery')" id="nav-gallery" class="sidebar-item w-full text-left px-4 py-3 rounded-lg text-sm text-gray-400 hover:text-white transition-all flex items-center gap-3">
            <span class="font-mono text-purple-400">[+]</span> Galeri Media
        </button>
        <button onclick="switchTab('status')" id="nav-status" class="sidebar-item w-full text-left px-4 py-3 rounded-lg text-sm text-gray-400 hover:text-white transition-all flex items-center gap-3">
            <span class="font-mono text-amber-400">[#]</span> Status Akun
        </button>
    </nav>

    <div class="p-4 border-t border-darkborder space-y-3">
        <div class="flex justify-between items-center px-2">
            <span class="text-xs text-gray-500">[C] Tema</span>
            <span class="text-[10px] font-bold bg-gray-800 text-white px-2 py-1 rounded">GELAP</span>
        </div>
        <button onclick="logout()" class="w-full text-left px-2 py-2 text-sm text-gray-400 hover:text-red-400 transition-all flex items-center gap-3">
            <span class="font-mono">[x]</span> Keluar
        </button>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="flex-1 overflow-y-auto bg-darkbg relative">
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <!-- A. VIEW: BUAT POST (Persis seperti layout screenshot) -->
    <div id="view-post" class="view-section active p-8 max-w-[1200px] mx-auto">
        <form id="postForm" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="flex justify-between items-end mb-6">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-bold text-white">Tulis Sesuatu</h2>
                    <button type="button" onclick="clearDraft()" class="text-xs border border-red-900/50 bg-red-900/20 text-red-400 px-3 py-1 rounded hover:bg-red-900/40 transition-colors font-bold tracking-wider">[X] RESET</button>
                </div>
                
                <!-- Target Platform Dropdown -->
                <div class="w-64">
                    <select name="platform" id="input_platform" class="w-full bg-[#0d1117] border border-darkborder text-gray-300 text-sm rounded-lg px-4 py-2.5 outline-none focus:border-blue-500 font-bold appearance-none cursor-pointer">
                        <option value="facebook">[FB] Facebook saja</option>
                        <option value="threads">[TH] Threads saja</option>
                        <option value="fb_threads_media">[+T] FB + TH (Media)</option>
                        <option value="fb_threads_nomedia" selected>[+T] FB + TH (TH Teks Saja)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Kiri: Textarea & Media -->
                <div class="lg:col-span-7 bg-darkcard border border-darkborder rounded-xl overflow-hidden flex flex-col shadow-sm">
                    <textarea name="content" id="input_content" rows="10" placeholder="Ketik status atau ide brilian Anda di sini..." required class="w-full p-6 bg-transparent text-gray-200 resize-none outline-none text-sm leading-relaxed"></textarea>
                    
                    <div class="border-t border-darkborder p-4 bg-[#0d1117]">
                        <label class="cursor-pointer text-sm font-bold text-gray-400 hover:text-white flex items-center gap-2 w-max">
                            <span class="text-blue-500 font-mono">[+]</span> Lampirkan Media
                            <input type="file" name="media[]" id="input_media" multiple accept="image/*,video/*" class="hidden">
                        </label>
                        <div id="media-count" class="text-xs text-emerald-400 mt-2 hidden"></div>
                    </div>
                </div>

                <!-- Kanan: Live Preview Tabbed -->
                <div class="lg:col-span-5 bg-darkcard border border-darkborder rounded-xl overflow-hidden flex flex-col shadow-sm h-[400px]">
                    <div class="flex border-b border-darkborder bg-[#0d1117]" id="previewTabs">
                        <button type="button" class="flex-1 py-3 text-sm font-bold border-b-2 border-blue-500 text-blue-500" onclick="switchPrev('fb')" id="tab-fb">[FB] Facebook</button>
                        <button type="button" class="flex-1 py-3 text-sm font-bold border-b-2 border-transparent text-gray-500" onclick="switchPrev('th')" id="tab-th">[TH] Threads</button>
                    </div>
                    
                    <div class="p-5 flex-1 overflow-y-auto bg-[#161b22]">
                        <!-- FB Preview -->
                        <div id="prev-fb" class="block">
                            <div class="bg-darkcard border border-darkborder rounded-lg p-4">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded bg-blue-600/20 border border-blue-500/30 flex justify-center items-center text-blue-500 font-bold text-xs">[FB]</div>
                                    <div><div class="font-bold text-sm text-gray-200">HALAMAN FB</div><div class="text-[10px] text-gray-500">Baru saja - Publik</div></div>
                                </div>
                                <div class="text-sm text-gray-300 whitespace-pre-wrap" id="fb-content-prev"></div>
                                <div id="fb-media-prev" class="hidden w-full h-24 bg-[#0d1117] rounded mt-3 border border-darkborder flex items-center justify-center text-xs text-gray-500">📸 Media</div>
                            </div>
                        </div>

                        <!-- TH Preview Autentik -->
                        <div id="prev-th" class="hidden">
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-gray-700 flex-shrink-0 z-10 border border-gray-600"></div>
                                    <div class="w-0.5 bg-gray-700 h-full mt-2"></div>
                                </div>
                                <div class="w-full pb-2">
                                    <div class="font-bold text-sm text-gray-200">@username</div>
                                    <div class="text-sm text-gray-300 whitespace-pre-wrap mt-1" id="th-content-prev"></div>
                                    <div id="th-media-prev" class="hidden w-full h-24 bg-[#0d1117] rounded mt-3 border border-darkborder flex items-center justify-center text-xs text-gray-500">📸 Media</div>
                                    <div class="flex gap-4 mt-3 text-gray-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 8 9 8z"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div class="mt-6 bg-darkcard border border-darkborder rounded-xl p-5 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <span class="text-sm font-bold text-gray-400">Waktu (Opsional):</span>
                    <div class="relative w-64">
                        <input type="text" id="input_datetime" name="scheduled_at" placeholder="MM/DD/YYYY   --:--" class="w-full bg-[#0d1117] border border-darkborder rounded-lg px-4 py-2.5 text-sm text-gray-300 outline-none focus:border-blue-500 cursor-pointer text-center font-mono">
                    </div>
                </div>
                
                <button type="submit" id="btnSubmitPost" class="w-full md:w-48 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg transition-colors flex justify-center items-center gap-2 shadow-lg">
                    <span>[>] Publish</span>
                </button>
            </div>
        </form>
    </div>

    <!-- B. VIEW: PLANNER JADWAL (Kalender & Timeline) -->
    <div id="view-planner" class="view-section p-8 max-w-[1200px] mx-auto">
        <h2 class="text-2xl font-bold text-white mb-6 border-b border-darkborder pb-4">Jadwal Planner</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- KALENDER KIRI -->
            <div class="lg:col-span-4">
                <div class="bg-darkcard border border-darkborder rounded-xl p-5 shadow-sm sticky top-6">
                    <div class="flex justify-between items-center mb-4">
                        <button onclick="changeMonth(-1)" class="p-2 hover:bg-gray-800 rounded text-gray-400 font-bold">&larr;</button>
                        <div class="flex gap-2">
                            <select id="cal-month" class="bg-[#0d1117] border border-darkborder text-gray-300 text-sm rounded px-2 py-1 outline-none" onchange="renderCalendar()">
                                <option value="0">Januari</option><option value="1">Februari</option><option value="2">Maret</option><option value="3">April</option><option value="4">Mei</option><option value="5">Juni</option><option value="6">Juli</option><option value="7">Agustus</option><option value="8">September</option><option value="9">Oktober</option><option value="10">November</option><option value="11">Desember</option>
                            </select>
                            <select id="cal-year" class="bg-[#0d1117] border border-darkborder text-gray-300 text-sm rounded px-2 py-1 outline-none" onchange="renderCalendar()"></select>
                        </div>
                        <button onclick="changeMonth(1)" class="p-2 hover:bg-gray-800 rounded text-gray-400 font-bold">&rarr;</button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center mb-2 text-xs font-bold text-gray-500">
                        <div>Mi</div><div>Se</div><div>Sl</div><div>Ra</div><div>Ka</div><div>Ju</div><div>Sa</div>
                    </div>
                    <div id="calendar-grid" class="grid grid-cols-7 gap-1 text-sm font-mono"></div>
                    
                    <div class="mt-4 pt-4 border-t border-darkborder flex justify-between items-center text-xs">
                        <button onclick="goToToday()" class="text-blue-400 hover:underline font-bold">Kembali Hari Ini</button>
                    </div>
                </div>
            </div>

            <!-- TIMELINE KANAN -->
            <div class="lg:col-span-8">
                <!-- Filter -->
                <div class="bg-darkcard border border-darkborder rounded-xl p-4 mb-6 flex gap-4 text-sm">
                    <select id="flt-status" class="bg-[#0d1117] border border-darkborder text-gray-300 rounded px-3 py-1.5 outline-none" onchange="renderTimeline()">
                        <option value="all">Semua Status</option><option value="scheduled">Antrean</option><option value="published">Sukses</option><option value="failed">Gagal</option>
                    </select>
                    <select id="flt-platform" class="bg-[#0d1117] border border-darkborder text-gray-300 rounded px-3 py-1.5 outline-none" onchange="renderTimeline()">
                        <option value="all">Semua Platform</option><option value="facebook">Facebook</option><option value="threads">Threads</option>
                    </select>
                </div>

                <h3 id="timeline-header" class="text-lg font-bold text-gray-300 mb-4 border-b border-darkborder pb-2">Jadwal: Hari Ini</h3>
                <div id="timeline-container" class="space-y-4"></div>
            </div>
        </div>
    </div>

    <!-- C. VIEW: GALERI MEDIA -->
    <div id="view-gallery" class="view-section p-8 max-w-[1200px] mx-auto">
        <h2 class="text-2xl font-bold text-white mb-6 border-b border-darkborder pb-4">Galeri Media</h2>
        <div class="text-center py-10 bg-darkcard border border-darkborder rounded-xl text-gray-500 text-sm">
            <span class="font-mono text-xl block mb-2">[+]</span>
            Galeri Media Server Tersedia (Under Maintenance Database)
        </div>
    </div>

    <!-- D. VIEW: STATUS AKUN -->
    <div id="view-status" class="view-section p-8 max-w-[800px] mx-auto">
        <h2 class="text-2xl font-bold text-white mb-6 border-b border-darkborder pb-4">Status Koneksi API</h2>
        <div class="bg-darkcard border border-darkborder rounded-xl p-6 shadow-sm space-y-4">
            <div class="flex justify-between items-center p-4 bg-[#0d1117] rounded-lg border border-darkborder">
                <span class="font-bold text-sm text-gray-300 flex items-center gap-3"><svg class="w-6 h-6 text-blue-500"><use href="#icon-fb"></use></svg> Facebook Fanpage</span>
                <span id="stat-fb" class="text-xs font-bold px-3 py-1.5 bg-gray-800 text-gray-400 rounded tracking-wider">MENGECEK...</span>
            </div>
            <div class="flex justify-between items-center p-4 bg-[#0d1117] rounded-lg border border-darkborder">
                <span class="font-bold text-sm text-gray-300 flex items-center gap-3"><svg class="w-6 h-6 text-white"><use href="#icon-threads"></use></svg> Threads Account</span>
                <span id="stat-th" class="text-xs font-bold px-3 py-1.5 bg-gray-800 text-gray-400 rounded tracking-wider">MENGECEK...</span>
            </div>
            <button onclick="checkStatus()" class="w-full py-3 bg-blue-900/20 text-blue-400 hover:bg-blue-900/40 rounded-lg text-sm font-bold mt-4 border border-blue-900/50 transition-colors">
                [↻] Refresh Status Koneksi
            </button>
        </div>
    </div>
</main>
<?php endif; ?>

<!-- ========================================== -->
<!-- CORE JAVASCRIPT & LOGIC (Robust & Error-Free) -->
<!-- ========================================== -->
<script>
// --- 1. GLOBAL STATE & SAFE PARSER ---
const csrfToken = $('#csrf_token').val() || '';
let globalPosts = [];
let currentCalDate = new Date();
let selectedDateStr = "";
let fpEditInstance = null; // Flatpickr modal edit

// Safe AJAX Caller untuk menangkal iklan InfinityFree
async function safeAjax(url, formData) {
    try {
        let response = await $.ajax({ url: url, type: 'POST', data: formData, processData: false, contentType: false });
        
        // Jika response langsung object JSON (jQuery sudah parse)
        if(typeof response === 'object') return response;
        
        // Jika response masih string (mungkin ketempelan HTML iklan)
        if(typeof response === 'string') {
            let clean = response.replace(/<!--.*?-->/gs, '').replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '').trim();
            return JSON.parse(clean);
        }
    } catch(e) {
        console.error("AJAX/Parser Error:", e);
        return { status: 'error', message: 'Koneksi ke server gagal. (Mungkin diblokir atau timeout)' };
    }
}

// --- 2. LOGIN LOGIC ---
$(document).ready(function() {
    if ($('#loginForm').length > 0) {
        $('#loginForm').submit(async function(e) {
            e.preventDefault();
            $('#btnLogin').prop('disabled', true).text('Memverifikasi...');
            
            let res = await safeAjax('api.php', new FormData(this));
            if(res.status === 'success') {
                window.location.reload();
            } else {
                showToast(res.message, 'error');
                $('#btnLogin').prop('disabled', false).text('Masuk Sistem');
            }
        });
    }

    // Jika masuk Dashboard
    if ($('#postForm').length > 0) {
        initDashboard();
    }
});

// --- 3. DASHBOARD INIT ---
function initDashboard() {
    // Navigasi Tabs
    switchTab('post'); // Default tab

    // Setup Flatpickr (Waktu Post)
    flatpickr("#input_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true, minDate: "today" });
    fpEditInstance = flatpickr("#edit_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true, minDate: "today" });

    // Draft Auto Saver
    if(localStorage.getItem('ezpost_draft')) $('#input_content').val(localStorage.getItem('ezpost_draft'));
    $('#input_content').on('input', function() { localStorage.setItem('ezpost_draft', $(this).val()); });

    // Live Preview Listeners
    $('#input_platform, #input_content, #input_media').on('input change', function() {
        if(this.id === 'input_media') {
            let len = this.files.length;
            if(len>0) $('#media-count').text(`[+] ${len} File Media Dipilih`).removeClass('hidden');
            else $('#media-count').addClass('hidden');
        }
        updatePreview();
    });
    updatePreview();

    // Planner Setup
    let ySel = $('#cal-year');
    let currYr = new Date().getFullYear();
    for(let i = currYr - 1; i <= currYr + 2; i++) ySel.append(`<option value="${i}">${i}</option>`);
    goToToday();

    // Submit Post Handler
    $('#postForm').submit(async function(e) {
        e.preventDefault();
        let btn = $('#btnSubmitPost'); let ori = btn.html();
        btn.prop('disabled', true).html('⏳ Uploading...');
        
        let res = await safeAjax('api.php', new FormData(this));
        showToast(res.message, res.status);
        
        if(res.status === 'success') {
            $('#postForm')[0].reset(); clearDraft();
            $('#input_platform').val('fb_threads_nomedia').trigger('change');
            $('#media-count').addClass('hidden');
            flatpickr("#input_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true, minDate: "today" }).clear();
            updatePreview(); loadAllHistory();
        }
        btn.prop('disabled', false).html(ori);
    });

    // Edit Schedule Submit
    $('#editScheduleForm').submit(async function(e) {
        e.preventDefault();
        let fd = new FormData();
        fd.append('action', 'edit_schedule');
        fd.append('csrf_token', csrfToken);
        fd.append('post_id', $('#edit_post_id').val());
        fd.append('new_datetime', $('#edit_datetime').val());
        
        let res = await safeAjax('api.php', fd);
        showToast(res.message, res.status);
        if(res.status === 'success') { closeEditModal(); loadAllHistory(); }
    });
}

// --- 4. UI FUNCTIONS (TABS & PREVIEW) ---
function switchTab(viewId) {
    $('.view-section').removeClass('active');
    $('.sidebar-item').removeClass('active');
    
    $(`#view-${viewId}`).addClass('active');
    $(`#nav-${viewId}`).addClass('active');
    
    if(viewId === 'planner') loadAllHistory();
    if(viewId === 'status') checkStatus();
}

function updatePreview() {
    const plat = $('#input_platform').val();
    const content = $('#input_content').val() || '<span class="text-gray-500 italic">...</span>';
    const hasMedia = $('#input_media')[0].files.length > 0;
    
    let isFb = (plat === 'facebook' || plat.startsWith('fb_threads'));
    let isTh = (plat === 'threads' || plat.startsWith('fb_threads'));
    
    // Manage Tabs Appearance
    $('#tab-fb').toggle(isFb); $('#tab-th').toggle(isTh);
    if(isFb) switchPrev('fb'); else switchPrev('th');

    // Fill Content
    $('#fb-content-prev').html(content);
    $('#th-content-prev').html(content);
    
    // Manage Media Box
    $('#fb-media-prev').toggle(hasMedia);
    $('#th-media-prev').toggle(hasMedia && plat !== 'fb_threads_nomedia');
}

function switchPrev(target) {
    $('#prev-fb, #prev-th').hide();
    $(`#prev-${target}`).show();
    $('#tab-fb, #tab-th').removeClass('border-blue-500 text-blue-500').addClass('border-transparent text-gray-500');
    $(`#tab-${target}`).addClass('border-blue-500 text-blue-500').removeClass('border-transparent text-gray-500');
}

function clearDraft() { localStorage.removeItem('ezpost_draft'); $('#input_content').val(''); updatePreview(); }

// --- 5. PLANNER (KALENDER & TIMELINE) ---
async function loadAllHistory() {
    let fd = new FormData(); fd.append('action', 'get_history'); fd.append('csrf_token', csrfToken);
    let res = await safeAjax('api.php', fd);
    if(res.status === 'success') { allPosts = res.data; renderCalendar(); renderTimeline(); }
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
        let isActive = (loopDate === selectedDateStr) ? 'active' : 'text-gray-400';
        let hasPost = allPosts.some(p => p.scheduled_date_raw === loopDate);
        let dotHtml = hasPost ? `<div class="cal-dot"></div>` : '';
        gridHtml += `<div class="cal-day ${isToday} ${isActive}" onclick="selectDate('${loopDate}')">${d}${dotHtml}</div>`;
    }
    $('#calendar-grid').html(gridHtml);
}

function renderTimeline() {
    let fStat = $('#flt-status').val(); let fPlat = $('#flt-platform').val();
    let dObj = new Date(selectedDateStr);
    $('#timeline-header').html(`[ ${dObj.toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})} ]`);

    let filtered = allPosts.filter(p => {
        return (p.scheduled_date_raw === selectedDateStr) && 
               (fStat === 'all' || p.status === fStat) && 
               (fPlat === 'all' || p.platform === fPlat);
    });

    let html = '';
    if(filtered.length === 0) {
        html = `<div class="text-center py-10 bg-[#0d1117] rounded-xl border border-darkborder text-gray-500 font-mono text-sm">Tidak ada jadwal ditemukan.</div>`;
    } else {
        filtered.forEach(post => {
            let bInfo = { bg: 'bg-gray-800', text: 'UNKNOWN' };
            if (post.status === 'published') bInfo = { bg: 'bg-emerald-900/30 text-emerald-400 border border-emerald-800', text: 'PUBLISHED' };
            else if (post.status === 'failed') bInfo = { bg: 'bg-red-900/30 text-red-400 border border-red-800', text: 'FAILED' };
            else if (post.status === 'scheduled') bInfo = { bg: 'bg-blue-900/30 text-blue-400 border border-blue-800', text: 'SCHEDULED' };

            let pName = post.platform.toUpperCase();
            let errHtml = post.error_log ? `<div class="mt-3 p-2 text-xs bg-red-950/50 text-red-300 rounded font-mono border border-red-900/50">${post.error_log}</div>` : '';
            
            let btnHtml = '';
            if(post.status === 'scheduled' || post.status === 'failed') {
                btnHtml = `<div class="mt-4 flex flex-wrap gap-2 pt-3 border-t border-darkborder">
                    <button class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded" onclick="actionPost('force', ${post.id})">[>] Publish Sekarang</button>
                    <button class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white text-xs font-bold rounded" onclick="openEditModal(${post.id}, '${post.scheduled_at}')">[✏️] Ubah Waktu</button>
                    <button class="px-3 py-1.5 bg-red-900/30 hover:bg-red-900/50 text-red-400 text-xs font-bold rounded border border-red-900/50" onclick="actionPost('delete', ${post.id})">[🗑️] Hapus</button>
                </div>`;
            }

            html += `
            <div class="bg-[#0d1117] border border-darkborder rounded-xl p-5 flex gap-4 hover:border-gray-700 transition-colors">
                <div class="text-right flex-shrink-0 w-12">
                    <div class="text-sm font-bold text-white">${post.scheduled_time_raw}</div>
                </div>
                <div class="border-l border-darkborder pl-4 w-full">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded tracking-wide ${bInfo.bg}">${bInfo.text}</span>
                        <span class="text-xs text-gray-500 font-bold font-mono">[${pName}]</span>
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

// --- 6. ACTION & UTILS ---
async function actionPost(act, pid) {
    if(act === 'delete' && !confirm("Hapus postingan ini selamanya?")) return;
    
    let fd = new FormData();
    fd.append('action', act === 'force' ? 'force_publish' : 'delete_post');
    fd.append('post_id', pid); fd.append('csrf_token', csrfToken);
    
    let res = await safeAjax('api.php', fd);
    showToast(res.message, res.status);
    if(res.status === 'success') {
        if(act === 'force') $.get('cron.php?secret=EZPost1995').always(() => loadAllHistory());
        else loadAllHistory();
    }
}

function openEditModal(pid, dTimeStr) {
    $('#edit_post_id').val(pid);
    fpEditInstance.setDate(dTimeStr);
    $('#editModal').removeClass('hidden');
}
function closeEditModal() { $('#editModal').addClass('hidden'); }

async function checkStatus() {
    $('#stat-fb, #stat-th').removeClass().addClass("text-xs font-bold px-3 py-1.5 bg-gray-800 text-gray-400 rounded tracking-wider").text("MENGECEK...");
    let fd = new FormData(); fd.append('action', 'check_status'); fd.append('csrf_token', csrfToken);
    let res = await safeAjax('api.php', fd);
    if(res.status === 'success') {
        let clOk = "text-xs font-bold px-3 py-1.5 bg-emerald-900/30 text-emerald-400 border border-emerald-800 rounded tracking-wider";
        let clErr = "text-xs font-bold px-3 py-1.5 bg-red-900/30 text-red-400 border border-red-800 rounded tracking-wider";
        $('#stat-fb').removeClass().addClass(res.facebook ? clOk : clErr).text(res.facebook ? "✔ TERHUBUNG" : "✖ TERPUTUS");
        $('#stat-th').removeClass().addClass(res.threads ? clOk : clErr).text(res.threads ? "✔ TERHUBUNG" : "✖ TERPUTUS");
    }
}

function showToast(msg, type='info') {
    let t = $('#toast'); let b = $('#toastBox');
    $('#toastMsg').text(msg);
    b.removeClass('border-blue-500 border-red-500 border-emerald-500');
    if(type==='success') b.addClass('border-emerald-500');
    else if(type==='error') b.addClass('border-red-500');
    else b.addClass('border-blue-500');
    t.removeClass('translate-x-full opacity-0');
    setTimeout(() => t.addClass('translate-x-full opacity-0'), 4000);
}

function logout() { document.cookie.split(";").forEach(c => document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/")); location.reload(); }
</script>
</body>
</html>