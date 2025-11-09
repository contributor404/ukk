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
        if (in_array($new_status, ['checked_in', 'pending', 'paid'])) {
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

// ----------------------------------------------------------------------
// --- LOGIKA FETCH DATA PESANAN UNTUK CETAK STRUK (TAMBAHAN) ---
// ----------------------------------------------------------------------
$print_booking = null;
if (isset($_GET['print_id']) && is_numeric($_GET['print_id'])) {
    $print_id = $_GET['print_id'];

    $print_query = "
        SELECT 
            b.booking_code, b.check_in, b.check_out, b.total_price, b.status,
            u.name as user_name,
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

        @media print {
            body * {
                visibility: hidden;
            }

            .print-area,
            .print-area * {
                visibility: visible;
            }

            .print-area {
                /* Mengubah posisi dan tampilan agar mirip struk thermal */
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                max-width: 80mm; /* Batasi lebar seperti struk thermal */
                margin: 0 auto;
                padding: 10px;
                color: #000;
                font-family: monospace, sans-serif;
                font-size: 12px;
                display: flex !important; /* Pastikan print-area terlihat di cetakan */
                flex-direction: column;
                align-items: center;
            }
            
            /* Sembunyikan elemen utama saat mencetak */
            #wrapper,
            .navbar,
            .container-fluid > .row,
            .modal-backdrop,
            .modal {
                display: none !important;
            }

            .print-header {
                text-align: center;
                margin-bottom: 10px;
                border-bottom: 1px dashed #000;
                padding-bottom: 5px;
            }

            .print-detail p {
                margin: 2px 0;
            }

            .print-total {
                margin-top: 10px;
                border-top: 1px dashed #000;
                padding-top: 5px;
                text-align: right;
                width: 100%; /* Agar garis pemisah mencakup seluruh lebar */
            }

            .print-total strong {
                font-size: 14px;
            }

            .status-badge {
                /* Pastikan badge status terlihat saat dicetak, tetapi tanpa warna latar belakang */
                background: none !important;
                color: #000 !important;
                padding: 0;
                border: none;
                font-weight: bold;
            }
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

        // ----------------------------------------------------------------------
        // FUNGSI UNTUK MENGAMBIL DATA DAN MEMICU CETAK
        // ----------------------------------------------------------------------
        function printStruk(id) {
            // Saat tombol "Struk" diklik, ia akan me-redirect halaman
            // dengan menambahkan parameter 'print_id=ID' ke URL.
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
        // ----------------------------------------------------------------------
    </script>
</body>

</html>