<?php
session_start();
include '../../koneksi.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

// 2. LOGIKA PROSES PESANAN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cart_data'])) {
    $id_karyawan_session = $_SESSION['user']['id'];
    $nama_pemesan = $_POST['nama_pemesan'] ?: 'Walk-in';
    $jenis_pesanan_form = $_POST['jenis_pesanan']; // 'dine_in' atau 'take_away'
    $metode_pembayaran = $_POST['metode_pembayaran'];
    $catatan = $_POST['catatan'] ?? '';
    $cart_data = json_decode($_POST['cart_data'], true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($cart_data)) {
        $pesan_error = "Terjadi kesalahan data keranjang. Silakan coba lagi.";
    } else {
        mysqli_begin_transaction($koneksi);
        try {
            // === VALIDASI STOK DENGAN SISTEM LOG_STOK ===
            $produk_info_map = []; // Untuk menyimpan info produk yang sudah divalidasi
            $stmt_produk_info = mysqli_prepare($koneksi, "SELECT id_produk, nama_produk, harga, status_produk, id_kategori_stok FROM produk WHERE id_produk = ?");
            $stmt_cek_stok_kategori = mysqli_prepare($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok = ?");
            $stmt_cek_stok_individu = mysqli_prepare($koneksi, "SELECT SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk = ?");

            foreach ($cart_data as $id => $item) {
                mysqli_stmt_bind_param($stmt_produk_info, "s", $id);
                mysqli_stmt_execute($stmt_produk_info);
                $produk_db = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_produk_info));

                if (!$produk_db || $produk_db['status_produk'] !== 'aktif') {
                    throw new Exception("Produk '{$item['name']}' tidak tersedia.");
                }

                $stok_saat_ini = 0;
                if ($produk_db['id_kategori_stok'] !== null) {
                    // Cek stok kategori
                    mysqli_stmt_bind_param($stmt_cek_stok_kategori, "s", $produk_db['id_kategori_stok']);
                    mysqli_stmt_execute($stmt_cek_stok_kategori);
                    $stok_saat_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_stok_kategori))['total'] ?? 0;
                } else {
                    // Cek stok individu
                    mysqli_stmt_bind_param($stmt_cek_stok_individu, "s", $id);
                    mysqli_stmt_execute($stmt_cek_stok_individu);
                    $stok_saat_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_cek_stok_individu))['total'] ?? 0;
                }

                if ($stok_saat_ini < $item['quantity']) {
                    throw new Exception("Stok untuk produk '{$item['name']}' tidak mencukupi (sisa: {$stok_saat_ini}).");
                }
                $produk_info_map[$id] = $produk_db; // Simpan info jika valid
            }

            // === PERUBAHAN UTAMA: RE-KALKULASI TOTAL HARGA & DISKON DI BACKEND ===
            $total_harga_sebelum_diskon = 0;
            $total_kue_balok = 0;

            foreach ($cart_data as $id => $item) {
                // Re-hitung total harga dari database, bukan dari frontend
                $total_harga_sebelum_diskon += $produk_info_map[$id]['harga'] * $item['quantity'];

                // Identifikasi jumlah kue balok (Asumsi ID dimulai dengan 'KB')
                if (strpos($id, 'KB') === 0) {
                    $total_kue_balok += $item['quantity'];
                }
            }

            // Terapkan logika diskon
            // Terapkan logika diskon dengan kelipatan
            $diskon = 0;

            // Cek jika jumlah tepat 5
            if ($total_kue_balok === 5) {
                $diskon = 2000;
            }
            // Cek jika jumlah 10 atau kelipatan 10
            else if ($total_kue_balok > 0 && ($total_kue_balok % 10) === 0) {
                $jumlah_porsi_10 = $total_kue_balok / 10;
                $diskon = $jumlah_porsi_10 * 5000;
            }

            $total_harga_final = $total_harga_sebelum_diskon - $diskon;

            // PENTING: Gunakan total_harga_final ini, bukan yang dikirim dari frontend
            $total_amount_yang_dipakai = $total_harga_final;

            // PENTING: Gunakan total_harga_final ini, bukan yang dikirim dari frontend
            $total_amount_yang_dipakai = $total_harga_final;
            // Logika "Beban Dapur" (tetap sama)
            $sql_beban = "SELECT SUM(dp.jumlah) AS total_item_aktif FROM detail_pesanan dp JOIN pesanan p ON dp.id_pesanan = p.id_pesanan WHERE p.status_pesanan IN ('pending', 'diproses') AND (dp.id_produk LIKE 'KB%' OR dp.id_produk LIKE 'KS%')";
            $result_beban = mysqli_query($koneksi, $sql_beban);
            $beban_dapur = mysqli_fetch_assoc($result_beban)['total_item_aktif'] ?? 0;
            $status_awal = ($beban_dapur < 50) ? 'diproses' : 'pending';

            // Insert ke tabel pesanan (Gunakan total harga yang sudah divalidasi dan didiskon)
            $id_pesanan_baru = "KSR-" . date("YmdHis");
            $tgl_pesanan = date("Y-m-d H:i:s");
            $tipe_pesanan_baru = 'kasir';
            $stmt_pesanan = mysqli_prepare($koneksi, "INSERT INTO pesanan (id_pesanan, id_karyawan, tipe_pesanan, jenis_pesanan, nama_pemesan, tgl_pesanan, total_harga, metode_pembayaran, status_pesanan, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt_pesanan, "sissssdsss", $id_pesanan_baru, $id_karyawan_session, $tipe_pesanan_baru, $jenis_pesanan_form, $nama_pemesan, $tgl_pesanan, $total_harga_final, $metode_pembayaran, $status_awal, $catatan);
            mysqli_stmt_execute($stmt_pesanan);

            // === PENGURANGAN STOK DENGAN INSERT KE LOG_STOK ===
            $stmt_detail = mysqli_prepare($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_saat_transaksi, sub_total) VALUES (?, ?, ?, ?, ?)");
            $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, id_kategori_stok, jumlah_perubahan, jenis_aksi, id_pesanan, keterangan) VALUES (?, ?, ?, 'penjualan', ?, ?)");

            foreach ($cart_data as $id => $item) {
                // 1. Insert ke detail pesanan
                $harga_saat_ini = $produk_info_map[$id]['harga'];
                $sub_total = $harga_saat_ini * $item['quantity'];
                mysqli_stmt_bind_param($stmt_detail, "ssidd", $id_pesanan_baru, $id, $item['quantity'], $harga_saat_ini, $sub_total);
                mysqli_stmt_execute($stmt_detail);

                // 2. Catat pengurangan stok di log_stok
                $id_produk_log = null;
                $id_kategori_log = null;
                $jumlah_pengurangan = -1 * abs($item['quantity']); // Pastikan nilainya negatif
                $keterangan_log = "Penjualan Kasir (Walk-in)";

                if ($produk_info_map[$id]['id_kategori_stok'] !== null) {
                    $id_kategori_log = $produk_info_map[$id]['id_kategori_stok'];
                } else {
                    $id_produk_log = $id;
                }
                mysqli_stmt_bind_param($stmt_log, "ssiss", $id_produk_log, $id_kategori_log, $jumlah_pengurangan, $id_pesanan_baru, $keterangan_log);
                mysqli_stmt_execute($stmt_log);
            }

            mysqli_commit($koneksi);
            $pesan_sukses = "Transaksi berhasil disimpan dengan ID: $id_pesanan_baru. <a href='struk.php?id=$id_pesanan_baru' target='_blank' class='btn btn-sm btn-info ms-2 fw-bold'><i class='fas fa-print'></i> Cetak Struk</a>";
            $clear_cart_js = "<script>localStorage.removeItem('kasir_cart');</script>";
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $pesan_error = "Transaksi Gagal: " . $e->getMessage();
        }
    }
}


// 3. LOGIKA PENGAMBILAN DATA PRODUK DAN STOK
$stok_terkini = [];
$query_stok = "
    SELECT id_produk, NULL as id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk IS NOT NULL GROUP BY id_produk
    UNION ALL
    SELECT NULL as id_produk, id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok IS NOT NULL GROUP BY id_kategori_stok
";
$result_stok = mysqli_query($koneksi, $query_stok);
while ($row_stok = mysqli_fetch_assoc($result_stok)) {
    if ($row_stok['id_produk']) {
        $stok_terkini['produk'][$row_stok['id_produk']] = $row_stok['total'];
    } elseif ($row_stok['id_kategori_stok']) {
        $stok_terkini['kategori'][$row_stok['id_kategori_stok']] = $row_stok['total'];
    }
}

// Query untuk mengambil data produk
$query_produk = "SELECT id_produk, nama_produk, harga, poto_produk, kategori, id_kategori_stok
                 FROM produk 
                 WHERE status_produk = 'aktif' 
                 ORDER BY kategori ASC, nama_produk ASC";
$result_produk = $koneksi->query($query_produk);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Input Pesanan - Kasir</title>
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png">
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .product-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .product-card .card-img-top {
            object-fit: cover;
            height: 120px;
        }

        .cart-item-row .form-control {
            height: calc(1.5em + .5rem + 2px);
            padding: .25rem .5rem;
            font-size: .875rem;
        }

        .cart-item-row .btn {
            padding: .25rem .5rem;
            font-size: .875rem;
        }

        #layoutSidenav_content {
            overflow-x: hidden;
            overflow-y: visible;
        }

        .daftar-menu-scrollable {
            max-height: calc(100vh - 100px);
            overflow-y: auto;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include "inc/navbar.php"; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include "inc/sidebar.php"; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Input Pesanan</h1>
                    <?php if (isset($pesan_sukses)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $pesan_sukses; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($pesan_error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $pesan_error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8 daftar-menu-scrollable">
                            <div class="row mb-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h5 class="card-title">Pilih Porsi untuk Kue Balok</h5>
                                            <p class="card-text text-muted">Klik salah satu porsi, lalu pilih varian Kue Balok.</p>
                                            <button type="button" class="btn btn-outline-primary btn-porsi" data-porsi="5">Setengah Porsi (5)</button>
                                            <button type="button" class="btn btn-outline-primary btn-porsi" data-porsi="10">Satu Porsi (10)</button>
                                            <button type="button" class="btn btn-outline-secondary ms-2" id="reset-porsi">Reset</button>
                                            <div class="mt-2" id="info-porsi-terpilih"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <?php
                                if ($result_produk && $result_produk->num_rows > 0) {
                                    $current_kategori = '';
                                    while ($row = $result_produk->fetch_assoc()) {
                                        if ($row['kategori'] != $current_kategori) {
                                            echo '<div class="col-12 mt-4"><h4>' . htmlspecialchars(strtoupper($row['kategori'])) . '</h4></div>';
                                            $current_kategori = $row['kategori'];
                                        }

                                        $stok_tampil = 0;
                                        if ($row['id_kategori_stok'] !== null) {
                                            $stok_tampil = $stok_terkini['kategori'][$row['id_kategori_stok']] ?? 0;
                                        } else {
                                            $stok_tampil = $stok_terkini['produk'][$row['id_produk']] ?? 0;
                                        }
                                ?>
                                        <div class="col-lg-3 col-md-4 mb-4">
                                            <div class="card product-card h-100"
                                                data-product-id="<?= htmlspecialchars($row['id_produk']); ?>"
                                                data-product-name="<?= htmlspecialchars($row['nama_produk']); ?>"
                                                data-product-price="<?= htmlspecialchars($row['harga']); ?>"
                                                data-product-stock="<?= htmlspecialchars($stok_tampil); ?>">

                                                <img src="../../assets/img/produk/<?= htmlspecialchars($row['poto_produk']); ?>"
                                                    class="card-img-top" alt="<?= htmlspecialchars($row['nama_produk']); ?>">

                                                <div class="card-body text-center d-flex flex-column">
                                                    <h6 class="card-title flex-grow-1"><?= htmlspecialchars($row['nama_produk']); ?></h6>
                                                    <p class="card-text mb-2"><strong>Rp <?= number_format($row['harga'], 0, ',', '.'); ?></strong></p>
                                                    <p class="card-text small text-muted">Stok: <?= $stok_tampil; ?></p>

                                                    <?php if ($stok_tampil > 0): ?>
                                                        <button class="btn btn-primary btn-sm add-to-cart-btn mt-auto">Pilih</button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm mt-auto" disabled>Stok Habis</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                <?php
                                    } // Akhir loop while
                                } else {
                                    echo '<div class="col-12"><p class="alert alert-warning">Belum ada produk aktif yang terdaftar.</p></div>';
                                }
                                ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card sticky-top" style="top: 20px;">
                                <div class="card-header">
                                    <h4 class="mb-0">Detail Pesanan</h4>
                                </div>
                                <div class="card-body">
                                    <form id="orderForm" action="pesanan_input.php" method="POST">
                                        <div id="cart-items" style="max-height: 300px; overflow-y: auto;">
                                            <p class="text-muted text-center" id="empty-cart-message">Keranjang kosong.</p>
                                        </div>
                                        <hr>
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h4>Total:</h4>
                                            <h4 id="total-display">Rp 0</h4>
                                        </div>
                                        <input type="hidden" name="cart_data" id="cart-data-input">
                                        <input type="hidden" name="total_amount" id="total-amount-input">
                                        <div class="form-group mb-3">
                                            <label for="nama_pemesan" class="form-label">Nama Pemesan:</label>
                                            <input type="text" class="form-control" id="nama_pemesan" name="nama_pemesan" placeholder="Nama Pelanggan">
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="catatan" class="form-label">Catatan (Opsional):</label>
                                            <textarea class="form-control" name="catatan" id="catatan" rows="2"></textarea>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="jenis_pesanan" class="form-label">Jenis Pesanan:</label>
                                            <select class="form-select" id="jenis_pesanan" name="jenis_pesanan" required>
                                                <option value="dine_in">Dine In</option>
                                                <option value="take_away" selected>Take Away</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="metode_pembayaran" class="form-label">Metode Pembayaran:</label>
                                            <select class="form-select" id="metode_pembayaran" name="metode_pembayaran" required>
                                                <option value="tunai">Tunai</option>
                                                <option value="qris">QRIS</option>
                                                <option value="dana">DANA</option>
                                                <option value="transfer bank">Transfer Bank</option>
                                            </select>
                                        </div>
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-success" id="charge-btn" disabled>Bayar</button>
                                            <button type="button" class="btn btn-danger" id="clear-cart-btn">Kosongkan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Definisi variabel global
            let cart = {};
            let selectedPortion = null; // Menyimpan pilihan porsi (5 atau 10)

            // Referensi elemen-elemen DOM
            const cartItemsDiv = document.getElementById('cart-items');
            const totalDisplay = document.getElementById('total-display');
            const chargeBtn = document.getElementById('charge-btn');
            const cartDataInput = document.getElementById('cart-data-input');
            const totalAmountInput = document.getElementById('total-amount-input');
            const emptyCartMessage = document.getElementById('empty-cart-message');

            // Fungsi untuk memformat angka menjadi format mata uang
            function numberFormat(amount) {
                return new Intl.NumberFormat('id-ID').format(amount);
            }

            // Fungsi untuk menyimpan data keranjang ke Local Storage
            function saveCartToLocalStorage() {
                localStorage.setItem('kasir_cart', JSON.stringify(cart));
            }

            // Fungsi untuk memuat data keranjang dari Local Storage
            function loadCartFromLocalStorage() {
                const storedCart = localStorage.getItem('kasir_cart');
                if (storedCart) {
                    cart = JSON.parse(storedCart);
                }
            }

            // Fungsi utama untuk memperbarui tampilan keranjang dan menghitung total
            function updateCartDisplay() {
                cartItemsDiv.innerHTML = '';
                let total = 0;
                let totalKueBalok = 0;
                let totalNonKueBalok = 0;
                let diskon = 0;

                const hasItems = Object.keys(cart).length > 0;

                if (hasItems) {
                    emptyCartMessage.style.display = 'none';
                    for (const productId in cart) {
                        const item = cart[productId];
                        const itemSubtotal = item.price * item.quantity;
                        total += itemSubtotal;

                        // Hitung total jumlah kue balok dan item non-kue balok
                        if (productId.startsWith('KB')) {
                            totalKueBalok += item.quantity;
                        } else {
                            totalNonKueBalok += item.quantity;
                        }

                        const itemHtml = `
                    <div class="d-flex justify-content-between align-items-center mb-2 cart-item-row" data-product-id="${productId}">
                        <div>
                            <strong class="d-block">${item.name}</strong>
                            <div class="input-group input-group-sm mt-1" style="width: 120px;">
                                <button class="btn btn-outline-secondary minus-btn" type="button">-</button>
                                <input type="text" class="form-control text-center quantity-input" value="${item.quantity}" readonly>
                                <button class="btn btn-outline-secondary plus-btn" type="button">+</button>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="d-block">Rp ${numberFormat(itemSubtotal)}</span>
                            <button class="btn btn-sm btn-link text-danger remove-item-btn p-0">Hapus</button>
                        </div>
                    </div>`;
                        cartItemsDiv.innerHTML += itemHtml;
                    }

                    // Tentukan diskon berdasarkan total kue balok
                    // Logika diskon kelipatan porsi
                    if (totalKueBalok === 5) {
                        // Jika jumlah tepat 5
                        diskon = 2000;
                    } else if (totalKueBalok > 0 && (totalKueBalok % 10) === 0) {
                        // Jika jumlah 10 atau kelipatan 10 (contoh: 10, 20, 30, dst.)
                        let jumlahPorsi10 = totalKueBalok / 10;
                        diskon = jumlahPorsi10 * 5000;
                    } else {
                        // Untuk jumlah selain 5, 10, dan kelipatan 10 (contoh: 15, 25, 30), tidak ada diskon
                        diskon = 0;
                    }

                    // Tampilkan diskon di keranjang jika ada
                    if (diskon > 0) {
                        cartItemsDiv.innerHTML += `
                    <div class="d-flex justify-content-between text-success mt-2 fw-bold">
                        <span>Diskon Porsi:</span>
                        <span>- Rp ${numberFormat(diskon)}</span>
                    </div>
                `;
                    }
                } else {
                    cartItemsDiv.innerHTML = '';
                    cartItemsDiv.appendChild(emptyCartMessage);
                    emptyCartMessage.style.display = 'block';
                }

                const totalFinal = total - diskon;
                totalDisplay.textContent = 'Rp ' + numberFormat(totalFinal);

                // Logika validasi tombol "Bayar"
                // Tombol akan nonaktif jika keranjang kosong ATAU jika
                // total kue balok kurang dari 5 DAN tidak ada item lain di keranjang.
                if (!hasItems || (totalKueBalok < 5 && totalNonKueBalok === 0)) {
                    chargeBtn.disabled = true;
                } else {
                    chargeBtn.disabled = false;
                }

                cartDataInput.value = JSON.stringify(cart);
                totalAmountInput.value = totalFinal;
                saveCartToLocalStorage();
            }

            // Fungsi untuk menambah produk ke keranjang
            function addToCart(card) {
                const productId = card.dataset.productId;
                const stock = parseInt(card.dataset.productStock);
                const productName = card.dataset.productName;
                const productPrice = parseInt(card.dataset.productPrice);
                const isKueBalok = productId.startsWith('KB');

                let quantityToAdd = 1;
                // Logika baru: Jika porsi dipilih dan produknya adalah Kue Balok
                if (selectedPortion !== null && isKueBalok) {
                    quantityToAdd = selectedPortion;
                    selectedPortion = null;
                    document.querySelectorAll('.btn-porsi').forEach(btn => btn.classList.remove('active', 'btn-primary'));
                    document.getElementById('info-porsi-terpilih').innerHTML = '';
                }

                if (cart[productId]) {
                    if (cart[productId].quantity + quantityToAdd <= stock) {
                        cart[productId].quantity += quantityToAdd;
                    } else {
                        alert('Stok tidak mencukupi!');
                    }
                } else {
                    if (stock >= quantityToAdd) {
                        cart[productId] = {
                            name: productName,
                            price: productPrice,
                            quantity: quantityToAdd,
                            stock: stock
                        };
                    } else {
                        alert('Stok produk ini habis!');
                    }
                }
                updateCartDisplay();
            }

            // Event listener untuk tombol porsi
            document.querySelectorAll('.btn-porsi').forEach(button => {
                button.addEventListener('click', function() {
                    document.querySelectorAll('.btn-porsi').forEach(btn => btn.classList.remove('active', 'btn-primary'));
                    this.classList.add('active', 'btn-primary');
                    selectedPortion = parseInt(this.dataset.porsi);
                    document.getElementById('info-porsi-terpilih').innerHTML = `
                <span class="badge bg-success">Porsi ${selectedPortion} Kue Balok dipilih. Pilih produk Kue Balok sekarang.</span>
            `;
                });
            });

            // Event listener untuk tombol reset porsi
            document.getElementById('reset-porsi').addEventListener('click', function() {
                document.querySelectorAll('.btn-porsi').forEach(btn => btn.classList.remove('active', 'btn-primary'));
                selectedPortion = null;
                document.getElementById('info-porsi-terpilih').innerHTML = '';
            });

            // Event listener untuk klik pada kartu produk
            document.querySelectorAll('.product-card').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'BUTTON') {
                        addToCart(this);
                    }
                });
                card.querySelector('.add-to-cart-btn')?.addEventListener('click', function(e) {
                    e.stopPropagation();
                    addToCart(card);
                });
            });

            // Event listener untuk tombol +/- dan "Hapus" di keranjang
            cartItemsDiv.addEventListener('click', function(e) {
                const row = e.target.closest('.cart-item-row');
                if (!row) return;
                const productId = row.dataset.productId;

                if (e.target.classList.contains('plus-btn')) {
                    if (cart[productId].quantity < cart[productId].stock) {
                        cart[productId].quantity++;
                    } else {
                        alert('Stok tidak mencukupi!');
                    }
                } else if (e.target.classList.contains('minus-btn')) {
                    cart[productId].quantity--;
                    if (cart[productId].quantity <= 0) {
                        delete cart[productId];
                    }
                } else if (e.target.classList.contains('remove-item-btn')) {
                    delete cart[productId];
                }
                updateCartDisplay();
            });

            // Event listener untuk tombol "Kosongkan"
            document.getElementById('clear-cart-btn').addEventListener('click', function() {
                if (Object.keys(cart).length > 0 && confirm('Anda yakin ingin mengosongkan keranjang?')) {
                    cart = {};
                    updateCartDisplay();
                }
            });

            // Event listener untuk submit form
            document.getElementById('orderForm').addEventListener('submit', function(e) {
                if (Object.keys(cart).length === 0) {
                    e.preventDefault();
                    alert('Keranjang pesanan masih kosong!');
                }
            });

            // Muat data dari Local Storage dan perbarui tampilan saat halaman dimuat
            loadCartFromLocalStorage();
            updateCartDisplay();
        });
    </script>
    <?php if (isset($clear_cart_js)) {
        echo $clear_cart_js;
    } ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/scripts.js"></script>

    <script>
        if (localStorage.getItem('sb|sidebar-toggled') === 'true') {
            document.body.classList.add('sb-sidenav-toggled');
        }
        window.addEventListener('DOMContentLoaded', event => {
            const sidebarToggle = document.body.querySelector('#sidebarToggle');
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', event => {
                    setTimeout(function() {
                        localStorage.setItem('sb|sidebar-toggled', document.body.classList.contains('sb-sidenav-toggled'));
                    }, 10);
                });
            }
        });
    </script>
</body>

</html>