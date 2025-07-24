<?php
// Memastikan sesi dimulai dengan aman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Menu Utama</div>
            <a class="nav-link" href="index.php">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
            </a>

            <div class="sb-sidenav-menu-heading">Operasional Kasir</div>


            <a class="nav-link" href="pesanan_input.php">
                <div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>
                Input Pesanan
            </a>

            <a class="nav-link" href="pesanan_masuk.php">
                <div class="sb-nav-link-icon"><i class="fas fa-inbox"></i></div>
                Pesanan Masuk
            </a>

            <a class="nav-link" href="pesanan_data_riwayat.php">
                <div class="sb-nav-link-icon"><i class="fas fa-history"></i></div>
                Riwayat Pesanan
            </a>

        </div>
    </div>
    <div class="sb-sidenav-footer">
        <div class="small">Panel Role:</div>
        <strong>Kasir</strong>
    </div>
</nav>