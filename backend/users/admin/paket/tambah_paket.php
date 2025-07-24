<?php
// 1. Mulai sesi
session_start();

// 2. Sesuaikan dengan koneksi Anda
include('../../../koneksi.php');

// 3. KEAMANAN: Cek hak akses. Hanya admin dan owner yang boleh mengakses.
if (!isset($_SESSION['user']) || ($_SESSION['user']['jabatan'] !== 'admin' && $_SESSION['user']['jabatan'] !== 'owner')) {
    header('Location: ../../../login.php');
    exit;
}

// 4. Ambil semua produk yang statusnya 'aktif' untuk ditampilkan di dropdown pilihan
$query_produk = "SELECT id_produk, nama_produk FROM produk WHERE status_produk = 'aktif' ORDER BY nama_produk ASC";
$result_produk = mysqli_query($koneksi, $query_produk);

$pageTitle = "Tambah Paket Baru";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?= htmlspecialchars($pageTitle) ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="../../../css/styles.css" rel="stylesheet" />
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        .btn-hapus-item { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
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
                    <h1 class="mt-4"><?= htmlspecialchars($pageTitle) ?></h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="data_paket.php">Data Paket</a></li>
                        <li class="breadcrumb-item active">Tambah Paket</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header">
                            <i class="fas fa-plus-circle me-1"></i>
                            Formulir Tambah Paket Baru
                        </div>
                        <div class="card-body">
                            <form id="form-tambah-paket" action="proses_tambah_paket.php" method="POST" enctype="multipart/form-data">

                                <div class="mb-3">
                                    <label for="nama_paket" class="form-label">Nama Paket</label>
                                    <input type="text" class="form-control" id="nama_paket" name="nama_paket" required>
                                </div>
                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi (Opsional)</label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="harga_paket" class="form-label">Harga Jual Paket</label>
                                        <input type="number" class="form-control" id="harga_paket" name="harga_paket" placeholder="Contoh: 25000" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="poto_paket" class="form-label">Foto Paket</label>
                                        <input class="form-control" type="file" id="poto_paket" name="poto_paket">
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5>Rincian Isi Paket</h5>
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-auto">
                                        <label for="produk-select" class="col-form-label">Pilih Produk:</label>
                                    </div>
                                    <div class="col">
                                        <select class="form-select" id="produk-select">
                                            <option selected disabled>-- Pilih item untuk ditambahkan --</option>
                                            <?php while ($row_produk = mysqli_fetch_assoc($result_produk)) : ?>
                                                <option value="<?= htmlspecialchars($row_produk['id_produk']) ?>">
                                                    <?= htmlspecialchars($row_produk['nama_produk']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-auto">
                                        <button type="button" id="btn-tambah-item" class="btn btn-success">Tambah Item</button>
                                    </div>
                                </div>

                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th style="width: 120px;">Jumlah</th>
                                            <th style="width: 80px;" class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="item-list-body">
                                        </tbody>
                                </table>
                                
                                <input type="hidden" name="items_json" id="items_json">

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary">Simpan Paket</button>
                                    <a href="data_paket.php" class="btn btn-secondary">Batal</a>
                                </div>
                            </form>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btnTambahItem = document.getElementById('btn-tambah-item');
            const produkSelect = document.getElementById('produk-select');
            const itemListBody = document.getElementById('item-list-body');
            const form = document.getElementById('form-tambah-paket');
            const itemsJsonInput = document.getElementById('items_json');

            // Fungsi untuk menambahkan item ke tabel
            btnTambahItem.addEventListener('click', function() {
                const selectedOption = produkSelect.options[produkSelect.selectedIndex];
                if (!selectedOption || selectedOption.disabled) {
                    alert('Silakan pilih produk terlebih dahulu.');
                    return;
                }

                const produkId = selectedOption.value;
                const produkNama = selectedOption.text;

                // Cek agar tidak ada produk duplikat di dalam paket
                if (document.querySelector(`tr[data-id="${produkId}"]`)) {
                    alert('Produk ini sudah ada di dalam paket.');
                    return;
                }

                // Membuat baris baru untuk tabel item
                const newRow = document.createElement('tr');
                newRow.setAttribute('data-id', produkId);
                newRow.innerHTML = `
                    <td>${produkNama}</td>
                    <td>
                        <input type="number" class="form-control form-control-sm item-jumlah" value="1" min="1" required>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm btn-hapus-item">Hapus</button>
                    </td>
                `;
                itemListBody.appendChild(newRow);
            });

            // Fungsi untuk menghapus item dari tabel
            itemListBody.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('btn-hapus-item')) {
                    e.target.closest('tr').remove();
                }
            });

            // Fungsi yang dijalankan saat form di-submit
            form.addEventListener('submit', function(e) {
                const items = [];
                const itemRows = itemListBody.querySelectorAll('tr');
                
                if (itemRows.length === 0) {
                    e.preventDefault(); // Hentikan submit form
                    alert('Paket harus berisi minimal satu item produk.');
                    return;
                }

                itemRows.forEach(row => {
                    const id = row.getAttribute('data-id');
                    const jumlah = row.querySelector('.item-jumlah').value;
                    items.push({ id_produk: id, jumlah: parseInt(jumlah) });
                });

                // Masukkan data item (dalam format JSON) ke input tersembunyi
                itemsJsonInput.value = JSON.stringify(items);
            });
        });
    </script>
</body>
</html>