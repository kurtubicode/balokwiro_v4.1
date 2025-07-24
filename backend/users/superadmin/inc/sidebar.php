<?php
// Memastikan sesi dimulai dengan aman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Utama</div>
            <a class="nav-link" href="../index.php">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
            </a>

            <div class="sb-sidenav-menu-heading">Manajemen</div>
            <a class="nav-link" href="../karyawan/data_karyawan.php">
                <div class="sb-nav-link-icon"><i class="fas fa-users-cog"></i></div>
                Data Karyawan
            </a>

            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse"
                data-bs-target="#collapseLaporan" aria-expanded="false" aria-controls="collapseLaporan">
                <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                Laporan
                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
            </a>
            <div class="collapse" id="collapseLaporan" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
                <nav class="sb-sidenav-menu-nested nav">
                    <a class="nav-link" href="../lap/laporan_penjualan.php?jenis_laporan=pemasukan">Pemasukan</a>
                    <a class="nav-link" href="../lap/laporan_penjualan.php?jenis_laporan=produk">Produk</a>
                    <a class="nav-link" href="../lap/laporan_penjualan.php?jenis_laporan=jam_sibuk">Jam Sibuk</a>
                    <a class="nav-link" href="../lap/laporan_penjualan.php?jenis_laporan=kategori_pembayaran">Kategori & Pembayaran</a>
                </nav>
            </div>

            <a class="nav-link" href="../ulasan/tinjau_feedback.php">
                <div class="sb-nav-link-icon"><i class="fas fa-comment-alt"></i></div>
                Masukan & Saran
            </a>
        </div>
    </div>
    <div class="sb-sidenav-footer">
        <div class="small">Panel Role:</div>
        <strong>Superadmin / Owner</strong>
    </div>
</nav>