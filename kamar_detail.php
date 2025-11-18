<?php
session_start();

include 'koneksi.php';
include 'bootstrap.php';

// Cek apakah ada parameter id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<script>
            alert("ID kamar tidak valid.");
            window.location.href = "index.php";
          </script>';
    exit;
}

if (!isset($_SESSION["user_id"])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'];
$user_id = $_SESSION["user_id"];

// Query untuk mengambil data tipe kamar berdasarkan id
// Perhatian: room_image kini mengambil SEMUA nama file gambar dari satu kamar yang tersedia
$query = "SELECT
    rt.*,
    (
        SELECT r.image
        FROM rooms r
        WHERE r.room_type_id = rt.id AND r.status = 'available'
        LIMIT 1
    ) AS room_images_string,
    (
        SELECT COUNT(*)
        FROM rooms r
        WHERE r.room_type_id = rt.id AND r.status = 'available'
    ) AS available_rooms
FROM
    room_types rt
WHERE
    rt.id = $id;
";
$result = $koneksi->query($query);

// Cek apakah data ditemukan
if ($result->num_rows == 0) {
    echo '<script>
            alert("Tipe kamar tidak ditemukan.");
            window->location.href = "index.php";
          </script>';
    exit;
}

$room = $result->fetch_assoc();

// --- LOGIKA PERBAIKAN GAMBAR DIMULAI DI SINI ---
$images_string = $room['room_images_string'] ?? ''; // Ambil string gambar atau string kosong jika null
$room_images = [];

if (!empty($images_string)) {
    // Pisahkan string menjadi array, lalu bersihkan (trim) setiap elemennya
    $room_images = array_map('trim', explode(',', $images_string));
    // Hapus elemen kosong jika ada
    $room_images = array_filter($room_images);
}

// Tambahkan gambar placeholder jika kurang dari 4 (opsional, tergantung desain)
// while (count($room_images) < 4) {
//     $room_images[] = 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg'; 
// }
// --- LOGIKA PERBAIKAN GAMBAR SELESAI ---


if ($room["available_rooms"] == "0") {
    echo '<script>
            alert("Kamar tidak tersedia.");
            window.location.href = "index.php";
          </script>';
    exit;
}

// Cek apakah user sudah booking kamar ini
$qq = "SELECT r.*, r.id AS unix_room_id, b.*, b.status AS booking_status FROM bookings b INNER JOIN rooms r ON r.id = b.room_id WHERE user_id = $user_id AND (b.status = 'pending' OR b.status = 'checked_in')";
$res = $koneksi->query($qq);

if ($res && $res->num_rows > 0) {
    $ress = $res->fetch_assoc();
    if ($ress["room_type_id"] == $id) {
        echo '<script>
                alert("Anda sudah memesan kamar ini dan statusnya masih aktif.");
                window.location.href = "index.php";
              </script>';
        exit;
    }
}


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
        .main-room-image {
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
        }

        .thumbnail-image {
            height: 150px;
            /* Tambahkan tinggi agar konsisten */
            object-fit: cover;
            border-radius: 10px;
            width: 100%;
            margin-bottom: 10px;
            /* Tambahkan sedikit jarak antar thumbnail */
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
    </style>
</head>

<body>
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
            <div class="col-lg-8 mb-4">
                <?php
                // Tentukan gambar utama
                $main_image_url = !empty($room_images)
                    ? "./uploads/kamar/" . $room_images[0]
                    : 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg';
                ?>
                <img src="<?= $main_image_url ?>"
                    class="img-fluid main-room-image w-100 mb-4" alt="<?= $room['name'] ?> Main Image">

                <div class="row">
                    <?php
                    // Tampilkan gambar thumbnail (mulai dari indeks 1)
                    $thumbnails = array_slice($room_images, 1);
                    $total_thumbnails = count($thumbnails);

                    // Maksimal 3 thumbnail yang ditampilkan
                    for ($i = 0; $i < 3; $i++):
                        $image_url = isset($thumbnails[$i])
                            ? "./uploads/kamar/" . $thumbnails[$i]
                            : 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg'; // Placeholder untuk gambar yang tidak ada

                        // Kolom dibagi rata (3 thumbnail = col-4)
                        $col_class = 'col-4';
                    ?>
                        <div class="<?= $col_class ?>">
                            <img src="<?= $image_url ?>" class="img-fluid rounded thumbnail-image" alt="Gallery Image <?= $i + 2 ?>">
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>