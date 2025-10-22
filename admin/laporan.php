<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// --- Logika Laporan Bulanan (Chart) ---
$monthly_bookings_data = [];
$monthly_revenue_data = [];
$year = date('Y');

for ($i = 1; $i <= 12; $i++) {
    $month = str_pad($i, 2, '0', STR_PAD_LEFT);
    
    // Jumlah Pesanan
    $query_count = "SELECT COUNT(*) as total FROM bookings WHERE MONTH(created_at) = '$month' AND YEAR(created_at) = '$year' AND status IN ('confirmed', 'checked_in', 'checked_out', 'paid')";
    $monthly_bookings_data[] = $koneksi->query($query_count)->fetch_assoc()['total'];

    // Total Pendapatan
    $query_revenue = "SELECT SUM(total_price) as total FROM bookings WHERE MONTH(created_at) = '$month' AND YEAR(created_at) = '$year' AND status IN ('confirmed', 'checked_in', 'checked_out', 'paid')";
    $revenue = $koneksi->query($query_revenue)->fetch_assoc()['total'];
    $monthly_revenue_data[] = $revenue ? $revenue : 0;
}
$monthly_bookings_json = json_encode($monthly_bookings_data);
$monthly_revenue_json = json_encode($monthly_revenue_data);

// --- Logika Statistik Penjualan Kamar (Berdasarkan Tanggal) ---
$stats_result = [];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $stmt = $koneksi->prepare("
        SELECT 
            rt.name as room_type_name,
            COUNT(b.id) as total_bookings,
            SUM(b.total_price) as total_revenue,
            SUM(DATEDIFF(b.check_out, b.check_in)) as total_nights
        FROM bookings b
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE b.status IN ('confirmed', 'checked_in', 'checked_out', 'paid') 
        AND b.check_in BETWEEN ? AND ?
        GROUP BY rt.name
        ORDER BY total_revenue DESC
    ");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $stats_result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Total Ringkasan
$total_booking_count = array_sum(array_column($stats_result, 'total_bookings'));
$total_revenue_sum = array_sum(array_column($stats_result, 'total_revenue'));
$total_nights_sum = array_sum(array_column($stats_result, 'total_nights'));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Statistik - Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="page-content-wrapper" class="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-align-left primary-text fs-4 me-3" id="menu-toggle"></i>
                    <h2 class="fs-2 m-0">Laporan & Statistik</h2>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <div class="row my-5">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-chart-bar me-2"></i> Laporan Bulanan (<?= $year ?>)
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" id="myTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings-chart" type="button" role="tab" aria-controls="bookings-chart" aria-selected="true">Jumlah Pesanan</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="revenue-tab" data-bs-toggle="tab" data-bs-target="#revenue-chart" type="button" role="tab" aria-controls="revenue-chart" aria-selected="false">Pendapatan</button>
                                    </li>
                                </ul>
                                <div class="tab-content pt-3" id="myTabContent">
                                    <div class="tab-pane fade show active" id="bookings-chart" role="tabpanel" aria-labelledby="bookings-tab">
                                        <canvas id="monthlyBookingsChart" height="100"></canvas>
                                    </div>
                                    <div class="tab-pane fade" id="revenue-chart" role="tabpanel" aria-labelledby="revenue-tab">
                                        <canvas id="monthlyRevenueChart" height="100"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row my-5">
                    <div class="col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-calendar-alt me-2"></i> Statistik Penjualan Kamar
                            </div>
                            <div class="card-body">
                                <form method="GET" action="laporan.php" class="row g-3 align-items-end mb-4">
                                    <div class="col-md-4">
                                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-search me-2"></i>Tampilkan Laporan</button>
                                    </div>
                                </form>

                                <?php if (!empty($stats_result)): ?>
                                <h5 class="mt-4">Ringkasan Periode <?= date('d M Y', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?></h5>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light border rounded text-center">
                                            <h4 class="mb-0"><?= $total_booking_count ?></h4>
                                            <small class="text-muted">Total Pesanan Sukses</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light border rounded text-center">
                                            <h4 class="mb-0">Rp <?= number_format($total_revenue_sum, 0, ',', '.') ?></h4>
                                            <small class="text-muted">Total Pendapatan</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 bg-light border rounded text-center">
                                            <h4 class="mb-0"><?= $total_nights_sum ?></h4>
                                            <small class="text-muted">Total Malam Terjual</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Tipe Kamar</th>
                                                <th>Jml. Pesanan</th>
                                                <th>Jml. Malam Terjual</th>
                                                <th>Pendapatan (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats_result as $stat): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($stat['room_type_name']) ?></td>
                                                    <td><?= $stat['total_bookings'] ?></td>
                                                    <td><?= $stat['total_nights'] ?></td>
                                                    <td><?= number_format($stat['total_revenue'], 0, ',', '.') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php elseif (isset($_GET['start_date'])): ?>
                                    <div class="alert alert-warning mt-4">Tidak ada data pesanan sukses untuk periode ini.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Toggle Sidebar
        var el = document.getElementById("wrapper");
        var toggleButton = document.getElementById("menu-toggle");

        toggleButton.onclick = function () {
            el.classList.toggle("toggled");
        };

        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

        // Chart Pesanan
        const ctxBookings = document.getElementById('monthlyBookingsChart').getContext('2d');
        new Chart(ctxBookings, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pesanan Sukses',
                    data: <?= $monthly_bookings_json ?>,
                    backgroundColor: 'rgba(13, 110, 253, 0.7)',
                    borderColor: 'rgba(13, 110, 253, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: { y: { beginAtZero: true } }
            }
        });

        // Chart Pendapatan
        const ctxRevenue = document.getElementById('monthlyRevenueChart').getContext('2d');
        new Chart(ctxRevenue, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Pendapatan (Rp)',
                    data: <?= $monthly_revenue_json ?>,
                    backgroundColor: 'rgba(25, 135, 84, 0.5)',
                    borderColor: 'rgba(25, 135, 84, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                scales: { y: { beginAtZero: true, ticks: { callback: function(value, index, values) { return 'Rp ' + value.toLocaleString('id-ID'); } } } }
            }
        });
    </script>
</body>
</html>