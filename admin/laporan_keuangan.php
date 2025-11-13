<?php
session_start();
// Pastikan path ini benar dan file koneksi Anda mengembalikan objek koneksi (mysqli)
include '../koneksi.php';

// Cek apakah user adalah admin (Disarankan)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    // Jika perlu redirect:
    // header("Location: ../login.php");
    // exit;
}

// --- FUNGSI UTILITY TANGGAL & FILTER ---

// Default filter: Bulan ini
$filter_mode = $_GET['filter_mode'] ?? 'month';
$filter_start = '';
$filter_end = '';
$filter_display = '';

// Logika penentuan tanggal mulai dan akhir berdasarkan mode filter
if ($filter_mode == 'day' && isset($_GET['date_filter']) && !empty($_GET['date_filter'])) {
    // Filter per Hari
    $date = new DateTime($_GET['date_filter']);
    // Filter dibuat dari awal hari sampai akhir hari
    $filter_start = $date->format('Y-m-d 00:00:00');
    $filter_end = $date->format('Y-m-d 23:59:59');
    $filter_display = 'Laporan Tanggal: ' . $date->format('d F Y');
} elseif ($filter_mode == 'month' && isset($_GET['month_filter']) && !empty($_GET['month_filter'])) {
    // Filter per Bulan
    $month_year = $_GET['month_filter']; // Format YYYY-MM
    $start_of_month = new DateTime($month_year . '-01');
    $end_of_month = new DateTime($month_year . '-01');
    $end_of_month->modify('last day of this month')->setTime(23, 59, 59);

    $filter_start = $start_of_month->format('Y-m-d H:i:s');
    $filter_end = $end_of_month->format('Y-m-d H:i:s');
    $filter_display = 'Laporan Bulan: ' . $start_of_month->format('F Y');
} else {
    // Default: Bulan ini
    $filter_mode = 'month';
    $start_of_month = new DateTime('first day of this month');
    $end_of_month = new DateTime('last day of this month');
    $end_of_month->setTime(23, 59, 59);

    $filter_start = $start_of_month->format('Y-m-d H:i:s');
    $filter_end = $end_of_month->format('Y-m-d H:i:s');
    $filter_display = 'Laporan Bulan Ini: ' . $start_of_month->format('F Y');
}

// --- FUNGSI UTAMA LAPORAN ---

/**
 * Mengambil total pendapatan dari tabel bookings (status paid/checked_out)
 */
function getTotalRevenue($koneksi, $start, $end)
{
    $query = "
        SELECT SUM(total_price) as total 
        FROM bookings 
        WHERE (status = 'paid' OR status = 'checked_out') 
        AND created_at BETWEEN ? AND ?
    ";

    if ($stmt = $koneksi->prepare($query)) {
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['total'] ?? 0;
    }
    return 0;
}

/**
 * Mengambil total pengeluaran dari tabel pengeluaran
 */
function getTotalExpenses($koneksi, $start, $end)
{
    $query = "
        SELECT SUM(jumlah) as total 
        FROM pengeluaran 
        WHERE tanggal BETWEEN DATE(?) AND DATE(?)
    ";

    if ($stmt = $koneksi->prepare($query)) {
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result['total'] ?? 0;
    }
    return 0;
}

/**
 * Mengambil histori pendapatan (bookings) dengan detail
 */
function getRevenueHistory($koneksi, $start, $end)
{
    $query = "
        SELECT 
            b.booking_code, b.total_price, b.created_at, b.status,
            u.name as user_name,
            r.room_number,
            rt.name as room_type_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE (b.status = 'paid' OR b.status = 'checked_out')
        AND b.created_at BETWEEN ? AND ?
        ORDER BY b.created_at DESC
    ";

    if ($stmt = $koneksi->prepare($query)) {
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
    return [];
}

/**
 * Mengambil histori pengeluaran dengan detail
 */
function getExpenseHistory($koneksi, $start, $end)
{
    $query = "
        SELECT 
            p.tanggal, p.keterangan, p.jumlah, p.kategori,
            u.name as user_id_name
        FROM pengeluaran p
        JOIN users u ON p.user_id = u.id
        WHERE p.tanggal BETWEEN DATE(?) AND DATE(?)
        ORDER BY p.tanggal DESC
    ";

    if ($stmt = $koneksi->prepare($query)) {
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }
    return [];
}


// --- PENGAMBILAN DATA UTAMA & PERHITUNGAN ---
$total_revenue = getTotalRevenue($koneksi, $filter_start, $filter_end);
$total_expenses = getTotalExpenses($koneksi, $filter_start, $filter_end);
$net_profit = $total_revenue - $total_expenses;

$revenue_history = getRevenueHistory($koneksi, $filter_start, $filter_end);
$expense_history = getExpenseHistory($koneksi, $filter_start, $filter_end);

// Helper untuk format Rupiah
function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 2, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        /* CSS untuk tampilan cetak */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                font-size: 10pt;
                margin: 0;
                padding: 0;
                color: #000;
            }

            .container-print {
                width: 100%;
                margin: 0;
                padding: 10px;
                box-shadow: none;
            }

            .table th,
            .table td {
                padding: 0.2rem;
                font-size: 10pt;
            }

            .print-header {
                text-align: center;
                border-bottom: 2px solid #000;
                margin-bottom: 15px;
            }

            /* Styling mirip struk untuk ringkasan */
            .summary-table {
                width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            .summary-table td {
                border-bottom: 1px dashed #000;
                padding: 5px 0;
            }

            .text-end {
                text-align: right;
            }
        }
    </style>
</head>

<body class="bg-light">
    <?php include "sidebar.php" ?>

    <div class="container my-5 container-print">

        <h2 class="mb-3 no-print">Laporan Keuangan Hotel</h2>
        <p class="lead text-muted no-print"><?= $filter_display ?></p>

        <div class="card shadow-sm mb-4 p-3 no-print">
            <h5 class="card-title mb-3">Filter Laporan</h5>
            <form method="GET" action="laporan_keuangan.php" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter_mode" class="form-label">Mode Filter</label>
                    <select class="form-select" id="filter_mode" name="filter_mode" onchange="toggleFilterInputs(this.value)">
                        <option value="month" <?= $filter_mode == 'month' ? 'selected' : '' ?>>Bulan</option>
                        <option value="day" <?= $filter_mode == 'day' ? 'selected' : '' ?>>Hari</option>
                    </select>
                </div>

                <div class="col-md-5">
                    <div id="month_input" style="display: <?= $filter_mode == 'month' ? 'block' : 'none' ?>;">
                        <label for="month_filter" class="form-label">Pilih Bulan</label>
                        <input type="month" class="form-control" id="month_filter" name="month_filter"
                            value="<?= $filter_mode == 'month' && isset($_GET['month_filter']) ? htmlspecialchars($_GET['month_filter']) : date('Y-m') ?>">
                    </div>
                    <div id="day_input" style="display: <?= $filter_mode == 'day' ? 'block' : 'none' ?>;">
                        <label for="date_filter" class="form-label">Pilih Tanggal</label>
                        <input type="date" class="form-control" id="date_filter" name="date_filter"
                            value="<?= $filter_mode == 'day' && isset($_GET['date_filter']) ? htmlspecialchars($_GET['date_filter']) : date('Y-m-d') ?>">
                    </div>
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Tampilkan</button>
                    <button type="button" class="btn btn-info text-white" onclick="window.print()"><i class="fas fa-print"></i> Cetak Laporan</button>
                </div>
            </form>
        </div>

        <div class="print-header d-none d-print-block">
            <h3>LAPORAN KEUANGAN</h3>
            <p style="margin: 0;"><?= $filter_display ?></p>
            <p style="font-size: 8pt;">Dicetak: <?= date('d/m/Y H:i:s') ?></p>
        </div>

        <h4 class="mb-3">Rekapitulasi Keuangan</h4>
        <div class="row mb-4">

            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase fw-bold">Pendapatan (Revenue)</div>
                                <h2 class="mb-0"><?= formatRupiah($total_revenue) ?></h2>
                            </div>
                            <i class="fas fa-money-bill-wave fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card bg-danger text-white shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase fw-bold">Pengeluaran (Expenses)</div>
                                <h2 class="mb-0"><?= formatRupiah($total_expenses) ?></h2>
                            </div>
                            <i class="fas fa-shopping-cart fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <?php $laba_class = $net_profit >= 0 ? 'bg-primary text-white' : 'bg-warning text-dark'; ?>
                <div class="card <?= $laba_class ?> shadow">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase fw-bold">Laba Bersih (Net Profit)</div>
                                <h2 class="mb-0"><?= formatRupiah($net_profit) ?></h2>
                            </div>
                            <i class="fas fa-chart-line fa-3x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-none d-print-block mb-4">
            <h5 class="mt-3">Ringkasan</h5>
            <table class="summary-table">
                <tr>
                    <td>Pendapatan</td>
                    <td class="text-end"><?= formatRupiah($total_revenue) ?></td>
                </tr>
                <tr>
                    <td>Pengeluaran</td>
                    <td class="text-end"><?= formatRupiah($total_expenses) ?></td>
                </tr>
                <tr class="fw-bold">
                    <td>LABA BERSIH</td>
                    <td class="text-end"><?= formatRupiah($net_profit) ?></td>
                </tr>
            </table>
        </div>


        <div class="row">

            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Histori Pendapatan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Kode Booking</th>
                                        <th>Kamar</th>
                                        <th class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($revenue_history)): ?>
                                        <?php foreach ($revenue_history as $rev): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($rev['created_at'])) ?></td>
                                                <td><?= htmlspecialchars($rev['booking_code']) ?></td>
                                                <td><?= htmlspecialchars($rev['room_number']) ?> (<?= htmlspecialchars($rev['room_type_name']) ?>)</td>
                                                <td class="text-end"><?= formatRupiah($rev['total_price']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Tidak ada transaksi pendapatan pada periode ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">Histori Pengeluaran</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th>Kategori</th>
                                        <th class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($expense_history)): ?>
                                        <?php foreach ($expense_history as $exp): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($exp['tanggal'])) ?></td>
                                                <td><?= htmlspecialchars($exp['keterangan']) ?></td>
                                                <td><?= htmlspecialchars($exp['kategori']) ?></td>
                                                <td class="text-end"><?= formatRupiah($exp['jumlah']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Tidak ada transaksi pengeluaran pada periode ini.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fungsi untuk menampilkan atau menyembunyikan input filter (Bulan atau Hari)
        function toggleFilterInputs(mode) {
            const monthInput = document.getElementById('month_input');
            const dayInput = document.getElementById('day_input');

            if (mode === 'month') {
                monthInput.style.display = 'block';
                dayInput.style.display = 'none';
            } else {
                monthInput.style.display = 'none';
                dayInput.style.display = 'block';
            }
        }

        // Panggil fungsi toggle saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            const mode = document.getElementById('filter_mode').value;
            toggleFilterInputs(mode);
        });

        // Mengatasi masalah pengiriman form dengan input kosong
        document.querySelector('form').addEventListener('submit', function(e) {
            const mode = document.getElementById('filter_mode').value;
            const monthFilter = document.getElementById('month_filter');
            const dayFilter = document.getElementById('date_filter');

            // Hapus atribut 'name' dari input yang tidak digunakan agar tidak ikut terkirim
            if (mode === 'month') {
                dayFilter.removeAttribute('name');
            } else {
                monthFilter.removeAttribute('name');
            }
        });
    </script>
</body>

</html>