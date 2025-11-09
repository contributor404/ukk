<?php
session_start();
include 'koneksi.php';
include 'bootstrap.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Cek apakah ada parameter id *tipe* kamar (room_type_id)
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: kamar_semua.php');
    exit;
}

$room_type_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Query untuk mengambil *satu* kamar yang *tersedia* dari tipe kamar yang diminta
$query = "
    SELECT r.id, r.room_number, r.floor, rt.name AS room_type, rt.price_per_night, rt.capacity, rt.id AS room_type_id
    FROM rooms r 
    JOIN room_types rt ON r.room_type_id = rt.id 
    WHERE rt.id = $room_type_id AND r.status = 'available'
    LIMIT 1
";
$result = $koneksi->query($query);

// Cek apakah kamar ditemukan dan tersedia
if ($result->num_rows == 0) {
    // Jika tidak ada kamar yang tersedia untuk tipe ini, redirect
    header('Location: kamar_semua.php');
    exit;
}

$room = $result->fetch_assoc();
$room_id_to_book = $room["id"]; // ID kamar fisik yang akan di-booking

// Variabel untuk menyimpan pesan
$success = "";
$error = "";
$booking_code_success = ""; // Menyimpan booking code untuk ditampilkan dan tombol cetak

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    $check_in = $koneksi->real_escape_string($_POST['check_in']);
    $check_out = $koneksi->real_escape_string($_POST['check_out']);
    $payment_method = $koneksi->real_escape_string($_POST['payment_method']); // Ambil metode pembayaran

    // Validasi tanggal
    $today = date('Y-m-d');

    try {
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
    } catch (Exception $e) {
        $error = "Format tanggal tidak valid.";
    }

    if (empty($error)) {
        $interval = $check_in_date->diff($check_out_date);
        $nights = $interval->days;
        
        if ($check_in < $today) {
            $error = "Tanggal check-in tidak boleh kurang dari hari ini!";
        } elseif ($check_out <= $check_in) {
            $error = "Tanggal check-out harus lebih besar dari tanggal check-in!";
        } elseif ($nights == 0) {
            $error = "Durasi menginap minimal harus 1 malam.";
        } else {
            // Hitung total harga
            $total_price = $room['price_per_night'] * $nights;

            // Generate booking code
            $booking_code = 'BK' . date('YmdHis') . rand(100, 999);

            // Mulai transaksi
            $koneksi->begin_transaction();

            try {
                // 1. Insert ke tabel bookings (STATUS LANGSUNG 'paid')
                $query_booking = "INSERT INTO bookings (booking_code, user_id, room_id, check_in, check_out, total_price, status) 
                                 VALUES ('$booking_code', $user_id, $room_id_to_book, '$check_in', '$check_out', $total_price, 'paid')";

                if (!$koneksi->query($query_booking)) {
                    throw new Exception("Error saat menyimpan pemesanan: " . $koneksi->error);
                }

                // 2. Update status kamar menjadi booked
                $query_update_room = "UPDATE rooms SET status = 'booked' WHERE id = $room_id_to_book";

                if (!$koneksi->query($query_update_room)) {
                    throw new Exception("Error saat mengupdate status kamar: " . $koneksi->error);
                }

                // Commit transaksi
                $koneksi->commit();

                $success = "Pemesanan dan pembayaran berhasil! Status kamar Anda telah dikonfirmasi dan dibayar.";
                $booking_code_success = $booking_code; // Simpan kode booking untuk tombol cetak

            } catch (Exception $e) {
                // Rollback transaksi jika terjadi error
                $koneksi->rollback();
                $error = "Terjadi kesalahan sistem. Silakan coba lagi. Detail: " . $e->getMessage();
            }
        }
    }
}

// Format harga
$formatted_price = number_format($room['price_per_night'], 0, ',', '.');

// Logika untuk menentukan tanggal minimal check-in
$min_date = date('Y-m-d');
$list_pesan = $koneksi->query("SELECT check_out FROM bookings b WHERE b.user_id = $user_id ORDER BY check_out DESC LIMIT 1");

if ($list_pesan->num_rows > 0) {
    $last_booking = $list_pesan->fetch_assoc();
    $min_date_for_new_booking = date("Y-m-d", strtotime($last_booking["check_out"] . " +1 day"));
    // Pastikan tanggal minimal tidak kurang dari hari ini
    $min_date = max($min_date, $min_date_for_new_booking);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kamar & Bayar - Hotel Booking</title>
    <style>
        .booking-card {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .booking-header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .booking-body {
            padding: 30px;
        }

        .room-info {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .price-tag {
            font-size: 1.5rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="booking-card">
                    <div class="booking-header">
                        <h1>Formulir Pemesanan & Pembayaran</h1>
                    </div>

                    <div class="booking-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success text-center">
                                <h4>✅ Pembayaran Berhasil!</h4>
                                <p><?= $success ?></p>
                                <p>Kode Booking Anda: **<?= $booking_code_success ?>**</p>
                                <a href="kamar_kode.php?kode=<?= $booking_code_success ?>" class="btn btn-warning btn-lg mt-3">
                                    <i class="fas fa-print me-2"></i> Cetak Bukti Pemesanan
                                </a>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="kamar_semua.php" class="btn btn-outline-primary mt-3">Kembali ke Daftar Kamar</a>
                            </div>

                        <?php else: ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <?= $error ?>
                                </div>
                            <?php endif; ?>

                            <div class="room-info">
                                <h3>Informasi Kamar yang Dipilih</h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Nomor Kamar:</strong> <?= $room['room_number'] ?></p>
                                        <p><strong>Tipe Kamar:</strong> <?= $room['room_type'] ?></p>
                                        <p><strong>Lantai:</strong> <?= $room['floor'] ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Kapasitas:</strong> <?= $room['capacity'] ?> orang</p>
                                        <p><strong>Harga per Malam:</strong> <span class="price-tag">Rp <?= $formatted_price ?></span></p>
                                    </div>
                                </div>
                            </div>

                            <form method="POST" action="">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="check_in" class="form-label">Tanggal Check-in</label>
                                        <input type="date" class="form-control" id="check_in" name="check_in" min="<?= $min_date ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="check_out" class="form-label">Tanggal Check-out</label>
                                        <input type="date" class="form-control" id="check_out" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day', strtotime($min_date))) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="payment_method" class="form-label">Metode Pembayaran</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="">Pilih Metode Pembayaran</option>
                                        <option value="transfer">Transfer Bank (BCA/Mandiri)</option>
                                        <option value="credit_card">Kartu Kredit/Debit</option>
                                        <option value="e_wallet">E-Wallet (GoPay/OVO/Dana)</option>
                                    </select>
                                    <small class="form-text text-muted">Memilih metode pembayaran akan mensimulasikan pembayaran yang berhasil dan status booking langsung menjadi **Paid**.</small>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> Perhatian: Dengan menekan tombol **Pesan dan Bayar**, status pemesanan Anda akan langsung menjadi **Paid** di sistem.
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">Pesan dan Bayar Sekarang</button>
                                    <a href="kamar_detail.php?id=<?= $room['room_type_id'] ?>" class="btn btn-outline-secondary">Kembali</a>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-5 mt-5">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkInInput = document.getElementById('check_in');
            const checkOutInput = document.getElementById('check_out');
            const minCheckOutDate = "<?= date('Y-m-d', strtotime('+1 day', strtotime($min_date))) ?>";

            // Set initial minimum check-out date
            checkOutInput.min = minCheckOutDate;

            function updateDates() {
                const checkInDate = new Date(checkInInput.value);

                // Set minimum check-out date to be one day after check-in
                if (checkInInput.value) {
                    const nextDay = new Date(checkInDate);
                    nextDay.setDate(nextDay.getDate() + 1);

                    const year = nextDay.getFullYear();
                    const month = String(nextDay.getMonth() + 1).padStart(2, '0');
                    const day = String(nextDay.getDate()).padStart(2, '0');

                    const newMinCheckOut = `${year}-${month}-${day}`;
                    checkOutInput.min = newMinCheckOut;

                    // Reset check-out date if it's before the new minimum
                    if (checkOutInput.value && new Date(checkOutInput.value) <= checkInDate) {
                        checkOutInput.value = '';
                    }
                } else {
                     // If check-in is cleared, reset check-out min to the initial value
                    checkOutInput.min = minCheckOutDate;
                }
            }

            checkInInput.addEventListener('change', updateDates);
        });
    </script>
</body>

</html>