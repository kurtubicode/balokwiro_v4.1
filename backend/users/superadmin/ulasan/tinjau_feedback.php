<?php
session_start();
include '../../../koneksi.php'; // Path dari users/superadmin/

// 1. OTENTIKASI & OTORISASI
// Pastikan user sudah login dan merupakan seorang owner.
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../../login.php');
    exit;
}

// 2. LOGIKA HAPUS FEEDBACK
// Cek jika ada permintaan POST untuk menghapus feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_feedback'])) {
    $id_feedback_to_delete = $_POST['id_feedback'];

    // Gunakan prepared statement untuk keamanan
    $stmt_delete = mysqli_prepare($koneksi, "DELETE FROM feedback WHERE id_feedback = ?");
    mysqli_stmt_bind_param($stmt_delete, "i", $id_feedback_to_delete);
    
    if (mysqli_stmt_execute($stmt_delete)) {
        // Redirect kembali ke halaman ini dengan pesan sukses
        header('Location: tinjau_feedback.php?status=dihapus');
        exit;
    } else {
        $error_hapus = "Gagal menghapus feedback.";
    }
}

// 3. LOGIKA PENGAMBILAN DATA
// Ambil semua data dari tabel feedback, urutkan dari yang paling baru
$sql = "SELECT id_feedback, nama, email, pesan, tanggal FROM feedback ORDER BY tanggal DESC";
$result = mysqli_query($koneksi, $sql);

$feedbacks = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $feedbacks[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Tinjau Masukan & Saran - Owner</title>
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png"> 

</head>
<body class="sb-nav-fixed">
    <?php include '../inc/navbar.php'; // Navbar khusus superadmin ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include '../inc/sidebar.php'; // Sidebar khusus superadmin ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Tinjau Masukan & Saran Pelanggan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Masukan & Saran</li>
                    </ol>

                    <?php if(isset($_GET['status']) && $_GET['status'] == 'dihapus'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            PEsan berhasil dihapus.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if(isset($error_hapus)): ?>
                         <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error_hapus; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-envelope-open-text me-1"></i>
                            Daftar Masukan dari Pelanggan
                        </div>
                        <div class="card-body">
                            <?php if (!empty($feedbacks)): ?>
                                <div class="accordion" id="accordionFeedback">
                                    <?php foreach ($feedbacks as $index => $feedback): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading-<?php echo $index; ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $index; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $index; ?>">
                                                    <div class="d-flex justify-content-between w-100 me-3">
                                                        <strong><?php echo htmlspecialchars($feedback['nama']); ?></strong>
                                                        <small class="text-muted"><?php echo date('d F Y, H:i', strtotime($feedback['tanggal'])); ?></small>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse-<?php echo $index; ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?php echo $index; ?>" data-bs-parent="#accordionFeedback">
                                                <div class="accordion-body">
                                                    <p><?php echo nl2br(htmlspecialchars($feedback['pesan'])); ?></p>
                                                    <hr>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="text-muted">Email: <?php echo htmlspecialchars($feedback['email']); ?></small>
                                                        
                                                        <form method="POST" action="tinjau_feedback.php" onsubmit="return confirm('Anda yakin ingin menghapus feedback ini secara permanen?');">
                                                            <input type="hidden" name="id_feedback" value="<?php echo $feedback['id_feedback']; ?>">
                                                            <button type="submit" name="hapus_feedback" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info text-center">Belum ada pesan yang masuk.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; KueBalok 2025</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>
</body>
</html>