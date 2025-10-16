<?php
session_start();
include 'koneksi.php';
include 'bootstrap.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking - Temukan Kamar Terbaik</title>
    <!-- Custom CSS -->
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 150px 0;
            margin-bottom: 50px;
        }
        
        .room-card {
            transition: transform 0.3s;
            margin-bottom: 30px;
            height: 100%;
        }
        
        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .room-img {
            height: 200px;
            object-fit: cover;
        }
        
        .facilities-icons {
            font-size: 1.2rem;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <!-- Navbar sudah diinclude dari bootstrap.php -->

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-3 fw-bold mb-4">Temukan Kenyamanan Menginap</h1>
            <p class="lead mb-5">Nikmati pengalaman menginap terbaik dengan fasilitas lengkap dan pelayanan prima</p>
            <a href="kamar_semua.php" class="btn btn-primary btn-lg px-5 py-3">Lihat Semua Kamar</a>
        </div>
    </section>

    <!-- Room Types Section -->
    <section class="container mb-5">
        <h2 class="text-center mb-5">Tipe Kamar Unggulan</h2>
        <div class="row" id="room-list">
            <?php
            // Query untuk mengambil data tipe kamar
            $query = "SELECT rt.*, r.image FROM room_types rt LEFT JOIN rooms r ON rt.id = r.room_type_id ORDER BY price_per_night LIMIT 6";
            $result = $koneksi->query($query);
            
            // Cek apakah ada data
            if ($result->num_rows > 0) {
                // Tampilkan data tipe kamar
                while ($row = $result->fetch_assoc()) {
                    // Konversi fasilitas dari format teks ke array
                    $facilities = explode(",", $row['facilities']);
                    $facilities_icons = '';
                    
                    // Buat ikon untuk setiap fasilitas
                    foreach ($facilities as $facility) {
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
                        
                        $facilities_icons .= "<span class='me-3'><i class='fas {$icon} me-1'></i> {$facility}</span>";
                    }
                    
                    // Format harga
                    $formatted_price = number_format($row['price_per_night'], 0, ',', '.');
                    
                    echo "<div class='col-md-4 mb-4'>";
                    echo "<div class='card room-card shadow'>";
                    echo "<img src='{$row['image']}' class='card-img-top room-img' alt='{$row['name']}'>";
                    echo "<div class='card-body'>";
                    echo "<h5 class='card-title'>{$row['name']}</h5>";
                    echo "<p class='card-text'>{$row['description']}</p>";
                    echo "<div class='d-flex justify-content-between align-items-center mb-3'>";
                    echo "<span class='badge bg-primary rounded-pill'>Kapasitas: {$row['capacity']} orang</span>";
                    echo "<span class='fw-bold text-primary'>Rp {$formatted_price}/malam</span>";
                    echo "</div>";
                    echo "<div class='facilities-icons mb-3'>{$facilities_icons}</div>";
                    echo "<a href='kamar_detail.php?id={$row['id']}' class='btn btn-outline-primary w-100'>Lihat Detail</a>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<div class='col-12'><p class='text-center'>Tidak ada tipe kamar yang tersedia saat ini.</p></div>";
            }
            ?>
        </div>
    </section>

    <!-- Footer -->
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
                <p class="mb-0">&copy; 2023 Hotel Booking. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- No JavaScript needed as we're using PHP to load data -->
</body>
</html>