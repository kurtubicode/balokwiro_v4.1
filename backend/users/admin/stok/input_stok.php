<?php
session_start();
include('../../../koneksi.php');

// Otentikasi & Otorisasi
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['jabatan'], ['admin', 'owner'])) {
    header('Location: ../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keterangan = $_POST['keterangan'] ?? 'Update stok oleh admin';
    // Ambil jenis aksi dari tombol yang ditekan
    $jenis_aksi = $_POST['jenis_aksi'] ?? 'penambahan'; 

    mysqli_begin_transaction($koneksi);
    try {
        // Proses Stok Kategori
        if (isset($_POST['stok_kategori'])) {
            foreach ($_POST['stok_kategori'] as $id => $jumlah) {
                if (is_numeric($jumlah) && $jumlah !== '') {
                    $jumlah_int = (int)$jumlah;
                    $jumlah_perubahan = $jumlah_int;

                    // Jika aksinya 'Set Stok Awal', kita hitung selisihnya
                    if ($jenis_aksi === 'stok_awal') {
                        $res = mysqli_query($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok = '$id'");
                        $stok_sekarang = mysqli_fetch_assoc($res)['total'] ?? 0;
                        $jumlah_perubahan = $jumlah_int - $stok_sekarang; // Perubahan adalah selisihnya
                    }
                    
                    // Hanya insert ke log jika ada perubahan
                    if ($jumlah_perubahan != 0) {
                        $stmt = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_kategori_stok, jumlah_perubahan, jenis_aksi, keterangan) VALUES (?, ?, ?, ?)");
                        mysqli_stmt_bind_param($stmt, "siss", $id, $jumlah_perubahan, $jenis_aksi, $keterangan);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
        }
        // Proses Stok Individu (logika sama)
        if (isset($_POST['stok_individu'])) {
            foreach ($_POST['stok_individu'] as $id => $jumlah) {
                if (is_numeric($jumlah) && $jumlah !== '') {
                    $jumlah_int = (int)$jumlah;
                    $jumlah_perubahan = $jumlah_int;

                    if ($jenis_aksi === 'stok_awal') {
                        $res = mysqli_query($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk = '$id'");
                        $stok_sekarang = mysqli_fetch_assoc($res)['total'] ?? 0;
                        $jumlah_perubahan = $jumlah_int - $stok_sekarang;
                    }

                    if ($jumlah_perubahan != 0) {
                        $stmt = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, jumlah_perubahan, jenis_aksi, keterangan) VALUES (?, ?, ?, ?)");
                        mysqli_stmt_bind_param($stmt, "siss", $id, $jumlah_perubahan, $jenis_aksi, $keterangan);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
        }
        mysqli_commit($koneksi);
        $_SESSION['notif'] = ['pesan' => 'Stok berhasil diperbarui.', 'tipe' => 'success'];
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['notif'] = ['pesan' => 'Gagal memperbarui stok: ' . $e->getMessage(), 'tipe' => 'danger'];
    }
    
    // Kembali ke halaman laporan
    header('Location: laporan_stok.php'); 
    exit;
}
?>