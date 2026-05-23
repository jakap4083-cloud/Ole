<?php
// User Payment QRIS Scanner view page (Displays generated QR code and monitors lunas real-time)
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/db.php';

require_login();
$user_id = $_SESSION['user_id'];

$topup_id = (int)($_GET['id'] ?? 0);

$db = get_db_connection();
$stmt = $db->prepare("SELECT * FROM topups WHERE id = ? AND user_id = ? LIMIT 1");
$stmt->execute([$topup_id, $user_id]);
$topup = $stmt->fetch();

if (!$topup) {
     header('Location: /pages/user/transactions.php');
     exit();
}

$csrf_token = generate_csrf_token();
?>

<div class="space-y-4 fade-in">
     <!-- 1. Top guide box -->
     <div class="bg-teal-900 border border-teal-850 rounded-2xl p-4 text-white text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Sistem Lunas Otomatis</span>
          <h2 class="font-display font-bold text-base mt-0.5">PINDAI QRIS UNTUK MEMBAYAR</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Silakan simpan gambar QRIS di bawah ini, lalu unggah/buka pada aplikasi e-wallet pembayaran Anda (Gopay, OVO, Dana, LinkAja, BCA, ShopeePay dll). JANGAN UBAH NOMINAL AGAR SISTEM MEMVERIFIKASI TRANSAKSI.</p>
     </div>

     <!-- 2. Main Ticket Info Details -->
     <div class="bg-white rounded-2xl p-5 border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.02)] space-y-4">
          <div class="text-center">
               <span class="block text-[10px] text-[#5B7774] uppercase tracking-wider font-semibold">Total Invoice Pembayaran</span>
               <span class="block font-display font-bold text-xl text-[#0F766E] font-mono leading-tight mt-0.5" id="ticket-total-amt">
                    <?php echo format_currency($topup['total_amount']); ?>
               </span>
               <div class="inline-block mt-2 bg-amber-50 rounded px-2.5 py-1 text-[9px] font-mono font-semibold text-amber-800 border border-amber-200">
                    Termasuk Kode Unik Cents: Rp <?php echo $topup['unique_nominal']; ?>
               </div>
          </div>

          <!-- Display Real generated QRIS string on canvas visual rendering using native google api qr helper -->
          <?php if (!empty($topup['qr_string'])): ?>
               <div class="bg-slate-50 p-4 border border-teal-100 rounded-xl flex flex-col items-center justify-center">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=<?php echo urlencode($topup['qr_string']); ?>&color=0f766e" alt="QRIS QR Code scanner" class="w-56 h-56 rounded border border-teal-100 bg-white" referrerPolicy="no-referrer">
                    <span class="block text-[10px] text-[#5B7774] font-medium mt-3 uppercase tracking-wide">Pindai / Scan QRIS Resmi Cashify V2</span>
               </div>
          <?php else: ?>
               <div class="bg-rose-50 border border-rose-200 p-6 rounded-xl text-center py-10">
                    <p class="text-xs text-rose-800 leading-relaxed font-semibold">QRIS string kosong dari provider Cashify.</p>
               </div>
          <?php endif; ?>

          <div class="bg-teal-50 border border-teal-200 p-3 rounded-xl text-center font-mono">
               <span class="block text-[9px] text-[#5B7774] uppercase tracking-wider">Status Pembayaran Tiket</span>
               <div class="flex items-center justify-center gap-1.5 mt-1">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse" id="status-bulb"></span>
                    <span class="text-xs font-bold text-teal-900 uppercase" id="status-label">Menunggu Pelunasan...</span>
               </div>
          </div>

          <div class="grid grid-cols-2 gap-3 text-center text-xs">
               <button onclick="cancelPendingTopup()" id="cancel-btn" class="h-10 border border-teal-200 hover:bg-rose-50 text-rose-600 rounded-xl font-bold transition-transform active:scale-95">
                    Batalkan Tiket
               </button>
               <a href="/pages/user/transactions.php" class="h-10 bg-teal-50 border border-teal-200 text-[#0F766E] rounded-xl font-bold flex items-center justify-center hover:bg-teal-100 transition-transform active:scale-95">
                    Riwayat Transaksi
               </a>
          </div>
     </div>
</div>

<script>
const topupId = <?php echo $topup_id; ?>;
const csrf = '<?php echo $csrf_token; ?>';

// Live polling system that checks backend complete webhook status on interval of 4000ms
const pollTimer = setInterval(async () => {
    try {
        const res = await fetch('/actions/user/index.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'check_deposit_status',
                csrf_token: csrf,
                topup_id: topupId
            })
        });
        const d = await res.json();
        if (d.success) {
            if (d.status === 'success') {
                 clearInterval(pollTimer);
                 
                 const bulb = document.getElementById('status-bulb');
                 bulb.className = 'inline-block w-2.5 h-2.5 rounded-full bg-emerald-600';
                 
                 const label = document.getElementById('status-label');
                 label.className = 'text-xs font-bold text-emerald-700 uppercase';
                 label.innerText = 'Sudah Terbayarkan!';
                 
                 showNotification('Selamat! Pembayaran Isi Ulang Anda Terkonfirmasi Lunas.', 'success');
                 
                 // Move forward
                 setTimeout(() => {
                      window.location.href = '/pages/user/home.php';
                 }, 2000);
            } else if (d.status === 'cancel' || d.status === 'expired') {
                 clearInterval(pollTimer);
                 document.getElementById('status-bulb').className = 'inline-block w-2.5 h-2.5 rounded-full bg-rose-600';
                 const label = document.getElementById('status-label');
                 label.className = 'text-xs font-bold text-rose-700 uppercase';
                 label.innerText = d.status === 'cancel' ? 'Dibatalkan' : 'Kedaluwarsa (Expired)';
                 showNotification('Sesi transaksi dibatalkan atau habis batas waktunya.', 'error');
            }
        }
    } catch (e) {
        // preserve fail silences during transient offline network drops
    }
}, 4000);

async function cancelPendingTopup() {
     if (!confirm('Apakah Anda yakin mau membatalkan pengajuan isi ulang saldo ini?')) return;
     
     clearInterval(pollTimer);
     
     try {
         const res = await fetch('/actions/user/index.php', {
             method: 'POST',
             headers: {'Content-Type': 'application/json'},
             body: JSON.stringify({
                 action: 'cancel_deposit',
                 csrf_token: csrf,
                 topup_id: topupId
             })
         });
         const data = await res.json();
         if (data.success) {
              showNotification('Tiket sukses dideaktivasi.', 'success');
              setTimeout(() => {
                   window.location.href = '/pages/user/transactions.php';
              }, 1200);
         } else {
              showNotification(data.error || 'Gagal dibatalkan.', 'error');
         }
     } catch (e) {
          showNotification(' VPS Connection failed.', 'error');
     }
}
</script>

<?php
render_footer(true, 'transactions');
?>
