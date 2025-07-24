<?php
require_once __DIR__ . '/koneksi.php';

echo "<h1>Memulai Proses Pembersihan Pesanan Kedaluwarsa...</h1>";

// Tentukan batas waktu, misalnya 2 jam yang lalu
// Anda bisa mengubah '2 HOUR' menjadi '1 DAY' atau sesuai kebutuhan
$batas_waktu = date('Y-m-d H:i:s', strtotime('-2 HOUR'));

// Status-status yang dianggap 'terbengkalai' jika terlalu lama
$status_target = ['menunggu_pilihan_pembayaran', 'menunggu_pembayaran_tunai'];
$placeholders = implode(',', array_fill(0, count($status_target), '?'));

// Siapkan query untuk mengubah status pesanan yang terbengkalai
$sql = "UPDATE pesanan 
        SET status_pesanan = 'kedaluwarsa' 
        WHERE status_pesanan IN ($placeholders) 
        AND tgl_pesanan < ?";

$stmt = $koneksi->prepare($sql);

// Bind parameter: semua status target, lalu batas waktu
$types = str_repeat('s', count($status_target)) . 's';
$params = array_merge($status_target, [$batas_waktu]);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo "<p>Proses selesai. Sejumlah <strong>{$affected_rows} pesanan</strong> yang dibuat sebelum {$batas_waktu} telah ditandai sebagai 'kedaluwarsa'.</p>";
} else {
    echo "<p style='color:red;'>Terjadi error saat menjalankan proses pembersihan: " . $stmt->error . "</p>";
}

$stmt->close();
$koneksi->close();
?>