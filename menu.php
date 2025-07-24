<?php
// Langkah 1: Definisikan judul halaman dan panggil header.
// Header akan secara otomatis memuat session_start() dan koneksi.php.
$page_title = "Menu Lengkap";
// include 'includes/header.php';
include 'backend/koneksi.php';

// =========================================================================
// == LOGIKA PENGAMBILAN DATA STOK & PRODUK
// =========================================================================

// Ambil semua data stok terkini dari log_stok dalam satu kali query yang efisien
$stok_terkini = [];
$query_stok = "
    SELECT id_produk, NULL as id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_produk IS NOT NULL GROUP BY id_produk
    UNION ALL
    SELECT NULL as id_produk, id_kategori_stok, SUM(jumlah_perubahan) as total FROM log_stok WHERE id_kategori_stok IS NOT NULL GROUP BY id_kategori_stok
";
$result_stok = mysqli_query($koneksi, $query_stok);

// Cek jika query stok berhasil sebelum melanjutkan
if ($result_stok) {
    while ($row_stok = mysqli_fetch_assoc($result_stok)) {
        if ($row_stok['id_produk']) {
            $stok_terkini['produk'][$row_stok['id_produk']] = $row_stok['total'];
        } elseif ($row_stok['id_kategori_stok']) {
            $stok_terkini['kategori'][$row_stok['id_kategori_stok']] = $row_stok['total'];
        }
    }
}

// Ambil semua produk yang aktif
$sql_produk = "SELECT id_produk, nama_produk, harga, poto_produk, kategori, id_kategori_stok
               FROM produk 
               WHERE status_produk = 'aktif' 
               ORDER BY id_produk ASC";
$result_produk = mysqli_query($koneksi, $sql_produk);

// Group products by jenis menu (KB, KS, OT, DK)
$produk_grouped = [
    'KB' => [],
    'KS' => [],
    'OT' => [],
    'DK' => []
];
if ($result_produk) {
    while ($row = mysqli_fetch_assoc($result_produk)) {
        $prefix = strtoupper(substr($row['id_produk'], 0, 2));
        if (isset($produk_grouped[$prefix])) {
            $produk_grouped[$prefix][] = $row;
        }
    }
}
$jenis_menu_labels = [
    'KB' => 'Kue Balok',
    'KS' => 'Ketan Susu',
    'OT' => 'Makanan Lain',
    'DK' => 'Minuman'
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="assets/img/logo-kuebalok.png">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Kue Balok Mang Wiro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,300;0,400;0,700;1,700&display=swap"
        rel="stylesheet" />

    <script src="https://unpkg.com/feather-icons"></script>

    <link rel="stylesheet" href="assets/css/style1.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        .kategori-title {
            width: 100%;
            text-align: center;
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            font-size: 2.2rem;
            color: var(--primary);
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            align-items: stretch;
        }

        /* ============================================= */
        /* == CSS PERBAIKAN UNTUK HALAMAN MENU & CARD == */
        /* ============================================= */

        /* --- 1. Perbaikan Posisi Konten Utama --- */
        /* Memberi jarak atas agar konten tidak tertutup navbar yang fixed */
        .main-content-page {
            padding-top: 6rem;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
            /* Sesuaikan angka ini jika navbar Anda lebih tinggi/pendek */
        }

        /* --- 2. Perbaikan Tampilan Kartu Menu (.menu-card) --- */
        .menu-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            padding: 1.5rem 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 370px;
            max-width: 100%;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            /* Efek sedikit terangkat saat disentuh mouse */
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-card .menu-card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 1rem;
            background: #f5f5f5;
        }

        .menu-card .menu-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0.5rem 0;
            min-height: 40px;
            max-height: 48px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-card .menu-card-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--primary);
            margin: 0.5rem 0;
        }

        .menu-card .menu-card-stock {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }

        /* --- 3. Perbaikan Tombol di Dalam Kartu --- */
        .add-to-cart-btn {
            margin-top: auto;
        }

        .add-to-cart-btn .btn {
            width: 100%;
            background-color: var(--bg);
            border: 1px solid var(--primary);
            color: var(--primary);
            padding: 0.75rem;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.2s, color 0.2s;
        }

        .add-to-cart-btn .btn:hover {
            background-color: var(--primary);
            color: #fff;
        }

        .add-to-cart-btn .btn:disabled {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #adb5bd;
            cursor: not-allowed;
        }

        .add-to-cart-btn .btn svg {
            margin-right: 0.5rem;
        }

        @media (max-width: 600px) {
            .menu-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .apple-style-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
                justify-content: unset;
            }

            .apple-card {
                width: 100%;
                min-width: 0;
                max-width: 100%;
            }
        }

        /* .main-content-page{
            padding: 1.5rem;
        } */

        .menu-filter-bar {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kategori-filter-btn-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
            background: #fff;
            border-radius: 999px;
            padding: 0.5rem 1.5rem;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.07);
            border: 1px solid #e0e0e0;
        }

        .menu-filter-bar {
            margin-top: 3rem;
            /* Sesuaikan nilai ini untuk mengatur jarak yang diinginkan */
        }

        .filter-btn {
            border-radius: 999px !important;
            border: 1px solid var(--primary) !important;
            padding: 0.5rem 1.5rem !important;
            font-weight: bold;
            background: #fff !important;
            color: var(--primary) !important;
            transition: background 0.2s, color 0.2s;
        }

        .filter-btn.active,
        .filter-btn:hover {
            background: var(--primary) !important;
            color: #fff !important;
        }

        .search-bar-wrapper {
            width: 100%;
            max-width: 400px;
            background: #fff;
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.07);
            border: 1px solid #e0e0e0;
            padding: 0.2rem 1rem;
            display: flex;
            align-items: center;
        }

        #menu-search-bar {
            width: 100%;
            border: none;
            outline: none;
            background: transparent;
            padding: 0.7rem 0.5rem;
            font-size: 1rem;
            border-radius: 999px;
        }

        .apple-style-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 32px;
            justify-content: center;
        }

        .apple-card {
            position: relative;
            width: 270px;
            min-height: 420px;
            background: #fff;
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow 0.2s;
        }

        .apple-card:hover {
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
        }

        .apple-card-img-wrapper {
            width: 100%;
            height: 220px;
            background: #f5f5f7;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .apple-card-img {
            max-width: 90%;
            max-height: 180px;
            object-fit: contain;
            border-radius: 18px;
        }

        .apple-card-content {
            padding: 24px 20px 60px 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .apple-card-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #222;
        }

        .apple-card-price {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0071e3;
            margin-bottom: 6px;
        }

        .apple-card-stock {
            font-size: 1rem;
            color: #555;
        }

        .apple-cart-btn {
            position: absolute;
            right: 18px;
            bottom: 18px;
            background: #222;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            font-size: 1.5rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .apple-cart-btn:hover {
            background: #0071e3;
        }
    </style>

</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-logo">
            <img src="assets/img/logo.png" alt="LOGO KUE BALOK MANG WIRO" style="width: 50px;" />
            <!-- <link rel="icon" type="image/png" href="backend/assets/img/logo-kuebalok.png"> -->

        </a>
        <div class="navbar-nav">
            <a href="index.php#home">Beranda</a>
            <a href="index.php#about">Tentang Kami</a>
            <a href="menu.php">Menu</a>
            <a href="lacak.php">Lacak Pesanan</a>
            <a href="index.php#faq">FAQ</a>
            <a href="index.php#contact">Kontak</a>
        </div>

        <div class="navbar-extra">
            <a href="keranjang.php" id="shopping-cart-button">
                <i data-feather="shopping-cart"></i>
                <span class="cart-item-count" style="display:none;">0</span>
            </a>
            <a href="#" id="hamburger-menu"><i data-feather="menu"></i></a>
        </div>

        <div class="search-form">
            <input type="search" id="search-box" placeholder="Cari menu...">
            <label for="search-box"><i data-feather="search"></i></label>
        </div>
    </nav>

    <main>
        <div class="main-content-page">
            <div class="menu-filter-bar">
                <div class="kategori-filter-btn-group">
                    <button class="filter-btn" data-filter="all">Semua</button>
                    <?php foreach ($jenis_menu_labels as $kode => $label): ?>
                        <button class="filter-btn" data-filter="<?= $kode ?>"> <?= htmlspecialchars($label) ?> </button>
                    <?php endforeach; ?>
                </div>
                <div class="search-bar-wrapper">
                    <input type="text" id="menu-search-bar" placeholder="Cari menu...">
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div id="menu-card-grid" class="menu-grid apple-style-grid">
                        <?php foreach ($produk_grouped as $kode => $produk_list):
                            foreach ($produk_list as $row):
                                $stok_tampil = 0;
                                if (!empty($row['id_kategori_stok'])) {
                                    $stok_tampil = $stok_terkini['kategori'][$row['id_kategori_stok']] ?? 0;
                                } else {
                                    $stok_tampil = $stok_terkini['produk'][$row['id_produk']] ?? 0;
                                }
                        ?>
                                <div class="apple-card" data-kategori="<?= $kode ?>" data-nama="<?= htmlspecialchars(strtolower($row['nama_produk'])) ?>">
                                    <div class="apple-card-img-wrapper">
                                        <img src="backend/assets/img/produk/<?= htmlspecialchars($row['poto_produk'] ?: 'default.jpg') ?>" alt="<?= htmlspecialchars($row['nama_produk']) ?>" class="apple-card-img">
                                    </div>
                                    <div class="apple-card-content">
                                        <h3 class="apple-card-title"><?= htmlspecialchars($row['nama_produk']) ?></h3>
                                        <p class="apple-card-price">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                                        <p class="apple-card-stock">Stok: <strong><?= $stok_tampil ?></strong></p>
                                    </div>
                                    <button class="apple-cart-btn"
                                        data-id="<?= htmlspecialchars($row['id_produk'] ?? '') ?>"
                                        data-nama="<?= htmlspecialchars($row['nama_produk'] ?? 'Produk') ?>"
                                        data-harga="<?= htmlspecialchars($row['harga'] ?? 0) ?>">
                                        <i data-feather="shopping-cart"></i>
                                    </button>
                                </div>
                        <?php endforeach;
                        endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter kategori
            const filterBtns = document.querySelectorAll('.filter-btn');
            const menuCards = document.querySelectorAll('.apple-card');
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.style.background = '#fff', b => b.style.color = 'var(--primary)');
                    this.style.background = 'var(--primary)';
                    this.style.color = '#fff';
                    const filter = this.dataset.filter;
                    menuCards.forEach(card => {
                        if (filter === 'all' || card.dataset.kategori === filter) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
            // Search bar
            const searchBar = document.getElementById('menu-search-bar');
            searchBar.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                menuCards.forEach(card => {
                    const nama = card.dataset.nama;
                    if (nama.includes(keyword)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });

            if (document.querySelector('.apple-card')) {
                const addToCartButtons = document.querySelectorAll('.apple-cart-btn');
                addToCartButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const product = {
                            id: this.dataset.id,
                            nama: this.dataset.nama,
                            harga: parseInt(this.dataset.harga)
                        };
                        addToCart(product);
                    });
                });
            }
        });
    </script>

    <?php include 'includes/footer.php'; ?>