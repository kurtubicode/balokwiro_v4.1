<?php
// Memastikan sesi dimulai dengan aman di setiap halaman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Memuat file koneksi dan header
// Pastikan path ini sesuai dengan struktur folder Anda
require_once __DIR__ . '/backend/koneksi.php';
include __DIR__ . '/includes/header.php';

// 1. VALIDASI AKSES & PENGAMBILAN DATA
// =======================================================

// Cek apakah ada pesanan yang sedang aktif di session.
// Jika tidak ada, artinya pengguna mengakses halaman ini secara tidak benar.
if (!isset($_SESSION['id_pesanan_aktif']) || empty($_SESSION['id_pesanan_aktif'])) {
    echo "<main>
            <section class='pilih-pembayaran-page'>
                <h2>Akses Ditolak</h2>
                <p class='summary'>Tidak ada pesanan yang sedang diproses. Silakan buat pesanan baru dari keranjang belanja Anda.</p>
                <div class='action-buttons'>
                    <a href='index.php' class='btn btn-primary'>Kembali ke Beranda</a>
                </div>
            </section>
          </main>";
    include __DIR__ . '/includes/footer.php';
    exit; // Hentikan eksekusi script
}

$id_pesanan = $_SESSION['id_pesanan_aktif'];

// Ambil data pesanan dari database untuk ditampilkan
$stmt = $koneksi->prepare("SELECT id_pesanan, total_harga, status_pesanan FROM pesanan WHERE id_pesanan = ?");
$stmt->bind_param("s", $id_pesanan); // "s" karena ID pesanan Anda adalah VARCHAR/string
$stmt->execute();
$result = $stmt->get_result();
$pesanan = $result->fetch_assoc();

// Jika karena suatu alasan pesanan tidak ditemukan atau statusnya sudah bukan menunggu, berikan pesan error
if (!$pesanan || $pesanan['status_pesanan'] !== 'menunggu_pilihan_pembayaran') {
    echo "<main>
            <section class='pilih-pembayaran-page'>
                <h2>Error: Pesanan Tidak Valid</h2>
                <p class='summary'>Pesanan ini tidak dapat diproses. Mungkin sudah dibayar atau dibatalkan. Silakan buat pesanan baru.</p>
                 <div class='action-buttons'>
                    <a href='index.php' class='btn btn-primary'>Kembali ke Beranda</a>
                </div>
            </section>
          </main>";
    include __DIR__ . '/includes/footer.php';
    unset($_SESSION['id_pesanan_aktif']); // Hapus session yang salah
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

    .pilih-pembayaran-page {
        padding: 8rem 7% 4rem;
        text-align: center;
        max-width: 550px;
        /* Sedikit diperkecil untuk tampilan lebih fokus */
        margin: auto;
    }

    .pilih-pembayaran-page h2 {
        font-size: 2.8rem;
        margin-bottom: 2rem;
        color: var(--primary);
    }

    /* === MEMPERBAIKI KARTU RINGKASAN === */
    .summary {
        background-color: #fff;
        padding: 2.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        border: 1px solid #eee;
        margin-bottom: 3rem;
    }

    .summary p {
        font-size: 1.6rem;
        line-height: 1.5;
        color: #666;
        margin: 0;
    }

    .summary p:first-child {
        margin-bottom: 1rem;
    }

    .summary strong {
        display: block;
        font-size: 2.2rem;
        font-weight: 700;
        color: #333;
        margin-top: 0.5rem;
    }

    /* Memberi warna pada harga agar lebih menonjol */
    .summary .total-price {
        color: var(--primary);
    }

    /* === MEMPERBAIKI GAYA TOMBOL PEMBAYARAN === */
    .pilihan-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .pilihan-container .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 1rem;
        padding: 1.2rem 3rem;
        font-size: 1.7rem;
        font-weight: 600;
        border-radius: 0.5rem;
        cursor: pointer;
        width: 100%;
        max-width: 400px;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        /* Mencegah layout 'loncat' saat hover */
    }

    .pilihan-container .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Tombol Online sebagai tombol utama (Primary) */
    .btn-online {
        background-color: var(--primary);
        color: #fff;
        border-color: var(--primary);
    }

    .btn-online:hover:not(:disabled) {
        background-color: #c89a6f;
        /* Versi lebih gelap dari warna primary */
        border-color: #c89a6f;
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    /* Tombol Tunai sebagai tombol sekunder (Secondary/Outline) */
    /* Tombol Tunai sebagai tombol sekunder (Secondary/Outline) */
    .btn-tunai {
        background-color: #fff;
        /* Latar belakang putih */
        color: var(--primary);
        /* Warna teks & ikon sesuai tema */
        border: 2px solid var(--primary);
        /* INI ADALAH STROKE/GARIS TEPI */
    }

    .btn-tunai:hover:not(:disabled) {
        background-color: var(--primary);
        color: #fff;
        transform: translateY(-3px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .action-buttons {
        margin-top: 4rem;
    }
</style>

<main>
    <section class="pilih-pembayaran-page">
        <h2>Pilih Metode Pembayaran</h2>

        <div class="summary">
            <p>Nomor Pesanan Anda: <strong>#<?php echo htmlspecialchars($pesanan['id_pesanan']); ?></strong></p>
            <p>Total yang harus dibayar: <strong>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></strong></p>
        </div>

        <div class="pilihan-container">
            <form action="proses_pembayaran_tunai.php" method="POST" style="margin:0; padding:0; width: 100%; display: flex; justify-content: center;">
                <button type="submit" class="btn btn-tunai">
                    <i data-feather="dollar-sign"></i>
                    <span>Bayar di Kasir (Tunai)</span>
                </button>
            </form>

            <button id="pay-button-online" class="btn btn-online">
                <i data-feather="credit-card"></i>
                <span>Bayar Sekarang (Online)</span>
            </button>
        </div>
    </section>
</main>

<script type="text/javascript" src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="SB-Mid-client-BZEuOjONbWpACX5e"></script>

<script src="https://unpkg.com/feather-icons"></script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi ikon
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        const payButtonOnline = document.getElementById('pay-button-online');
        const payButtonTunai = document.querySelector('.btn-tunai');

        if (payButtonOnline) {
            payButtonOnline.addEventListener('click', function() {
                // Nonaktifkan kedua tombol agar tidak diklik ganda
                this.disabled = true;
                this.querySelector('span').innerText = "Memproses...";
                payButtonTunai.disabled = true;

                // Panggil backend untuk mendapatkan Snap Token.
                fetch('proses_pembayaran_midtrans.php')
                    .then(response => {
                        if (!response.ok) {
                            // Jika status error (4xx, 5xx), coba baca sebagai teks
                            return response.text().then(text => {
                                throw new Error('Server merespon dengan error: ' + text);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }

                        // Jika token berhasil didapat, buka popup pembayaran Midtrans
                        window.snap.pay(data.snap_token, {
                            onSuccess: function(result) {
                                // Arahkan ke halaman sukses dengan ID pesanan
                                window.location.href = 'halaman_sukses.php?order_id=' + result.order_id;
                            },
                            onPending: function(result) {
                                // Arahkan ke halaman yang sama (halaman sukses akan cek statusnya)
                                window.location.href = 'halaman_sukses.php?order_id=' + result.order_id;
                            },
                            onError: function(result) {
                                alert("Pembayaran Gagal. Silakan coba lagi.");
                                // Aktifkan kembali tombol jika pembayaran gagal
                                payButtonOnline.disabled = false;
                                payButtonOnline.querySelector('span').innerText = "Bayar Sekarang (Online)";
                                payButtonTunai.disabled = false;
                            },
                            onClose: function() {
                                // Aktifkan kembali tombol jika popup ditutup
                                payButtonOnline.disabled = false;
                                payButtonOnline.querySelector('span').innerText = "Bayar Sekarang (Online)";
                                payButtonTunai.disabled = false;
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        alert('Terjadi kesalahan: ' + error.message);
                        // Aktifkan kembali tombol jika koneksi gagal
                        payButtonOnline.disabled = false;
                        payButtonOnline.querySelector('span').innerText = "Bayar Sekarang (Online)";
                        payButtonTunai.disabled = false;
                    });
            });
        }
    });
</script>

<?php
// Panggil footer
include __DIR__ . '/includes/footer.php';
?>