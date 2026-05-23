<?php
// Secure Administrator logout action
require_once __DIR__ . '/../../includes/session.php';
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);
header('Location: /pages/admin/login.php');
exit();
