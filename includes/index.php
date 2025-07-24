<?php
session_start();
include('../../koneksi.php');

// Otentikasi & Otorisasi
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['jabatan'], ['admin', 'owner'])) {
    header('Location: ../../login.php');
    exit;
}

// ===================================================================
// === PERUBAHAN DIMULAI DI SINI: MENGGUNAKAN LOG_STOK ===
// ===================================================================

// Langkah 1: Hitung semua stok terkini dari log_stok
$stok_terkini = [];
$query_stok = "
    SELECT id_produk, NULL as id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk IS NOT NULL GROUP BY id_produk
    UNION ALL
    SELECT NULL as id_produk, id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok IS NOT NULL GROUP BY id_kategori_stok
";
$result_stok_log = mysqli_query($koneksi, $query_stok);
while ($row_stok = mysqli_fetch_assoc($result_stok_log)) {
    if ($row_stok['id_produk']) {
        $stok_terkini['produk'][$row_stok['id_produk']] = $row_stok['total'];
    } elseif ($row_stok['id_kategori_stok']) {
        $stok_terkini['kategori'][$row_stok['id_kategori_stok']] = $row_stok['total'];
    }
}

// Langkah 2: Ambil semua produk dan filter di PHP untuk stok kritis
$batas_stok_kritis = 20;
$produk_stok_kritis = [];
$sql_produk = "SELECT id_produk, nama_produk, id_kategori_stok FROM produk WHERE status_produk = 'aktif'";
$result_produk = mysqli_query($koneksi, $sql_produk);

while ($produk = mysqli_fetch_assoc($result_produk)) {
    $stok_produk_saat_ini = 0;
    // Tentukan stok produk berdasarkan tipe (individu atau kategori)
    if ($produk['id_kategori_stok'] !== null) {
        $stok_produk_saat_ini = $stok_terkini['kategori'][$produk['id_kategori_stok']] ?? 0;
    } else {
        $stok_produk_saat_ini = $stok_terkini['produk'][$produk['id_produk']] ?? 0;
    }

    // Cek jika stok di bawah batas kritis
    if ($stok_produk_saat_ini < $batas_stok_kritis) {
        $produk_stok_kritis[] = [
            'nama_produk' => $produk['nama_produk'],
            'stok' => $stok_produk_saat_ini
        ];
    }
}
// Urutkan produk kritis berdasarkan stok terendah
usort($produk_stok_kritis, function ($a, $b) {
    return $a['stok'] <=> $b['stok'];
});


// === AKHIR DARI PERUBAHAN ===
// ===================================================================


// === Query untuk Kartu Ringkasan Produk (Tidak ada perubahan) ===
$sql_total_aktif = "SELECT COUNT(id_produk) as total FROM produk WHERE status_produk = 'aktif'";
$result_total_aktif = mysqli_query($koneksi, $sql_total_aktif);
$total_menu_aktif = mysqli_fetch_assoc($result_total_aktif)['total'] ?? 0;

$sql_total_nonaktif = "SELECT COUNT(id_produk) as total FROM produk WHERE status_produk = 'tidak aktif'";
$result_total_nonaktif = mysqli_query($koneksi, $sql_total_nonaktif);
$total_menu_nonaktif = mysqli_fetch_assoc($result_total_nonaktif)['total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Dashboard - Admin</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png">
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">KueBalok</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!"><i class="fas fa-bars"></i></button>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fas fa-user fa-fw"></i></a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li>
                        <h6 class="dropdown-header">Login sebagai:<br><strong><?php echo isset($_SESSION['user']['nama']) ? htmlspecialchars($_SESSION['user']['nama']) : 'Pengguna'; ?></strong><br><small class="text-muted"><?php echo isset($_SESSION['user']['jabatan']) ? ucfirst(htmlspecialchars($_SESSION['user']['jabatan'])) : 'Role'; ?></small></h6>
                    </li>
                    <li>
                        <hr class="dropdown-divider" />
                    </li>
                    <li><a class="dropdown-item" href="../../logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
    </nav>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        <div class="sb-sidenav-menu-heading">Utama</div>
                        <a class="nav-link" href="index.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        <div class="sb-sidenav-menu-heading">Menu Utama</div>
                        <a class="nav-link" href="menu/data_menu.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-utensils"></i></div>
                            Manajemen Menu
                        </a>
                        <a class="nav-link" href="stok/laporan_stok.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>
                            Manajemen Stok
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Panel Role:</div>
                    <strong><?php echo isset($_SESSION['user']['jabatan']) ? ucfirst(htmlspecialchars($_SESSION['user']['jabatan'])) : ''; ?></strong>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard Admin</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Selamat Datang, <?php echo htmlspecialchars($_SESSION['user']['nama']); ?>!</li>
                    </ol>

                    <div class="row">
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body d-flex align-items-center">
                                    <i class="fas fa-utensils fa-3x me-3"></i>
                                    <div>
                                        <h4>Manajemen Menu</h4>
                                        <p class="mb-0">Tambah, edit, atau hapus produk.</p>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="menu/data_menu.php">Kelola Sekarang</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6 col-md-6">
                            <div class="card bg-info text-white mb-4">
                                <div class="card-body d-flex align-items-center">
                                    <i class="fas fa-boxes fa-3x me-3"></i>
                                    <div>
                                        <h4>Manajemen Stok</h4>
                                        <p class="mb-0">Update stok harian produk.</p>
                                    </div>
                                </div>
                                <div class="card-footer d-flex align-items-center justify-content-between">
                                    <a class="small text-white stretched-link" href="stok/manajemen_stok.php">Kelola Sekarang</a>
                                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header bg-danger text-white">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Notifikasi Stok Kritis (Di Bawah <?php echo $batas_stok_kritis; ?>)
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($produk_stok_kritis)): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($produk_stok_kritis as $produk): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?php echo htmlspecialchars($produk['nama_produk']); ?>
                                                    <span class="badge bg-danger rounded-pill"><?php echo $produk['stok']; ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="alert alert-success mb-0">
                                            <i class="fas fa-check-circle"></i> Semua stok produk dalam kondisi aman.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <i class="fas fa-chart-pie me-1"></i>
                                    Ringkasan Produk
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                        <span>Total Menu Aktif</span>
                                        <strong class="fs-4"><?php echo $total_menu_aktif; ?></strong>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>Total Menu Tidak Aktif</span>
                                        <strong class="fs-4"><?php echo $total_menu_nonaktif; ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../js/scripts.js"></script>
</body>

</html>