<?php
// backend/users/superadmin/lap/export_pdf.php

session_start();
require_once '../../../../composer/vendor/autoload.php'; // Panggil autoloader Composer
require_once '../../../koneksi.php'; // Panggil koneksi database

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. OTENTIKASI & OTORISASI (Hanya Owner/Superadmin)
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    die("Akses ditolak. Anda harus login sebagai owner.");
}

// 2. AMBIL DATA DENGAN FILTER TANGGAL
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$where_clause = "WHERE status_pesanan = 'selesai' AND DATE(tgl_pesanan) BETWEEN ? AND ?";

// -- Query untuk tabel rincian transaksi --
$sql_laporan = "SELECT p.id_pesanan, p.nama_pemesan, p.tgl_pesanan, p.total_harga, p.metode_pembayaran, k.nama as nama_kasir FROM pesanan p LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan $where_clause ORDER BY p.tgl_pesanan DESC";
$stmt_laporan = mysqli_prepare($koneksi, $sql_laporan);
mysqli_stmt_bind_param($stmt_laporan, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_laporan);
$data_laporan = mysqli_fetch_all(mysqli_stmt_get_result($stmt_laporan), MYSQLI_ASSOC);

// -- Query untuk ringkasan eksekutif --
$sql_summary = "SELECT COUNT(id_pesanan) as total_pesanan, SUM(total_harga) as total_pendapatan FROM pesanan $where_clause";
$stmt_summary = mysqli_prepare($koneksi, $sql_summary);
mysqli_stmt_bind_param($stmt_summary, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_summary);
$summary = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_summary));
$total_pendapatan = $summary['total_pendapatan'] ?? 0;
$total_pesanan_selesai = $summary['total_pesanan'] ?? 0;
$rata_rata_per_pesanan = ($total_pesanan_selesai > 0) ? ($total_pendapatan / $total_pesanan_selesai) : 0;

// -- Query untuk produk terlaris --
$sql_terlaris = "SELECT pr.nama_produk, SUM(dp.jumlah) as total_terjual FROM detail_pesanan dp JOIN produk pr ON dp.id_produk = pr.id_produk JOIN pesanan p ON dp.id_pesanan = p.id_pesanan $where_clause GROUP BY pr.id_produk, pr.nama_produk ORDER BY total_terjual DESC LIMIT 1";
$stmt_terlaris = mysqli_prepare($koneksi, $sql_terlaris);
mysqli_stmt_bind_param($stmt_terlaris, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_terlaris);
$produk_terlaris_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_terlaris));
$produk_terlaris = $produk_terlaris_data ? $produk_terlaris_data['nama_produk'] . ' (' . $produk_terlaris_data['total_terjual'] . ' terjual)' : 'Tidak ada';

// -- Query untuk rincian penjualan per produk --
$sql_penjualan_produk = "SELECT pr.nama_produk, SUM(dp.jumlah) as total_jumlah, SUM(dp.sub_total) as total_omzet FROM detail_pesanan dp JOIN produk pr ON dp.id_produk = pr.id_produk JOIN pesanan p ON dp.id_pesanan = p.id_pesanan $where_clause GROUP BY pr.id_produk, pr.nama_produk ORDER BY total_omzet DESC";
$stmt_penjualan_produk = mysqli_prepare($koneksi, $sql_penjualan_produk);
mysqli_stmt_bind_param($stmt_penjualan_produk, "ss", $start_date, $end_date);
mysqli_stmt_execute($stmt_penjualan_produk);
$data_penjualan_produk = mysqli_fetch_all(mysqli_stmt_get_result($stmt_penjualan_produk), MYSQLI_ASSOC);

// 3. BANGUN TAMPILAN LAPORAN DENGAN HTML & CSS
// Kita gunakan output buffering untuk menangkap semua echo HTML ke dalam sebuah variabel
ob_start();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
        }

        .summary-table,
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .summary-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }

        .summary-table .label {
            font-weight: bold;
            width: 40%;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .detail-table th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #777;
        }

        h2 {
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Kue Balok Mang Wiro</h1>
        <p>Laporan Penjualan</p>
        <p><strong>Periode:</strong> <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?></p>
    </div>

    <h2>Ringkasan Eksekutif</h2>
    <table class="summary-table">
        <tr>
            <td class="label">Total Pendapatan</td>
            <td>Rp <?= number_format($total_pendapatan, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="label">Total Pesanan Selesai</td>
            <td><?= number_format($total_pesanan_selesai) ?> Transaksi</td>
        </tr>
        <tr>
            <td class="label">Rata-rata per Pesanan</td>
            <td>Rp <?= number_format($rata_rata_per_pesanan, 0, ',', '.') ?></td>
        </tr>
        <tr>
            <td class="label">Produk Terlaris</td>
            <td><?= htmlspecialchars($produk_terlaris) ?></td>
        </tr>
    </table>

    <h2>Rincian Penjualan per Produk</h2>
    <table class="detail-table">
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Jumlah Terjual</th>
                <th class="text-right">Total Omzet</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data_penjualan_produk as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                    <td><?= $item['total_jumlah'] ?></td>
                    <td class="text-right">Rp <?= number_format($item['total_omzet'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Daftar Rincian Transaksi</h2>
    <table class="detail-table">
        <thead>
            <tr>
                <th>No</th>
                <th>ID Pesanan</th>
                <th>Tanggal</th>
                <th>Pemesan</th>
                <th class="text-right">Total</th>
                <th>Kasir</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data_laporan)): ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Tidak ada data untuk periode ini.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1;
                foreach ($data_laporan as $laporan): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($laporan['id_pesanan']) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($laporan['tgl_pesanan'])) ?></td>
                        <td><?= htmlspecialchars($laporan['nama_pemesan']) ?></td>
                        <td class="text-right">Rp <?= number_format($laporan['total_harga'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($laporan['nama_kasir'] ?? 'Online') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="footer">
        Laporan ini dibuat secara otomatis pada <?= date('d M Y, H:i:s') ?>
    </div>
</body>

</html>

<?php
// Ambil konten HTML yang sudah di-generate
$html = ob_get_clean();

// 4. PROSES KONVERSI KE PDF DENGAN DOMPDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait'); // Ukuran kertas A4, orientasi portrait
$dompdf->render();

// Nama file yang akan di-download
$filename = "Laporan-Penjualan-" . date('d-m-Y') . ".pdf";
// Stream file PDF ke browser untuk di-download
$dompdf->stream($filename, ["Attachment" => true]); // Set Attachment ke true agar langsung download
?>