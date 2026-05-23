<?php
// Secure product retrieval and detail builders

require_once __DIR__ . '/db.php';

function get_product_by_id($product_id) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT p.*, 
                           (p.profit_per_day * p.duration_days) as total_profit_estimate,
                           ((p.profit_per_day * p.duration_days) / p.price * 100) as roi_percent
                           FROM products p 
                           WHERE p.id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    return $stmt->fetch();
}

function get_active_products_by_category($category) {
    $db = get_db_connection();
    $stmt = $db->prepare("SELECT p.*, 
                           (p.profit_per_day * p.duration_days) as total_profit_estimate,
                           ((p.profit_per_day * p.duration_days) / p.price * 100) as roi_percent
                           FROM products p 
                           WHERE p.category_name = ? AND p.is_active = 1 
                           ORDER BY p.price ASC");
    $stmt->execute([$category]);
    return $stmt->fetchAll();
}
