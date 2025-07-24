<?php
// Memastikan sesi dimulai dengan aman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
    <a class="navbar-brand ps-3" href="../index.php">KueBalok</a> <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>

    <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" id="navbarDropdown" href="#" role="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                
                <li>
                    <h6 class="dropdown-header">
                        Login sebagai:<br>
                        <strong><?php echo isset($_SESSION['user']['nama']) ? htmlspecialchars($_SESSION['user']['nama']) : 'Pengguna'; ?></strong><br>
                        <small class="text-muted"><?php echo isset($_SESSION['user']['jabatan']) ? ucfirst(htmlspecialchars($_SESSION['user']['jabatan'])) : 'Role'; ?></small>
                    </h6>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item" href="../../../logout.php">Logout</a></li>
                </ul>
        </li>
    </ul>
</nav>