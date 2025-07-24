<?php
// Selalu mulai session di awal
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Panggil file koneksi Anda
// Pastikan path ini benar sesuai struktur folder Anda
require_once __DIR__ . '/backend/koneksi.php';

// Keamanan: Hanya izinkan akses via POST dan pastikan ada session pesanan
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['id_pesanan_aktif'])) {
    // Jika tidak, arahkan ke halaman utama
    header('Location: index.php');
    exit;
}

// 1. Ambil ID pesanan dari session
$id_pesanan = $_SESSION['id_pesanan_aktif'];

// 2. Siapkan data baru untuk diupdate
$status_baru = 'menunggu_pembayaran_tunai'; // Status khusus untuk alur ini
$metode_pembayaran_baru = 'Tunai';

// 3. Lakukan UPDATE ke database menggunakan prepared statement
$stmt = $koneksi->prepare(
    "UPDATE pesanan SET status_pesanan = ?, metode_pembayaran = ? WHERE id_pesanan = ?"
);
// "sss" berarti tipe datanya: string, string, string (karena id_pesanan Anda VARCHAR)
$stmt->bind_param("sss", $status_baru, $metode_pembayaran_baru, $id_pesanan);

// Eksekusi query
if ($stmt->execute()) {
    // Jika update berhasil:
    
    // 4. Hapus session agar pesanan tidak bisa diproses ulang
    unset($_SESSION['id_pesanan_aktif']);

    // 5. Arahkan pengguna ke halaman konfirmasi
    header('Location: konfirmasi_tunai.php?order_id=' . urlencode($id_pesanan));
    exit();

} else {
    // Jika karena suatu alasan query gagal, berikan pesan error
    die("Terjadi kesalahan. Tidak dapat memperbarui status pesanan.");
}
?>