<?php
// Memastikan sesi dimulai dengan aman di setiap halaman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// =================================================================
// == PERBAIKAN UTAMA ADA DI SINI ==
// =================================================================

// Menggunakan path absolut yang andal untuk memuat file koneksi.
// Dari 'public/includes/' kita naik dua level ke root proyek ('../../'), lalu masuk ke 'backend/'
require_once __DIR__ . '/../backend/koneksi.php';

// Cek apakah koneksi berhasil dibuat. Jika tidak, hentikan skrip.
if (!isset($koneksi) || $koneksi === null) {
    // Pesan ini hanya akan muncul jika path di atas salah, sangat berguna untuk debugging.
    die("FATAL ERROR: Gagal memuat variabel koneksi dari file koneksi.php. Periksa kembali path di header.php");
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/png" href="backend/assets/img/logo-kuebalok.png">
    
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Kue Balok Mang Wiro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,300;0,400;0,700;1,700&display=swap"
        rel="stylesheet" />

    <script src="https://unpkg.com/feather-icons"></script>


    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-logo">
            <img src="assets/img/logo.png" alt="LOGO KUE BALOK MANG WIRO" style="width: 50px;"/>
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