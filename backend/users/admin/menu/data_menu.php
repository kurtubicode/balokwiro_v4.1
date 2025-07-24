<?php
// 1. WAJIB: Mulai sesi di baris paling atas
session_start();

include('../../../koneksi.php');

// 2. KEAMANAN: Cek hak akses. Hanya admin dan owner yang boleh mengakses.
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    header('Location: ../../../login.php');
    exit;
}

// 3. Mengambil data produk untuk ditampilkan di tabel, diurutkan agar rapi
$result = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY kategori, nama_produk");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Data Menu - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="../../../css/styles.css" rel="stylesheet" />
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">
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
                    <h1 class="mt-4">Data Menu</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Data Menu</li>
                    </ol>

                    <?php
                    // Blok notifikasi dari sesi (sekarang akan berfungsi)
                    if (isset($_SESSION['notif'])) {
                        $notif = $_SESSION['notif'];
                        echo '<div class="alert alert-' . htmlspecialchars($notif['tipe']) . ' alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($notif['pesan']);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                        echo '</div>';
                        unset($_SESSION['notif']);
                    }
                    ?>

                    <div class="mb-3">
                        <a href="tambah_menu.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Menu</a>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Daftar Menu Tersedia
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>ID Produk</th>
                                        <th>Nama</th>
                                        <th>Harga</th>
                                        <th>Kategori</th>
                                        <th>Gambar</th>
                                        <th>Opsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id_produk']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                                            <td>Rp <?= number_format($row['harga']) ?></td>
                                            <td><?= htmlspecialchars(ucfirst($row['kategori'])) ?></td>
                                            <td><img src="../../../assets/img/produk/<?= htmlspecialchars($row['poto_produk']) ?>" width="100" alt="<?= htmlspecialchars($row['nama_produk']) ?>"></td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-2">

                                                    <?php
                                                    // PERBAIKAN: Menggunakan variabel $row, bukan $data
                                                    if ($row['status_produk'] === 'aktif'):
                                                    ?>

                                                        <a href="menu_edit.php?id_produk=<?= htmlspecialchars($row['id_produk']) ?>" class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <a href="produk_nonaktifkan.php?id=<?= htmlspecialchars($row['id_produk']) ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Anda yakin ingin menonaktifkan produk ini?')" title="Nonaktifkan">
                                                            <i class="fas fa-eye-slash"></i>
                                                        </a>

                                                    <?php else: ?>

                                                        <a href="produk_aktifkan.php?id=<?= htmlspecialchars($row['id_produk']) ?>" class="btn btn-success btn-sm" onclick="return confirm('Anda yakin ingin mengaktifkan kembali produk ini?')" title="Aktifkan">
                                                            <i class="fas fa-eye"></i> Aktifkan
                                                        </a>

                                                    <?php endif; ?>

                                                    <a href="menu_hapus.php?id_produk=<?= htmlspecialchars($row['id_produk']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('PERHATIAN: Menghapus produk akan menghilangkannya secara permanen. Lanjutkan?')" title="Hapus Permanen">
                                                        <i class="fas fa-trash"></i>
                                                    </a>

                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; KueBalok 2025</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/datatables-simple-demo.js"></script>
</body>

</html>