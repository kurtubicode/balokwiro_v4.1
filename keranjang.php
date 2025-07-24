<?php
// Definisikan judul halaman untuk digunakan di header
$page_title = "Keranjang Belanja";

// Panggil kerangka bagian atas (header)
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

    .cart-page {
        padding: 8rem 7% 4rem;
        max-width: 960px;
        margin: 0 auto;
    }

    .cart-page h2 {
        text-align: center;
        font-size: 2.6rem;
        margin-bottom: 2rem;
        color: var(--primary);
    }

    .cart-page h2 span {
        color: #333;
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
        font-size: 1.4rem;
    }

    .cart-table th,
    .cart-table td {
        border: 1px solid #ddd;
        padding: 1rem 1.2rem;
        text-align: left;
        vertical-align: middle;
    }

    .cart-table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .cart-table .text-end {
        text-align: right;
    }

    .cart-table .text-center {
        text-align: center;
    }

    .quantity-control {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .quantity-control button {
        background: var(--primary);
        color: white;
        border: none;
        cursor: pointer;
        padding: 0.4rem 0.8rem;
        font-size: 1.2rem;
        line-height: 1;
    }

    .quantity-control input {
        width: 50px;
        text-align: center;
        border: 1px solid #ccc;
        margin: 0 0.5rem;
        padding: 0.3rem;
        font-size: 1.4rem;
    }

    .remove-btn {
        color: #e74c3c;
        text-decoration: none;
        font-weight: bold;
    }

    .remove-btn:hover {
        color: #c0392b;
    }

    .cart-total {
        text-align: right;
        font-size: 1.8rem;
        font-weight: bold;
        margin-bottom: 2rem;
    }

    .checkout-form {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 1px solid #ddd;
    }

    .checkout-form h3 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 2rem;
    }

    .checkout-form .input-group {
        display: flex;
        align-items: center;
        margin-top: 1.5rem;
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        padding-left: 1.5rem;
        border-radius: 0.5rem;
    }

    .checkout-form .input-group input,
    .checkout-form .input-group textarea,
    .checkout-form .input-group select {
        width: 100%;
        padding: 1.5rem;
        font-size: 1.4rem;
        background: none;
        color: #333;
        border: none;
        outline: none;
        resize: vertical;
        font-family: inherit;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }

    .checkout-form .input-group svg {
        color: #555;
    }

    .checkout-form .btn {
        margin-top: 2rem;
        display: inline-block;
        padding: 1rem 3rem;
        font-size: 1.7rem;
        font-weight: 700;
        color: #fff;
        background-color: var(--primary);
        border-radius: 0.5rem;
        cursor: pointer;
        width: 100%;
        box-sizing: border-box;
        transition: background-color 0.3s ease;
        border: none;
    }

    .checkout-form .btn:hover {
        background-color: #c89a6f;
    }

    .checkout-form .input-group select {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 1.5rem center;
        background-size: 1.2em;
        padding-right: 4rem;
    }
</style>

<main>
    <section class="cart-page" id="cart">
        <h2>Keranjang <span>Belanja Anda</span></h2>

        <div class="row">
            <div class="cart-container">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga Satuan</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items-container">
                    </tbody>
                </table>
            </div>

            <div class="cart-total" id="cart-total-price">
                Total: Rp 0
            </div>

            <div class="checkout-form">
                <h3>Lengkapi Data untuk Pemesanan</h3>

                <form id="checkout-form" action="proses_checkout.php" method="POST">
                    <div class="input-group">
                        <i data-feather="user"></i>
                        <input type="text" name="nama_pemesan" placeholder="Nama Lengkap Anda" required>
                    </div>
                    <div class="input-group">
                        <i data-feather="mail"></i>
                        <input type="email" name="email_pemesan" placeholder="Alamat email Anda" required>
                    </div>
                    <div class="input-group">
                        <i data-feather="phone"></i>
                        <input
                            type="tel"
                            name="telepon_pemesan"
                            placeholder="08XXXXXXXXX"
                            required
                            maxlength="13"
                            pattern="08[0-9]{8,11}"
                            title="Nomor telepon harus dimulai dengan 08 dan terdiri dari 10 hingga 13 digit angka.">
                    </div>
                    <div class="input-group">
                        <i data-feather="list"></i>
                        <select name="jenis_pesanan" required>
                            <option value="" disabled selected>Pilih Jenis Pesanan</option>
                            <option value="take_away">Take away</option>
                            <option value="dine_in">Dine in</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <i data-feather="file-text"></i>
                        <textarea name="catatan"
                            placeholder="Catatan untuk pesanan (opsional). Contoh: Kue baloknya setengah matang."
                            rows="3"></textarea>
                    </div>

                    <input type="hidden" name="keranjang_data" id="cart-data-input">

                    <button type="submit" class="btn">Lanjutkan ke Pembayaran</button>
                </form>
            </div>
        </div>
    </section>
</main>

<script src="https://unpkg.com/feather-icons"></script>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Memanggil fungsi untuk menampilkan item keranjang dari file script.js utama Anda.
        // Pastikan file script.js Anda sudah dimuat (biasanya di footer.php atau sebelum tag </body>).
        if (typeof renderCartPage === 'function') {
            renderCartPage();
        } else {
            console.error('Fungsi renderCartPage tidak ditemukan. Pastikan file script.js utama sudah dimuat sebelum script ini.');
        }

        // Inisialisasi ikon Feather Icons
        if (typeof feather !== 'undefined') {
            feather.replace();
        }

        // Menambahkan event listener ke form checkout
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', function(event) {
                // 1. Ambil data keranjang dari localStorage
                const cartData = localStorage.getItem('kueBalokCart');

                // 2. Validasi: jika keranjang kosong, hentikan pengiriman form dan beri peringatan
                if (!cartData || Object.keys(JSON.parse(cartData)).length === 0) {
                    event.preventDefault(); // Mencegah form dikirim
                    alert('Keranjang Anda kosong! Silakan pilih produk terlebih dahulu.');
                    return;
                }

                // 3. Jika keranjang ada isinya, masukkan data tersebut ke dalam input tersembunyi
                document.getElementById('cart-data-input').value = cartData;

                // 4. Setelah itu, biarkan form melanjutkan proses 'submit' standarnya ke 'proses_checkout.php'
            });
        }
    });
</script>

<?php
// Panggil kerangka bagian bawah (footer)
include 'includes/footer.php';
?>