<?php
// 1. WAJIB: Mulai sesi di baris paling atas
session_start();

include('../../../koneksi.php');

// 2. KEAMANAN: Cek hak akses. Hanya owner yang boleh mengakses.
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../../login.php');
    exit;
}

// 3. Mengambil data karyawan untuk ditampilkan di tabel, diurutkan berdasarkan nama
$query_mysql = mysqli_query($koneksi, "SELECT * FROM karyawan ORDER BY nama ASC");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Data Karyawan - Owner</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png"> 

</head>

<body class="sb-nav-fixed">
    <?php include "../inc/navbar.php"; // Asumsi path navbar untuk superadmin 
    ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include "../inc/sidebar.php"; // Asumsi path sidebar untuk superadmin 
            ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Data Karyawan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Data Karyawan</li>
                    </ol>

                    <?php
                    // 4. Menggunakan sistem notifikasi sesi yang konsisten
                    if (isset($_SESSION['notif'])) {
                        $notif = $_SESSION['notif'];
                        echo '<div class="alert alert-' . htmlspecialchars($notif['tipe']) . ' alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($notif['pesan']);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['notif']);
                    }
                    ?>

                    <div class="mb-3">
                        <a href="kar_input.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Tambah Karyawan</a>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-users me-1"></i>
                            Daftar Karyawan Terdaftar
                        </div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Jabatan</th>
                                        <th>No. Telepon</th>
                                        <th>Email</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($data = mysqli_fetch_assoc($query_mysql)) { ?>
                                        <tr>
                                            <td><?= htmlspecialchars($data['id_karyawan']); ?></td>
                                            <td><?= htmlspecialchars($data['nama']); ?></td>
                                            <td><?= htmlspecialchars($data['username']); ?></td>
                                            <td><?= htmlspecialchars(ucfirst($data['jabatan'])); ?></td>
                                            <td><?= htmlspecialchars($data['no_telepon']); ?></td>
                                            <td><?= htmlspecialchars($data['email']); ?></td>
                                            <td>
                                                <div class="d-flex gap-2">
                                                    <a class="btn btn-warning btn-sm" href="kar_edit.php?id_karyawan=<?= $data['id_karyawan']; ?>" title="Edit"><i class="fas fa-edit"></i></a>

                                                    <?php
                                                    // Tambahkan kondisi: Tampilkan tombol Hapus HANYA JIKA jabatannya BUKAN 'owner'
                                                    if ($data['jabatan'] !== 'owner') {
                                                    ?>
                                                        <a class="btn btn-danger btn-sm" href="kar_hapus.php?id_karyawan=<?= $data['id_karyawan']; ?>" onclick="return confirm('Anda yakin ingin menghapus karyawan ini?')" title="Hapus"><i class="fas fa-trash"></i></a>
                                                    <?php
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/datatables-simple-demo.js"></script>
</body>

</html>