<?php
// Secure Math Captcha Endpoint helper callback
require_once __DIR__ . '/../../includes/captcha.php';
header('Content-Type: application/json');
echo json_encode(['captcha_quest' => generate_captcha()]);
