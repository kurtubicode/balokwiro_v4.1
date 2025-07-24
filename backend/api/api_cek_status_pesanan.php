<?php
// File: api_cek_status.php
header('Content-Type: application/json');
require_once __DIR__ . '/../koneksi.php'; // Sesuaikan path

$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['sukses' => false, 'pesan' => 'ID Pesanan tidak disediakan.']);
    exit;
}

$stmt = $koneksi->prepare("SELECT status_pesanan, metode_pembayaran FROM pesanan WHERE id_pesanan = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_assoc();

if ($pesanan) {
    echo json_encode([
        'sukses' => true, 
        'status' => $pesanan['status_pesanan'],
        'metode_pembayaran' => $pesanan['metode_pembayaran'] ?? ''
    ]);
} else {
    echo json_encode(['sukses' => false, 'status' => 'tidak_ditemukan']);
}

$stmt->close();
$koneksi->close();
?>