<?php
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
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
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
        <div class="row" id="room-list">

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
    <script src="./assets/js/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            $.ajax({
                url: "api/hotel.php",
                method: "GET",
                dataType: "json",
                success: function(response) {
                    const container = $("#room-list");
                    container.empty(); // kosongkan isi dulu

                    if (response.status === "success") {
                        response.data.forEach(function(room) {
                            // Tentukan badge
                            let badge = room.available_rooms > 0 ?
                                `<span class="badge bg-success">Tersedia ${room.available_rooms} kamar</span>` :
                                `<span class="badge bg-danger">Tidak tersedia</span>`;

                            // Format harga
                            let priceFormatted = new Intl.NumberFormat('id-ID').format(room.price_per_night);

                            // Ikon fasilitas
                            let icons = '';
                            room.facilities.split(",").forEach(facility => {
                                let icon = 'fa-check';
                                let f = facility.toLowerCase();

                                if (f.includes('wifi')) icon = 'fa-wifi';
                                else if (f.includes('tv') || f.includes('televisi')) icon = 'fa-tv';
                                else if (f.includes('ac') || f.includes('air')) icon = 'fa-snowflake';
                                else if (f.includes('sarapan') || f.includes('breakfast')) icon = 'fa-utensils';
                                else if (f.includes('parkir') || f.includes('parking')) icon = 'fa-car';

                                icons += `<span class="me-3"><i class="fas ${icon} me-1"></i> ${facility}</span>`;
                            });

                            room.room_image = room.room_image ? "./uploads/kamar/" + room.room_image : "https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg";

                            // Susun card kamar
                            const html = `
                        <div class="col-md-4 mb-4">
                            <div class="card room-card shadow">
                                <img src="${room.room_image}" class="card-img-top room-img" alt="${room.name}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0">${room.name}</h5>
                                        ${badge}
                                    </div>
                                    <p class="card-text">${room.description}</p>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="badge bg-primary rounded-pill">Kapasitas: ${room.capacity} orang</span>
                                        <span class="fw-bold text-primary">Rp ${priceFormatted}/malam</span>
                                    </div>
                                    <div class="facilities-icons mb-3">${icons}</div>
                                    <a href="kamar_detail.php?id=${room.id}" class="btn btn-outline-primary w-100">Lihat Detail</a>
                                </div>
                            </div>
                        </div>`;

                            container.append(html);
                        });
                    } else {
                        container.html(`<div class="col-12"><p class="text-center">Tidak ada tipe kamar yang tersedia saat ini.</p></div>`);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Gagal memuat data:", error);
                    $("#room-list").html(`<div class='col-12'><p class='text-center text-danger'>Gagal memuat data kamar.</p></div>`);
                }
            })
        })
    </script>
</body>

</html>