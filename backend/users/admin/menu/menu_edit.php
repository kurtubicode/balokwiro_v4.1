<?php
session_start();
include('../../../koneksi.php');

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    header('Location: ../../../login.php');
    exit;
}

// 2. VALIDASI & AMBIL DATA PRODUK YANG AKAN DIEDIT
if (!isset($_GET['id_produk'])) {
    $_SESSION['notif'] = ['pesan' => 'Aksi tidak valid, ID produk tidak ditemukan.', 'tipe' => 'warning'];
    header('Location: data_menu.php');
    exit;
}
$id_produk = $_GET['id_produk'];

// Ambil data lama menggunakan prepared statement
$stmt = mysqli_prepare($koneksi, "SELECT * FROM produk WHERE id_produk = ?");
mysqli_stmt_bind_param($stmt, "s", $id_produk);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    $_SESSION['notif'] = ['pesan' => 'Data produk tidak ditemukan di database.', 'tipe' => 'warning'];
    header('Location: data_menu.php');
    exit;
}

// 3. PROSES SIMPAN PERUBAHAN (UPDATE)
if (isset($_POST['submit'])) {
    $nama_produk = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];
    $id_produk_update = $_POST['id_produk']; // Ambil ID dari hidden input

    $gambar_lama = $data['foto_produk'];
    $nama_gambar_baru = $_FILES['gambar']['name'];

    // Cek apakah ada gambar baru yang diupload
    if (!empty($nama_gambar_baru)) {
        $tmp = $_FILES['gambar']['tmp_name'];
        $path = "../../../assets/img/produk/" . $nama_gambar_baru;
        // Pindahkan file baru dan siapkan nama file untuk diupdate
        if (move_uploaded_file($tmp, $path)) {
            $gambar_untuk_db = $nama_gambar_baru;
            // Hapus gambar lama jika perlu (opsional)
            if (file_exists("../../../assets/img/produk/" . $gambar_lama) && $gambar_lama !== 'default.jpg') {
                unlink("../../../assets/img/produk/" . $gambar_lama);
            }
        } else {
            $_SESSION['notif'] = ['pesan' => 'Gagal mengupload gambar baru.', 'tipe' => 'danger'];
            header("Location: produk_edit.php?id_produk=" . $id_produk_update);
            exit;
        }
    } else {
        // Jika tidak ada gambar baru, gunakan nama gambar lama
        $gambar_untuk_db = $gambar_lama;
    }

    // Siapkan query UPDATE dengan prepared statement
    $query_update = "UPDATE produk SET nama_produk=?, harga=?, kategori=?, poto_produk=? WHERE id_produk=?";
    $stmt_update = mysqli_prepare($koneksi, $query_update);
    mysqli_stmt_bind_param($stmt_update, "sdsss", $nama_produk, $harga, $kategori, $gambar_untuk_db, $id_produk_update);

    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['notif'] = ['pesan' => 'Data produk berhasil diperbarui.', 'tipe' => 'success'];
    } else {
        $_SESSION['notif'] = ['pesan' => 'Gagal memperbarui data produk. Error: ' . mysqli_error($koneksi), 'tipe' => 'danger'];
    }
    header("Location: data_menu.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Menu - Admin</title>
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png"> 
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <?php include "../inc/navbar.php"; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include "../inc/sidebar.php"; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Edit Menu</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="data_menu.php">Data Menu</a></li>
                        <li class="breadcrumb-item active">Edit Menu</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-edit me-1"></i>Formulir Edit Menu</div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="id_produk" value="<?= htmlspecialchars($data['id_produk']) ?>">

                                <div class="mb-3">
                                    <label for="id_produk_display" class="form-label">ID Menu</label>
                                    <input type="text" class="form-control" id="id_produk_display" value="<?= htmlspecialchars($data['id_produk']) ?>" disabled>
                                </div>
                                <div class="mb-3">
                                    <label for="nama_produk" class="form-label">Nama Menu</label>
                                    <input type="text" class="form-control" name="nama_produk" id="nama_produk" value="<?= htmlspecialchars($data['nama_produk']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga</label>
                                    <input type="number" class="form-control" name="harga" id="harga" value="<?= htmlspecialchars($data['harga']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="kategori" class="form-label">Kategori</label>
                                    <select class="form-select" name="kategori" id="kategori" required>
                                        <option value="makanan" <?= ($data['kategori'] === 'makanan') ? 'selected' : '' ?>>Makanan</option>
                                        <option value="minuman" <?= ($data['kategori'] === 'minuman') ? 'selected' : '' ?>>Minuman</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Gambar Saat Ini:</label><br>
                                    <img src="../../../assets/img/produk/<?= htmlspecialchars($data['poto_produk']) ?>" width="150" alt="Gambar saat ini" class="img-thumbnail">
                                </div>
                                <div class="mb-3">
                                    <label for="gambar" class="form-label">Upload Gambar Baru (Kosongkan jika tidak ingin diubah)</label>
                                    <input class="form-control" type="file" name="gambar" id="gambar" accept="image/jpeg, image/png, image/jpg">
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="data_menu.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>
</body>
</html>