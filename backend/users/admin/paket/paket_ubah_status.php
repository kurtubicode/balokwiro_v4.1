<?php
// 1. Mulai sesi
session_start();

// 2. Sesuaikan dengan koneksi Anda
include('../../../koneksi.php');

// 3. KEAMANAN: Cek hak akses. Hanya admin dan owner yang boleh mengakses.
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    $_SESSION['notif'] = ['tipe' => 'danger', 'pesan' => 'Anda tidak memiliki hak akses.'];
    header('Location: ../../../login.php');
    exit;
}

// 4. Pastikan ID paket diterima dari URL
if (isset($_GET['id'])) {
    $id_paket = mysqli_real_escape_string($koneksi, $_GET['id']);

    // 5. Ambil status paket saat ini dari database
    $query_cek = "SELECT status_paket FROM paket WHERE id_paket = ?";
    $stmt_cek = mysqli_prepare($koneksi, $query_cek);
    mysqli_stmt_bind_param($stmt_cek, 's', $id_paket);
    mysqli_stmt_execute($stmt_cek);
    $result_cek = mysqli_stmt_get_result($stmt_cek);

    if ($row = mysqli_fetch_assoc($result_cek)) {
        $status_sekarang = $row['status_paket'];

        // 6. Tentukan status baru (kebalikannya)
        $status_baru = ($status_sekarang == 'aktif') ? 'tidak aktif' : 'aktif';

        // 7. Update status baru ke database
        $query_update = "UPDATE paket SET status_paket = ? WHERE id_paket = ?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, 'ss', $status_baru, $id_paket);
        
        if (mysqli_stmt_execute($stmt_update)) {
            $_SESSION['notif'] = [
                'tipe' => 'success',
                'pesan' => 'Status paket berhasil diubah menjadi ' . $status_baru . '.'
            ];
        } else {
            $_SESSION['notif'] = [
                'tipe' => 'danger',
                'pesan' => 'Gagal mengubah status paket.'
            ];
        }
        mysqli_stmt_close($stmt_update);
    } else {
        $_SESSION['notif'] = [
            'tipe' => 'danger',
            'pesan' => 'Paket tidak ditemukan.'
        ];
    }
    mysqli_stmt_close($stmt_cek);
} else {
    $_SESSION['notif'] = [
        'tipe' => 'warning',
        'pesan' => 'Tidak ada ID paket yang dipilih.'
    ];
}

// 8. Kembalikan pengguna ke halaman data paket
header('Location: data_paket.php');
exit;
?>