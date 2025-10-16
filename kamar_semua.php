<?php
include 'koneksi.php';
include 'bootstrap.php'
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Kamar - Hotel Booking</title>
    <!-- Custom CSS -->
    <style>
        .page-header {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 80px 0;
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
            height: 250px;
            object-fit: cover;
        }
        
        .facilities-icons {
            font-size: 1.2rem;
            color: #0d6efd;
        }
        
        .filter-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <!-- Page Header -->
    <header class="page-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Semua Kamar</h1>
            <p class="lead">Temukan kamar yang sesuai dengan kebutuhan dan budget Anda</p>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container mb-5">
        <!-- Filter Section -->
        <div class="filter-section shadow-sm mb-5">
            <form action="" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="capacity" class="form-label">Kapasitas</label>
                        <select class="form-select" id="capacity" name="capacity">
                            <option value="">Semua Kapasitas</option>
                            <option value="1">1 Orang</option>
                            <option value="2">2 Orang</option>
                            <option value="3">3 Orang</option>
                            <option value="4">4 Orang</option>
                            <option value="5">5+ Orang</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="price" class="form-label">Harga Maksimum</label>
                        <select class="form-select" id="price" name="price">
                            <option value="">Semua Harga</option>
                            <option value="500000">Dibawah Rp 500.000</option>
                            <option value="1000000">Dibawah Rp 1.000.000</option>
                            <option value="1500000">Dibawah Rp 1.500.000</option>
                            <option value="2000000">Dibawah Rp 2.000.000</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Kamar</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" placeholder="Nama atau fasilitas kamar...">
                            <button class="btn btn-primary" type="submit">Cari</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Room List -->
        <div class="row">
            <?php
            // Inisialisasi query dasar
            $query = "SELECT rt.*, 
                    (SELECT r.image FROM rooms r WHERE r.room_type_id = rt.id LIMIT 1) as room_image,
                    (SELECT COUNT(*) FROM rooms r WHERE r.room_type_id = rt.id AND r.status = 'available') as available_rooms
                    FROM room_types rt";
            
            // Array untuk menyimpan kondisi WHERE
            $conditions = [];
            $params = [];
            
            // Filter berdasarkan kapasitas
            if (isset($_GET['capacity']) && !empty($_GET['capacity'])) {
                $capacity = $_GET['capacity'];
                $conditions[] = "rt.capacity = $capacity";
            }
            
            // Filter berdasarkan harga maksimum
            if (isset($_GET['price']) && !empty($_GET['price'])) {
                $price = $_GET['price'];
                $conditions[] = "rt.price_per_night <= $price";
            }
            
            // Filter berdasarkan pencarian
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search = $koneksi->real_escape_string($_GET['search']);
                $conditions[] = "(rt.name LIKE '%$search%' OR rt.description LIKE '%$search%' OR rt.facilities LIKE '%$search%')";
            }
            
            // Tambahkan kondisi WHERE jika ada
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }
            
            // Tambahkan pengurutan
            $query .= " ORDER BY rt.price_per_night ASC";
            
            $result = $koneksi->query($query);

            ?>
            <p class="text-danger" style="font-weight: bold;">Fatal error: <?= var_dump($result->fetch_all()); ?></p>
            <?php
            
            // Cek apakah ada data
            if ($result->num_rows > 0) {
                // Tampilkan data tipe kamar
                while ($row = $result->fetch_assoc()) {
                    // Konversi fasilitas dari format teks ke array
                    $facilities = explode(",", $row['facilities']);
                    $facilities_icons = '';
                    
                    // Buat ikon untuk setiap fasilitas (maksimal 3 untuk tampilan card)
                    $count = 0;
                    foreach ($facilities as $facility) {
                        if ($count >= 3) break; // Batasi hanya 3 fasilitas yang ditampilkan
                        
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
                        $count++;
                    }
                    
                    // Format harga
                    $formatted_price = number_format($row['price_per_night'], 0, ',', '.');
                    
                    // Tentukan badge ketersediaan
                    $availability_badge = '';
                    if ($row['available_rooms'] > 0) {
                        $availability_badge = "<span class='badge bg-success'>Tersedia {$row['available_rooms']} kamar</span>";
                    } else {
                        $availability_badge = "<span class='badge bg-danger'>Tidak tersedia</span>";
                    }
                    
                    echo "<div class='col-md-6 col-lg-4 mb-4'>";
                    echo "<div class='card room-card shadow h-100'>";
                    echo "<img src='{$row['room_image']}' class='card-img-top room-img' alt='{$row['name']}'>";
                    echo "<div class='card-body d-flex flex-column'>";
                    echo "<div class='d-flex justify-content-between align-items-start mb-2'>";
                    echo "<h5 class='card-title mb-0'>{$row['name']}</h5>";
                    echo "{$availability_badge}";
                    echo "</div>";
                    echo "<p class='card-text mb-2'>".substr($row['description'], 0, 100)."...</p>";
                    echo "<div class='d-flex justify-content-between align-items-center mb-2'>";
                    echo "<span class='badge bg-primary rounded-pill'>Kapasitas: {$row['capacity']} orang</span>";
                    echo "<span class='fw-bold text-primary'>Rp {$formatted_price}/malam</span>";
                    echo "</div>";
                    echo "<div class='facilities-icons mb-3'>{$facilities_icons}</div>";
                    echo "<a href='kamar_detail.php?id={$row['id']}' class='btn btn-outline-primary mt-auto w-100'>Lihat Detail</a>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<div class='col-12'><div class='alert alert-info'>Tidak ada kamar yang sesuai dengan kriteria pencarian Anda.</div></div>";
            }
            ?>
        </div>
    </div>

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
                <p class="mb-0">&copy; 2030 Hotel Booking. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>