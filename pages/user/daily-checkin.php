<?php
// User Absen Absensi (Daily Check-in Reset 7 days) view page
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user_id = $_SESSION['user_id'];
$csrf_token = generate_csrf_token();

// Fetch daily bonus settings configurations
$db = get_db_connection();
$stmt = $db->query("SELECT * FROM daily_bonus_settings WHERE is_active = 1 ORDER BY day_num ASC");
$days_presets = $stmt->fetchAll();

// Fetch last claim date for user
$stmt_check = $db->prepare("SELECT day_num, claimed_date FROM daily_bonus_claims WHERE user_id = ? ORDER BY claimed_date DESC LIMIT 1");
$stmt_check->execute([$user_id]);
$last_claim = $stmt_check->fetch();

$today = date('Y-m-d');
$has_claimed_today = false;
$next_expected_day = 1;

if ($last_claim) {
     if ($last_claim['claimed_date'] === $today) {
          $has_claimed_today = true;
          $next_expected_day = $last_claim['day_num']; // current day already done
     } else {
          $last_day_val = (int)$last_claim['day_num'];
          $next_expected_day = ($last_day_val >= 7) ? 1 : ($last_day_val + 1);
     }
}
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top visual guide header -->
     <div class="bg-teal-900 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Hadiah Loyalitas</span>
          <h2 class="font-display font-bold text-base mt-0.5">BONUS PRESENSI HARIAN ABSEN</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Dapatkan tunjangan tunai gratis presensi harian masuk secara langsung ke Saldo Utama Anda. Klaim bonus Anda setiap hari sekuensial dari hari ke-1 s.d ke-7. Jika absen terputus, urutan akan diatur ulang kembali ke Hari ke-1.</p>
     </div>

     <!-- 2. Absen Calendar Steps grid -->
     <div class="bg-white rounded-2xl p-5 border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.02)] space-y-4 text-left">
          <div class="border-b border-teal-50 pb-2 flex justify-between items-center text-xs">
               <h3 class="font-display font-bold text-teal-900">Ulasan Kalender Absensi 7 Hari</h3>
               <span class="text-[10px] text-[#5B7774] font-medium">Batas reset harian: 00:00 WIB</span>
          </div>

          <div class="grid grid-cols-4 gap-2.5 text-center text-xs">
               <?php foreach ($days_presets as $dp): ?>
                    <?php
                         $day_num = (int)$dp['day_num'];
                         $is_completed = false;
                         $is_active_target = false;
                         
                         if ($last_claim) {
                              $last_day_num = (int)$last_claim['day_num'];
                              if ($has_claimed_today) {
                                   if ($day_num <= $last_day_num) {
                                        $is_completed = true;
                                   }
                              } else {
                                   if ($day_num < $next_expected_day) {
                                        $is_completed = true;
                                   } elseif ($day_num == $next_expected_day) {
                                        $is_active_target = true;
                                   }
                              }
                         } else {
                              if ($day_num === 1) {
                                   $is_active_target = true;
                              }
                         }
                    ?>
                    
                    <div class="p-2.5 rounded-xl border flex flex-col justify-between h-20 relative <?php 
                         if ($is_completed) {
                              echo 'bg-emerald-50 border-emerald-200 text-emerald-800';
                         } elseif ($is_active_target) {
                              echo 'bg-teal-50 border-teal-300 text-teal-900 font-bold shadow-[0_0_8px_rgba(15,118,110,0.15)] ring-2 ring-[#0F766E]/20';
                         } else {
                              echo 'bg-slate-50 border-slate-100 text-slate-400';
                         }
                    ?>">
                         <span class="block text-[10px] font-bold">D-<?php echo $day_num; ?></span>
                         <span class="block text-[9px] font-mono font-black py-0.5"><?php echo number_format($dp['reward_amount'] / 1000); ?>K</span>
                         
                         <div class="mt-1">
                              <?php if ($is_completed): ?>
                                   <span class="text-[8px] uppercase font-bold text-emerald-600 block">Klaim</span>
                              <?php elseif ($is_active_target && !$has_claimed_today): ?>
                                   <span class="text-[8px] uppercase font-bold text-teal-800 animated pulse block">Siap</span>
                              <?php else: ?>
                                   <span class="text-[8px] uppercase font-semibold block">Nanti</span>
                              <?php endif; ?>
                         </div>
                    </div>
               <?php endforeach; ?>
          </div>

          <!-- Check Action buttons based on status locks -->
          <div class="pt-3 border-t border-teal-50">
               <?php if ($has_claimed_today): ?>
                    <button disabled class="w-full h-11 bg-teal-100 text-teal-400 border border-teal-200 rounded-xl text-xs font-bold cursor-not-allowed">
                         Sudah Absen Hari Ini (Kembali Besok)
                    </button>
               <?php else: ?>
                    <button onclick="triggerAbsenCheckin(<?php echo $next_expected_day; ?>)" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-bold rounded-xl text-xs shadow-md transition-transform active:scale-95 flex items-center justify-center gap-2">
                         Ambil Bonus Presensi Day <?php echo $next_expected_day; ?>
                    </button>
               <?php endif; ?>
          </div>
     </div>
</div>

<script>
async function triggerAbsenCheckin(day_num) {
     const csrf = '<?php echo $csrf_token; ?>';
     
     try {
          const res = await fetch('/actions/user/index.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({
                    action: 'claim_daily_bonus',
                    csrf_token: csrf,
                    day_num: day_num
               })
          });
          const d = await res.json();
          if (d.success) {
               showNotification(d.message || 'Presensi Sukses!', 'success');
               setTimeout(() => { window.location.reload(); }, 1500);
          } else {
               showNotification(d.error || 'Gagal absen.', 'error');
          }
     } catch (e) {
          showNotification('VPS Timeout.', 'error');
     }
}
</script>

<?php
render_footer(true, 'home');
?>
