<?php
session_start();
include('../../../koneksi.php');

// 1. OTENTIKASI & OTORISASI (Hanya Admin & Owner)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['jabatan'], ['admin', 'owner'])) {
    header('Location: ../../login.php');
    exit;
}

// =========================================================================
// == PERBAIKAN: Menggunakan SATU QUERY EFISIEN untuk semua data stok
// =========================================================================

$produk_dan_stok = [];
$query_lengkap = "
    SELECT 
        p.id_produk, 
        p.nama_produk, 
        p.id_kategori_stok, 
        sk.nama_kategori,
        COALESCE(stok_log.total, 0) AS stok_saat_ini
    FROM produk p
    LEFT JOIN (
        -- Subquery untuk menghitung total stok dari log
        SELECT id_produk, NULL as id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk IS NOT NULL GROUP BY id_produk
        UNION ALL
        SELECT NULL as id_produk, id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok IS NOT NULL GROUP BY id_kategori_stok
    ) AS stok_log ON p.id_produk = stok_log.id_produk OR p.id_kategori_stok = stok_log.id_kategori_stok
    LEFT JOIN kategori_stok sk ON p.id_kategori_stok = sk.id_kategori_stok
    ORDER BY p.id_kategori_stok DESC, p.nama_produk ASC
";
$result_lengkap = mysqli_query($koneksi, $query_lengkap);
while ($row = mysqli_fetch_assoc($result_lengkap)) {
    $produk_dan_stok[] = $row;
}

// Pisahkan data untuk form di modal
$data_stok_kategori = [];
$data_stok_individu = [];
$kategori_terproses = [];

foreach ($produk_dan_stok as $item) {
    if ($item['id_kategori_stok']) {
        // Pastikan setiap kategori hanya muncul sekali
        if (!in_array($item['id_kategori_stok'], $kategori_terproses)) {
            $data_stok_kategori[] = [
                'id_kategori_stok' => $item['id_kategori_stok'],
                'nama_kategori' => $item['nama_kategori'],
                'stok_saat_ini' => $item['stok_saat_ini']
            ];
            $kategori_terproses[] = $item['id_kategori_stok'];
        }
    } else {
        $data_stok_individu[] = [
            'id_produk' => $item['id_produk'],
            'nama_produk' => $item['nama_produk'],
            'stok_saat_ini' => $item['stok_saat_ini']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <title>Manajemen Stok - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                    <h1 class="mt-4">Manajemen Stok</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Stok</li>
                    </ol>

                    <?php if (isset($_SESSION['notif'])): ?>
                        <div class="alert alert-<?= htmlspecialchars($_SESSION['notif']['tipe']) ?> alert-dismissible fade show">
                            <?= htmlspecialchars($_SESSION['notif']['pesan']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php unset($_SESSION['notif']);
                    endif; ?>

                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-boxes me-1"></i>Laporan Stok Terkini</span>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#inputStokModal">
                                <i class="fas fa-edit me-1"></i> Input / Update Stok
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>Nama Produk</th>
                                        <th>Tipe Stok</th>
                                        <th class="text-center">Stok Efektif Saat Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($produk_dan_stok as $produk): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
                                            <td>
                                                <?php if ($produk['id_kategori_stok']): ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($produk['nama_kategori']) ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Individu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center fs-5"><strong><?= htmlspecialchars($produk['stok_saat_ini']) ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="inputStokModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Form Input Stok</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form action="input_stok.php" method="POST">
                                    <div class="mb-3">
                                        <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                                        <input type="text" id="keterangan" name="keterangan" class="form-control" placeholder="Cth: Stok awal pagi atau Penambahan adonan">
                                    </div>

                                    <h5 class="mt-4">Stok Kategori</h5>
                                    <h6>Stok Kue Balok dan Ketan Susu</h6>
                                    <hr>
                                    <?php foreach ($data_stok_kategori as $item): ?>
                                        <div class="mb-3 row align-items-center">
                                            <label class="col-sm-5 col-form-label"><?= htmlspecialchars($item['nama_kategori']) ?> <br><small class="text-muted">Stok Saat Ini: <?= $item['stok_saat_ini'] ?></small></label>
                                            <div class="col-sm-7">
                                                <input type="number" class="form-control" name="stok_kategori[<?= $item['id_kategori_stok'] ?>]" placeholder="Input jumlah...">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <h5 class="mt-4">Stok Individu</h5>
                                    <h6>Stok Minuman dan lainnya</h6>
                                    <hr>
                                    <?php foreach ($data_stok_individu as $item): ?>
                                        <div class="mb-3 row align-items-center">
                                            <label class="col-sm-5 col-form-label"><?= htmlspecialchars($item['nama_produk']) ?> <br><small class="text-muted">Stok Saat Ini: <?= $item['stok_saat_ini'] ?></small></label>
                                            <div class="col-sm-7">
                                                <input type="number" class="form-control" name="stok_individu[<?= $item['id_produk'] ?>]" placeholder="Input jumlah...">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>

                                    <div class="modal-footer mt-4">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="jenis_aksi" value="penambahan" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>Tambah Stok</button>
                                        <button type="submit" name="jenis_aksi" value="stok_awal" class="btn btn-warning" title="Mengatur ulang total stok menjadi angka yang diinput"><i class="fas fa-sync-alt me-1"></i>Set Total Stok</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const datatablesSimple = document.getElementById('datatablesSimple');
            if (datatablesSimple) {
                new simpleDatatables.DataTable(datatablesSimple);
            }
        });
    </script>
</body>

</html>