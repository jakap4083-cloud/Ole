<?php
// User Profile view layout screen
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/vip-helper.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user_id = $_SESSION['user_id'];

// Get user profile detail
$db = get_db_connection();
$stmt = $db->prepare("SELECT * FROM user_profiles WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

$stmt_user = $db->prepare("SELECT username, email, phone_number, created_at FROM users WHERE id = ? LIMIT 1");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch();

$vip = get_user_vip_details($user_id);
?>

<div class="space-y-4 fade-in">
     <!-- 1. Beautiful VIP Badge & Card container detail header -->
     <div class="bg-gradient-to-br from-[#0F766E] to-[#2DD4BF] text-white rounded-2xl p-5 border border-[#B7D6D2]/20 text-left relative overflow-hidden">
          <div class="absolute -right-16 -top-16 w-36 h-36 rounded-full bg-white opacity-10"></div>
          <div class="absolute -left-12 -bottom-12 w-28 h-28 rounded-full bg-white opacity-10"></div>

          <div class="flex items-center gap-3.5 relative z-10">
               <div class="w-14 h-14 rounded-full bg-teal-850 border-2 border-teal-400 flex items-center justify-center text-teal-200 font-display font-bold text-lg select-none">
                    <?php echo strtoupper(substr($user_data['username'], 0, 1)); ?>
               </div>
               <div class="space-y-0.5">
                    <h2 class="text-base font-bold font-display text-white leading-tight"><?php echo sanitize_output($user_data['username']); ?></h2>
                    <span class="block text-[10px] text-teal-300 font-mono"><?php echo sanitize_output($user_data['phone_number']); ?></span>
                    <span class="block text-[9px] text-[#2DD4BF] font-mono">MITRA ID: #<?php echo $user_id; ?></span>
               </div>
          </div>

          <div class="mt-4 pt-3.5 border-t border-teal-800 flex justify-between items-center text-[10px] text-teal-200 font-mono relative z-10">
               <span>Tanggal Bergabung:</span>
               <span class="text-white font-bold"><?php echo date('d-m-Y', strtotime($user_data['created_at'])); ?></span>
          </div>
     </div>

     <!-- 2. Main functional profile navigations list link categories group -->
     <div class="bg-white border border-teal-100 rounded-2xl p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] text-left space-y-1">
          
          <!-- Bank Account linking -->
          <a href="/pages/user/bank.php" class="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors">
               <div class="flex items-center gap-3 text-teal-900">
                    <svg class="w-5 h-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <span class="text-xs font-bold text-[#12302F]">Tautkan Rekening Bank</span>
               </div>
               <svg class="w-4 h-4 text-[#5B7774]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
               </svg>
          </a>

          <!-- Transaction PIN configuration -->
          <a href="/pages/user/pin.php" class="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors">
               <div class="flex items-center gap-3 text-teal-900">
                    <svg class="w-5 h-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m-2-2a2 2 0 00-2-2m2 2a2 2 0 012 2m0 0a2 2 0 01-2 2m0-2a2 2 0 00-2 2m2-2a2 2 0 012 2m0 0V19a2 2 0 01-2 2h-6a2 2 0 01-2-2V9a2 2 0 012-2h2m0 0h2"></path>
                    </svg>
                    <span class="text-xs font-bold text-[#12302F]">PIN Transaksi Transaksi</span>
               </div>
               <svg class="w-4 h-4 text-[#5B7774]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
               </svg>
          </a>

          <!-- VIP Level Scheme Info -->
          <a href="/pages/public/info.php?cat=vip_scheme" class="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors">
               <div class="flex items-center gap-3 text-teal-900">
                    <svg class="w-5 h-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                    <span class="text-xs font-bold text-[#12302F]">Rencana & Insentif VIP</span>
               </div>
               <div class="flex items-center gap-1.5">
                    <span class="text-[9px] bg-amber-500 text-white font-bold px-1.5 py-0.5 rounded uppercase font-mono tracking-wider"><?php echo sanitize_output($vip['name']); ?></span>
                    <svg class="w-4 h-4 text-[#5B7774]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>
               </div>
          </a>

          <!-- About Platform / Terms of Services -->
          <a href="/pages/public/info.php?cat=faq" class="flex items-center justify-between p-3 rounded-xl hover:bg-teal-50/50 transition-colors">
               <div class="flex items-center gap-3 text-teal-900">
                    <svg class="w-5 h-5 text-teal-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-xs font-bold text-[#12302F]">Pusat Bantuan & FAQ</span>
               </div>
               <svg class="w-4 h-4 text-[#5B7774]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
               </svg>
          </a>
     </div>

     <!-- Logout Container Button -->
     <div class="pt-2">
          <a href="/actions/user/logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari akun NOXARA Anda?')" class="w-full h-11 bg-rose-50 border border-rose-200 text-rose-700 hover:bg-rose-100 rounded-xl text-xs font-bold transition-transform active:scale-95 flex items-center justify-center gap-2">
               <!-- Logout custom SVG -->
               <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
               </svg>
               Keluar Sesi Akun
          </a>
     </div>
</div>

<?php
render_footer(true, 'profile');
?>
