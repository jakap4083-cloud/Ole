<?php
// Simple Mathematics Captcha Generator

function generate_captcha() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $num1 = rand(1, 15);
    $num2 = rand(1, 10);
    $operators = ['+', '-'];
    $op = $operators[rand(0, 1)];
    
    if ($op === '-') {
        if ($num1 < $num2) {
            $temp = $num1;
            $num1 = $num2;
            $num2 = $temp;
        }
        $answer = $num1 - $num2;
    } else {
        $answer = $num1 + $num2;
    }
    
    $_SESSION['captcha_answer'] = $answer;
    return "$num1 $op $num2 = ?";
}

function verify_captcha($input) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['captcha_answer'])) {
        return false;
    }
    $verified = ((int)$input === (int)$_SESSION['captcha_answer']);
    unset($_SESSION['captcha_answer']); // force cycle
    return $verified;
}
