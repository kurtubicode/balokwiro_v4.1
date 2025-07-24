<?php
// 1. Mulai sesi
session_start();

// 2. Sesuaikan dengan koneksi Anda
include('../../../koneksi.php');

// 3. KEAMANAN: Cek hak akses. Hanya admin dan owner yang boleh mengakses.
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    // Set notifikasi untuk akses tidak sah
    $_SESSION['notif'] = [
        'tipe' => 'danger',
        'pesan' => 'Anda tidak memiliki hak akses untuk halaman ini.'
    ];
    header('Location: ../../../login.php');
    exit;
}

// 4. Cek apakah form disubmit dengan metode POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Ambil data dari form dan amankan
    $nama_paket = mysqli_real_escape_string($koneksi, $_POST['nama_paket']);
    $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $harga_paket = (int)$_POST['harga_paket'];
    $items_json = $_POST['items_json'];
    
    // Decode data JSON dari item-item paket
    $items = json_decode($items_json, true);

    // Validasi dasar
    if (empty($nama_paket) || empty($harga_paket) || empty($items)) {
        $_SESSION['notif'] = [
            'tipe' => 'danger',
            'pesan' => 'Semua field wajib diisi dan minimal ada 1 item dalam paket.'
        ];
        header('Location: tambah_paket.php');
        exit;
    }

    // --- LOGIKA UPLOAD GAMBAR ---
    $poto_paket_nama = "default-paket.jpg"; // Nama default jika tidak ada gambar diupload
    if (isset($_FILES['poto_paket']) && $_FILES['poto_paket']['error'] == 0) {
        $upload_dir = '../../../assets/img/paket/'; // Pastikan folder ini ada dan bisa ditulisi
        $file_tmp = $_FILES['poto_paket']['tmp_name'];
        $file_nama_asli = basename($_FILES['poto_paket']['name']);
        $file_ext = strtolower(pathinfo($file_nama_asli, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            // Buat nama file unik untuk menghindari konflik
            $poto_paket_nama = "paket_" . time() . "." . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $poto_paket_nama);
        } else {
            $_SESSION['notif'] = [
                'tipe' => 'danger',
                'pesan' => 'Hanya file gambar (JPG, PNG, GIF) yang diizinkan.'
            ];
            header('Location: tambah_paket.php');
            exit;
        }
    }

    // --- LOGIKA DATABASE DENGAN TRANSAKSI ---
    mysqli_begin_transaction($koneksi);

    try {
        // Buat ID Paket baru (misal: PKT001, PKT002, dst.)
        $query_last_id = "SELECT id_paket FROM paket ORDER BY id_paket DESC LIMIT 1";
        $result_last_id = mysqli_query($koneksi, $query_last_id);
        $last_id_row = mysqli_fetch_assoc($result_last_id);
        
        if ($last_id_row) {
            $last_num = (int)substr($last_id_row['id_paket'], 3);
            $new_num = $last_num + 1;
            $id_paket_baru = "PKT" . str_pad($new_num, 3, "0", STR_PAD_LEFT);
        } else {
            $id_paket_baru = "PKT001";
        }
        
        // 1. Insert data ke tabel 'paket'
        $query_paket = "INSERT INTO paket (id_paket, nama_paket, deskripsi, harga_paket, poto_paket, status_paket) VALUES (?, ?, ?, ?, ?, 'aktif')";
        $stmt_paket = mysqli_prepare($koneksi, $query_paket);
        mysqli_stmt_bind_param($stmt_paket, 'ssids', $id_paket_baru, $nama_paket, $deskripsi, $harga_paket, $poto_paket_nama);
        
        if (!mysqli_stmt_execute($stmt_paket)) {
            throw new Exception("Gagal menyimpan data paket utama.");
        }
        mysqli_stmt_close($stmt_paket);

        // 2. Insert data ke tabel 'detail_paket'
        $query_detail = "INSERT INTO detail_paket (id_paket, id_produk, jumlah) VALUES (?, ?, ?)";
        $stmt_detail = mysqli_prepare($koneksi, $query_detail);

        foreach ($items as $item) {
            $id_produk = $item['id_produk'];
            $jumlah = $item['jumlah'];
            mysqli_stmt_bind_param($stmt_detail, 'ssi', $id_paket_baru, $id_produk, $jumlah);
            
            if (!mysqli_stmt_execute($stmt_detail)) {
                throw new Exception("Gagal menyimpan rincian item paket.");
            }
        }
        mysqli_stmt_close($stmt_detail);

        // Jika semua query berhasil, commit transaksi
        mysqli_commit($koneksi);
        $_SESSION['notif'] = [
            'tipe' => 'success',
            'pesan' => 'Paket baru berhasil ditambahkan!'
        ];
        header('Location: data_paket.php');
        exit;

    } catch (Exception $e) {
        // Jika terjadi error, rollback semua perubahan
        mysqli_rollback($koneksi);
        
        // Hapus file gambar yang sudah terupload jika terjadi error database
        if ($poto_paket_nama !== "default-paket.jpg" && file_exists($upload_dir . $poto_paket_nama)) {
            unlink($upload_dir . $poto_paket_nama);
        }
        
        $_SESSION['notif'] = [
            'tipe' => 'danger',
            'pesan' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
        header('Location: tambah_paket.php');
        exit;
    }

} else {
    // Jika halaman diakses langsung tanpa POST, kembalikan ke halaman data paket
    header('Location: data_paket.php');
    exit;
}
?>