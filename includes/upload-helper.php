<?php
// Secure files upload helper utility with MIME type security hardening

function upload_site_file($file, $target_subfolder, $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'], $max_size_bytes = 2097152) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Berkas tidak terpilih atau terjadi kegagalan upload.'];
    }
    
    // Check sizes
    if ($file['size'] > $max_size_bytes) {
         return ['success' => false, 'error' => 'Ukuran berkas melebihi batas maksimal ' . ($max_size_bytes / 1024 / 1024) . ' MB.'];
    }
    
    // Path typos prevention and extension validation
    $original_name = basename($file['name']);
    $extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowed_extensions)) {
         return ['success' => false, 'error' => 'Ekstensi berkas tidak diizinkan. Hanya menerima: ' . implode(', ', $allowed_extensions)];
    }
    
    // Validate actual binary MIME type (prevent .php file disguised as .jpg)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_path'] ?? $file['tmp_name']);
    finfo_close($finfo);
    
    $safe_mimes = [
        'image/jpeg', 'image/png', 'image/webp',
        'application/pdf' // allowed for chat system attachments if designated
    ];
    
    if (!in_array($mime_type, $safe_mimes)) {
         return ['success' => false, 'error' => 'MIME berkas tidak aman untuk diunggah ke server VPS.'];
    }
    
    // Setup target subfolder
    $base_upload_dir = __DIR__ . '/../uploads/';
    $target_dir = rtrim($base_upload_dir, '/') . '/' . trim($target_subfolder, '/') . '/';
    
    if (!is_dir($target_dir)) {
         mkdir($target_dir, 0755, true);
    }
    
    // Rename with high entropy secure name prefix
    $new_name = 'nox_' . bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $target_dir . $new_name;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
         $public_url = '/uploads/' . trim($target_subfolder, '/') . '/' . $new_name;
         return ['success' => true, 'public_url' => $public_url, 'filename' => $new_name];
    } else {
         return ['success' => false, 'error' => 'Gagal memindahkan berkas fisik ke folder penyimpanan VPS. Periksa izin akses folder (CHMOD 755 / 777).'];
    }
}
