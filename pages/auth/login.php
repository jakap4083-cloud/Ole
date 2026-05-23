<?php
// Secure User Login UI Screen
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/flash.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

if (isset($_SESSION['user_id'])) {
     header('Location: /pages/user/home.php');
     exit();
}

$csrf_token = generate_csrf_token();
$promo_counters = [];
try {
     $db = get_db_connection();
     $stmt = $db->query("SELECT * FROM promo_counters LIMIT 1");
     $promo_counters = $stmt->fetch();
} catch (Exception $e) {}

if (!$promo_counters) {
     $promo_counters = [
         'users_joined' => 18742,
         'total_topup' => 248920000.00,
         'total_withdrawn' => 142100000.00,
         'trans_success' => 9841,
         'active_today' => 3120
     ];
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Masuk ke Akun | NOXARA</title>
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
    </style>
</head>
<body class="bg-slate-900 md:py-4">
    <div class="app-container shadow-2xl border-x border-teal-100 min-h-screen p-6 flex flex-col justify-start">
         
         <!-- Top Logo Layout -->
         <div class="text-center my-6">
              <div class="inline-flex items-center justify-center bg-teal-800 text-white w-14 h-14 rounded-2xl shadow-md border-2 border-teal-400 mb-2">
                   <!-- Custom SVG Logo -->
                   <svg class="w-8 h-8 text-teal-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                   </svg>
              </div>
              <h1 class="font-display text-2xl font-bold tracking-tight text-[#0F766E]">NOXARA</h1>
              <p class="text-xs text-[#5B7774] font-medium tracking-wide">Cloud Mining Platform Premium</p>
         </div>

         <!-- Validation Alert Area -->
         <div id="alert-container">
              <?php echo display_flash_alerts(); ?>
         </div>

         <!-- Login Form Card -->
         <form id="login-form" class="space-y-4 bg-white p-5 rounded-2xl border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.03)] fade-in">
              <?php echo csrf_field(); ?>
              
              <!-- Input Username / Email / Phone -->
              <div>
                   <label class="block text-xs font-semibold text-[#12302F] mb-1.5 uppercase tracking-wider">Username / Email / No HP</label>
                   <div class="relative">
                        <input id="username" type="text" required class="w-full h-12 px-4 rounded-xl border border-teal-200 bg-teal-50/10 focus:outline-none focus:border-[#0F766E] text-sm text-[#12302F] font-medium" placeholder="Masukkan akun Anda">
                   </div>
              </div>

              <!-- Input Password with Show/Hide -->
              <div>
                   <label class="block text-xs font-semibold text-[#12302F] mb-1.5 uppercase tracking-wider">Password</label>
                   <div class="relative">
                        <input id="password" type="password" required class="w-full h-12 pl-4 pr-12 rounded-xl border border-teal-200 bg-teal-50/10 focus:outline-none focus:border-[#0F766E] text-sm text-[#12302F]" placeholder="Sandi minimal 8 karakter">
                        <button type="button" onclick="togglePasswordView()" class="absolute right-3.5 top-3.5 text-teal-600 focus:outline-none">
                             <!-- Show/Hide custom SVG -->
                             <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                             </svg>
                        </button>
                   </div>
              </div>

              <!-- Mathematics Captcha -->
              <div>
                   <label class="block text-xs font-semibold text-[#12302F] mb-1.5 uppercase tracking-wider">Verifikasi Kecerdasan</label>
                   <div class="grid grid-cols-2 gap-3">
                        <div class="h-12 bg-teal-50 border border-teal-200 rounded-xl flex items-center justify-center font-mono font-bold text-teal-800 tracking-wider select-none text-sm" id="captcha-quest-field">
                             Memuat...
                        </div>
                        <input id="captcha" type="number" required class="w-full h-12 px-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-sm text-center font-mono font-bold" placeholder="Hasil = ?">
                   </div>
              </div>

              <!-- Checkbox Terms -->
              <div class="flex items-start gap-2.5 pt-1">
                   <input id="terms" type="checkbox" required class="mt-1 w-4 h-4 rounded text-teal-700 bg-teal-50 border-teal-200 focus:ring-teal-700">
                   <label for="terms" class="text-xs text-[#5B7774] leading-relaxed">Saya menyatakan telah menyetujui <a href="/pages/public/info.php?cat=tos" class="text-[#0F766E] font-semibold underline">Syarat & Ketentuan</a> dan <a href="/pages/public/info.php?cat=privacy" class="text-[#0F766E] font-semibold underline">Kebijakan Privasi</a> NOXARA.</label>
              </div>

              <!-- Action buttons -->
              <div class="pt-2">
                   <button type="submit" class="w-full h-12 bg-[#0F766E] hover:bg-teal-800 text-white font-semibold rounded-xl text-sm transition-transform active:scale-[0.98] shadow-md flex items-center justify-center gap-2">
                        Masuk Sekarang
                   </button>
              </div>

              <div class="flex justify-between items-center text-xs pt-1.5 border-t border-teal-50">
                   <a href="https://wa.me/628123456789" class="text-[#5B7774] hover:text-[#0F766E]">Lupa Sandi? CS Chat</a>
                   <a href="/pages/auth/register.php" class="text-[#0F766E] font-semibold hover:underline">Belum punya akun? Daftar</a>
              </div>
         </form>

         <!-- Platform Statistic Counters Section (Promosi) -->
         <div class="mt-6 bg-teal-900 text-white rounded-2xl p-5 border border-teal-800 relative overflow-hidden">
               <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
               <h3 class="font-display text-sm font-bold text-teal-200 mb-3 border-b border-teal-800 pb-1.5">Sirkulasi Kinerja Platform Real-Time</h3>
               
               <div class="grid grid-cols-2 gap-4">
                    <div>
                         <span class="block text-[10px] text-teal-300 font-semibold tracking-wider uppercase">Bergabung</span>
                         <span class="block font-display font-bold text-lg text-teal-100"><?php echo number_format($promo_counters['users_joined']); ?> Akun</span>
                    </div>
                    <div>
                         <span class="block text-[10px] text-teal-300 font-semibold tracking-wider uppercase">Transaksi Sukses</span>
                         <span class="block font-display font-bold text-lg text-teal-100"><?php echo number_format($promo_counters['trans_success']); ?> Sesi</span>
                    </div>
                    <div>
                         <span class="block text-[10px] text-teal-300 font-semibold tracking-wider uppercase">Pembayaran</span>
                         <span class="block font-display font-bold text-lg text-teal-100 font-mono"><?php echo format_currency($promo_counters['total_topup']); ?></span>
                    </div>
                    <div>
                         <span class="block text-[10px] text-teal-300 font-semibold tracking-wider uppercase font-mono">Penarikan Lunas</span>
                         <span class="block font-display font-bold text-lg text-teal-100 font-mono"><?php echo format_currency($promo_counters['total_withdrawn']); ?></span>
                    </div>
               </div>

               <div class="mt-4 pt-3 border-t border-teal-800 flex justify-between items-center text-[10px] text-teal-300 font-medium font-mono">
                    <span>Sistem Terverifikasi SSL Secure</span>
                    <span>Aktif Hari Ini: <?php echo number_format($promo_counters['active_today']); ?> User</span>
               </div>
         </div>

         <!-- Platform Brief / FAQ Accordions -->
         <div class="my-6 space-y-3">
              <h3 class="font-display font-bold text-sm text-[#0F766E] opacity-90 px-1">Tentang Platform & FAQ</h3>
              
              <div class="bg-white rounded-xl border border-teal-100 p-4">
                   <h4 class="text-xs font-bold text-[#12302F] mb-1">Misi & Landasan NOXARA</h4>
                   <p class="text-[11px] text-[#5B7774] leading-relaxed">NOXARA berkomitmen menghadirkan ekosistem investasi pertambangan awan (*cloud mining*) yang transparan dan aman bagi nasabah retail seluler. Dengan sistem kontrol terenkripsi, dana Anda dipadu dengan infrastruktur berefisiensi optimal.</p>
              </div>

              <div class="bg-white rounded-xl border border-teal-100 p-4">
                   <h4 class="text-xs font-bold text-[#12302F] mb-1">Bagaimana cara mencairkan profit?</h4>
                   <p class="text-[11px] text-[#5B7774] leading-relaxed">Ketika mesin tambang Anda menghasilkan profit harian, nominal terakumulasi di Saldo Profit. Anda dapat dengan bebas memindahkan saldo tersebut ke Saldo Utama untuk ditarik langsung ke rekening terdaftar Anda di halaman penarikan.</p>
              </div>
         </div>

         <!-- Static Bottom Footer Info links -->
         <div class="mt-auto py-4 text-center text-[10px] text-[#5B7774] space-x-2">
              <a href="/pages/public/info.php?cat=tos" class="hover:underline">Syarat & Ketentuan</a>
              <span>•</span>
              <a href="/pages/public/info.php?cat=privacy" class="hover:underline">Kebijakan Privasi</a>
              <span>•</span>
              <span class="font-mono">© 2026 NOXARA.page</span>
         </div>
    </div>

    <!-- Alert Toast Wrapper -->
    <div id="toast-wrapper" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 w-[350px] pointer-events-none space-y-2"></div>

    <script>
        function showToast(msg, type = 'success') {
             const container = document.getElementById('toast-wrapper');
             if (!container) return;
             
             const toast = document.createElement('div');
             toast.className = `p-3.5 rounded-xl shadow-lg text-xs font-semibold flex items-center gap-2 transform transition-all duration-300 translate-y-[-10px] opacity-0 pointer-events-auto`;
             
             if (type === 'success') {
                  toast.className += ' bg-teal-800 text-teal-100 border border-teal-600';
             } else {
                  toast.className += ' bg-rose-800 text-rose-100 border border-rose-600';
             }
             
             toast.innerHTML = `<span>${msg}</span>`;
             container.appendChild(toast);
             
             setTimeout(() => {
                  toast.classList.remove('translate-y-[-10px]', 'opacity-0');
             }, 10);
             
             setTimeout(() => {
                  toast.classList.add('translate-y-[-10px]', 'opacity-0');
                  setTimeout(() => { toast.remove(); }, 300);
             }, 3500);
        }

        function togglePasswordView() {
             const input = document.getElementById('password');
             if (input.type === 'password') {
                  input.type = 'text';
             } else {
                  input.type = 'password';
             }
        }

        async function reloadCaptcha() {
             try {
                  const res = await fetch('/actions/auth/captcha-quest.php');
                  const data = await res.json();
                  document.getElementById('captcha-quest-field').innerText = data.captcha_quest;
             } catch (e) {
                  document.getElementById('captcha-quest-field').innerText = 'Err';
             }
        }

        // Initialize Captcha Load
        reloadCaptcha();

        // Submit form handler
        document.getElementById('login-form').addEventListener('submit', async function(e) {
             e.preventDefault();
             
             const payload = {
                  csrf_token: document.querySelector('input[name="csrf_token"]').value,
                  terms_agree: document.getElementById('terms').checked,
                  captcha_answer: document.getElementById('captcha').value,
                  username: document.getElementById('username').value,
                  password: document.getElementById('password').value
             };

             const btn = e.submitter || e.currentTarget.querySelector('button[type="submit"]');
             btn.disabled = true;
             btn.innerText = 'Memverifikasi...';

             try {
                  const res = await fetch('/actions/auth/login-action.php', {
                       method: 'POST',
                       headers: { 'Content-Type': 'application/json' },
                       body: JSON.stringify(payload)
                  });
                  const r = await res.json();
                  if (r.success) {
                       showToast('Kredensial valid! Menyiapkan dasbor Anda.', 'success');
                       setTimeout(() => {
                            window.location.href = '/pages/user/loading.php';
                       }, 1000);
                  } else {
                       showToast(r.error || 'Terjadi kesalahan login.', 'error');
                       reloadCaptcha();
                       document.getElementById('captcha').value = '';
                       btn.disabled = false;
                       btn.innerText = 'Masuk Sekarang';
                  }
             } catch (err) {
                  showToast('Gagal terhubung ke server VPS.', 'error');
                  reloadCaptcha();
                  btn.disabled = false;
                  btn.innerText = 'Masuk Sekarang';
             }
        });
    </script>
</body>
</html>
