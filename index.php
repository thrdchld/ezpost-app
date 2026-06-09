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
    <!-- Default theme will be overridden by JS for dark/light mode -->
    <link id="fp-theme" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <style>
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #30363d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #58a6ff; }
        
        /* Light mode scrollbar adjustments */
        html:not(.dark) ::-webkit-scrollbar-thumb { background: #cbd5e1; }
        html:not(.dark) ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        body { font-family: system-ui, sans-serif; transition: background-color 0.3s, color 0.3s; }
        
        /* Dark Mode Colors */
        .dark body { background-color: #0f1115; color: #c9d1d9; }
        .dark .bg-app-card { background-color: #161b22; }
        .dark .border-app { border-color: #30363d; }
        .dark .bg-app-input { background-color: #0d1117; }
        .dark .hover-bg-item:hover { background-color: #1f2937; }
        .dark .active-bg-item { background-color: #1f2937; color: #fff; font-weight: bold; border-left: 3px solid #3b82f6; }
        
        /* Light Mode Colors */
        html:not(.dark) body { background-color: #f1f5f9; color: #1e293b; }
        html:not(.dark) .bg-app-card { background-color: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        html:not(.dark) .border-app { border-color: #e2e8f0; }
        html:not(.dark) .bg-app-input { background-color: #f8fafc; color: #0f172a; }
        html:not(.dark) .hover-bg-item:hover { background-color: #f1f5f9; }
        html:not(.dark) .active-bg-item { background-color: #e0e7ff; color: #1e40af; font-weight: bold; border-left: 3px solid #3b82f6; }

        .view-section { display: none; }
        .view-section.active { display: block; animation: fadeIn 0.2s ease-in-out; }
        
        /* Calendar UI */
        .cal-day { aspect-ratio: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; cursor: pointer; border-radius: 0.5rem; position: relative; transition: all 0.2s; }
        .dark .cal-day:hover { background-color: #1f2937; }
        html:not(.dark) .cal-day:hover { background-color: #e2e8f0; }
        .cal-day.active { background-color: #3b82f6 !important; color: white !important; font-weight: bold; }
        .cal-day.today { border: 1px solid #3b82f6; }
        .cal-dot { position: absolute; bottom: 4px; width: 6px; height: 6px; border-radius: 50%; background-color: #3b82f6; }
        
        /* Media Thumbnails */
        .media-preview-item { position: relative; width: 80px; height: 80px; border-radius: 8px; overflow: hidden; border: 1px solid #30363d; flex-shrink: 0; }
        .media-preview-item img, .media-preview-item video { width: 100%; height: 100%; object-fit: cover; }
        .media-preview-remove { position: absolute; top: 2px; right: 2px; background: rgba(0,0,0,0.7); color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 10px; cursor: pointer; border: 1px solid rgba(255,255,255,0.3); }
        .media-preview-remove:hover { background: red; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body class="antialiased flex h-screen overflow-hidden">

<!-- ========================================== -->
<!-- ICONS DICTIONARY (SVG) -->
<!-- ========================================== -->
<svg style="display: none;">
    <symbol id="icon-fb" viewBox="0 0 24 24"><path fill="currentColor" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.469h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.469h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></symbol>
    <symbol id="icon-threads" viewBox="0 0 24 24"><path fill="currentColor" d="M16.711 13.333c-0.198 1.401-1.037 3.237-2.934 3.237-1.498 0-2.483-1.077-2.483-2.616v-2.071c0-1.579 0.999-2.665 2.528-2.665 1.758 0 2.639 1.589 2.766 2.871h1.996c-0.218-2.222-1.905-4.571-4.762-4.571-2.645 0-4.582 1.872-4.582 4.417v2.019c0 2.502 1.87 4.364 4.509 4.364 2.876 0 4.607-2.235 4.887-4.985h-1.925z M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.666 12.06c0 3.398-2.158 5.76-5.59 5.76-3.415 0-5.632-2.316-5.632-5.717v-1.993c0-3.428 2.22-5.761 5.645-5.761 3.266 0 5.485 2.193 5.568 5.43h-2.029c-0.106-2.122-1.512-3.593-3.539-3.593-2.195 0-3.593 1.487-3.593 3.924v1.993c0 2.392 1.385 3.882 3.58 3.882 2.217 0 3.6-1.554 3.6-4.004h1.99z"/></symbol>
    <symbol id="icon-trash" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></symbol>
    <symbol id="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></symbol>
    <symbol id="icon-image" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></symbol>
</svg>

<!-- ========================================== -->
<!-- TOAST & MODAL (No Alerts!) -->
<!-- ========================================== -->
<div id="toast" class="fixed top-5 right-5 z-[9999] transform transition-transform duration-300 translate-x-full opacity-0 pointer-events-none">
    <div class="bg-gray-800 text-white border-l-4 shadow-xl p-4 rounded flex items-center gap-3" id="toastBox">
        <span id="toastMsg" class="font-medium text-sm">Notifikasi</span>
    </div>
</div>

<div id="editModal" class="fixed inset-0 bg-black/80 z-[9998] hidden flex justify-center items-center">
    <div class="bg-app-card border border-app w-full max-w-sm rounded-xl p-6 shadow-2xl">
        <h3 class="text-lg font-bold mb-4">Ubah Jadwal Tayang</h3>
        <form id="editScheduleForm" class="space-y-4">
            <input type="hidden" id="edit_post_id">
            <input type="text" id="edit_datetime" required class="w-full px-4 py-3 bg-app-input border border-app rounded text-center font-bold outline-none cursor-pointer">
            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded font-bold text-sm">Batal</button>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded font-bold text-sm">Simpan</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteConfirmModal" class="fixed inset-0 bg-black/80 z-[9998] hidden flex justify-center items-center">
    <div class="bg-app-card border border-app w-full max-w-sm rounded-xl p-6 shadow-2xl text-center">
        <div class="w-16 h-16 bg-red-900/30 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-red-500/50">
            <svg class="w-8 h-8"><use href="#icon-trash"></use></svg>
        </div>
        <h3 class="text-lg font-bold mb-2">Hapus Permanen?</h3>
        <p class="text-sm text-gray-400 mb-6" id="deleteConfirmText">Tindakan ini tidak dapat dibatalkan.</p>
        <div class="flex gap-3">
            <button type="button" onclick="$('#deleteConfirmModal').addClass('hidden')" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded font-bold text-sm">Batal</button>
            <button type="button" id="btnConfirmDelete" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded font-bold text-sm">Ya, Hapus</button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. LOGIN SCREEN -->
<!-- ========================================== -->
<?php if (!is_logged_in()): ?>
<div class="w-full h-full flex justify-center items-center bg-darkbg">
    <div class="w-full max-w-sm bg-app-card border border-app rounded-xl p-8 shadow-2xl">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-black tracking-widest">[EZ] POST</h1>
            <p class="text-xs text-gray-500 mt-2">Masuk ke Ruang Kerja</p>
        </div>
        <form id="loginForm" class="space-y-5">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="csrf_token" id="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Email</label>
                <input type="email" name="email" required class="w-full mt-1 px-4 py-3 bg-app-input border border-app rounded-lg outline-none focus:border-blue-500 transition-colors">
            </div>
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase">Password</label>
                <input type="password" name="password" required class="w-full mt-1 px-4 py-3 bg-app-input border border-app rounded-lg outline-none focus:border-blue-500 transition-colors">
            </div>
            <button type="submit" id="btnLogin" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-lg shadow-lg transition-all mt-4">
                Masuk Sistem
            </button>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. DASHBOARD WORKSPACE -->
<!-- ========================================== -->
<?php else: ?>
<!-- SIDEBAR -->
<aside class="w-64 bg-app-card border-r border-app flex flex-col z-10 flex-shrink-0 transition-colors">
    <div class="p-6 border-b border-app flex items-center gap-3">
        <div class="w-8 h-8 bg-blue-600 rounded text-white font-bold flex justify-center items-center text-sm shadow-md">[EZ]</div>
        <h1 class="text-xl font-bold tracking-wide">EZPost</h1>
    </div>
    
    <nav class="flex-1 py-6 px-3 flex flex-col gap-2">
        <a href="#post" id="nav-post" class="sidebar-item hover-bg-item w-full text-left px-4 py-3 rounded-lg text-sm text-gray-500 transition-all flex items-center gap-3">
            <span class="font-mono text-blue-500">[*]</span> Buat Post
        </a>
        <a href="#planner" id="nav-planner" class="sidebar-item hover-bg-item w-full text-left px-4 py-3 rounded-lg text-sm text-gray-500 transition-all flex items-center gap-3">
            <span class="font-mono text-emerald-500">[@]</span> Jadwal Planner
        </a>
        <a href="#gallery" id="nav-gallery" class="sidebar-item hover-bg-item w-full text-left px-4 py-3 rounded-lg text-sm text-gray-500 transition-all flex items-center gap-3">
            <span class="font-mono text-purple-500">[+]</span> Galeri Media
        </a>
        <a href="#status" id="nav-status" class="sidebar-item hover-bg-item w-full text-left px-4 py-3 rounded-lg text-sm text-gray-500 transition-all flex items-center gap-3">
            <span class="font-mono text-amber-500">[#]</span> Status Akun
        </a>
    </nav>

    <div class="p-4 border-t border-app space-y-3">
        <button onclick="toggleTheme()" class="flex justify-between items-center px-3 py-2 w-full hover-bg-item rounded-lg transition-colors">
            <span class="text-xs text-gray-500 font-bold">[C] Tema</span>
            <span id="theme-label" class="text-[10px] font-bold bg-gray-800 text-white px-2 py-1 rounded">GELAP</span>
        </button>
        <button onclick="logout()" class="w-full text-left px-3 py-2 text-sm text-gray-500 hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all flex items-center gap-3">
            <span class="font-mono text-red-500">[x]</span> Keluar
        </button>
    </div>
</aside>

<!-- MAIN CONTENT -->
<main class="flex-1 overflow-y-auto relative transition-colors">
    <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    
    <!-- A. VIEW: BUAT POST -->
    <div id="view-post" class="view-section p-8 max-w-[1200px] mx-auto">
        <form id="postForm" enctype="multipart/form-data">
            
            <div class="flex justify-between items-end mb-6">
                <div class="flex items-center gap-4">
                    <h2 class="text-2xl font-bold">Tulis Sesuatu</h2>
                    <button type="button" onclick="resetForm()" class="text-xs border border-red-500/50 bg-red-500/10 text-red-500 px-3 py-1.5 rounded hover:bg-red-500/20 transition-colors font-bold tracking-wider">[X] RESET</button>
                </div>
                
                <!-- Target Platform Dropdown -->
                <div class="w-64">
                    <select name="platform" id="input_platform" class="w-full bg-app-input border border-app text-sm rounded-lg px-4 py-2.5 outline-none focus:border-blue-500 font-bold appearance-none cursor-pointer">
                        <option value="facebook">[FB] Facebook saja</option>
                        <option value="threads">[TH] Threads saja</option>
                        <option value="fb_threads_media">[+T] FB + TH (Media)</option>
                        <option value="fb_threads_nomedia" selected>[+T] FB + TH (TH Teks Saja)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Kiri: Textarea & Media -->
                <div class="lg:col-span-7 bg-app-card border border-app rounded-xl overflow-hidden flex flex-col shadow-sm">
                    <textarea name="content" id="input_content" rows="10" placeholder="Ketik status atau ide brilian Anda di sini..." class="w-full p-6 bg-transparent resize-none outline-none text-sm leading-relaxed"></textarea>
                    
                    <!-- Media Previews Area -->
                    <div id="media-preview-container" class="px-6 pb-2 flex gap-3 overflow-x-auto hidden">
                        <!-- Thumbs rendered here via JS -->
                    </div>

                    <div class="border-t border-app p-4 bg-app-input flex justify-between items-center">
                        <label class="cursor-pointer text-sm font-bold text-gray-500 hover:text-blue-500 flex items-center gap-2 w-max transition-colors">
                            <span class="text-blue-500 font-mono text-lg">[+]</span> Lampirkan Media
                            <!-- Hidden File Input (we intercept this via JS) -->
                            <input type="file" id="media_selector" multiple accept="image/*,video/*" class="hidden">
                        </label>
                        <div id="media-count" class="text-xs font-bold text-blue-500 hidden px-3 py-1 bg-blue-500/10 rounded-full"></div>
                    </div>
                </div>

                <!-- Kanan: Live Preview Tabbed -->
                <div class="lg:col-span-5 bg-app-card border border-app rounded-xl overflow-hidden flex flex-col shadow-sm h-[400px]">
                    <div class="flex border-b border-app bg-app-input" id="previewTabs">
                        <button type="button" class="flex-1 py-3 text-sm font-bold border-b-2 border-blue-500 text-blue-500" onclick="switchPrev('fb')" id="tab-fb">[FB] Facebook</button>
                        <button type="button" class="flex-1 py-3 text-sm font-bold border-b-2 border-transparent text-gray-500" onclick="switchPrev('th')" id="tab-th">[TH] Threads</button>
                    </div>
                    
                    <!-- MOCKUP TAMPILAN PREVIEW -->
                    <div class="p-5 flex-1 overflow-y-auto">
                        <!-- FB Preview -->
                        <div id="prev-fb" class="block">
                            <div class="bg-app-input border border-app rounded-lg p-4">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded bg-blue-600/20 border border-blue-500/30 flex justify-center items-center text-blue-500 font-bold text-xs">[FB]</div>
                                    <div><div class="font-bold text-sm">Halaman FB Anda</div><div class="text-[10px] text-gray-500">Baru saja - Publik</div></div>
                                </div>
                                <div class="text-sm whitespace-pre-wrap" id="fb-content-prev"></div>
                                <div id="fb-media-prev" class="hidden w-full h-32 bg-gray-200 dark:bg-gray-800 rounded mt-3 flex items-center justify-center text-xs text-gray-500">📸 Media Terlampir</div>
                                <div class="flex justify-between border-t border-app mt-4 pt-3 text-gray-500 text-xs font-bold px-2"><span>👍 Suka</span><span>💬 Komentar</span><span>↪️ Bagikan</span></div>
                            </div>
                        </div>

                        <!-- TH Preview Autentik -->
                        <div id="prev-th" class="hidden">
                            <div class="flex gap-3">
                                <div class="flex flex-col items-center">
                                    <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-700 flex-shrink-0 z-10 border border-app"></div>
                                    <div class="w-0.5 bg-gray-300 dark:bg-gray-700 h-full mt-2"></div>
                                </div>
                                <div class="w-full pb-2">
                                    <div class="font-bold text-sm">@username_threads</div>
                                    <div class="text-sm whitespace-pre-wrap mt-1" id="th-content-prev"></div>
                                    <div id="th-media-prev" class="hidden w-full h-32 bg-gray-200 dark:bg-gray-800 rounded mt-3 border border-app flex items-center justify-center text-xs text-gray-500">📸 Media Terlampir</div>
                                    <div class="flex gap-4 mt-4 text-gray-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 8 9 8z"></path></svg>
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div class="mt-6 bg-app-card border border-app rounded-xl p-5 flex flex-col md:flex-row justify-between items-center gap-4 shadow-sm">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <span class="text-sm font-bold text-gray-500">Waktu (Opsional):</span>
                    <div class="relative w-64">
                        <input type="text" id="input_datetime" name="scheduled_at" placeholder="Langsung Publish..." class="w-full bg-app-input border border-app rounded-lg px-4 py-3 text-sm outline-none focus:border-blue-500 cursor-pointer text-center font-mono font-bold">
                    </div>
                </div>
                
                <button type="submit" id="btnSubmitPost" class="w-full md:w-56 bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-lg transition-all flex justify-center items-center gap-2 shadow-lg">
                    <span>[>] Publish Sekarang</span>
                </button>
            </div>
        </form>
    </div>

    <!-- B. VIEW: PLANNER JADWAL -->
    <div id="view-planner" class="view-section p-8 max-w-[1200px] mx-auto">
        <h2 class="text-2xl font-bold mb-6 border-b border-app pb-4">Jadwal Planner</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            <!-- KALENDER KIRI -->
            <div class="lg:col-span-4">
                <div class="bg-app-card border border-app rounded-xl p-5 shadow-sm sticky top-6">
                    <div class="flex justify-between items-center mb-4">
                        <button onclick="changeMonth(-1)" class="p-2 hover-bg-item rounded text-gray-500 font-bold">&larr;</button>
                        <div class="flex gap-2">
                            <select id="cal-month" class="bg-app-input border border-app text-sm rounded px-2 py-1 outline-none font-bold" onchange="renderCalendar()">
                                <option value="0">Januari</option><option value="1">Februari</option><option value="2">Maret</option><option value="3">April</option><option value="4">Mei</option><option value="5">Juni</option><option value="6">Juli</option><option value="7">Agustus</option><option value="8">September</option><option value="9">Oktober</option><option value="10">November</option><option value="11">Desember</option>
                            </select>
                            <select id="cal-year" class="bg-app-input border border-app text-sm rounded px-2 py-1 outline-none font-bold" onchange="renderCalendar()"></select>
                        </div>
                        <button onclick="changeMonth(1)" class="p-2 hover-bg-item rounded text-gray-500 font-bold">&rarr;</button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center mb-2 text-xs font-bold text-gray-400">
                        <div>Mi</div><div>Se</div><div>Sl</div><div>Ra</div><div>Ka</div><div>Ju</div><div>Sa</div>
                    </div>
                    <div id="calendar-grid" class="grid grid-cols-7 gap-1 text-sm font-mono"></div>
                    
                    <div class="mt-4 pt-4 border-t border-app flex justify-between items-center text-xs">
                        <button onclick="goToToday()" class="text-blue-500 hover:underline font-bold">Kembali Hari Ini</button>
                    </div>
                </div>
            </div>

            <!-- TIMELINE KANAN -->
            <div class="lg:col-span-8">
                <!-- Filter -->
                <div class="bg-app-card border border-app rounded-xl p-4 mb-6 flex gap-4 text-sm">
                    <select id="flt-status" class="bg-app-input border border-app rounded px-3 py-2 outline-none font-bold" onchange="renderTimeline()">
                        <option value="all">Semua Status</option><option value="scheduled">Antrean</option><option value="published">Sukses</option><option value="failed">Gagal</option>
                    </select>
                    <select id="flt-platform" class="bg-app-input border border-app rounded px-3 py-2 outline-none font-bold" onchange="renderTimeline()">
                        <option value="all">Semua Platform</option><option value="facebook">Facebook</option><option value="threads">Threads</option>
                    </select>
                </div>

                <h3 id="timeline-header" class="text-lg font-bold mb-4 border-b border-app pb-2">Jadwal: Hari Ini</h3>
                <div id="timeline-container" class="space-y-4"></div>
            </div>
        </div>
    </div>

    <!-- C. VIEW: GALERI MEDIA (REAL) -->
    <div id="view-gallery" class="view-section p-8 max-w-[1200px] mx-auto">
        <div class="flex justify-between items-center mb-6 border-b border-app pb-4">
            <h2 class="text-2xl font-bold">Galeri Media Server</h2>
            <button onclick="loadGallery()" class="text-sm bg-blue-500/10 text-blue-500 px-4 py-2 rounded font-bold hover:bg-blue-500/20 transition-colors">[↻] Refresh</button>
        </div>
        <div id="gallery-container" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <!-- Rendered via JS -->
            <div class="col-span-full text-center py-10 text-gray-500 font-mono text-sm">Memuat galeri...</div>
        </div>
    </div>

    <!-- D. VIEW: STATUS AKUN -->
    <div id="view-status" class="view-section p-8 max-w-[800px] mx-auto">
        <h2 class="text-2xl font-bold mb-6 border-b border-app pb-4">Status Koneksi API</h2>
        <div class="bg-app-card border border-app rounded-xl p-6 shadow-sm space-y-4">
            <div class="flex justify-between items-center p-4 bg-app-input rounded-lg border border-app">
                <span class="font-bold text-sm flex items-center gap-3"><svg class="w-6 h-6 text-blue-500"><use href="#icon-fb"></use></svg> Facebook Fanpage</span>
                <span id="stat-fb" class="text-xs font-bold px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-500 rounded tracking-wider">MENGECEK...</span>
            </div>
            <div class="flex justify-between items-center p-4 bg-app-input rounded-lg border border-app">
                <span class="font-bold text-sm flex items-center gap-3"><svg class="w-6 h-6 text-gray-800 dark:text-white"><use href="#icon-threads"></use></svg> Threads Account</span>
                <span id="stat-th" class="text-xs font-bold px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-500 rounded tracking-wider">MENGECEK...</span>
            </div>
            <button onclick="checkStatus()" class="w-full py-3 bg-blue-500/10 text-blue-500 hover:bg-blue-500/20 rounded-lg text-sm font-bold mt-4 transition-colors">
                [↻] Refresh Status Koneksi
            </button>
        </div>
    </div>
</main>
<?php endif; ?>

<!-- ========================================== -->
<!-- CORE JAVASCRIPT & LOGIC (Robust & Full) -->
<!-- ========================================== -->
<script>
// --- 1. GLOBAL STATE & SAFE PARSER ---
const csrfToken = $('#csrf_token').val() || '';
let selectedFiles = []; // Wajib di atas agar tidak ReferenceError
let globalPosts = [];
let currentCalDate = new Date();
let selectedDateStr = "";
let fpEditInstance = null;
let fpPostInstance = null;
let currentActionPostId = null;
let isDarkMode = true;

// Safe AJAX Caller untuk menangkal iklan InfinityFree
async function safeAjax(url, formData) {
    try {
        let response = await $.ajax({ url: url, type: 'POST', data: formData, processData: false, contentType: false });
        if(typeof response === 'object') return response;
        if(typeof response === 'string') {
            let clean = response.replace(/<!--.*?-->/gs, '').replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '').trim();
            return JSON.parse(clean);
        }
    } catch(e) {
        console.error("AJAX Error:", e);
        return { status: 'error', message: 'Koneksi ke server gagal atau diblokir.' };
    }
}

// --- 2. INITIALIZATION ---
$(document).ready(function() {
    // A. SETUP THEME
    let savedTheme = localStorage.getItem('ez_theme');
    if(savedTheme === 'light') {
        isDarkMode = false;
        $('html').removeClass('dark');
        $('#theme-label').text('TERANG').removeClass('bg-gray-800 text-white').addClass('bg-gray-200 text-gray-800');
        $('#fp-theme').attr('href', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    }

    // B. LOGIN LOGIC
    if ($('#loginForm').length > 0) {
        $('#loginForm').submit(async function(e) {
            e.preventDefault();
            $('#btnLogin').prop('disabled', true).text('Memverifikasi...');
            let res = await safeAjax('api.php', new FormData(this));
            if(res.status === 'success') window.location.reload();
            else { showToast(res.message, 'error'); $('#btnLogin').prop('disabled', false).text('Masuk Sistem'); }
        });
        return; // Hentikan script jika di halaman login
    }

    // C. DASHBOARD INIT
    initRouter();
    
    // Setup Flatpickr
    fpPostInstance = flatpickr("#input_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true, minDate: "today" });
    fpEditInstance = flatpickr("#edit_datetime", { enableTime: true, dateFormat: "Y-m-d H:i", time_24hr: true, minDate: "today", appendTo: document.getElementById('editModal') });

    // Setup Draft
    if(localStorage.getItem('ezpost_draft')) $('#input_content').val(localStorage.getItem('ezpost_draft'));
    $('#input_content').on('input', function() { localStorage.setItem('ezpost_draft', $(this).val()); updatePreview(); });
    $('#input_platform').on('change', updatePreview);

    // Setup Media Array Handler (The Fix for `selectedFiles`)
    $('#media_selector').on('change', function(e) {
        let files = Array.from(e.target.files);
        files.forEach(f => selectedFiles.push(f));
        $(this).val(''); // Reset input
        renderMediaThumbs();
        updatePreview();
    });

    // Setup Planner Cal
    let ySel = $('#cal-year');
    let currYr = new Date().getFullYear();
    for(let i = currYr - 1; i <= currYr + 2; i++) ySel.append(`<option value="${i}">${i}</option>`);
    goToToday();

    // Setup Form Post Submit
    $('#postForm').submit(async function(e) {
        e.preventDefault();
        
        let content = $('#input_content').val().trim();
        if(content === '' && selectedFiles.length === 0) {
            showToast("Konten atau Media tidak boleh kosong!", "error"); return;
        }

        let btn = $('#btnSubmitPost'); let ori = btn.html();
        btn.prop('disabled', true).html('⏳ Mengunggah...');
        
        let fd = new FormData(this);
        fd.delete('media[]'); // Remove empty hidden input
        // Append actual files
        selectedFiles.forEach(file => { fd.append('media[]', file); });
        
        let res = await safeAjax('api.php', fd);
        showToast(res.message, res.status);
        
        if(res.status === 'success') {
            resetForm();
            loadAllHistory();
        }
        btn.prop('disabled', false).html(ori);
    });

    // Edit Modal Submit
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
});

// --- 3. SPA ROUTER & THEME ---
function initRouter() {
    $(window).on('hashchange', function() {
        let hash = window.location.hash || '#post';
        let viewId = hash.replace('#', '');
        
        $('.view-section').removeClass('active');
        $('.sidebar-item').removeClass('active-bg-item text-blue-500').addClass('text-gray-500');
        
        $(`#view-${viewId}`).addClass('active');
        let nav = $(`#nav-${viewId}`);
        if(nav.length) nav.addClass('active-bg-item text-blue-500').removeClass('text-gray-500');
        
        if(viewId === 'planner') loadAllHistory();
        if(viewId === 'gallery') loadGallery();
        if(viewId === 'status') checkStatus();
    });
    // Trigger initial route
    $(window).trigger('hashchange');
}

function switchTab(viewId) {
    window.location.hash = '#' + viewId;
}

function toggleTheme() {
    isDarkMode = !isDarkMode;
    if(isDarkMode) {
        $('html').addClass('dark');
        localStorage.setItem('ez_theme', 'dark');
        $('#theme-label').text('GELAP').removeClass('bg-gray-200 text-gray-800').addClass('bg-gray-800 text-white');
        $('#fp-theme').attr('href', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css');
    } else {
        $('html').removeClass('dark');
        localStorage.setItem('ez_theme', 'light');
        $('#theme-label').text('TERANG').removeClass('bg-gray-800 text-white').addClass('bg-gray-200 text-gray-800');
        $('#fp-theme').attr('href', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    }
}

// --- 4. MEDIA MANAGEMENT (Create Post) ---
function renderMediaThumbs() {
    let container = $('#media-preview-container');
    if(selectedFiles.length === 0) {
        container.addClass('hidden').empty();
        $('#media-count').addClass('hidden');
        return;
    }

    container.removeClass('hidden').empty();
    $('#media-count').removeClass('hidden').text(`[+] ${selectedFiles.length} File Dipilih`);

    selectedFiles.forEach((file, index) => {
        let url = URL.createObjectURL(file);
        let mediaHtml = file.type.startsWith('video/') 
            ? `<video src="${url}" muted></video>` 
            : `<img src="${url}">`;
        
        container.append(`
            <div class="media-preview-item shadow-md">
                ${mediaHtml}
                <div class="media-preview-remove" onclick="removeMedia(${index})">X</div>
            </div>
        `);
    });
}

function removeMedia(index) {
    selectedFiles.splice(index, 1);
    renderMediaThumbs();
    updatePreview();
}

function resetForm() {
    $('#postForm')[0].reset();
    clearDraft();
    selectedFiles = [];
    renderMediaThumbs();
    $('#input_platform').val('fb_threads_nomedia').trigger('change');
    if(fpPostInstance) fpPostInstance.clear();
    updatePreview();
}

function clearDraft() { 
    localStorage.removeItem('ezpost_draft'); 
    $('#input_content').val(''); 
    updatePreview(); 
}

// --- 5. LIVE PREVIEW ---
function updatePreview() {
    const plat = $('#input_platform').val();
    const content = $('#input_content').val() || '<span class="text-gray-500 italic">Mulai mengetik...</span>';
    const hasMedia = selectedFiles.length > 0;
    
    let isFb = (plat === 'facebook' || plat.startsWith('fb_threads'));
    let isTh = (plat === 'threads' || plat.startsWith('fb_threads'));
    
    $('#tab-fb').toggle(isFb); $('#tab-th').toggle(isTh);
    if(isFb) switchPrev('fb'); else switchPrev('th');

    $('#fb-content-prev').html(content);
    $('#th-content-prev').html(content);
    
    $('#fb-media-prev').toggle(hasMedia);
    $('#th-media-prev').toggle(hasMedia && plat !== 'fb_threads_nomedia');
}

function switchPrev(target) {
    $('#prev-fb, #prev-th').hide();
    $(`#prev-${target}`).show();
    $('#tab-fb, #tab-th').removeClass('border-blue-500 text-blue-500').addClass('border-transparent text-gray-500');
    $(`#tab-${target}`).addClass('border-blue-500 text-blue-500').removeClass('border-transparent text-gray-500');
}

// --- 6. PLANNER (KALENDER & TIMELINE) ---
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
        let isActive = (loopDate === selectedDateStr) ? 'active' : '';
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
        html = `<div class="text-center py-10 bg-app-input rounded-xl border border-app text-gray-500 font-mono text-sm shadow-inner">Tidak ada jadwal ditemukan.</div>`;
    } else {
        filtered.forEach(post => {
            let bInfo = { bg: 'bg-gray-500', text: 'UNKNOWN' };
            if (post.status === 'published') bInfo = { bg: 'bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/50', text: 'PUBLISHED' };
            else if (post.status === 'failed') bInfo = { bg: 'bg-red-500/20 text-red-600 dark:text-red-400 border border-red-500/50', text: 'FAILED' };
            else if (post.status === 'scheduled') bInfo = { bg: 'bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-500/50', text: 'SCHEDULED' };

            let pName = post.platform.toUpperCase();
            let errHtml = post.error_log ? `<div class="mt-3 p-2 text-xs bg-red-500/10 text-red-500 rounded font-mono border border-red-500/30">${post.error_log}</div>` : '';
            
            let btnHtml = '';
            if(post.status === 'scheduled' || post.status === 'failed') {
                btnHtml = `<div class="mt-4 flex flex-wrap gap-2 pt-3 border-t border-app">
                    <button class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded shadow" onclick="actionPost('force', ${post.id})">[>] Kirim Sekarang</button>
                    <button class="px-3 py-1.5 bg-gray-500 hover:bg-gray-600 text-white text-xs font-bold rounded shadow" onclick="openEditModal(${post.id}, '${post.scheduled_at}')">[✏️] Ubah Waktu</button>
                    <button class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-xs font-bold rounded border border-red-500/50" onclick="confirmDelete(${post.id})">[🗑️] Hapus</button>
                </div>`;
            }

            html += `
            <div class="bg-app-card border border-app rounded-xl p-5 flex gap-4 hover:border-blue-500/30 transition-colors shadow-sm">
                <div class="text-right flex-shrink-0 w-12">
                    <div class="text-sm font-bold">${post.scheduled_time_raw}</div>
                </div>
                <div class="border-l border-app pl-4 w-full">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded tracking-wide ${bInfo.bg}">${bInfo.text}</span>
                        <span class="text-xs text-gray-500 font-bold font-mono">[${pName}]</span>
                    </div>
                    <p class="text-sm whitespace-pre-wrap">${post.content}</p>
                    ${errHtml}
                    ${btnHtml}
                </div>
            </div>`;
        });
    }
    $('#timeline-container').html(html);
}

// --- 7. ACTION & MODALS ---
async function actionPost(act, pid) {
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

function confirmDelete(pid) {
    currentActionPostId = pid;
    $('#deleteConfirmModal').removeClass('hidden');
}

$('#btnConfirmDelete').click(function() {
    $('#deleteConfirmModal').addClass('hidden');
    if(currentActionPostId) actionPost('delete', currentActionPostId);
});

function openEditModal(pid, dTimeStr) {
    $('#edit_post_id').val(pid);
    if(fpEditInstance) fpEditInstance.setDate(dTimeStr);
    $('#editModal').removeClass('hidden');
}
function closeEditModal() { $('#editModal').addClass('hidden'); }

// --- 8. GALLERY LOGIC ---
async function loadGallery() {
    let container = $('#gallery-container');
    container.html('<div class="col-span-full text-center py-10 text-blue-500 font-bold text-sm">Sedang mengambil data server...</div>');
    
    let fd = new FormData(); fd.append('action', 'get_gallery'); fd.append('csrf_token', csrfToken);
    let res = await safeAjax('api.php', fd);
    
    if(res.status !== 'success') {
        container.html(`<div class="col-span-full text-center py-10 text-red-500 font-bold">${res.message}</div>`); return;
    }
    
    if(res.data.length === 0) {
        container.html('<div class="col-span-full text-center py-10 text-gray-500 font-mono text-sm border border-dashed border-app rounded-xl bg-app-input">Tidak ada media yang tersimpan.</div>'); return;
    }

    let html = '';
    res.data.forEach(file => {
        let mediaTag = file.type === 'video' 
            ? `<video src="${file.url}" class="w-full h-32 object-cover rounded-t-lg" controls preload="metadata"></video>`
            : `<a href="${file.url}" target="_blank"><img src="${file.url}" class="w-full h-32 object-cover rounded-t-lg hover:opacity-80 transition-opacity"></a>`;
            
        html += `
        <div class="bg-app-card border border-app rounded-lg shadow-sm flex flex-col">
            ${mediaTag}
            <div class="p-3 flex-1 flex flex-col justify-between">
                <div>
                    <div class="text-xs text-gray-400 truncate mb-1 font-mono" title="${file.name}">${file.name}</div>
                    <div class="text-[10px] font-bold text-gray-500">${file.size} • ${file.date_formatted}</div>
                </div>
                <button onclick="deleteMedia('${file.name}')" class="mt-3 w-full py-1.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 text-xs font-bold rounded border border-red-500/30 transition-colors">Hapus</button>
            </div>
        </div>`;
    });
    container.html(html);
}

async function deleteMedia(filename) {
    if(!confirm(`Hapus file ${filename} permanen dari server?`)) return;
    let fd = new FormData(); fd.append('action', 'delete_media'); fd.append('filename', filename); fd.append('csrf_token', csrfToken);
    let res = await safeAjax('api.php', fd);
    showToast(res.message, res.status);
    if(res.status === 'success') loadGallery();
}

// --- 9. STATUS CHECKER ---
async function checkStatus() {
    let statFb = $('#stat-fb'); let statTh = $('#stat-th');
    statFb.removeClass().addClass("text-xs font-bold px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-500 rounded tracking-wider").text("MENGECEK...");
    statTh.removeClass().addClass("text-xs font-bold px-3 py-1.5 bg-gray-200 dark:bg-gray-800 text-gray-500 rounded tracking-wider").text("MENGECEK...");
    
    let fd = new FormData(); fd.append('action', 'check_status'); fd.append('csrf_token', csrfToken);
    let res = await safeAjax('api.php', fd);
    
    if(res.status === 'success') {
        let clOk = "text-xs font-bold px-3 py-1.5 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30 rounded tracking-wider";
        let clErr = "text-xs font-bold px-3 py-1.5 bg-red-500/20 text-red-600 dark:text-red-400 border border-red-500/30 rounded tracking-wider";
        statFb.removeClass().addClass(res.facebook ? clOk : clErr).text(res.facebook ? "✔ TERHUBUNG" : "✖ TERPUTUS");
        statTh.removeClass().addClass(res.threads ? clOk : clErr).text(res.threads ? "✔ TERHUBUNG" : "✖ TERPUTUS");
    }
}

// --- 10. UTILS ---
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