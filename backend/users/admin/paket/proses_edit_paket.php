<?php
// 1. Mulai sesi
session_start();

// 2. Sesuaikan dengan koneksi Anda
include('../../../koneksi.php');

// 3. KEAMANAN: Cek hak akses
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    $_SESSION['notif'] = ['tipe' => 'danger', 'pesan' => 'Anda tidak memiliki hak akses.'];
    header('Location: ../../../login.php');
    exit;
}

// 4. Cek apakah form disubmit dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil semua data dari form dan amankan
    $id_paket = mysqli_real_escape_string($koneksi, $_POST['id_paket']);
    $nama_paket = mysqli_real_escape_string($koneksi, $_POST['nama_paket']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $harga_paket = (int)$_POST['harga_paket'];
    $items_json = $_POST['items_json'];
    $poto_lama = mysqli_real_escape_string($koneksi, $_POST['poto_lama']);
    
    // Decode data JSON
    $items = json_decode($items_json, true);

    // Validasi dasar
    if (empty($id_paket) || empty($nama_paket) || empty($harga_paket) || empty($items)) {
        $_SESSION['notif'] = ['tipe' => 'danger', 'pesan' => 'Semua field wajib diisi dan minimal ada 1 item dalam paket.'];
        header('Location: paket_edit.php?id=' . $id_paket);
        exit;
    }

    // --- LOGIKA UPLOAD GAMBAR BARU (JIKA ADA) ---
    $poto_paket_nama = $poto_lama; // Secara default, gunakan foto lama
    $upload_dir = '../../../assets/img/paket/';
    
    if (isset($_FILES['poto_paket']) && $_FILES['poto_paket']['error'] == 0) {
        // Ada file baru yang diupload, proses file tersebut
        $file_tmp = $_FILES['poto_paket']['tmp_name'];
        $file_nama_asli = basename($_FILES['poto_paket']['name']);
        $file_ext = strtolower(pathinfo($file_nama_asli, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $poto_paket_nama = "paket_" . time() . "." . $file_ext; // Buat nama unik baru
            if (!move_uploaded_file($file_tmp, $upload_dir . $poto_paket_nama)) {
                 $_SESSION['notif'] = ['tipe' => 'danger', 'pesan' => 'Gagal mengupload foto baru.'];
                 header('Location: paket_edit.php?id=' . $id_paket);
                 exit;
            }
        } else {
            $_SESSION['notif'] = ['tipe' => 'danger', 'pesan' => 'Format file foto tidak valid.'];
            header('Location: paket_edit.php?id=' . $id_paket);
            exit;
        }
    }

    // --- LOGIKA DATABASE DENGAN TRANSAKSI ---
    mysqli_begin_transaction($koneksi);

    try {
        // 1. UPDATE data di tabel 'paket'
        $query_paket = "UPDATE paket SET nama_paket = ?, deskripsi = ?, harga_paket = ?, poto_paket = ? WHERE id_paket = ?";
        $stmt_paket = mysqli_prepare($koneksi, $query_paket);
        mysqli_stmt_bind_param($stmt_paket, 'ssiss', $nama_paket, $deskripsi, $harga_paket, $poto_paket_nama, $id_paket);
        if (!mysqli_stmt_execute($stmt_paket)) {
            throw new Exception("Gagal mengupdate data paket utama.");
        }
        mysqli_stmt_close($stmt_paket);

        // 2. HAPUS semua item lama dari 'detail_paket' yang terkait dengan id_paket ini
        $query_delete_items = "DELETE FROM detail_paket WHERE id_paket = ?";
        $stmt_delete = mysqli_prepare($koneksi, $query_delete_items);
        mysqli_stmt_bind_param($stmt_delete, 's', $id_paket);
        if (!mysqli_stmt_execute($stmt_delete)) {
            throw new Exception("Gagal menghapus rincian item lama.");
        }
        mysqli_stmt_close($stmt_delete);
        
        // 3. INSERT kembali semua item baru dari form ke 'detail_paket'
        $query_insert_items = "INSERT INTO detail_paket (id_paket, id_produk, jumlah) VALUES (?, ?, ?)";
        $stmt_insert = mysqli_prepare($koneksi, $query_insert_items);
        foreach ($items as $item) {
            $id_produk = $item['id_produk'];
            $jumlah = $item['jumlah'];
            mysqli_stmt_bind_param($stmt_insert, 'ssi', $id_paket, $id_produk, $jumlah);
            if (!mysqli_stmt_execute($stmt_insert)) {
                throw new Exception("Gagal menyimpan rincian item baru.");
            }
        }
        mysqli_stmt_close($stmt_insert);

        // Jika semua query berhasil, commit transaksi
        mysqli_commit($koneksi);
        
        // Hapus foto lama JIKA ada foto baru yang berhasil diupload DAN foto lama bukan default
        if ($poto_paket_nama != $poto_lama && $poto_lama != 'default-paket.jpg') {
            if (file_exists($upload_dir . $poto_lama)) {
                unlink($upload_dir . $poto_lama);
            }
        }

        $_SESSION['notif'] = ['tipe' => 'success', 'pesan' => 'Paket berhasil diupdate!'];
        header('Location: data_paket.php');
        exit;

    } catch (Exception $e) {
        // Jika terjadi error, rollback semua perubahan database
        mysqli_rollback($koneksi);

        // Hapus file baru yang terlanjur diupload jika terjadi error
        if ($poto_paket_nama != $poto_lama && file_exists($upload_dir . $poto_paket_nama)) {
            unlink($upload_dir . $poto_paket_nama);
        }
        
        $_SESSION['notif'] = ['tipe' => 'danger', 'pesan' => 'Terjadi kesalahan: ' . $e->getMessage()];
        header('Location: paket_edit.php?id=' . $id_paket);
        exit;
    }
} else {
    header('Location: data_paket.php');
    exit;
}
?>