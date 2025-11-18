<?php
include 'bootstrap.php';
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
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

        .status-pending {
            background-color: #ffc107;
            color: #000 !important;
        }

        .status-confirmed {
            background-color: #198754;
        }

        .status-paid {
            background-color: #0d6efd;
        }

        .status-cancelled {
            background-color: #dc3545;
        }

        .status-checked_in {
            background-color: #0dcaf0;
        }

        .status-checked_out {
            background-color: #6c757d;
        }

        .status-failed {
            background-color: #dc3545;
        }
    </style>
</head>

<body>
    <header class="page-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Riwayat Pemesanan</h1>
            <p class="lead">Lihat semua riwayat pemesanan kamar Anda</p>
        </div>
    </header>

    <div class="container mb-5" id="riwayat-list">
        <!-- Data riwayat akan dimuat oleh jQuery -->
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
    <script src="./assets/js/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            $.ajax({
                url: "api/riwayat.php",
                method: "GET",
                dataType: "json",
                success: function(response) {
                    const container = $("#riwayat-list");
                    container.empty();

                    if (response.status === "success" && response.data.length > 0) {
                        response.data.forEach(function(row) {
                            // Ganti label status
                            let statusText = row.booking_status;
                            const statusMap = {
                                "pending": "Menunggu Konfirmasi",
                                "confirmed": "Dikonfirmasi",
                                "cancelled": "Dibatalkan",
                                "checked_in": "Check-in",
                                "checked_out": "Check-out",
                                "paid": "Lunas",
                                "failed": "Gagal"
                            };
                            if (statusMap[row.booking_status]) {
                                statusText = statusMap[row.booking_status];
                            }

                            // Format tanggal dan harga
                            const checkIn = new Date(row.check_in);
                            const checkOut = new Date(row.check_out);
                            const options = {
                                day: "2-digit",
                                month: "short",
                                year: "numeric"
                            };
                            const checkInFormatted = checkIn.toLocaleDateString("id-ID", options);
                            const checkOutFormatted = checkOut.toLocaleDateString("id-ID", options);
                            const totalFormatted = new Intl.NumberFormat("id-ID").format(row.total_price);

                            // Gambar default
                            const image = row.room_image && row.room_image[0].trim() !== "" ?
                                "./uploads/kamar/" + row.room_image[0] :
                                "https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg";

                            // Bangun HTML card
                            const card = `
                        <div class="history-card mb-3">
                            <img src="${image}" alt="${row.room_type_name}" />
                            <div class="card-body">
                                <div>
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title">${row.room_type_name}</h5>
                                        <span class="fw-bold text-primary">Rp ${totalFormatted}</span>
                                    </div>
                                    <p class="card-text mb-1"><strong>Kode Booking:</strong> ${row.booking_code}</p>
                                    <p class="card-text">
                                        <strong>Check-in:</strong> ${checkInFormatted} |
                                        <strong>Check-out:</strong> ${checkOutFormatted}
                                    </p>
                                    <div class="d-flex gap-2">
                                        <span class="status-badge status-${row.booking_status}">
                                            ${statusText}
                                        </span>
                                    </div>
                                </div>
                                <a href="kamar_kode.php?kode=${row.booking_code}"
                                   class="btn btn-outline-primary align-self-end mt-3">
                                   Lihat Detail
                                </a>
                            </div>
                        </div>
                    `;

                            container.append(card);
                        });
                    } else {
                        container.html(`
                    <div class="text-center">
                        <p>Anda belum memiliki riwayat pemesanan.</p>
                        <a href="kamar_semua.php" class="btn btn-primary">Pesan Kamar Sekarang</a>
                    </div>
                `);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Gagal memuat data:", error);
                    $("#riwayat-list").html(`
                <div class='text-center text-danger'>
                    <p>Gagal memuat data riwayat pemesanan.</p>
                </div>
            `);
                }
            });
        });
    </script>
</body>

</html>