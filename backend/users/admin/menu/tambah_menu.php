<?php
session_start();
include('../../../koneksi.php');

// Otentikasi & Otorisasi
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['jabatan'], ['admin', 'owner'])) {
    header('Location: ../../../login.php');
    exit;
}

// --- FUNGSI UNTUK MEMBUAT ID PRODUK OTOMATIS (Tidak berubah, sudah baik) ---
function generateNextId($prefix, $koneksi)
{
    $query = "SELECT MAX(id_produk) AS last_id FROM produk WHERE id_produk LIKE ?";
    $stmt = mysqli_prepare($koneksi, $query);
    $search_param = $prefix . '%';
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $last_id = $row['last_id'];
    if ($last_id) {
        $number = (int) substr($last_id, strlen($prefix)) + 1;
    } else {
        $number = 1;
    }
    return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
}

// --- LOGIKA UNTUK PRE-CALCULATE NEXT ID (Tidak berubah) ---
$next_ids = [
    'KB' => generateNextId('KB', $koneksi),
    'KS' => generateNextId('KS', $koneksi),
    'OT' => generateNextId('OT', $koneksi),
    'DK' => generateNextId('DK', $koneksi)
];

// --- LOGIKA PROSES FORM SAAT DISUBMIT (DENGAN PENYESUAIAN LOG_STOK) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {

    // Ambil semua data dari form
    $id_produk = $_POST['id_produk'];
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $kategori_produk = $_POST['kategori']; // makanan atau minuman
    $jenis_menu = $_POST['jenis_menu']; // KB, KS, OT, DK
    $stok_awal = (int)($_POST['stok_awal'] ?? 0); // Ambil nilai stok awal

    // === PERBAIKAN 1: TENTUKAN ID KATEGORI STOK ===
    $id_kategori_stok = null; // Default untuk produk individu
    if ($jenis_menu === 'KB') {
        $id_kategori_stok = 'KAT-KB'; // Sesuaikan dengan ID di tabel kategori_stok Anda
    } elseif ($jenis_menu === 'KS') {
        $id_kategori_stok = 'KAT-KS'; // Sesuaikan dengan ID di tabel kategori_stok Anda
    }

    // Proses upload gambar (tidak berubah)
    $gambar = $_FILES['gambar']['name'] ?? 'default.jpg';
    $tmp = $_FILES['gambar']['tmp_name'];
    $path = "../../../assets/img/produk/" . $gambar;

    // Memulai transaksi database
    mysqli_begin_transaction($koneksi);
    try {
        // Pindahkan file gambar terlebih dahulu
        if (!empty($tmp)) {
            if (!move_uploaded_file($tmp, $path)) {
                throw new Exception("Gagal mengupload gambar.");
            }
        }

        // === PERBAIKAN 2: Sertakan 'id_kategori_stok' saat INSERT ===
        $query = "INSERT INTO produk (id_produk, nama_produk, harga, kategori, id_kategori_stok, poto_produk) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        // Tipe data menjadi "ssdsss"
        mysqli_stmt_bind_param($stmt, "ssdsss", $id_produk, $nama, $harga, $kategori_produk, $id_kategori_stok, $gambar);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menambahkan produk ke database. Error: " . mysqli_error($koneksi));
        }

        // === PERBAIKAN 3: BUAT CATATAN STOK AWAL DI log_stok ===
        // Stok awal hanya dibuat untuk produk dengan stok individu. 
        // Produk kategori (Kue Balok/Ketan Susu) stoknya diinput terpisah sebagai adonan.
        if ($id_kategori_stok === null && $stok_awal > 0) {
            $stmt_log = mysqli_prepare($koneksi, "INSERT INTO log_stok (id_produk, jumlah_perubahan, jenis_aksi, keterangan) VALUES (?, ?, 'stok_awal', ?)");
            $keterangan_log = "Stok awal untuk produk baru: " . $nama;
            mysqli_stmt_bind_param($stmt_log, "sis", $id_produk, $stok_awal, $keterangan_log);
            mysqli_stmt_execute($stmt_log);
        }

        // Jika semua berhasil, simpan perubahan
        mysqli_commit($koneksi);
        $_SESSION['notif'] = ['pesan' => 'Menu baru berhasil ditambahkan.', 'tipe' => 'success'];
        header("Location: data_menu.php");
        exit;
    } catch (Exception $e) {
        // Jika ada error, batalkan semua
        mysqli_rollback($koneksi);
        $_SESSION['notif'] = ['pesan' => $e->getMessage(), 'tipe' => 'danger'];
        header("Location: tambah_menu.php");
        exit;
    }
}

// Ambil data produk untuk ditampilkan di tabel (opsional, bisa dihapus jika tidak ada tabel di halaman ini)
$result = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY kategori, nama_produk");

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Tambah Menu - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="../../../css/styles.css" rel="stylesheet" />
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                    <h1 class="mt-4">Tambah Menu Baru</h1>
                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Formulir Tambah Menu</div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row gx-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="jenis_menu" class="form-label">Jenis Menu</label>
                                            <select class="form-select" id="jenis_menu" name="jenis_menu" required>
                                                <option value="" selected disabled>-- Pilih Jenis Menu --</option>
                                                <option value="KB">Kue Balok</option>
                                                <option value="KS">Ketan Susu</option>
                                                <option value="OT">Makanan Lain</option>
                                                <option value="DK">Minuman</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="id_produk" class="form-label">ID Menu (Otomatis)</label>
                                            <input type="text" class="form-control" name="id_produk" id="id_produk" readonly required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama_produk" class="form-label">Nama Menu</label>
                                            <input type="text" class="form-control" name="nama_produk" id="nama_produk" required>
                                        </div>
                                        <div class="mb-3" id="stok-awal-container" style="display: none;">
                                            <label for="stok_awal" class="form-label">Stok Awal</label>
                                            <input type="number" class="form-control" name="stok_awal" id="stok_awal" min="0" value="0">
                                            <small class="form-text text-muted">Hanya untuk Makanan Lain & Minuman. Stok Kue Balok/Ketan Susu diatur di Manajemen Stok.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="harga" class="form-label">Harga</label>
                                            <input type="number" class="form-control" name="harga" id="harga" min="0" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="kategori" class="form-label">Kategori</label>
                                            <select class="form-select" name="kategori" id="kategori" required>
                                                <option value="makanan">Makanan</option>
                                                <option value="minuman">Minuman</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="gambar" class="form-label">Upload Gambar</label>
                                            <input class="form-control" type="file" name="gambar" id="gambar" accept="image/jpeg, image/png, image/jpg">
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Menu
                                </button>
                                <a href="data_menu.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../../js/scripts.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nextIds = <?php echo json_encode($next_ids); ?>;
            const jenisMenuSelect = document.getElementById('jenis_menu');
            const idProdukInput = document.getElementById('id_produk');
            const kategoriSelect = document.getElementById('kategori');
            const stokAwalContainer = document.getElementById('stok-awal-container');

            jenisMenuSelect.addEventListener('change', function() {
                const selectedPrefix = this.value;
                if (selectedPrefix) {
                    idProdukInput.value = nextIds[selectedPrefix];

                    // Tampilkan/Sembunyikan input stok awal
                    if (selectedPrefix === 'OT' || selectedPrefix === 'DK') {
                        stokAwalContainer.style.display = 'block';
                    } else {
                        stokAwalContainer.style.display = 'none';
                    }

                    // Otomatis set kategori
                    if (selectedPrefix === 'DK') {
                        kategoriSelect.value = 'minuman';
                    } else {
                        kategoriSelect.value = 'makanan';
                    }
                } else {
                    idProdukInput.value = '';
                    stokAwalContainer.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>