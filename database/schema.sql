-- NOXARA Database Schema
-- Optimized for aaPanel & MySQL 8.x

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `cron_logs`;
DROP TABLE IF EXISTS `login_attempts`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `user_status_logs`;
DROP TABLE IF EXISTS `user_freezes`;
DROP TABLE IF EXISTS `maintenance_settings`;
DROP TABLE IF EXISTS `menu_settings`;
DROP TABLE IF EXISTS `feature_settings`;
DROP TABLE IF EXISTS `deposit_quick_amounts`;
DROP TABLE IF EXISTS `deposit_display_methods`;
DROP TABLE IF EXISTS `topups`;
DROP TABLE IF EXISTS `cashify_settings`;
DROP TABLE IF EXISTS `withdrawals`;
DROP TABLE IF EXISTS `referral_commissions`;
DROP TABLE IF EXISTS `referral_tree`;
DROP TABLE IF EXISTS `user_referrals`;
DROP TABLE IF EXISTS `referral_commission_rates`;
DROP TABLE IF EXISTS `referral_settings`;
DROP TABLE IF EXISTS `mining_profit_logs`;
DROP TABLE IF EXISTS `mining_sessions`;
DROP TABLE IF EXISTS `mining_settings`;
DROP TABLE IF EXISTS `user_products`;
DROP TABLE IF EXISTS `product_purchases`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `product_categories`;
DROP TABLE IF EXISTS `daily_bonus_claims`;
DROP TABLE IF EXISTS `daily_bonus_rewards`;
DROP TABLE IF EXISTS `daily_bonus_settings`;
DROP TABLE IF EXISTS `vip_game_plays`;
DROP TABLE IF EXISTS `vip_game_sessions`;
DROP TABLE IF EXISTS `vip_game_rewards`;
DROP TABLE IF EXISTS `vip_games`;
DROP TABLE IF EXISTS `voucher_usages`;
DROP TABLE IF EXISTS `vouchers`;
DROP TABLE IF EXISTS `user_vip_status`;
DROP TABLE IF EXISTS `vip_levels`;
DROP TABLE IF EXISTS `chat_messages`;
DROP TABLE IF EXISTS `chat_threads`;
DROP TABLE IF EXISTS `promos`;
DROP TABLE IF EXISTS `app_download_settings`;
DROP TABLE IF EXISTS `information_pages`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `banners`;
DROP TABLE IF EXISTS `welcome_popup_settings`;
DROP TABLE IF EXISTS `promo_counters`;
DROP TABLE IF EXISTS `site_settings`;
DROP TABLE IF EXISTS `ledger_transactions`;
DROP TABLE IF EXISTS `balance_accounts`;
DROP TABLE IF EXISTS `pin_reset_requests`;
DROP TABLE IF EXISTS `user_pins`;
DROP TABLE IF EXISTS `user_bank_accounts`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('active', 'blocked', 'suspended') DEFAULT 'active',
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Admins Table
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('super_admin', 'admin', 'support', 'finance') DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User Profiles
CREATE TABLE `user_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `full_name` VARCHAR(100) NULL,
  `referral_code` VARCHAR(20) NOT NULL UNIQUE,
  `referred_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`referred_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. User Bank Accounts
CREATE TABLE `user_bank_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_name` VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. User Transaction PINs
CREATE TABLE `user_pins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `pin_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. PIN Reset Requests
CREATE TABLE `pin_reset_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `requested_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Balance Accounts
CREATE TABLE `balance_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `main_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `bonus_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `profit_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `commission_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `locked_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `total_profit` DECIMAL(15, 2) DEFAULT 0.00,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Ledger Transactions (Pure immutable state)
CREATE TABLE `ledger_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL, -- e.g. 'deposit', 'withdrawal_request', 'withdrawal_approve', 'purchase', 'mining_profit', 'referral_commission', 'game_reward', 'daily_bonus', 'voucher_claim'
  `amount` DECIMAL(15, 2) NOT NULL,
  `balance_type` ENUM('main_balance', 'bonus_balance', 'profit_balance', 'commission_balance', 'locked_balance') NOT NULL,
  `direction` ENUM('in', 'out') NOT NULL,
  `description` TEXT NOT NULL,
  `idempotency_key` VARCHAR(255) UNIQUE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Site Settings Table
CREATE TABLE `site_settings` (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` TEXT NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Promo Counters
CREATE TABLE `promo_counters` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `users_joined` INT DEFAULT 18274,
  `total_topup` DECIMAL(15, 2) DEFAULT 248920000.00,
  `total_withdrawn` DECIMAL(15, 2) DEFAULT 142100000.00,
  `trans_success` INT DEFAULT 9841,
  `active_today` INT DEFAULT 3120
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Welcome Popup Settings
CREATE TABLE `welcome_popup_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `is_active` TINYINT(1) DEFAULT 1,
  `banner_image` VARCHAR(255) DEFAULT '/assets/img/default-welcome-banner.jpg',
  `title` VARCHAR(255) DEFAULT 'Selamat datang di Platform NOXARA',
  `description` TEXT,
  `whatsapp_group_link` VARCHAR(255) DEFAULT 'https://chat.whatsapp.com/ExampleNoxara',
  `display_mode` ENUM('every_login', 'once_a_day', 'until_closed') DEFAULT 'every_login'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Banners Slider
CREATE TABLE `banners` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `order_num` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `duration_seconds` INT DEFAULT 5,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL, -- NULL means broadcast
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Information Pages / FAQ
CREATE TABLE `information_pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category` ENUM('usage', 'vip', 'about', 'faq', 'privacy', 'tos') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `order_num` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. App Download Settings
CREATE TABLE `app_download_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `app_version` VARCHAR(50) DEFAULT '1.0.0',
  `file_size_mb` DECIMAL(5, 2) DEFAULT 8.50,
  `download_url` VARCHAR(255) DEFAULT '',
  `is_active` TINYINT(1) DEFAULT 0,
  `install_note` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Promos & Events Settings
CREATE TABLE `promos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `banner_image` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `start_date` DATE,
  `end_date` DATE,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Chat Threads (Live Chat system webs)
CREATE TABLE `chat_threads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `status` ENUM('open', 'resolved') DEFAULT 'open',
  `assigned_admin_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Chat Messages
CREATE TABLE `chat_messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `thread_id` INT NOT NULL,
  `sender_type` ENUM('user', 'admin') NOT NULL,
  `sender_id` INT NOT NULL, -- Match tables depending on type
  `message` TEXT,
  `attachment_path` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`thread_id`) REFERENCES `chat_threads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. VIP Levels Configuration
CREATE TABLE `vip_levels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `level_num` INT UNIQUE NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `min_deposit` DECIMAL(15, 2) NOT NULL,
  `min_withdrawal` DECIMAL(15, 2) NOT NULL,
  `withdrawn_fee_percent` DECIMAL(5, 2) DEFAULT 0.00,
  `game_enabled` TINYINT(1) DEFAULT 1,
  `voucher_enabled` TINYINT(1) DEFAULT 1,
  `badge_image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. User VIP status mapping
CREATE TABLE `user_vip_status` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `vip_level` INT DEFAULT 0,
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Vouchers Table
CREATE TABLE `vouchers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) UNIQUE NOT NULL,
  `type` ENUM('topup_bonus', 'product_discount', 'balance_claim') NOT NULL,
  `value_percent` DECIMAL(5, 2) DEFAULT 0.00, -- for percentage-based types
  `flat_value` DECIMAL(15, 2) DEFAULT 0.00, -- for balance_claim or flat types
  `vip_level` INT NOT NULL DEFAULT 0, -- Min vip required
  `quota` INT DEFAULT 100,
  `used_count` INT DEFAULT 0,
  `min_transaction` DECIMAL(15, 2) DEFAULT 0.00,
  `max_discount` DECIMAL(15, 2) DEFAULT 0.00,
  `valid_until` DATETIME NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Voucher Usages
CREATE TABLE `voucher_usages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `voucher_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `used_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. VIP Games Configuration
CREATE TABLE `vip_games` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key_name` VARCHAR(50) UNIQUE NOT NULL, -- 'gosok', 'puzzle', 'tap_coin'
  `display_name` VARCHAR(100) NOT NULL,
  `vip_level` INT NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) DEFAULT 1,
  `min_reward` DECIMAL(15, 2) DEFAULT 0.00,
  `max_reward` DECIMAL(15, 2) DEFAULT 0.00,
  `probability_percent` INT DEFAULT 50, -- chance of getting rewarding block
  `play_limit_per_day` INT DEFAULT 1,
  `cooldown_seconds` INT DEFAULT 0,
  `description` TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. VIP Game plays log
CREATE TABLE `vip_game_plays` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `game_id` INT NOT NULL,
  `played_date` DATE NOT NULL,
  `reward_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `status` ENUM('win', 'zonk', 'pending') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`game_id`) REFERENCES `vip_games` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Daily Bonus Settings
CREATE TABLE `daily_bonus_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `day_num` INT UNIQUE NOT NULL, -- Day 1 to 7
  `reward_amount` DECIMAL(15, 2) NOT NULL,
  `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Daily Bonus Claims Log
CREATE TABLE `daily_bonus_claims` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `day_num` INT NOT NULL,
  `claimed_date` DATE NOT NULL,
  `reward_amount` DECIMAL(15, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_day_claim` (`user_id`, `claimed_date`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Product Categories
CREATE TABLE `product_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` ENUM('Biasa', 'Medium', 'High') UNIQUE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. Products
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` ENUM('Biasa', 'Medium', 'High') NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(15, 2) NOT NULL,
  `profit_per_day` DECIMAL(15, 2) NOT NULL,
  `duration_days` INT NOT NULL DEFAULT 30,
  `stock` INT NOT NULL DEFAULT 99,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. User purchased active products
CREATE TABLE `user_products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `price_paid` DECIMAL(15, 2) NOT NULL,
  `profit_per_day` DECIMAL(15, 2) NOT NULL,
  `active_until` DATETIME NOT NULL,
  `status` ENUM('active', 'expired') DEFAULT 'active',
  `bought_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. Product Purchases detailed ledger cross reference
CREATE TABLE `product_purchases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `user_product_id` INT NOT NULL,
  `amount_paid` DECIMAL(15, 2) NOT NULL,
  `bonus_amount_used` DECIMAL(15, 2) NOT NULL,
  `main_amount_used` DECIMAL(15, 2) NOT NULL,
  `voucher_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_product_id`) REFERENCES `user_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 31. Mining Sessions
CREATE TABLE `mining_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `user_product_id` INT NOT NULL,
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `ends_at` TIMESTAMP NOT NULL,
  `status` ENUM('running', 'completed', 'claimed') DEFAULT 'running',
  `profit_amount` DECIMAL(15, 2) NOT NULL,
  `idempotency_key` VARCHAR(255) UNIQUE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_product_id`) REFERENCES `user_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 32. Mining Profit Logs (individual distributed entries)
CREATE TABLE `mining_profit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `session_id` INT NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`session_id`) REFERENCES `mining_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 33. Referral settings
CREATE TABLE `referral_settings` (
  `key` VARCHAR(100) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 34. Referral commission levels
CREATE TABLE `referral_commission_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('topup', 'purchase') NOT NULL,
  `level` INT NOT NULL, -- 1, 2, 3
  `percent` DECIMAL(5, 2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 35. Flat binary level tree structure
CREATE TABLE `referral_tree` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `parent_id` INT DEFAULT NULL,
  `level` INT NOT NULL, -- Tree height mapping
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 36. Referral Commissions Log
CREATE TABLE `referral_commissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `earner_id` INT NOT NULL, -- who gets paid
  `source_id` INT NOT NULL, -- who triggered it
  `source_type` ENUM('topup', 'purchase') NOT NULL,
  `reference_id` INT NOT NULL, -- topup_id or purchase_id
  `level` INT NOT NULL, -- 1, 2 or 3
  `amount` DECIMAL(15, 2) NOT NULL,
  `status` ENUM('processed', 'unprocessed') DEFAULT 'processed',
  `idempotency_key` VARCHAR(255) UNIQUE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`earner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`source_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 37. Withdrawals System
CREATE TABLE `withdrawals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `amount` DECIMAL(15, 2) NOT NULL,
  `fee_amount` DECIMAL(15, 2) NOT NULL,
  `net_amount` DECIMAL(15, 2) NOT NULL,
  `bank_name` VARCHAR(100) NOT NULL,
  `account_number` VARCHAR(50) NOT NULL,
  `account_name` VARCHAR(150) NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `rejection_reason` VARCHAR(255) DEFAULT NULL,
  `idempotency_key` VARCHAR(255) UNIQUE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 38. Cashify configuration & overrides
CREATE TABLE `cashify_settings` (
  `id` INT PRIMARY KEY,
  `base_url` VARCHAR(255) DEFAULT 'https://cashify.my.id',
  `api_version` VARCHAR(10) DEFAULT 'v2',
  `qr_id` VARCHAR(255) DEFAULT '1b935c41-bf43-4075-8f57-56b6cbfa2d07',
  `license_key` VARCHAR(255) DEFAULT 'cashify_261885e5c5f830e68f929de05e3bfdf72e118d859edc5419472f79a813eed3ea',
  `webhook_secret` VARCHAR(255) DEFAULT NULL,
  `package_ids` TEXT, -- json serialized array
  `qr_type` VARCHAR(50) DEFAULT 'static',
  `payment_method` VARCHAR(50) DEFAULT 'qris',
  `use_qris` TINYINT(1) DEFAULT 1,
  `use_unique_code` TINYINT(1) DEFAULT 1,
  `expired_minutes` INT DEFAULT 15,
  `polling_interval` INT DEFAULT 5,
  `max_polling_attempts` INT DEFAULT 180,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 39. Topups Log
CREATE TABLE `topups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `original_amount` DECIMAL(15, 2) NOT NULL,
  `unique_nominal` INT DEFAULT 0,
  `total_amount` DECIMAL(15, 2) NOT NULL,
  `transaction_id_cashify` VARCHAR(255) DEFAULT NULL,
  `qr_string` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'paid', 'success', 'cancel', 'expired') DEFAULT 'pending',
  `voucher_id` INT DEFAULT NULL,
  `method_display` VARCHAR(100) DEFAULT 'QRIS',
  `idempotency_key` VARCHAR(255) UNIQUE NOT NULL,
  `expired_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 40. Custom deposit amounts configurables
CREATE TABLE `deposit_quick_amounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `amount` DECIMAL(15, 2) NOT NULL,
  `order_num` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 41. Feature global state toggles
CREATE TABLE `feature_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) UNIQUE NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `is_enabled` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 42. Menu navigation status indicators
CREATE TABLE `menu_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) UNIQUE NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `is_enabled` TINYINT(1) DEFAULT 1,
  `type` ENUM('grid_home', 'bottom_nav') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 43. Maintenance flags
CREATE TABLE `maintenance_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `is_active` TINYINT(1) DEFAULT 0,
  `message` TEXT NOT NULL,
  `whitelist_ips` TEXT -- Comma separated whitelisted IPs
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 44. User freezing status logs
CREATE TABLE `user_freezes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL UNIQUE,
  `freeze_type` ENUM('all', 'main_balance', 'bonus_balance', 'profit_balance', 'commission_balance', 'withdraw_only', 'purchase_only') NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 45. User logs table
CREATE TABLE `user_status_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `notes` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 46. Admin auditlogs
CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 47. Security failure tracking systems
CREATE TABLE `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `username` VARCHAR(100) NOT NULL,
  `attempts` INT DEFAULT 1,
  `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 48. Password resets request tables
CREATE TABLE `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL UNIQUE,
  `expired_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 49. Cron jobs logging
CREATE TABLE `cron_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cron_name` VARCHAR(100) NOT NULL,
  `status` ENUM('success', 'failed') NOT NULL,
  `message` TEXT,
  `run_time_seconds` DECIMAL(8, 4) DEFAULT 0.0000,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
