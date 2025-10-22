<?php
session_start();

include 'koneksi.php';
include 'bootstrap.php';

// Cek apakah ada parameter id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];
$user_id = $_SESSION["user_id"];

// Query untuk mengambil data tipe kamar berdasarkan id
$query = "SELECT
    rt.*,
    (
        SELECT r.image
        FROM rooms r
        WHERE r.room_type_id = rt.id
        LIMIT 1
    ) AS room_image,
    (
        SELECT COUNT(*)
        FROM rooms r
        WHERE r.room_type_id = rt.id AND r.status = 'available'
    ) AS available_rooms
FROM
    room_types rt
WHERE
    rt.id = $id
    AND NOT EXISTS (
        SELECT *
        FROM bookings b
          WHERE b.user_id = $user_id
    );
";
$result = $koneksi->query($query);

// Cek apakah data ditemukan
if ($result->num_rows == 0) {
    header('Location: index.php');
    exit;
}

$room = $result->fetch_assoc();

// Format harga
$formatted_price = number_format($room['price_per_night'], 0, ',', '.');

// Konversi fasilitas dari format teks ke array
$facilities = explode(",", $room['facilities']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $room['name'] ?> - Hotel Booking</title>
    <style>
        .room-image {
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }

        .facility-item {
            margin-bottom: 15px;
        }

        .facility-icon {
            font-size: 1.5rem;
            color: #0d6efd;
            margin-right: 10px;
        }

        .price-tag {
            font-size: 2rem;
            font-weight: bold;
            color: #0d6efd;
        }

        .availability-badge {
            font-size: 1.2rem;
        }

        .booking-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
    </style>
</head>

<body>
    <!-- Room Detail Section -->
    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="kamar_semua.php">Kamar</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?= $room['name'] ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <!-- Room Images -->
            <div class="col-lg-8 mb-4">
                <img src="<?= !empty($room['room_image']) ? $room['room_image'] : 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg' ?>"
                    class="img-fluid room-image w-100 mb-4" alt="<?= $room['name'] ?>">

                <div class="row">
                    <div class="col-4">
                        <img src="https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg" class="img-fluid rounded" alt="Bathroom">
                    </div>
                    <div class="col-4">
                        <img src="https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg" class="img-fluid rounded" alt="Bed">
                    </div>
                    <div class="col-4">
                        <img src="https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg" class="img-fluid rounded" alt="View">
                    </div>
                </div>
            </div>

            <!-- Room Info -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="card-title mb-4"><?= $room['name'] ?></h1>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <span class="price-tag">Rp <?= $formatted_price ?></span>
                            <span class="text-muted">per malam</span>
                        </div>

                        <?php if ($room['available_rooms'] > 0): ?>
                            <div class="mb-4">
                                <span class="badge bg-success availability-badge">Tersedia <?= $room['available_rooms'] ?> kamar</span>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <span class="badge bg-danger availability-badge">Tidak tersedia</span>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h5>Kapasitas:</h5>
                            <p><i class="fas fa-user me-2"></i> <?= $room['capacity'] ?> orang</p>
                        </div>

                        <div class="mb-4">
                            <h5>Fasilitas:</h5>
                            <ul class="list-unstyled">
                                <?php foreach ($facilities as $facility): ?>
                                    <?php
                                    $facility = trim($facility);
                                    $icon = 'fa-check';

                                    // Tentukan ikon berdasarkan fasilitas
                                    if (stripos($facility, 'wifi') !== false) {
                                        $icon = 'fa-wifi';
                                    } elseif (stripos($facility, 'tv') !== false || stripos($facility, 'televisi') !== false) {
                                        $icon = 'fa-tv';
                                    } elseif (stripos($facility, 'ac') !== false || stripos($facility, 'air') !== false) {
                                        $icon = 'fa-snowflake';
                                    } elseif (stripos($facility, 'sarapan') !== false || stripos($facility, 'breakfast') !== false) {
                                        $icon = 'fa-utensils';
                                    } elseif (stripos($facility, 'parkir') !== false || stripos($facility, 'parking') !== false) {
                                        $icon = 'fa-car';
                                    }
                                    ?>
                                    <li class="facility-item">
                                        <i class="fas <?= $icon ?> facility-icon"></i> <?= $facility ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <?php if ($room['available_rooms'] > 0): ?>
                            <?php if (isset($_SESSION["user_id"])): ?>
                                <a href="kamar_pesan.php?id=<?= $room['id'] ?>" class="btn btn-primary btn-lg w-100 mb-3">Pesan Sekarang</a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-lg w-100 mb-3">Pesan Sekarang</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-lg w-100 mb-3" disabled>Tidak Tersedia</button>
                        <?php endif; ?>

                        <a href="kamar_semua.php" class="btn btn-outline-secondary w-100">Lihat Kamar Lainnya</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Room Description -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-4">Deskripsi</h3>
                        <p class="card-text"><?= $room['description'] ?></p>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>