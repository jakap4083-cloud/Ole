<?php
// Secure User Registration UI Screen
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
$ref_prefill = sanitize_input($_GET['ref'] ?? '');

$banners = [];
try {
     $db = get_db_connection();
     // Fetch active promo banners if any
     $stmt = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC, id DESC");
     $banners = $stmt->fetchAll();
} catch (Exception $e) {}

if (empty($banners)) {
     $banners = [
         [
             'title' => 'Promo VIP Member Baru',
             'image_url' => 'nox_banner_vip_bonus.png', // placeholder asset
             'link_url' => '/pages/public/info.php?cat=vip_scheme',
             'details' => 'Dapatkan saldo bonus selamat datang gratis Rp 10.000 setelah berhasil mendaftar!'
         ]
     ];
}
?>
<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Daftar Akun Baru | NOXARA</title>
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
         
         <div class="text-center my-4">
              <div class="inline-flex items-center justify-center bg-teal-800 text-white w-12 h-12 rounded-2xl shadow-md border-2 border-teal-400 mb-2">
                   <svg class="w-7 h-7 text-teal-200" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                   </svg>
              </div>
              <h1 class="font-display text-xl font-bold tracking-tight text-[#0F766E]">Pendaftaran Anggota</h1>
              <p class="text-xs text-[#5B7774]">Gabung komunitas penambang awan premium</p>
         </div>

         <!-- Check Register toggles -->
         <?php if (!is_feature_enabled('register')): ?>
              <div class="bg-rose-50 border border-rose-200 p-5 rounded-2xl text-center py-10 my-8">
                   <svg class="w-12 h-12 text-rose-500 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                   </svg>
                   <h3 class="font-bold text-sm text-[#12302F] mb-1">Pendaftaran Ditutup Sementara</h3>
                   <p class="text-xs text-[#5B7774] leading-relaxed">Administrator menonaktifkan pembuatan akun baru untuk program pemeliharaan terjadwal. Hubungi CS WhatsApp untuk bantuan.</p>
                   <a href="/pages/auth/login.php" class="inline-flex mt-4 text-xs font-semibold text-[#0F766E] border border-[#0F766E] rounded-lg px-4 py-2 hover:bg-teal-50">Kembali ke Login</a>
              </div>
         <?php else: ?>

              <!-- Registration active. Render Form. -->
              <form id="register-form" class="space-y-3 bg-white p-5 rounded-2xl border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.03)] fade-in">
                   <?php echo csrf_field(); ?>
                   
                   <!-- Username -->
                   <div>
                        <label class="block text-[11px] font-semibold text-[#12302F] mb-1 uppercase tracking-wider">Username</label>
                        <input id="username" type="text" required class="w-full h-11 px-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-xs font-medium" placeholder="4-20 karakter alfanumerik">
                   </div>

                   <!-- Email -->
                   <div>
                        <label class="block text-[11px] font-semibold text-[#12302F] mb-1 uppercase tracking-wider">Email Terdaftar</label>
                        <input id="email" type="email" required class="w-full h-11 px-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-xs" placeholder="nama@email.com">
                   </div>

                   <!-- Phone Number -->
                   <div>
                        <label class="block text-[11px] font-semibold text-[#12302F] mb-1 uppercase tracking-wider">Nomor Handphone (Aktif)</label>
                        <div class="relative">
                             <span class="absolute left-4 top-3 text-xs text-teal-600 font-bold font-mono">+62</span>
                             <input id="phone" type="tel" required class="w-full h-11 pl-12 pr-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-xs font-mono font-medium" placeholder="81234567xxx">
                        </div>
                   </div>

                   <!-- Passwords grid -->
                   <div class="grid grid-cols-2 gap-3">
                        <div>
                             <label class="block text-[11px] font-semibold text-[#12302F] mb-1 uppercase tracking-wider">Sandi</label>
                             <input id="password" type="password" required class="w-full h-11 px-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-xs" placeholder="Min. 8 karakter">
                        </div>
                        <div>
                             <label class="block text-[11px] font-semibold text-[#12302F] mb-1 uppercase tracking-wider">Konfirmasi Sandi</label>
                             <input id="password_confirm" type="password" required class="w-full h-11 px-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-xs" placeholder="Ketik ulang">
                        </div>
                   </div>

                   <!-- Optional Referral Code field -->
                   <div>
                        <label class="block text-[11px] font-semibold text-[#12302F] mb-1 uppercase tracking-wider">Kode Referensi / Pengundang (Opsional)</label>
                        <input id="referrer" type="text" class="w-full h-11 px-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-xs font-mono font-bold tracking-wider text-teal-800" placeholder="Ketik kode jika ada" value="<?php echo $ref_prefill; ?>">
                   </div>

                   <!-- Mathematics Captcha -->
                   <div>
                        <label class="block text-[11px] font-semibold text-[#12302F] mb-1 uppercase tracking-wider">Verifikasi Keamanan</label>
                        <div class="grid grid-cols-2 gap-3">
                             <div class="h-11 bg-teal-50 border border-teal-200 rounded-xl flex items-center justify-center font-mono font-bold text-teal-800 text-xs" id="captcha-quest-field">
                                  Memuat...
                             </div>
                             <input id="captcha" type="number" required class="w-full h-11 px-4 rounded-xl border border-teal-200 focus:outline-none focus:border-[#0F766E] text-xs text-center font-mono font-bold" placeholder="Hasil">
                        </div>
                   </div>

                   <!-- Checkbox Terms -->
                   <div class="flex items-start gap-2.5 pt-1">
                        <input id="terms" type="checkbox" required class="mt-0.5 w-4 h-4 rounded text-teal-700 bg-teal-50 border-teal-200 focus:ring-teal-700">
                        <label for="terms" class="text-[10px] text-[#5B7774] leading-relaxed">Saya menyatakan telah menyetujui <a href="/pages/public/info.php?cat=tos" class="text-[#0F766E] font-semibold underline">Syarat & Ketentuan</a> dan <a href="/pages/public/info.php?cat=privacy" class="text-[#0F766E] font-semibold underline">Kebijakan Privasi</a> NOXARA.</label>
                   </div>

                   <!-- Action buttons -->
                   <div class="pt-2">
                        <button type="submit" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-semibold rounded-xl text-xs transition-transform active:scale-[0.98] shadow-md">
                             Selesaikan Pendaftaran
                        </button>
                   </div>

                   <div class="text-center text-xs pt-2.5 border-t border-teal-50">
                        <a href="/pages/auth/login.php" class="text-[#0F766E] font-semibold hover:underline">Sudah punya akun? Masuk</a>
                   </div>
              </form>
         <?php endif; ?>

         <!-- Promo Slider Banner Display -->
         <div class="mt-5 bg-teal-900 rounded-2xl p-4 text-white relative overflow-hidden border border-teal-800">
              <h3 class="font-display text-xs font-bold text-teal-200 mb-1 border-b border-teal-800 pb-1 uppercase tracking-wider">Penawaran Menarik Saat Ini</h3>
              <?php foreach ($banners as $banner): ?>
                   <div class="fade-in space-y-1">
                        <p class="text-xs font-bold text-teal-100"><?php echo sanitize_output($banner['title']); ?></p>
                        <p class="text-[10px] text-teal-300 leading-relaxed"><?php echo sanitize_output($banner['details'] ?? ''); ?></p>
                        <a href="<?php echo sanitize_output($banner['link_url']); ?>" class="inline-block text-[9px] text-[#2DD4BF] font-semibold hover:underline">Baca Ketentuan & Skema Bonus VIP →</a>
                   </div>
              <?php endforeach; ?>
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
             toast.className = `p-3 outline-none rounded-xl shadow-lg text-xs font-semibold flex items-center gap-2 transform transition-all duration-300 translate-y-[-10px] opacity-0 pointer-events-auto`;
             
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
        if (document.getElementById('captcha-quest-field')) {
             reloadCaptcha();
        }

        // Register form handler
        const regForm = document.getElementById('register-form');
        if (regForm) {
             regForm.addEventListener('submit', async function(e) {
                  e.preventDefault();
                  
                  const payload = {
                       csrf_token: document.querySelector('input[name="csrf_token"]').value,
                       terms_agree: document.getElementById('terms').checked,
                       captcha_answer: document.getElementById('captcha').value,
                       username: document.getElementById('username').value,
                       email: document.getElementById('email').value,
                       // Ensure indonesia format phone starts with numeric 62 instead of zero
                       phone: '62' + document.getElementById('phone').value,
                       password: document.getElementById('password').value,
                       password_confirmation: document.getElementById('password_confirm').value,
                       referrer_code: document.getElementById('referrer').value
                  };

                  const btn = e.submitter || e.currentTarget.querySelector('button[type="submit"]');
                  btn.disabled = true;
                  btn.innerText = 'Pendaftaran diproses...';

                  try {
                       const res = await fetch('/actions/auth/register-action.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                       });
                       const r = await res.json();
                       if (r.success) {
                            showToast('Pendaftaran Berhasil! Selamat datang di NOXARA. Silakan login.', 'success');
                            setTimeout(() => {
                                 window.location.href = '/pages/auth/login.php';
                            }, 1500);
                       } else {
                            showToast(r.error || 'Gagal mendaftar.', 'error');
                            reloadCaptcha();
                            document.getElementById('captcha').value = '';
                            btn.disabled = false;
                            btn.innerText = 'Selesaikan Pendaftaran';
                       }
                  } catch (err) {
                       showToast('Gagal mendaftar ke server VPS.', 'error');
                       reloadCaptcha();
                       btn.disabled = false;
                       btn.innerText = 'Selesaikan Pendaftaran';
                  }
             });
        }
    </script>
</body>
</html>
