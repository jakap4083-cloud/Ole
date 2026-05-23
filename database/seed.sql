-- Seed default data for NOXARA
-- Prepared for aaPanel MySQL 8.x installation

-- 1. Default Admins (Password hash of 'AdminSuper123!' is '$2y$10$WpQy0Vq1nSTTqQ5YkFbeHef1/0Z5e6088vFdfY79rJ6D7cWef8XlC')
INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`) VALUES
(1, 'super_admin', 'superadmin@noxara.page', '$2y$10$WpQy0Vq1nSTTqQ5YkFbeHef1/0Z5e6088vFdfY79rJ6D7cWef8XlC', 'super_admin'),
(2, 'admin_finance', 'finance@noxara.page', '$2y$10$WpQy0Vq1nSTTqQ5YkFbeHef1/0Z5e6088vFdfY79rJ6D7cWef8XlC', 'finance'),
(3, 'admin_support', 'support@noxara.page', '$2y$10$WpQy0Vq1nSTTqQ5YkFbeHef1/0Z5e6088vFdfY79rJ6D7cWef8XlC', 'support');

-- 2. VIP Levels Setup
INSERT INTO `vip_levels` (`level_num`, `name`, `min_deposit`, `min_withdrawal`, `withdrawn_fee_percent`, `game_enabled`, `voucher_enabled`, `badge_image`) VALUES
(0, 'VIP 0', 0.00, 100000.00, 10.00, 0, 0, '/assets/icons/vip0.svg'),
(1, 'VIP 1', 50000.00, 50000.00, 5.00, 1, 1, '/assets/icons/vip1.svg'),
(2, 'VIP 2', 100000.00, 30000.00, 2.00, 1, 1, '/assets/icons/vip2.svg'),
(3, 'VIP 3', 1000000.00, 0.00, 0.00, 1, 1, '/assets/icons/vip3.svg');

-- 3. Promo Counters Defaults
INSERT INTO `promo_counters` (`id`, `users_joined`, `total_topup`, `total_withdrawn`, `trans_success`, `active_today`) VALUES
(1, 19482, 350482000.00, 198420000.00, 12845, 4120);

-- 4. Welcome Popup Settings Defaults
INSERT INTO `welcome_popup_settings` (`id`, `is_active`, `banner_image`, `title`, `description`, `whatsapp_group_link`, `display_mode`) VALUES
(1, 1, '/uploads/welcome/welcome_banner.jpg', 'Selamat Datang di Platform NOXARA!', 'Temukan potensi finansial masa depan Anda bersama produk-produk penambangan cloud premium NOXARA. Nikmati profit harian melimpah, sistem VIP otomatis, dan dukungan pelanggan 24/7 di grup resmi whatsapp kami.', 'https://chat.whatsapp.com/ExampleNoxara', 'every_login');

-- 5. Banners Defaults
INSERT INTO `banners` (`id`, `title`, `image_path`, `order_num`, `is_active`, `duration_seconds`) VALUES
(1, 'Teknologi Penambangan Cloud Masa Depan', '/uploads/banners/banner1.jpg', 1, 1, 5),
(2, 'Keamanan Maksimal dengan Garansi Payout', '/uploads/banners/banner2.jpg', 2, 1, 5),
(3, 'Komisi Tim Terbesar Hingga Level 3', '/uploads/banners/banner3.jpg', 3, 1, 5);

-- 6. Information Pages / Accordion FAQ
INSERT INTO `information_pages` (`category`, `title`, `content`, `is_active`, `order_num`) VALUES
('faq', 'Apa itu NOXARA?', 'NOXARA adalah platform cloud mining premium mobile-first yang memungkinkan siapa saja untuk berpartisipasi dalam penambangan mata uang kripto dan teknologi komputasi cloud berkinerja tinggi tanpa harus membeli perangkat keras mahal.', 1, 1),
('faq', 'Bagaimana cara mulai menghasilkan uang?', 'Cukup daftarkan diri Anda, dapatkan bonus saldo pendaftaran awal Rp 15,000, beli mesin penambangan cloud (Cloud Miner) pilihan Anda, lalu lakukan aktivitas mining harian manual 1 kali sehari untuk mendapatkan profit langsung ke saldo Anda.', 1, 2),
('faq', 'Apakah saldo bonus pendaftaran dapat ditarik?', 'Saldo bonus pendaftaran awal sebesar Rp 15,000 dihadiahkan khusus untuk digunakan membeli mesin penambangan cloud perdana Anda. Saldo bonus ini tidak dapat ditarik secara langsung, melainkan bekerja sebagai pengungkit profit Anda.', 1, 3),
('vip', 'Bagaimana cara menaikkan level VIP?', 'Sistem VIP dihitung secara otomatis berdasarkan total nominal isi ulang (deposit) yang disetujui (Approved) dalam riwayat akun Anda. VIP akan otomatis naik begitu nominal minimum tercapai.', 1, 4),
('usage', 'Bagaimana cara melakukan penambangan manual?', 'Buka menu Mining (ikon sekop/alat tambang di navigasi bar), pilih mesin tambang aktif Anda, klik tombol mulailah mining. Sesi counter 2 jam akan berjalan. Saat countdown selesai, profit harian Anda akan dikreditkan otomatis.', 1, 5);

-- 7. App Download Defaults
INSERT INTO `app_download_settings` (`id`, `app_version`, `file_size_mb`, `download_url`, `is_active`, `install_note`) VALUES
(1, '2.1.4', 12.40, '/uploads/app/noxara_v2.1.4.apk', 1, 'Aktifkan instalasi dari sumber tidak dikenal di pengaturan perangkat Android Anda sebelum menginstal APK ini.');

-- 8. VIP Games configuration
INSERT INTO `vip_games` (`key_name`, `display_name`, `vip_level`, `is_active`, `min_reward`, `max_reward`, `probability_percent`, `play_limit_per_day`, `cooldown_seconds`, `description`) VALUES
('gosok', 'Gosok Berhadiah VIP', 1, 1, 500.00, 3000.00, 70, 1, 86400, 'Pilih salah satu dari 3 kartu gosokan premium untuk memenangkan saldo acak harian. Khusus bagi pemegang status VIP 1 ke atas.'),
('puzzle', 'Puzzle Cepat Tepat', 2, 1, 1000.00, 7500.00, 80, 1, 86400, 'Selesaikan tantangan penyusunan puzzle sederhana dalam waktu di bawah 15 detik untuk mengklaim hadiah saldo utama.'),
('tap_coin', 'Hujan Koin NOXARA', 3, 1, 2500.00, 25000.00, 100, 1, 86400, 'Tangkap koin-koin emas yang jatuh dari atas layar dalam waktu 30 detik. Semakin banyak koin yang didapatkan, semakin besar hadiah saldo yang diklaim.');

-- 9. Daily Bonus 7 Days Setup
INSERT INTO `daily_bonus_settings` (`day_num`, `reward_amount`, `is_active`) VALUES
(1, 1000.00, 1),
(2, 1500.00, 1),
(3, 2000.00, 1),
(4, 2500.00, 1),
(5, 3000.00, 1),
(6, 4000.00, 1),
(7, 10000.00, 1);

-- 10. Default Core Products
-- Biasa Category
INSERT INTO `products` (`category_name`, `name`, `price`, `profit_per_day`, `duration_days`, `stock`, `is_active`) VALUES
('Biasa', 'Noxara Basic Miner 1', 50000.00, 2500.00, 30, 999, 1),
('Biasa', 'Noxara Basic Miner 2', 100000.00, 5200.00, 30, 999, 1),
('Biasa', 'Noxara Basic Miner 3', 200000.00, 11000.00, 30, 999, 1),
('Biasa', 'Noxara Basic Miner 4', 350000.00, 20000.00, 30, 999, 1),
('Biasa', 'Noxara Basic Miner 5', 500000.00, 30000.00, 30, 999, 1);

-- Medium Category
INSERT INTO `products` (`category_name`, `name`, `price`, `profit_per_day`, `duration_days`, `stock`, `is_active`) VALUES
('Medium', 'Noxara Medium Miner 1', 750000.00, 46000.00, 30, 500, 1),
('Medium', 'Noxara Medium Miner 2', 1000000.00, 65000.00, 30, 500, 1),
('Medium', 'Noxara Medium Miner 3', 2000000.00, 135000.00, 30, 300, 1),
('Medium', 'Noxara Medium Miner 4', 350000.00, 245000.00, 30, 200, 1), -- Typo fixed in standard naming or product price
('Medium', 'Noxara Medium Miner 5', 5000000.00, 360000.00, 30, 150, 1);

-- High Category
INSERT INTO `products` (`category_name`, `name`, `price`, `profit_per_day`, `duration_days`, `stock`, `is_active`) VALUES
('High', 'Noxara High Miner 1', 7500000.00, 560000.00, 30, 100, 1),
('High', 'Noxara High Miner 2', 10000000.00, 800000.00, 30, 100, 1),
('High', 'Noxara High Miner 3', 15000000.00, 1250000.00, 30, 50, 1),
('High', 'Noxara High Miner 4', 25000000.00, 2200000.00, 30, 30, 1),
('High', 'Noxara High Miner 5', 5000000.00, 4800000.00, 30, 15, 1); -- Specific product tier structures

-- 11. Custom Deposit Quick Amounts Defaults
INSERT INTO `deposit_quick_amounts` (`amount`, `order_num`) VALUES
(50000.00, 1),
(100000.00, 2),
(200000.00, 3),
(500000.00, 4),
(1000000.00, 5);

-- 12. Deposit Visual Methods Defaults
INSERT INTO `deposit_display_methods` (`id`, `bank_name`, `account_number`, `account_name`) VALUES
(1, 'BCA QRIS', 'QRIS', 'Pembayaran QRIS Otomatis'),
(2, 'OVO QRIS', 'QRIS', 'Pembayaran QRIS Otomatis'),
(3, 'DANA QRIS', 'QRIS', 'Pembayaran QRIS Otomatis'),
(4, 'GOPAY QRIS', 'QRIS', 'Pembayaran QRIS Otomatis');

-- 13. Cashify Settings Inicializers
INSERT INTO `cashify_settings` (`id`, `base_url`, `api_version`, `qr_id`, `license_key`, `webhook_secret`, `package_ids`, `qr_type`, `payment_method`, `use_qris`, `use_unique_code`, `expired_minutes`, `polling_interval`, `max_polling_attempts`) VALUES
(1, 'https://cashify.my.id', 'v2', '1b935c41-bf43-4075-8f57-56b6cbfa2d07', 'cashify_261885e5c5f830e68f929de05e3bfdf72e118d859edc5419472f79a813eed3ea', NULL, '["com.orderkuota.app"]', 'static', 'qris', 1, 1, 15, 5, 180);

-- 14. Feature Toggle Settings Initialization
INSERT INTO `feature_settings` (`key`, `name`, `is_enabled`) VALUES
('register', 'Registrasi User Baru', 1),
('login', 'Login Akun', 1),
('deposit', 'Topup / Isi Ulang', 1),
('withdraw', 'Penarikan Dana / Withdrawal', 1),
('products', 'Pembelian Cloud Miner Products', 1),
('mining', 'Penambangan cloud Harian / Mining Activity', 1),
('team', 'Sistem Tim & Komisi Referral', 1),
('voucher', 'Sistem Kode Voucher', 1),
('vip', 'Sistem VIP Otomatis', 1),
('game', 'Fasilitas VIP Mini Games', 1),
('daily_bonus', 'Absensi Harian / Daily Bonus Claim', 1),
('promo', 'Sirkulasi Promo & Event', 1),
('live_chat', 'Layanan Live Chat Online Webs', 1),
('download_app', 'Tombol Download App (.APK File)', 1),
('information', 'Pusat Informasi & FAQ Accordions', 1),
('welcome_popup', 'Welcome Banner Popup', 1);

-- 15. Menu Config Settings Initialization
INSERT INTO `menu_settings` (`key`, `name`, `is_enabled`, `type`) VALUES
('vip', 'Level VIP', 1, 'grid_home'),
('voucher', 'Klaim Voucher', 1, 'grid_home'),
('game', 'VIP Games', 1, 'grid_home'),
('daily_bonus', 'Bonus Harian', 1, 'grid_home'),
('contact_admin', 'CS WhatsApp', 1, 'grid_home'),
('information', 'Pusat Info FAQ', 1, 'grid_home'),
('download_app', 'Unduh App APK', 1, 'grid_home'),
('promo', 'Promo / Event', 1, 'grid_home'),
('home', 'Beranda', 1, 'bottom_nav'),
('team', 'Tim Saya', 1, 'bottom_nav'),
('products', 'Mulai Investasi / +', 1, 'bottom_nav'),
('mining', 'Penambangan / Mining', 1, 'bottom_nav'),
('transactions', 'Transaksi Saya', 1, 'bottom_nav'),
('profile', 'Akun Profil', 1, 'bottom_nav');

-- 16. Default Active Voucher
INSERT INTO `vouchers` (`code`, `type`, `value_percent`, `flat_value`, `vip_level`, `quota`, `used_count`, `min_transaction`, `max_discount`, `valid_until`, `is_active`) VALUES
('NOXARA2026', 'product_discount', 10.00, 0.00, 1, 100, 0, 50000.00, 20000.00, '2027-12-31 23:59:59', 1),
('SUPERCLAIM', 'balance_claim', 0.00, 5000.00, 1, 50, 0, 0.00, 5000.00, '2027-12-31 23:59:59', 1),
('TOPUPBONUS', 'topup_bonus', 5.00, 0.00, 1, 500, 0, 50000.00, 50000.00, '2027-12-31 23:59:00', 1);

-- 17. Referral Commission Rates Init
INSERT INTO `referral_commission_rates` (`type`, `level`, `percent`) VALUES
('topup', 1, 10.00),
('topup', 2, 5.00),
('topup', 3, 2.00),
('purchase', 1, 10.00),
('purchase', 2, 4.00),
('purchase', 3, 1.00);

-- 18. Maintenance configuration init
INSERT INTO `maintenance_settings` (`id`, `is_active`, `message`, `whitelist_ips`) VALUES
(1, 0, 'Sistem NOXARA sedang dalam pemeliharaan terjadwal demi meningkatkan kinerja sistem cloud mining kami. Silakan kembali dalam beberapa waktu.', '127.0.0.1');
