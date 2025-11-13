<?php
session_start();
// Pastikan file koneksi.php sudah me-return objek koneksi (misalnya mysqli)
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$search_term = ''; 
$where_clauses = []; 

// --- LOGIKA BARU: Ambil Nama Admin untuk Struk ---
$admin_name = 'Admin'; // Default jika gagal
if (isset($_SESSION['user_id'])) {
    $admin_id = $_SESSION['user_id'];
    $admin_query = "SELECT name FROM users WHERE id = ?";
    $stmt_admin = $koneksi->prepare($admin_query);
    if ($stmt_admin) {
        $stmt_admin->bind_param("i", $admin_id);
        $stmt_admin->execute();
        $admin_result = $stmt_admin->get_result();
        $admin_data = $admin_result->fetch_assoc();
        $stmt_admin->close();
        if ($admin_data) {
            $admin_name = $admin_data['name'];
        }
    }
}
// --- Akhir Logika Nama Admin ---


// --- Logika Update Status Pesanan (Tidak Berubah) ---
// ... (Kode update status di sini) ...
if (isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];
    $room_id = $_POST['room_id'];

    $koneksi->begin_transaction();
    try {
        // 1. Update Status Pesanan
        $stmt_booking = $koneksi->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt_booking->bind_param("si", $new_status, $booking_id);
        if (!$stmt_booking->execute()) {
            throw new Exception("Gagal update status pesanan.");
        }
        $stmt_booking->close();

        // 2. Update Status Kamar
        $room_status = 'available'; // Default
        if (in_array($new_status, ['checked_in', 'pending', 'paid'])) {
            $room_status = 'booked';
        }
        // Jika status dibatalkan atau checked_out, status kamar kembali ke available
        if (in_array($new_status, ['cancelled', 'checked_out', 'failed'])) {
            $room_status = 'available';
        }

        $stmt_room = $koneksi->prepare("UPDATE rooms SET status = ? WHERE id = ?");
        $stmt_room->bind_param("si", $room_status, $room_id);
        if (!$stmt_room->execute()) {
            throw new Exception("Gagal update status kamar.");
        }
        $stmt_room->close();

        $koneksi->commit();
        // Menggunakan booking_code daripada id untuk pesan
        $message = "<div class='alert alert-success'>Status pesanan " . $_POST['booking_code_display'] . " berhasil diubah menjadi " . $new_status . "!</div>";
    } catch (Exception $e) {
        $koneksi->rollback();
        $message = "<div class='alert alert-danger'>Kesalahan saat mengubah status: " . $e->getMessage() . "</div>";
    }
}
// ----------------------------------------------------------------------

// --- LOGIKA FETCH DATA PESANAN UNTUK CETAK STRUK (Tidak Berubah) ---
// ... (Blok ini tidak berubah) ...
$print_booking = null;
if (isset($_GET['print_id']) && is_numeric($_GET['print_id'])) {
    $print_id = $_GET['print_id'];

    $print_query = "
        SELECT 
            b.id, b.booking_code, b.check_in, b.check_out, b.total_price, b.status, b.created_at,
            u.name as user_name, u.email as user_email,
            r.room_number,
            rt.name as room_type_name,
            rt.price_per_night 
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN rooms r ON b.room_id = r.id
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE b.id = ?
    ";

    $stmt_print = $koneksi->prepare($print_query);
    if ($stmt_print) {
        $stmt_print->bind_param("i", $print_id);
        $stmt_print->execute();
        $print_result = $stmt_print->get_result();
        $print_booking = $print_result->fetch_assoc();
        $stmt_print->close();

        if ($print_booking) {
            // Hitung durasi menginap (malam)
            $check_in_date = new DateTime($print_booking['check_in']);
            $check_out_date = new DateTime($print_booking['check_out']);
            $interval = $check_in_date->diff($check_out_date);
            $print_booking['nights'] = $interval->days;

            // Format data untuk ditampilkan di struk
            $print_booking['formatted_total_price'] = number_format($print_booking['total_price'], 0, ',', '.');
            $print_booking['formatted_price_per_night'] = number_format($print_booking['price_per_night'], 0, ',', '.');
            $print_booking['payment_status'] = strtoupper(str_replace('_', ' ', $print_booking['status']));
            $print_booking['check_in_formatted'] = date('d/m/Y', strtotime($print_booking['check_in']));
            $print_booking['check_out_formatted'] = date('d/m/Y', strtotime($print_booking['check_out']));
        }
    }
}
// ----------------------------------------------------------------------

// ============== LOGIKA PENGHAPUSAN OTOMATIS DAN PERHITUNGAN WAKTU (Tidak Berubah) ==============
/** ... Fungsi calculateExpirationStatus ... */
function calculateExpirationStatus($checkOutDate) {
    // Gunakan tanggal check_out sebagai penentu batas waktu (ditambah 1 hari)
    $check_out = new DateTime($checkOutDate);
    // Kita anggap batas kedaluwarsa adalah 24 jam setelah check_out
    $expiry_time = $check_out->modify('+1 day'); 
    $now = new DateTime();

    if ($now > $expiry_time) {
        return ['status' => 'KADALUWARSA', 'is_expired' => true];
    }
    
    $interval = $now->diff($expiry_time);
    $time_unit = '';
    $time_value = 0;

    if ($interval->days > 0) {
        $time_value = $interval->days;
        $time_unit = 'hari';
    } elseif ($interval->h > 0) {
        $time_value = $interval->h;
        $time_unit = 'jam';
    } elseif ($interval->i > 0) {
        $time_value = $interval->i;
        $time_unit = 'menit';
    } elseif ($interval->s > 0) {
        $time_value = $interval->s;
        $time_unit = 'detik';
    } else {
        // Jika selisih kurang dari satu detik, tapi belum expired
        return ['status' => 'Segera berakhir', 'is_expired' => false];
    }
    
    return ['status' => "$time_value $time_unit lagi", 'is_expired' => false];
}
// ... (Logika penghapusan otomatis dan fetch data lainnya tidak berubah) ...

// Hapus pesanan yang statusnya 'checked_out' atau 'cancelled' DAN sudah lebih dari 7 hari sejak check_out.
$seven_days_ago = date('Y-m-d', strtotime('-7 days'));
$delete_query = "
    DELETE b, r 
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE 
        (b.status = 'checked_out' OR b.status = 'cancelled' OR b.status = 'failed') AND 
        b.check_out < ?
";
$stmt_delete = $koneksi->prepare($delete_query);
if ($stmt_delete) {
    $stmt_delete->bind_param("s", $seven_days_ago);
    $stmt_delete->execute();
    $deleted_count = $stmt_delete->affected_rows;
    if ($deleted_count > 0) {
        // Pesan ini hanya untuk debugging, bisa dihapus di versi produksi
        // $message .= "<div class='alert alert-info'>$deleted_count pesanan lama berhasil dihapus otomatis.</div>";
    }
    $stmt_delete->close();
}


// --- Logika Pencarian (Tidak Berubah) ---
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $like = '%' . $search_term . '%';

    // Tambahkan kondisi WHERE untuk pencarian
    $where_clauses[] = "
            (
                b.booking_code LIKE ? OR
                u.name LIKE ? OR
                u.email LIKE ? OR
                r.room_number LIKE ? OR
                rt.name LIKE ? OR
                b.status LIKE ?
            )
    ";
    // Ulangi parameter LIKE sesuai jumlah klausa yang menggunakan LIKE
    $param_types = str_repeat('s', 6);
    $params = array_fill(0, 6, $like);
}

// 4. Ambil Daftar Pesanan (Tampil) dengan Pencarian
$query = "
     SELECT 
            b.id, b.booking_code, b.check_in, b.check_out, b.total_price, b.status, b.created_at, b.room_id,
            u.name as user_name, u.email as user_email,
            r.room_number,
            rt.name as room_type_name
     FROM bookings b
     JOIN users u ON b.user_id = u.id
     JOIN rooms r ON b.room_id = r.id
     JOIN room_types rt ON r.room_type_id = rt.id
";

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " ORDER BY b.created_at DESC";

// Gunakan prepare statement jika ada pencarian
if (!empty($search_term)) {
    $stmt = $koneksi->prepare($query);
    // Binding parameter dinamis
    $params_ref = array();
    $params_ref[] = &$param_types;
    foreach ($params as $param) {
        $params_ref[] = &$param;
    }

    call_user_func_array(array($stmt, 'bind_param'), $params_ref);
    $stmt->execute();
    $bookings_result = $stmt->get_result();
    $bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Jalankan kueri sederhana jika tidak ada pencarian
    $bookings_result = $koneksi->query($query);
    $bookings = $bookings_result->fetch_all(MYSQLI_ASSOC);
}


// 2. Perhitungan dan Penggabungan Status Kedaluwarsa
if (!empty($bookings)) {
    foreach ($bookings as &$booking) {
        // Catatan: check_out dari DB formatnya YYYY-MM-DD. Kita perlu menambahkan waktu
        // atau menghitung interval dengan akurat. Saya akan menggunakan fungsi di atas.
        
        // Kita anggap expired 1 hari setelah check_out date (untuk pending/paid)
        // atau jika statusnya sudah checked_out/cancelled/failed
        if (in_array($booking['status'], ['checked_out', 'cancelled', 'failed'])) {
             $booking['expired_status'] = '<span class="badge bg-secondary">Selesai/Dihapus</span>';
        } else {
             $expiry_data = calculateExpirationStatus($booking['check_out']);
             if ($expiry_data['is_expired']) {
                 $booking['expired_status'] = '<span class="badge bg-danger">EXPIRED</span>';
             } else {
                 $booking['expired_status'] = '<span class="badge bg-success">' . $expiry_data['status'] . '</span>';
             }
        }
    }
    unset($booking); // Hapus referensi terakhir
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pesanan - Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
    <style>
        .status-pending { background-color: #ffc107; color: #000; }
        .status-confirmed, .status-paid { background-color: #0d6efd; color: #fff; }
        .status-checked-in { background-color: #198754; color: #fff; }
        .status-checked-out { background-color: #6c757d; color: #fff; }
        .status-cancelled, .status-failed { background-color: #dc3545; color: #fff; }
        
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 80mm;
                margin: 0 auto;
                padding: 10px;
                color: #000;
                font-family: monospace, sans-serif;
                font-size: 12px;
                display: flex !important;
                flex-direction: column;
                align-items: center;
            }
            #wrapper, .navbar, .container-fluid>.row, .modal-backdrop, .modal { display: none !important; }
            .print-header { text-align: center; margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 5px; }
            .print-detail p { margin: 2px 0; }
            .print-total { margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px; text-align: right; width: 100%; }
            .print-total strong { font-size: 14px; }
            .status-badge { background: none !important; color: #000 !important; padding: 0; border: none; font-weight: bold; }
        }
    </style>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="page-content-wrapper" class="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
                 <div class="d-flex align-items-center">
                    <i class="fas fa-align-left primary-text fs-4 me-3" id="menu-toggle"></i>
                    <h2 class="fs-2 m-0">Kelola Pesanan</h2>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <?= $message ?>
                <div class="row my-4">
                    <h3 class="fs-4 mb-3">Daftar Semua Pesanan</h3>

                    <div class="col-md-12 mb-3">
                        <form method="GET" action="kelola_pesanan.php" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Cari Kode Booking, Pelanggan, Kamar, atau Status..." value="<?= htmlspecialchars($search_term) ?>">
                            <button type="submit" class="btn btn-outline-primary">Cari</button>
                            <?php if (!empty($search_term)): ?>
                                <a href="kelola_pesanan.php" class="btn btn-outline-secondary ms-2">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table bg-white rounded shadow-sm table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Kode Booking</th>
                                        <th scope="col">Pelanggan</th>
                                        <th scope="col">Kamar</th>
                                        <th scope="col">Check-in</th>
                                        <th scope="col">Check-out</th>
                                        <th scope="col">Total</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Kedaluwarsa</th> 
                                        <th scope="col" width="200">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($bookings)): ?>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($booking['booking_code']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($booking['user_name']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($booking['user_email']) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type_name']) ?>)</td>
                                                <td><?= htmlspecialchars($booking['check_in']) ?></td>
                                                <td><?= htmlspecialchars($booking['check_out']) ?></td>
                                                <td>Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></td>
                                                <td>
                                                    <span class="badge status-<?= str_replace('_', '-', htmlspecialchars($booking['status'])) ?>">
                                                        <?= strtoupper(htmlspecialchars($booking['status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= $booking['expired_status'] ?></td>
                                                
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning text-dark action-btn mb-1 mb-md-0"
                                                        data-bs-toggle="modal" data-bs-target="#actionModal"
                                                        data-id="<?= $booking['id'] ?>"
                                                        data-code="<?= htmlspecialchars($booking['booking_code']) ?>"
                                                        data-current-status="<?= htmlspecialchars($booking['status']) ?>"
                                                        data-room-id="<?= $booking['room_id'] ?>">
                                                        <i class="fas fa-cogs"></i> Status
                                                    </button>

                                                    <?php if (in_array($booking['status'], ['paid', 'checked_out'])): ?>
                                                        <button onclick="printStruk(<?= $booking['id'] ?>)" class="btn btn-sm btn-info text-white mt-1 mt-md-0">
                                                            <i class="fas fa-print"></i> Struk
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Tidak ada pesanan yang ditemukan.</td>
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

    <div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="kelola_pesanan.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="actionModalLabel">Ubah Status Pesanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="booking_id" id="modal_booking_id">
                        <input type="hidden" name="room_id" id="modal_room_id">
                        <input type="hidden" name="booking_code_display" id="modal_booking_code_input">
                        <p>Kode Booking: <strong id="modal_booking_code"></strong></p>
                        <p>Status Saat Ini: <span class="badge" id="modal_current_status_badge"></span></p>

                        <div class="mb-3">
                            <label for="new_status" class="form-label">Pilih Status Baru</label>
                            <select class="form-select" id="new_status" name="new_status" required>
                                <option value="pending">pending (Menunggu Pembayaran/Konfirmasi)</option>
                                <option value="paid">paid (Pembayaran Diterima)</option>
                                <option value="checked_in">checked_in (Sudah Check-in)</option>
                                <option value="checked_out">checked_out (Sudah Check-out)</option>
                                <option value="cancelled">cancelled (Dibatalkan)</option>
                                <option value="failed">failed (Pembayaran Gagal)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <div class="print-area text-center" style="flex-direction: column; align-items: center; display: none;">
        <?php if ($print_booking): ?>
            <div class="print-header">
                <h3>Hotel Booking</h3>
                <p>Jl. Hotel Indah No. 123</p>
                <p>(021) 1234-5678</p>
            </div>

            <div class="print-detail">
                <p>===================================</p>
                <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
                <p>Kode Booking: <strong><?= htmlspecialchars($print_booking['booking_code']) ?></strong></p>
                <p>Pelanggan: <?= htmlspecialchars($print_booking['user_name']) ?></p>
                <p>-----------------------------------</p>
                <p>Kamar: <?= htmlspecialchars($print_booking['room_type_name']) ?> (No. <?= htmlspecialchars($print_booking['room_number']) ?>)</p>
                <p>Check-in: <?= $print_booking['check_in_formatted'] ?></p>
                <p>Check-out: <?= $print_booking['check_out_formatted'] ?></p>
                <p>Durasi: <?= $print_booking['nights'] ?> malam</p>
                <p>-----------------------------------</p>
                <p>Harga/Malam: Rp <?= $print_booking['formatted_price_per_night'] ?></p>
                <p>Total Harga: Rp <?= $print_booking['formatted_total_price'] ?></p>
                <p>Status Pembayaran: <?= $print_booking['payment_status'] ?></p>
            </div>

            <div class="print-total">
                <p>===================================</p>
                <p>Total Bayar: <strong>Rp <?= $print_booking['formatted_total_price'] ?></strong></p>
            </div>

            <div class="text-center mt-3">
                <p>Terima kasih atas pemesanan Anda!</p>
            </div>
        <?php endif; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar
        var el = document.getElementById("wrapper");
        var toggleButton = document.getElementById("menu-toggle");

        toggleButton.onclick = function() {
            el.classList.toggle("toggled");
        };

        // Populate Action Modal
        document.querySelectorAll('.action-btn').forEach(button => {
            button.addEventListener('click', function() {
                const id = this.dataset.id;
                const code = this.dataset.code;
                const status = this.dataset.currentStatus;
                const roomId = this.dataset.roomId;

                document.getElementById('modal_booking_id').value = id;
                document.getElementById('modal_room_id').value = roomId;
                document.getElementById('modal_booking_code').textContent = code;
                document.getElementById('modal_booking_code_input').value = code; // Set untuk pesan sukses

                const statusBadge = document.getElementById('modal_current_status_badge');
                statusBadge.textContent = status.toUpperCase();
                statusBadge.className = 'badge status-' + status.replace('_', '-');

                document.getElementById('new_status').value = status; // Set default ke status saat ini
            });
        });

        // FUNGSI UNTUK MENGAMBIL DATA DAN MEMICU CETAK
        function printStruk(id) {
            window.location.href = 'kelola_pesanan.php?print_id=' + id;
        }

        <?php if ($print_booking): ?>
            // Logic ini hanya dieksekusi jika data struk ($print_booking) berhasil diambil
            document.addEventListener('DOMContentLoaded', () => {
                // Tampilkan area cetak (penting jika Anda menggunakan JS untuk menyembunyikannya secara default)
                document.querySelector('.print-area').style.display = 'flex';

                // Panggil dialog cetak browser
                window.print();

                // Setelah selesai cetak/user menutup dialog, hapus parameter 'print_id'
                // dari URL untuk kembali ke tampilan normal tanpa harus me-refresh ulang.
                setTimeout(() => {
                    window.history.pushState('', document.title, window.location.pathname);
                    document.querySelector('.print-area').style.display = 'none';
                }, 500);
            });
        <?php endif; ?>
    </script>
</body>

</html>