<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../koneksi.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $jabatan  = $_POST['jabatan'];
    $no_tlp   = trim($_POST['no_tlp']);
    $email    = trim($_POST['email']);

    if (empty($nama) || empty($username) || empty($password) || empty($jabatan) || empty($no_tlp) || empty($email)) {
        $_SESSION['notif'] = ['pesan' => 'Semua kolom wajib diisi.', 'tipe' => 'warning'];
    } elseif (strlen($password) < 8) {
        $_SESSION['notif'] = ['pesan' => 'Password minimal harus 8 karakter.', 'tipe' => 'warning'];
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['notif'] = ['pesan' => 'Format email tidak valid.', 'tipe' => 'warning'];
    } elseif (!ctype_digit($no_tlp) || strlen($no_tlp) < 10 || strlen($no_tlp) > 13) {
        $_SESSION['notif'] = ['pesan' => 'Format nomor telepon tidak valid.', 'tipe' => 'warning'];
    } else {
        $stmt_check = mysqli_prepare($koneksi, "SELECT id_karyawan FROM karyawan WHERE username = ?");
        mysqli_stmt_bind_param($stmt_check, "s", $username);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            $_SESSION['notif'] = ['pesan' => 'Gagal! Username "' . htmlspecialchars($username) . '" sudah digunakan.', 'tipe' => 'danger'];
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO karyawan (nama, username, password, jabatan, no_telepon, email) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($koneksi, $query);
            mysqli_stmt_bind_param($stmt, "ssssss", $nama, $username, $hashed_password, $jabatan, $no_tlp, $email);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['notif'] = ['pesan' => 'Karyawan baru berhasil ditambahkan.', 'tipe' => 'success'];
                header('Location: data_karyawan.php');
                exit;
            } else {
                $_SESSION['notif'] = ['pesan' => 'Gagal menambahkan karyawan. Terjadi error pada database.', 'tipe' => 'danger'];
            }
        }
    }

    $_SESSION['form_data'] = $_POST;
    header('Location: kar_input.php');
    exit;
}

$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title>Tambah Karyawan - Owner</title>
    <link href="../../../css/styles.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">
    <style>
        .eye-toggle-icon {
            position: absolute;
            top: 38px;
            right: 15px;
            cursor: pointer;
            color: #adb5bd;
        }

        .eye-toggle-icon:hover {
            color: #6c757d;
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include __DIR__ . "/../inc/navbar.php"; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include __DIR__ . "/../inc/sidebar.php"; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Tambah Data Karyawan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="data_karyawan.php">Data Karyawan</a></li>
                        <li class="breadcrumb-item active">Tambah Karyawan</li>
                    </ol>

                    <?php
                    if (isset($_SESSION['notif'])) {
                        $notif = $_SESSION['notif'];
                        echo '<div class="alert alert-' . htmlspecialchars($notif['tipe']) . ' alert-dismissible fade show" role="alert">';
                        echo htmlspecialchars($notif['pesan']);
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['notif']);
                    }
                    ?>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-user-plus me-1"></i>Formulir Karyawan Baru</div>
                        <div class="card-body">
                            <form action="" method="post">
                                <div class="mb-3">
                                    <label for="nama" class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="nama" id="nama" value="<?= htmlspecialchars($form_data['nama'] ?? '') ?>" required />
                                </div>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" id="username" value="<?= htmlspecialchars($form_data['username'] ?? '') ?>" required />
                                </div>
                                <div class="mb-3">
                                    <label for="jabatan" class="form-label">Jabatan</label>
                                    <select class="form-select" name="jabatan" id="jabatan" required>
                                        <option value="" disabled <?= !isset($form_data['jabatan']) ? 'selected' : '' ?>>-- Pilih Jabatan --</option>
                                        <option value="kasir" <?= (isset($form_data['jabatan']) && $form_data['jabatan'] == 'kasir') ? 'selected' : '' ?>>Kasir</option>
                                        <option value="admin" <?= (isset($form_data['jabatan']) && $form_data['jabatan'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                                        <option value="owner" <?= (isset($form_data['jabatan']) && $form_data['jabatan'] == 'owner') ? 'selected' : '' ?>>Owner</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="no_tlp" class="form-label">No. Telepon</label>
                                    <input type="tel" class="form-control" name="no_tlp" id="no_tlp" value="<?= htmlspecialchars($form_data['no_tlp'] ?? '') ?>" required pattern="08[0-9]{8,11}" maxlength="13" />
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" id="email" value="<?= htmlspecialchars($form_data['email'] ?? '') ?>" required />
                                </div>
                                <div class="mb-3 position-relative">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" required minlength="8" style="padding-right: 40px;">
                                    <i class="fa-solid fa-eye eye-toggle-icon" id="togglePassword"></i>
                                    <small class="form-text text-muted">Minimal 8 karakter.</small>
                                </div>

                                <button type="submit" class="btn btn-primary">Simpan Karyawan</button>
                                <a href="data_karyawan.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto"></footer>
        </div>
    </div>

    <!-- JS toggle password -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const toggle = document.getElementById("togglePassword");
            const password = document.getElementById("password");

            toggle.addEventListener("click", function() {
                const isPassword = password.type === "password";
                password.type = isPassword ? "text" : "password";

                this.classList.toggle("fa-eye");
                this.classList.toggle("fa-eye-slash");
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../js/scripts.js"></script>
</body>

</html>