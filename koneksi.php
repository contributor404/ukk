<?php
// Konfigurasi database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'hotel_booking';

// Membuat koneksi
$koneksi = mysqli_connect($host, $username, $password, $database);

// Cek koneksi
if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>