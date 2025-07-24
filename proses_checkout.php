<?php
// Selalu mulai session di awal
session_start();

// Panggil file koneksi Anda
require_once __DIR__ . '/backend/koneksi.php';

// 1. VALIDASI DAN AMBIL DATA
// =======================================================

// Hanya izinkan akses melalui metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Ambil semua data dari form
$nama_pemesan = $_POST['nama_pemesan'] ?? 'Pelanggan';
$email_pemesan = $_POST['email_pemesan'] ?? 'N/A';
$telepon_pemesan = $_POST['telepon_pemesan'] ?? 'N/A';
$jenis_pesanan = $_POST['jenis_pesanan'] ?? 'take_away';
$catatan = $_POST['catatan'] ?? '';
$keranjangJSON = $_POST['keranjang_data'] ?? '{}';
$keranjang = json_decode($keranjangJSON, true);

if (empty($keranjang)) {
    die("Keranjang Anda kosong. Silakan kembali dan pilih produk.");
}


// 2. PROSES PENYIMPANAN KE DATABASE
// =======================================================

$total_harga = 0; // KOREKSI: Inisialisasi variabel di sini untuk menghilangkan warning
$id_pesanan_baru = "ONLINE-" . time(); // Membuat ID manual sesuai keinginan Anda

$koneksi->autocommit(FALSE); // Mulai transaksi

try {
    // Langkah 2a: Hitung total harga dari data keranjang
    foreach ($keranjang as $item) {
        if (is_array($item) && isset($item['harga'], $item['jumlah'])) {
            $total_harga += $item['harga'] * $item['jumlah'];
        }
    }
    if ($total_harga <= 0) {
        throw new Exception("Total harga tidak valid.");
    }

    // Langkah 2b: Siapkan data lain dan simpan ke tabel `pesanan`
    $id_karyawan_online = 3; // ID untuk 'user' sistem/online
    $status_pesanan = 'menunggu_pilihan_pembayaran'; 
    $tipe_pesanan = 'online';
    $metode_pembayaran = null;

    // KOREKSI: Menggunakan query INSERT yang lengkap dan benar
    $stmt_pesanan = $koneksi->prepare(
        "INSERT INTO pesanan (id_pesanan, id_karyawan, tipe_pesanan, jenis_pesanan, nama_pemesan, email, no_telepon, catatan, tgl_pesanan, total_harga, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)"
    );
    $stmt_pesanan->bind_param("sissssssdss", $id_pesanan_baru, $id_karyawan_online, $tipe_pesanan, $jenis_pesanan, $nama_pemesan, $email_pemesan, $telepon_pemesan, $catatan, $total_harga, $metode_pembayaran, $status_pesanan);
    $stmt_pesanan->execute();

    // Langkah 2c: Simpan setiap item keranjang ke tabel `detail_pesanan`
    $stmt_detail = $koneksi->prepare(
        "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_saat_transaksi, sub_total) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($keranjang as $id_produk => $item) {
        $subtotal_item = $item['harga'] * $item['jumlah'];
        $stmt_detail->bind_param("ssidd", $id_pesanan_baru, $id_produk, $item['jumlah'], $item['harga'], $subtotal_item);
        $stmt_detail->execute();
    }

    $koneksi->commit();
} catch (Exception $e) {
    $koneksi->rollback();
    die("Terjadi kesalahan saat menyimpan pesanan: " . $e->getMessage());
}

$koneksi->autocommit(TRUE);


// 3. SIMPAN ID KE SESSION & ARAHKAN PENGGUNA
// =======================================================
$_SESSION['id_pesanan_aktif'] = $id_pesanan_baru;

echo "
<script>
    localStorage.removeItem('kueBalokCart');
    window.location.href = 'pilih_pembayaran.php';
</script>
";
exit();
?>