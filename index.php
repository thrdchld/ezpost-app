<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZPost - Dashboard</title>
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token']; ?>">
    
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        bgMain: 'var(--bg-main)',
                        panel: 'var(--panel)',
                        borderCol: 'var(--border-col)',
                        textMain: 'var(--text-main)',
                        textSec: 'var(--text-sec)',
                        accent: 'var(--accent)',
                        accentHover: 'var(--accent-hover)'
                    }
                }
            }
        }
    </script>
    <style>
        :root {
            color-scheme: light;
            --bg-main: #F3F4F6;
            --panel: #FFFFFF;
            --border-col: #D1D5DB;
            --text-main: #111827;
            --text-sec: #4B5563;
            --accent: #2563EB;
            --accent-hover: #1D4ED8;
            --error-bg: #FEE2E2;
            --error-text: #B91C1C;
        }
        
        .dark {
            color-scheme: dark;
            --bg-main: #111827;
            --panel: #1F2937;
            --border-col: #374151;
            --text-main: #F9FAFB;
            --text-sec: #9CA3AF;
            --accent: #3B82F6;
            --accent-hover: #60A5FA;
            --error-bg: rgba(153, 27, 27, 0.2);
            --error-text: #F87171;
        }

        body { background-color: var(--bg-main); color: var(--text-main); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .thread-divider { position: relative; }
        .thread-divider::before { content: ''; position: absolute; left: -1rem; top: 0; bottom: 0; width: 2px; background-color: var(--border-col); }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: var(--border-col); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }
        textarea:focus, input:focus, select:focus, button:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }

        input[type="date"]::-webkit-calendar-picker-indicator, input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer; opacity: 0.6; filter: invert(0.5);
        }
        .dark input[type="date"]::-webkit-calendar-picker-indicator, .dark input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(0.8);
        }
    </style>
</head>
<body class="bg-bgMain text-textMain h-[100dvh] w-full overflow-hidden flex flex-col md:flex-row text-[15px]">

    <!-- MODAL & TOAST UI -->
    <div id="ezModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none transition-opacity duration-200 hidden">
        <div class="bg-panel border border-borderCol rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl transform scale-95 transition-transform duration-200" id="ezModalContent">
            <h3 class="text-xl font-bold text-textMain mb-2" id="ezModalTitle">Perhatian</h3>
            <p class="text-sm text-textSec mb-8 leading-relaxed" id="ezModalBody">Pesan disini</p>
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
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
            </div>
            <h1 class="text-2xl font-bold mb-2 text-center text-textMain">Masuk ke EZPost</h1>
            <form id="loginForm" class="space-y-4">
                <div>
                    <label for="email" class="block text-sm font-medium text-textSec mb-1">Email</label>
                    <input type="email" id="email" name="email" required class="w-full bg-bgMain border border-borderCol rounded-lg px-4 py-3 focus:border-accent transition-colors text-textMain">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-textSec mb-1">Password</label>
                    <input type="password" id="password" name="password" required class="w-full bg-bgMain border border-borderCol rounded-lg px-4 py-3 focus:border-accent transition-colors text-textMain">
                </div>
                <button type="submit" class="w-full bg-accent hover:bg-accentHover text-white font-semibold rounded-lg px-4 py-3 mt-6 transition-colors shadow-md flex justify-center items-center gap-2 group">
                    <svg class="transition-transform group-hover:translate-x-1" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                    Masuk
                </button>
                <div id="loginError" class="text-[var(--error-text)] bg-[var(--error-bg)] text-sm mt-3 hidden text-center p-2 rounded-lg border border-[var(--error-text)]"></div>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- MAIN DASHBOARD -->
    <header class="md:hidden flex items-center justify-between p-4 border-b border-borderCol bg-panel w-full shrink-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-accent rounded flex items-center justify-center shadow-sm text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <h1 class="text-lg font-bold text-textMain tracking-wide">EZPost</h1>
        </div>
        <button id="menuBtn" class="text-textSec hover:text-textMain p-2 border border-transparent hover:border-borderCol rounded transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
    </header>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden transition-opacity"></div>
    <aside id="sidebar" class="w-64 border-r border-borderCol bg-panel flex flex-col h-full fixed md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40 shrink-0 shadow-xl md:shadow-none">
        <div class="p-6 hidden md:flex items-center gap-3">
            <div class="w-10 h-10 bg-accent rounded-lg flex items-center justify-center shadow-md text-white">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            </div>
            <h1 class="text-xl font-bold text-textMain tracking-wide">EZPost</h1>
        </div>
        
        <nav class="flex-1 px-4 space-y-2 mt-4 md:mt-0 overflow-y-auto">
            <a href="#create" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg bg-borderCol font-medium text-textMain transition-all" data-target="create">
                <svg class="text-accent transition-colors" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                <span>Buat Post</span>
            </a>
            <a href="#scheduled" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="scheduled">
                <svg class="group-hover:text-accent transition-colors" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Jadwal & Riwayat</span>
            </a>
            <a href="#media" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="media">
                <svg class="group-hover:text-accent transition-colors" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                <span>Galeri Media</span>
            </a>
            <a href="#accounts" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="accounts">
                <svg class="group-hover:text-accent transition-colors" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span>Akun</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-borderCol space-y-2 bg-panel">
            <button id="themeToggle" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-textSec hover:bg-borderCol/50 hover:text-textMain transition-all group">
                <span class="flex items-center gap-3" id="themeIconContainer"></span>
                <span id="themeLabel" class="text-xs font-bold px-2 py-1 bg-bgMain rounded border border-borderCol transition-colors">GELAP</span>
            </button>
            <button onclick="logout()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-textSec hover:bg-[var(--error-bg)] hover:text-[var(--error-text)] transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Keluar</span>
            </button>
        </div>
    </aside>

    <main class="flex-1 min-h-0 overflow-y-auto bg-bgMain relative w-full flex flex-col">
        <!-- View: Create Post -->
        <section id="view-create" class="view-section p-4 md:p-8 max-w-5xl mx-auto w-full block">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 relative z-10">
                
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-textMain tracking-tight">Tulis Sesuatu</h2>
                    <!-- TOMBOL RESET MINI -->
                    <button id="btnResetDraft" class="hidden px-2.5 py-1.5 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] rounded-md text-xs font-bold hover:bg-red-700 hover:border-red-700 hover:text-white transition-colors flex items-center gap-1 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                        Reset
                    </button>
                </div>
                
                <div class="relative w-full sm:w-64" id="platformDropdownWrap">
                    <button type="button" id="platformTrigger" class="w-full bg-panel border border-borderCol rounded-lg px-4 py-2.5 text-textMain hover:border-accent shadow-sm flex items-center justify-between transition-colors">
                        <div class="flex items-center gap-3" id="platformSelectedText">
                            <svg class="text-textMain" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg>
                            <span class="font-medium">Threads</span>
                        </div>
                        <svg class="text-textSec transition-transform duration-200" id="platformArrow" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    
                    <div id="platformMenu" class="hidden absolute top-full left-0 mt-2 w-full bg-panel border border-borderCol rounded-lg shadow-xl overflow-hidden opacity-0 transform -translate-y-2 transition-all duration-200">
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain flex items-center gap-3 transition-colors" data-value="threads">
                            <svg class="text-textMain" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg>
                            <span class="font-medium">Threads</span>
                        </button>
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain border-t border-borderCol flex items-center gap-3 transition-colors" data-value="facebook">
                            <svg class="text-[#1877F2]" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                            <span class="font-medium">Facebook</span>
                        </button>
                    </div>
                    <input type="hidden" id="platformSelect" value="threads">
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Text Area -->
                <div class="flex-1 bg-panel border border-borderCol rounded-xl p-5 shadow-sm flex flex-col relative z-0">
                    <textarea id="postContent" class="w-full bg-transparent resize-none flex-1 min-h-[200px] lg:min-h-[150px] text-textMain placeholder-textSec border-none p-0 focus:ring-0 leading-relaxed text-base" placeholder="Apa yang ingin Anda bagikan hari ini?"></textarea>
                    
                    <div id="mediaPreviewContainer" class="flex flex-wrap gap-3 mt-4 empty:hidden"></div>

                    <div class="mt-4 border-t border-borderCol pt-4 flex items-center justify-between">
                        <label class="cursor-pointer text-textSec hover:text-accent transition-colors flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-bgMain border border-transparent hover:border-borderCol group">
                            <svg class="group-hover:scale-110 transition-transform" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                            <span class="font-medium text-sm">Lampirkan Media</span>
                            <input type="file" id="mediaUpload" multiple accept="image/*,video/mp4" class="hidden">
                        </label>
                        <span id="mediaCount" class="text-xs font-bold text-textMain bg-borderCol px-2 py-1 rounded hidden shadow-inner"></span>
                    </div>
                </div>

                <!-- Preview Panel -->
                <div class="w-full lg:w-96 bg-panel border border-borderCol rounded-xl p-5 shadow-sm h-fit">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.8)]"></div>
                        <h3 class="text-xs font-bold text-textSec uppercase tracking-wider">PREVIEW LIVE</h3>
                    </div>
                    <div id="threadsPreview" class="space-y-4">
                        <div class="text-sm text-textSec italic text-center py-10 border border-dashed border-borderCol rounded-lg flex flex-col items-center gap-2">
                            <svg class="opacity-50" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            Ketik sesuatu di editor...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-panel p-5 rounded-xl border border-borderCol shadow-sm">
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto relative">
                    <label class="text-sm font-bold text-textSec shrink-0">Waktu (Opsional):</label>
                    
                    <!-- UI TANGGAL & JAM -->
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="date" id="scheduleDate" class="w-full sm:w-[160px] bg-bgMain border border-borderCol rounded-lg px-3 py-2.5 text-sm focus:border-accent text-textMain shadow-inner transition-colors outline-none uppercase tracking-wider" onkeydown="return false">
                        <input type="time" id="scheduleTimeHour" class="w-full sm:w-[110px] bg-bgMain border border-borderCol rounded-lg px-3 py-2.5 text-sm focus:border-accent text-textMain shadow-inner transition-colors outline-none tracking-wider" onkeydown="return false">
                        
                        <button type="button" id="btnClearDate" class="hidden text-[var(--error-text)] hover:text-red-700 p-2 rounded transition-colors bg-[var(--error-bg)] border border-[var(--error-text)] ml-1 shrink-0" title="Batal Jadwal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                </div>

                <div class="flex w-full sm:w-auto mt-2 sm:mt-0">
                    <button id="btnPublish" class="w-full sm:w-auto bg-accent hover:bg-accentHover text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all duration-300 shadow-md flex items-center justify-center gap-2 group cursor-pointer relative overflow-hidden">
                        <span id="publishBtnIcon" class="flex items-center z-10">
                            <svg class="group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        </span>
                        <span id="publishBtnText" class="z-10">Publish</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- View: Scheduled & Riwayat -->
        <section id="view-scheduled" class="view-section p-4 md:p-8 w-full max-w-5xl mx-auto hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Jadwal & Riwayat</h2>
                <button onclick="loadScheduledPosts()" class="p-2 text-textSec hover:text-accent transition-colors bg-panel rounded-lg border border-borderCol shadow-sm flex items-center gap-2 text-sm font-bold" title="Refresh Data">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Refresh
                </button>
            </div>
            
            <div id="scheduledContainer" class="w-full">
                <!-- Data Jadwal & Riwayat Semua Postingan -->
            </div>
        </section>

        <!-- View: Galeri Media -->
        <section id="view-media" class="view-section p-4 md:p-8 w-full max-w-5xl mx-auto hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Galeri Media</h2>
                <div class="flex items-center gap-3 flex-wrap">
                    <select id="mediaSortSelect" class="bg-panel border border-borderCol text-textMain rounded-lg px-3 py-2 text-sm font-bold shadow-sm focus:border-accent outline-none cursor-pointer">
                        <option value="date">Urutkan: Tanggal Upload</option>
                        <option value="size">Urutkan: Ukuran File</option>
                    </select>
                    <button id="btnSelectAllMedia" class="px-3 py-2 text-textSec bg-bgMain border border-borderCol hover:border-accent hover:text-accent transition-colors rounded-lg text-sm font-bold shadow-sm">
                        Pilih Semua
                    </button>
                    <button id="btnDeleteSelectedMedia" class="hidden px-3 py-2 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] hover:bg-red-700 hover:text-white transition-colors rounded-lg text-sm font-bold shadow-sm flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"></path><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"></path><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"></path></svg>
                        Hapus Terpilih (<span id="selectedMediaCount">0</span>)
                    </button>
                </div>
            </div>
            <div id="mediaContainer" class="w-full"></div>
        </section>

        <!-- View: Accounts -->
        <section id="view-accounts" class="view-section p-4 md:p-8 w-full max-w-5xl mx-auto hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Status API & Akun</h2>
                <button onclick="checkAccountStatus()" class="p-2 text-textSec hover:text-accent transition-colors bg-panel rounded-lg border border-borderCol shadow-sm flex items-center gap-2 text-sm font-bold" title="Cek Ulang Status">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                    Cek Status
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-panel border border-borderCol rounded-xl p-6 shadow-sm group hover:border-accent transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg flex items-center gap-3">
                            <div class="bg-[#1877F2]/10 p-2 rounded-lg text-[#1877F2]"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg></div>
                            Facebook
                        </h3>
                        <span id="statusFb" class="px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded flex items-center gap-1">Memeriksa...</span>
                    </div>
                </div>
                <div class="bg-panel border border-borderCol rounded-xl p-6 shadow-sm group hover:border-accent transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg flex items-center gap-3">
                            <div class="bg-borderCol/50 p-2 rounded-lg text-textMain"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0v-1.5a9 9 0 1 0 -5.5 8.28"/></svg></div>
                            Threads
                        </h3>
                        <span id="statusThreads" class="px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded flex items-center gap-1">Memeriksa...</span>
                    </div>
                </div>
            </div>
        </section>
        <div class="h-32 w-full shrink-0 md:hidden block border-t border-transparent"></div>
    </main>
    <?php endif; ?>

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

        // --- SISTEM MODAL & TOAST ---
        function showModal(title, message, isConfirm = false, onConfirm = null) {
            const modal = document.getElementById('ezModal');
            const content = document.getElementById('ezModalContent');
            document.getElementById('ezModalTitle').textContent = title;
            document.getElementById('ezModalBody').innerHTML = message.replace(/\n/g, '<br>');
            
            const actions = document.getElementById('ezModalActions');
            actions.innerHTML = '';
            
            if (isConfirm) {
                const btnCancel = document.createElement('button');
                btnCancel.className = 'px-5 py-2.5 rounded-lg font-bold text-textSec bg-bgMain border border-borderCol hover:bg-borderCol transition-colors text-sm';
                btnCancel.textContent = 'Batal';
                btnCancel.onclick = closeModal;
                actions.appendChild(btnCancel);
                
                const btnOk = document.createElement('button');
                btnOk.className = 'px-5 py-2.5 rounded-lg font-bold text-white bg-[var(--error-text)] hover:bg-red-700 transition-colors shadow-sm text-sm';
                btnOk.textContent = 'Ya, Lanjutkan';
                btnOk.onclick = () => { closeModal(); if(onConfirm) onConfirm(); };
                actions.appendChild(btnOk);
            } else {
                const btnOk = document.createElement('button');
                btnOk.className = 'px-5 py-2.5 rounded-lg font-bold text-white bg-accent hover:bg-accentHover transition-colors shadow-sm text-sm';
                btnOk.textContent = 'OK Mengerti';
                btnOk.onclick = closeModal;
                actions.appendChild(btnOk);
            }

            modal.classList.remove('hidden');
            void modal.offsetWidth; // trigger reflow
            modal.classList.remove('opacity-0', 'pointer-events-none');
            content.classList.remove('scale-95');
        }

        function closeModal() {
            const modal = document.getElementById('ezModal');
            const content = document.getElementById('ezModalContent');
            modal.classList.add('opacity-0', 'pointer-events-none');
            content.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 200);
        }

        function showToast(msg, type='success') {
            const toast = document.createElement('div');
            const isSuccess = type === 'success';
            toast.className = `px-5 py-3.5 rounded-xl shadow-2xl text-sm font-bold text-white transform translate-y-10 opacity-0 transition-all duration-300 flex items-center gap-3 ${isSuccess ? 'bg-green-600' : 'bg-red-600'}`;
            toast.innerHTML = isSuccess 
                ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> <span>${msg}</span>` 
                : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> <span>${msg}</span>`;
            document.getElementById('toastContainer').appendChild(toast);
            
            requestAnimationFrame(() => toast.classList.remove('translate-y-10', 'opacity-0'));
            setTimeout(() => {
                toast.classList.add('opacity-0', 'scale-95');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // --- PLATFORM SELECTOR ---
        const pTrigger = document.getElementById('platformTrigger');
        const pMenu = document.getElementById('platformMenu');
        const pInput = document.getElementById('platformSelect');
        const pSelectedText = document.getElementById('platformSelectedText');
        const pArrow = document.getElementById('platformArrow');

        if(pTrigger) {
            pTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                if (pMenu.classList.contains('hidden')) {
                    pMenu.classList.remove('hidden');
                    setTimeout(() => { pMenu.classList.remove('opacity-0', '-translate-y-2'); pArrow.classList.add('rotate-180'); }, 10);
                } else { closePlatformMenu(); }
            });

            document.addEventListener('click', closePlatformMenu);

            function closePlatformMenu() {
                if(!pMenu) return;
                pMenu.classList.add('opacity-0', '-translate-y-2');
                pArrow.classList.remove('rotate-180');
                setTimeout(() => pMenu.classList.add('hidden'), 200);
            }

            document.querySelectorAll('.platform-option').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    pInput.value = e.currentTarget.dataset.value;
                    pSelectedText.innerHTML = e.currentTarget.innerHTML;
                    updateLivePreview();
                });
            });
        }

        // --- THEME TOGGLE ---
        const themeToggleBtn = document.getElementById('themeToggle');
        const themeLabel = document.getElementById('themeLabel');
        const themeIconContainer = document.getElementById('themeIconContainer');
        const htmlElement = document.documentElement;

        const iconSun = `<svg class="group-hover:text-amber-500 transition-colors" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg> Tema`;
        const iconMoon = `<svg class="group-hover:text-blue-400 transition-colors" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> Tema`;

        function updateThemeLabel() {
            if (!themeLabel) return;
            if (htmlElement.classList.contains('dark')) {
                themeLabel.textContent = 'GELAP';
                themeLabel.className = 'text-xs font-bold px-2 py-1 bg-borderCol text-textMain rounded border border-transparent transition-colors';
                themeIconContainer.innerHTML = iconMoon;
            } else {
                themeLabel.textContent = 'TERANG';
                themeLabel.className = 'text-xs font-bold px-2 py-1 bg-bgMain text-textMain rounded border border-borderCol transition-colors';
                themeIconContainer.innerHTML = iconSun;
            }
        }
        updateThemeLabel(); 

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                htmlElement.classList.toggle('dark');
                if (htmlElement.classList.contains('dark')) localStorage.setItem('theme', 'dark');
                else localStorage.setItem('theme', 'light');
                updateThemeLabel();
            });
        }
        
        // --- SMART BUTTON & WAKTU ---
        let isTimeError = false;
        const iconSend = `<svg class="group-hover:translate-x-1 transition-transform" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>`;
        const iconClock = `<svg class="group-hover:rotate-12 transition-transform" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`;
        const iconStop = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="7.86 2 16.14 2 22 7.86 22 16.14 16.14 22 7.86 22 2 16.14 2 7.86 7.86 2"></polygon><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;

        const dateInput = document.getElementById('scheduleDate');
        const timeInput = document.getElementById('scheduleTimeHour');
        const clearBtn = document.getElementById('btnClearDate');
        const btnPublish = document.getElementById('btnPublish');
        const btnIcon = document.getElementById('publishBtnIcon');
        const btnText = document.getElementById('publishBtnText');

        function checkScheduleState() {
            if(!dateInput || !timeInput) return;
            const hasDate = dateInput.value !== '';
            const hasTime = timeInput.value !== '';

            if (hasDate || hasTime) {
                clearBtn.classList.remove('hidden');
                
                if (hasDate && hasTime) {
                    const scheduledTime = new Date(`${dateInput.value}T${timeInput.value}`);
                    const now = new Date();

                    if (scheduledTime <= now) {
                        isTimeError = true;
                        btnPublish.className = 'w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all duration-300 shadow-md flex items-center justify-center gap-2 cursor-not-allowed';
                        btnIcon.innerHTML = iconStop;
                        btnText.textContent = 'Time Error';
                    } else {
                        isTimeError = false;
                        btnPublish.className = 'w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all duration-300 shadow-md flex items-center justify-center gap-2 group cursor-pointer relative overflow-hidden';
                        btnIcon.innerHTML = iconClock;
                        btnText.textContent = 'Publish Later';
                    }
                } else {
                    isTimeError = true;
                    btnPublish.className = 'w-full sm:w-auto bg-orange-600 hover:bg-orange-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all duration-300 shadow-md flex items-center justify-center gap-2 cursor-not-allowed';
                    btnIcon.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>`;
                    btnText.textContent = 'Isi Jam!';
                }
            } else {
                clearBtn.classList.add('hidden');
                isTimeError = false;
                btnPublish.className = 'w-full sm:w-auto bg-accent hover:bg-accentHover text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all duration-300 shadow-md flex items-center justify-center gap-2 group cursor-pointer relative overflow-hidden';
                btnIcon.innerHTML = iconSend;
                btnText.textContent = 'Publish';
            }
        }

        if (dateInput && timeInput) {
            dateInput.addEventListener('input', checkScheduleState);
            timeInput.addEventListener('input', checkScheduleState);
            
            clearBtn.addEventListener('click', () => {
                dateInput.value = '';
                timeInput.value = '';
                checkScheduleState();
            });
        }

        // --- SISTEM DINAMIS: CEK STATUS AKUN DB ---
        async function checkAccountStatus() {
            const elFb = document.getElementById('statusFb');
            const elTh = document.getElementById('statusThreads');
            if(!elFb || !elTh) return;

            elFb.innerHTML = 'Memeriksa...';
            elTh.innerHTML = 'Memeriksa...';

            try {
                const res = await fetch('api.php?action=check_accounts&_t=' + Date.now());
                const data = parseSafeJSON(await res.text());
                const okHtml = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg> OK`;
                const failHtml = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Belum Terhubung`;

                if(data.status === 'success') {
                    const accounts = data.data;
                    elFb.innerHTML = accounts.includes('facebook') ? okHtml : failHtml;
                    elFb.className = accounts.includes('facebook') ? 'px-2 py-1 border border-[var(--success-text)] text-[var(--success-text)] bg-[var(--success-bg)] text-xs font-bold rounded flex items-center gap-1' : 'px-2 py-1 border border-[var(--error-text)] text-[var(--error-text)] bg-[var(--error-bg)] text-xs font-bold rounded flex items-center gap-1';
                    
                    elTh.innerHTML = accounts.includes('threads') ? okHtml : failHtml;
                    elTh.className = accounts.includes('threads') ? 'px-2 py-1 border border-[var(--success-text)] text-[var(--success-text)] bg-[var(--success-bg)] text-xs font-bold rounded flex items-center gap-1' : 'px-2 py-1 border border-[var(--error-text)] text-[var(--error-text)] bg-[var(--error-bg)] text-xs font-bold rounded flex items-center gap-1';
                }
            } catch(e) {}
        }

        // --- SISTEM DINAMIS: SEMUA RIWAYAT & JADWAL ---
        async function loadScheduledPosts() {
            const container = document.getElementById('scheduledContainer');
            if(!container) return;
            container.innerHTML = '<div class="text-center py-12"><svg class="animate-spin inline w-8 h-8 text-accent" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><p class="mt-2 text-textSec text-sm">Memuat data riwayat...</p></div>';
            try {
                const res = await fetch('api.php?action=get_posts&_t=' + Date.now());
                const data = parseSafeJSON(await res.text());
                if (data.status === 'success') {
                    const allPosts = data.data; // TAMPILKAN SEMUA TANPA FILTER
                    if (allPosts.length === 0) {
                        container.innerHTML = '<div class="bg-panel border border-borderCol rounded-xl p-12 text-center text-textSec flex flex-col items-center justify-center shadow-sm"><svg class="mb-4 text-borderCol w-16 h-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><p>Tidak ada riwayat atau jadwal saat ini.</p></div>';
                        return;
                    }
                    let html = '<div class="grid grid-cols-1 gap-4">';
                    allPosts.forEach(p => {
                        let contentSnippet = p.content;
                        if(p.platform === 'threads') {
                            try { const th = JSON.parse(p.content); contentSnippet = th.join(' '); } catch(e){}
                        }
                        contentSnippet = contentSnippet.replace(/<[^>]*>?/gm, '');
                        if(contentSnippet.length > 90) contentSnippet = contentSnippet.substring(0, 90) + '...';
                        if(!contentSnippet) contentSnippet = '[Lampiran Media]';

                        const platColor = p.platform === 'facebook' ? 'text-[#1877F2] bg-[#1877F2]/10 border-[#1877F2]/20' : 'text-textMain bg-borderCol/50 border-borderCol';
                        const platName = p.platform.charAt(0).toUpperCase() + p.platform.slice(1);
                        
                        let statusBadge = '';
                        let forceBtn = '';
                        if(p.status === 'failed') {
                            statusBadge = '<span class="px-2 py-1 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] rounded text-[10px] font-bold">GAGAL KIRIM</span>';
                            forceBtn = `<button onclick="forcePublishPost(${p.id})" class="p-2 sm:p-2.5 text-accent bg-accent/10 hover:bg-accent hover:text-white rounded-lg transition-colors border border-accent shrink-0 flex items-center gap-2 text-xs font-bold" title="Coba Kirim Ulang"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg> Kirim Ulang</button>`;
                        } else if(p.status === 'scheduled') {
                            statusBadge = '<span class="px-2 py-1 bg-purple-900/30 text-purple-400 border border-purple-900/50 rounded text-[10px] font-bold tracking-wider">ANTRIAN</span>';
                            forceBtn = `<button onclick="forcePublishPost(${p.id})" class="p-2 sm:p-2.5 text-accent bg-accent/10 hover:bg-accent hover:text-white rounded-lg transition-colors border border-accent shrink-0 flex items-center gap-2 text-xs font-bold" title="Bypass Jadwal & Kirim Sekarang"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg> Kirim Sekarang</button>`;
                        } else {
                            statusBadge = '<span class="px-2 py-1 bg-green-900/30 text-green-400 border border-green-900/50 rounded text-[10px] font-bold tracking-wider">SUKSES</span>';
                        }

                        const failReason = p.status === 'failed' && p.error_log ? `<div class="mt-2 text-xs text-[var(--error-text)] bg-[var(--error-bg)] p-2 rounded border border-[var(--error-text)]"><b>Error:</b> ${p.error_log}</div>` : '';
                        const dateText = p.status === 'scheduled' ? `Jadwal: ${p.scheduled_at}` : `Dibuat: ${p.created_at}`;

                        html += `
                        <div class="bg-panel p-5 rounded-xl border border-borderCol shadow-sm flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                            <div class="flex-1 w-full">
                                <div class="flex items-center flex-wrap gap-2 mb-2">
                                    ${statusBadge}
                                    <span class="px-2 py-1 border ${platColor} text-[10px] font-bold rounded uppercase tracking-wide">${platName}</span>
                                    <span class="text-xs text-textSec font-bold flex items-center gap-1.5 ml-auto"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> ${dateText}</span>
                                </div>
                                <p class="text-sm text-textMain leading-relaxed break-words">${contentSnippet}</p>
                                ${failReason}
                            </div>
                            <div class="flex gap-2 self-end sm:self-center">
                                ${forceBtn}
                                <button onclick="deleteScheduledPost(${p.id})" class="p-2 sm:p-2.5 text-[var(--error-text)] bg-[var(--error-bg)] hover:bg-red-700 hover:text-white rounded-lg transition-colors border border-[var(--error-text)] shrink-0" title="Hapus Riwayat">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>`;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                }
            } catch (e) {
                container.innerHTML = '<div class="bg-[var(--error-bg)] text-[var(--error-text)] p-4 rounded-lg text-center text-sm border border-[var(--error-text)]">Gagal memuat riwayat. Terjadi kesalahan saat menghubungi server.</div>';
            }
        }

        window.deleteScheduledPost = function(id) {
            showModal('Hapus Riwayat?', 'Apakah Anda yakin ingin menghapus data postingan ini dari database?', true, async () => {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('id', id);
                try {
                    const res = await fetch('api.php?action=delete_post', { method: 'POST', body: formData });
                    const data = parseSafeJSON(await res.text());
                    if(data.status === 'success') { showToast(data.message); loadScheduledPosts(); } 
                    else { showModal('Gagal', data.message); }
                } catch(e) { showModal('Error', 'Gagal menghubungi server untuk menghapus.'); }
            });
        }

        // FITUR BARU: FORCE PUBLISH
        window.forcePublishPost = function(id) {
            showModal('Kirim Sekarang?', 'Postingan ini akan diabaikan dari antrian jadwal dan langsung dikirim ke server Meta detik ini juga. Lanjutkan?', true, async () => {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('id', id);
                formData.append('action', 'force_publish');
                
                showToast('🚀 Mengirim permintaan ke server...', 'success');
                try {
                    const res = await fetch('api.php', { method: 'POST', body: formData });
                    const data = parseSafeJSON(await res.text());
                    
                    if(data.status === 'success') {
                        // Tembak cron diam-diam di background agar dieksekusi sekarang juga
                        fetch('cron.php?secret=EZPost1995')
                            .then(() => {
                                showToast('✅ Postingan sukses terkirim ke Meta!');
                                setTimeout(loadScheduledPosts, 1500); // refresh list
                            })
                            .catch(() => {
                                showToast('Jadwal sudah diubah ke Sekarang. Menunggu cron jalan.');
                                loadScheduledPosts();
                            });
                    } else {
                        showModal('Gagal', data.message);
                    }
                } catch(e) { 
                    showModal('Error', 'Gagal memproses pengiriman manual.'); 
                }
            });
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
                    mediaItems = data.data;
                    renderMediaGallery();
                }
            } catch(e) { container.innerHTML = '<div class="text-[var(--error-text)] p-4 text-center text-sm">Gagal memuat galeri media.</div>'; }
        }

        function formatBytes(bytes) {
            if(bytes === 0) return '0 Bytes';
            const k = 1024, dm = 2, sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function renderMediaGallery() {
            const container = document.getElementById('mediaContainer');
            const sortMode = document.getElementById('mediaSortSelect').value;
            
            if (mediaItems.length === 0) {
                container.innerHTML = '<div class="bg-panel border border-borderCol rounded-xl p-12 text-center text-textSec"><p>Galeri server Anda kosong.</p></div>';
                return;
            }

            let sorted = [...mediaItems];
            if (sortMode === 'size') {
                sorted.sort((a, b) => b.size - a.size);
            } else {
                sorted.sort((a, b) => b.date - a.date);
            }

            let html = '';
            let lastDateStr = '';

            html += '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">';
            
            sorted.forEach(item => {
                if (sortMode === 'date') {
                    const dateObj = new Date(item.date * 1000);
                    const dateStr = dateObj.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
                    if (dateStr !== lastDateStr) {
                        html += `</div><div class="flex items-center my-6"><div class="h-px bg-borderCol flex-1"></div><span class="px-4 text-xs font-bold text-textSec uppercase tracking-widest">${dateStr}</span><div class="h-px bg-borderCol flex-1"></div></div><div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">`;
                        lastDateStr = dateStr;
                    }
                }

                const isSelected = selectedMediaIds.has(item.name);
                const protectionBadge = item.is_protected ? '<div class="absolute top-2 left-2 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow flex items-center gap-1 z-20" title="Digunakan di Jadwal"><svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> Terkunci</div>' : '';
                
                let visual = item.is_video 
                    ? `<video src="${item.url}" class="w-full h-32 object-cover"></video><div class="absolute inset-0 bg-black/40 flex items-center justify-center text-white"><svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg></div>`
                    : `<img src="${item.url}" class="w-full h-32 object-cover">`;

                html += `
                <div class="relative rounded-xl overflow-hidden border-2 transition-all cursor-pointer group ${isSelected ? 'border-accent shadow-md scale-[0.98]' : 'border-borderCol hover:border-textSec'} bg-bgMain" onclick="toggleSelectMedia('${item.name}')">
                    ${protectionBadge}
                    <div class="absolute top-2 right-2 w-5 h-5 rounded border ${isSelected ? 'bg-accent border-accent text-white' : 'bg-black/30 border-white/50 text-transparent'} flex items-center justify-center transition-colors z-20">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    ${visual}
                    <div class="p-2 bg-panel text-xs text-textMain flex justify-between items-center border-t border-borderCol">
                        <span class="truncate font-medium w-3/4">${formatBytes(item.size)}</span>
                        <span class="text-textSec font-bold">${item.is_video ? 'MP4' : 'IMG'}</span>
                    </div>
                </div>`;
            });
            html += '</div>';
            container.innerHTML = html;
            updateMediaActionState();
        }

        window.toggleSelectMedia = function(name) {
            if (selectedMediaIds.has(name)) selectedMediaIds.delete(name);
            else selectedMediaIds.add(name);
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
            const cnt = document.getElementById('selectedMediaCount');
            if(!btn) return;
            cnt.textContent = selectedMediaIds.size;
            if(selectedMediaIds.size > 0) btn.classList.remove('hidden');
            else btn.classList.add('hidden');
        }

        document.getElementById('btnDeleteSelectedMedia')?.addEventListener('click', () => {
            if(selectedMediaIds.size === 0) return;
            showModal('Hapus File Terpilih?', `Apakah Anda yakin ingin menghapus ${selectedMediaIds.size} file ini secara permanen dari server? (File yang dikunci jadwal akan otomatis dilewati)`, true, async () => {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('files', JSON.stringify(Array.from(selectedMediaIds)));

                try {
                    const res = await fetch('api.php?action=delete_media', { method: 'POST', body: formData });
                    const data = parseSafeJSON(await res.text());
                    if(data.status === 'success') {
                        showToast(data.message);
                        selectedMediaIds.clear();
                        loadMediaGallery();
                    } else showModal('Gagal', data.message);
                } catch(e) { showModal('Error', 'Gagal memproses penghapusan.'); }
            });
        });

        // --- NAVIGATION ROUTER ---
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const menuBtn = document.getElementById('menuBtn');

        function toggleMenu() {
            if(sidebar && overlay) { sidebar.classList.toggle('-translate-x-full'); overlay.classList.toggle('hidden'); }
        }
        if(menuBtn) menuBtn.addEventListener('click', toggleMenu);
        if(overlay) overlay.addEventListener('click', toggleMenu);

        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.nav-link').forEach(nav => {
                    nav.classList.remove('bg-borderCol', 'text-textMain');
                    nav.classList.add('text-textSec');
                    const icon = nav.querySelector('svg');
                    if(icon && !nav.classList.contains('hover:bg-borderCol/50')) icon.classList.remove('text-accent');
                });
                
                e.currentTarget.classList.add('bg-borderCol', 'text-textMain');
                e.currentTarget.classList.remove('text-textSec');
                const targetIcon = e.currentTarget.querySelector('svg');
                if(targetIcon) targetIcon.classList.add('text-accent');

                const target = e.currentTarget.getAttribute('data-target');
                document.querySelectorAll('.view-section').forEach(sec => sec.classList.add('hidden'));
                document.getElementById(`view-${target}`).classList.remove('hidden');

                if (target === 'scheduled') loadScheduledPosts();
                if (target === 'accounts') checkAccountStatus();
                if (target === 'media') loadMediaGallery();

                if (window.innerWidth < 768) toggleMenu();
            });
        });

        if(document.getElementById('view-accounts')) checkAccountStatus();

        // --- DRAFT PROTECTION & MEDIA UPLOAD ---
        const textarea = document.getElementById('postContent');
        const livePreviewArea = document.getElementById('threadsPreview');
        const btnResetDraft = document.getElementById('btnResetDraft');

        function checkDraftState() {
            if (textarea && (textarea.value.trim().length > 0 || selectedFiles.length > 0)) {
                btnResetDraft.classList.remove('hidden');
            } else if (btnResetDraft) {
                btnResetDraft.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (textarea) {
                const savedDraft = localStorage.getItem('ezpost_draft');
                if (savedDraft) {
                    textarea.value = savedDraft;
                    updateLivePreview();
                    checkDraftState();
                }
            }
        });

        if (textarea) {
            textarea.addEventListener('input', () => {
                localStorage.setItem('ezpost_draft', textarea.value);
                updateLivePreview();
                checkDraftState();
            });
        }

        if (btnResetDraft) {
            btnResetDraft.addEventListener('click', () => {
                showModal('Reset Draft?', 'Apakah Anda yakin ingin membuang semua tulisan dan lampiran file yang belum dipublikasikan saat ini?', true, () => {
                    textarea.value = '';
                    localStorage.removeItem('ezpost_draft');
                    selectedFiles = [];
                    renderPreviews();
                    updateLivePreview();
                    document.getElementById('scheduleDate').value = '';
                    document.getElementById('scheduleTimeHour').value = '';
                    checkScheduleState();
                    checkDraftState();
                    showToast('Draft editor berhasil dibersihkan.');
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
                mediaCount.textContent = `${selectedFiles.length} Terlampir`;
                mediaCount.classList.remove('hidden');
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
            selectedFiles.splice(index, 1);
            renderPreviews(); updateLivePreview(); checkDraftState();
        }

        // --- PREVIEW EDITOR ---
        function splitThreads(text) {
            const paragraphs = text.split(/\n\s*\n/);
            let threads = [];
            paragraphs.forEach(p => {
                const trimmed = p.trim();
                if (trimmed.length === 0) return;
                if (trimmed.length > 490) { 
                    let words = trimmed.split(' '); let currentChunk = '';
                    words.forEach(word => {
                        if ((currentChunk + word).length > 490) { threads.push(currentChunk.trim()); currentChunk = word + ' '; } 
                        else { currentChunk += word + ' '; }
                    });
                    if (currentChunk.trim()) threads.push(currentChunk.trim());
                } else { threads.push(trimmed); }
            });
            return threads;
        }

        function updateLivePreview() {
            if(!textarea) return;
            const text = textarea.value;
            const platform = document.getElementById('platformSelect').value;
            
            if (text.length === 0 && selectedFiles.length === 0) {
                livePreviewArea.innerHTML = '<div class="text-sm text-textSec italic text-center py-10 border border-dashed border-borderCol rounded-lg flex flex-col items-center gap-2"><svg class="opacity-50" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>Ketik sesuatu di editor...</div>';
                return;
            }

            let attachHtml = selectedFiles.length > 0 ? `<div class="mt-3 inline-flex items-center gap-1.5 bg-borderCol px-3 py-1.5 rounded-lg text-xs text-textMain border border-borderCol font-bold shadow-sm"><svg class="text-accent" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> ${selectedFiles.length} Media Terlampir</div>` : '';

            if (platform === 'threads') {
                const threads = splitThreads(text);
                if (threads.length === 0 && selectedFiles.length > 0) threads.push(""); 
                
                livePreviewArea.innerHTML = '';
                threads.forEach((thread, index) => {
                    const div = document.createElement('div');
                    div.className = 'bg-panel p-4 rounded-xl border border-borderCol text-sm relative ml-5 thread-divider shadow-sm';
                    let mediaDisplay = (index === 0) ? attachHtml : '';
                    div.innerHTML = `
                        <div class="absolute -left-[1.35rem] top-4 bg-panel w-5 h-5 rounded-full border border-borderCol flex items-center justify-center text-[10px] font-bold text-textSec">${index + 1}</div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-6 h-6 rounded-full border border-borderCol bg-bgMain flex items-center justify-center text-textMain"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
                            <span class="font-bold text-textMain text-xs">Akun Anda</span>
                        </div>
                        <div class="text-textMain leading-relaxed">${thread.replace(/\n/g, '<br>')}</div>
                        ${mediaDisplay}
                        <div class="mt-4 pt-3 border-t border-borderCol flex gap-4 text-textSec text-[10px] font-bold">
                            <span class="flex items-center gap-1.5"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg> 0</span> 
                            <span class="flex items-center gap-1.5"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 2.1l4 4-4 4"/><path d="M3 12.2v-2a4 4 0 0 1 4-4h13.8L17 2.1"/><path d="M7 21.9l-4-4 4-4"/><path d="M21 11.8v2a4 4 0 0 1-4 4H3.2l3.8 3.9"/></svg> 0</span> 
                            <span class="flex items-center gap-1.5"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg> 0</span>
                        </div>`;
                    livePreviewArea.appendChild(div);
                });
            } else {
                livePreviewArea.innerHTML = `
                <div class="bg-panel p-4 rounded-xl border border-borderCol text-sm shadow-sm">
                    <div class="flex items-center gap-3 mb-3 border-b border-borderCol pb-3">
                        <div class="w-8 h-8 rounded border border-accent bg-accent/10 flex items-center justify-center text-[#1877F2]">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                        </div>
                        <div><div class="font-bold text-textMain text-xs uppercase tracking-wide">Halaman FB</div><div class="text-[10px] text-textSec mt-0.5 flex items-center gap-1">Baru saja <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line></svg></div></div>
                    </div>
                    <div class="text-textMain leading-relaxed">${text.replace(/\n/g, '<br>')}</div>
                    ${attachHtml}
                </div>`;
            }
        };

        // --- SUBMIT API (DENGAN PROGRESS BAR) ---
        async function submitPost() {
            if (isTimeError) return showModal('Informasi', 'Lengkapi atau perbaiki Waktu (Tanggal & Jam) jika ingin menjadwalkan post.');

            const content = textarea.value.trim();
            const platform = document.getElementById('platformSelect').value;
            const dateVal = document.getElementById('scheduleDate').value;
            const timeVal = document.getElementById('scheduleTimeHour').value;
            let scheduledAt = ''; let isScheduled = false;

            if (dateVal && timeVal) { scheduledAt = `${dateVal} ${timeVal}:00`; isScheduled = true; }
            if (!content && selectedFiles.length === 0) return showModal('Informasi', 'Konten tulisan atau media tidak boleh kosong!');

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('platform', platform);
            formData.append('content', content);
            if (isScheduled) formData.append('scheduled_at', scheduledAt);
            selectedFiles.forEach(file => formData.append('media[]', file));

            const btn = document.getElementById('btnPublish');
            const btnTextElement = document.getElementById('publishBtnText');
            const oriHtml = btn.innerHTML;
            const oriClasses = btn.className;

            let progressInterval;

            try {
                btn.className = 'w-full sm:w-auto bg-gray-500 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold shadow-md flex items-center justify-center gap-2 cursor-not-allowed opacity-80 transition-all';
                btn.disabled = true;
                
                btn.innerHTML = `<span class="flex items-center z-10"><svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></span><span id="publishBtnText" class="z-10 tracking-wider">Memulai...</span>`;
                const dynamicText = document.getElementById('publishBtnText');

                progressInterval = setInterval(async () => {
                    try {
                        let pRes = await fetch('api.php?action=get_progress&_t=' + Date.now());
                        let pData = await parseSafeJSON(await pRes.text());
                        if(pData.status === 'success' && pData.progress) {
                            dynamicText.textContent = pData.progress;
                        }
                    } catch(e){}
                }, 1500);

                const res = await fetch('api.php?action=save_post&_t=' + Date.now(), { method: 'POST', body: formData });
                const rawText = await res.text();
                clearInterval(progressInterval);
                
                let data;
                try { data = parseSafeJSON(rawText); } 
                catch (parseError) { throw new Error('Server PHP memunculkan Error rahasia.'); }

                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    textarea.value = '';
                    localStorage.removeItem('ezpost_draft');
                    selectedFiles = []; 
                    document.getElementById('scheduleDate').value = '';
                    document.getElementById('scheduleTimeHour').value = '';
                    checkScheduleState();
                    checkDraftState();
                    renderPreviews();
                    updateLivePreview();
                } else {
                    showModal('Ditolak Meta', data.message);
                }
            } catch (err) {
                if(progressInterval) clearInterval(progressInterval);
                showModal('Koneksi Putus', 'Server memutus sambungan. Penyebab utama:\n1. Video/Gambar melebihi batas.\n2. Proses memakan waktu > 60 detik (Timeout).');
            } finally {
                btn.innerHTML = oriHtml;
                btn.className = oriClasses;
                btn.disabled = false;
            }
        }

        document.getElementById('btnPublish')?.addEventListener('click', submitPost);
    </script>
</body>
</html>