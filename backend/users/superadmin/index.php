<?php
session_start();
include('../../koneksi.php');

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// === DATA UNTUK KARTU PERFORMA HARI INI (TODAY'S PERFORMANCE) ===
$today = date('Y-m-d');

// Pendapatan Hari Ini
$stmt_pendapatan = mysqli_prepare($koneksi, "SELECT SUM(total_harga) AS total FROM pesanan WHERE DATE(tgl_pesanan) = ?");
mysqli_stmt_bind_param($stmt_pendapatan, "s", $today);
mysqli_stmt_execute($stmt_pendapatan);
$pendapatan_hari_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pendapatan))['total'] ?? 0;

// Transaksi Hari Ini
$stmt_transaksi = mysqli_prepare($koneksi, "SELECT COUNT(id_pesanan) AS jumlah FROM pesanan WHERE DATE(tgl_pesanan) = ?");
mysqli_stmt_bind_param($stmt_transaksi, "s", $today);
mysqli_stmt_execute($stmt_transaksi);
$transaksi_hari_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_transaksi))['jumlah'] ?? 0;

// --- PERBAIKAN: Query untuk menghitung pesanan online yang menunggu konfirmasi ---
// Definisikan status yang ingin kita hitung.
$status_konfirmasi = 'menunggu_konfirmasi';

// Siapkan query yang aman untuk menghitung jumlah pesanan dengan status tersebut.
$stmt_online = mysqli_prepare($koneksi, "SELECT COUNT(id_pesanan) AS jumlah FROM pesanan WHERE status_pesanan = ?");

// Bind parameter untuk keamanan
mysqli_stmt_bind_param($stmt_online, "s", $status_konfirmasi);

// Eksekusi query
mysqli_stmt_execute($stmt_online);

// Ambil hasilnya dan simpan ke variabel. Jika tidak ada, nilainya 0.
$pesanan_online_baru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_online))['jumlah'] ?? 0;

// === DATA UNTUK GRAFIK PENJUALAN 7 HARI TERAKHIR ===
$sql_chart = "SELECT DATE(tgl_pesanan) as tanggal, SUM(total_harga) as total 
              FROM pesanan
              WHERE DATE(tgl_pesanan) >= CURDATE() - INTERVAL 6 DAY 
              GROUP BY DATE(tgl_pesanan) 
              ORDER BY tanggal ASC";
$result_chart = mysqli_query($koneksi, $sql_chart);

$sales_data_raw = [];
while ($row = mysqli_fetch_assoc($result_chart)) {
    $sales_data_raw[$row['tanggal']] = $row['total'];
}

// Siapkan label dan data untuk 7 hari, isi 0 jika tidak ada penjualan
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($date)); // Format tanggal (e.g., 07 Jun)
    $chart_data[] = $sales_data_raw[$date] ?? 0;
}


// === DATA UNTUK TOP 5 PRODUK BULAN INI (Versi lebih optimal) ===
$tanggal_awal_bulan = date('Y-m-01');
$tanggal_awal_bulan_depan = date('Y-m-01', strtotime('+1 month'));

$sql_top_produk = "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual 
                   FROM detail_pesanan dp
                   JOIN produk p ON dp.id_produk = p.id_produk
                   JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan
                   WHERE pk.tgl_pesanan >= ? AND pk.tgl_pesanan < ?
                   GROUP BY p.id_produk, p.nama_produk
                   ORDER BY total_terjual DESC LIMIT 5";

$stmt_top_produk = mysqli_prepare($koneksi, $sql_top_produk);
// "ss" berarti kedua parameter adalah string
mysqli_stmt_bind_param($stmt_top_produk, "ss", $tanggal_awal_bulan, $tanggal_awal_bulan_depan);
mysqli_stmt_execute($stmt_top_produk);
$result_top_produk = mysqli_stmt_get_result($stmt_top_produk);


// === DATA UNTUK FEEDBACK TERBARU ===
$sql_feedback = "SELECT nama, pesan, tanggal FROM feedback ORDER BY tanggal DESC LIMIT 3";
$result_feedback = mysqli_query($koneksi, $sql_feedback);

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Dashboard - Owner</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png"> 
</head>

<body class="sb-nav-fixed">
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="index.php">KueBalok</a> <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
            <i class="fas fa-bars"></i>
        </button>

        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user fa-fw"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">

                    <li>
                        <h6 class="dropdown-header">
                            Login sebagai:<br>
                            <strong><?php echo isset($_SESSION['user']['nama']) ? htmlspecialchars($_SESSION['user']['nama']) : 'Pengguna'; ?></strong><br>
                            <small class="text-muted"><?php echo isset($_SESSION['user']['jabatan']) ? ucfirst(htmlspecialchars($_SESSION['user']['jabatan'])) : 'Role'; ?></small>
                        </h6>
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

                        <div class="sb-sidenav-menu-heading">Manajemen</div>
                        <a class="nav-link" href="karyawan/data_karyawan.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-users-cog"></i></div>
                            Data Karyawan
                        </a>

                        <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                            data-bs-target="#collapseLaporan" aria-expanded="false" aria-controls="collapseLaporan">
                            <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                            Laporan
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>
                        <div class="collapse" id="collapseLaporan" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <a class="nav-link" href="lap/laporan_penjualan.php?jenis_laporan=pemasukan">Pemasukan</a>
                                <a class="nav-link" href="lap/laporan_penjualan.php?jenis_laporan=produk">Produk</a>
                                <a class="nav-link" href="lap/laporan_penjualan.php?jenis_laporan=jam_sibuk">Jam Sibuk</a>
                                <a class="nav-link" href="lap/laporan_penjualan.php?jenis_laporan=kategori_pembayaran">Kategori & Pembayaran</a>
                            </nav>
                        </div>

                        <a class="nav-link" href="ulasan/tinjau_feedback.php">
                            <div class="sb-nav-link-icon"><i class="fas fa-comment-alt"></i></div>
                            Masukan & Saran
                        </a>
                    </div>
                </div>
                <div class="sb-sidenav-footer">
                    <div class="small">Panel Role:</div>
                    <strong>Superadmin / Owner</strong>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard Owner</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Ringkasan Bisnis Hari Ini (<?php echo date('d F Y'); ?>)</li>
                    </ol>

                    <div class="row">
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div><i class="fas fa-dollar-sign fa-2x"></i></div>
                                        <div class="text-end">
                                            <div class="fs-1 fw-bold">Rp <?php echo number_format($pendapatan_hari_ini); ?></div>
                                            <div>Pendapatan Hari Ini</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-primary text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div><i class="fas fa-receipt fa-2x"></i></div>
                                        <div class="text-end">
                                            <div class="fs-1 fw-bold"><?php echo $transaksi_hari_ini; ?></div>
                                            <div>Transaksi Hari Ini</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-4 col-md-6">
                            <div class="card bg-info text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div><i class="fas fa-inbox fa-2x"></i></div>
                                        <div class="text-end">
                                            <div class="fs-1 fw-bold"><?php echo $pesanan_online_baru; ?></div>
                                            <div>Pesanan Online Baru</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-8">
                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-chart-line me-1"></i>Grafik Penjualan 7 Hari Terakhir</div>
                                <div class="card-body"><canvas id="salesChart" width="100%" height="40"></canvas></div>
                            </div>
                        </div>
                        <div class="col-xl-4">
                            <div class="card mb-4">
                                <div class="card-header"><i class="fas fa-star me-1"></i>Top 5 Produk Bulan Ini</div>
                                <div class="card-body">
                                    <ul class="list-group list-group-flush">
                                        <?php if (mysqli_num_rows($result_top_produk) > 0): ?>
                                            <?php while ($produk = mysqli_fetch_assoc($result_top_produk)): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <?php echo htmlspecialchars($produk['nama_produk']); ?>
                                                    <span class="badge bg-primary rounded-pill"><?php echo $produk['total_terjual']; ?></span>
                                                </li>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <li class="list-group-item">Belum ada penjualan bulan ini.</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-comment-alt me-1"></i>3 Feedback Terbaru dari Pelanggan</div>
                        <div class="card-body">
                            <?php if (mysqli_num_rows($result_feedback) > 0): ?>
                                <?php while ($feedback = mysqli_fetch_assoc($result_feedback)): ?>
                                    <div class="border-bottom pb-2 mb-2">
                                        <strong><?php echo htmlspecialchars($feedback['nama']); ?></strong> - <small class="text-muted"><?php echo date('d M Y', strtotime($feedback['tanggal'])); ?></small>
                                        <p class="mb-0 fst-italic">"<?php echo htmlspecialchars(substr($feedback['pesan'], 0, 100)); ?>..."</p>
                                    </div>
                                <?php endwhile; ?>
                                <a href="ulasan/tinjau_feedback.php" class="small mt-2 d-block">Lihat semua feedback &rarr;</a>
                            <?php else: ?>
                                <p class="text-muted">Belum ada feedback yang masuk.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/scripts.js"></script>
    <script>
        // Inisialisasi Grafik Penjualan
        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Pendapatan',
                    data: <?php echo json_encode($chart_data); ?>,
                    borderColor: 'rgba(2,117,216,1)',
                    backgroundColor: 'rgba(2,117,216,0.1)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>