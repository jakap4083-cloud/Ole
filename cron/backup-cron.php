<?php
// Auto backup Database MySQL structural Schema and seed to VPS safe target storage
// Runs weekly or daily in aaPanel.
// Run: php /www/wwwroot/noxara.page/cron/backup-cron.php

$start_time = microtime(true);
require_once __DIR__ . '/../includes/db.php';

try {
    $config_file = __DIR__ . '/../config/database.php';
    if (!file_exists($config_file)) {
         throw new Exception("Database config missing.");
    }
    $config = require $config_file;
    
    $backup_dir = __DIR__ . '/../storage/backups/';
    if (!is_dir($backup_dir)) {
         mkdir($backup_dir, 0755, true);
    }
    
    $filename = 'nox_db_backup_' . date('Ymd_His') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Command based safe backup via native mysqldump (aaPanel has it default in CLI paths)
    $command = sprintf(
        "mysqldump -h %s --port=%d -u %s -p%s %s > %s 2>&1",
        escapeshellarg($config['host']),
        (int)$config['port'],
        escapeshellarg($config['username']),
        escapeshellarg($config['password']),
        escapeshellarg($config['database']),
        escapeshellarg($filepath)
    );
    
    // Run system exec
    $output = [];
    $retval = -1;
    exec($command, $output, $retval);
    
    if ($retval !== 0) {
        // Fallback manual PHP dump if VPS has security bans on exec/mysqldump
        $manual_status = manual_php_backup($filepath);
        if (!$manual_status) {
             throw new Exception("Exec mysqldump failed with exit code: $retval. Output: " . implode(', ', $output));
        }
    }
    
    // Keep directory lightweight, clear backups older than 30 days
    $files = glob($backup_dir . 'nox_db_backup_*.sql');
    $expire_sec = 30 * 24 * 3600;
    $deleted = 0;
    foreach ($files as $f) {
         if (time() - filemtime($f) > $expire_sec) {
              unlink($f);
              $deleted++;
         }
    }
    
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('backup-cron', 'success', "Backup database sukses. File: $filename. Dibersihkan: $deleted file lama.", $elapsed);
    echo "Database backup complete. Target: $filename\n";

} catch (Exception $e) {
    $elapsed = microtime(true) - $start_time;
    require_once __DIR__ . '/../includes/helpers.php';
    write_system_log('backup-cron', 'failed', "Gagal membackup database: " . $e->getMessage(), $elapsed);
    echo "DB Backup failed: " . $e->getMessage() . "\n";
}

function manual_php_backup($filepath) {
    try {
        $db = get_db_connection();
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $sql = "-- NOXARA Fallback PHP database backup\n";
        $sql .= "-- Generate date: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
             $stmt = $db->query("SHOW CREATE TABLE `$table`");
             $create_row = $stmt->fetch(PDO::FETCH_NUM);
             $sql .= $create_row[1] . ";\n\n";
             
             $rows_stmt = $db->query("SELECT * FROM `$table`");
             $rows = $rows_stmt->fetchAll(PDO::FETCH_ASSOC);
             
             foreach ($rows as $row) {
                  $keys = array_keys($row);
                  $escaped = array_map(function($v) use ($db) {
                       if ($v === null) return 'NULL';
                       return $db->quote($v);
                  }, array_values($row));
                  
                  $sql .= "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $escaped) . ");\n";
             }
             $sql .= "\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $sql);
        return true;
    } catch (Exception $e) {
        error_log("Fallback manual PHP backup failed: " . $e->getMessage());
        return false;
    }
}
