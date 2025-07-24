<?php
// 1. Mulai sesi terlebih dahulu
session_start();

// 2. Kosongkan semua variabel sesi (Best Practice)
// Ini menghapus semua data dari array $_SESSION
$_SESSION = array();

// 3. Hancurkan sesi
// Ini akan menghapus file sesi di server
session_destroy();

// 4. Arahkan pengguna kembali ke halaman login
// Pastikan tidak ada spasi setelah "Location:"
header('Location: login.php');

// 5. Hentikan eksekusi script
// Penting untuk memastikan tidak ada kode lain yang berjalan setelah redirect.
exit;
?>