<?php
// Secure User Session logout
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/flash.php';

unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['email']);

set_flash_message('success', 'Berhasil logout. Silakan masukkan nomor HP/username kembali.');
header('Location: /pages/auth/login.php');
exit();
