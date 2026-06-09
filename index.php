<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZPost Enterprise</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: { extend: { colors: { darkbg: '#0d1117', darkcard: '#161b22', darkborder: '#30363d', primary: '#2563eb' } } }
        }
    </script>
    <style>
        /* Minimalist Scrollbar & Native Input Adjustments */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }
        input[type="date"], input[type="time"] { color-scheme: dark; }
        body { background-color: #0d1117; color: #c9d1d9; font-family: system-ui, -apple-system, sans-serif; }
        
        /* Hide sections by default */
        .app-view { display: none; }
        .app-view.active { display: block; }
        .nav-item.active { background-color: #1f2937; color: #fff; font-weight: bold; border-left: 3px solid #3b82f6; }
        .tab-btn.active { border-bottom: 2px solid #3b82f6; color: #3b82f6; }
        .preview-tab { display: none; }
        .preview-tab.active { display: block; }
    </style>
</head>
<body class="antialiased flex h-screen overflow-hidden">

<!-- ========================================== -->
<!-- ICONS LIBRARY (Aman dari kegagalan load) -->
<!-- ========================================== -->
<svg style="display: none;">
    <symbol id="icon-fb" viewBox="0 0 24 24"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></symbol>
    <symbol id="icon-threads" viewBox="0 0 24 24"><path fill="currentColor" d="M16.711 13.333c-0.198 1.401-1.037 3.237-2.934 3.237-1.498 0-2.483-1.077-2.483-2.616v-2.071c0-1.579 0.999-2.665 2.528-2.665 1.758 0 2.639 1.589 2.766 2.871h1.996c-0.218-2.222-1.905-4.571-4.762-4.571-2.645 0-4.582 1.872-4.582 4.417v2.019c0 2.502 1.87 4.364 4.509 4.364 2.876 0 4.607-2.235 4.887-4.985h-1.925z M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.666 12.06c0 3.398-2.158 5.76-5.59 5.76-3.415 0-5.632-2.316-5.632-5.717v-1.993c0-3.428 2.22-5.761 5.645-5.761 3.266 0 5.485 2.193 5.568 5.43h-2.029c-0.106-2.122-1.512-3.593-3.539-3.593-2.195 0-3.593 1.487-3.593 3.924v1.993c0 2.392 1.385 3.882 3.58 3.882 2.217 0 3.6-1.554 3.6-4.004h1.99z"/></symbol>
    <symbol id="icon-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></symbol>
    <symbol id="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></symbol>
    <symbol id="icon-send" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></symbol>
</svg>

<!-- ========================================== -->
<!-- MODALS & TOASTS -->
<!-- ========================================== -->
<div id="toast" class="fixed top-5 right-5 z-50 transform transition-transform duration-200 translate-x-full opacity-0 pointer-events-none">
    <div class="bg-gray-800 text-white border-l-4 shadow-lg p-4 rounded flex items-center gap-3" id="toastBox">
        <span id="toastMsg" class="font-medium text-sm">Pesan Notifikasi</span>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black/80 z-50 hidden flex justify-center items-center">
    <div class="bg-darkcard border border-darkborder w-full max-w-sm rounded-xl p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-4">Ubah Waktu Tayang</h3>
        <form id="editScheduleForm" class="space-y-4">
            <input type="hidden" id="edit_post_id">
            <div class="grid grid-cols-2 gap-3">
                <input type="date" id="edit_date" required class="w-full px-3 py-2 bg-[#0d1117] border border-darkborder rounded text-white text-sm outline-none">
                <input type="time" id="edit_time" required class="w-full px-3 py-2 bg-[#0d1117] border border-darkborder rounded text-white text-sm outline-none">
            </div>
            <div class="pt-2 flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded text-sm font-bold">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded text-sm font-bold">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- LOGIN SCREEN -->
<!-- ========================================== -->
<?php if (!is_logged_in()): ?>
<div class="w-full h-full flex justify-center items-center bg-darkbg">
    <div class="w-full max-w-sm bg-darkcard border border-darkborder rounded-xl p-8 shadow-2xl">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-white tracking-wide">🚀 EZPOST</h1>
            <p class="text-xs text-gray-500 mt-1">Enterprise Publish System</p>
        </div>
        <form id="loginForm" class="space-y-4">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <div><label class="text-xs font-bold text-gray-400 uppercase">Email</label><input type="email" name="email" required class="w-full mt-1 px-4 py-2.5 bg-[#0d1117] border border-darkborder rounded-lg text-white outline-none focus:border-blue-500"></div>
            <div><label class="text-xs font-bold text-gray-400 uppercase">Password</label><input type="password" name="password" required class="w-full mt-1 px-4 py-2.5 bg-[#0d1117] border border-darkborder rounded-lg text-white outline-none focus:border-blue-500"></div>
            <button type="submit" id="btnLogin" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 rounded-lg mt-2">Masuk ke Sistem</button>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MAIN DASHBOARD -->
<!-- ========================================== -->
<?php else: ?>
<!-- SIDEBAR NAVIGATION -->
<aside class="w-64 bg-darkcard border-r border-darkborder flex flex-col flex-shrink-0 z-10 hidden md:flex">
    <div class="p-6 border-b border-darkborder">
        <h1 class="text-xl font-black text-white tracking-widest">EZPOST</h1>
        <p class="text-xs text-gray-500">Workspace</p>
    </div>
    <nav class="flex-1 py-4 flex flex-col gap-1 px-3">
        <a href="#post" class="nav-item px-4 py-2.5 rounded-lg text-sm text-gray-400 hover:text-white flex items-center gap-3"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Buat Postingan</a>
        <a href="#planner" class="nav-item px-4 py-2.5 rounded-lg text-sm text-gray-400 hover:text-white flex items-center gap-3"><span class="w-2 h-2 rounded-full bg-blue-500"></span> Jadwal & Riwayat</a>
        <a href="#gallery" class="nav-item px-4 py-2.5 rounded-lg text-sm text-gray-400 hover:text-white flex items-center gap-3"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Galeri Media</a>
        <a href="#status" class="nav-item px-4 py-2.5 rounded-lg text-sm text-gray-400 hover:text-white flex items-center gap-3"><span class="w-2 h-2 rounded-full bg-amber-500"></span> Status API</a>
    </nav>
    <div class="p-4 border-t border-darkborder">
        <button onclick="logout()" class="w-full py-2 bg-red-900/30 text-red-400 hover:bg-red-900/60 rounded-lg text-sm font-bold border border-red-900/50">Keluar</button>
    </div>
</aside>

<!-- MAIN CONTENT AREA -->
<main class="flex-1 bg-darkbg overflow-y-auto h-full relative">
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <!-- VIEW: BUAT POSTINGAN -->
    <div id="view-post" class="app-view p-6 lg:p-8 max-w-6xl mx-auto active">
        <div class="flex justify-between items-center mb-6 border-b border-darkborder pb-4">
            <h2 class="text-2xl font-bold text-white">Tulis Sesuatu</h2>
            <button onclick="clearDraft()" class="text-xs bg-red-900/30 text-red-400 px-3 py-1.5 rounded hover:bg-red-900/60 font-bold border border-red-900/50">🗑️ Hapus Draf</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- Form Input -->
            <div class="lg:col-span-7 space-y-6">
                <form id="postForm" class="space-y-5 bg-darkcard border border-darkborder p-5 rounded-xl">
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase block mb-2">Target Platform</label>
                        <select name="platform" id="input_platform" class="w-full px-3 py-2.5 bg-[#0d1117] border border-darkborder rounded-lg text-white text-sm outline-none">
                            <option value="facebook">Facebook saja</option>
                            <option value="threads">Threads saja</option>
                            <option value="fb_threads_media">Facebook + Threads</option>
                            <option value="fb_threads_nomedia" selected>Facebook + Threads (TH Teks Saja) - Default</option>
                        </select>
                    </div>
                    <div>
                        <textarea name="content" id="input_content" rows="6" placeholder="Ketik status atau ide Anda..." required class="w-full px-4 py-3 bg-[#0d1117] border border-darkborder rounded-lg text-white resize-none outline-none focus:border-blue-500 text-sm leading-relaxed"></textarea>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-400 uppercase block mb-2">Media Lampiran</label>
                        <input type="file" name="media[]" id="input_media" multiple accept="image/*,video/*" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-xs file:font-bold file:bg-gray-800 file:text-white hover:file:bg-gray-700 bg-[#0d1117] border border-darkborder rounded-lg cursor-pointer">
                    </div>
                    
                    <div class="pt-4 border-t border-darkborder">
                        <label class="text-xs font-bold text-gray-400 uppercase block mb-2">Waktu Penjadwalan (Opsional)</label>
                        <div class="flex gap-3">
                            <input type="date" id="input_date" class="w-full px-3 py-2.5 bg-[#0d1117] border border-darkborder rounded-lg text-white text-sm outline-none">
                            <input type="time" id="input_time" class="w-full px-3 py-2.5 bg-[#0d1117] border border-darkborder rounded-lg text-white text-sm outline-none">
                        </div>
                        <p class="text-[10px] text-gray-500 mt-2">* Kosongkan tanggal dan waktu untuk dipublish detik ini juga.</p>
                    </div>
                </form>
            </div>

            <!-- Preview & Submit -->
            <div class="lg:col-span-5 space-y-6">
                <!-- Smart Button -->
                <div class="bg-darkcard border border-darkborder rounded-xl p-4">
                    <button type="submit" form="postForm" id="btnSubmitPost" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 rounded-lg shadow-lg flex justify-center items-center gap-2">
                        <svg class="w-5 h-5"><use href="#icon-send"></use></svg> <span>Publish Sekarang</span>
                    </button>
                </div>

                <!-- Live Preview -->
                <div class="bg-darkcard border border-darkborder rounded-xl overflow-hidden flex flex-col h-[450px]">
                    <div class="flex border-b border-darkborder bg-[#0d1117]" id="previewTabs">
                        <button class="tab-btn flex-1 py-2.5 text-sm font-bold text-gray-500 border-b-2 border-transparent" onclick="switchPreview('fb')" id="tab-fb">Facebook</button>
                        <button class="tab-btn flex-1 py-2.5 text-sm font-bold text-gray-500 border-b-2 border-transparent" onclick="switchPreview('th')" id="tab-th">Threads</button>
                    </div>
                    
                    <div class="flex-1 p-4 overflow-y-auto bg-[#161b22]">
                        <!-- FB Preview -->
                        <div id="prev-fb" class="preview-tab">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 rounded-full bg-blue-600 flex-shrink-0"></div>
                                <div><div class="font-bold text-sm text-gray-200 leading-tight">Halaman Facebook</div><div class="text-[10px] text-gray-500">Baru saja · Publik</div></div>
                            </div>
                            <div class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed" id="fb-content-prev"></div>
                            <div id="fb-media-prev" class="hidden w-full h-32 bg-[#0d1117] rounded-lg mt-3 border border-darkborder flex items-center justify-center text-xs text-gray-500">📸 Media Terlampir</div>
                        </div>

                        <!-- Threads Preview -->
                        <div id="prev-th" class="preview-tab">
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-gray-600 flex-shrink-0 z-10"></div>
                                    <div class="w-0.5 bg-gray-800 h-full mt-2"></div>
                                </div>
                                <div class="w-full pb-4">
                                    <div class="font-bold text-sm text-gray-200 leading-tight">@username</div>
                                    <div class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed mt-1" id="th-content-prev"></div>
                                    <div id="th-media-prev" class="hidden w-full h-32 bg-[#0d1117] rounded-lg mt-3 border border-darkborder flex items-center justify-center text-xs text-gray-500">📸 Media Terlampir</div>
                                    <div class="flex gap-4 mt-3 text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 8 9 8z"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW: PLANNER & RIWAYAT -->
    <div id="view-planner" class="app-view p-6 lg:p-8 max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-6 border-b border-darkborder pb-4">Jadwal & Riwayat</h2>
        
        <div id="planner-container" class="space-y-8">
            <div class="text-center py-10 text-gray-500 text-sm">Memuat data...</div>
        </div>
    </div>

    <!-- VIEW: GALERI MEDIA -->
    <div id="view-gallery" class="app-view p-6 lg:p-8 max-w-6xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-6 border-b border-darkborder pb-4">Galeri Media Server</h2>
        <div class="text-center py-10 text-gray-500 text-sm">Fitur Galeri sedang diinisialisasi...</div>
    </div>

    <!-- VIEW: STATUS API -->
    <div id="view-status" class="app-view p-6 lg:p-8 max-w-3xl mx-auto">
        <h2 class="text-2xl font-bold text-white mb-6 border-b border-darkborder pb-4">Status Koneksi Platform</h2>
        <div class="bg-darkcard border border-darkborder rounded-xl p-5 shadow-sm space-y-4">
            <div class="flex justify-between items-center p-3 bg-[#0d1117] rounded-lg border border-darkborder">
                <span class="font-bold text-sm text-gray-300 flex items-center gap-2"><svg class="w-5 h-5 text-blue-500"><use href="#icon-fb"></use></svg> Facebook Pages</span>
                <span id="stat-fb" class="text-xs font-bold px-2 py-1 bg-gray-800 text-gray-400 rounded">Mengecek...</span>
            </div>
            <div class="flex justify-between items-center p-3 bg-[#0d1117] rounded-lg border border-darkborder">
                <span class="font-bold text-sm text-gray-300 flex items-center gap-2"><svg class="w-5 h-5 text-white"><use href="#icon-threads"></use></svg> Threads Akun</span>
                <span id="stat-th" class="text-xs font-bold px-2 py-1 bg-gray-800 text-gray-400 rounded">Mengecek...</span>
            </div>
            <button onclick="checkStatus()" class="w-full py-2 bg-blue-900/20 text-blue-400 hover:bg-blue-900/40 rounded-lg text-sm font-bold mt-4 border border-blue-900/50">Refresh Status</button>
        </div>
    </div>

</main>
<?php endif; ?>

<!-- ========================================== -->
<!-- CORE JAVASCRIPT LOGIC -->
<!-- ========================================== -->
<script>
// 1. GLOBAL VARIABLES & STATE
const csrfToken = document.getElementById('csrf_token') ? document.getElementById('csrf_token').value : '';
let globalPosts = [];

// 2. SAFE API CALLER (Bypass InfinityFree Ads Injection)
async function apiCall(formData) {
    try {
        let res = await fetch('api.php', { method: 'POST', body: formData });
        let text = await res.text();
        
        // Membersihkan script ikan / tracking tambahan
        text = text.replace(/<!--.*?-->/gs, '');
        text = text.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
        
        return JSON.parse(text.trim());
    } catch (e) {
        console.error("API Error:", e);
        return { status: 'error', message: 'Gagal memproses respon server. Koneksi bermasalah.' };
    }
}

// 3. UI UTILITIES
function showToast(msg, type='info') {
    const t = document.getElementById('toast');
    const b = document.getElementById('toastBox');
    document.getElementById('toastMsg').innerText = msg;
    
    b.className = 'text-white border-l-4 shadow-lg p-3 rounded flex items-center gap-3 bg-gray-800';
    if(type === 'success') b.classList.add('border-emerald-500');
    else if(type === 'error') b.classList.add('border-red-500');
    else b.classList.add('border-blue-500');
    
    t.classList.remove('translate-x-full', 'opacity-0');
    setTimeout(() => t.classList.add('translate-x-full', 'opacity-0'), 3500);
}

// 4. ROUTER SYSTEM (Mulus, No Reload)
function handleRoute() {
    if(!document.getElementById('view-post')) return; // Exit if not logged in
    
    let hash = window.location.hash || '#post';
    
    // Hide all, remove active navs
    document.querySelectorAll('.app-view').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    
    // Show targeted
    let targetView = document.getElementById('view-' + hash.replace('#', ''));
    if(targetView) targetView.classList.add('active');
    
    let targetNav = document.querySelector(`.nav-item[href="${hash}"]`);
    if(targetNav) targetNav.classList.add('active');

    // Trigger actions based on route
    if(hash === '#planner') loadPlanner();
    if(hash === '#status') checkStatus();
}
window.addEventListener('hashchange', handleRoute);

// 5. INIT DASHBOARD (Bila sudah login)
document.addEventListener('DOMContentLoaded', () => {
    // A. Form Login Handler
    const formLogin = document.getElementById('loginForm');
    if(formLogin) {
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            let btn = document.getElementById('btnLogin');
            btn.disabled = true; btn.innerText = "Memverifikasi...";
            
            let fd = new FormData(formLogin);
            let res = await apiCall(fd);
            
            if(res.status === 'success') window.location.reload();
            else { showToast(res.message, 'error'); btn.disabled = false; btn.innerText = "Masuk ke Sistem"; }
        });
        return; // Hentikan eksekusi script lain jika masih di layar login
    }

    // B. Setup Aplikasi Utama
    handleRoute(); // Init route
    
    // Draft loader
    let draft = localStorage.getItem('ez_draft');
    if(draft) document.getElementById('input_content').value = draft;

    // Live Preview Listeners
    ['input_platform', 'input_content', 'input_media'].forEach(id => {
        let el = document.getElementById(id);
        if(el) el.addEventListener('input', updateLivePreview);
    });
    updateLivePreview();

    // Draft Saver Listener
    document.getElementById('input_content').addEventListener('input', function() {
        localStorage.setItem('ez_draft', this.value);
    });

    // Form Post Submit Handler
    document.getElementById('postForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        let btn = document.getElementById('btnSubmitPost');
        let oriHtml = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = "⏳ Memproses Upload...";
        
        let fd = new FormData(e.target);
        
        // Gabungkan tanggal dan jam manual
        let d = document.getElementById('input_date').value;
        let t = document.getElementById('input_time').value;
        if(d && t) fd.append('scheduled_at', `${d} ${t}:00`);
        
        let res = await apiCall(fd);
        showToast(res.message, res.status);
        
        if(res.status === 'success') {
            e.target.reset(); clearDraft(); updateLivePreview();
        }
        btn.disabled = false; btn.innerHTML = oriHtml;
    });

    // Edit Modal Submit Handler
    document.getElementById('editScheduleForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        let pid = document.getElementById('edit_post_id').value;
        let d = document.getElementById('edit_date').value;
        let t = document.getElementById('edit_time').value;
        
        let fd = new FormData();
        fd.append('action', 'edit_schedule');
        fd.append('csrf_token', csrfToken);
        fd.append('post_id', pid);
        fd.append('new_datetime', `${d} ${t}:00`);

        let res = await apiCall(fd);
        showToast(res.message, res.status);
        if(res.status === 'success') { closeEditModal(); loadPlanner(); }
    });
});

// 6. DASHBOARD FUNCTIONS
function updateLivePreview() {
    let plat = document.getElementById('input_platform').value;
    let content = document.getElementById('input_content').value || '<span class="text-gray-500 italic">Mulai mengetik...</span>';
    let hasMedia = document.getElementById('input_media').files.length > 0;

    let isFb = (plat === 'facebook' || plat.startsWith('fb_threads'));
    let isTh = (plat === 'threads' || plat.startsWith('fb_threads'));

    // Reset Tabs
    document.getElementById('tab-fb').style.display = isFb ? 'block' : 'none';
    document.getElementById('tab-th').style.display = isTh ? 'block' : 'none';
    
    // Auto switch tab if current is hidden
    if(!isFb) switchPreview('th');
    else if(!isTh) switchPreview('fb');
    else switchPreview('fb'); // Default if both

    // Content
    document.getElementById('fb-content-prev').innerHTML = content;
    document.getElementById('th-content-prev').innerHTML = content;

    // Media
    document.getElementById('fb-media-prev').style.display = hasMedia ? 'flex' : 'none';
    document.getElementById('th-media-prev').style.display = (hasMedia && plat !== 'fb_threads_nomedia') ? 'flex' : 'none';
}

function switchPreview(target) {
    document.querySelectorAll('.preview-tab').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    document.getElementById(`prev-${target}`).classList.add('active');
    document.getElementById(`tab-${target}`).classList.add('active');
}

function clearDraft() {
    localStorage.removeItem('ez_draft');
    document.getElementById('input_content').value = '';
    updateLivePreview();
}

async function loadPlanner() {
    let container = document.getElementById('planner-container');
    container.innerHTML = '<div class="text-center py-10 text-gray-500">Memuat riwayat...</div>';
    
    let fd = new FormData();
    fd.append('action', 'get_history');
    fd.append('csrf_token', csrfToken);
    
    let res = await apiCall(fd);
    if(res.status === 'success') {
        globalPosts = res.data;
        renderPlannerUI();
    }
}

function renderPlannerUI() {
    let container = document.getElementById('planner-container');
    if(globalPosts.length === 0) {
        container.innerHTML = '<div class="text-center py-10 bg-darkcard border border-darkborder rounded-xl text-gray-500 text-sm">Belum ada riwayat postingan.</div>';
        return;
    }

    // Grouping by Date
    const groups = {};
    globalPosts.forEach(p => {
        let dateLabel = getSmartDateLabel(p.scheduled_date_raw);
        if(!groups[dateLabel]) groups[dateLabel] = [];
        groups[dateLabel].push(p);
    });

    let html = '';
    for (const [dateLabel, posts] of Object.entries(groups)) {
        html += `<div class="mb-8"><h3 class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3 border-b border-darkborder pb-2">${dateLabel}</h3><div class="space-y-3">`;
        
        posts.forEach(post => {
            let badgeInfo = getBadgeStyle(post.status);
            let iconId = post.platform === 'facebook' ? '#icon-fb' : '#icon-threads';
            let errHtml = post.error_log ? `<div class="mt-2 text-[10px] text-red-400 bg-red-900/20 p-2 rounded border border-red-900/30 font-mono">${post.error_log}</div>` : '';
            
            let btnHtml = '';
            if(post.status === 'scheduled' || post.status === 'failed') {
                btnHtml = `<div class="mt-3 flex gap-2 pt-3 border-t border-darkborder">
                    <button onclick="actionPost('force', ${post.id})" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-[11px] font-bold rounded flex items-center gap-1"><svg class="w-3 h-3"><use href="#icon-send"></use></svg> Kirim Paksa</button>
                    <button onclick="openEditModal(${post.id}, '${post.scheduled_date_raw}', '${post.scheduled_time_raw}')" class="px-3 py-1.5 bg-gray-700 hover:bg-gray-600 text-white text-[11px] font-bold rounded flex items-center gap-1"><svg class="w-3 h-3"><use href="#icon-edit"></use></svg> Ubah</button>
                    <button onclick="actionPost('delete', ${post.id})" class="px-3 py-1.5 bg-red-900/30 hover:bg-red-900/50 text-red-400 text-[11px] font-bold rounded flex items-center gap-1 border border-red-900/50"><svg class="w-3 h-3"><use href="#icon-trash"></use></svg> Hapus</button>
                </div>`;
            }

            html += `<div class="bg-darkcard border border-darkborder rounded-xl p-4 flex gap-4 hover:border-gray-600 transition-colors">
                <div class="text-right flex-shrink-0 w-12 pt-1">
                    <div class="text-sm font-bold text-white">${post.scheduled_time_raw}</div>
                </div>
                <div class="border-l border-darkborder pl-4 w-full">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-0.5 text-[9px] font-bold rounded ${badgeInfo.css}">${badgeInfo.text}</span>
                        <svg class="w-4 h-4 text-gray-400"><use href="${iconId}"></use></svg>
                    </div>
                    <p class="text-sm text-gray-300 whitespace-pre-wrap">${post.content}</p>
                    ${errHtml}
                    ${btnHtml}
                </div>
            </div>`;
        });
        html += `</div></div>`;
    }
    container.innerHTML = html;
}

async function actionPost(act, id) {
    if(act === 'delete' && !confirm("Yakin ingin menghapus permanen?")) return;
    
    let fd = new FormData();
    fd.append('action', act === 'force' ? 'force_publish' : 'delete_post');
    fd.append('post_id', id);
    fd.append('csrf_token', csrfToken);
    
    let res = await apiCall(fd);
    showToast(res.message, res.status);
    if(res.status === 'success') {
        if(act === 'force') fetch('cron.php?secret=EZPost1995').then(() => loadPlanner());
        else loadPlanner();
    }
}

// Edit Modal Logic
function openEditModal(id, d, t) {
    document.getElementById('edit_post_id').value = id;
    document.getElementById('edit_date').value = d;
    document.getElementById('edit_time').value = t;
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }

// Status Checker Logic
async function checkStatus() {
    document.getElementById('stat-fb').className = "text-xs font-bold px-2 py-1 bg-gray-800 text-gray-400 rounded";
    document.getElementById('stat-th').className = "text-xs font-bold px-2 py-1 bg-gray-800 text-gray-400 rounded";
    document.getElementById('stat-fb').innerText = "Mengecek...";
    document.getElementById('stat-th').innerText = "Mengecek...";
    
    let fd = new FormData(); fd.append('action', 'check_status'); fd.append('csrf_token', csrfToken);
    let res = await apiCall(fd);
    
    if(res.status === 'success') {
        let clOk = "text-xs font-bold px-2 py-1 bg-emerald-900/30 text-emerald-400 border border-emerald-800 rounded";
        let clErr = "text-xs font-bold px-2 py-1 bg-red-900/30 text-red-400 border border-red-800 rounded";
        document.getElementById('stat-fb').className = res.facebook ? clOk : clErr;
        document.getElementById('stat-fb').innerText = res.facebook ? "✔ Terhubung" : "✖ Terputus";
        document.getElementById('stat-th').className = res.threads ? clOk : clErr;
        document.getElementById('stat-th').innerText = res.threads ? "✔ Terhubung" : "✖ Terputus";
    }
}

// Helper Functions
function getSmartDateLabel(dateStr) {
    let td = new Date(); let tgt = new Date(dateStr);
    td.setHours(0,0,0,0); tgt.setHours(0,0,0,0);
    let diff = Math.round((tgt - td)/(1000*60*60*24));
    if(diff === 0) return "Hari Ini"; if(diff === -1) return "Kemarin"; if(diff === 1) return "Besok";
    return tgt.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' });
}
function getBadgeStyle(status) {
    if(status === 'published') return { css: 'bg-emerald-900/40 text-emerald-400 border border-emerald-800', text: 'PUBLISHED' };
    if(status === 'failed') return { css: 'bg-red-900/40 text-red-400 border border-red-800', text: 'FAILED' };
    return { css: 'bg-blue-900/40 text-blue-400 border border-blue-800', text: 'SCHEDULED' };
}
function logout() { document.cookie.split(";").forEach(c => document.cookie = c.replace(/^ +/, "").replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/")); location.reload(); }
</script>
</body>
</html>