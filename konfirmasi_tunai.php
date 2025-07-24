<?php
// Ambil nomor pesanan dari URL untuk ditampilkan
$order_id = $_GET['order_id'] ?? 'Tidak Dikenal';

// Panggil header. Diasumsikan koneksi.php sudah ada di dalam header.php
include 'includes/header.php';
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

    /* === STRUKTUR KARTU BARU === */
    .container-konfirmasi {
        max-width: 600px;
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
        transition: color 0.5s ease;
    }

    .icon-info {
        color: #3498db;
        /* Biru untuk Info */
    }

    .icon-sukses {
        color: #2ecc71;
        /* Hijau untuk Sukses */
    }

    .card-konfirmasi h3 {
        font-size: 2.4rem;
        margin-bottom: 0.5rem;
        color: var(--primary);
    }

    .card-konfirmasi .pesan-status {
        font-size: 1.6rem;
        color: #555;
        margin-bottom: 2rem;
        line-height: 1.6;
    }

    /* Detail Pesanan yang rapi */
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
        margin-bottom: 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .order-details span {
        color: #6c757d;
        font-weight: 500;
    }

    .order-container {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .order-number {
        font-size: 2.0rem;
        font-weight: bold;
        color: #333;
    }

    .copy-button {
        background-color: transparent;
        border: none;
        font-size: 1.5rem;
        color: #555;
        cursor: pointer;
        padding: 5px;
        transition: transform 0.2s ease, color 0.2s ease;
    }

    .copy-button:hover {
        color: #000;
        transform: scale(1.1);
    }

    .copy-feedback {
        color: #28a745;
        font-weight: bold;
        height: 20px;
        margin-top: 10px;
        transition: opacity 0.3s ease;
        font-size: 1.4rem;
    }

    /* === GAYA TOMBOL === */
    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .action-buttons a {
        text-decoration: none;
        padding: 1rem 2rem;
        border-radius: 0.5rem;
        font-size: 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        display: inline-block;
    }

    .back-link {
        background-color: var(--primary);
        color: #fff;
        border: 2px solid var(--primary);
    }

    .back-link:hover {
        background-color: #2c3e50;
        border-color: #2c3e50;
        transform: translateY(-2px);
    }

    .btn-primary {
        background-color: #fff;
        color: var(--primary);
        border: 2px solid var(--primary);
    }

    .btn-primary:hover {
        background-color: var(--primary);
        color: #fff;
        transform: translateY(-2px);
    }
</style>

<main>
    <section class="konfirmasi-page">
        <div class="container-konfirmasi">
            <h2 style="text-align: center; font-size: 2.6rem; margin-bottom: 2rem;">Konfirmasi <span>Pesanan</span></h2>

            <div class="card-konfirmasi">

                <div id="status-icon" class="icon-status icon-info">
                    <i class="fas fa-cash-register"></i>
                </div>

                <h3 id="status-judul">Pesanan Anda Telah Dibuat!</h3>

                <p id="status-instruksi" class="pesan-status">
                    Silakan tunjukkan nomor pesanan ini kepada kasir kami untuk menyelesaikan pembayaran.
                </p>

                <div class="order-details">
                    <p>
                        <span>Nomor Pesanan</span>
                    <div class="order-container">
                        <strong class="order-number">#<?php echo htmlspecialchars($order_id); ?></strong>
                        <button id="tombolSalin" class="copy-button" title="Salin Nomor Pesanan">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    </p>
                </div>

                <p id="pesanSalin" class="copy-feedback"></p>

                <div class="action-buttons">
                    <a href="index.php" class="back-link">Kembali ke Beranda</a>
                    <a href="lacak.php?id=<?php echo htmlspecialchars($order_id); ?>" class="btn btn-primary">Lacak Pesanan Anda</a>
                </div>

            </div>
        </div>
    </section>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil ID pesanan dari PHP untuk digunakan di JavaScript
        const orderId = "<?php echo htmlspecialchars($order_id); ?>";

        // Elemen-elemen yang akan diubah
        const iconDiv = document.getElementById('status-icon');
        const judulH2 = document.getElementById('status-judul');
        const instruksiP = document.getElementById('status-instruksi');

        // Fungsi untuk memeriksa status pesanan ke server
        async function checkStatus() {
            // Jika orderId tidak valid, hentikan pengecekan
            if (!orderId || orderId === 'Tidak Dikenal') {
                clearInterval(statusInterval);
                return;
            }

            try {
                // Panggil file API yang sudah kita buat sebelumnya
                const response = await fetch(`backend/api/api_cek_status.php?order_id=${orderId}`);
                const data = await response.json();

                // Jika statusnya BUKAN lagi 'menunggu_pembayaran_tunai'
                if (data.sukses && data.status !== 'menunggu_pembayaran_tunai') {
                    // Hentikan pengecekan berkala agar tidak membebani server
                    clearInterval(statusInterval);

                    // Ubah tampilan halaman menjadi 'sukses'
                    iconDiv.className = 'icon-status icon-sukses'; // Menggunakan kelas baru
                    iconDiv.innerHTML = '<i class="fas fa-check-circle"></i>';

                    judulH2.innerText = 'Pembayaran Dikonfirmasi!';

                    // Ganti teks instruksi dengan status baru yang lebih deskriptif
                    const statusText = data.status.replace(/_/g, ' ');
                    instruksiP.innerText = `Terima kasih! Pesanan Anda telah lunas dan sekarang sedang ${statusText}.`;
                }
            } catch (error) {
                console.error("Gagal memeriksa status:", error);
                // Hentikan jika terjadi error untuk mencegah loop tak terbatas
                clearInterval(statusInterval);
            }
        }

        // Jalankan fungsi checkStatus setiap 7 detik (7000 milidetik)
        const statusInterval = setInterval(checkStatus, 7000);
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {

        // Targetkan elemen berdasarkan struktur HTML Anda
        const tombolSalin = document.getElementById('tombolSalin');
        const nomorPesananElement = document.querySelector('.order-number');
        const pesanSalinElement = document.getElementById('pesanSalin');

        if (tombolSalin && nomorPesananElement) {

            tombolSalin.addEventListener('click', function() {
                // Ambil teks dari dalam span, hilangkan tanda '#' di awal
                const teksUntukDisalin = nomorPesananElement.innerText.trim().substring(1);

                navigator.clipboard.writeText(teksUntukDisalin).then(() => {

                    // Beri feedback visual kepada pengguna
                    pesanSalinElement.innerText = 'Berhasil disalin!';

                    // Ubah ikon tombol
                    tombolSalin.innerHTML = '<i class="fas fa-check" style="color: #28a745;"></i>';

                    // Setelah 2 detik, kembalikan seperti semula
                    setTimeout(() => {
                        pesanSalinElement.innerText = '';
                        tombolSalin.innerHTML = '<i class="fas fa-copy"></i>';
                    }, 2000);

                }).catch(err => {
                    console.error('Gagal menyalin:', err);
                    pesanSalinElement.innerText = 'Gagal menyalin.';
                    pesanSalinElement.style.color = 'red';
                });
            });
        }
    });
</script>

<?php
// Panggil footer
include 'includes/footer.php';
?>