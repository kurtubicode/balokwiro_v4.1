<?php
// session_start();
date_default_timezone_set('Asia/Jakarta');
$host = "localhost";
$username = "root";
$password = "";
$database = "123";


$koneksi = mysqli_connect($host, $username, $password, $database);
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>

