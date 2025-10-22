<?php
session_start();
include 'koneksi.php';
include 'bootstrap.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Query untuk mengambil data riwayat booking user
$query = "SELECT 
            b.id,
            b.booking_code,
            b.check_in,
            b.check_out,
            b.total_price,
            b.status as booking_status,
            rt.name as room_type_name,
            (SELECT r.image FROM rooms r WHERE r.room_type_id = rt.id LIMIT 1) as room_image
          FROM bookings b
          JOIN rooms r ON b.room_id = r.id
          JOIN room_types rt ON r.room_type_id = rt.id
          JOIN payments p ON b.id = p.booking_id
          WHERE b.user_id = $user_id
          ORDER BY b.id DESC";

$result = $koneksi->query($query);

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pemesanan - Hotel Booking</title>
    <style>
        .page-header {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }

        .history-card {
            display: flex;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .history-card:hover {
            transform: translateY(-5px);
        }

        .history-card img {
            width: 200px;
            height: auto;
            object-fit: cover;
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .history-card .card-body {
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
        }
        
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
            color: white;
        }

        .status-pending { background-color: #ffc107; color: #000 !important; }
        .status-confirmed { background-color: #198754; }
        .status-paid { background-color: #0d6efd; }
        .status-cancelled { background-color: #dc3545; }
        .status-checked_in { background-color: #0dcaf0; }
        .status-checked_out { background-color: #6c757d; }
        .status-failed { background-color: #dc3545; }

    </style>
</head>
<body>
    <header class="page-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Riwayat Pemesanan</h1>
            <p class="lead">Lihat semua riwayat pemesanan kamar Anda</p>
        </div>
    </header>

    <div class="container mb-5">
        <?php if ($result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): 
                $booking_status = isset($status_labels[$row['booking_status']]) ? $status_labels[$row['booking_status']] : $row['booking_status'];
            ?>
                <div class="history-card">
                    <img src="<?= !empty($row['room_image']) ? $row['room_image'] : 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg' ?>" alt="<?= htmlspecialchars($row['room_type_name']) ?>">
                    <div class="card-body">
                        <div>
                            <div class="d-flex justify-content-between">
                                <h5 class="card-title"><?= htmlspecialchars($row['room_type_name']) ?></h5>
                                <span class="fw-bold text-primary">Rp <?= number_format($row['total_price'], 0, ',', '.') ?></span>
                            </div>
                            <p class="card-text mb-1"><strong>Kode Booking:</strong> <?= htmlspecialchars($row['booking_code']) ?></p>
                            <p class="card-text">
                                <strong>Check-in:</strong> <?= date('d M Y', strtotime($row['check_in'])) ?> | 
                                <strong>Check-out:</strong> <?= date('d M Y', strtotime($row['check_out'])) ?>
                            </p>
                            <div class="d-flex gap-2">
                                <span class="status-badge status-<?= $row['booking_status'] ?>"><?= $booking_status ?></span>
                            </div>
                        </div>
                        <a href="kamar_kode.php?kode=<?= $row['booking_code'] ?>" class="btn btn-outline-primary align-self-end mt-3">Lihat Detail</a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center">
                <p>Anda belum memiliki riwayat pemesanan.</p>
                <a href="kamar_semua.php" class="btn btn-primary">Pesan Kamar Sekarang</a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white py-5">
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
                <p class="mb-0">&copy; 2030 Hotel Booking. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>