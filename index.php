<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EZPost Publish Planner</title>
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
        .thread-divider::before { content: ''; position: absolute; left: -1.05rem; top: 2.5rem; bottom: -1rem; width: 2px; background-color: var(--border-col); }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg-main); }
        ::-webkit-scrollbar-thumb { background: var(--border-col); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--accent); }
        textarea:focus, input:focus, select:focus, button:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 1px var(--accent); }
        
        /* Ikon Native Picker Fix WCAG */
        input[type="date"], input[type="time"] { color-scheme: light dark; }
        .dark input[type="date"]::-webkit-calendar-picker-indicator, .dark input[type="time"]::-webkit-calendar-picker-indicator {
            filter: invert(1) opacity(0.7); cursor: pointer;
        }
    </style>
</head>
<body class="bg-bgMain text-textMain h-[100dvh] w-full overflow-hidden flex flex-col md:flex-row text-[15px]">

    <!-- GLOBAL MODAL & TOAST -->
    <div id="ezModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none transition-opacity duration-200 hidden">
        <div class="bg-panel border border-borderCol rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl transform scale-95 transition-transform duration-200" id="ezModalContent">
            <h3 class="text-xl font-bold text-textMain mb-2" id="ezModalTitle">Informasi</h3>
            <p class="text-sm text-textSec mb-8 leading-relaxed" id="ezModalBody">Pesan</p>
            <div class="flex justify-end gap-3" id="ezModalActions"></div>
        </div>
    </div>
    
    <div id="ezEditModal" class="fixed inset-0 z-[90] flex items-center justify-center bg-black/70 opacity-0 pointer-events-none transition-opacity duration-200 hidden">
        <div class="bg-panel border border-borderCol rounded-2xl p-6 max-w-sm w-full mx-4 shadow-2xl transform scale-95 transition-transform duration-200" id="ezEditModalContent">
            <h3 class="text-xl font-bold text-textMain mb-4 flex items-center gap-2"><span class="text-accent font-mono">[@]</span> Ubah Jadwal</h3>
            <form id="editScheduleForm" class="space-y-4">
                <input type="hidden" id="edit_post_id">
                <div><label class="block text-xs text-textSec mb-1 font-bold">Tanggal Baru</label><input type="date" id="edit_date" required class="w-full px-4 py-2.5 bg-bgMain border border-borderCol rounded-lg text-textMain focus:border-accent outline-none uppercase"></div>
                <div><label class="block text-xs text-textSec mb-1 font-bold">Waktu Baru</label><input type="time" id="edit_time" required class="w-full px-4 py-2.5 bg-bgMain border border-borderCol rounded-lg text-textMain focus:border-accent outline-none"></div>
                <div class="pt-4 flex gap-3">
                    <button type="button" onclick="closeEditModal()" class="flex-1 px-4 py-2.5 bg-bgMain border border-borderCol text-textSec hover:text-textMain rounded-lg font-bold transition-colors">Batal</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 bg-accent hover:bg-accentHover text-white rounded-lg font-bold transition-colors shadow-md">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div id="toastContainer" class="fixed bottom-6 right-6 z-[100] flex flex-col gap-3 pointer-events-none"></div>

    <?php if (!is_logged_in()): ?>
    <!-- LOGIN SCREEN -->
    <div class="flex items-center justify-center w-full h-full p-4 overflow-y-auto">
        <div class="bg-panel p-8 rounded-xl border border-borderCol w-full max-w-md shadow-lg">
            <div class="flex justify-center mb-6">
                <div class="w-12 h-12 bg-accent rounded-xl flex items-center justify-center shadow-md text-white font-bold text-xl">[EZ]</div>
            </div>
            <h1 class="text-2xl font-bold mb-2 text-center text-textMain">Masuk ke EZPost</h1>
            <p class="text-center text-sm text-textSec mb-6">Gunakan email & password Anda</p>
            <form id="loginForm" class="space-y-4">
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-sm font-medium text-textSec mb-1">Email</label>
                    <input type="email" name="email" required class="w-full bg-bgMain border border-borderCol rounded-lg px-4 py-3 focus:border-accent text-textMain outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-textSec mb-1">Password</label>
                    <input type="password" name="password" required class="w-full bg-bgMain border border-borderCol rounded-lg px-4 py-3 focus:border-accent text-textMain outline-none">
                </div>
                <button type="submit" class="w-full bg-accent hover:bg-accentHover text-white font-semibold rounded-lg px-4 py-3 mt-6 transition-colors shadow-md flex justify-center items-center gap-2 group">
                    <span class="font-mono font-bold group-hover:translate-x-1 transition-transform">[>]</span> Masuk
                </button>
                <div id="loginError" class="text-[var(--error-text)] bg-[var(--error-bg)] text-sm mt-3 hidden text-center p-2 rounded-lg border border-[var(--error-text)]"></div>
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- DASHBOARD -->
    
    <header class="md:hidden flex items-center justify-between p-4 border-b border-borderCol bg-panel w-full shrink-0 z-20 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-accent rounded flex items-center justify-center shadow-sm text-white font-bold text-xs">[EZ]</div>
            <h1 class="text-lg font-bold text-textMain tracking-wide">EZPost</h1>
        </div>
        <button id="menuBtn" class="text-textSec hover:text-textMain p-2 font-mono font-bold transition-all text-xl">[=]</button>
    </header>

    <div id="sidebarOverlay" class="fixed inset-0 bg-black/60 z-30 hidden md:hidden transition-opacity"></div>
    <aside id="sidebar" class="w-64 border-r border-borderCol bg-panel flex flex-col h-full fixed md:relative transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40 shrink-0 shadow-xl md:shadow-none">
        <div class="p-6 hidden md:flex items-center gap-3">
            <div class="w-10 h-10 bg-accent rounded-lg flex items-center justify-center shadow-md text-white font-bold text-sm">[EZ]</div>
            <h1 class="text-xl font-bold text-textMain tracking-wide">EZPost</h1>
        </div>
        
        <nav class="flex-1 px-4 space-y-2 mt-4 md:mt-0 overflow-y-auto">
            <a href="#create" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg bg-borderCol font-medium text-textMain transition-all" data-target="create">
                <span class="font-mono text-accent nav-icon">[*]</span> <span>Buat Post</span>
            </a>
            <a href="#scheduled" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="scheduled">
                <span class="font-mono nav-icon group-hover:text-accent transition-colors">[@]</span> <span>Jadwal Planner</span>
            </a>
            <a href="#media" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="media">
                <span class="font-mono nav-icon group-hover:text-accent transition-colors">[+]</span> <span>Galeri Media</span>
            </a>
            <a href="#accounts" class="nav-link group flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-borderCol/50 text-textSec hover:text-textMain transition-all" data-target="accounts">
                <span class="font-mono nav-icon group-hover:text-accent transition-colors">[#]</span> <span>Status Akun</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-borderCol space-y-2 bg-panel">
            <button id="themeToggle" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-textSec hover:bg-borderCol/50 hover:text-textMain transition-all group">
                <span class="flex items-center gap-3"><span id="themeIconContainer" class="font-mono group-hover:text-amber-500 transition-colors">[o]</span> Tema</span>
                <span id="themeLabel" class="text-xs font-bold px-2 py-1 bg-bgMain rounded border border-borderCol transition-colors font-mono">GELAP</span>
            </button>
            <button onclick="logout()" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg text-textSec hover:bg-[var(--error-bg)] hover:text-[var(--error-text)] transition-all group">
                <span class="font-mono group-hover:text-red-500 transition-colors">[x]</span> <span>Keluar</span>
            </button>
        </div>
    </aside>

    <main class="flex-1 min-h-0 overflow-y-auto bg-bgMain relative w-full flex flex-col">
        <!-- View: Create Post -->
        <section id="view-create" class="view-section p-4 md:p-8 max-w-5xl mx-auto w-full block">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 relative z-10">
                <div class="flex items-center gap-3">
                    <h2 class="text-2xl font-bold text-textMain tracking-tight">Tulis Sesuatu</h2>
                    <button id="btnResetDraft" class="hidden px-2.5 py-1.5 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] rounded-md text-xs font-bold hover:bg-red-700 hover:border-red-700 hover:text-white transition-colors flex items-center gap-1 shadow-sm uppercase tracking-wider">
                        <span class="font-mono">[x]</span> Reset
                    </button>
                </div>
                
                <div class="relative w-full sm:w-64" id="platformDropdownWrap">
                    <button type="button" id="platformTrigger" class="w-full bg-panel border border-borderCol rounded-lg px-4 py-2.5 text-textMain hover:border-accent shadow-sm flex items-center justify-between transition-colors">
                        <div class="flex items-center gap-3" id="platformSelectedText">
                            <span class="font-mono font-bold text-accent">[TH]</span>
                            <span class="font-medium text-sm">Threads</span>
                        </div>
                        <span id="platformArrow" class="font-mono text-textSec transition-transform duration-200 text-xs">▼</span>
                    </button>
                    
                    <div id="platformMenu" class="hidden absolute top-full left-0 mt-2 w-full bg-panel border border-borderCol rounded-lg shadow-xl overflow-hidden opacity-0 transform -translate-y-2 transition-all duration-200">
                        <!-- Pilihan 1: Facebook Saja -->
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain flex items-center gap-3 transition-colors" data-value="facebook">
                            <span class="font-mono font-bold text-[#1877F2]">[FB]</span><span class="font-medium text-sm">Facebook saja</span>
                        </button>
                        <!-- Pilihan 2: Threads Saja -->
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain border-t border-borderCol flex items-center gap-3 transition-colors" data-value="threads">
                            <span class="font-mono font-bold text-textMain">[TH]</span><span class="font-medium text-sm">Threads saja</span>
                        </button>
                        <!-- Pilihan 3: Keduanya (Full Media) -->
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain border-t border-borderCol flex items-center gap-3 transition-colors" data-value="fb_threads_media">
                            <span class="font-mono font-bold text-purple-500">[++]</span><span class="font-medium text-sm">FB + TH (Keduanya Media)</span>
                        </button>
                        <!-- Pilihan 4: Keduanya (Threads Teks Saja) - DEFAULT -->
                        <button type="button" class="platform-option w-full text-left px-4 py-3 hover:bg-bgMain border-t border-borderCol flex items-center gap-3 transition-colors bg-borderCol/20" data-value="fb_threads_nomedia">
                            <span class="font-mono font-bold text-accent">[+T]</span><span class="font-medium text-sm">FB + TH (TH Teks Saja)</span>
                        </button>
                    </div>
                    <input type="hidden" id="platformSelect" value="fb_threads_nomedia"> <!-- Set value default -->
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Text Area -->
                <div class="flex-1 bg-panel border border-borderCol rounded-xl p-5 shadow-sm flex flex-col relative z-0">
                    <textarea id="postContent" class="w-full bg-transparent resize-none flex-1 min-h-[200px] lg:min-h-[150px] text-textMain placeholder-textSec border-none p-0 focus:ring-0 leading-relaxed text-base" placeholder="Apa yang ingin Anda bagikan hari ini?"></textarea>
                    <div id="mediaPreviewContainer" class="flex flex-wrap gap-3 mt-4 empty:hidden"></div>
                    <div class="mt-4 border-t border-borderCol pt-4 flex items-center justify-between">
                        <label class="cursor-pointer text-textSec hover:text-accent transition-colors flex items-center gap-2 px-3 py-1.5 rounded-md hover:bg-bgMain border border-transparent hover:border-borderCol group">
                            <span class="font-mono font-bold text-lg group-hover:scale-110 transition-transform">[+]</span>
                            <span class="font-medium text-sm">Lampirkan Media</span>
                            <input type="file" id="mediaUpload" multiple accept="image/*,video/mp4" class="hidden">
                        </label>
                        <span id="mediaCount" class="text-xs font-bold text-accent bg-accent/10 px-2 py-1 rounded hidden shadow-inner font-mono"></span>
                    </div>
                </div>

                <!-- Preview Panel -->
                <div class="w-full lg:w-[400px] bg-panel border border-borderCol rounded-xl shadow-sm h-fit overflow-hidden flex flex-col">
                    <div class="bg-bgMain px-4 pt-3 flex gap-2 border-b border-borderCol" id="previewTabsContainer"></div>
                    <div class="p-5 overflow-y-auto max-h-[500px]" id="threadsPreview">
                        <div class="text-sm text-textSec italic text-center py-10 border border-dashed border-borderCol rounded-lg flex flex-col items-center gap-2">
                            <span class="font-mono text-2xl opacity-50">[/]</span>
                            Ketik sesuatu di editor...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-panel p-5 rounded-xl border border-borderCol shadow-sm">
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto relative">
                    <label class="text-sm font-bold text-textSec shrink-0">Waktu (Opsional):</label>
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <input type="date" id="scheduleDate" class="w-full sm:w-[160px] bg-bgMain border border-borderCol rounded-lg px-3 py-2.5 text-sm focus:border-accent text-textMain shadow-inner transition-colors outline-none uppercase tracking-wider" onkeydown="return false">
                        <input type="time" id="scheduleTimeHour" class="w-full sm:w-[110px] bg-bgMain border border-borderCol rounded-lg px-3 py-2.5 text-sm focus:border-accent text-textMain shadow-inner transition-colors outline-none tracking-wider" onkeydown="return false">
                        <button type="button" id="btnClearDate" class="hidden text-[var(--error-text)] hover:text-red-700 p-2 rounded transition-colors bg-[var(--error-bg)] border border-[var(--error-text)] ml-1 shrink-0 font-mono font-bold" title="Batal Jadwal">[X]</button>
                    </div>
                </div>

                <div class="flex w-full sm:w-auto mt-2 sm:mt-0">
                    <button id="btnPublish" class="w-full sm:w-auto bg-accent hover:bg-accentHover text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all duration-300 shadow-md flex items-center justify-center gap-2 group cursor-pointer relative overflow-hidden">
                        <span id="publishBtnIcon" class="flex items-center z-10 font-mono font-bold group-hover:translate-x-1 transition-transform">[>]</span>
                        <span id="publishBtnText" class="z-10">Publish</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- View: Planner / Scheduled -->
        <section id="view-scheduled" class="view-section p-4 md:p-8 w-full max-w-5xl mx-auto hidden">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Publish Planner</h2>
                <button onclick="loadScheduledPosts()" class="p-2 text-textSec hover:text-accent transition-colors bg-panel rounded-lg border border-borderCol shadow-sm flex items-center gap-2 text-sm font-bold">
                    <span class="font-mono">[@]</span> Refresh
                </button>
            </div>
            
            <!-- Filters -->
            <div class="flex flex-wrap gap-2 mb-6 bg-panel p-2 rounded-lg border border-borderCol inline-flex shadow-sm">
                <button onclick="filterHistory('all')" id="flt-all" class="px-4 py-1.5 text-sm font-bold rounded-md bg-accent text-white filter-btn transition-colors">Semua</button>
                <button onclick="filterHistory('today')" id="flt-today" class="px-4 py-1.5 text-sm font-bold rounded-md text-textSec hover:text-textMain bg-transparent filter-btn transition-colors">Hari Ini</button>
                <button onclick="filterHistory('month')" id="flt-month" class="px-4 py-1.5 text-sm font-bold rounded-md text-textSec hover:text-textMain bg-transparent filter-btn transition-colors">Bulan Ini</button>
            </div>

            <div id="scheduledContainer" class="w-full space-y-8"></div>
        </section>

        <!-- View: Media -->
        <section id="view-media" class="view-section p-4 md:p-8 w-full max-w-5xl mx-auto hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <h2 class="text-2xl font-bold text-textMain tracking-tight">Galeri Media</h2>
                <div class="flex items-center gap-3 flex-wrap">
                    <select id="mediaSortSelect" class="bg-panel border border-borderCol text-textMain rounded-lg px-3 py-2 text-sm font-bold shadow-sm focus:border-accent outline-none cursor-pointer">
                        <option value="date">Urutkan: Tanggal Upload</option>
                        <option value="size">Urutkan: Ukuran File</option>
                    </select>
                    <button id="btnSelectAllMedia" class="px-3 py-2 text-textSec bg-bgMain border border-borderCol hover:border-accent hover:text-accent transition-colors rounded-lg text-sm font-bold shadow-sm">Pilih Semua</button>
                    <button id="btnDeleteSelectedMedia" class="hidden px-3 py-2 bg-[var(--error-bg)] text-[var(--error-text)] border border-[var(--error-text)] hover:bg-red-700 hover:text-white transition-colors rounded-lg text-sm font-bold shadow-sm flex items-center gap-1">
                        <span class="font-mono font-bold">[X]</span> Hapus Terpilih (<span id="selectedMediaCount">0</span>)
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
                    <span class="font-mono">[@]</span> Cek Status
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-panel border border-borderCol rounded-xl p-6 shadow-sm group hover:border-accent transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg flex items-center gap-3">
                            <div class="bg-[#1877F2]/10 p-2 rounded-lg text-[#1877F2] font-mono text-sm font-bold">[FB]</div> Facebook
                        </h3>
                        <span id="statusFb" class="px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded">Memeriksa...</span>
                    </div>
                </div>
                <div class="bg-panel border border-borderCol rounded-xl p-6 shadow-sm group hover:border-accent transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-lg flex items-center gap-3">
                            <div class="bg-borderCol/50 p-2 rounded-lg text-textMain font-mono text-sm font-bold">[TH]</div> Threads
                        </h3>
                        <span id="statusThreads" class="px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded">Memeriksa...</span>
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
                const firstBrace = rawText.indexOf('{'); const lastBrace = rawText.lastIndexOf('}');
                if (firstBrace !== -1 && lastBrace !== -1) { return JSON.parse(rawText.substring(firstBrace, lastBrace + 1)); }
                throw new Error("Invalid JSON Format");
            }
        }

        // --- SISTEM MODAL & TOAST ---
        function showModal(title, message, isConfirm = false, onConfirm = null) {
            const modal = document.getElementById('ezModal'); const content = document.getElementById('ezModalContent');
            document.getElementById('ezModalTitle').textContent = title;
            document.getElementById('ezModalBody').innerHTML = message.replace(/\n/g, '<br>');
            const actions = document.getElementById('ezModalActions'); actions.innerHTML = '';
            
            if (isConfirm) {
                const btnCancel = document.createElement('button');
                btnCancel.className = 'px-5 py-2.5 rounded-lg font-bold text-textSec bg-bgMain border border-borderCol hover:bg-borderCol transition-colors text-sm';
                btnCancel.textContent = 'Batal'; btnCancel.onclick = closeModal; actions.appendChild(btnCancel);
                
                const btnOk = document.createElement('button');
                btnOk.className = 'px-5 py-2.5 rounded-lg font-bold text-white bg-[var(--error-text)] hover:bg-red-700 transition-colors shadow-sm text-sm';
                btnOk.textContent = 'Ya, Lanjutkan';
                btnOk.onclick = () => { closeModal(); if(onConfirm) onConfirm(); }; actions.appendChild(btnOk);
            } else {
                const btnOk = document.createElement('button');
                btnOk.className = 'px-5 py-2.5 rounded-lg font-bold text-white bg-accent hover:bg-accentHover transition-colors shadow-sm text-sm';
                btnOk.textContent = 'OK Mengerti'; btnOk.onclick = closeModal; actions.appendChild(btnOk);
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
            toast.innerHTML = `<span class="font-mono font-bold">${isSuccess ? '[+]' : '[x]'}</span> <span>${msg}</span>`;
            document.getElementById('toastContainer').appendChild(toast);
            requestAnimationFrame(() => toast.classList.remove('translate-y-10', 'opacity-0'));
            setTimeout(() => { toast.classList.add('opacity-0', 'scale-95'); setTimeout(() => toast.remove(), 300); }, 4000);
        }

        // --- PLATFORM SELECTOR ---
        const pTrigger = document.getElementById('platformTrigger');
        const pMenu = document.getElementById('platformMenu');
        const pInput = document.getElementById('platformSelect');
        const pSelectedText = document.getElementById('platformSelectedText');
        const pArrow = document.getElementById('platformArrow');

        if(pTrigger) {
            // Setup Inisial (Teks dari opsi pertama yg terpilih via HTML)
            const defaultOpt = document.querySelector(`.platform-option[data-value="${pInput.value}"]`);
            if(defaultOpt) pSelectedText.innerHTML = defaultOpt.innerHTML;

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
                pMenu.classList.add('opacity-0', '-translate-y-2'); pArrow.classList.remove('rotate-180');
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

        function updateThemeLabel() {
            if (!themeLabel) return;
            if (htmlElement.classList.contains('dark')) {
                themeLabel.textContent = 'GELAP'; themeLabel.className = 'text-xs font-bold px-2 py-1 bg-borderCol text-textMain rounded border border-transparent transition-colors font-mono';
                themeIconContainer.innerHTML = '[C]';
            } else {
                themeLabel.textContent = 'TERANG'; themeLabel.className = 'text-xs font-bold px-2 py-1 bg-bgMain text-textMain rounded border border-borderCol transition-colors font-mono';
                themeIconContainer.innerHTML = '[O]';
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
        
        // --- SMART BUTTON & DATETIME ---
        let isTimeError = false;
        const dateInput = document.getElementById('scheduleDate');
        const timeInput = document.getElementById('scheduleTimeHour');
        const clearBtn = document.getElementById('btnClearDate');
        const btnPublish = document.getElementById('btnPublish');
        const btnIcon = document.getElementById('publishBtnIcon');
        const btnText = document.getElementById('publishBtnText');

        function checkScheduleState() {
            if(!dateInput || !timeInput) return;
            const hasDate = dateInput.value !== ''; const hasTime = timeInput.value !== '';

            if (hasDate || hasTime) {
                clearBtn.classList.remove('hidden');
                if (hasDate && hasTime) {
                    const scheduledTime = new Date(`${dateInput.value}T${timeInput.value}`); const now = new Date();
                    if (scheduledTime <= now) {
                        isTimeError = true;
                        btnPublish.className = 'w-full sm:w-auto bg-red-600 hover:bg-red-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all shadow-md flex items-center justify-center gap-2 cursor-not-allowed';
                        btnIcon.innerHTML = '<span class="font-mono">[X]</span>'; btnText.textContent = 'Time Error';
                    } else {
                        isTimeError = false;
                        btnPublish.className = 'w-full sm:w-auto bg-purple-600 hover:bg-purple-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all shadow-md flex items-center justify-center gap-2 group cursor-pointer relative overflow-hidden';
                        btnIcon.innerHTML = '<span class="font-mono group-hover:rotate-12 transition-transform">[@]</span>'; btnText.textContent = 'Publish Later';
                    }
                } else {
                    isTimeError = true;
                    btnPublish.className = 'w-full sm:w-auto bg-orange-600 hover:bg-orange-700 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all shadow-md flex items-center justify-center gap-2 cursor-not-allowed';
                    btnIcon.innerHTML = '<span class="font-mono">[!]</span>'; btnText.textContent = 'Isi Jam!';
                }
            } else {
                clearBtn.classList.add('hidden'); isTimeError = false;
                btnPublish.className = 'w-full sm:w-auto bg-accent hover:bg-accentHover text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold transition-all shadow-md flex items-center justify-center gap-2 group cursor-pointer relative overflow-hidden';
                btnIcon.innerHTML = '<span class="font-mono font-bold group-hover:translate-x-1 transition-transform">[>]</span>'; btnText.textContent = 'Publish';
            }
        }

        if (dateInput && timeInput) {
            dateInput.addEventListener('input', checkScheduleState); timeInput.addEventListener('input', checkScheduleState);
            clearBtn.addEventListener('click', () => { dateInput.value = ''; timeInput.value = ''; checkScheduleState(); });
        }

        // --- SISTEM DINAMIS: CEK STATUS AKUN ---
        async function checkAccountStatus() {
            const elFb = document.getElementById('statusFb'); const elTh = document.getElementById('statusThreads');
            if(!elFb || !elTh) return;
            elFb.innerHTML = 'Memeriksa...'; elFb.className = 'px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded flex items-center gap-1';
            elTh.innerHTML = 'Memeriksa...'; elTh.className = 'px-2 py-1 border border-[var(--border-col)] text-textSec bg-bgMain text-xs font-bold rounded flex items-center gap-1';

            try {
                const res = await fetch('api.php?action=check_accounts&_t=' + Date.now()); const data = parseSafeJSON(await res.text());
                const okHtml = `<span class="font-mono">[OK]</span>`; const failHtml = `<span class="font-mono">[X] Belum Terhubung</span>`;
                if(data.status === 'success') {
                    const accounts = data.data;
                    elFb.innerHTML = accounts.includes('facebook') ? okHtml : failHtml;
                    elFb.className = accounts.includes('facebook') ? 'px-2 py-1 border border-[var(--success-text)] text-[var(--success-text)] bg-[var(--success-bg)] text-xs font-bold rounded' : 'px-2 py-1 border border-[var(--error-text)] text-[var(--error-text)] bg-[var(--error-bg)] text-xs font-bold rounded';
                    elTh.innerHTML = accounts.includes('threads') ? okHtml : failHtml;
                    elTh.className = accounts.includes('threads') ? 'px-2 py-1 border border-[var(--success-text)] text-[var(--success-text)] bg-[var(--success-bg)] text-xs font-bold rounded' : 'px-2 py-1 border border-[var(--error-text)] text-[var(--error-text)] bg-[var(--error-bg)] text-xs font-bold rounded';
                }
            } catch(e) {}
        }

        // --- SISTEM DINAMIS: PLANNER ---
        let allPostsData = [];
        
        function getDateLabel(dateStr) {
            const today = new Date(); const target = new Date(dateStr);
            const tDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const tgDate = new Date(target.getFullYear(), target.getMonth(), target.getDate());
            const diffDays = Math.round((tgDate - tDate) / (1000 * 60 * 60 * 24));
            if (diffDays === 0) return "Hari Ini"; if (diffDays === -1) return "Kemarin"; if (diffDays === 1) return "Besok";
            return target.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        async function loadScheduledPosts() {
            const container = document.getElementById('scheduledContainer'); if(!container) return;
            container.innerHTML = '<div class="text-center py-12"><svg class="animate-spin inline w-8 h-8 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg><p class="mt-2 text-textSec text-sm font-bold font-mono">Memuat planner...</p></div>';
            
            try {
                const res = await fetch('api.php?action=get_posts&_t=' + Date.now());
                const data = parseSafeJSON(await res.text());
                if (data.status === 'success') {
                    allPostsData = data.data; renderPlannerHistory('all');
                }
            } catch (e) { container.innerHTML = '<div class="bg-[var(--error-bg)] text-[var(--error-text)] p-4 rounded-lg text-center text-sm font-bold border border-[var(--error-text)]">Gagal memuat jadwal.</div>'; }
        }

        window.filterHistory = function(type) {
            $('.filter-btn').removeClass('bg-accent text-white').addClass('text-textSec bg-transparent');
            $(`#flt-${type}`).removeClass('text-textSec bg-transparent').addClass('bg-accent text-white');
            renderPlannerHistory(type);
        }

        function renderPlannerHistory(filterType) {
            const container = $('#scheduledContainer'); container.empty();
            let filtered = allPostsData;
            const now = new Date(); const currMonth = now.getMonth(); const currYear = now.getFullYear();
            const currDateStr = now.toISOString().split('T')[0];

            if (filterType === 'today') filtered = allPostsData.filter(p => p.scheduled_date_raw === currDateStr);
            else if (filterType === 'month') filtered = allPostsData.filter(p => parseInt(p.month)-1 === currMonth && parseInt(p.year) === currYear);

            if(filtered.length === 0) {
                container.html('<div class="bg-panel border border-borderCol rounded-xl p-12 text-center text-textSec flex flex-col items-center justify-center shadow-sm"><span class="text-4xl mb-4 font-mono font-bold opacity-50">[@]</span><p>Tidak ada riwayat atau antrean pada rentang waktu ini.</p></div>');
                return;
            }

            const groups = {};
            filtered.forEach(post => {
                let label = getDateLabel(post.scheduled_date_raw);
                if(!groups[label]) groups[label] = []; groups[label].push(post);
            });

            for (const [dateLabel, posts] of Object.entries(groups)) {
                let labelColor = 'text-textSec'; if(dateLabel === 'Hari Ini') labelColor = 'text-accent font-black';
                let html = `<div class="mb-8"><h3 class="text-sm font-bold uppercase tracking-widest mb-4 border-b border-borderCol pb-2 ${labelColor}">${dateLabel}</h3><div class="space-y-4">`;

                posts.forEach(p => {
                    let cSnippet = p.content.replace(/<[^>]*>?/gm, '');
                    if(p.platform === 'threads') { try { cSnippet = JSON.parse(p.content).join(' '); } catch(e){} }
                    if(cSnippet.length > 90) cSnippet = cSnippet.substring(0, 90) + '...';
                    if(!cSnippet) cSnippet = '[Ada Lampiran Media]';

                    const plColor = p.platform === 'facebook' ? 'text-[#1877F2] border-[#1877F2]/30 bg-[#1877F2]/10' : 'text-textMain border-borderCol bg-bgMain';
                    const plName = p.platform.charAt(0).toUpperCase() + p.platform.slice(1);
                    
                    let bgStatus = 'bg-bgMain', borderStatus = 'border-borderCol';
                    let stBadge = '<span class="px-2 py-1 bg-gray-800 text-gray-400 border border-gray-700 rounded text-[10px] font-bold">UNKNOWN</span>';
                    
                    if (p.status === 'published') { stBadge = '<span class="px-2 py-1 bg-[var(--success-bg)] text-[var(--success-text)] border border-[var(--success-text)] rounded text-[10px] font-bold font-mono">[OK] SUKSES</span>'; }
                    else if (p.status === 'failed') { bgStatus = 'bg-[var(--error-bg)]'; borderStatus = 'border-[var(--error-text)]'; stBadge = '<span class="px-2 py-1 bg-red-900 text-red-200 border border-red-500 rounded text-[10px] font-bold font-mono">[X] GAGAL</span>'; }
                    else if (p.status === 'scheduled') { bgStatus = 'bg-purple-900/10'; borderStatus = 'border-purple-500/50'; stBadge = '<span class="px-2 py-1 bg-purple-900 text-purple-200 border border-purple-500 rounded text-[10px] font-bold font-mono">[@] ANTREAN</span>'; }

                    const errReason = p.status === 'failed' && p.error_log ? `<div class="mt-3 text-xs text-[var(--error-text)] bg-red-950 p-2 rounded border border-[var(--error-text)] font-mono"><b>Log Error:</b><br>${p.error_log}</div>` : '';
                    
                    let actions = '';
                    if (p.status === 'scheduled' || p.status === 'failed') {
                        actions = `
                        <div class="mt-4 pt-3 border-t border-borderCol flex flex-wrap gap-2">
                            <button class="px-3 py-1.5 bg-accent hover:bg-accentHover text-white text-xs font-bold rounded shadow-sm flex items-center gap-1 force-publish-btn" data-id="${p.id}"><span class="font-mono">[>]</span> Kirim Sekarang</button>
                            <button class="px-3 py-1.5 bg-bgMain hover:bg-borderCol border border-borderCol text-textMain text-xs font-bold rounded shadow-sm flex items-center gap-1" onclick="openEditModal(${p.id}, '${p.scheduled_date_raw}', '${p.scheduled_time_raw}')"><span class="font-mono">[~]</span> Edit Waktu</button>
                            <button class="px-3 py-1.5 bg-[var(--error-bg)] hover:bg-red-700 border border-[var(--error-text)] text-[var(--error-text)] hover:text-white text-xs font-bold rounded shadow-sm flex items-center gap-1" onclick="deleteScheduledPost(${p.id})"><span class="font-mono">[X]</span> Hapus</button>
                        </div>`;
                    }

                    html += `
                    <div class="p-5 rounded-xl border-2 transition-colors ${bgStatus} ${borderStatus} shadow-sm">
                        <div class="flex items-center flex-wrap gap-2 mb-3">
                            ${stBadge}
                            <span class="px-2 py-1 border ${plColor} text-[10px] font-bold rounded uppercase tracking-wider">${plName}</span>
                            <span class="text-sm text-textMain font-black ml-auto font-mono">${p.scheduled_time_raw} WIB</span>
                        </div>
                        <p class="text-sm text-textMain leading-relaxed break-words font-medium">${cSnippet}</p>
                        ${errReason}
                        ${actions}
                    </div>`;
                });
                html += `</div></div>`; container.append(html);
            }
        }

        window.openEditModal = function(id, d, t) { $('#edit_post_id').val(id); $('#edit_date').val(d); $('#edit_time').val(t); $('#ezEditModal').removeClass('hidden').removeClass('opacity-0'); $('#ezEditModalContent').removeClass('scale-95'); }
        window.closeEditModal = function() { $('#ezEditModal').addClass('opacity-0'); $('#ezEditModalContent').addClass('scale-95'); setTimeout(() => $('#ezEditModal').addClass('hidden'), 200); }

        $('#editScheduleForm').submit(async function(e) {
            e.preventDefault();
            const fd = new FormData(); fd.append('action', 'edit_schedule'); fd.append('csrf_token', csrfToken);
            fd.append('post_id', $('#edit_post_id').val()); fd.append('new_date', $('#edit_date').val()); fd.append('new_time', $('#edit_time').val());
            try {
                const res = await fetch('api.php', { method: 'POST', body: fd }); const data = parseSafeJSON(await res.text());
                showToast(data.message, data.status); if(data.status === 'success') { closeEditModal(); loadScheduledPosts(); }
            } catch(e) { showToast("Gagal mengubah jadwal", "error"); }
        });

        $(document).on('click', '.force-publish-btn', async function() {
            let btn = $(this); let pid = btn.data('id'); let ori = btn.html();
            btn.prop('disabled', true).html('<span class="font-mono">[*]</span> Memproses...');
            try {
                const fd = new FormData(); fd.append('action', 'force_publish'); fd.append('csrf_token', csrfToken); fd.append('post_id', pid);
                const res = await fetch('api.php', { method: 'POST', body: fd }); const data = parseSafeJSON(await res.text());
                if(data.status === 'success') {
                    showToast("Sedang dikirimkan...", "success");
                    fetch('cron.php?secret=EZPost1995').finally(() => loadScheduledPosts());
                } else { showToast(data.message, 'error'); btn.prop('disabled', false).html(ori); }
            } catch (e) { showToast('Gagal memanggil API.', 'error'); btn.prop('disabled', false).html(ori); }
        });

        window.deleteScheduledPost = function(id) {
            showModal('Hapus Permanen?', 'Apakah Anda yakin ingin menghapus jadwal ini beserta foto/video lampirannya?', true, async () => {
                const fd = new FormData(); fd.append('action', 'delete_post'); fd.append('csrf_token', csrfToken); fd.append('id', id);
                try {
                    const res = await fetch('api.php', { method: 'POST', body: fd }); const data = parseSafeJSON(await res.text());
                    if(data.status === 'success') { showToast(data.message); loadScheduledPosts(); } else showModal('Gagal', data.message);
                } catch(e) { showModal('Error', 'Gagal menghapus.'); }
            });
        }

        // --- SISTEM DINAMIS: GALERI MEDIA ---
        let mediaItems = []; let selectedMediaIds = new Set();
        async function loadMediaGallery() {
            const container = document.getElementById('mediaContainer'); if(!container) return;
            container.innerHTML = '<div class="text-center py-12"><svg class="animate-spin inline w-8 h-8 text-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></div>';
            try {
                const res = await fetch('api.php?action=get_media&_t=' + Date.now()); const data = parseSafeJSON(await res.text());
                if (data.status === 'success') { mediaItems = data.data; renderMediaGallery(); }
            } catch(e) { container.innerHTML = '<div class="text-[var(--error-text)] p-4 text-center font-bold">Gagal memuat galeri media.</div>'; }
        }

        function formatBytes(bytes) {
            if(bytes === 0) return '0 Bytes'; const k = 1024, dm = 2, sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k)); return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function renderMediaGallery() {
            const container = document.getElementById('mediaContainer'); const sortMode = document.getElementById('mediaSortSelect').value;
            if (mediaItems.length === 0) { container.innerHTML = '<div class="bg-panel border border-borderCol rounded-xl p-12 text-center text-textSec font-bold shadow-sm">Galeri server Anda bersih.</div>'; return; }

            let sorted = [...mediaItems];
            if (sortMode === 'size') sorted.sort((a, b) => b.size - a.size); else sorted.sort((a, b) => b.date - a.date);

            let html = '<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">'; let lastDateStr = '';
            sorted.forEach(item => {
                if (sortMode === 'date') {
                    const dateObj = new Date(item.date * 1000); const dateStr = dateObj.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
                    if (dateStr !== lastDateStr) {
                        html += `</div><div class="flex items-center my-6"><div class="h-px bg-borderCol flex-1"></div><span class="px-4 text-xs font-bold text-textSec uppercase tracking-widest font-mono">${dateStr}</span><div class="h-px bg-borderCol flex-1"></div></div><div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">`;
                        lastDateStr = dateStr;
                    }
                }
                const isSelected = selectedMediaIds.has(item.name);
                const protectionBadge = item.is_protected ? '<div class="absolute top-2 left-2 bg-purple-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow z-20 font-mono">[@] DIKUNCI JADWAL</div>' : '';
                let visual = item.is_video ? `<video src="${item.url}" class="w-full h-32 object-cover"></video><div class="absolute inset-0 bg-black/40 flex items-center justify-center text-white font-bold font-mono">[VID]</div>` : `<img src="${item.url}" class="w-full h-32 object-cover">`;

                html += `
                <div class="relative rounded-xl overflow-hidden border-2 transition-all cursor-pointer group ${isSelected ? 'border-accent shadow-md scale-[0.98]' : 'border-borderCol hover:border-textSec'} bg-bgMain" onclick="toggleSelectMedia('${item.name}')">
                    ${protectionBadge}
                    <div class="absolute top-2 right-2 w-6 h-6 rounded border-2 ${isSelected ? 'bg-accent border-accent text-white' : 'bg-black/50 border-white/50 text-transparent'} flex items-center justify-center transition-colors z-20 font-mono font-bold text-xs">V</div>
                    ${visual}
                    <div class="p-2 bg-panel text-xs text-textMain flex justify-between items-center border-t border-borderCol font-mono">
                        <span class="truncate font-bold">${formatBytes(item.size)}</span>
                        <span class="text-textSec font-bold">${item.is_video ? 'MP4' : 'IMG'}</span>
                    </div>
                </div>`;
            });
            html += '</div>'; container.innerHTML = html; updateMediaActionState();
        }

        window.toggleSelectMedia = function(name) { if (selectedMediaIds.has(name)) selectedMediaIds.delete(name); else selectedMediaIds.add(name); renderMediaGallery(); }
        document.getElementById('mediaSortSelect')?.addEventListener('change', renderMediaGallery);
        document.getElementById('btnSelectAllMedia')?.addEventListener('click', () => { if (selectedMediaIds.size === mediaItems.length) selectedMediaIds.clear(); else mediaItems.forEach(i => selectedMediaIds.add(i.name)); renderMediaGallery(); });

        function updateMediaActionState() {
            const btn = document.getElementById('btnDeleteSelectedMedia'); const cnt = document.getElementById('selectedMediaCount');
            if(!btn) return; cnt.textContent = selectedMediaIds.size;
            if(selectedMediaIds.size > 0) btn.classList.remove('hidden'); else btn.classList.add('hidden');
        }

        document.getElementById('btnDeleteSelectedMedia')?.addEventListener('click', () => {
            if(selectedMediaIds.size === 0) return;
            showModal('Hapus File Terpilih?', `Anda akan menghapus ${selectedMediaIds.size} file ini permanen. File yang sedang dikunci jadwal akan dilewati. Lanjut?`, true, async () => {
                const fd = new FormData(); fd.append('action', 'delete_media'); fd.append('csrf_token', csrfToken); fd.append('files', JSON.stringify(Array.from(selectedMediaIds)));
                try {
                    const res = await fetch('api.php', { method: 'POST', body: fd }); const data = parseSafeJSON(await res.text());
                    if(data.status === 'success') { showToast(data.message); selectedMediaIds.clear(); loadMediaGallery(); } else showModal('Gagal', data.message);
                } catch(e) { showModal('Error', 'Gagal memproses penghapusan.'); }
            });
        });

        // --- AUTH LOGIC ---
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = loginForm.querySelector('button'); const oriHtml = btn.innerHTML;
                btn.innerHTML = '<span class="font-mono">[*]</span> Memverifikasi...'; btn.disabled = true;
                
                try {
                    const res = await fetch('api.php', { method: 'POST', body: new FormData(loginForm) });
                    const data = parseSafeJSON(await res.text());
                    if (data.status === 'success') location.reload();
                    else {
                        document.getElementById('loginError').textContent = data.message;
                        document.getElementById('loginError').classList.remove('hidden');
                        btn.innerHTML = oriHtml; btn.disabled = false;
                    }
                } catch(e) { showModal('Error', "Kesalahan JSON/Server saat login."); btn.innerHTML = oriHtml; btn.disabled = false; }
            });
        }
        function logout() { fetch('api.php', {method: 'POST', body: new URLSearchParams({'action':'logout'})}).then(() => location.reload()); }

        // --- DRAFT PROTECTION & TEXT EDITOR PREVIEW ---
        const textarea = document.getElementById('postContent');
        const livePreviewArea = document.getElementById('threadsPreview');
        const btnResetDraft = document.getElementById('btnResetDraft');

        function checkDraftState() {
            if (textarea && (textarea.value.trim().length > 0 || selectedFiles.length > 0)) btnResetDraft.classList.remove('hidden');
            else if (btnResetDraft) btnResetDraft.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (textarea) {
                const savedDraft = localStorage.getItem('ezpost_draft');
                if (savedDraft) { textarea.value = savedDraft; updateLivePreview(); checkDraftState(); }
            }
        });

        if (textarea) { textarea.addEventListener('input', () => { localStorage.setItem('ezpost_draft', textarea.value); updateLivePreview(); checkDraftState(); }); }
        if (btnResetDraft) {
            btnResetDraft.addEventListener('click', () => {
                showModal('Reset Draft?', 'Buang semua tulisan dan media yang belum di-publish?', true, () => {
                    textarea.value = ''; localStorage.removeItem('ezpost_draft'); selectedFiles = [];
                    renderPreviews(); updateLivePreview(); document.getElementById('scheduleDate').value = ''; document.getElementById('scheduleTimeHour').value = '';
                    checkScheduleState(); checkDraftState(); showToast('Draft dibersihkan.');
                });
            });
        }

        // Live Preview Tabs & Logic
        const tabsContainer = document.getElementById('previewTabsContainer');
        function updateLivePreview() {
            if(!textarea) return;
            const text = textarea.value; const platform = document.getElementById('platformSelect').value;
            let isFb = platform === 'facebook' || platform.startsWith('fb_threads');
            let isTh = platform === 'threads' || platform.startsWith('fb_threads');
            
            // Build Tabs Header
            let tHtml = '';
            if(isFb) tHtml += `<button class="px-4 py-2 font-bold text-sm border-b-2 text-[#1877F2] border-[#1877F2] prev-tab-btn" data-target="fb"><span class="font-mono">[FB]</span> Facebook</button>`;
            if(isTh) tHtml += `<button class="px-4 py-2 font-bold text-sm border-b-2 text-textSec border-transparent prev-tab-btn" data-target="th"><span class="font-mono">[TH]</span> Threads</button>`;
            tabsContainer.innerHTML = tHtml;

            // Handle Tab Clicking
            document.querySelectorAll('.prev-tab-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    document.querySelectorAll('.prev-tab-btn').forEach(b => { b.classList.remove('text-[#1877F2]', 'text-white', 'border-[#1877F2]', 'border-white'); b.classList.add('text-textSec', 'border-transparent'); });
                    let tgt = e.currentTarget.dataset.target;
                    e.currentTarget.classList.remove('text-textSec', 'border-transparent');
                    e.currentTarget.classList.add(tgt==='fb'?'text-[#1877F2]':'text-white', tgt==='fb'?'border-[#1877F2]':'border-white');
                    document.querySelectorAll('.prev-content-pane').forEach(p => p.classList.add('hidden'));
                    document.getElementById('prev-' + tgt).classList.remove('hidden');
                });
            });
            // Aktifkan tab pertama
            if(document.querySelector('.prev-tab-btn')) document.querySelector('.prev-tab-btn').click();

            if (text.length === 0 && selectedFiles.length === 0) {
                livePreviewArea.innerHTML = '<div class="text-sm text-textSec italic text-center py-10 border border-dashed border-borderCol rounded-lg"><span class="font-mono block text-2xl mb-2 opacity-50">[/]</span>Ketik sesuatu atau tambahkan media...</div>';
                return;
            }

            let fbMedia = selectedFiles.length > 0 ? `<div class="mt-3 w-full h-32 bg-gray-800 rounded-lg flex items-center justify-center text-xs text-gray-400 font-mono border border-gray-700">[+] ${selectedFiles.length} MEDIA TERLAMPIR</div>` : '';
            let thMedia = (selectedFiles.length > 0 && platform !== 'fb_threads_nomedia') ? fbMedia : '';

            let cHtml = '';
            if(isFb) {
                cHtml += `<div id="prev-fb" class="prev-content-pane hidden"><div class="bg-panel p-4 rounded-xl border border-borderCol text-sm shadow-sm">
                    <div class="flex items-center gap-3 mb-3 border-b border-borderCol pb-3">
                        <div class="w-8 h-8 rounded border border-accent bg-accent/10 flex items-center justify-center text-[#1877F2] font-mono text-xs font-bold">[FB]</div>
                        <div><div class="font-bold text-textMain text-xs uppercase tracking-wide">Halaman FB</div><div class="text-[10px] text-textSec font-mono mt-0.5">Baru saja - Publik</div></div>
                    </div>
                    <div class="text-textMain leading-relaxed whitespace-pre-wrap">${text}</div>${fbMedia}
                </div></div>`;
            }

            if(isTh) {
                let thCont = ''; const threads = splitThreads(text); if (threads.length === 0 && selectedFiles.length > 0) threads.push(""); 
                threads.forEach((thread, index) => {
                    let mDisp = (index === 0) ? thMedia : '';
                    thCont += `<div class="bg-panel p-4 rounded-xl border border-borderCol text-sm relative ml-5 thread-divider shadow-sm mb-4">
                        <div class="absolute -left-[1.35rem] top-4 bg-panel w-5 h-5 rounded-full border border-borderCol flex items-center justify-center text-[10px] font-bold font-mono text-textSec">${index + 1}</div>
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-6 h-6 rounded-full border border-borderCol bg-bgMain flex items-center justify-center text-textMain font-mono text-[10px] font-bold">[U]</div>
                            <span class="font-bold text-textMain text-xs">Akun Anda</span>
                        </div>
                        <div class="text-textMain leading-relaxed whitespace-pre-wrap">${thread}</div>${mDisp}
                        <div class="mt-4 pt-3 border-t border-borderCol flex gap-4 text-textSec font-mono text-[10px] font-bold"><span>[ ] Komen</span> <span>[ ] Repost</span> <span>[ ] Suka</span></div>
                    </div>`;
                });
                cHtml += `<div id="prev-th" class="prev-content-pane hidden">${thCont}</div>`;
            }
            livePreviewArea.innerHTML = cHtml;
            // Tampilkan aktif lagi
            if(document.querySelector('.prev-tab-btn')) document.querySelector('.prev-tab-btn').click();
        }

        if (document.getElementById('platformSelect')) document.getElementById('platformSelect').addEventListener('change', updateLivePreview);

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
            formData.append('action', 'save_post');
            formData.append('csrf_token', csrfToken);
            formData.append('platform', platform);
            formData.append('content', content);
            if (isScheduled) formData.append('scheduled_at', scheduledAt);
            selectedFiles.forEach(file => formData.append('media[]', file));

            const btn = document.getElementById('btnPublish');
            const oriHtml = btn.innerHTML; const oriClasses = btn.className;
            let progressInterval;

            try {
                btn.className = 'w-full sm:w-auto bg-gray-500 text-white px-8 py-3 sm:py-2.5 rounded-lg font-bold shadow-md flex items-center justify-center gap-2 cursor-not-allowed opacity-80 transition-all';
                btn.disabled = true;
                btn.innerHTML = `<span class="flex items-center z-10"><svg class="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg></span><span id="publishBtnText" class="z-10 tracking-wider">Memulai...</span>`;
                const dynamicText = document.getElementById('publishBtnText');

                progressInterval = setInterval(async () => {
                    try {
                        let pRes = await fetch('api.php', {method:'POST', body:new URLSearchParams({'action':'get_progress'})});
                        let pData = parseSafeJSON(await pRes.text());
                        if(pData.status === 'success' && pData.progress) dynamicText.textContent = pData.progress;
                    } catch(e){}
                }, 1500);

                const res = await fetch('api.php', { method: 'POST', body: formData });
                const rawText = await res.text();
                clearInterval(progressInterval);
                
                let data;
                try { data = parseSafeJSON(rawText); } 
                catch (parseError) { throw new Error('Server PHP memunculkan Error rahasia.'); }

                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    textarea.value = ''; localStorage.removeItem('ezpost_draft'); selectedFiles = []; 
                    document.getElementById('scheduleDate').value = ''; document.getElementById('scheduleTimeHour').value = '';
                    checkScheduleState(); checkDraftState(); renderPreviews(); updateLivePreview();
                } else { showModal('Ditolak Meta', data.message); }
            } catch (err) {
                if(progressInterval) clearInterval(progressInterval);
                showModal('Koneksi Putus', 'Server memutus sambungan. Video/Gambar melebihi batas atau Timeout Eksekusi.');
            } finally {
                btn.innerHTML = oriHtml; btn.className = oriClasses; btn.disabled = false;
            }
        }

        document.getElementById('btnPublish')?.addEventListener('click', submitPost);
    </script>
</body>
</html>