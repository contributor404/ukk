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
// TELAH DIUBAH: Menghapus JOIN ke tabel 'payments' dan kolom 'p.status'
$query = "SELECT b.*, r.room_number, r.floor, rt.name as room_type, rt.price_per_night, rt.capacity
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.id 
          JOIN room_types rt ON r.room_type_id = rt.id 
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

// Status booking dalam bahasa Indonesia. 
// Karena tidak ada tabel payments, status pembayaran akan disamakan dengan status booking.
$status_labels = [
    'pending' => 'Menunggu Konfirmasi',
    'confirmed' => 'Dikonfirmasi',
    'cancelled' => 'Dibatalkan',
    'checked_in' => 'Check-in',
    'checked_out' => 'Check-out',
    'paid' => 'Lunas', // Status 'paid' di tabel bookings akan digunakan sebagai status pembayaran
    'failed' => 'Gagal'
];

$booking_status = isset($status_labels[$booking['status']]) ? $status_labels[$booking['status']] : $booking['status'];

// TELAH DIUBAH: Menggunakan status dari tabel bookings sebagai Status Pembayaran
// Status pembayaran akan menjadi 'Lunas' jika status booking adalah 'paid', atau 'Menunggu Konfirmasi' (pending)
$payment_status_raw = ($booking['status'] == 'paid' || $booking['status'] == 'confirmed' || $booking['status'] == 'checked_in' || $booking['status'] == 'checked_out') ? 'paid' : ($booking['status'] == 'failed' ? 'failed' : 'pending');
$payment_status = isset($status_labels[$payment_status_raw]) ? $status_labels[$payment_status_raw] : $payment_status_raw;

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Booking - Hotel Booking</title>
    <style>
        /* CSS Utama */
        .print-area {
            display: none;
            flex-direction: column !important;
            align-items: center !important;
            text-align: center !important;
        }

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

        /* --- CSS Khusus untuk Print/Cetak --- */
        @media print {
            body * {
                visibility: hidden;
            }
            
            .print-area,
            .print-area * {
                visibility: visible;
            }

            .print-area {
                position: absolute;
                left: 0;
                display: flex;
                top: 0;
                width: 100%;
                /* Mengubah lebar menjadi 100% untuk tampilan cetak */
                max-width: 80mm;
                /* Batasi lebar seperti struk thermal, misal 80mm */
                margin: 0 auto;
                /* Posisikan di tengah halaman cetak */
                padding: 10px;
                /* Tambahkan padding agar tidak terlalu mepet */
                color: #000;
                /* Pastikan teks berwarna hitam */
                font-family: monospace, sans-serif;
                /* Gunakan font yang ringkas */
                font-size: 12px;
            }

            .booking-card,
            .booking-header,
            .booking-body,
            .container,
            .row,
            .col-md-8 {
                /* Sembunyikan semua elemen tampilan utama */
                display: none !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            .print-header {
                text-align: center;
                margin-bottom: 10px;
                border-bottom: 1px dashed #000;
                padding-bottom: 5px;
            }

            .print-detail p {
                margin: 2px 0;
            }

            .print-total {
                margin-top: 10px;
                border-top: 1px dashed #000;
                padding-top: 5px;
                text-align: right;
            }

            .print-total strong {
                font-size: 14px;
            }

            .d-grid {
                display: none;
            }

            .footer {
                display: none;
            }

            .status-badge {
                /* Pastikan badge status terlihat saat dicetak, tetapi tanpa warna latar belakang */
                background: none !important;
                color: #000 !important;
                padding: 0;
                border: none;
                font-weight: bold;
            }
        }
    </style>
    <script>
        function printStruk() {
            window.print();
        }
    </script>
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

                        <div class="info-box d-print-none">
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
                                        <span class="status-badge <?= $payment_status_raw == 'paid' ? 'status-confirmed' : ($payment_status_raw == 'failed' ? 'status-cancelled' : 'status-pending') ?>">
                                            <?= $payment_status ?>
                                        </span>
                                    </p>
                                    <p><strong>Total Pembayaran:</strong> <span class="price-tag">Rp <?= $formatted_price ?></span></p>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info d-print-none">
                            <i class="fas fa-info-circle me-2"></i> Pesanan Anda akan diproses setelah admin menyetujui pembayaran. Silakan cek status pemesanan secara berkala di halaman riwayat pemesanan.
                        </div>

                        <div class="d-grid gap-2 mb-3 d-print-none">
                            <button onclick="printStruk()" class="btn btn-success btn-lg"><i class="fas fa-print me-2"></i> Cetak Struk</button>
                        </div>

                        <div class="d-grid gap-2 d-print-none">
                            <a href="riwayat.php" class="btn btn-primary btn-lg">Lihat Riwayat Pemesanan</a>
                            <a href="index.php" class="btn btn-outline-secondary">Kembali ke Beranda</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="print-area text-center" style="flex-direction: column; align-items: center;">
        <div class="print-header">
            <h3>Hotel Booking</h3>
            <p>Jl. Hotel Indah No. 123</p>
            <p>(021) 1234-5678</p>
        </div>

        <div class="print-detail">
            <p>===================================</p>
            <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
            <p>Kode Booking: <strong><?= $booking['booking_code'] ?></strong></p>
            <p>-----------------------------------</p>
            <p>Kamar: <?= $booking['room_type'] ?> (No. <?= $booking['room_number'] ?>)</p>
            <p>Lantai: <?= $booking['floor'] ?></p>
            <p>Check-in: <?= date('d/m/Y', strtotime($booking['check_in'])) ?></p>
            <p>Check-out: <?= date('d/m/Y', strtotime($booking['check_out'])) ?></p>
            <p>Durasi: <?= $nights ?> malam</p>
            <p>-----------------------------------</p>
            <p>Harga/Malam: Rp <?= number_format($booking['price_per_night'], 0, ',', '.') ?></p>
            <p>Total Harga: Rp <?= $formatted_price ?></p>
            <p>Status Pembayaran: <?= $payment_status ?></p>
        </div>

        <div class="print-total">
            <p>===================================</p>
            <p>Total Bayar: <strong>Rp <?= $formatted_price ?></strong></p>
        </div>

        <div class="text-center mt-3">
            <p>Terima kasih atas pemesanan Anda!</p>
        </div>
    </div>

    <footer class="bg-dark text-white py-5 mt-5 d-print-none">
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