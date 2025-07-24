<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Menggunakan __DIR__ untuk path yang lebih andal
require_once __DIR__ . '/../../koneksi.php';

// 1. OTENTIKASI & OTORISASI KASIR
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

$id_kasir_login = $_SESSION['user']['id'];

// 2. LOGIKA PEMROSESAN SEMUA AKSI FORM (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pesanan = $_POST['id_pesanan'] ?? null;
    if (!$id_pesanan) {
        // Jika tidak ada ID pesanan di post, redirect saja
        header('Location: pesanan_masuk.php');
        exit;
    }

    // Aksi: Konfirmasi Pembayaran Tunai oleh Kasir (VERSI FINAL DENGAN LOG_STOK)
    if (isset($_POST['konfirmasi_bayar_tunai'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $id_kasir = $_SESSION['user']['id'];

        mysqli_begin_transaction($koneksi);
        try {
            // 1. Ambil detail item dari pesanan ini
            $sql_items = "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            // 2. Siapkan statement untuk INSERT ke log_stok dan get info produk
            $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'penjualan', ?, ?)");
            $stmt_info = mysqli_prepare($koneksi, "SELECT id_kategori_stok FROM produk WHERE id_produk = ?");

            // 3. Kurangi stok dengan cara INSERT ke log_stok untuk setiap item
            while ($item = mysqli_fetch_assoc($result_items)) {
                // Dapatkan info kategori stok dari produk
                mysqli_stmt_bind_param($stmt_info, "s", $item['id_produk']);
                mysqli_stmt_execute($stmt_info);
                $info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));

                $id_produk_log = null;
                $id_kategori_log = null;

                if ($info && $info['id_kategori_stok'] !== null) {
                    $id_kategori_log = $info['id_kategori_stok'];
                } else {
                    $id_produk_log = $item['id_produk'];
                }

                $jumlah_pengurangan = -1 * abs($item['jumlah']);
                $keterangan_log = "Penjualan via Kasir";

                mysqli_stmt_bind_param($stmt_log, "ssiss", $id_produk_log, $id_kategori_log, $jumlah_pengurangan, $id_pesanan, $keterangan_log);
                mysqli_stmt_execute($stmt_log);
            }

            // 4. Tentukan status pesanan berikutnya (ke antrean dapur)
            $sql_beban = "SELECT SUM(dp.jumlah) AS total_item_aktif FROM detail_pesanan dp JOIN pesanan p ON dp.id_pesanan = p.id_pesanan WHERE p.status_pesanan IN ('pending', 'diproses')";
            $result_beban = mysqli_query($koneksi, $sql_beban);
            $beban_dapur = mysqli_fetch_assoc($result_beban)['total_item_aktif'] ?? 0;
            $status_baru = ($beban_dapur < 20) ? 'diproses' : 'pending';

            // 5. Update status pesanan dan catat kasir yang melayani
            $stmt_update = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = ?, id_karyawan = ? WHERE id_pesanan = ? AND status_pesanan = 'menunggu_pembayaran_tunai'");
            mysqli_stmt_bind_param($stmt_update, "sis", $status_baru, $id_kasir, $id_pesanan);
            mysqli_stmt_execute($stmt_update);

            mysqli_commit($koneksi);
            $_SESSION['notif'] = ['pesan' => "Pembayaran tunai untuk #$id_pesanan berhasil dikonfirmasi dan stok telah diperbarui.", 'tipe' => 'success'];
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Gagal memproses pembayaran tunai: ' . $e->getMessage(), 'tipe' => 'danger'];
        }
    }

    if (isset($_POST['validasi_pesanan'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $id_kasir = $_SESSION['user']['id'];

        mysqli_begin_transaction($koneksi);
        try {
            $sql_beban = "SELECT SUM(dp.jumlah) AS total_item_aktif FROM detail_pesanan dp JOIN pesanan p ON dp.id_pesanan = p.id_pesanan WHERE p.status_pesanan IN ('pending', 'diproses') AND (p.id_pesanan != ?) AND (dp.id_produk LIKE 'KB%' OR dp.id_produk LIKE 'KS%')";
            $stmt_beban = mysqli_prepare($koneksi, $sql_beban);
            mysqli_stmt_bind_param($stmt_beban, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_beban);
            $result_beban = mysqli_stmt_get_result($stmt_beban);
            $beban_dapur = mysqli_fetch_assoc($result_beban)['total_item_aktif'] ?? 0;

            $status_baru = ($beban_dapur < 20) ? 'diproses' : 'pending';

            $stmt_update = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = ?, id_karyawan = ? WHERE id_pesanan = ? AND status_pesanan = 'menunggu_konfirmasi'");
            mysqli_stmt_bind_param($stmt_update, "sis", $status_baru, $id_kasir, $id_pesanan);
            mysqli_stmt_execute($stmt_update);
            mysqli_commit($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Pesanan berhasil divalidasi dan masuk antrean.', 'tipe' => 'success'];
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Gagal memvalidasi pesanan: ' . $e->getMessage(), 'tipe' => 'danger'];
        }
    }

    if (isset($_POST['batalkan_pesanan'])) {
        $id_pesanan = $_POST['id_pesanan'];
        mysqli_begin_transaction($koneksi);
        try {
            $sql_items = "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'pembatalan', ?, ?)");
            $stmt_info = mysqli_prepare($koneksi, "SELECT id_kategori_stok FROM produk WHERE id_produk = ?");

            while ($item = mysqli_fetch_assoc($result_items)) {
                mysqli_stmt_bind_param($stmt_info, "s", $item['id_produk']);
                mysqli_stmt_execute($stmt_info);
                $info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_info));

                $id_produk_log = null;
                $id_kategori_log = null;
                $jumlah_penambahan = abs($item['jumlah']);
                $keterangan_log = "Dibatalkan oleh kasir";

                if ($info && $info['id_kategori_stok'] !== null) {
                    $id_kategori_log = $info['id_kategori_stok'];
                } else {
                    $id_produk_log = $item['id_produk'];
                }
                mysqli_stmt_bind_param($stmt_log, "ssiss", $id_produk_log, $id_kategori_log, $jumlah_penambahan, $id_pesanan, $keterangan_log);
                mysqli_stmt_execute($stmt_log);
            }

            $stmt_batal = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ? AND status_pesanan = 'menunggu_konfirmasi'");
            mysqli_stmt_bind_param($stmt_batal, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_batal);

            mysqli_commit($koneksi);
            $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah dibatalkan dan stok dikembalikan.", 'tipe' => 'warning'];
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Gagal membatalkan pesanan. Error: ' . $e->getMessage(), 'tipe' => 'danger'];
        }
    }

    if (isset($_POST['siap_diambil'])) {
        $stmt_siap = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'siap_diambil' WHERE id_pesanan = ? AND status_pesanan IN ('pending', 'diproses')");
        mysqli_stmt_bind_param($stmt_siap, "s", $id_pesanan);
        mysqli_stmt_execute($stmt_siap);
        $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah ditandai Siap Diambil.", 'tipe' => 'info'];
    }

    if (isset($_POST['konfirmasi_pengambilan'])) {
        $stmt_selesai = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'selesai' WHERE id_pesanan = ? AND status_pesanan = 'siap_diambil'");
        mysqli_stmt_bind_param($stmt_selesai, "s", $id_pesanan);
        mysqli_stmt_execute($stmt_selesai);
        $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah diselesaikan.", 'tipe' => 'success'];
    }

    header('Location: pesanan_masuk.php');
    exit;
}

// 3. LOGIKA PENGAMBILAN DATA UNTUK DITAMPILKAN
$sql_pesanan_baru = "SELECT * FROM pesanan WHERE status_pesanan IN ('menunggu_pilihan_pembayaran', 'menunggu_pembayaran', 'menunggu_konfirmasi') ORDER BY tgl_pesanan ASC";
$sql_tunggu_tunai = "SELECT * FROM pesanan WHERE status_pesanan = 'menunggu_pembayaran_tunai' ORDER BY tgl_pesanan ASC";
$sql_antrean_dapur = "SELECT * FROM pesanan WHERE status_pesanan IN ('pending', 'diproses') ORDER BY FIELD(status_pesanan, 'diproses', 'pending'), tgl_pesanan ASC";
$sql_siap_diambil = "SELECT * FROM pesanan WHERE status_pesanan = 'siap_diambil' ORDER BY tgl_pesanan ASC";

$pesanan_masuk_online = mysqli_fetch_all(mysqli_query($koneksi, $sql_pesanan_baru), MYSQLI_ASSOC);
$pesanan_tunggu_tunai = mysqli_fetch_all(mysqli_query($koneksi, $sql_tunggu_tunai), MYSQLI_ASSOC);
$antrean_dapur = mysqli_fetch_all(mysqli_query($koneksi, $sql_antrean_dapur), MYSQLI_ASSOC);
$pesanan_siap_diambil = mysqli_fetch_all(mysqli_query($koneksi, $sql_siap_diambil), MYSQLI_ASSOC);

$detail_items = [];
$all_pesanan_ids = array_merge(
    array_column($pesanan_masuk_online, 'id_pesanan'),
    array_column($pesanan_tunggu_tunai, 'id_pesanan'),
    array_column($antrean_dapur, 'id_pesanan'),
    array_column($pesanan_siap_diambil, 'id_pesanan')
);

if (!empty($all_pesanan_ids)) {
    $ids_placeholder = implode(',', array_fill(0, count($all_pesanan_ids), '?'));
    $sql_details = "SELECT dp.id_pesanan, dp.jumlah, p.nama_produk 
                    FROM detail_pesanan dp 
                    JOIN produk p ON dp.id_produk = p.id_produk 
                    WHERE dp.id_pesanan IN ($ids_placeholder)";

    $stmt_details = mysqli_prepare($koneksi, $sql_details);
    mysqli_stmt_bind_param($stmt_details, str_repeat('s', count($all_pesanan_ids)), ...$all_pesanan_ids);
    mysqli_stmt_execute($stmt_details);
    $result_details = mysqli_stmt_get_result($stmt_details);

    while ($row = mysqli_fetch_assoc($result_details)) {
        $detail_items[$row['id_pesanan']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Manajemen Pesanan - Kasir</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png">
    <style>
        .kanban-board {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            padding-bottom: 1rem;
        }

        .kanban-column {
            flex: 0 0 320px;
            min-width: 320px;
            background-color: #f8f9fa;
            border-radius: 0.5rem;
        }

        .kanban-column .card-header {
            font-weight: bold;
        }

        .kanban-column .card-body {
            max-height: 68vh;
            overflow-y: auto;
            padding: 0.8rem;
        }

        .card-pesanan {
            border-left-width: 5px;
            margin-bottom: 1rem;
        }

        .card-pesanan-body {
            padding: 0.8rem;
            font-size: 0.875rem;
        }

        .card-title {
            font-size: 1rem;
            font-weight: bold;
        }

        .card-subtitle {
            font-size: 0.8rem;
        }

        .item-list {
            list-style-type: none;
            padding-left: 0;
            margin-bottom: 0.5rem;
        }

        .item-list li {
            padding: 0.2rem 0;
        }

        .total-price {
            font-size: 0.9rem;
            font-weight: bold;
        }

        .jenis-pesanan-badge {
            text-transform: capitalize;
        }

        .catatan-pesanan {
            background-color: #fff3cd;
            border-left: 4px solid #ffeeba;
            padding: 10px;
            border-radius: 4px;
            margin-top: 0.75rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .catatan-pesanan strong {
            display: block;
            margin-bottom: 5px;
            color: #856404;
        }

        .catatan-pesanan p {
            color: #555;
            margin-bottom: 0;
        }
    </style>
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
                    <h1 class="mt-4">Manajemen Pesanan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pesanan Masuk</li>
                    </ol>

                    <?php if (isset($_SESSION['notif'])): $notif = $_SESSION['notif']; ?>
                        <div class="alert alert-<?= $notif['tipe'] ?> alert-dismissible fade show" role="alert"><?= htmlspecialchars($notif['pesan']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
                    <?php unset($_SESSION['notif']);
                    endif; ?>

                    <div class="kanban-board">

                        <div class="kanban-column">
                            <div class="card h-100">
                                <div class="card-header bg-primary text-white"><i class="fas fa-cash-register me-1"></i>Bayar di Kasir (<?= count($pesanan_tunggu_tunai) ?>)</div>
                                <div class="card-body">
                                    <?php if (empty($pesanan_tunggu_tunai)): ?>
                                        <p class="text-center text-muted mt-3">Tidak ada pesanan.</p>
                                        <?php else: foreach ($pesanan_tunggu_tunai as $pesanan): ?>
                                            <div class="card card-pesanan border-primary">
                                                <div class="card-pesanan-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($pesanan['id_pesanan']) ?></h6>
                                                    <hr class="my-2">
                                                    <p class="mb-1 small"><strong>Jenis:</strong> <span class="badge bg-light text-dark jenis-pesanan-badge"><?= htmlspecialchars(str_replace('_', ' ', $pesanan['jenis_pesanan'])) ?></span></p>
                                                    <ul class="item-list small">
                                                        <?php if (isset($detail_items[$pesanan['id_pesanan']])): foreach ($detail_items[$pesanan['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x <?= htmlspecialchars($item['nama_produk']) ?></li>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </ul>
                                                    <?php if (!empty($pesanan['catatan'])): ?>
                                                        <div class="catatan-pesanan">
                                                            <strong><i class="fas fa-sticky-note me-1"></i>Catatan:</strong>
                                                            <p><?= htmlspecialchars($pesanan['catatan']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p class="total-price mt-2">Total: Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></p>
                                                    <form method="POST" class="mt-2" onsubmit="return confirm('Konfirmasi pembayaran tunai untuk pesanan ini? Stok akan dikurangi.');">
                                                        <input type="hidden" name="id_pesanan" value="<?= $pesanan['id_pesanan'] ?>">
                                                        <button type="submit" name="konfirmasi_bayar_tunai" class="btn btn-primary btn-sm w-100"><i class="fas fa-check"></i> Konfirmasi Bayar</button>
                                                    </form>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="kanban-column">
                            <div class="card h-100">
                                <div class="card-header bg-secondary text-white"><i class="fas fa-inbox me-1"></i>Pesanan Online Belum Lunas (<?= count($pesanan_masuk_online) ?>)</div>
                                <div class="card-body">
                                    <?php if (empty($pesanan_masuk_online)): ?>
                                        <p class="text-center text-muted mt-3">Tidak ada pesanan.</p>
                                        <?php else: foreach ($pesanan_masuk_online as $pesanan): ?>
                                            <div class="card card-pesanan border-secondary">
                                                <div class="card-pesanan-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($pesanan['id_pesanan']) ?></h6>
                                                    <hr class="my-2">
                                                    <p class="mb-1 small"><strong>Jenis:</strong> <span class="badge bg-light text-dark jenis-pesanan-badge"><?= htmlspecialchars(str_replace('_', ' ', $pesanan['jenis_pesanan'])) ?></span></p>
                                                    <ul class="item-list small">
                                                        <?php if (isset($detail_items[$pesanan['id_pesanan']])): foreach ($detail_items[$pesanan['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x <?= htmlspecialchars($item['nama_produk']) ?></li>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </ul>
                                                    <?php if (!empty($pesanan['catatan'])): ?>
                                                        <div class="catatan-pesanan">
                                                            <strong><i class="fas fa-sticky-note me-1"></i>Catatan:</strong>
                                                            <p><?= htmlspecialchars($pesanan['catatan']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p class="total-price mt-2">Total: Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></p>
                                                    <div class="alert alert-light p-2 small text-center mt-2">Menunggu Pembayaran Online dari Pelanggan</div>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="kanban-column">
                            <div class="card h-100">
                                <div class="card-header bg-warning text-dark"><i class="fas fa-fire-burner me-1"></i>Antrean Dapur (<?= count($antrean_dapur) ?>)</div>
                                <div class="card-body">
                                    <?php if (empty($antrean_dapur)): ?>
                                        <p class="text-center text-muted mt-3">Antrean dapur kosong.</p>
                                        <?php else: foreach ($antrean_dapur as $antrean): ?>
                                            <div class="card card-pesanan border-warning">
                                                <div class="card-pesanan-body">
                                                    <div class="d-flex justify-content-between">
                                                        <h5 class="card-title"><?= htmlspecialchars($antrean['nama_pemesan']) ?></h5>
                                                        <span class="badge bg-<?= $antrean['status_pesanan'] === 'pending' ? 'danger' : 'primary' ?>"><?= ucfirst($antrean['status_pesanan']) ?></span>
                                                    </div>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($antrean['id_pesanan']) ?></h6>
                                                    <hr class="my-2">
                                                    <p class="mb-1 small"><strong>Jenis:</strong> <span class="badge bg-light text-dark jenis-pesanan-badge"><?= htmlspecialchars(str_replace('_', ' ', $antrean['jenis_pesanan'])) ?></span></p>
                                                    <ul class="item-list small">
                                                        <?php if (isset($detail_items[$antrean['id_pesanan']])): foreach ($detail_items[$antrean['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x <?= htmlspecialchars($item['nama_produk']) ?></li>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </ul>
                                                    <?php if (!empty($antrean['catatan'])): ?>
                                                        <div class="catatan-pesanan">
                                                            <strong><i class="fas fa-sticky-note me-1"></i>Catatan:</strong>
                                                            <p><?= htmlspecialchars($antrean['catatan']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p class="total-price mt-2">Total: Rp <?= number_format($antrean['total_harga'], 0, ',', '.') ?></p>
                                                    <form method="POST" class="mt-2" onsubmit="return confirm('Tandai pesanan ini sudah SIAP DIAMBIL?');">
                                                        <input type="hidden" name="id_pesanan" value="<?= $antrean['id_pesanan'] ?>">
                                                        <button type="submit" name="siap_diambil" class="btn btn-dark btn-sm w-100">Tandai Siap Diambil</button>
                                                    </form>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="kanban-column">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white"><i class="fas fa-check-circle me-1"></i>Siap Diambil (<?= count($pesanan_siap_diambil) ?>)</div>
                                <div class="card-body">
                                    <?php if (empty($pesanan_siap_diambil)): ?>
                                        <p class="text-center text-muted mt-3">Tidak ada pesanan.</p>
                                        <?php else: foreach ($pesanan_siap_diambil as $siap): ?>
                                            <div class="card card-pesanan border-success">
                                                <div class="card-pesanan-body">
                                                    <h5 class="card-title"><?= htmlspecialchars($siap['nama_pemesan']) ?></h5>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($siap['id_pesanan']) ?></h6>
                                                    <hr class="my-2">
                                                    <p class="mb-1 small"><strong>Jenis:</strong> <span class="badge bg-light text-dark jenis-pesanan-badge"><?= htmlspecialchars(str_replace('_', ' ', $siap['jenis_pesanan'])) ?></span></p>
                                                    <ul class="item-list small">
                                                        <?php if (isset($detail_items[$siap['id_pesanan']])): foreach ($detail_items[$siap['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x <?= htmlspecialchars($item['nama_produk']) ?></li>
                                                        <?php endforeach;
                                                        endif; ?>
                                                    </ul>
                                                    <?php if (!empty($siap['catatan'])): ?>
                                                        <div class="catatan-pesanan">
                                                            <strong><i class="fas fa-sticky-note me-1"></i>Catatan:</strong>
                                                            <p><?= htmlspecialchars($siap['catatan']); ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p class="total-price mt-2">Total: Rp <?= number_format($siap['total_harga'], 0, ',', '.') ?></p>
                                                    <form method="POST" class="mt-2" onsubmit="return confirm('Konfirmasi pesanan ini sudah diambil pelanggan?');">
                                                        <input type="hidden" name="id_pesanan" value="<?= $siap['id_pesanan'] ?>">
                                                        <button type="submit" name="konfirmasi_pengambilan" class="btn btn-success btn-sm w-100">Konfirmasi Pengambilan</button>
                                                    </form>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/scripts.js"></script>
    <script>
        // Fungsi ini dijalankan segera untuk menerapkan status sidebar TERTUTUP jika tersimpan
        if (localStorage.getItem('sb|sidebar-toggled') === 'true') {
            document.body.classList.add('sb-sidenav-toggled');
        }

        window.addEventListener('DOMContentLoaded', event => {
            const sidebarToggle = document.body.querySelector('#sidebarToggle');
            if (sidebarToggle) {
                // Event listener ini hanya bertugas MENYIMPAN status, bukan melakukan toggle
                sidebarToggle.addEventListener('click', event => {
                    // Diberi sedikit jeda agar skrip asli (scripts.js) selesai mengubah class
                    setTimeout(function() {
                        localStorage.setItem('sb|sidebar-toggled', document.body.classList.contains('sb-sidenav-toggled'));
                    }, 10);
                });
            }
        });
    </script>
</body>

</html>