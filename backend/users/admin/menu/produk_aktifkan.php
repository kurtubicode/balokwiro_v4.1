<?php
// 1. Mulai Sesi dan Panggil Koneksi
session_start();
include('../../../koneksi.php');

// 2. Keamanan: Cek Sesi Login dan Hak Akses
// Hanya admin dan owner yang boleh melakukan aksi ini
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    header('Location: ../../login.php');
    exit;
}

// 3. Validasi Input: Pastikan ID produk ada di URL
if (isset($_GET['id'])) {
    $id_produk = $_GET['id'];

    // 4. Siapkan Query Update yang Aman dengan Prepared Statement
    // Ini mengubah status produk kembali menjadi 'aktif'
    $query = "UPDATE produk SET status_produk = 'aktif' WHERE id_produk = ?";
    $stmt = mysqli_prepare($koneksi, $query);

    if ($stmt) {
        // Bind parameter ke query
        mysqli_stmt_bind_param($stmt, "s", $id_produk);

        // 5. Eksekusi Query dan Beri Notifikasi
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['notif'] = [
                'pesan' => 'Produk berhasil diaktifkan kembali.',
                'tipe' => 'success'
            ];
        } else {
            $_SESSION['notif'] = [
                'pesan' => 'Gagal mengaktifkan produk.',
                'tipe' => 'danger'
            ];
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['notif'] = [
            'pesan' => 'Terjadi kesalahan pada query.',
            'tipe' => 'danger'
        ];
    }
} else {
    $_SESSION['notif'] = [
        'pesan' => 'Aksi tidak valid, ID produk tidak ditemukan.',
        'tipe' => 'warning'
    ];
}

// 6. Alihkan Kembali ke Halaman Daftar Menu
// Ganti 'data_produk.php' jika nama file Anda adalah 'datamenu.php' atau lainnya
header('Location: data_menu.php'); 
exit;
?>