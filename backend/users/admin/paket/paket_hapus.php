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

    // --- MULAI PROSES PENGHAPUSAN ---

    // 5. Ambil nama file foto sebelum menghapus data dari database
    $query_foto = "SELECT poto_paket FROM paket WHERE id_paket = ?";
    $stmt_foto = mysqli_prepare($koneksi, $query_foto);
    mysqli_stmt_bind_param($stmt_foto, 's', $id_paket);
    mysqli_stmt_execute($stmt_foto);
    $result_foto = mysqli_stmt_get_result($stmt_foto);
    
    if ($row_foto = mysqli_fetch_assoc($result_foto)) {
        $nama_file_foto = $row_foto['poto_paket'];

        // 6. Hapus data dari database
        $query_hapus = "DELETE FROM paket WHERE id_paket = ?";
        $stmt_hapus = mysqli_prepare($koneksi, $query_hapus);
        mysqli_stmt_bind_param($stmt_hapus, 's', $id_paket);

        if (mysqli_stmt_execute($stmt_hapus)) {
            // Jika data di DB berhasil dihapus, lanjutkan hapus file fotonya
            $path_foto = '../../../assets/img/paket/' . $nama_file_foto;
            
            // Hapus file foto jika bukan file default dan file tersebut ada
            if ($nama_file_foto != 'default-paket.jpg' && file_exists($path_foto)) {
                unlink($path_foto);
            }

            $_SESSION['notif'] = [
                'tipe' => 'success',
                'pesan' => 'Paket berhasil dihapus secara permanen.'
            ];
        } else {
            $_SESSION['notif'] = [
                'tipe' => 'danger',
                'pesan' => 'Gagal menghapus paket dari database.'
            ];
        }
        mysqli_stmt_close($stmt_hapus);
    } else {
        $_SESSION['notif'] = [
            'tipe' => 'danger',
            'pesan' => 'Paket tidak ditemukan.'
        ];
    }
    mysqli_stmt_close($stmt_foto);
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