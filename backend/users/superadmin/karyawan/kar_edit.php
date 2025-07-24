<?php
session_start();
include('../../../koneksi.php');

// Cek akses
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// Validasi ID
if (!isset($_GET['id_karyawan']) || !is_numeric($_GET['id_karyawan'])) {
    $_SESSION['notif'] = ['pesan' => 'Aksi tidak valid, ID karyawan tidak ditemukan.', 'tipe' => 'warning'];
    header('Location: data_karyawan.php');
    exit;
}
$id_karyawan_edit = $_GET['id_karyawan'];

// Ambil data
$stmt_get = mysqli_prepare($koneksi, "SELECT * FROM karyawan WHERE id_karyawan = ?");
mysqli_stmt_bind_param($stmt_get, "i", $id_karyawan_edit);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$data = mysqli_fetch_assoc($result_get);

// Jika tidak ditemukan
if (!$data) {
    $_SESSION['notif'] = ['pesan' => 'Data karyawan tidak ditemukan.', 'tipe' => 'warning'];
    header('Location: data_karyawan.php');
    exit;
}

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan_post = $_POST['id_karyawan'];
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $jabatan = $_POST['jabatan'];
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $no_telepon_input = trim($_POST['no_tlp']);

    if (!empty($no_telepon_input)) {
        if (!ctype_digit($no_telepon_input) || strlen($no_telepon_input) < 10 || strlen($no_telepon_input) > 13) {
            $_SESSION['notif'] = ['pesan' => 'Update Gagal! Format nomor telepon tidak valid.', 'tipe' => 'danger'];
            header("Location: kar_edit.php?id_karyawan=" . $id_karyawan_post);
            exit;
        }
        $no_telepon_final = $no_telepon_input;
    } else {
        $no_telepon_final = $data['no_telepon'];
    }

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query_update = "UPDATE karyawan SET nama=?, username=?, jabatan=?, no_telepon=?, email=?, password=? WHERE id_karyawan=?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "ssssssi", $nama, $username, $jabatan, $no_telepon_final, $email, $hashed_password, $id_karyawan_post);
    } else {
        $query_update = "UPDATE karyawan SET nama=?, username=?, jabatan=?, no_telepon=?, email=? WHERE id_karyawan=?";
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sssssi", $nama, $username, $jabatan, $no_telepon_final, $email, $id_karyawan_post);
    }

    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['notif'] = ['pesan' => 'Data karyawan berhasil diperbarui.', 'tipe' => 'success'];
    } else {
        $_SESSION['notif'] = ['pesan' => 'Gagal memperbarui data. Error: ' . mysqli_error($koneksi), 'tipe' => 'danger'];
    }
    header("Location: data_karyawan.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Edit Karyawan - Owner</title>
    <link href="../../../css/styles.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">
    <style>
        .eye-toggle-icon {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #adb5bd;
            font-size: 1rem;
        }

        .eye-toggle-icon:hover {
            color: #495057;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include "../inc/navbar.php"; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include "../inc/sidebar.php"; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Edit Data Karyawan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="data_karyawan.php">Data Karyawan</a></li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-user-edit me-1"></i>Formulir Edit: <?= htmlspecialchars($data['nama']) ?></div>
                        <div class="card-body">
                            <form action="kar_edit.php?id_karyawan=<?= htmlspecialchars($id_karyawan_edit) ?>" method="post">
                                <input type="hidden" name="id_karyawan" value="<?= htmlspecialchars($data['id_karyawan']) ?>">

                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="nama" id="nama" value="<?= htmlspecialchars($data['nama']) ?>" required />
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($data['username']) ?>" required />
                                </div>
                                <div class="mb-3">
                                    <label for="jabatan" class="form-label">Jabatan</label>
                                    <select class="form-select" name="jabatan" id="jabatan" required>
                                        <option value="kasir" <?= ($data['jabatan'] === 'kasir') ? 'selected' : '' ?>>Kasir</option>
                                        <option value="admin" <?= ($data['jabatan'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                        <option value="owner" <?= ($data['jabatan'] === 'owner') ? 'selected' : '' ?>>Owner</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="no_tlp" class="form-label">No. Telepon</label>
                                    <input type="tel" class="form-control" name="no_tlp" id="no_tlp" value="<?= htmlspecialchars($data['no_telepon']) ?>" required pattern="08[0-9]{8,11}" maxlength="13"
                                        title="Nomor telepon harus dimulai dengan 08 dan terdiri dari 10 hingga 13 digit." />
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($data['email']) ?>" required />
                                </div>
                                <hr>
                               

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password Baru</label>
                                    <div class="position-relative">
                                        <input type="password" class="form-control pe-5" name="password" id="password"
                                    placeholder="Kosongkan jika tidak ingin mengubah password"
                                    minlength="8"
                                    pattern=".{8,}"
                                    title="Password minimal 8 karakter" />
                                        <span class="eye-toggle-icon">
                                            <i class="fas fa-eye" id="togglePassword"></i>
                                        </span>
                                    </div>
                                    <small class="form-text text-muted">Hanya isi kolom ini jika Anda ingin mengubah password karyawan.</small>
                                </div>
                                

                                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                <a href="data_karyawan.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto"></footer>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById("togglePassword");
            const passwordInput = document.getElementById("password");

            togglePassword.addEventListener("click", function() {
                const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
                passwordInput.setAttribute("type", type);
                togglePassword.classList.toggle("fa-eye");
                togglePassword.classList.toggle("fa-eye-slash");
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../js/scripts.js"></script>
</body>

</html>