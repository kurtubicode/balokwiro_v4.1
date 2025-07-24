<?php
// 1. Mulai Sesi dan Panggil Koneksi
session_start();
include('../../../koneksi.php');

// 2. Keamanan: Cek Sesi Login dan Hak Akses (Role)
// Hanya admin dan owner yang boleh melakukan aksi ini
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    // Arahkan ke halaman login jika tidak memiliki hak akses
    header('Location: ../../login.php');
    exit;
}

// 3. Validasi Input: Pastikan ID produk ada di URL
if (isset($_GET['id'])) {
    $id_produk = $_GET['id'];

    // 4. Siapkan Query Update yang Aman dengan Prepared Statement
    // Ini mengubah status produk menjadi 'tidak aktif'
    $query = "UPDATE produk SET status_produk = 'tidak aktif' WHERE id_produk = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    // Periksa apakah statement berhasil disiapkan
    if ($stmt) {
        // Bind parameter ke query
        mysqli_stmt_bind_param($stmt, "s", $id_produk);

        // 5. Eksekusi Query dan Beri Notifikasi
        if (mysqli_stmt_execute($stmt)) {
            // Jika berhasil, siapkan notifikasi sukses
            $_SESSION['notif'] = [
                'pesan' => 'Produk berhasil dinonaktifkan.',
                'tipe' => 'success'
            ];
        } else {
            // Jika gagal, siapkan notifikasi error
            $_SESSION['notif'] = [
                'pesan' => 'Gagal menonaktifkan produk.',
                'tipe' => 'danger'
            ];
        }
        // Tutup statement
        mysqli_stmt_close($stmt);
    } else {
        // Jika statement gagal disiapkan
        $_SESSION['notif'] = [
            'pesan' => 'Terjadi kesalahan pada query.',
            'tipe' => 'danger'
        ];
    }
} else {
    // Jika tidak ada ID di URL, siapkan notifikasi peringatan
    $_SESSION['notif'] = [
        'pesan' => 'Aksi tidak valid, ID produk tidak ditemukan.',
        'tipe' => 'warning'
    ];
}

// 6. Alihkan Kembali ke Halaman Daftar Menu
// Ganti 'data_produk.php' jika nama file Anda berbeda
header('Location: data_menu.php');
exit;
?>