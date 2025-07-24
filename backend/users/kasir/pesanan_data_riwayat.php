<?php
session_start();
include '../../koneksi.php'; // Path disesuaikan dari users/kasir/

// 1. OTENTIKASI & OTORISASI
// Pastikan user sudah login dan merupakan seorang kasir.
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

// 2. LOGIKA PENGAMBILAN DATA
// Tentukan tanggal yang akan ditampilkan. Defaultnya adalah hari ini.
$tanggal_filter = date('Y-m-d'); // Default hari ini
if (isset($_GET['tanggal']) && !empty($_GET['tanggal'])) {
    // Jika ada tanggal yang dipilih dari form, gunakan tanggal tersebut
    $tanggal_filter = $_GET['tanggal'];
}

// Siapkan query untuk mengambil riwayat pesanan pada tanggal yang dipilih
// Kita JOIN dengan tabel karyawan untuk mendapatkan nama kasir
$sql = "SELECT 
            pk.id_pesanan, 
            pk.nama_pemesan, 
            pk.tgl_pesanan, 
            pk.total_harga, 
            pk.metode_pembayaran,
            k.nama AS nama_kasir
        FROM 
            pesanan pk
        JOIN 
            karyawan k ON pk.id_karyawan = k.id_karyawan
        WHERE 
            DATE(pk.tgl_pesanan) = ?
        ORDER BY 
            pk.tgl_pesanan DESC";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "s", $tanggal_filter);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$riwayat_pesanan = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $riwayat_pesanan[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Riwayat Pesanan - Kasir</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
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
                    <h1 class="mt-4">Riwayat Pesanan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Riwayat Pesanan</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-table me-1"></i>
                            Daftar Transaksi pada tanggal <?php echo date('d F Y', strtotime($tanggal_filter)); ?>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple" class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>No. Pesanan</th>
                                        <th>Waktu</th>
                                        <th>Nama Pemesan</th>
                                        <th>Total Harga</th>
                                        <th>Metode Bayar</th>
                                        <th>Kasir</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($riwayat_pesanan)): ?>
                                        <?php foreach ($riwayat_pesanan as $pesanan): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($pesanan['id_pesanan']); ?></td>
                                                <td><?php echo date('H:i:s', strtotime($pesanan['tgl_pesanan'])); ?></td>
                                                <td><?php echo htmlspecialchars($pesanan['nama_pemesan']); ?></td>
                                                <td>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></td>
                                                <td><?php echo htmlspecialchars(ucfirst($pesanan['metode_pembayaran'])); ?></td>
                                                <td><?php echo htmlspecialchars($pesanan['nama_kasir']); ?></td>
                                                <td>
                                                    <a href="detail_pesanan.php?id=<?php echo $pesanan['id_pesanan']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-search"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada data transaksi pada tanggal ini.</td>
                                        </tr>
                                    <?php endif; ?>
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
    <script src="../../js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="../../js/datatables-simple-demo.js"></script>
</body>

</html>