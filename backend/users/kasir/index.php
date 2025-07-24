<?php
session_start();
include('../../koneksi.php'); // Path dari users/kasir/

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

// Ambil ID kasir yang sedang login
$id_kasir_login = $_SESSION['user']['id'];

// 2. AMBIL DATA UNTUK KARTU-KARTU DASHBOARD
// ==========================================================

// PENAMBAHAN: Jumlah pesanan yang menunggu pembayaran tunai di kasir
$sql_tunggu_tunai = "SELECT COUNT(id_pesanan) as total FROM pesanan WHERE status_pesanan = 'menunggu_pembayaran_tunai'";
$result_tunggu_tunai = mysqli_query($koneksi, $sql_tunggu_tunai);
$jumlah_tunggu_tunai = mysqli_fetch_assoc($result_tunggu_tunai)['total'] ?? 0;

// Jumlah pesanan yang sedang diproses di dapur
$sql_diproses = "SELECT COUNT(id_pesanan) as total FROM pesanan WHERE status_pesanan = 'diproses'";
$result_diproses = mysqli_query($koneksi, $sql_diproses);
$jumlah_diproses = mysqli_fetch_assoc($result_diproses)['total'] ?? 0;

// Jumlah pesanan yang masih pending (antre)
$sql_pending = "SELECT COUNT(id_pesanan) as total FROM pesanan WHERE status_pesanan = 'pending'";
$result_pending = mysqli_query($koneksi, $sql_pending);
$jumlah_pending = mysqli_fetch_assoc($result_pending)['total'] ?? 0;

// Jumlah transaksi yang dilayani oleh kasir ini hari ini
$today = date('Y-m-d');
$stmt_pribadi = mysqli_prepare($koneksi, "SELECT COUNT(id_pesanan) as total FROM pesanan WHERE id_karyawan = ? AND DATE(tgl_pesanan) = ? AND status_pesanan != 'dibatalkan'");
mysqli_stmt_bind_param($stmt_pribadi, "is", $id_kasir_login, $today);
mysqli_stmt_execute($stmt_pribadi);
$result_pribadi = mysqli_stmt_get_result($stmt_pribadi);
$transaksi_pribadi_hari_ini = mysqli_fetch_assoc($result_pribadi)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Dashboard - Kasir</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>

<body class="sb-nav-fixed">
    <?php include 'inc/navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'inc/sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Dashboard Kasir</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item active">Selamat Bekerja, <?php echo htmlspecialchars($_SESSION['user']['nama']); ?>!</li>
                    </ol>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="card card-link bg-primary text-white mb-4">
                                <div class="card-body py-4 text-center">
                                    <i class="fas fa-cash-register fa-3x mb-3"></i>
                                    <h4 class="card-title">Input Pesanan Baru</h4>
                                    <p class="card-text">Buka Point of Sale untuk transaksi di tempat.</p>
                                </div>
                                <a class="card-footer text-center" href="pesanan_input.php">
                                    Mulai Transaksi <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="card card-link bg-info text-white mb-4">
                                <div class="card-body py-4 text-center">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <h4 class="card-title">Pesanan Masuk & Antrean</h4>
                                    <p class="card-text">Kelola semua pesanan online dan antrean dapur.</p>
                                </div>
                                <a class="card-footer text-center" href="pesanan_masuk.php">
                                    Kelola Pesanan <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <h4 class="mt-3">Status Saat Ini</h4>
                    <div class="row">
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-info text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="fs-1 fw-bold"><?php echo $jumlah_tunggu_tunai; ?></div>
                                        <div><i class="fas fa-hand-holding-usd fa-3x opacity-50"></i></div>
                                    </div>
                                    <p class="mb-0">Menunggu Pembayaran di Kasir</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-warning text-dark mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="fs-1 fw-bold"><?php echo $jumlah_pending; ?></div>
                                        <div><i class="fas fa-clock fa-3x opacity-50"></i></div>
                                    </div>
                                    <p class="mb-0">Pesanan Mengantre (Pending)</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card bg-success text-white mb-4">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div class="fs-1 fw-bold"><?php echo $jumlah_diproses; ?></div>
                                        <div><i class="fas fa-blender-phone fa-3x opacity-50"></i></div>
                                    </div>
                                    <p class="mb-0">Pesanan Sedang Diproses</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-3 col-md-6">
                            <div class="card card-body text-center bg-light">
                                Anda telah melayani <strong><?php echo $transaksi_pribadi_hari_ini; ?></strong> transaksi hari ini.
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/scripts.js"></script>
</body>

</html>