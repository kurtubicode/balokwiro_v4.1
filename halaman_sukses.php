<?php
// Memastikan sesi dimulai dengan aman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Panggil file koneksi dan header
require_once 'backend/koneksi.php';
include 'includes/header.php';

// Ambil order_id dari URL yang dikirim oleh Midtrans
$order_id = $_GET['order_id'] ?? null;

// Validasi: jika tidak ada order_id, tampilkan pesan error
if (!$order_id) {
    echo "<main><section class='konfirmasi-page'>
            <div class='container-konfirmasi'>
                <h2>Halaman Tidak Valid</h2>
                <p>Nomor pesanan tidak ditemukan.</p>
            </div>
          </section></main>";
    include 'includes/footer.php';
    exit;
}

// Ambil data pesanan dari database untuk ditampilkan
$stmt_get = mysqli_prepare($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = ?");
mysqli_stmt_bind_param($stmt_get, "s", $order_id);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$pesanan = mysqli_fetch_assoc($result_get);

// Jika pesanan tidak ditemukan, tampilkan pesan error
if (!$pesanan) {
    echo "<main><section class='konfirmasi-page'>
            <div class='container-konfirmasi'>
                <h2>Pesanan Tidak Ditemukan</h2>
                <p>Pesanan dengan nomor " . htmlspecialchars($order_id) . " tidak ada dalam sistem kami.</p>
            </div>
          </section></main>";
    include 'includes/footer.php';
    exit;
}
?>

<style>
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    main {
        flex: 1;
    }

    .konfirmasi-page {
        padding: 8rem 7% 4rem;
    }

    .container-konfirmasi {
        max-width: 800px;
        margin: auto;
    }

    .card-konfirmasi {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 2.5rem;
        text-align: center;
        background-color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .icon-status {
        font-size: 5rem;
        margin-bottom: 1rem;
    }

    .icon-sukses {
        color: #198754;
    }

    /* Warna hijau untuk sukses */
    .card-konfirmasi h3 {
        font-size: 2.4rem;
        margin-bottom: 0.5rem;
    }

    .card-konfirmasi .pesan-sukses {
        font-size: 1.6rem;
        color: #555;
        margin-bottom: 2rem;
    }

    /* Detail Pesanan yang lebih rapi */
    .order-details {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 1.5rem;
        text-align: left;
        margin: 2rem 0;
        background-color: #f8f9fa;
    }

    .order-details p {
        font-size: 1.5rem;
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
        color: #6c757d;
    }

    .order-details strong {
        color: #343a40;
    }

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .action-buttons .btn {
        display: inline-block;
        padding: 1rem 2rem;
        font-size: 1.5rem;
        text-decoration: none;
        border-radius: 0.5rem;
        cursor: pointer;
        font-weight: 500;
    }

    /* === GAYA TOMBOL BARU === */
    /* Gaya untuk tombol utama (Kembali ke Beranda) */
    .btn-primary {
        background-color: var(--primary);
        color: #fff;
        border: 2px solid var(--primary);
        /* Menambahkan border agar ukuran konsisten */
        transition: background-color 0.3s ease, transform 0.2s ease;
    }

    .btn-primary:hover {
        background-color: #2c3e50;
        /* Warna sedikit lebih gelap saat hover */
        border-color: #2c3e50;
        transform: translateY(-2px);
    }

    /* Gaya untuk tombol sekunder (Lacak Pesanan) */
    .btn-secondary {
        background-color: #fff;
        color: var(--primary);
        border: 2px solid var(--primary);
        /* Border dengan warna tema utama */
        transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
    }

    .btn-secondary:hover {
        background-color: var(--primary);
        color: #fff;
        transform: translateY(-2px);
    }

    /* === CSS UNTUK TOMBOL SALIN === */
    .order-container {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .order-number {
        font-weight: bold;
    }

    .copy-button {
        background: transparent;
        border: none;
        cursor: pointer;
        font-size: 1.6rem;
        color: #555;
        padding: 0.5rem;
        transition: transform 0.2s ease;
    }

    .copy-button:hover {
        transform: scale(1.1);
    }



    /* Style untuk baris Nomor Pesanan */
    .order-item {
        font-size: 1.5rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid #eee;
        padding-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        /* Menjaga agar tetap rata tengah vertikal */
    }

    /* Wadah baru untuk item di sisi kanan */
    .order-value-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        /* Meratakan semua item ke kanan */
    }

    /* Ganti aturan .copy-feedback yang lama dengan ini */
    .copy-feedback {
        text-align: center;
        /* Tengahkan teks */
        margin-top: 1.5rem;
        /* Beri jarak dari kartu di atasnya */
        min-height: 22px;
        /* Sediakan ruang agar layout tidak "loncat" */
        font-weight: bold;
        font-size: 1.6rem;
        color: #198754;
    }
</style>
<main>
    <section class="konfirmasi-page">
        <div class="container-konfirmasi">
            <h2 style="text-align: center; font-size: 2.6rem; margin-bottom: 2rem;">Status <span>Pemesanan</span></h2>

            <div class="card-konfirmasi">

                <div class="icon-status icon-sukses">
                    <i class="fas fa-check-circle"></i>
                </div>

                <h3>Pembayaran Berhasil!</h3>

                <p class="pesan-sukses">
                    Terima kasih! Pesanan Anda telah lunas dan akan segera kami proses.
                </p>

                <div class="order-details">
                    <div class="order-item">
                        <span>Nomor Pesanan</span>
                        <div class="order-value-container">
                            <div class="order-container">
                                <strong class="order-number">#<?php echo htmlspecialchars($pesanan['id_pesanan']); ?></strong>
                                <button id="tombolSalin" class="copy-button" title="Salin Nomor Pesanan">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <p><span>Tanggal</span> <strong><?php echo date('d F Y, H:i', strtotime($pesanan['tgl_pesanan'])); ?></strong></p>
                    <p><span>Total Pembayaran</span> <strong>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></strong></p>
                    <p><span>Metode Pembayaran</span> <strong id="metode-pembayaran-display"><i class="fas fa-spinner fa-spin"></i> Memuat...</strong></p>
                </div>
                <div id="pesanSalin" class="copy-feedback"></div>

                <div class="action-buttons">
                    <a href="index.php" class="btn btn-primary">Kembali ke Beranda</a>
                    <a href="lacak.php" class="btn btn-secondary">Lacak Pesanan</a>
                </div>

            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil order ID dari URL
        const urlParams = new URLSearchParams(window.location.search);
        const orderId = urlParams.get('order_id');

        if (orderId) {
            // Tunggu 2 detik untuk memberi waktu notifikasi handler bekerja
            setTimeout(function() {
                fetch('backend/api/api_get_metode_bayar.php?order_id=' + orderId)
                    .then(response => response.json())
                    .then(data => {
                        const displayElement = document.getElementById('metode-pembayaran-display');
                        if (data.success) {
                            // Jika berhasil, tampilkan metode pembayaran
                            displayElement.innerHTML = data.metode_pembayaran;
                        } else {
                            // Jika gagal, tampilkan pesan error
                            displayElement.innerText = 'Gagal Memuat';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching payment method:', error);
                        const displayElement = document.getElementById('metode-pembayaran-display');
                        displayElement.innerText = 'Error';
                    });
            }, 2000); // Penundaan 2000 milidetik = 2 detik
        }
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tombolSalin = document.getElementById('tombolSalin');
        const nomorPesananElement = document.querySelector('.order-number');
        const pesanSalinElement = document.getElementById('pesanSalin');

        if (tombolSalin && nomorPesananElement) {
            tombolSalin.addEventListener('click', function() {
                const teksUntukDisalin = nomorPesananElement.innerText.trim().substring(1);

                navigator.clipboard.writeText(teksUntukDisalin).then(() => {
                    pesanSalinElement.innerText = 'Berhasil disalin!';
                    tombolSalin.innerHTML = '<i class="fas fa-check" style="color: #198754;"></i>';

                    setTimeout(() => {
                        pesanSalinElement.innerText = '';
                        tombolSalin.innerHTML = '<i class="fas fa-copy"></i>';
                    }, 2000);

                }).catch(err => {
                    console.error('Gagal menyalin:', err);
                    pesanSalinElement.innerText = 'Gagal menyalin.';
                });
            });
        }
    });
</script>

<?php
// Panggil kerangka bagian bawah (footer)
include 'includes/footer.php';
?>