<?php
header('Content-Type: application/json');
require_once '../koneksi.php'; // Sesuaikan path jika koneksi.php ada di root

// Validasi input ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => true, 'message' => 'ID Pesanan tidak valid.']);
    exit;
}
$id_pesanan = $_GET['id'];

$response = ['error' => true, 'message' => 'Pesanan tidak ditemukan.'];

try {
    // Ambil data header pesanan
    $stmt_header = mysqli_prepare($koneksi, "SELECT p.*, k.nama as nama_kasir FROM pesanan p LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan WHERE p.id_pesanan = ?");
    mysqli_stmt_bind_param($stmt_header, "s", $id_pesanan);
    mysqli_stmt_execute($stmt_header);
    $header_result = mysqli_stmt_get_result($stmt_header);
    $header_data = mysqli_fetch_assoc($header_result);

    if ($header_data) {
        // Ambil data item-item pesanan
        $stmt_items = mysqli_prepare($koneksi, "SELECT dp.*, pr.nama_produk FROM detail_pesanan dp JOIN produk pr ON dp.id_produk = pr.id_produk WHERE dp.id_pesanan = ?");
        mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
        mysqli_stmt_execute($stmt_items);
        $items_result = mysqli_stmt_get_result($stmt_items);
        $items_data = mysqli_fetch_all($items_result, MYSQLI_ASSOC);

        // Susun data dalam format JSON yang rapi
        $response = [
            'error' => false,
            'data' => [
                'header' => $header_data,
                'items' => $items_data
            ]
        ];
    }
} catch (Exception $e) {
    $response = ['error' => true, 'message' => 'Terjadi error di server: ' . $e->getMessage()];
    http_response_code(500);
}

echo json_encode($response);
