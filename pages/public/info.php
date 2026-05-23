<?php
// Public informational details page (VIP schemes or terms FAQs)
require_once __DIR__ . '/../../includes/header-helper.php';

require_login();
$cat = $_GET['cat'] ?? 'faq';

render_header('Informasi NOXARA', true, 'profile');
?>

<div class="space-y-4 text-left fade-in">
     <!-- 1. Header Banner -->
     <div class="bg-teal-900 text-white rounded-2xl p-4 border border-teal-850 relative overflow-hidden">
          <div class="absolute -right-16 -bottom-16 w-32 h-32 rounded-full bg-teal-800 opacity-20"></div>
          <span class="block text-[9px] font-mono font-bold text-teal-300 tracking-wider uppercase">Pusat Informasi</span>
          <h2 class="font-display font-bold text-base mt-0.5">MANUAL & PETUNJUK RESMI</h2>
          <p class="text-[10px] text-teal-200 mt-1 leading-relaxed">Panduan lengkap mengenai tata tertib pengoperasian perangkat hardware penambangan koin server sewaan Noxara Page.</p>
     </div>

     <!-- 2. Rich structured layout according to category selection -->
     <div class="bg-white rounded-2xl p-5 border border-teal-100 shadow-[0_4px_16px_rgba(15,118,110,0.01)] space-y-4">
          <?php if ($cat === 'vip_scheme'): ?>
               <h3 class="font-display font-bold text-sm text-[#12302F] border-b border-teal-50 pb-2">Rincian Insentif Rencana Tingkatan VIP</h3>
               
               <div class="space-y-3.5 text-xs">
                    <!-- VIP 0 -->
                    <div class="bg-teal-50/20 p-3 rounded-xl border border-teal-50 space-y-1">
                         <div class="flex justify-between items-center">
                              <strong class="text-teal-905 font-bold">LEVEL VIP 0 (MEMBER BIASA)</strong>
                              <span class="text-[9px] bg-teal-105 border border-teal-200 text-teal-800 font-bold px-1.5 py-0.5 rounded font-mono">Gratis</span>
                         </div>
                         <p class="text-[11px] text-[#5B7774] leading-relaxed">Syarat: Nilai isi ulang minimum Rp 0.<br>Manfaat: Biaya penarikan dana denda 10%, Batas penarikan minimal Rp 50.000, Kuota games harian 0 kali.</p>
                    </div>

                    <!-- VIP 1 -->
                    <div class="bg-amber-50/20 p-3 rounded-xl border border-amber-100 space-y-1">
                         <div class="flex justify-between items-center">
                              <strong class="text-amber-900 font-bold">LEVEL VIP 1 (INVESTOR PEMULA)</strong>
                              <span class="text-[9px] bg-amber-500 text-white font-bold px-1.5 py-0.5 rounded font-mono">Rp 50.000</span>
                         </div>
                         <p class="text-[11px] text-[#5B7774] leading-relaxed">Syarat: Nilai kumulatif pengisian saldo minimum Rp 50.000.<br>Manfaat: Biaya penarikan dana menurun jadi 8%, Batas penarikan minimal Rp 30.000, Mendapat jatah kuota games harian 2 kali.</p>
                    </div>

                    <!-- VIP 2 -->
                    <div class="bg-teal-900 border border-teal-950 p-3 rounded-xl text-white space-y-1">
                         <div class="flex justify-between items-center">
                              <strong class="text-white font-bold">LEVEL VIP 2 (PARTNER UTAMA)</strong>
                              <span class="text-[9px] bg-[#2DD4BF] text-teal-950 font-bold px-1.5 py-0.5 rounded font-mono">Rp 500.000</span>
                         </div>
                         <p class="text-[11px] text-teal-200 leading-relaxed">Syarat: Nilai kumulatif pengisian saldo minimum Rp 500.000.<br>Manfaat: Biaya penarikan s.d denda 5% saja, Batas penarikan minimal menurun Rp 20.000, Jatah kuota games harian dtingkatkan s.d 5 kali.</p>
                    </div>
               </div>

          <?php else: ?>
               <!-- DEFAULT FAQ LAYOUTS -->
               <h3 class="font-display font-bold text-sm text-[#12302F] border-b border-teal-50 pb-2">Pertanyaan Sering Diajukan (FAQ)</h3>
               
               <div class="space-y-4 text-xs font-sans">
                    <!-- Q1 -->
                    <div class="space-y-1">
                         <h4 class="font-bold text-[#12302F]">S1: Apakah penambangan komputasi harus dinyalakan manual harian?</h4>
                         <p class="text-[11px] text-[#5B7774] leading-relaxed">Benar. Investor harus menekan tombol "Putar Sesi 24 Jam" di Control Room halaman pertambangan harian sekali setiap harinya setelah waktu reset untuk melahirkan profit. Batas klaim dapat dilakukan secara mandiri.</p>
                    </div>

                    <!-- Q2 -->
                    <div class="space-y-1">
                         <h4 class="font-bold text-[#12302F]">S2: Bagaimana proses pembayaran QRIS diproses otomatis?</h4>
                         <p class="text-[11px] text-[#5B7774] leading-relaxed">Sistem database Noxara Page bermitra langsung dengan API Cashify mutakhir. Setiap kali pengajuan tiket diisi, lunas QRIS dideteksi secara langsung oleh server webhook, otomatis mengkreditkan saldo secara instant tanpa transfer manual.</p>
                    </div>

                    <!-- Q3 -->
                    <div class="space-y-1">
                         <h4 class="font-bold text-[#12302F]">S3: Mengapa Saldo Utama dan Saldo Bonus didebit terpisah?</h4>
                         <p class="text-[11px] text-[#5B7774] leading-relaxed">Saldo Bonus (Event rujukan/voucher tunai) dialokasikan khusus sebagai pemotong instan biaya persewaan produk. Hal ini guna memastikan kelancaran perputaran modal buku besar audit kaku.</p>
                    </div>
               </div>
          <?php endif; ?>
     </div>

     <div class="pt-2">
          <a href="/pages/user/profile.php" class="w-full h-11 bg-teal-50 border border-teal-200 text-[#0F766E] font-bold rounded-xl text-xs flex items-center justify-center hover:bg-teal-100 transition-transform active:scale-95">
               Kembali ke Profil Saya
          </a>
     </div>
</div>

<?php
render_footer(true, 'profile');
?>
