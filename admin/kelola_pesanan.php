<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$search_term = ''; // Variabel untuk menyimpan kata kunci pencarian
$where_clauses = []; // Array untuk menyimpan kondisi WHERE

// --- Logika Update Status Pesanan (Tidak Berubah) ---
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
        if (in_array($new_status, ['confirmed', 'checked_in', 'pending', 'paid'])) {
            $room_status = 'booked';
        }
        // Jika status dibatalkan atau checked_out, status kamar kembali ke available
        if (in_array($new_status, ['cancelled', 'checked_out', 'failed'])) {
            // Perlu cek apakah kamar ini tidak sedang dipesan oleh booking lain yang Confirmed/Checked_in
            // Untuk penyederhanaan, kita langsung set available, tapi dalam sistem nyata perlu pengecekan lebih lanjut.
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

// --- Logika Pencarian ---
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_term = trim($_GET['search']);
    $like = '%' . $search_term . '%';

    // Tambahkan kondisi WHERE untuk pencarian
    // Mencari berdasarkan: Kode Booking, Nama Pelanggan, Email Pelanggan, Nomor Kamar, Tipe Kamar, Status
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
        b.id, b.booking_code, b.check_in, b.check_out, b.total_price, b.status, b.created_at,
        u.name as user_name, u.email as user_email,
        r.room_number,
        rt.name as room_type_name,
        r.id as room_id
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
    // Panggil bind_param menggunakan call_user_func_array karena parameter dinamis

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
        /* Gaya tambahan untuk status */
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-confirmed,
        .status-paid {
            background-color: #0d6efd;
            color: #fff;
        }

        .status-checked-in {
            background-color: #198754;
            color: #fff;
        }

        .status-checked-out {
            background-color: #6c757d;
            color: #fff;
        }

        .status-cancelled,
        .status-failed {
            background-color: #dc3545;
            color: #fff;
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
                                        <th scope="col" width="150">Aksi</th>
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
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-warning text-dark action-btn"
                                                        data-bs-toggle="modal" data-bs-target="#actionModal"
                                                        data-id="<?= $booking['id'] ?>"
                                                        data-code="<?= htmlspecialchars($booking['booking_code']) ?>"
                                                        data-current-status="<?= htmlspecialchars($booking['status']) ?>"
                                                        data-room-id="<?= $booking['room_id'] ?>">
                                                        <i class="fas fa-cogs"></i> Status
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">Tidak ada pesanan yang ditemukan.</td>
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
                                <option value="confirmed">confirmed (Pesanan Dikonfirmasi)</option>
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
    </script>
</body>

</html>