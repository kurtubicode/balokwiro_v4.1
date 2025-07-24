<?php
// Atur header untuk memberitahu browser bahwa responsnya adalah JSON
header('Content-Type: application/json');

// Panggil file koneksi database
require_once '../koneksi.php';

// Ambil order_id dari parameter URL
$order_id = $_GET['order_id'] ?? null;

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Order ID tidak disediakan.']);
    exit;
}

$query = "SELECT metode_pembayaran FROM pesanan WHERE id_pesanan = ?";
$stmt = mysqli_prepare($koneksi, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    if ($data) {
        // Ambil nilai mentah dari database
        $metode_raw = $data['metode_pembayaran'];
        $metode_display = '';

        // -- BAGIAN LOGIKA PENERJEMAHAN DIMULAI DI SINI --
        switch ($metode_raw) {
            case 'qris':
                $metode_display = 'QRIS';
                break;
            case 'echannel':
                $metode_display = 'Bank Transfer';
                break;
            case 'cstore':
                $metode_display = 'Indomaret / Alfamart';
                break;
            // Tambahkan case lain jika ada metode pembayaran lain
            // contoh: case 'credit_card': $metode_display = 'Kartu Kredit'; break;

            default:
                // Jika tidak ada yang cocok, format saja agar lebih rapi
                // contoh: 'bank_transfer' akan menjadi 'Bank Transfer'
                $metode_display = ucwords(str_replace('_', ' ', $metode_raw));
                break;
        }
        // -- BAGIAN LOGIKA PENERJEMAHAN SELESAI --

        // Kirim respons sukses dengan data yang sudah diterjemahkan
        echo json_encode(['success' => true, 'metode_pembayaran' => $metode_display]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pesanan tidak ditemukan.']);
    }

    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyiapkan query ke database.']);
}

mysqli_close($koneksi);
?>