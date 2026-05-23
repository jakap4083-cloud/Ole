<?php
// Secure PDO Database Wrapper Connection

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        $config_file = __DIR__ . '/../config/database.php';
        if (!file_exists($config_file)) {
            die("Database configuration file missing.");
        }
        $config = require $config_file;
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            // Logs detail internally. Do not dump passwords or trace elements onto UI
            error_log("Connection failed: " . $e->getMessage());
            die("Sistem mengalami kegagalan koneksi database. Silakan coba sesaat lagi.");
        }
    }
    return $pdo;
}
