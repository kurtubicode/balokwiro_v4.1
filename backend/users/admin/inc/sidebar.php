<?php
// Memastikan sesi dimulai dengan aman (jika file ini di-include terpisah)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
    <div class="sb-sidenav-menu">
        <div class="nav">
            <div class="sb-sidenav-menu-heading">Menu Utama</div>
            <a class="nav-link" href="../index.php">
                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                Dashboard
            </a>

            <div class="sb-sidenav-menu-heading">Manajemen</div>
            <a class="nav-link" href="../menu/data_menu.php">
                <div class="sb-nav-link-icon"><i class="fas fa-utensils"></i></div>
                Manajemen Menu
            </a>
            <a class="nav-link" href="../paket/data_paket.php">
                <div class="sb-nav-link-icon"><i class="fas fa-box-open"></i></div>
                Manajemen Paket
            </a>
            <a class="nav-link" href="../stok/laporan_stok.php">
                <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>
                Manajemen Stok
            </a>
        </div>
    </div>
    <div class="sb-sidenav-footer">
        <div class="small">Panel Role:</div>
        <strong><?php echo isset($_SESSION['user']['jabatan']) ? ucfirst(htmlspecialchars($_SESSION['user']['jabatan'])) : ''; ?></strong>
    </div>
</nav>