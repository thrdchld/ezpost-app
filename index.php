<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZPost Enterprise Planner</title>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else { document.documentElement.classList.remove('dark'); }
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: 'var(--bg-main)', panel: 'var(--panel)', borderCol: 'var(--border-col)',
                        textMain: 'var(--text-main)', textSec: 'var(--text-sec)', accent: 'var(--accent)', accentHover: 'var(--accent-hover)'
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            color-scheme: light;
            --bg-main: #F3F4F6; --panel: #FFFFFF; --border-col: #D1D5DB;
            --text-main: #111827; --text-sec: #4B5563; --accent: #2563EB; --accent-hover: #1D4ED8;
            --error-bg: #FEE2E2; --error-text: #B91C1C;
        }
        .dark {
            color-scheme: dark;
            --bg-main: #0f1115; --panel: #161b22; --border-col: #30363d;
            --text-main: #c9d1d9; --text-sec: #8b949e; --accent: #3B82F6; --accent-hover: #60A5FA;
            --error-bg: rgba(153, 27, 27, 0.2); --error-text: #F87171;
        }
        body { background-color: var(--bg-main); color: var(--text-main); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .thread-divider { position: relative; }
        .thread-divider::before { content: ''; position: absolute; left: 1rem; top: 2.5rem; bottom: -1rem; width: 2px; background-color: var(--border-col); z-index: 0;}
        .thread-divider:last-child::before { display: none; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: var(--border-col); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }
        textarea:focus, input:focus, select:focus, button:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
        
        /* Flatpickr Dark Mode Overrides */
        .flatpickr-calendar { background: var(--panel) !important; border: 1px solid var(--border-col) !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2) !important; color: var(--text-main) !important; }
        .flatpickr-months .flatpickr-month, .flatpickr-months .flatpickr-prev-month, .flatpickr-months .flatpickr-next-month { color: var(--text-main) !important; fill: var(--text-main) !important; }
        .flatpickr-current-month .flatpickr-monthDropdown-months { background: var(--panel) !important; }
        .flatpickr-weekday { color: var(--text-sec) !important; }
        .flatpickr-day { color: var(--text-main) !important; }
        .flatpickr-day:hover, .flatpickr-day:focus { background: var(--bg-main) !important; border-color: var(--border-col) !important; }
        .flatpickr-day.selected { background: var(--accent) !important; border-color: var(--accent) !important; color: #fff !important; }
        .flatpickr-time { border-top: 1px solid var(--border-col) !important; }
        .flatpickr-time input, .flatpickr-time .flatpickr-am-pm { color: var(--text-main) !important; }
        .flatpickr-time input:hover, .flatpickr-time input:focus { background: var(--bg-main) !important; }
    </style>
</head>
<body class="bg-bgMain text-textMain h-[100dvh] w-full overflow-hidden flex flex-col md:flex-row text-[15px]">

    <!-- NO ALERTS! GLOBAL MODAL & TOAST UI -->
    <div id="ezModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none transition-opacity duration-200 hidden">
        <div class="bg-panel border border-borderCol rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl transform scale-95 transition-transform duration-200" id="ezModalContent">
            <h3 class="text-xl font-bold text-textMain mb-2" id="ezModalTitle">Perhatian</h3>
            <div class="text-sm text-textSec mb-6 leading-relaxed max-h-60 overflow-y-auto" id="ezModalBody">Pesan disini</div>
            <div class="flex justify-end gap-3" id="ezModalActions"></div>
        </div>
    </div>
    <div id="toastContainer" class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none"></div>

    <?php if (!is_logged_in()): ?>
    <!-- LOGIN SCREEN -->
    <div class="flex items-center justify-center w-full h-full p-4 overflow-y-auto">
        <div class="bg-panel p-8 rounded-xl border border-borderCol w-full max-w-md shadow-lg">
            <div class="flex justify-center mb-6">
                <div class="w-12 h-12 bg-accent rounded-xl flex items-center justify-center shadow-md text-white">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
            </div>
            <h1 class="text-2xl font-bold mb-6 text-center text-textMain">Masuk ke EZPost</h1>
            <form id="loginForm" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-textSec mb-1">Email</label>
                    <input type="email" name="email" required class="w-full bg-bgMain border border-borderCol rounded-lg px-4 py-3 focus:border-accent text-textMain">
                </div>
                <div>
                    <label class="block text-sm font-medium text-textSec mb-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-bgMain border border-borderCol rounded-lg px-4 py-3 focus:border-accent text-textMain">
                </div>
                <button type="submit" class="w-full bg-accent hover:bg-accentHover text-white font-semibold rounded-lg px-4 py-3 mt-6 shadow-md flex justify-center items-center gap-2 group">
                    <svg class="transition-transform group-hover:translate-x-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Masuk
                </button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- MAIN DASHBOARD -->
    <header class="md:hidden flex items-center justify-between p-4 border-b border-borderCol bg-panel w-full shrink-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-accent rounded flex items-center justify-center shadow-sm text-white"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg></div>
            <h1 class="text-lg font-bold text-textMain tracking-wide">EZPost</h1>
        </div>
        <button id="menuBtn" class="text-textSec hover:text-textMain p-2 border border-transparent hover:border-borderCol rounded transition-all"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
    </header>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden transition-opacity"></div>
    <aside id="sidebar" class="w-64 border-r border-borderCol bg-panel flex flex-col h-full fixed md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40 shrink-0 shadow-xl md:shadow-none">
        <div class="p-6 hidden md:flex items-center gap-3">
            <div class="w-10 h-10 bg-accent rounded-lg flex items-center justify-center shadow-md text-white"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg></div>
            <h1 class="text-xl font-bold text-textMain tracking-wide">EZPost</h1>
        </div>
        
        <nav class="flex-1 px-4 space-y-2 mt-4 md:mt-0 overflow-y-auto">
            <a href="#create" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg bg-borderCol font-medium text-textMain transition-all" data-target="create">
                <svg class="text-accent transition-colors" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg> <span>Buat Post</span>
            </a>
            <a href="#scheduled" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="scheduled">
                <svg class="group-hover:text-accent transition-colors" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> <span>Planner</span>
            </a>
            <a href="#media" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="media">
                <svg class="group-hover:text-accent transition-colors" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg> <span>Galeri Media</span>
            </a>
            <a href="#accounts" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="accounts">
                <svg class="group-hover:text-accent transition-colors" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> <span>Akun</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-borderCol space-y-2 bg-panel">
            <button id="themeToggle" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-textSec hover:bg-borderCol/50 hover:text-textMain transition-all group">
                <span class="flex items-center gap-3" id="themeIconContainer"></span>
                <span id="themeLabel" class="text-xs font-bold px-2 py-1 bg-bgMain rounded border border-borderCol transition-colors">GELAP</span>
            </button>
            <button onclick="logout()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-textSec hover:bg-[var(--error-bg)] hover:text-[var(--error-text)] transition-all">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> <span>Keluar</span>
            </button>
        </div>
    </aside>

    <main class="flex-1 min-h-0 overflow-y-auto bg-bgMain relative w-full flex flex-col">
        <!-- VIEW: CREATE POST -->
        <section id="view-create" class="view-section p-4 md:p-8 max-w-6xl mx-auto w-full block">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 relative z-10">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-textMain tracking-tight">Tulis Sesuatu</h2>
                    <button id="btnResetDraft" class="hidden px-3 py-1.5 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] rounded-md text-xs font-bold hover:bg-red-700 hover:text-white transition-colors flex items-center gap-1 shadow-sm">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg> Reset
                    </button>
                </div>
                
                <div class="relative w-full sm:w-auto min-w-[280px]" id="platformDropdownWrap">
                    <button type="button" id="platformTrigger" class="w-full bg-panel border border-borderCol rounded-lg px-4 py-2.5 text-textMain hover:border-accent shadow-sm flex items-center justify-between transition-colors">
                        <div class="flex items-center gap-3" id="platformSelectedText">
                            <svg class="text-[#1877F2]" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            <span class="font-medium text-sm">FB + Threads (No Media)</span>
                        </div>
                        <svg class="text-textSec transition-transform duration-200" id="platformArrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    
                    <div id="platformMenu" class="hidden absolute top-full right-0 mt-2 w-full bg-panel border border-borderCol rounded-lg shadow-xl overflow-hidden opacity-0 transform -translate-y-2 transition-all duration-200 z-50">
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain border-b border-borderCol flex items-center gap-3 transition-colors text-sm" data-value="fb_threads_nomedia">
                            <div class="flex"><svg class="text-[#1877F2]" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg><svg class="text-textMain ml-1" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg></div>
                            <span class="font-bold">FB + Threads (No Media)</span>
                        </button>
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain border-b border-borderCol flex items-center gap-3 transition-colors text-sm" data-value="fb_threads_media">
                            <div class="flex"><svg class="text-[#1877F2]" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg><svg class="text-textMain ml-1" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg></div>
                            <span class="font-medium">FB + Threads (With Media)</span>
                        </button>
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain border-b border-borderCol flex items-center gap-3 transition-colors text-sm" data-value="facebook">
                            <svg class="text-[#1877F2]" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            <span class="font-medium">Facebook Only</span>
                        </button>
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain flex items-center gap-3 transition-colors text-sm" data-value="threads">
                            <svg class="text-textMain" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg>
                            <span class="font-medium">Threads Only</span>
                        </button>
                    </div>
                    <input type="hidden" id="platformSelect" value="fb_threads_nomedia">
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- PANEL KIRI (EDITOR & JADWAL) -->
                <div class="flex-1 flex flex-col gap-6 relative z-0">
                    <div class="bg-panel border border-borderCol rounded-xl p-5 shadow-sm flex flex-col">
                        <textarea id="postContent" class="w-full bg-transparent resize-none flex-1 min-h-[200px] text-textMain placeholder-textSec border-none p-0 focus:ring-0 leading-relaxed text-base" placeholder="Apa yang ingin Anda bagikan hari ini?"></textarea>
                        <div id="mediaPreviewContainer" class="flex flex-wrap gap-3 mt-4 empty:hidden"></div>
                        <div class="mt-4 border-t border-borderCol pt-4 flex items-center justify-between">
                            <label class="cursor-pointer text-textSec hover:text-accent transition-colors flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-bgMain border border-transparent hover:border-borderCol group">
                                <svg class="group-hover:scale-110 transition-transform" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                <span class="font-medium text-sm">Lampirkan Media</span>
                                <input type="file" id="mediaUpload" multiple accept="image/*,video/mp4" class="hidden">
                            </label>
                            <span id="mediaCount" class="text-xs font-bold text-accent bg-accent/10 px-2 py-1 rounded hidden"></span>
                        </div>
                    </div>
                    
                    <!-- ACTION BAR / JADWAL (DI BAWAH EDITOR) -->
                    <div class="bg-panel p-5 rounded-xl border border-borderCol shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="w-full sm:w-auto relative" id="scheduleWrap">
                            <label class="text-xs font-bold text-textSec uppercase tracking-wider mb-2 block">Jadwal Tayang</label>
                            <div class="flex items-center relative">
                                <input type="text" id="scheduleTime" placeholder="Pilih Tanggal & Jam..." data-input class="w-full sm:w-[240px] bg-bgMain border border-borderCol rounded-l-lg pl-4 pr-10 py-2.5 text-sm focus:border-accent text-textMain shadow-inner transition-colors cursor-pointer">
                                <button type="button" id="btnClearDate" class="hidden absolute right-12 text-red-500 hover:text-red-700 bg-bgMain px-1" title="Hapus Jadwal" data-clear>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                </button>
                                <button type="button" data-toggle class="p-2.5 bg-panel border border-borderCol hover:border-accent hover:text-accent rounded-r-lg transition-colors text-textSec flex items-center justify-center border-l-0 shadow-sm" title="Buka Kalender">
                                    <svg class="transition-transform hover:scale-110" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="w-full sm:w-auto mt-4 sm:mt-0 self-end">
                            <button id="btnPublish" class="w-full sm:w-auto bg-accent hover:bg-accentHover text-white px-8 py-3 rounded-lg font-bold transition-all shadow-md flex items-center justify-center gap-2 group cursor-pointer">
                                <span id="publishBtnIcon" class="flex items-center z-10">
                                    <svg class="group-hover:translate-x-1 transition-transform" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                </span>
                                <span id="publishBtnText" class="z-10">Publish Sekarang</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PANEL KANAN (LIVE PREVIEW) -->
                <div class="w-full lg:w-96 flex flex-col gap-4">
                    <div class="bg-panel border border-borderCol rounded-xl shadow-sm flex flex-col overflow-hidden h-[500px]">
                        <!-- TABS PREVIEW MULTI-PLATFORM -->
                        <div class="bg-bgMain px-2 pt-2 flex gap-1 border-b border-borderCol" id="previewTabsContainer">
                            <!-- Di-render oleh JS -->
                        </div>
                        <div class="p-4 flex-1 overflow-y-auto bg-panel" id="previewContentContainer">
                            <!-- Preview Asli -->
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- VIEW: PUBLISH PLANNER -->
        <section id="view-scheduled" class="view-section p-4 md:p-8 w-full max-w-6xl mx-auto hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Publish Planner</h2>
            </div>
            
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- KALENDER KIRI -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-panel border border-borderCol rounded-xl p-5 shadow-sm sticky top-4">
                        <div class="flex justify-between items-center mb-4">
                            <button onclick="changeMonth(-1)" class="p-2 hover:bg-bgMain rounded text-textSec">&larr;</button>
                            <div class="flex gap-2 font-bold text-sm">
                                <select id="cal-month" class="bg-bgMain border border-borderCol text-textMain rounded px-2 py-1 outline-none focus:border-accent" onchange="renderCalendar()">
                                    <option value="0">Januari</option><option value="1">Februari</option><option value="2">Maret</option><option value="3">April</option><option value="4">Mei</option><option value="5">Juni</option><option value="6">Juli</option><option value="7">Agustus</option><option value="8">September</option><option value="9">Oktober</option><option value="10">November</option><option value="11">Desember</option>
                                </select>
                                <select id="cal-year" class="bg-bgMain border border-borderCol text-textMain rounded px-2 py-1 outline-none focus:border-accent" onchange="renderCalendar()"></select>
                            </div>
                            <button onclick="changeMonth(1)" class="p-2 hover:bg-bgMain rounded text-textSec">&rarr;</button>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center mb-2">
                            <div class="text-xs font-bold text-textSec">Mi</div><div class="text-xs font-bold text-textSec">Se</div><div class="text-xs font-bold text-textSec">Sl</div><div class="text-xs font-bold text-textSec">Ra</div><div class="text-xs font-bold text-textSec">Ka</div><div class="text-xs font-bold text-textSec">Ju</div><div class="text-xs font-bold text-textSec">Sa</div>
                        </div>
                        <div id="calendar-grid" class="grid grid-cols-7 gap-1 text-sm"></div>
                        <div class="mt-4 pt-4 border-t border-borderCol flex justify-between items-center text-xs">
                            <button onclick="goToToday()" class="text-accent hover:underline font-bold">Hari Ini</button>
                            <span class="flex items-center gap-1 text-textSec"><div class="w-2 h-2 rounded-full bg-accent"></div> Ada Agenda</span>
                        </div>
                    </div>
                </div>

                <!-- TIMELINE KANAN -->
                <div class="w-full lg:w-2/3">
                    <div class="flex flex-wrap gap-2 mb-4">
                        <select id="flt-status" class="bg-panel border border-borderCol text-textMain rounded-lg px-3 py-2 text-sm font-bold shadow-sm focus:border-accent" onchange="renderTimeline()">
                            <option value="all">Semua Status</option><option value="scheduled">Antrean</option><option value="published">Sukses Tayang</option><option value="failed">Gagal</option>
                        </select>
                        <select id="flt-platform" class="bg-panel border border-borderCol text-textMain rounded-lg px-3 py-2 text-sm font-bold shadow-sm focus:border-accent" onchange="renderTimeline()">
                            <option value="all">Semua Platform</option><option value="facebook">Facebook</option><option value="threads">Threads</option>
                        </select>
                    </div>
                    <h2 id="timeline-header" class="text-lg font-bold text-textMain mb-4 border-b border-borderCol pb-2">Jadwal: -</h2>
                    <div id="timeline-container" class="space-y-4"></div>
                </div>
            </div>
        </section>

        <!-- View: Galeri Media -->
        <section id="view-media" class="view-section p-4 md:p-8 w-full max-w-6xl mx-auto hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Galeri Media</h2>
                <div class="flex items-center gap-3 flex-wrap">
                    <select id="mediaSortSelect" class="bg-panel border border-borderCol text-textMain rounded-lg px-3 py-2 text-sm font-bold shadow-sm focus:border-accent outline-none cursor-pointer">
                        <option value="date">Urutkan: Terbaru</option>
                        <option value="size">Urutkan: Ukuran Besar</option>
                    </select>
                    <button id="btnSelectAllMedia" class="px-3 py-2 text-textSec bg-bgMain border border-borderCol hover:border-accent hover:text-accent transition-colors rounded-lg text-sm font-bold shadow-sm">
                        Pilih Semua
                    </button>
                    <button id="btnDeleteSelectedMedia" class="hidden px-3 py-2 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] hover:bg-red-700 hover:text-white transition-colors rounded-lg text-sm font-bold shadow-sm flex items-center gap-1">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                        Hapus (<span id="selectedMediaCount">0</span>)
                    </button>
                </div>
            </div>
            <div id="mediaContainer" class="w-full"></div>
        </section>

        <!-- View: Accounts -->
        <section id="view-accounts" class="view-section p-4 md:p-8 w-full max-w-5xl mx-auto hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Status API & Akun</h2>
                <button onclick="checkAccountStatus()" class="p-2 text-textSec hover:text-accent transition-colors bg-panel rounded-lg border border-borderCol shadow-sm flex items-center gap-2 text-sm font-bold">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg> Cek Status
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-panel border border-borderCol rounded-xl p-6 shadow-sm group hover:border-accent transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg flex items-center gap-3">
                            <div class="bg-[#1877F2]/10 p-2 rounded-lg text-[#1877F2]"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></div> Facebook
                        </h3>
                        <span id="statusFb" class="px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded flex items-center gap-1">Memeriksa...</span>
                    </div>
                </div>
                <div class="bg-panel border border-borderCol rounded-xl p-6 shadow-sm group hover:border-accent transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg flex items-center gap-3">
                            <div class="bg-borderCol/50 p-2 rounded-lg text-textMain"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg></div> Threads
                        </h3>
                        <span id="statusThreads" class="px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded flex items-center gap-1">Memeriksa...</span>
                    </div>
                </div>
            </div>
        </section>
        <div class="h-32 w-full shrink-0 md:hidden block border-t border-transparent"></div>
    </main>
    <?php endif; ?>

    <!-- MODAL EDIT JADWAL -->
    <div id="editModal" class="fixed inset-0 bg-black/80 z-[110] hidden flex justify-center items-center opacity-0 transition-opacity duration-300">
        <div class="bg-panel border border-borderCol w-full max-w-sm rounded-xl p-6 shadow-2xl transform scale-95 transition-transform duration-300" id="editModalContent">
            <h3 class="text-lg font-bold text-textMain mb-4 border-b border-borderCol pb-2">Jadwal Ulang</h3>
            <div class="space-y-4">
                <input type="hidden" id="edit_post_id">
                <div class="relative w-full" id="editScheduleWrap">
                    <input type="text" id="edit_datetime" placeholder="Pilih Waktu Baru..." data-input class="w-full bg-bgMain border border-borderCol rounded-lg pl-4 pr-10 py-2.5 text-sm focus:border-accent text-textMain shadow-inner transition-colors cursor-pointer">
                    <button type="button" data-toggle class="absolute right-0 top-0 bottom-0 px-3 bg-panel border-l border-borderCol hover:text-accent rounded-r-lg text-textSec flex items-center justify-center">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </button>
                </div>
                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2 bg-bgMain border border-borderCol text-textSec hover:text-textMain rounded-md font-medium text-sm">Batal</button>
                    <button type="button" onclick="submitEditSchedule()" class="flex-1 px-4 py-2 bg-accent hover:bg-accentHover text-white rounded-md font-medium shadow-sm text-sm">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        
        // --- SAFE JSON PARSER ---
        function parseSafeJSON(rawText) {
            try { return JSON.parse(rawText); } 
            catch(e) {
                const firstBrace = rawText.indexOf('{');
                const lastBrace = rawText.lastIndexOf('}');
                if (firstBrace !== -1 && lastBrace !== -1) {
                    return JSON.parse(rawText.substring(firstBrace, lastBrace + 1));
                }
                throw new Error("Invalid JSON Format");
            }
        }

        // --- MODAL & TOAST ---
        function showModal(title, message, isConfirm = false, onConfirm = null) {
            const modal = document.getElementById('ezModal');
            const content = document.getElementById('ezModalContent');
            document.getElementById('ezModalTitle').textContent = title;
            document.getElementById('ezModalBody').innerHTML = message.replace(/\n/g, '<br>');
            const actions = document.getElementById('ezModalActions');
            actions.innerHTML = '';
            if (isConfirm) {
                const btnCancel = document.createElement('button'); btnCancel.className = 'px-4 py-2 rounded-lg font-bold text-textSec bg-bgMain border border-borderCol hover:bg-borderCol transition-colors text-sm'; btnCancel.textContent = 'Batal'; btnCancel.onclick = closeModal; actions.appendChild(btnCancel);
                const btnOk = document.createElement('button'); btnOk.className = 'px-4 py-2 rounded-lg font-bold text-white bg-[var(--error-text)] hover:bg-red-700 transition-colors shadow-sm text-sm'; btnOk.textContent = 'Ya, Lanjutkan'; btnOk.onclick = () => { closeModal(); if(onConfirm) onConfirm(); }; actions.appendChild(btnOk);
            } else {
                const btnOk = document.createElement('button'); btnOk.className = 'px-4 py-2 rounded-lg font-bold text-white bg-accent hover:bg-accentHover transition-colors shadow-sm text-sm'; btnOk.textContent = 'OK Mengerti'; btnOk.onclick = closeModal; actions.appendChild(btnOk);
            }
            modal.classList.remove('hidden'); void modal.offsetWidth;
            modal.classList.remove('opacity-0', 'pointer-events-none'); content.classList.remove('scale-95');
        }
        function closeModal() {
            const modal = document.getElementById('ezModal'); const content = document.getElementById('ezModalContent');
            modal.classList.add('opacity-0', 'pointer-events-none'); content.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 200);
        }
        function showToast(msg, type='success') {
            const toast = document.createElement('div'); const isSuccess = type === 'success';
            toast.className = `px-5 py-3.5 rounded-xl shadow-2xl text-sm font-bold text-white transform translate-y-10 opacity-0 transition-all duration-300 flex items-center gap-3 ${isSuccess ? 'bg-green-600' : 'bg-red-600'}`;
            toast.innerHTML = isSuccess ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>${msg}</span>` : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> <span>${msg}</span>`;
            document.getElementById('toastContainer').appendChild(toast);
            requestAnimationFrame(() => toast.classList.remove('translate-y-10', 'opacity-0'));
            setTimeout(() => { toast.classList.add('opacity-0', 'scale-95'); setTimeout(() => toast.remove(), 300); }, 4000);
        }

        // --- PLATFORM DROPDOWN ---
        const pTrigger = document.getElementById('platformTrigger');
        const pMenu = document.getElementById('platformMenu');
        const pInput = document.getElementById('platformSelect');
        const pSelectedText = document.getElementById('platformSelectedText');
        const pArrow = document.getElementById('platformArrow');

        if(pTrigger) {
            pTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (pMenu.classList.contains('hidden')) { pMenu.classList.remove('hidden'); setTimeout(() => { pMenu.classList.remove('opacity-0', '-translate-y-2'); pArrow.classList.add('rotate-180'); }, 10); } 
                else { closePlatformMenu(); }
            });
            document.addEventListener('click', closePlatformMenu);
            function closePlatformMenu() { if(!pMenu) return; pMenu.classList.add('opacity-0', '-translate-y-2'); pArrow.classList.remove('rotate-180'); setTimeout(() => pMenu.classList.add('hidden'), 200); }
            document.querySelectorAll('.platform-option').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    pInput.value = e.currentTarget.dataset.value; pSelectedText.innerHTML = e.currentTarget.innerHTML; updateLivePreview();
                });
            });
        }

        // --- THEME TOGGLE ---
        const themeToggleBtn = document.getElementById('themeToggle');
        const themeLabel = document.getElementById('themeLabel');
        const themeIconContainer = document.getElementById('themeIconContainer');
        const htmlElement = document.documentElement;
        const iconSun = `<svg class="group-hover:text-amber-500 transition-colors" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Tema`;
        const iconMoon = `<svg class="group-hover:text-blue-400 transition-colors" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> Tema`;

        function updateThemeLabel() {
            if (!themeLabel) return;
            if (htmlElement.classList.contains('dark')) {
                themeLabel.textContent = 'GELAP'; themeLabel.className = 'text-xs font-bold px-2 py-1 bg-borderCol text-textMain rounded border border-transparent transition-colors'; themeIconContainer.innerHTML = iconMoon;
            } else {
                themeLabel.textContent = 'TERANG'; themeLabel.className = 'text-xs font-bold px-2 py-1 bg-bgMain text-textMain rounded border border-borderCol transition-colors'; themeIconContainer.innerHTML = iconSun;
            }
        }
        updateThemeLabel(); 
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                htmlElement.classList.toggle('dark');
                if (htmlElement.classList.contains('dark')) localStorage.setItem('theme', 'dark'); else localStorage.setItem('theme', 'light');
                updateThemeLabel();
            });
        }

        // --- FLATPICKR (WAKTU & TOMBOL ADAPTIF) ---
        let isTimeError = false;
        const iconSend = `<svg class="group-hover:translate-x-1 transition-transform" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>`;
        const iconClock = `<svg class="group-hover:rotate-12 transition-transform" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
        const iconStop = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;

        let flatpickrInstance = null;
        if (document.getElementById("scheduleWrap")) {
            flatpickrInstance = flatpickr("#scheduleWrap", {
                wrap: true, enableTime: true, time_24hr: true, dateFormat: "Y-m-d H:i", disableMobile: true, 
                onChange: function(selectedDates, dateStr, instance) {
                    const clearBtn = document.getElementById('btnClearDate');
                    const btnPublish = document.getElementById('btnPublish');
                    const btnIcon = document.getElementById('publishBtnIcon');
                    const btnText = document.getElementById('publishBtnText');

                    if (dateStr && selectedDates.length > 0) {
                        clearBtn.classList.remove('hidden');
                        const now = new Date();
                        if (selectedDates[0] <= now) {
                            isTimeError = true;
                            btnPublish.className = 'w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold shadow-md flex items-center justify-center gap-2 cursor-not-allowed';
                            btnIcon.innerHTML = iconStop; btnText.textContent = 'Time Error';
                        } else {
                            isTimeError = false;
                            btnPublish.className = 'w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all shadow-md flex items-center justify-center gap-2 group cursor-pointer';
                            btnIcon.innerHTML = iconClock; btnText.textContent = 'Publish Later';
                        }
                    } else {
                        clearBtn.classList.add('hidden'); isTimeError = false;
                        btnPublish.className = 'w-full sm:w-auto bg-accent hover:bg-accentHover text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all shadow-md flex items-center justify-center gap-2 group cursor-pointer';
                        btnIcon.innerHTML = iconSend; btnText.textContent = 'Publish';
                    }
                }
            });
            document.getElementById('btnClearDate').addEventListener('click', () => { if (flatpickrInstance) flatpickrInstance.clear(); });
        }

        // FLATPICKR EDIT MODAL
        let flatpickrEdit = null;
        if(document.getElementById('editScheduleWrap')) {
            flatpickrEdit = flatpickr("#editScheduleWrap", {
                wrap: true, enableTime: true, time_24hr: true, dateFormat: "Y-m-d H:i", disableMobile: true
            });
        }

        // --- PLANNER (CALENDAR & TIMELINE) ---
        let allPosts = [];
        let selectedDateStr = new Date().toISOString().split('T')[0];
        let currentCalDate = new Date();

        function initPlanner() {
            let ySel = document.getElementById('cal-year');
            if(!ySel) return;
            let currYr = new Date().getFullYear();
            for(let i = currYr - 1; i <= currYr + 2; i++) {
                let opt = document.createElement('option'); opt.value = i; opt.text = i; ySel.add(opt);
            }
            goToToday();
        }

        async function loadPlannerData() {
            try {
                const res = await fetch('api.php?action=get_history&_t=' + Date.now());
                const data = parseSafeJSON(await res.text());
                if (data.status === 'success') {
                    allPosts = data.data;
                    renderCalendar();
                    renderTimeline();
                }
            } catch(e) {}
        }

        function changeMonth(dir) {
            currentCalDate.setMonth(currentCalDate.getMonth() + dir);
            document.getElementById('cal-month').value = currentCalDate.getMonth();
            document.getElementById('cal-year').value = currentCalDate.getFullYear();
            renderCalendar();
        }

        function goToToday() {
            currentCalDate = new Date();
            document.getElementById('cal-month').value = currentCalDate.getMonth();
            document.getElementById('cal-year').value = currentCalDate.getFullYear();
            let y = currentCalDate.getFullYear(); let m = String(currentCalDate.getMonth() + 1).padStart(2, '0'); let d = String(currentCalDate.getDate()).padStart(2, '0');
            selectedDateStr = `${y}-${m}-${d}`;
            loadPlannerData();
        }

        function selectDate(dateStr) { selectedDateStr = dateStr; renderCalendar(); renderTimeline(); }

        function renderCalendar() {
            let year = parseInt(document.getElementById('cal-year').value);
            let month = parseInt(document.getElementById('cal-month').value);
            currentCalDate = new Date(year, month, 1);
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            let grid = document.getElementById('calendar-grid');
            if(!grid) return;
            let gridHtml = '';
            for(let i=0; i<firstDay; i++) { gridHtml += `<div></div>`; }
            
            const todayStr = new Date().toISOString().split('T')[0];
            
            for(let d=1; d<=daysInMonth; d++) {
                let loopDate = `${year}-${String(month+1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                let isToday = (loopDate === todayStr) ? 'today' : '';
                let isActive = (loopDate === selectedDateStr) ? 'active' : 'text-gray-400';
                let hasPost = allPosts.some(p => p.scheduled_date_raw === loopDate);
                let dotHtml = hasPost ? `<div class="cal-dot"></div>` : '';
                gridHtml += `<div class="cal-day ${isToday} ${isActive}" onclick="selectDate('${loopDate}')">${d}${dotHtml}</div>`;
            }
            grid.innerHTML = gridHtml;
        }

        function renderTimeline() {
            const container = document.getElementById('timeline-container');
            let fStat = document.getElementById('flt-status').value;
            let fPlat = document.getElementById('flt-platform').value;
            
            let dObj = new Date(selectedDateStr);
            document.getElementById('timeline-header').innerHTML = `Jadwal: ${dObj.toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'})}`;

            let filtered = allPosts.filter(p => {
                return (p.scheduled_date_raw === selectedDateStr) && (fStat === 'all' || p.status === fStat) && (fPlat === 'all' || p.platform === fPlat);
            });

            if(filtered.length === 0) {
                container.innerHTML = `<div class="text-center py-10 bg-darkcard rounded-xl border border-darkborder text-textSec">Tidak ada aktivitas pada tanggal ini.</div>`;
                return;
            }

            let html = '';
            filtered.forEach(post => {
                let bInfo = { cl: 'bg-gray-800 text-gray-300', text: 'UNKNOWN' };
                if (post.status === 'published') bInfo = { cl: 'bg-emerald-900/30 text-emerald-400 border border-emerald-800/50', text: 'SUKSES' };
                else if (post.status === 'failed') bInfo = { cl: 'bg-red-900/30 text-red-400 border border-red-800/50', text: 'GAGAL' };
                else if (post.status === 'scheduled') bInfo = { cl: 'bg-amber-900/30 text-amber-400 border border-amber-800/50', text: 'ANTREAN' };

                let pIcon = post.platform === 'facebook' ? '<svg class="text-[#1877F2]" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>' : '<svg class="text-textMain" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg>';
                
                let errHtml = post.error_log ? `<div class="mt-3 p-3 text-xs bg-red-900/20 border border-red-800/30 text-red-300 rounded font-mono break-words">${post.error_log}</div>` : '';
                
                let snippet = post.content;
                if(post.platform === 'threads') { try { snippet = JSON.parse(post.content).join(' '); } catch(e){} }
                if(!snippet) snippet = '[Lampiran Media]';

                let actions = '';
                if(post.status === 'scheduled' || post.status === 'failed') {
                    actions = `
                    <div class="mt-4 pt-3 border-t border-borderCol flex flex-wrap gap-2">
                        <button onclick="forcePublish(${post.id})" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold rounded shadow-sm flex items-center gap-1">🚀 Kirim</button>
                        <button onclick="openEditModal(${post.id}, '${post.scheduled_date_raw} ${post.scheduled_time_raw}')" class="px-3 py-1.5 bg-bgMain border border-borderCol hover:text-accent text-textSec text-xs font-bold rounded flex items-center gap-1">✏️ Jadwal</button>
                        <button onclick="deletePost(${post.id})" class="px-3 py-1.5 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] hover:bg-red-700 hover:text-white text-xs font-bold rounded flex items-center gap-1 ml-auto">🗑️ Hapus</button>
                    </div>`;
                }

                html += `
                <div class="bg-darkcard border border-darkborder rounded-xl p-5 hover:border-gray-600 transition-colors flex gap-4">
                    <div class="text-right flex-shrink-0 w-12">
                        <div class="text-lg font-bold text-textMain leading-none">${post.scheduled_time_raw}</div>
                    </div>
                    <div class="border-l border-borderCol pl-4 w-full">
                        <div class="flex justify-between items-start mb-2">
                            <span class="px-2 py-0.5 text-[10px] font-bold rounded ${bInfo.cl}">${bInfo.text}</span>
                            ${pIcon}
                        </div>
                        <p class="text-sm text-gray-300 whitespace-pre-wrap leading-relaxed">${snippet}</p>
                        ${errHtml}
                        ${actions}
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }

        window.forcePublish = async function(id) {
            const formData = new FormData(); formData.append('csrf_token', csrfToken); formData.append('post_id', id); formData.append('action', 'force_publish');
            try {
                showToast("Memproses pengiriman...");
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = parseSafeJSON(await res.text());
                if(data.status === 'success') {
                    fetch('cron.php?secret=EZPost1995').finally(() => { showToast("Sukses terkirim!"); loadPlannerData(); });
                } else showModal('Gagal', data.message);
            } catch(e) { showModal('Error', 'Gagal menghubungi server.'); }
        }

        window.deletePost = function(id) {
            showModal('Hapus Post?', 'Apakah Anda yakin ingin menghapus post ini dari database?', true, async () => {
                const formData = new FormData(); formData.append('csrf_token', csrfToken); formData.append('id', id); formData.append('action', 'delete_post');
                const res = await fetch('api.php', { method: 'POST', body: formData });
                const data = parseSafeJSON(await res.text());
                if(data.status === 'success') { showToast("Dihapus."); loadPlannerData(); }
            });
        }

        window.openEditModal = function(id, datetimeStr) {
            document.getElementById('edit_post_id').val = id;
            if(flatpickrEdit) flatpickrEdit.setDate(datetimeStr);
            const modal = document.getElementById('editModal'); const content = document.getElementById('editModalContent');
            modal.classList.remove('hidden'); void modal.offsetWidth;
            modal.classList.remove('opacity-0'); content.classList.remove('scale-95');
        }
        
        window.closeEditModal = function() {
            const modal = document.getElementById('editModal'); const content = document.getElementById('editModalContent');
            modal.classList.add('opacity-0'); content.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        window.submitEditSchedule = async function() {
            const id = document.getElementById('edit_post_id').val;
            const dt = document.getElementById('edit_datetime').value;
            if(!dt) return alert("Pilih waktu baru!");
            
            const formData = new FormData(); formData.append('csrf_token', csrfToken); formData.append('action', 'edit_schedule');
            formData.append('id', id); formData.append('datetime', dt);
            
            const res = await fetch('api.php', { method: 'POST', body: formData });
            const data = parseSafeJSON(await res.text());
            if(data.status === 'success') { showToast("Jadwal diubah!"); closeEditModal(); loadPlannerData(); }
            else showModal("Gagal", data.message);
        }


        // --- DRAFT PROTECTION & MEDIA UPLOAD ---
        const textarea = document.getElementById('postContent');
        const livePreviewArea = document.getElementById('threadsPreview');
        const btnResetDraft = document.getElementById('btnResetDraft');
        let selectedFiles = [];

        function checkDraftState() {
            if (textarea && (textarea.value.trim().length > 0 || selectedFiles.length > 0)) {
                btnResetDraft.classList.remove('hidden');
            } else if (btnResetDraft) { btnResetDraft.classList.add('hidden'); }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (textarea) {
                const savedDraft = localStorage.getItem('ezpost_draft');
                if (savedDraft) { textarea.value = savedDraft; updateLivePreview(); checkDraftState(); }
            }
            if(document.getElementById('view-scheduled')) initPlanner();
        });

        if (textarea) {
            textarea.addEventListener('input', () => {
                localStorage.setItem('ezpost_draft', textarea.value);
                updateLivePreview(); checkDraftState();
            });
        }

        if (btnResetDraft) {
            btnResetDraft.addEventListener('click', () => {
                showModal('Reset Draft?', 'Yakin buang semua tulisan?', true, () => {
                    textarea.value = ''; localStorage.removeItem('ezpost_draft'); selectedFiles = [];
                    renderPreviews(); updateLivePreview(); checkDraftState(); showToast('Draft dibersihkan.');
                    if(flatpickrInstance) flatpickrInstance.clear();
                });
            });
        }

        const mediaInput = document.getElementById('mediaUpload');
        const previewContainer = document.getElementById('mediaPreviewContainer');
        const mediaCount = document.getElementById('mediaCount');

        if (mediaInput) {
            mediaInput.addEventListener('change', (e) => {
                Array.from(e.target.files).forEach(file => { selectedFiles.push(file); });
                mediaInput.value = ''; renderPreviews(); updateLivePreview(); checkDraftState();
            });
        }

        function renderPreviews() {
            previewContainer.innerHTML = '';
            if (selectedFiles.length > 0) {
                mediaCount.textContent = `${selectedFiles.length} Terlampir`; mediaCount.classList.remove('hidden');
            } else { mediaCount.classList.add('hidden'); }

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'relative w-20 h-20 rounded-lg overflow-hidden border border-borderCol group shadow-sm bg-bgMain';
                    let mediaEl = file.type.startsWith('video') 
                        ? `<video src="${e.target.result}" class="w-full h-full object-cover"></video><div class="absolute inset-0 bg-black/50 flex items-center justify-center text-white text-xs font-bold"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div>`
                        : `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    div.innerHTML = `${mediaEl}
                        <button type="button" onclick="removeMedia(${index})" class="absolute top-1 right-1 w-6 h-6 bg-[var(--error-text)] hover:bg-red-700 text-white font-bold rounded-md flex items-center justify-center text-xs opacity-100 md:opacity-0 group-hover:opacity-100 transition-opacity z-10 shadow-sm border border-red-900" title="Hapus">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>`;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        window.removeMedia = function(index) {
            selectedFiles.splice(index, 1); renderPreviews(); updateLivePreview(); checkDraftState();
        }

        // --- PREVIEW EDITOR ---
        function updateLivePreview() {
            if(!textarea) return;
            const text = textarea.value;
            const platform = document.getElementById('platformSelect').value;
            
            if (text.length === 0 && selectedFiles.length === 0) {
                livePreviewArea.innerHTML = '<div class="text-sm text-textSec italic text-center py-10 border border-dashed border-borderCol rounded-lg flex flex-col items-center gap-2"><svg class="opacity-50" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Ketik sesuatu atau tambahkan media...</div>';
                return;
            }

            // --- TAB BUILDER UNTUK MULTI PLATFORM ---
            let tabsHtml = '';
            let isFbActive = (platform === 'facebook' || platform.startsWith('fb_threads'));
            let isThActive = (platform === 'threads' || platform.startsWith('fb_threads'));
            
            if(isFbActive) tabsHtml += `<button class="prev-tab-btn px-4 py-2 font-bold text-sm border-b-2 ${isFbActive ? 'border-blue-500 text-blue-500' : 'border-transparent text-textSec'} flex items-center gap-2" onclick="showPrevTab('fb')"><svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg> Facebook</button>`;
            if(isThActive) tabsHtml += `<button class="prev-tab-btn px-4 py-2 font-bold text-sm border-b-2 ${!isFbActive ? 'border-white text-textMain' : 'border-transparent text-textSec'} flex items-center gap-2" onclick="showPrevTab('th')"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg> Threads</button>`;
            
            let htmlOut = `<div class="bg-bgMain px-2 pt-2 flex gap-1 border-b border-borderCol">${tabsHtml}</div><div class="p-4 flex-1 overflow-y-auto bg-panel" id="previewContentContainer">`;

            let attachHtml = selectedFiles.length > 0 ? `<div class="mt-3 inline-flex items-center gap-1.5 bg-borderCol px-3 py-1.5 rounded-lg text-xs text-textMain border border-borderCol font-bold shadow-sm"><svg class="text-accent" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> ${selectedFiles.length} Media Terlampir</div>` : '';
            let fbAttach = attachHtml;
            let thAttach = (platform === 'threads' || platform === 'fb_threads_media') ? attachHtml : '';

            // Render FB Preview
            htmlOut += `<div id="prev-fb" class="prev-tab-content ${isFbActive ? 'block' : 'hidden'}">
                <div class="bg-panel rounded-xl text-sm">
                    <div class="flex items-center gap-3 mb-3 border-b border-borderCol pb-3">
                        <div class="w-8 h-8 rounded border border-accent bg-accent/10 flex items-center justify-center text-[#1877F2]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </div>
                        <div><div class="font-bold text-textMain text-xs uppercase tracking-wide">Halaman FB</div><div class="text-[10px] text-textSec mt-0.5 flex items-center gap-1">Baru saja <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line></svg></div></div>
                    </div>
                    <div class="text-textMain leading-relaxed whitespace-pre-wrap">${text}</div>
                    ${fbAttach}
                </div></div>`;

            // Render TH Preview
            htmlOut += `<div id="prev-th" class="prev-tab-content ${!isFbActive ? 'block' : 'hidden'}">`;
            const threads = splitThreads(text);
            if (threads.length === 0 && selectedFiles.length > 0 && thAttach !== '') threads.push(""); 
            
            threads.forEach((thread, index) => {
                let mediaDisplay = (index === 0) ? thAttach : '';
                htmlOut += `
                    <div class="bg-panel p-4 rounded-xl text-sm relative ml-5 thread-divider">
                        <div class="absolute -left-[1.35rem] top-4 bg-panel w-5 h-5 rounded-full border border-borderCol flex items-center justify-center text-[10px] font-bold text-textSec z-10">${index + 1}</div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-6 h-6 rounded-full border border-borderCol bg-bgMain flex items-center justify-center text-textMain"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                            <span class="font-bold text-textMain text-xs">Akun Anda</span>
                        </div>
                        <div class="text-textMain leading-relaxed whitespace-pre-wrap">${thread}</div>
                        ${mediaDisplay}
                        <div class="mt-4 pt-3 border-t border-borderCol flex gap-4 text-textSec text-[10px] font-bold">
                            <span class="flex items-center gap-1.5"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> 0</span> 
                            <span class="flex items-center gap-1.5"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 2.1l4 4-4 4"/><path d="M3 12.2v-2a4 4 0 0 1 4-4h13.8L17 2.1"/><path d="M7 21.9l-4-4 4-4"/><path d="M21 11.8v2a4 4 0 0 1-4 4H3.2l3.8 3.9"/></svg> 0</span> 
                            <span class="flex items-center gap-1.5"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg> 0</span>
                        </div>
                    </div>`;
            });
            htmlOut += `</div></div>`;
            livePreviewArea.innerHTML = htmlOut;
        };

        window.showPrevTab = function(target) {
            $('.prev-tab-content').addClass('hidden');
            $(`#prev-${target}`).removeClass('hidden');
            $('.prev-tab-btn').removeClass('border-blue-500 border-white text-blue-500 text-textMain').addClass('border-transparent text-textSec');
            if(event && event.currentTarget) {
                event.currentTarget.classList.remove('border-transparent', 'text-textSec');
                if(target==='fb') event.currentTarget.classList.add('border-blue-500', 'text-blue-500');
                else event.currentTarget.classList.add('border-white', 'text-textMain');
            }
        }

        if (platformSelect) platformSelect.addEventListener('change', updateLivePreview);

        // --- SUBMIT API (DENGAN PROGRESS BAR) ---
        async function submitPost() {
            if (isTimeError) return showModal('Informasi', 'Lengkapi atau perbaiki Waktu (Tanggal & Jam) jika ingin menjadwalkan post.');

            const content = textarea.value.trim();
            const platform = document.getElementById('platformSelect').value;
            const scheduledAt = document.getElementById('scheduleTime').value;
            let isScheduled = scheduledAt !== '';

            if (!content && selectedFiles.length === 0) return showModal('Informasi', 'Konten tulisan atau media tidak boleh kosong!');

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('platform', platform);
            formData.append('content', platform === 'threads' ? JSON.stringify(splitThreads(content)) : content);
            if (isScheduled) formData.append('scheduled_at', scheduledAt + ':00');
            selectedFiles.forEach(file => formData.append('media[]', file));

            const btn = document.getElementById('btnPublish');
            const oriHtml = btn.innerHTML; const oriClasses = btn.className;

            try {
                btn.className = 'w-full sm:w-auto bg-gray-500 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold shadow-md flex items-center justify-center gap-2 cursor-not-allowed opacity-80 transition-all';
                btn.disabled = true;
                btn.innerHTML = `<span class="flex items-center z-10"><svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></span><span id="publishBtnText" class="z-10 tracking-wider">Memproses...</span>`;

                const res = await fetch('api.php?action=save_post&_t=' + Date.now(), { method: 'POST', body: formData });
                const rawText = await res.text();
                
                let data;
                try { data = parseSafeJSON(rawText); } 
                catch (parseError) { throw new Error('Server PHP memunculkan Error rahasia.'); }

                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    textarea.value = ''; localStorage.removeItem('ezpost_draft'); selectedFiles = []; 
                    document.getElementById('scheduleTime').value = '';
                    if(flatpickrInstance) flatpickrInstance.clear();
                    renderPreviews(); updateLivePreview(); checkDraftState();
                } else {
                    showModal('Ditolak Meta', data.message);
                }
            } catch (err) {
                showModal('Koneksi Putus', 'Server memutus sambungan (Timeout).');
            } finally {
                btn.innerHTML = oriHtml; btn.className = oriClasses; btn.disabled = false;
            }
        }

        document.getElementById('btnPublish')?.addEventListener('click', submitPost);

        // --- SISTEM DINAMIS: CEK STATUS AKUN DB ---
        async function checkAccountStatus() {
            const elFb = document.getElementById('statusFb');
            const elTh = document.getElementById('statusThreads');
            if(!elFb || !elTh) return;

            try {
                const res = await fetch('api.php?action=check_accounts&_t=' + Date.now());
                const data = parseSafeJSON(await res.text());
                const okHtml = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> OK`;
                const failHtml = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Belum Terhubung`;

                if(data.status === 'success') {
                    const accounts = data.data;
                    elFb.innerHTML = accounts.includes('facebook') ? okHtml : failHtml;
                    elFb.className = accounts.includes('facebook') ? 'px-2 py-1 border border-[var(--success-text)] text-[var(--success-text)] bg-[var(--success-bg)] text-xs font-bold rounded flex items-center gap-1' : 'px-2 py-1 border border-[var(--error-text)] text-[var(--error-text)] bg-[var(--error-bg)] text-xs font-bold rounded flex items-center gap-1';
                    
                    elTh.innerHTML = accounts.includes('threads') ? okHtml : failHtml;
                    elTh.className = accounts.includes('threads') ? 'px-2 py-1 border border-[var(--success-text)] text-[var(--success-text)] bg-[var(--success-bg)] text-xs font-bold rounded flex items-center gap-1' : 'px-2 py-1 border border-[var(--error-text)] text-[var(--error-text)] bg-[var(--error-bg)] text-xs font-bold rounded flex items-center gap-1';
                }
            } catch(e) {}
        }

        // --- SISTEM DINAMIS: GALERI MEDIA ---
        let mediaItems = [];
        let selectedMediaIds = new Set();
        
        async function loadMediaGallery() {
            const container = document.getElementById('mediaContainer');
            if(!container) return;
            container.innerHTML = '<div class="text-center py-12"><svg class="animate-spin inline w-8 h-8 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></div>';
            
            try {
                const res = await fetch('api.php?action=get_media&_t=' + Date.now());
                const data = parseSafeJSON(await res.text());
                
                if (data.status === 'success') {
                    mediaItems = data.data; renderMediaGallery();
                }
            } catch(e) {}
        }

        function formatBytes(bytes) {
            if(bytes === 0) return '0 Bytes'; const k = 1024, dm = 2, sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function renderMediaGallery() {
            const container = document.getElementById('mediaContainer');
            const sortMode = document.getElementById('mediaSortSelect').value;
            if (mediaItems.length === 0) { container.innerHTML = '<div class="bg-panel border border-borderCol rounded-xl p-12 text-center text-textSec"><p>Galeri server Anda kosong.</p></div>'; return; }

            let sorted = [...mediaItems];
            if (sortMode === 'size') sorted.sort((a, b) => b.size - a.size);
            else sorted.sort((a, b) => b.date - a.date);

            let html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">';
            let lastDateStr = '';
            
            sorted.forEach(item => {
                if (sortMode === 'date') {
                    const dateStr = new Date(item.date * 1000).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
                    if (dateStr !== lastDateStr) {
                        html += `</div><div class="flex items-center my-6"><div class="h-px bg-borderCol flex-1"></div><span class="px-4 text-xs font-bold text-textSec uppercase tracking-widest">${dateStr}</span><div class="h-px bg-borderCol flex-1"></div></div><div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">`;
                        lastDateStr = dateStr;
                    }
                }
                const isSelected = selectedMediaIds.has(item.name);
                const protectionBadge = item.is_protected ? '<div class="absolute top-2 left-2 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow flex items-center gap-1 z-20"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Kunci</div>' : '';
                let visual = item.is_video ? `<video src="${item.url}" class="w-full h-32 object-cover"></video><div class="absolute inset-0 bg-black/40 flex items-center justify-center text-white"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div>` : `<img src="${item.url}" class="w-full h-32 object-cover">`;

                html += `
                <div class="relative rounded-xl overflow-hidden border-2 transition-all cursor-pointer group ${isSelected ? 'border-accent shadow-md scale-[0.98]' : 'border-borderCol hover:border-textSec'} bg-bgMain" onclick="toggleSelectMedia('${item.name}')">
                    ${protectionBadge}
                    <div class="absolute top-2 right-2 w-5 h-5 rounded border ${isSelected ? 'bg-accent border-accent text-white' : 'bg-black/30 border-white/50 text-transparent'} flex items-center justify-center transition-colors z-20"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg></div>
                    ${visual}
                    <div class="p-2 bg-panel text-xs text-textMain flex justify-between items-center border-t border-borderCol">
                        <span class="truncate font-medium w-3/4">${formatBytes(item.size)}</span><span class="text-textSec font-bold">${item.is_video ? 'MP4' : 'IMG'}</span>
                    </div>
                </div>`;
            });
            html += '</div>'; container.innerHTML = html; updateMediaActionState();
        }

        window.toggleSelectMedia = function(name) {
            if (selectedMediaIds.has(name)) selectedMediaIds.delete(name); else selectedMediaIds.add(name);
            renderMediaGallery();
        }

        document.getElementById('mediaSortSelect')?.addEventListener('change', renderMediaGallery);
        document.getElementById('btnSelectAllMedia')?.addEventListener('click', () => {
            if (selectedMediaIds.size === mediaItems.length) selectedMediaIds.clear();
            else mediaItems.forEach(i => selectedMediaIds.add(i.name));
            renderMediaGallery();
        });

        function updateMediaActionState() {
            const btn = document.getElementById('btnDeleteSelectedMedia');
            if(!btn) return;
            document.getElementById('selectedMediaCount').textContent = selectedMediaIds.size;
            if(selectedMediaIds.size > 0) btn.classList.remove('hidden'); else btn.classList.add('hidden');
        }

        document.getElementById('btnDeleteSelectedMedia')?.addEventListener('click', () => {
            if(selectedMediaIds.size === 0) return;
            showModal('Hapus File Terpilih?', `Hapus ${selectedMediaIds.size} file ini? (File di jadwal akan dilewati)`, true, async () => {
                const formData = new FormData(); formData.append('csrf_token', csrfToken); formData.append('files', JSON.stringify(Array.from(selectedMediaIds)));
                try {
                    const res = await fetch('api.php?action=delete_media', { method: 'POST', body: formData });
                    const data = parseSafeJSON(await res.text());
                    if(data.status === 'success') { showToast(data.message); selectedMediaIds.clear(); loadMediaGallery(); } 
                    else showModal('Gagal', data.message);
                } catch(e) { showModal('Error', 'Gagal memproses penghapusan.'); }
            });
        });
    </script>
</body>
</html>