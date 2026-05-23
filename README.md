# NOXARA Premium Mobile-First Web-App Platform

NOXARA adalah platform penambangan awan (*cloud mining*) berkinerja tinggi komersial yang dirancang eksklusif dengan filosofi **mobile-first** beresolusi tinggi, didukung estetika elegan bertema **Teal Ocean Premium** dan keamanan sistem tangguh (*double-ledger immunity*).

---

## 🚀 Fitur Utama Sistem

1. **Mobile-First App Shell**: Memiliki nuansa layaknya aplikasi seluler Android/iOS asli dengan batasan kontainer optimal (Max Width: `430px` - `480px`) dan navigasi bawah bertekstur modern (*Teal Ocean*).
2. **Double-Ledger Financial System**: Kebal manipulasi saldo. Seluruh mutasi keuangan (Isi Ulang, Penarikan, Hasil Tambang, Rabat, dsb) tercatat secara berpasangan dalam buku besar (*Ledger Transaction*) dan diverifikasi secara kriptografis lewat validasi *Idempotency Keys*.
3. **Automated VIP Status Sync**: Tingkatan level VIP naik secara otomatis secara langsung menghitung total riwayat pengisian dana yang disetujui (Approved Topup), memberikan insentif pengurangan potongan biaya admin penarikan.
4. **Voucher System**: Mendukung klaim voucher bonus tunai langsung (Balance Claim), serta voucher diskon potongan pembelian mesin miner.
5. **Interactive VIP mini-games**: Menyediakan mini-game interaktif (Gosok Berhadiah, Puzzle Berwaktu, Hujan Koin) sebagai media pengklaiman *reward* interaktif harian.
6. **Live Chat Webs**: Sistem komunikasi langsung antara user dan administrator secara aman tanpa modul pihak ketiga. Support kirim file dokumen dan lampiran gambar.

---

## 🛠️ Stack Teknologi

- **Bahasa Utama**: PHP Native 8.2 (Dipadukan dengan PDO MySQL).
- **Database**: MySQL 8.x (InnoDB dengan penyandian `utf8mb4`).
- **Web Server**: Nginx, aaPanel, dan Let's Encrypt SSL.
- **Frontend**: Vanilla HTML5, Tailwind CSS modern terintegrasi, Vanilla JavaScript ES6 (Tanpa ketergantungan framework Next/React/Svelte/Laravel).

---

## 📂 Struktur Direktori Real

Direktori platform disusun secara modular untuk menjaga pembagian tugas kode secara rapi:

- `/config/` : Berisi setelan krusial database (`database.php`) serta konfigurasi dasar sistem (`app.php`).
- `/includes/` : Berisi seluruh modul logika pendukung yang modular (Mekanisme VIP, Voucher, Ledgers, dll).
- `/cron/` : Skrip cron yang berjalan periodik via CLI PHP aaPanel (untuk pembagian profit, kedaluwarsa produk, sinkronisasi status, dsb).
- `/pages/` : Render tampilan antarmuka terbagi menjadi user, guest (auth), admin, dan public/error/maintenance.
- `/actions/` : Skrip endpoints backend AJAX yang bertugas mengambil, mengubah, dan memvalidasi perintah masukan.
- `/uploads/` : Folder penyimpanan unggahan statis (banner promosi, qris, lampiran chat). Bebas dari pengeksekusian perintah PHP.
- `/nginx/` : Aturan konfigurasi keamanan Nginx aaPanel.

---

## 💎 Keamanan Tambahan & Pencegahan Eksploitasi

- **Pencegahan Upload PHP Disguise**: Menggunakan pengecekan ketat ekstensi file dikombinasikan dengan pembacaan MIME biner asli (`finfo_file`).
- **PHP Execution Block**: Aturan Nginx memblokir segala bentuk pemanggilan file berekstensi `.php` di bawah folder `/uploads/`.
- **Sensitif Area Deny list**: Folder seperti `config`, `includes`, `actions`, `cron`, `storage` ditutup total dari akses peramban internet (browser), mengembalikan respons `403 Forbidden` langsung dari Nginx.
- **Form Hijacking Defenses**: Seluruh penyerahan formulir data diproteksi token `CSRF` dengan validasi algoritma perbandingan string konstan ketat (`hash_equals`).
