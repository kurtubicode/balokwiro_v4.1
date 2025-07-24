<?php
// Tidak perlu session_start() lagi karena kita tidak pakai session untuk keranjang
// session_start(); 

// Panggil file autoloader Composer
require_once '/laragon/www/percobaan/composer/vendor/midtrans/Midtrans.php'; // Pastikan path ini BENAR
// Panggil file koneksi database Anda
include 'backend/koneksi.php';


// --- Konfigurasi Midtrans ---
// GANTI DENGAN SERVER KEY SANDBOX ANDA YANG BENAR
\Midtrans\Config::$serverKey = 'SB-Mid-server-m7XR9SAjhEL4DjuqSc1o-qOM'; 
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;


// --- Validasi dan Ambil Data dari POST ---

// Cek apakah metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Ambil data pelanggan dari form
$nama_pemesan = $_POST['nama_pemesan'] ?? 'Pelanggan';
$telepon_pemesan = $_POST['telepon_pemesan'] ?? '08123456789';
$email_pemesan = $_POST['email_pemesan'] ?? 'pelanggan@example.com';

// Ambil data keranjang dari POST yang dikirim JavaScript
$keranjangJSON = $_POST['keranjang_data'] ?? '{}';
$keranjang = json_decode($keranjangJSON, true); // 'true' untuk menjadikannya array asosiatif

// Cek apakah keranjang kosong setelah di-decode
if (empty($keranjang)) {
    echo json_encode(['error' => 'Keranjang kosong. Tidak ada item untuk diproses.']);
    exit;
}


// --- Siapkan Data untuk Dikirim ke Midtrans ---

$total_harga = 0;
$items_details = [];

// Loop melalui data keranjang yang sudah menjadi array PHP
foreach ($keranjang as $id_produk => $item) {
    // Pastikan item adalah array dan memiliki key yang dibutuhkan
    if (is_array($item) && isset($item['harga'], $item['jumlah'], $item['nama'])) {
        $total_harga += $item['harga'] * $item['jumlah'];
        $items_details[] = [
            'id'       => $id_produk,
            'price'    => (int)$item['harga'],
            'quantity' => (int)$item['jumlah'],
            'name'     => $item['nama']
        ];
    }
}

// Jika setelah loop total harga masih 0 (karena data tidak valid), kirim error
if ($total_harga <= 0) {
    echo json_encode(['error' => 'Data keranjang tidak valid.']);
    exit;
}

// Buat ID Pesanan yang unik
$order_id = 'KUEBALOK-' . time();

// Simpan pesanan ke database Anda dengan status 'menunggu_pembayaran'
// (Ini opsional tapi SANGAT direkomendasikan agar Anda punya catatannya)
// $query = $koneksi->prepare("INSERT INTO pesanan (id_pesanan, nama_pemesan, ...) VALUES (?, ?, ...)");
// $query->bind_param("ss...", $order_id, $nama_pemesan, ...);
// $query->execute();


// --- Buat Transaksi Midtrans ---
$params = [
    'transaction_details' => [
        'order_id' => $order_id,
        'gross_amount' => (int)$total_harga,
    ],
    'item_details' => $items_details,
    'customer_details' => [
        // Gunakan nama depan saja jika nama lengkap, atau sesuaikan
        'first_name' => explode(' ', $nama_pemesan)[0], 
        'last_name' => count(explode(' ', $nama_pemesan)) > 1 ? end(explode(' ', $nama_pemesan)) : '',
        'email' => $email_pemesan,
        'phone' => $telepon_pemesan,
    ],
];

try {
    // Dapatkan Snap Token dari Midtrans
    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
    // Kirim token kembali ke frontend dalam format JSON
    header('Content-Type: application/json');
    echo json_encode(['snap_token' => $snapToken]);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
}

?>