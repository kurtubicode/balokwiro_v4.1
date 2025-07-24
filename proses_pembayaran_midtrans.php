<?php
// Selalu mulai session di awal
session_start();

// KOREKSI: Menggunakan path yang sudah benar sesuai penemuan Anda
require_once __DIR__ . '/composer/vendor/midtrans/Midtrans.php'; 
require_once __DIR__ . '/backend/koneksi.php'; // PASTIKAN PATH INI JUGA BENAR

// 1. AMBIL ID PESANAN DARI SESSION
// =======================================================
if (!isset($_SESSION['id_pesanan_aktif'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Akses ditolak. Tidak ada pesanan aktif untuk diproses.']);
    exit;
}
$id_pesanan = $_SESSION['id_pesanan_aktif'];

// 2. AMBIL DATA DARI DATABASE
// =======================================================
try {
    // Ambil data pesanan utama (nama, email, total, dll)
    $stmt_pesanan = $koneksi->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
    $stmt_pesanan->bind_param("s", $id_pesanan); // "s" karena ID Anda VARCHAR
    $stmt_pesanan->execute();
    $pesanan = $stmt_pesanan->get_result()->fetch_assoc();

    if (!$pesanan) {
        throw new Exception("Data pesanan tidak ditemukan di database.");
    }

    // Ambil detail item pesanan (item apa saja yang dibeli)

    $stmt_detail = $koneksi->prepare("SELECT dp.id_produk, dp.jumlah, dp.harga_saat_transaksi, p.nama_produk FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id_produk WHERE dp.id_pesanan = ?");
    $stmt_detail->bind_param("s", $id_pesanan); // "s" karena ID Anda VARCHAR
    $stmt_detail->execute();
    $detail_items = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($detail_items)) {
        throw new Exception("Detail item untuk pesanan ini tidak ditemukan.");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal mengambil data pesanan: ' . $e->getMessage()]);
    exit;
}

// 3. SIAPKAN PARAMETER UNTUK MIDTRANS
// =======================================================
// Konfigurasi Midtrans (GANTI SERVER KEY ANDA)
\Midtrans\Config::$serverKey = 'SB-Mid-server-m7XR9SAjhEL4DjuqSc1o-qOM'; 
\Midtrans\Config::$isProduction = false;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Siapkan array untuk item_details
$items_details_midtrans = [];
foreach ($detail_items as $item) {
    $items_details_midtrans[] = [
        'id'       => $item['id_produk'],
        'price'    => (int)$item['harga_saat_transaksi'],
        'quantity' => (int)$item['jumlah'],
        'name'     => $item['nama_produk']
    ];
}

// Siapkan parameter lengkap
$params = [
    'transaction_details' => [
        'order_id' => $pesanan['id_pesanan'],
        'gross_amount' => (int)$pesanan['total_harga'],
    ],
    'item_details' => $items_details_midtrans,
    'customer_details' => [
        'first_name' => $pesanan['nama_pemesan'],
        'email'      => $pesanan['email'],
        'phone'      => $pesanan['no_telepon'],
    ],
];

// 4. MINTA SNAP TOKEN DAN KIRIM KE FRONTEND
// =======================================================
try {
    $snapToken = \Midtrans\Snap::getSnapToken($params);
    
    // Kirim response sukses dalam format JSON
    header('Content-Type: application/json');
    echo json_encode(['snap_token' => $snapToken, 'order_id' => $pesanan['id_pesanan']]);

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Midtrans Error: ' . $e->getMessage()]);
}
?>