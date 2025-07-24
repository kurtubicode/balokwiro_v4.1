<?php
session_start();
include '../../koneksi.php';

// Otentikasi & Otorisasi: Pastikan yang mengakses adalah kasir atau owner
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['jabatan'], ['kasir', 'owner'])) {
    die("Akses ditolak.");
}

// Validasi ID Pesanan dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Pesanan tidak valid.");
}
$id_pesanan = $_GET['id'];

// Ambil data pesanan utama
$stmt_pesanan = mysqli_prepare($koneksi, "SELECT p.*, k.nama as nama_kasir FROM pesanan p JOIN karyawan k ON p.id_karyawan = k.id_karyawan WHERE p.id_pesanan = ?");
mysqli_stmt_bind_param($stmt_pesanan, "s", $id_pesanan);
mysqli_stmt_execute($stmt_pesanan);
$pesanan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pesanan));

// Ambil item-item yang dipesan
$stmt_items = mysqli_prepare($koneksi, "SELECT d.*, pr.nama_produk FROM detail_pesanan d JOIN produk pr ON d.id_produk = pr.id_produk WHERE d.id_pesanan = ?");
mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
mysqli_stmt_execute($stmt_items);
$items = mysqli_fetch_all(mysqli_stmt_get_result($stmt_items), MYSQLI_ASSOC);

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png"> 

    <meta charset="UTF-8">
    <title>Struk Pesanan - <?= htmlspecialchars($id_pesanan) ?></title>
    <style>
        /* CSS untuk format struk thermal printer */
        @page {
            size: 80mm auto;
            /* Lebar kertas thermal 80mm */
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 10pt;
            /* Ukuran font standar untuk struk */
            color: #000;
            background-color: #fff;
            padding: 10px;
            width: 280px;
            /* Lebar konten agar pas di kertas 80mm */
            margin: auto;
        }

        .receipt-header,
        .receipt-footer {
            text-align: center;
        }

        .receipt-header h1 {
            font-size: 14pt;
            margin: 0;
            padding: 0;
        }

        .receipt-header p {
            margin: 2px 0;
            font-size: 9pt;
        }

        .transaction-details {
            margin-top: 15px;
            font-size: 9pt;
        }

        .transaction-details td {
            padding: 1px 0;
        }

        .items-table {
            width: 100%;
            margin-top: 10px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        .items-table th,
        .items-table td {
            padding: 5px 0;
        }

        .items-table .qty,
        .items-table .price {
            white-space: nowrap;
        }

        .items-table .name {
            width: 100%;
        }

        .summary-table {
            width: 100%;
            margin-top: 10px;
        }

        .summary-table td {
            padding: 2px 0;
        }

        .text-right {
            text-align: right;
        }

        .receipt-footer {
            margin-top: 20px;
        }

        .print-button {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14pt;
            margin-top: 20px;
        }

        /* Sembunyikan tombol saat mencetak */
        @media print {
            .print-button {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>Kue Balok Mang Wiro</h1>
            <p>Jl. Ostista no. 50, Subang</p>
            <p>Telp: 0812-2257-2886</p>
        </div>

        <table class="transaction-details">
            <tr>
                <td>No. Pesanan</td>
                <td>: <?= htmlspecialchars($pesanan['id_pesanan']) ?></td>
            </tr>
            <tr>
                <td>Tanggal</td>
                <td>: <?= date('d/m/Y H:i', strtotime($pesanan['tgl_pesanan'])) ?></td>
            </tr>
            <tr>
                <td>Kasir</td>
                <td>: <?= htmlspecialchars($pesanan['nama_kasir']) ?></td>
            </tr>
            <tr>
                <td>Pelanggan</td>
                <td>: <?= htmlspecialchars($pesanan['nama_pemesan']) ?></td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="name">Item</th>
                    <th class="qty text-right">Jml</th>
                    <th class="price text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="name" colspan="3"><?= htmlspecialchars($item['nama_produk']) ?></td>
                    </tr>
                    <tr>
                        <td class="name"><?= number_format($item['harga_saat_transaksi'], 0, ',', '.') ?></td>
                        <td class="qty text-right"><?= $item['jumlah'] ?></td>
                        <td class="price text-right"><?= number_format($item['sub_total'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td><strong>Total</strong></td>
                <td class="text-right"><strong>Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></strong></td>
            </tr>
            <tr>
                <td>Metode Bayar</td>
                <td class="text-right"><?= ucfirst(htmlspecialchars($pesanan['metode_pembayaran'])) ?></td>
            </tr>
        </table>

        <div class="receipt-footer">
            <p>Terima Kasih Atas Kunjungan Anda!</p>
            <p>@kuebalokmangwiro</p>
        </div>
    </div>

    <button class="print-button" onclick="window.print()">Cetak Ulang Struk</button>

</body>

</html>