<?php
// User Products Purchase catalogs view file
require_once __DIR__ . '/../../includes/header-helper.php';
require_once __DIR__ . '/../../includes/product-helper.php';
require_once __DIR__ . '/../../includes/settings-helper.php';

require_login();
$user_id = $_SESSION['user_id'];

// Fetch active products list grouped by sorting constraints
$products = get_active_products();
$csrf_token = generate_csrf_token();

render_header('Sewa Mesin Cloud Miner', true, 'products');
?>

<div class="space-y-4 fade-in">
     <!-- 1. Intro Card -->
     <div class="bg-gradient-to-br from-[#0F766E] to-[#2DD4BF] text-white rounded-2xl p-4 border border-[#B7D6D2]/20 text-left relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-white opacity-10"></div>
          <span class="block text-[10px] text-teal-300 font-mono tracking-widest block uppercase">Hardware Unggulan</span>
          <h2 class="font-display font-bold text-base leading-tight mt-0.5">SEWA CLOUD MINING HARDWARE</h2>
          <p class="text-[11px] text-teal-200 mt-1 pb-1 leading-relaxed">Pilih spesifikasi mesin penambangan server kami di bawah ini. Pendapatan dihitung penuh harian dan dialokasikan secara instan langsung ke Saldo Profit Anda setelah dihidupkan setiap harinya.</p>
     </div>

     <!-- Check product toggler settings -->
     <?php if (!is_feature_enabled('products')): ?>
          <div class="bg-rose-50 border border-rose-200 rounded-xl p-6 text-center py-12">
               <svg class="w-10 h-10 text-rose-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
               </svg>
               <h4 class="font-bold text-xs text-[#12302F]">Sewa Ditutup Sementara</h4>
               <p class="text-[11px] text-[#5B7774]">Administrator sedang merestrukturisasi unit persediaan mesin server hardware penambang.</p>
          </div>
     <?php else: ?>

          <!-- 2. Product List Loop -->
          <div class="space-y-3.5 text-left">
               <?php foreach ($products as $p): ?>
                    <div class="bg-white rounded-2xl border border-teal-100 p-4 shadow-[0_4px_12px_rgba(15,118,110,0.01)] flex flex-col justify-between hover:border-[#0F766E] transition-all relative">
                         <!-- Unit Tag Level or Type -->
                         <div class="absolute right-4 top-4">
                              <span class="bg-teal-50 border border-teal-200 text-teal-800 text-[9px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider">UNIT: <?php echo sanitize_output($p['category']); ?></span>
                         </div>

                         <div class="space-y-1 pr-14">
                              <h3 class="font-display font-bold text-sm text-[#12302F]"><?php echo sanitize_output($p['name']); ?></h3>
                              <p class="text-[10px] text-[#5B7774] leading-relaxed"><?php echo sanitize_output($p['details']); ?></p>
                         </div>

                         <!-- Highlight performance specs Grid -->
                         <div class="grid grid-cols-2 gap-3 bg-teal-50/20 p-2.5 rounded-xl border border-teal-50 border-dotted my-3 text-xs">
                              <div>
                                   <span class="block text-[9px] text-[#5B7774] font-medium leading-relaxed">Profit Harian:</span>
                                   <span class="block font-bold text-emerald-700 font-mono"><?php echo format_currency($p['profit_per_day']); ?></span>
                              </div>
                              <div>
                                   <span class="block text-[9px] text-[#5B7774] font-medium leading-relaxed">Masa Kontrak:</span>
                                   <span class="block font-bold text-[#12302F]"><?php echo $p['duration_days']; ?> Hari</span>
                              </div>
                              <div>
                                   <span class="block text-[9px] text-[#5B7774] font-medium leading-relaxed">Estimasi Total ROI:</span>
                                   <?php $roi = (float)$p['profit_per_day'] * (int)$p['duration_days']; ?>
                                   <span class="block font-bold text-teal-800 font-mono"><?php echo format_currency($roi); ?></span>
                              </div>
                              <div>
                                   <span class="block text-[9px] text-[#5B7774] font-medium leading-relaxed">Kondisi Stok:</span>
                                   <span class="block font-bold <?php echo ($p['stock'] > 0) ? 'text-emerald-600' : 'text-rose-600'; ?> font-mono">Sisa <?php echo $p['stock']; ?> Unit</span>
                              </div>
                         </div>

                         <!-- Dynamic Apply Diskon Voucher / Order button -->
                         <div class="flex items-center justify-between border-t border-teal-50 pt-3">
                              <div class="text-left">
                                   <span class="block text-[9px] text-[#5B7774] uppercase tracking-wider font-semibold">Harga Sewa Mesin</span>
                                   <span class="block font-display font-bold text-sm text-[#0F766E] font-mono leading-tight"><?php echo format_currency($p['price']); ?></span>
                              </div>
                              
                              <?php if ($p['stock'] > 0): ?>
                                   <button onclick="openPurchaseModal(<?php echo $p['id']; ?>, '<?php echo sanitize_output($p['name']); ?>', <?php echo (float)$p['price']; ?>)" class="h-9 px-4 bg-[#0F766E] hover:bg-teal-800 text-white rounded-lg text-xs font-bold transition-transform active:scale-95 shadow-sm">
                                        Sewa Unit Sekarang
                                   </button>
                              <?php else: ?>
                                   <button disabled class="h-9 px-4 bg-teal-100 text-teal-400 rounded-lg text-xs font-semibold cursor-not-allowed">
                                        Stok Habis
                                   </button>
                              <?php endif; ?>
                         </div>
                    </div>
               <?php endforeach; ?>
          </div>
     <?php endif; ?>
</div>

<!-- Modal checkout screen hidden by default logic overlay layout -->
<div id="checkout-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 flex items-end justify-center hidden">
     <div class="bg-white rounded-t-[24px] max-w-[480px] w-full p-6 text-left space-y-4 fade-in">
          <div class="flex justify-between items-center border-b border-teal-50 pb-2.5">
               <h3 class="font-display font-bold text-sm text-[#12302F]">Konfirmasi Persewaan Unit</h3>
               <button onclick="closePurchaseModal()" class="text-[#5B7774] focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
               </button>
          </div>

          <div class="space-y-1">
               <span class="text-[10px] text-[#5B7774] font-medium uppercase tracking-wider">Perangkat Terpilih:</span>
               <h4 id="modal-product-name" class="font-display font-bold text-[#12302F] text-sm">Hardware Name</h4>
               <div class="flex justify-between items-center text-xs pt-1">
                    <span class="text-[#5B7774]">Harga Normal:</span>
                    <span id="modal-product-price" class="font-bold text-[#0F766E] font-mono">Rp 0</span>
               </div>
          </div>

          <!-- Voucher Apply Code Block -->
          <div class="bg-teal-50/40 p-3 rounded-xl border border-teal-100 space-y-1.5 focus-within:border-[#0F766E]">
               <label class="block text-[10px] font-bold text-[#12302F] uppercase tracking-wider">Punya Voucher Diskon? (Masbukkan Kode)</label>
               <div class="flex gap-2">
                    <input id="checkout-voucher" type="text" class="flex-1 h-9 px-3 bg-white border border-teal-200 rounded-lg text-xs font-mono font-bold uppercase placeholder:font-sans placeholder-shown:font-normal" placeholder="nox_xxx / PROMO">
               </div>
          </div>

          <div class="bg-amber-50 border border-amber-200 p-3 rounded-xl text-[10px] text-amber-900 leading-relaxed">
               <strong>Ketentuan Saldo:</strong> Pembelian mesin akan memotong porsi <em>Saldo Bonus</em> Anda terlebih dahulu. Jika saldo bonus rujukan kosong/kurang, sisa harga sisa sewa didebit dari <em>Saldo Utama</em> Anda.
          </div>

          <div class="pt-2">
               <button id="modal-submit-btn" onclick="submitProductPurchase()" class="w-full h-11 bg-[#0F766E] hover:bg-teal-800 text-white font-bold rounded-lg text-xs transition-transform active:scale-95 shadow-md flex items-center justify-center">
                    Setujui Persewaan & Potong Saldo
               </button>
          </div>
     </div>
</div>

<script>
let currentProductId = null;
let currentProductPrice = 0;

function openPurchaseModal(id, name, price) {
     currentProductId = id;
     currentProductPrice = price;
     
     document.getElementById('modal-product-name').innerText = name;
     
     // Currency format
     const priceStr = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(price);
     document.getElementById('modal-product-price').innerText = priceStr;
     
     document.getElementById('checkout-voucher').value = '';
     
     const modal = document.getElementById('checkout-modal');
     modal.classList.remove('hidden');
}

function closePurchaseModal() {
     document.getElementById('checkout-modal').classList.add('hidden');
}

async function submitProductPurchase() {
     const voucher = document.getElementById('checkout-voucher').value.trim();
     const csrf_token = '<?php echo $csrf_token; ?>';
     
     const btn = document.getElementById('modal-submit-btn');
     btn.disabled = true;
     btn.innerText = 'Mengotomatisasikan Transaksi Ledger...';
     
     try {
          const res = await fetch('/actions/user/index.php', {
               method: 'POST',
               headers: {'Content-Type': 'application/json'},
               body: JSON.stringify({
                    action: 'buy_product',
                    csrf_token: csrf_token,
                    product_id: currentProductId,
                    voucher_code: voucher
               })
          });
          const r = await res.json();
          if (r.success) {
               showNotification(r.message || 'Sewa Miner Berhasil diaktifkan!', 'success');
               closePurchaseModal();
               setTimeout(() => {
                    window.location.href = '/pages/user/mining.php';
               }, 1500);
          } else {
               showNotification(r.error || 'Gagal checkout unit.', 'error');
               btn.disabled = false;
               btn.innerText = 'Setujui Persewaan & Potong Saldo';
          }
     } catch (e) {
          showNotification(' VPS Connection failed.', 'error');
          btn.disabled = false;
          btn.innerText = 'Setujui Persewaan & Potong Saldo';
     }
}
</script>

<?php
render_footer(true, 'products');
?>
