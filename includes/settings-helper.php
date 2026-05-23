<?php
// Secure Web Settings custom helper manager database-linked overrides

require_once __DIR__ . '/db.php';

function get_setting($key, $default_value = '') {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("SELECT value FROM site_settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default_value;
    } catch (Exception $e) {
        return $default_value;
    }
}

function set_setting($key, $value) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("INSERT INTO site_settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        return $stmt->execute([$key, $value, $value]);
    } catch (Exception $e) {
        return false;
    }
}

function is_feature_enabled($key) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("SELECT is_enabled FROM feature_settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? (bool)$row['is_enabled'] : true;
    } catch (Exception $e) {
        return true;
    }
}

function is_menu_enabled($key) {
    $db = get_db_connection();
    try {
        $stmt = $db->prepare("SELECT is_enabled FROM menu_settings WHERE `key` = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? (bool)$row['is_enabled'] : true;
    } catch (Exception $e) {
        return true;
    }
}
