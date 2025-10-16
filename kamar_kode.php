<?php
session_start();
include 'koneksi.php';
include 'bootstrap.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Cek apakah ada parameter kode booking
if (!isset($_GET['kode']) || empty($_GET['kode'])) {
    header('Location: riwayat.php');
    exit;
}

$booking_code = $koneksi->real_escape_string($_GET["kode"]);
$user_id = $_SESSION['user_id'];

// Query untuk mengambil data booking
$query = "SELECT b.*, r.room_number, r.floor, rt.name as room_type, rt.price_per_night, rt.capacity, p.status as payment_status 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          JOIN room_types rt ON r.room_type_id = rt.id 
          JOIN payments p ON p.booking_id = b.id 
          WHERE b.booking_code = '$booking_code' AND b.user_id = $user_id";
$result = $koneksi->query($query);

// Cek apakah booking ditemukan
if ($result->num_rows == 0) {
    header('Location: riwayat.php');
    exit;
}

$booking = $result->fetch_assoc();

// Format harga dan tanggal
$formatted_price = number_format($booking['total_price'], 0, ',', '.');
$check_in_date = date('d F Y', strtotime($booking['check_in']));
$check_out_date = date('d F Y', strtotime($booking['check_out']));

// Hitung jumlah malam
$check_in = new DateTime($booking['check_in']);
$check_out = new DateTime($booking['check_out']);
$interval = $check_in->diff($check_out);
$nights = $interval->days;

// Status booking dan pembayaran dalam bahasa Indonesia
$status_labels = [
    'pending' => 'Menunggu Konfirmasi',
    'confirmed' => 'Dikonfirmasi',
    'cancelled' => 'Dibatalkan',
    'checked_in' => 'Check-in',
    'checked_out' => 'Check-out',
    'paid' => 'Lunas',
    'failed' => 'Gagal'
];

$booking_status = isset($status_labels[$booking['status']]) ? $status_labels[$booking['status']] : $booking['status'];
$payment_status = isset($status_labels[$booking['payment_status']]) ? $status_labels[$booking['payment_status']] : $booking['payment_status'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Booking - Hotel Booking</title>
    <style>
        .booking-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        
        .booking-header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .booking-body {
            padding: 30px;
        }
        
        .booking-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .booking-code {
            font-size: 1.8rem;
            font-weight: bold;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border: 2px dashed #0d6efd;
            border-radius: 10px;
            background-color: #f0f7ff;
        }
        
        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
        
        .status-badge {
            font-size: 1rem;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        
        .status-pending {
            background-color: #ffc107;
            color: #212529;
        }
        
        .status-confirmed {
            background-color: #198754;
            color: white;
        }
        
        .status-cancelled {
            background-color: #dc3545;
            color: white;
        }
        
        .info-box {
            border-left: 4px solid #0d6efd;
            background-color: #f0f7ff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="booking-card">
                    <div class="booking-header">
                        <h1>Detail Pemesanan</h1>
                    </div>
                    
                    <div class="booking-body">
                        <div class="booking-code">
                            Kode Booking: <?= $booking['booking_code'] ?>
                        </div>
                        
                        <div class="info-box">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Status Pemesanan:</strong> 
                            <span class="status-badge <?= $booking['status'] == 'confirmed' ? 'status-confirmed' : ($booking['status'] == 'cancelled' ? 'status-cancelled' : 'status-pending') ?>">
                                <?= $booking_status ?>
                            </span>
                            <p class="mt-2 mb-0">Pemesanan Anda sedang menunggu konfirmasi dari admin. Silakan cek status pemesanan secara berkala di halaman riwayat pemesanan.</p>
                        </div>
                        
                        <div class="booking-info">
                            <h3>Informasi Kamar</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nomor Kamar:</strong> <?= $booking['room_number'] ?></p>
                                    <p><strong>Tipe Kamar:</strong> <?= $booking['room_type'] ?></p>
                                    <p><strong>Lantai:</strong> <?= $booking['floor'] ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Kapasitas:</strong> <?= $booking['capacity'] ?> orang</p>
                                    <p><strong>Harga per Malam:</strong> Rp <?= number_format($booking['price_per_night'], 0, ',', '.') ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="booking-info">
                            <h3>Detail Pemesanan</h3>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Check-in:</strong> <?= $check_in_date ?></p>
                                    <p><strong>Check-out:</strong> <?= $check_out_date ?></p>
                                    <p><strong>Durasi:</strong> <?= $nights ?> malam</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status Pembayaran:</strong> 
                                        <span class="status-badge <?= $booking['payment_status'] == 'paid' ? 'status-confirmed' : ($booking['payment_status'] == 'failed' ? 'status-cancelled' : 'status-pending') ?>">
                                            <?= $payment_status ?>
                                        </span>
                                    </p>
                                    <p><strong>Total Pembayaran:</strong> <span class="price-tag">Rp <?= $formatted_price ?></span></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Pesanan Anda akan diproses setelah admin menyetujui pembayaran. Silakan cek status pemesanan secara berkala di halaman riwayat pemesanan.
                        </div>
                        
                        <div class="d-grid gap-2">
                            <a href="riwayat.php" class="btn btn-primary btn-lg">Lihat Riwayat Pemesanan</a>
                            <a href="index.php" class="btn btn-outline-secondary">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Hotel Booking</h5>
                    <p>Temukan pengalaman menginap terbaik dengan harga terjangkau dan fasilitas lengkap.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Kontak</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i> Jl. Hotel Indah No. 123, Kota</p>
                    <p><i class="fas fa-phone me-2"></i> (021) 1234-5678</p>
                    <p><i class="fas fa-envelope me-2"></i> info@hotelbooking.com</p>
                </div>
                <div class="col-md-4">
                    <h5>Ikuti Kami</h5>
                    <div class="d-flex gap-3 fs-4">
                        <a href="#" class="text-white"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; 2023 Hotel Booking. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>