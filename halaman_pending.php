<?php
// Panggil file koneksi dan header
include __DIR__ . '/backend/koneksi.php';
include __DIR__ . '/includes/header.php';

// Ambil order_id dari URL yang dikirim oleh Midtrans
$order_id = $_GET['order_id'] ?? null;

// Validasi: jika tidak ada order_id, tampilkan pesan error
if (!$order_id) {
    echo "<main><section class='status-page'><h2>Halaman Tidak Valid</h2><p>Nomor pesanan tidak ditemukan.</p></section></main>";
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Ambil data pesanan dari database untuk ditampilkan
$stmt = $koneksi->prepare("SELECT * FROM pesanan WHERE id_pesanan = ?");
$stmt->bind_param("s", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_assoc();

// Jika pesanan tidak ditemukan, tampilkan pesan error
if (!$pesanan) {
    echo "<main><section class='status-page'><h2>Pesanan Tidak Ditemukan</h2><p>Pesanan dengan nomor " . htmlspecialchars($order_id) . " tidak ada dalam sistem kami.</p></section></main>";
    include __DIR__ . '/includes/footer.php';
    exit;
}
?>

<style>
    .status-page {
        padding: 8rem 7% 4rem;
        text-align: center;
        max-width: 600px;
        margin: auto;
    }

    .status-page .icon {
        font-size: 5rem;
        color: #f39c12;
    }

    /* KOREKSI: Warna kuning/oranye untuk pending */
    .status-page h2 {
        font-size: 2.6rem;
        color: var(--primary);
        margin: 1rem 0;
    }

    .status-page .message {
        font-size: 1.6rem;
        line-height: 1.6;
        margin-bottom: 2rem;
    }

    .order-details {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 2rem;
        text-align: left;
        margin-bottom: 2rem;
    }

    .order-details p {
        font-size: 1.4rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 1rem;
        display: flex;
        justify-content: space-between;
    }

    .order-details p:last-child {
        margin-bottom: 0;
        border-bottom: none;
        padding-bottom: 0;
    }

    .order-details span {
        color: #555;
    }

    .order-details strong {
        color: #333;
    }

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
    }

    .action-buttons .btn {
        display: inline-block;
        padding: 1rem 2rem;
        font-size: 1.5rem;
        text-decoration: none;
        border-radius: 0.5rem;
        cursor: pointer;
        border: none;
    }

    .btn-primary {
        background-color: var(--primary);
        color: #fff;
    }

    .btn-secondary {
        background-color: #ecf0f1;
        color: #333;
    }
</style>

<main>
    <section class="status-page">
        <div class="icon">⏳</div>
        <h2>Menunggu Pembayaran</h2>
        <p class="message">
            Pesanan Anda telah kami terima dan sedang menunggu pembayaran. Silakan selesaikan pembayaran sesuai instruksi yang telah ditampilkan pada jendela pembayaran sebelumnya atau yang dikirimkan ke email Anda.
        </p>

        <div class="order-details">
            <p><span>Nomor Pesanan</span> <strong>#<?php echo htmlspecialchars($pesanan['id_pesanan']); ?></strong></p>
            <p><span>Total yang Harus Dibayar</span> <strong>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></strong></p>
        </div>

        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">Kembali ke Beranda</a>
            <a href="lacak.php" class="btn btn-secondary">Lacak Status Pesanan</a>
        </div>
    </section>
</main>

<?php
// Panggil footer
include __DIR__ . '/includes/footer.php';
?>