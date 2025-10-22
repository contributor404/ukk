<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// --- Ambil Data Statistik ---
$total_bookings = $koneksi->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc()['total'];
$total_users = $koneksi->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'")->fetch_assoc()['total'];
$total_rooms = $koneksi->query("SELECT COUNT(*) as total FROM rooms")->fetch_assoc()['total'];
$pending_bookings = $koneksi->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'")->fetch_assoc()['total'];

// Data untuk chart
// Ambil semua tanggal dalam bulan ini
$days_in_month = date('t'); // jumlah hari dalam bulan ini
$current_month = date('m');
$current_year = date('Y');

// Siapkan array awal (semua tanggal = 0)
$daily_bookings = array_fill(1, $days_in_month, 0);

// Query data booking per tanggal bulan ini
$query = "
SELECT 
    DAY(created_at) AS hari,
    COUNT(*) AS total
FROM bookings
WHERE 
    MONTH(created_at) = '$current_month'
    AND YEAR(created_at) = '$current_year'
GROUP BY DAY(created_at)
ORDER BY hari ASC
";
$result = $koneksi->query($query);

// Isi data ke array berdasarkan hasil query
while ($row = $result->fetch_assoc()) {
    $hari = (int)$row['hari'];
    $daily_bookings[$hari] = (int)$row['total'];
}

// Siapkan data untuk Chart.js
$labels = range(1, $days_in_month);
$data = array_values($daily_bookings);

$labels_json = json_encode($labels);
$data_json = json_encode($data);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hotel Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>

<body>

    <?php
    include "sidebar.php";
    ?>

    <!-- Main Content -->
    <div class="content">
        <nav class="navbar rounded mb-4 px-4 py-3">
            <div class="d-flex justify-content-between align-items-center w-100">
                <h2 class="mb-0 fw-semibold">Dashboard</h2>
                <span class="text-muted">Selamat datang, <strong>Admin</strong></span>
            </div>
        </nav>

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card card-stat p-3 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= $total_bookings ?></h3>
                            <p class="text-muted mb-0">Total Pesanan</p>
                        </div>
                        <div class="icon-box"><i class="fa-solid fa-book fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat p-3 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= $total_users ?></h3>
                            <p class="text-muted mb-0">Total Pengguna</p>
                        </div>
                        <div class="icon-box"><i class="fa-solid fa-users fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat p-3 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= $total_rooms ?></h3>
                            <p class="text-muted mb-0">Total Kamar</p>
                        </div>
                        <div class="icon-box"><i class="fa-solid fa-bed fs-4"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stat p-3 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?= $pending_bookings ?></h3>
                            <p class="text-muted mb-0">Perlu Konfirmasi</p>
                        </div>
                        <div class="icon-box bg-warning bg-opacity-25 text-warning"><i class="fa-solid fa-hourglass-half fs-4"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-semibold">
                Statistik Pesanan Bulanan (Bulan Ini)
            </div>
            <div class="card-body">
                <canvas id="dailyBookingsChart" height="100"></canvas>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            const ctx = document.getElementById('dailyBookingsChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= $labels_json ?>,
                    datasets: [{
                        label: 'Jumlah Pesanan',
                        data: <?= $data_json ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.6)',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        title: {
                            display: true,
                            text: 'Statistik Pesanan Bulanan (Bulan Ini)'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        }
                    }
                }
            });
        </script>

</body>

</html>