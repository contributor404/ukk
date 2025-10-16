<?php
session_start();
include 'koneksi.php';
include 'bootstrap.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Cek apakah ada parameter id kamar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: kamar_semua.php');
    exit;
}

$room_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Query untuk mengambil data kamar
$query = "SELECT r.*, rt.name as room_type, rt.price_per_night, rt.capacity 
          FROM rooms r 
          JOIN room_types rt ON r.room_type_id = rt.id 
          WHERE r.id = $room_id AND r.status = 'available'";
$result = $koneksi->query($query);

// Cek apakah kamar ditemukan dan tersedia
if ($result->num_rows == 0) {
    header('Location: kamar_semua.php');
    exit;
}

$room = $result->fetch_assoc();

// Proses form pemesanan
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    $check_in = $koneksi->real_escape_string($_POST['check_in']);
    $check_out = $koneksi->real_escape_string($_POST['check_out']);
    
    // Validasi tanggal
    $today = date('Y-m-d');
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $interval = $check_in_date->diff($check_out_date);
    $nights = $interval->days;
    
    if ($check_in < $today) {
        $error = "Tanggal check-in tidak boleh kurang dari hari ini!";
    } elseif ($check_out <= $check_in) {
        $error = "Tanggal check-out harus lebih besar dari tanggal check-in!";
    } else {
        // Hitung total harga
        $total_price = $room['price_per_night'] * $nights;
        
        // Generate booking code
        $booking_code = 'BK' . date('YmdHis') . rand(100, 999);
        
        // Mulai transaksi
        $koneksi->begin_transaction();
        
        try {
            // Insert ke tabel bookings
            $query_booking = "INSERT INTO bookings (booking_code, user_id, room_id, check_in, check_out, total_price, status) 
                             VALUES ('$booking_code', $user_id, $room_id, '$check_in', '$check_out', $total_price, 'pending')";
            
            if (!$koneksi->query($query_booking)) {
                throw new Exception("Error saat menyimpan pemesanan: ");
            }
            
            $booking_id = $koneksi->insert_id;
            
            // Insert ke tabel payments
            $query_payment = "INSERT INTO payments (booking_id, amount, status) 
                             VALUES ($booking_id, $total_price, 'pending')";
            
            if (!$koneksi->query($query_payment)) {
                throw new Exception("Error saat menyimpan pembayaran: ");
            }
            
            // Update status kamar menjadi booked
            $query_update_room = "UPDATE rooms SET status = 'booked' WHERE id = $room_id";
            
            if (!$koneksi->query($query_update_room)) {
                throw new Exception("Error saat mengupdate status kamar: ");
            }
            
            // Commit transaksi
            $koneksi->commit();
            
            $success = "Pemesanan berhasil dibuat dengan kode booking: $booking_code. Admin akan memverifikasi pembayaran Anda.";
            
            // Redirect ke halaman detail booking setelah 2 detik
            header("refresh:2;url=kamar_kode.php?kode=$booking_code");
            
        } catch (Exception $e) {
            // Rollback transaksi jika terjadi error
            $koneksi->rollback();
            $error = $e->getMessage();
        }
    }
}

// Format harga
$formatted_price = number_format($room['price_per_night'], 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kamar - Hotel Booking</title>
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
                        <h1>Formulir Pemesanan Kamar</h1>
                    </div>
                    
                    <div class="booking-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?= $success ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="room-info">
                            <h3>Informasi Kamar</h3>
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
                                    <input type="date" class="form-control" id="check_in" name="check_in" min="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="check_out" class="form-label">Tanggal Check-out</label>
                                    <input type="date" class="form-control" id="check_out" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Pesanan Anda akan diproses setelah admin menyetujui pembayaran.
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Pesan Sekarang</button>
                                <a href="kamar_detail.php?id=<?= $room['room_type_id'] ?>" class="btn btn-outline-secondary">Kembali</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
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
    
    <!-- JavaScript untuk menghitung total harga -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkInInput = document.getElementById('check_in');
            const checkOutInput = document.getElementById('check_out');
            
            function updateDates() {
                const checkInDate = new Date(checkInInput.value);
                
                // Set minimum check-out date to be one day after check-in
                if (checkInInput.value) {
                    const nextDay = new Date(checkInDate);
                    nextDay.setDate(nextDay.getDate() + 1);
                    
                    const year = nextDay.getFullYear();
                    const month = String(nextDay.getMonth() + 1).padStart(2, '0');
                    const day = String(nextDay.getDate()).padStart(2, '0');
                    
                    checkOutInput.min = `${year}-${month}-${day}`;
                    
                    // Reset check-out date if it's before the new minimum
                    if (checkOutInput.value && new Date(checkOutInput.value) <= checkInDate) {
                        checkOutInput.value = '';
                    }
                }
            }
            
            checkInInput.addEventListener('change', updateDates);
        });
    </script>
</body>
</html>