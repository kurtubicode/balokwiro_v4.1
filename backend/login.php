<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['user'])) {
    $jabatan = strtolower($_SESSION['user']['jabatan']);
    switch ($jabatan) {
        case 'owner':
            header("Location: users/superadmin/index.php");
            break;
        case 'admin':
            header("Location: users/admin/index.php");
            break;
        case 'kasir':
            header("Location: users/kasir/index.php");
            break;
        default:
            header("Location: login.php");
            break;
    }
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['nama'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = mysqli_prepare($koneksi, "SELECT id_karyawan, nama, username, password, jabatan FROM karyawan WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        if ($data && password_verify($password, $data['password'])) {
            $_SESSION['user'] = [
                'id'       => $data['id_karyawan'],
                'nama'     => $data['nama'],
                'username' => $data['username'],
                'jabatan'  => $data['jabatan']
            ];

            $jabatan = strtolower($data['jabatan']);
            switch ($jabatan) {
                case 'owner':
                    header("Location: users/superadmin/index.php");
                    exit;
                case 'admin':
                    header("Location: users/admin/index.php");
                    exit;
                case 'kasir':
                    header("Location: users/kasir/index.php");
                    exit;
                default:
                    $error = "Jabatan tidak dikenali.";
            }
        } else {
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Semua field wajib diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kue Balok Mang Wiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Tambahan padding agar ikon tidak menumpuk di input */
        #password {
            padding-right: 40px;
        }

        .eye-toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #adb5bd;
            /* abu-abu soft */
            transition: color 0.2s ease;
        }

        .eye-toggle-icon:hover {
            color: #6c757d;
            /* lebih gelap sedikit pas hover */
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="text-center">
            <img class="login-logo" src="assets/img/logo-kuebalok.png" alt="Logo Kue Balok">
            <h1 class="login-title">Welcome Back!</h1>
            <p class="login-subtitle">Silakan login untuk melanjutkan</p>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="login.php" method="post">
            <div class="form-label-group">
                <input type="text" id="nama" name="nama" class="form-control" placeholder=" " required autofocus>
                <label for="nama">Username</label>
            </div>


            <div class="form-label-group" style="position: relative;">
                <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                <label for="password">Password</label>
                <i class="fa-solid fa-eye eye-toggle-icon" id="togglePassword"></i>
            </div>


            <button class="login-btn" type="submit">Sign In</button>
        </form>

        <div class="login-footer text-center">
            &copy; <?= date('Y') ?> Kue Balok Mang Wiro. All rights reserved.
        </div>
    </div>

    <script>
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("password");

        togglePassword.addEventListener("click", function() {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);

            // Ganti ikon Font Awesome
            this.classList.toggle("fa-eye");
            this.classList.toggle("fa-eye-slash");
        });
    </script>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>