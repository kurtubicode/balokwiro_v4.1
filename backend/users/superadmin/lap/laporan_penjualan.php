<?php
session_start();
include '../../../koneksi.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 2. LOGIKA FILTER
// =========================================================================
// ==              LOGIKA FILTER BARU (LEBIH AMAN)                      ==
// =========================================================================

$jenis_laporan = $_GET['jenis_laporan'] ?? 'pemasukan';
$periode = $_GET['periode'] ?? 'bulanan';
$nilai = $_GET['nilai'] ?? '';

// Tentukan tanggal mulai dan selesai berdasarkan periode yang dipilih
$tanggal_mulai = '';
$tanggal_selesai = '';
$label_periode = '';

switch ($periode) {
    case 'harian':
        // PERBAIKAN: Jika nilai tidak dalam format YYYY-MM-DD, kosongkan.
        if (!empty($nilai) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            $nilai = '';
        }
        if (empty($nilai)) {
            $nilai = date('Y-m-d');
        }
        $tanggal_mulai = $nilai;
        $tanggal_selesai = $nilai;
        $label_periode = date('d F Y', strtotime($nilai));
        break;

    case 'mingguan':
        // PERBAIKAN: Jika nilai tidak dalam format YYYY-Www, kosongkan.
        if (!empty($nilai) && !str_contains($nilai, '-W')) {
            $nilai = '';
        }
        if (empty($nilai)) {
            $nilai = date('Y-\WW');
        }
        [$tahun, $minggu] = explode('-W', $nilai);
        $dto = new DateTime();
        $dto->setISODate((int)$tahun, (int)$minggu); // Konversi ke integer untuk keamanan
        $tanggal_mulai = $dto->format('Y-m-d');
        $label_periode = "Minggu ke-" . $minggu . " Tahun " . $tahun;
        $dto->modify('+6 days');
        $tanggal_selesai = $dto->format('Y-m-d');
        break;
    case 'tahunan':
        // Validasi format empat digit untuk tahun
        if (empty($nilai) || !preg_match('/^\d{4}$/', $nilai)) {
            $nilai = date('Y'); // Default ke tahun sekarang jika tidak valid
        }
        // Atur tanggal mulai ke 1 Januari dan tanggal selesai ke 31 Desember
        $tanggal_mulai = $nilai . '-01-01';
        $tanggal_selesai = $nilai . '-12-31';
        $label_periode = "Tahun " . $nilai;
        break;

    case 'bulanan':
    default:
        // PERBAIKAN: Jika nilai tidak dalam format YYYY-MM, kosongkan.
        if (!empty($nilai) && !preg_match('/^\d{4}-\d{2}$/', $nilai)) {
            $nilai = '';
        }
        if (empty($nilai)) {
            $nilai = date('Y-m');
        }
        $tanggal_mulai = $nilai . '-01';
        $tanggal_selesai = date('Y-m-t', strtotime($tanggal_mulai));
        $label_periode = date('F Y', strtotime($tanggal_mulai));
        break;
}
// Judul Halaman dinamis
$judul_halaman = "Laporan Pemasukan";

// 3. STRUKTUR KONDISIONAL UNTUK SETIAP JENIS LAPORAN
if ($jenis_laporan === 'pemasukan') {
    $judul_halaman = "Laporan Pemasukan";
    // Query untuk kartu ringkasan
    $stmt_pendapatan = mysqli_prepare($koneksi, "SELECT SUM(total_harga) AS total FROM pesanan WHERE DATE(tgl_pesanan) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt_pendapatan, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_pendapatan);
    $total_pendapatan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pendapatan))['total'] ?? 0;

    $stmt_transaksi = mysqli_prepare($koneksi, "SELECT COUNT(id_pesanan) AS jumlah FROM pesanan WHERE DATE(tgl_pesanan) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt_transaksi, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_transaksi);
    $jumlah_transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_transaksi))['jumlah'] ?? 0;

    $stmt_item = mysqli_prepare($koneksi, "SELECT SUM(dp.jumlah) AS total FROM detail_pesanan dp JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt_item, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_item);
    $total_item_terjual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_item))['total'] ?? 0;

    // Query untuk tabel rincian
    $sql_rincian = "SELECT pk.id_pesanan, pk.tgl_pesanan, pk.total_harga, k.nama AS nama_kasir FROM pesanan pk JOIN karyawan k ON pk.id_karyawan = k.id_karyawan WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ? ORDER BY pk.tgl_pesanan DESC";
    $stmt_rincian = mysqli_prepare($koneksi, $sql_rincian);
    mysqli_stmt_bind_param($stmt_rincian, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_rincian);
    $result_rincian = mysqli_stmt_get_result($stmt_rincian);
    $daftar_transaksi = [];
    while ($row = mysqli_fetch_assoc($result_rincian)) {
        $daftar_transaksi[] = $row;
    }
} elseif ($jenis_laporan === 'produk') {
    $judul_halaman = "Laporan Analisis Produk";
    $base_sql = "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id_produk JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ? GROUP BY p.id_produk, p.nama_produk";

    $stmt_terlaris = mysqli_prepare($koneksi, $base_sql . " ORDER BY total_terjual DESC LIMIT 10");
    mysqli_stmt_bind_param($stmt_terlaris, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_terlaris);
    $produk_terlaris = mysqli_fetch_all(mysqli_stmt_get_result($stmt_terlaris), MYSQLI_ASSOC);

    $stmt_kurang_laku = mysqli_prepare($koneksi, $base_sql . " ORDER BY total_terjual ASC LIMIT 10");
    mysqli_stmt_bind_param($stmt_kurang_laku, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_kurang_laku);
    $produk_kurang_laku = mysqli_fetch_all(mysqli_stmt_get_result($stmt_kurang_laku), MYSQLI_ASSOC);
} elseif ($jenis_laporan === 'jam_sibuk') {
    $judul_halaman = "Analisis Jam Sibuk";
    $sql_jam = "SELECT HOUR(tgl_pesanan) as jam, COUNT(id_pesanan) as jumlah_transaksi FROM pesanan WHERE DATE(tgl_pesanan) BETWEEN ? AND ? GROUP BY jam ORDER BY jam ASC";
    $stmt_jam = mysqli_prepare($koneksi, $sql_jam);
    mysqli_stmt_bind_param($stmt_jam, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_jam);
    $data_jam_sibuk_raw = mysqli_fetch_all(mysqli_stmt_get_result($stmt_jam), MYSQLI_ASSOC);

    // Siapkan data untuk Chart.js
    $labels_jam = [];
    $data_transaksi_per_jam = [];
    $all_hours = range(0, 23); // Buat array jam dari 0-23
    $sales_by_hour = array_column($data_jam_sibuk_raw, 'jumlah_transaksi', 'jam'); // map jam ke penjualan

    foreach ($all_hours as $hour) {
        $labels_jam[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        $data_transaksi_per_jam[] = $sales_by_hour[$hour] ?? 0;
    }
} elseif ($jenis_laporan === 'kategori_pembayaran') {
    $judul_halaman = "Analisis Tipe Produk & Pembayaran";

    // --- PERUBAHAN: Query untuk Tipe Produk (bukan lagi Kategori) ---
    $sql_tipe_produk = "SELECT 
                            CASE 
                                WHEN LEFT(p.id_produk, 2) = 'KB' THEN 'Kue Balok'
                                WHEN LEFT(p.id_produk, 2) = 'KS' THEN 'Ketan Susu'
                                WHEN LEFT(p.id_produk, 2) = 'OT' THEN 'Makanan Lain'
                                WHEN LEFT(p.id_produk, 2) = 'DK' THEN 'Minuman'
                                ELSE 'Lainnya' 
                            END AS tipe_produk,
                            SUM(dp.sub_total) as total_pendapatan 
                        FROM detail_pesanan dp 
                        JOIN produk p ON dp.id_produk = p.id_produk 
                        JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan 
                        WHERE pk.status_pesanan = 'selesai' AND DATE(pk.tgl_pesanan) BETWEEN ? AND ? 
                        GROUP BY tipe_produk
                        ORDER BY total_pendapatan DESC";

    $stmt_tipe_produk = mysqli_prepare($koneksi, $sql_tipe_produk);
    mysqli_stmt_bind_param($stmt_tipe_produk, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_tipe_produk);
    $data_tipe_produk = mysqli_fetch_all(mysqli_stmt_get_result($stmt_tipe_produk), MYSQLI_ASSOC);

    // Query Metode Pembayaran (tetap sama)
    $sql_pembayaran = "SELECT metode_pembayaran, COUNT(id_pesanan) as jumlah_penggunaan FROM pesanan WHERE status_pesanan = 'selesai' AND DATE(tgl_pesanan) BETWEEN ? AND ? GROUP BY metode_pembayaran";
    $stmt_pembayaran = mysqli_prepare($koneksi, $sql_pembayaran);
    mysqli_stmt_bind_param($stmt_pembayaran, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_pembayaran);
    $data_pembayaran = mysqli_fetch_all(mysqli_stmt_get_result($stmt_pembayaran), MYSQLI_ASSOC);
}


?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title><?php echo htmlspecialchars($judul_halaman); ?> - Owner</title>
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">

</head>

<body class="sb-nav-fixed">
    <?php include '../inc/navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include '../inc/sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4"><?php echo htmlspecialchars($judul_halaman); ?></h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Laporan</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div><i class="fas fa-filter me-1"></i>Filter Laporan</div>

                                <div>
                                    <?php
                                    // Ambil semua parameter filter saat ini untuk disertakan di link ekspor
                                    $export_params = [
                                        'jenis_laporan' => $jenis_laporan,
                                        'periode' => $periode,
                                        'nilai' => $nilai
                                    ];
                                    $export_query_string = http_build_query($export_params);
                                    ?>
                                    <a href="export_pdf.php?start_date=<?= $tanggal_mulai ?>&end_date=<?= $tanggal_selesai ?>" class="btn btn-danger btn-sm">
                                        <i class="fas fa-file-pdf me-1"></i> Export ke PDF
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="card-body">
                                    <form method="GET" action="laporan_penjualan.php" id="filterForm">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4">
                                                <label for="jenis_laporan" class="form-label">Jenis Laporan:</label>
                                                <select class="form-select" name="jenis_laporan" id="jenis_laporan">
                                                    <option value="pemasukan" <?= $jenis_laporan == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                                                    <option value="produk" <?= $jenis_laporan == 'produk' ? 'selected' : '' ?>>Produk</option>
                                                    <option value="jam_sibuk" <?= $jenis_laporan == 'jam_sibuk' ? 'selected' : '' ?>>Jam Sibuk</option>
                                                    <option value="kategori_pembayaran" <?= $jenis_laporan == 'kategori_pembayaran' ? 'selected' : '' ?>>Kategori & Pembayaran</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="periode" class="form-label">Periode:</label>
                                                <select class="form-select" name="periode" id="periode">
                                                    <option value="harian" <?= $periode == 'harian' ? 'selected' : '' ?>>Harian</option>
                                                    <option value="mingguan" <?= $periode == 'mingguan' ? 'selected' : '' ?>>Mingguan</option>
                                                    <option value="bulanan" <?= $periode == 'bulanan' ? 'selected' : '' ?>>Bulanan</option>
                                                    <option value="tahunan" <?= $periode == 'tahunan' ? 'selected' : '' ?>>Tahunan</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label for="nilai" class="form-label" id="nilai_label">Pilih Nilai:</label>
                                                <input type="date" class="form-control" id="nilai_harian" name="nilai" value="<?= htmlspecialchars($nilai) ?>">
                                                <input type="week" class="form-control" id="nilai_mingguan" name="nilai" value="<?= htmlspecialchars($nilai) ?>">
                                                <input type="month" class="form-control" id="nilai_bulanan" name="nilai" value="<?= htmlspecialchars($nilai) ?>">

                                                <select class="form-select" id="nilai_tahunan" name="nilai">
                                                    <?php
                                                    // Query untuk mendapatkan semua tahun unik dari data pesanan
                                                    $query_tahun = mysqli_query($koneksi, "SELECT DISTINCT YEAR(tgl_pesanan) AS tahun FROM pesanan ORDER BY tahun DESC");
                                                    while ($row_tahun = mysqli_fetch_assoc($query_tahun)) {
                                                        $selected = ($nilai == $row_tahun['tahun']) ? 'selected' : '';
                                                        echo "<option value='{$row_tahun['tahun']}' $selected>{$row_tahun['tahun']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <h4 class="mt-4">Hasil untuk Periode <?php echo date('d M Y', strtotime($tanggal_mulai)) . ' - ' . date('d M Y', strtotime($tanggal_selesai)); ?></h4>

                            <?php if ($jenis_laporan === 'pemasukan'): ?>
                                <div class="row">
                                    <div class="col-xl-4 col-md-6">
                                        <div class="card bg-success text-white mb-4">
                                            <div class="card-body">
                                                <div class="fs-5">Total Pendapatan</div>
                                                <div class="fs-3 fw-bold">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="card bg-primary text-white mb-4">
                                            <div class="card-body">
                                                <div class="fs-5">Jumlah Transaksi</div>
                                                <div class="fs-3 fw-bold"><?php echo $jumlah_transaksi; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-xl-4 col-md-6">
                                        <div class="card bg-warning text-dark mb-4">
                                            <div class="card-body">
                                                <div class="fs-5">Total Item Terjual</div>
                                                <div class="fs-3 fw-bold"><?php echo $total_item_terjual; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mb-4">
                                    <div class="card-header"><i class="fas fa-table me-1"></i>Rincian Transaksi</div>
                                    <div class="card-body">
                                        <table id="datatablesSimple">
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th>Waktu</th>
                                                    <th>No. Pesanan</th>
                                                    <th>Total</th>
                                                    <th>Kasir</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($daftar_transaksi as $transaksi): ?>
                                                    <tr>
                                                        <td><?php echo date('d/m/Y', strtotime($transaksi['tgl_pesanan'])); ?></td>
                                                        <td><?php echo date('H:i:s', strtotime($transaksi['tgl_pesanan'])); ?></td>
                                                        <td><?php echo htmlspecialchars($transaksi['id_pesanan']); ?></td>
                                                        <td>Rp <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?></td>
                                                        <td><?php echo htmlspecialchars($transaksi['nama_kasir']); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-info detail-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#detailPesananModal"
                                                                data-id-pesanan="<?= htmlspecialchars($transaksi['id_pesanan']) ?>">
                                                                Detail
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            <?php elseif ($jenis_laporan === 'produk'): ?>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card mb-4">
                                            <div class="card-header bg-success text-white"><i class="fas fa-star me-1"></i>10 Produk Terlaris</div>
                                            <div class="card-body">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Nama Produk</th>
                                                            <th class="text-end">Jumlah Terjual</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($produk_terlaris)): ?>
                                                            <?php foreach ($produk_terlaris as $index => $produk): ?>
                                                                <tr>
                                                                    <th><?= $index + 1 ?></th>
                                                                    <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
                                                                    <td class="text-end"><strong><?= htmlspecialchars($produk['total_terjual']) ?></strong></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="3" class="text-center">Tidak ada data.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card mb-4">
                                            <div class="card-header bg-danger text-white"><i class="fas fa-thumbs-down me-1"></i>10 Produk Kurang Diminati</div>
                                            <div class="card-body">
                                                <table class="table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Nama Produk</th>
                                                            <th class="text-end">Jumlah Terjual</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($produk_kurang_laku)): ?>
                                                            <?php foreach ($produk_kurang_laku as $index => $produk): ?>
                                                                <tr>
                                                                    <th><?= $index + 1 ?></th>
                                                                    <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
                                                                    <td class="text-end"><strong><?= htmlspecialchars($produk['total_terjual']) ?></strong></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td colspan="3" class="text-center">Tidak ada data.</td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <?php elseif ($jenis_laporan === 'jam_sibuk'): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-primary text-white"><i class="fas fa-clock me-1"></i>Grafik Jumlah Transaksi per Jam</div>
                                    <div class="card-body"><canvas id="jamSibukChart"></canvas></div>
                                </div>

                            <?php elseif ($jenis_laporan === 'kategori_pembayaran'): ?>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="card mb-4">
                                            <div class="card-header bg-info text-white"><i class="fas fa-tags me-1"></i>Pendapatan per Tipe Produk</div>
                                            <div class="card-body"><canvas id="tipeProdukChart" height="200"></canvas></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card mb-4">
                                            <div class="card-header bg-warning text-dark"><i class="fas fa-credit-card me-1"></i>Popularitas Metode Pembayaran</div>
                                            <div class="card-body"><canvas id="pembayaranChart" height="200"></canvas></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
            </main>
        </div>
    </div>
    <div class="modal fade" id="detailPesananModal" tabindex="-1" aria-labelledby="detailPesananModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detailPesananModalLabel">Detail Pesanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detailPesananContent">
                    <p class="text-center">Memuat data...</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/datatables-simple-demo.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const periodeSelect = document.getElementById('periode');
            const inputHarian = document.getElementById('nilai_harian');
            const inputMingguan = document.getElementById('nilai_mingguan');
            const inputBulanan = document.getElementById('nilai_bulanan');
            const inputTahunan = document.getElementById('nilai_tahunan'); // <-- Variabel baru

            function toggleFilterInputs() {
                const periode = periodeSelect.value;
                // Sembunyikan semua input terlebih dahulu
                inputHarian.style.display = 'none';
                inputMingguan.style.display = 'none';
                inputBulanan.style.display = 'none';
                inputTahunan.style.display = 'none'; // <-- Sembunyikan input tahunan

                // Nonaktifkan semua input agar nilainya tidak terkirim
                inputHarian.disabled = true;
                inputMingguan.disabled = true;
                inputBulanan.disabled = true;
                inputTahunan.disabled = true; // <-- Nonaktifkan input tahunan

                // Tampilkan dan aktifkan input yang sesuai
                if (periode === 'harian') {
                    inputHarian.style.display = 'block';
                    inputHarian.disabled = false;
                } else if (periode === 'mingguan') {
                    inputMingguan.style.display = 'block';
                    inputMingguan.disabled = false;
                } else if (periode === 'bulanan') {
                    inputBulanan.style.display = 'block';
                    inputBulanan.disabled = false;
                } else if (periode === 'tahunan') { // <-- Logika baru
                    inputTahunan.style.display = 'block';
                    inputTahunan.disabled = false;
                }
            }

            periodeSelect.addEventListener('change', toggleFilterInputs);
            // Panggil sekali saat halaman dimuat untuk menampilkan input yang benar sesuai kondisi awal
            toggleFilterInputs();
            // Logika untuk menampilkan Chart hanya jika data yang sesuai ada
            <?php if ($jenis_laporan === 'jam_sibuk' && isset($labels_jam)): ?>
                new Chart(document.getElementById('jamSibukChart'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($labels_jam); ?>,
                        datasets: [{
                            label: 'Jumlah Transaksi',
                            data: <?php echo json_encode($data_transaksi_per_jam); ?>,
                            backgroundColor: 'rgba(2,117,216,0.8)',
                            borderColor: 'rgba(2,117,216,1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>

            <?php if ($jenis_laporan === 'kategori_pembayaran' && isset($data_tipe_produk)): ?>
                new Chart(document.getElementById('tipeProdukChart'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($data_tipe_produk, 'tipe_produk')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($data_tipe_produk, 'total_pendapatan')); ?>,
                            backgroundColor: ['#0275d8', '#5cb85c', '#f0ad4e', '#d9534f', '#343a40'],
                        }]
                    }
                });
            <?php endif; ?>

            <?php if ($jenis_laporan === 'kategori_pembayaran' && isset($data_kategori)): ?>
                new Chart(document.getElementById('kategoriChart'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($data_kategori, 'kategori')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($data_kategori, 'total_pendapatan')); ?>,
                            backgroundColor: ['#0275d8', '#5cb85c', '#f0ad4e', '#d9534f'],
                        }]
                    }
                });
            <?php endif; ?>

            <?php if ($jenis_laporan === 'kategori_pembayaran' && isset($data_pembayaran)): ?>
                new Chart(document.getElementById('pembayaranChart'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($data_pembayaran, 'metode_pembayaran')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($data_pembayaran, 'jumlah_penggunaan')); ?>,
                            backgroundColor: ['#f0ad4e', '#5bc0de', '#5cb85c', '#d9534f'],
                        }]
                    }
                });


            <?php endif; ?>

        });

        document.addEventListener('DOMContentLoaded', function() {
            const detailModal = document.getElementById('detailPesananModal');
            detailModal.addEventListener('show.bs.modal', async function(event) {
                const button = event.relatedTarget;
                const pesananId = button.getAttribute('data-id-pesanan');
                const modalTitle = detailModal.querySelector('.modal-title');
                const modalBody = detailModal.querySelector('#detailPesananContent');

                // Set judul modal dan tampilkan status loading
                modalTitle.textContent = 'Detail Pesanan #' + pesananId;
                modalBody.innerHTML = '<p class="text-center">Memuat data...</p>';

                try {
                    // Panggil API yang sudah kita buat
                    const response = await fetch(`../../../api/api_get_detail_pesanan.php?id=${pesananId}`);
                    const result = await response.json();

                    if (result.error) {
                        modalBody.innerHTML = `<div class="alert alert-danger">${result.message}</div>`;
                    } else {
                        // Jika berhasil, bangun HTML dari data JSON
                        const header = result.data.header;
                        const items = result.data.items;
                        let contentHtml = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><strong>Nama Pemesan:</strong> ${header.nama_pemesan}</li>
                                    <li><strong>Kasir:</strong> ${header.nama_kasir || 'Online'}</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><strong>Jenis:</strong> ${header.tipe_pesanan}</li>
                                    <li><strong>Metode Bayar:</strong> ${header.metode_pembayaran}</li>
                                </ul>
                            </div>
                        </div>
                    `;
                        if (header.bukti_pembayaran) {
                            // Tentukan path ke gambar. Sesuaikan jika perlu.
                            const imagePath = `../../../assets/img/bukti_bayar/${header.bukti_pembayaran}`;
                            contentHtml += `
        <a href="${imagePath}" target="_blank" rel="noopener noreferrer" class="btn btn-info btn-sm mb-3">
            <i class="fas fa-image"></i> Lihat Bukti Pembayaran
        </a>
        <hr>
    `;
                        }

                        if (header.catatan) {
                            contentHtml += `
                            <h6>Catatan:</h6>
                            <p class="fst-italic bg-light p-2 rounded">${header.catatan.replace(/\n/g, '<br>')}</p>
                        `;
                        }

                        contentHtml += `
                        <h6>Rincian Item:</h6>
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;

                        items.forEach(item => {
                            contentHtml += `
                            <tr>
                                <td>${item.nama_produk}</td>
                                <td class="text-center">${item.jumlah}</td>
                                <td class="text-end">Rp ${parseInt(item.harga_saat_transaksi).toLocaleString('id-ID')}</td>
                                <td class="text-end">Rp ${parseInt(item.sub_total).toLocaleString('id-ID')}</td>
                            </tr>
                        `;
                        });

                        contentHtml += `
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <th colspan="3" class="text-end">Total Akhir</th>
                                    <th class="text-end">Rp ${parseInt(header.total_harga).toLocaleString('id-ID')}</th>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                        modalBody.innerHTML = contentHtml;
                    }
                } catch (error) {
                    modalBody.innerHTML = '<div class="alert alert-danger">Gagal terhubung ke server.</div>';
                    console.error('Fetch error:', error);
                }
            });
        });
    </script>
</body>

</html>