<?php
// File ini bisa di-include di halaman yang sering diakses, seperti public/includes/header.php
// agar pengecekan berjalan secara berkala saat ada aktivitas pengguna.

// Pastikan koneksi sudah ada. __DIR__ memastikan path selalu benar.
if (!isset($koneksi)) {
    include_once __DIR__ . '/koneksi.php';
}

// 1. Cari semua pesanan yang statusnya 'menunggu_pembayaran' dan sudah lewat 10 menit
$sql_cari = "SELECT id_pesanan FROM pesanan WHERE status_pesanan = 'menunggu_pembayaran' AND tgl_pesanan < NOW() - INTERVAL 10 MINUTE";
$result_cari = mysqli_query($koneksi, $sql_cari);

// Jika ditemukan ada pesanan yang kadaluarsa, proses satu per satu
if ($result_cari && mysqli_num_rows($result_cari) > 0) {

    // Siapkan statement di luar loop agar lebih efisien
    $stmt_items = mysqli_prepare($koneksi, "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?");
    $stmt_info = mysqli_prepare($koneksi, "SELECT id_kategori_stok FROM produk WHERE id_produk = ?");
    $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'pembatalan', ?, ?)");
    $stmt_batal = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ?");

    // 2. Loop setiap pesanan yang kadaluarsa
    while ($pesanan_kadaluarsa = mysqli_fetch_assoc($result_cari)) {
        $id_pesanan = $pesanan_kadaluarsa['id_pesanan'];

        // 3. Gunakan transaksi untuk setiap pesanan agar aman
        mysqli_begin_transaction($koneksi);
        try {
            // Ambil detail item dari pesanan yang akan dibatalkan
            mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            // 4. Kembalikan stok dengan mencatatnya di log_stok
            while ($item = mysqli_fetch_assoc($result_items)) {
                // Cek tipe stok produk ini
                mysqli_stmt_bind_param($stmt_info, "s", $item['id_produk']);
                mysqli_stmt_execute($stmt_info);
                $info_produk = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));

                $id_produk_log = null;
                $id_kategori_log = null;
                $jumlah_kembali = abs($item['jumlah']); // Pastikan nilainya positif
                $keterangan_log = "Otomatis (tidak dibayar > 10 menit)";

                if ($info_produk && $info_produk['id_kategori_stok'] !== null) {
                    $id_kategori_log = $info_produk['id_kategori_stok'];
                } else {
                    $id_produk_log = $item['id_produk'];
                }

                // Masukkan catatan pengembalian stok ke log
                mysqli_stmt_bind_param($stmt_log, "ssiss", $id_produk_log, $id_kategori_log, $jumlah_kembali, $id_pesanan, $keterangan_log);
                mysqli_stmt_execute($stmt_log);
            }

            // 5. Ubah status pesanan menjadi 'dibatalkan'
            mysqli_stmt_bind_param($stmt_batal, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_batal);

            // 6. Jika semua berhasil, simpan perubahan
            mysqli_commit($koneksi);
        } catch (Exception $e) {
            // Jika ada satu saja error, batalkan semua perubahan untuk pesanan ini
            mysqli_rollback($koneksi);
            // Optional: catat error ke file log
            error_log("Gagal membatalkan pesanan #$id_pesanan: " . $e->getMessage());
        }
    }
}
