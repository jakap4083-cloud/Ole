<?php
// Administrator Dashboard Login view interface page

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Verify if admin session has already been loaded inside authentication cookie parameters
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
     header('Location: /pages/admin/dashboard.php');
     exit();
}

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Gerbang Kontrol VPS Admin | NOXARA</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
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
        body { background-color: #EAF5F4; color: #12302F; font-family: 'Inter', sans-serif; }
        .app-container { max-width: 480px; margin: 0 auto; background-color: #F3FAF9; min-height: 100vh; }
        
        /* Loading Notification float animation styles style */
        .toast-notification {
             position: fixed;
             top: 24px;
             left: 50%;
             transform: translateX(-50%) translateY(-100px);
             z-index: 100;
             transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .toast-notification.show {
             transform: translateX(-50%) translateY(0);
        }
    </style>
</head>
<body class="bg-slate-950 md:py-4">
    
    <!-- Custom Floating responsive notification system Toast -->
    <div id="toast-wrapper" class="toast-notification flex items-center gap-3 p-4 bg-[#12302F] text-white border border-teal-200 shadow-xl rounded-xl max-w-[340px] w-full text-xs font-semibold">
         <span id="toast-icon" class="inline-block w-2.5 h-2.5 rounded-full bg-amber-400"></span>
         <p id="toast-message" class="flex-1 leading-normal text-left">Pesan rute informasi keamanan.</p>
    </div>

    <div class="app-container shadow-2xl border-x border-teal-100 min-h-screen p-6 flex flex-col justify-between">
         <!-- Main Content Wrapper wrapper context -->
         <div class="space-y-6 my-auto">
              <!-- Header Brand display branding info of NOXARA -->
              <div class="text-center space-y-1">
                   <span class="text-[10px] font-mono font-bold text-teal-850 tracking-widest block uppercase">Root Superuser Gate</span>
                   <h1 class="font-display font-bold text-2xl text-[#0F766E] tracking-tight">MANAGEMENT PANEL</h1>
                   <p class="text-[11px] text-[#5B7774] max-w-[280px] mx-auto leading-relaxed">Autentikasi tingkat ganda untuk mengontrol database keuangan ledger, persediaan hardware produk, voucher, dan status rekening denda withdraw.</p>
              </div>

              <!-- Form wrapper card container -->
              <div class="bg-white rounded-2xl p-6 border border-teal-100 shadow-[0_8px_24px_rgba(15,118,110,0.04)] space-y-4">
                   <form id="admin-login-post-form" class="space-y-4 text-left">
                        <?php echo csrf_field(); ?>
                        
                        <!-- Account email -->
                        <div>
                             <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wider">Alamat Surel Superuser / Admin</label>
                             <input id="admin-email-input" type="email" required class="w-full h-11 px-4 text-xs rounded-xl border border-teal-200 focus:outline-[#0F766E] bg-teal-50/10 font-medium" placeholder="admin@noxara.page">
                        </div>

                        <!-- Private key passphrase secret passwords -->
                        <div>
                             <label class="block text-[10px] font-bold text-[#12302F] mb-1.5 uppercase tracking-wider">Kata Sandi Enkripsi Root</label>
                             <input id="admin-password-input" type="password" required class="w-full h-11 px-4 text-xs rounded-xl border border-teal-200 focus:outline-[#0F766E] bg-teal-50/10" placeholder="•••••••••••••••">
                        </div>

                        <div class="pt-2">
                             <button type="submit" id="admin-submit-btn" class="w-full h-11 bg-teal-950 hover:bg-[#0F766E] text-white font-bold rounded-xl text-xs transition-transform active:scale-95 shadow-lg flex items-center justify-center">
                                  Autentikasi Kunci Superuser
                             </button>
                        </div>
                   </form>
              </div>

         </div>

         <!-- App Humble Footnotes details of system logs security -->
         <div class="text-center text-[9px] text-[#5B7774] font-mono select-none py-2 border-t border-teal-50">
              <span>Secure Shell Connect: <strong>0.0.0.0:3000</strong></span>
         </div>
    </div>

    <!-- Inject universal toasts handler scripts -->
    <script>
    function showNotification(msg, type = 'info') {
         const t = document.getElementById('toast-wrapper');
         const tlbl = document.getElementById('toast-message');
         const tbulb = document.getElementById('toast-icon');
         
         tlbl.innerText = msg;
         if (type === 'success') {
              tbulb.className = 'inline-block w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm';
         } else if (type === 'error') {
              tbulb.className = 'inline-block w-2.5 h-2.5 rounded-full bg-rose-600 shadow-sm';
         } else {
              tbulb.className = 'inline-block w-2.5 h-2.5 rounded-full bg-amber-400 shadow-sm';
         }
         
         t.classList.add('show');
         setTimeout(() => {
              t.classList.remove('show');
         }, 3500);
    }

    const adminForm = document.getElementById('admin-login-post-form');
    if (adminForm) {
         adminForm.addEventListener('submit', async function(e) {
              e.preventDefault();
              
              const email = document.getElementById('admin-email-input').value.trim();
              const p = document.getElementById('admin-password-input').value;
              const csrf = document.querySelector('input[name="csrf_token"]').value;

              const btn = document.getElementById('admin-submit-btn');
              btn.disabled = true;
              btn.innerText = 'Menguji Handshake SHA-256 Sesi...';

              try {
                   const res = await fetch('/actions/admin/login-action.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                             email: email,
                             password: p,
                             csrf_token: csrf
                        })
                   });
                   const data = await res.json();
                   if (data.success) {
                        showNotification('Kunci Terotentikasi. Membuka Dashboard Admin...', 'success');
                        setTimeout(() => {
                             window.location.href = '/pages/admin/dashboard.php';
                        }, 1200);
                   } else {
                        showNotification(data.error || 'Autentikasi admin ditolak.', 'error');
                        btn.disabled = false;
                        btn.innerText = 'Autentikasi Kunci Superuser';
                   }
              } catch (err) {
                   showNotification('VPS connection timeout.', 'error');
                   btn.disabled = false;
                   btn.innerText = 'Autentikasi Kunci Superuser';
              }
         });
    }
    </script>
</body>
</html>
