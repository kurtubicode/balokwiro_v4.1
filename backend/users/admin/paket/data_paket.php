<?php
// 1. WAJIB: Mulai sesi di baris paling atas
session_start();

// Sesuaikan dengan koneksi Anda
include('../../../koneksi.php');

// 2. KEAMANAN: Cek hak akses. Hanya admin dan owner yang boleh mengakses.
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    header('Location: ../../../login.php');
    exit;
}

// 3. Mengambil data paket untuk ditampilkan di tabel
$result = mysqli_query($koneksi, "SELECT * FROM paket ORDER BY id_paket ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Data Paket - Admin</title>
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
                    <h1 class="mt-4">Data Paket Menu</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Data Paket</li>
                    </ol>

                    <?php
                    // Blok notifikasi dari sesi
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
                        <a href="tambah_paket.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Paket</a>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Daftar Paket Tersedia
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped table-bordered">

                                <thead>
                                    <tr>
                                        <th>ID Paket</th>
                                        <th>Nama</th>
                                        <th>Harga</th>
                                        <th>Gambar</th>
                                        <th>Status</th>
                                        <th class="text-center">Opsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($result && mysqli_num_rows($result) > 0) {
                                        while ($row = mysqli_fetch_assoc($result)) { ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['id_paket']) ?></td>
                                                <td><?= htmlspecialchars($row['nama_paket']) ?></td>
                                                <td>Rp <?= number_format($row['harga_paket']) ?></td>
                                                <td>
                                                    <img src="../../../assets/img/paket/<?= htmlspecialchars($row['poto_paket']) ?>" width="100" alt="<?= htmlspecialchars($row['nama_paket']) ?>">
                                                </td>
                                                <td>
                                                    <?php if ($row['status_paket'] === 'aktif'): ?>
                                                        <span class="badge bg-success">Aktif</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        <a href="paket_edit.php?id=<?= htmlspecialchars($row['id_paket']) ?>" class="btn btn-warning btn-sm" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="paket_ubah_status.php?id=<?= htmlspecialchars($row['id_paket']) ?>" class="btn btn-secondary btn-sm" title="Ubah Status">
                                                            <i class="fas fa-toggle-on"></i>
                                                        </a>
                                                        <a href="paket_hapus.php?id=<?= htmlspecialchars($row['id_paket']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Anda yakin ingin menghapus paket ini secara permanen?')" title="Hapus Permanen">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                    <?php
                                        }
                                    } else {
                                        // Tampilkan baris ini jika tidak ada data paket
                                        echo '<tr><td colspan="6" class="text-center">Belum ada data paket.</td></tr>';
                                    }
                                    ?>
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