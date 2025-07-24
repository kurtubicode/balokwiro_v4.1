<?php
session_start();
include('../../../koneksi.php');

// Cek hak akses, hanya owner yang bisa hapus
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// Pastikan ada id_karyawan yang dikirim
if (isset($_GET['id_karyawan'])) {
    $id_target_hapus = $_GET['id_karyawan'];
    $id_user_login = $_SESSION['user']['id'];

    // --- PENGAMAN 1: CEGAH OWNER MENGHAPUS DIRINYA SENDIRI ---
    if ($id_target_hapus == $id_user_login) {
        $_SESSION['notif'] = ['pesan' => 'Error! Anda tidak bisa menghapus akun Anda sendiri.', 'tipe' => 'danger'];
        header("Location: data_karyawan.php");
        exit;
    }

    // Ambil data user yang akan dihapus untuk cek jabatan
    $stmt_check = mysqli_prepare($koneksi, "SELECT jabatan FROM karyawan WHERE id_karyawan = ?");
    mysqli_stmt_bind_param($stmt_check, "i", $id_target_hapus);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $target_user = mysqli_fetch_assoc($result_check);

    if ($target_user) {
        // --- PENGAMAN 2: CEGAH OWNER DIHAPUS ---
        if ($target_user['jabatan'] === 'owner') {
            $_SESSION['notif'] = ['pesan' => 'Error! Akun dengan jabatan "Owner" tidak dapat dihapus.', 'tipe' => 'danger'];
            header("Location: data_karyawan.php");
            exit;
        }

        // Jika semua pengecekan aman, lanjutkan proses hapus
        $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM karyawan WHERE id_karyawan = ?");
        mysqli_stmt_bind_param($stmt_delete, "i", $id_target_hapus);

        if (mysqli_stmt_execute($stmt_delete)) {
            $_SESSION['notif'] = ['pesan' => 'Data karyawan berhasil dihapus.', 'tipe' => 'success'];
        } else {
            $_SESSION['notif'] = ['pesan' => 'Gagal menghapus data karyawan.', 'tipe' => 'danger'];
        }
    } else {
        $_SESSION['notif'] = ['pesan' => 'Karyawan tidak ditemukan.', 'tipe' => 'warning'];
    }
} else {
    $_SESSION['notif'] = ['pesan' => 'Aksi tidak valid.', 'tipe' => 'warning'];
}

header("Location: data_karyawan.php");
exit;
