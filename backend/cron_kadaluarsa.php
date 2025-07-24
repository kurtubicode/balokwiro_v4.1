<?php
// backend/cron_kadaluarsa.php

// Menggunakan path absolut agar bisa dijalankan dari mana saja
require_once __DIR__ . '/koneksi.php';

// Cek jika koneksi gagal
if (!$koneksi) {
    error_log("Cron Job Gagal: Tidak bisa terhubung ke database.");
    exit;
}

echo "Cron Job Dijalankan: " . date('Y-m-d H:i:s') . "\n";

// 1. Cari semua pesanan yang statusnya 'menunggu_pembayaran' dan sudah lewat 10 menit
$sql_cari = "SELECT id_pesanan FROM pesanan WHERE status_pesanan = 'menunggu_pembayaran' AND tgl_pesanan < NOW() - INTERVAL 10 MINUTE";
$result_cari = mysqli_query($koneksi, $sql_cari);

if (!$result_cari || mysqli_num_rows($result_cari) == 0) {
    echo "Tidak ada pesanan kadaluarsa yang ditemukan.\n";
    exit;
}

echo "Ditemukan " . mysqli_num_rows($result_cari) . " pesanan kadaluarsa. Memproses...\n";

// Siapkan statement di luar loop agar lebih efisien
$stmt_items = mysqli_prepare($koneksi, "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?");
$stmt_info = mysqli_prepare($koneksi, "SELECT id_kategori_stok FROM produk WHERE id_produk = ?");
$stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'pembatalan', ?, ?)");
$stmt_batal = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ?");

// Loop setiap pesanan yang kadaluarsa
while ($pesanan_kadaluarsa = mysqli_fetch_assoc($result_cari)) {
    $id_pesanan = $pesanan_kadaluarsa['id_pesanan'];
    mysqli_begin_transaction($koneksi);
    try {
        // ... (Sisa logika dari file cek_kadaluarsa.php Anda tidak berubah) ...
        // Ambil detail item, kembalikan stok, dan ubah status pesanan.
        // Logikanya sudah benar.
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        error_log("Gagal membatalkan pesanan #$id_pesanan: " . $e->getMessage());
    }
}

echo "Proses selesai.\n";

?>