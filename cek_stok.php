<?php
// Mengatur header agar output dikenali sebagai JSON
header('Content-Type: application/json');
include 'backend/koneksi.php';

// Siapkan response default
$response = ['stok' => 0, 'error' => true, 'message' => 'Terjadi kesalahan tidak diketahui.'];

// Pastikan ID produk disediakan di URL
if (!isset($_GET['id'])) {
    $response['message'] = 'ID produk tidak disediakan.';
    echo json_encode($response);
    exit;
}

$id_produk = $_GET['id'];
$stok_saat_ini = 0;

try {
    // Langkah 1: Cek info produk untuk mengetahui tipe stoknya (individu atau kategori)
    $stmt_info = mysqli_prepare($koneksi, "SELECT status_produk, id_kategori_stok FROM produk WHERE id_produk = ?");
    mysqli_stmt_bind_param($stmt_info, "s", $id_produk);
    mysqli_stmt_execute($stmt_info);
    $result_info = mysqli_stmt_get_result($stmt_info);
    $info_produk = mysqli_fetch_assoc($result_info);

    // Jika produk tidak ada atau tidak aktif, kirim error
    if (!$info_produk || $info_produk['status_produk'] !== 'aktif') {
        $response['message'] = 'Produk tidak ditemukan atau tidak aktif.';
        echo json_encode($response);
        exit;
    }

    // Langkah 2: Hitung stok berdasarkan tipenya dari tabel log_stok
    if ($info_produk['id_kategori_stok'] !== null) {
        // Jika produk menggunakan STOK KATEGORI
        $id_kategori = $info_produk['id_kategori_stok'];
        $stmt_stok = mysqli_prepare($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok = ?");
        mysqli_stmt_bind_param($stmt_stok, "s", $id_kategori);
    } else {
        // Jika produk menggunakan STOK INDIVIDU
        $stmt_stok = mysqli_prepare($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk = ?");
        mysqli_stmt_bind_param($stmt_stok, "s", $id_produk);
    }

    // Eksekusi query penghitungan stok
    mysqli_stmt_execute($stmt_stok);
    $result_stok = mysqli_stmt_get_result($stmt_stok);
    $data_stok = mysqli_fetch_assoc($result_stok);

    // Ambil nilai stok, jika null (belum ada histori), anggap 0
    $stok_saat_ini = $data_stok['total'] ?? 0;

    // Siapkan response sukses
    $response = [
        'stok' => (int)$stok_saat_ini,
        'error' => false,
        'message' => 'Stok berhasil diambil.'
    ];
} catch (Exception $e) {
    // Tangkap jika ada error database
    $response['message'] = 'Gagal mengambil data stok dari server.';
    // Anda bisa mencatat $e->getMessage() ke file log error untuk debugging
}

// Mengubah array PHP menjadi format JSON dan menampilkannya
echo json_encode($response);
