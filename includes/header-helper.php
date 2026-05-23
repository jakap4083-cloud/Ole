<?php
// Page render helper wrapper containing layout head, styling references, and dynamic configurations
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Safe check maintenance mode globally
$maintenance = is_maintenance_mode();
if ($maintenance && !isset($_SESSION['admin_id'])) {
     header('Location: /pages/public/maintenance.php');
     exit();
}

function render_header($title = 'NOXARA', $show_bottom_nav = true, $active_tab = 'home') {
     $csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo sanitize_output($title); ?> | NOXARA</title>
    <!-- Use Google Fonts (Space Grotesk display paired with Inter & JetBrains Mono as per styling instruction docs) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
    <!-- Tailwind CSS dynamic framework CDN (Since custom tailwind 4 is loaded visually) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        noxara: {
                            bg: '#EAF5F4',
                            surface: '#F3FAF9',
                            soft: '#D9EEEC',
                            primary: '#0F766E',
                            light: '#14B8A6',
                            accent: '#2DD4BF',
                            text: '#12302F',
                            muted: '#5B7774',
                            border: '#B7D6D2',
                            success: '#16A34A',
                            danger: '#DC2626',
                            warning: '#EA580C'
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Space Grotesk', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace']
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background-color: #EAF5F4;
            color: #12302F;
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        /* Custom styled slim container simulating standard mobile layout */
        .app-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            background-color: #F3FAF9;
            box-shadow: 0 10px 25px -5px rgba(15, 118, 110, 0.1), 0 8px 10px -6px rgba(15, 118, 110, 0.05);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        @media (min-width: 768px) {
            .app-container {
                min-height: 840px;
                height: 840px;
                border-radius: 40px;
                border: 8px solid #12302F;
                overflow: hidden;
                box-shadow: 0 25px 50px -12px rgba(15, 118, 110, 0.25);
            }
        }
        /* Hide default scrollbars for supreme minimal look */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none; /* IE and Edge */
            scrollbar-width: none; /* Firefox */
        }
        /* Ripple & transitions */
        .btn-active:active {
            transform: scale(0.96);
        }
        /* Animation transitions */
        .fade-in {
            animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-full bg-[#EAF5F4] md:py-8 flex items-center justify-center">
    <!-- Outer Wrapper with side branding for premium presentation on large screens -->
    <div class="w-full flex items-center justify-center gap-16 px-4">
        
        <!-- App Mockup Phone Container -->
        <div class="app-container border-x border-[#B7D6D2]/30 w-full flex-shrink-0">
            <!-- Simulated Top Status Bar -->
            <div class="h-6 flex justify-between items-center px-6 pt-2 shrink-0 select-none bg-[#F3FAF9] text-[#12302F] text-[10px] font-bold">
                <span>9:41</span>
                <div class="flex items-center gap-1.5 font-mono">
                    <span class="text-[8px] opacity-70">4G LTE</span>
                    <div class="w-3 h-3 bg-[#12302F] rounded-full"></div>
                    <div class="w-3 h-3 bg-[#12302F] opacity-20 rounded-full"></div>
                </div>
            </div>
            
            <!-- Dynamic Main App Content Body viewport -->
            <main class="flex-1 overflow-y-auto no-scrollbar pb-24 <?php echo ($active_tab === 'home') ? '' : 'p-4'; ?>">
<?php
}

function render_footer($show_bottom_nav = true, $active_tab = 'home') {
     if ($show_bottom_nav) {
          require_once __DIR__ . '/../../includes/settings-helper.php';
          // Check Bottom Nav togglers
          $nav_enabled = [
              'home' => is_menu_enabled('home'),
              'team' => is_menu_enabled('team'),
              'products' => is_menu_enabled('products'),
              'mining' => is_menu_enabled('mining'),
              'transactions' => is_menu_enabled('transactions'),
              'profile' => is_menu_enabled('profile')
          ];
?>
        </main>
        
        <!-- Live Chat Floating Button Pojok Kiri Bawah -->
        <?php if (is_feature_enabled('live_chat') && is_menu_enabled('contact_admin')): ?>
        <a id="floating-chat" href="/pages/user/chat.php" class="absolute bottom-20 left-6 z-40 bg-[#12302F] hover:bg-teal-950 text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-transform duration-200 active:scale-95 border-2 border-teal-200/20">
             <!-- SVG Custom Style Chat Polos matching theme -->
             <svg class="w-5 h-5 text-[#2DD4BF]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
             </svg>
        </a>
        <?php endif; ?>

        <!-- Bottom Tab Bar Navigation (Absolute inside applet mockup frame layout) -->
        <nav class="absolute bottom-0 left-0 right-0 bg-white border-t border-[#B7D6D2] px-2 py-2.5 flex justify-between items-center z-50 shadow-[0_-4px_10px_rgba(15,118,110,0.05)] md:rounded-b-[38px] shrink-0">
             <!-- TAB 1: Beranda -->
             <?php if ($nav_enabled['home']): ?>
             <a href="/pages/user/home.php" class="flex-1 flex flex-col items-center justify-center py-1 transition-colors <?php echo ($active_tab === 'home') ? 'text-[#0F766E]' : 'text-[#5B7774] hover:text-[#0F766E]'; ?>">
                 <svg class="w-5.5 h-5.5 mb-0.5 animate-pulse" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                 </svg>
                 <span class="text-[9px] font-black tracking-tight">HOME</span>
             </a>
             <?php endif; ?>

             <!-- TAB 2: Tim -->
             <?php if ($nav_enabled['team']): ?>
             <a href="/pages/user/team.php" class="flex-1 flex flex-col items-center justify-center py-1 transition-colors <?php echo ($active_tab === 'team') ? 'text-[#0F766E]' : 'text-[#5B7774] hover:text-[#0F766E]'; ?>">
                 <svg class="w-5.5 h-5.5 mb-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                 </svg>
                 <span class="text-[9px] font-bold tracking-tight">TIM</span>
             </a>
             <?php endif; ?>

             <!-- TAB 3: POSITIVE BUTTON (Produk) -->
             <?php if ($nav_enabled['products']): ?>
             <a href="/pages/user/products.php" class="relative -top-5 flex-1 flex flex-col items-center justify-center py-1 shrink-0">
                 <div class="bg-[#0F766E] text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg shadow-teal-850/30 transform transition-transform duration-200 active:scale-90 border-[4px] border-[#F3FAF9]">
                     <svg class="w-5.5 h-5.5 text-white" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                     </svg>
                 </div>
                 <span class="text-[9px] font-black text-[#0F766E] mt-0.5">PRODUK</span>
             </a>
             <?php endif; ?>

             <!-- TAB 4: Mining -->
             <?php if ($nav_enabled['mining']): ?>
             <a href="/pages/user/mining.php" class="flex-1 flex flex-col items-center justify-center py-1 transition-colors <?php echo ($active_tab === 'mining') ? 'text-[#0F766E]' : 'text-[#5B7774] hover:text-[#0F766E]'; ?>">
                 <svg class="w-5.5 h-5.5 mb-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 21h5l-.813-5.096M15 10H9v6h6v-6zm3-5H6a2 2 0 00-2 2v6a2 2 0 002 2h12a2 2 0 002-2V7a2 2 0 00-2-2z"></path>
                 </svg>
                 <span class="text-[9px] font-bold tracking-tight">MINING</span>
             </a>
             <?php endif; ?>

             <!-- TAB 5: Transaksi -->
             <?php if ($nav_enabled['transactions']): ?>
             <a href="/pages/user/transactions.php" class="flex-1 flex flex-col items-center justify-center py-1 transition-colors <?php echo ($active_tab === 'transactions') ? 'text-[#0F766E]' : 'text-[#5B7774] hover:text-[#0F766E]'; ?>">
                 <svg class="w-5.5 h-5.5 mb-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                 </svg>
                 <span class="text-[9px] font-bold tracking-tight">TRANSAKSI</span>
             </a>
             <?php endif; ?>

             <!-- TAB 6: Profil -->
             <?php if ($nav_enabled['profile']): ?>
             <a href="/pages/user/profile.php" class="flex-1 flex flex-col items-center justify-center py-1 transition-colors <?php echo ($active_tab === 'profile') ? 'text-[#0F766E]' : 'text-[#5B7774] hover:text-[#0F766E]'; ?>">
                 <svg class="w-5.5 h-5.5 mb-0.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                     <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                 </svg>
                 <span class="text-[9px] font-bold tracking-tight">PROFIL</span>
             </a>
             <?php endif; ?>
         </nav>
<?php
      } else {
?>
         </main>
<?php
      }
?>
        </div> <!-- End .app-container mockup -->

        <!-- Side-car Branding Card Element (Presents beautiful viewport details on wide screens) -->
        <div class="max-w-xs hidden lg:block select-none text-left flex-shrink">
             <span class="text-[10px] font-mono font-black text-[#0F766E] tracking-widest block uppercase mb-1">PRO-GRADE COMPILED WEB PORTAL</span>
             <h1 class="text-5xl font-black text-[#0F766E] leading-tight tracking-tighter">MOBILE<br/>FIRST<br/>OCEAN.</h1>
             <p class="mt-4 text-[#5B7774] text-xs font-semibold leading-relaxed uppercase tracking-wider">NOXARA is built for speed, performance, and iron-clad integrity. A premium hardware-backed mining experience in the palm of your hand.</p>
             <div class="mt-8 flex gap-2.5">
                  <div class="w-16 h-1 bg-[#0F766E] rounded-full"></div>
                  <div class="w-4 h-1 bg-[#B7D6D2] rounded-full"></div>
                  <div class="w-4 h-1 bg-[#B7D6D2] rounded-full"></div>
             </div>
        </div>

    </div> <!-- End outer gap-16 layout -->
</div> <!-- End backdrop layout center -->

    <!-- Alert / Toast Popup Toast DOM Node -->
    <div id="toast-wrapper" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[350px] pointer-events-none space-y-2"></div>

    <script>
        // System Wide Robust Action Handlers & Custom Toast
        function showNotification(msg, type = 'success') {
             const container = document.getElementById('toast-wrapper');
             if (!container) return;
             
             const toast = document.createElement('div');
             toast.className = `p-3.5 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2 transform transition-all duration-300 translate-y-[-10px] opacity-0 pointer-events-auto`;
             
             // color mapping
             if (type === 'success') {
                  toast.className += ' bg-emerald-600 text-white';
             } else if (type === 'error') {
                  toast.className += ' bg-rose-600 text-white';
             } else {
                  toast.className += ' bg-amber-500 text-white';
             }
             
             toast.innerHTML = `
                 <span>${msg}</span>
             `;
             
             container.appendChild(toast);
             
             // Slide down
             setTimeout(() => {
                  toast.classList.remove('translate-y-[-10px]', 'opacity-0');
             }, 10);
             
             // Slide up fade out
             setTimeout(() => {
                  toast.classList.add('translate-y-[-10px]', 'opacity-0');
                  setTimeout(() => {
                       toast.remove();
                  }, 300);
             }, 3500);
        }

        // Global Form Ajax Wrapper
        async function fetchAPI(url, data) {
             try {
                 const res = await fetch(url, {
                     method: 'POST',
                     headers: { 'Content-Type': 'application/json' },
                     body: JSON.stringify(data)
                 });
                 if (!res.ok) {
                     const errData = await res.json();
                     throw new Error(errData.error || 'Server error: ' + res.status);
                 }
                 return await res.json();
             } catch (e) {
                 showNotification(e.message, 'error');
                 return { success: false, error: e.message };
             }
        }
    </script>
</body>
</html>
<?php
}
