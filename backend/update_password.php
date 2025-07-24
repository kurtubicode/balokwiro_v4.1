<?php
// // update_passwords.php

// // JALANKAN FILE INI SEKALI SAJA!
// // BACKUP DATABASE ANDA SEBELUM MENJALANKAN INI!

// include 'koneksi.php';

// // Ambil semua karyawan yang passwordnya belum di-hash
// // (Asumsi password yang belum di-hash panjangnya < 60)
// $query = "SELECT id_karyawan, password FROM karyawan WHERE CHAR_LENGTH(password) < 60";
// $result = mysqli_query($koneksi, $query);

// if ($result) {
//     while ($row = mysqli_fetch_assoc($result)) {
//         $id = $row['id_karyawan'];
//         $plain_password = $row['password'];

//         // Hash password
//         $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

//         // Update password di database dengan prepared statement
//         $update_stmt = mysqli_prepare($koneksi, "UPDATE karyawan SET password = ? WHERE id_karyawan = ?");
//         mysqli_stmt_bind_param($update_stmt, "si", $hashed_password, $id);
        
//         if(mysqli_stmt_execute($update_stmt)) {
//             echo "Password untuk ID Karyawan: " . $id . " berhasil di-hash.<br>";
//         } else {
//             echo "Gagal update password untuk ID Karyawan: " . $id . "<br>";
//         }
//     }
//     echo "<h2>Proses selesai. Hapus atau rename file ini sekarang.</h2>";
// } else {
//     echo "Gagal mengambil data karyawan.";
// }

// mysqli_close($koneksi);
// ?>