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

$room_type_id = $_GET['id']; // ID Tipe Kamar
$user_id = $_SESSION['user_id'];

// Query untuk mengambil informasi tipe kamar
$query_type = "
    SELECT rt.name AS room_type, rt.price_per_night, rt.capacity, rt.id AS room_type_id
    FROM room_types rt 
    WHERE rt.id = $room_type_id
";
$result_type = $koneksi->query($query_type);

// Cek apakah tipe kamar ditemukan
if ($result_type->num_rows == 0) {
    header('Location: kamar_semua.php');
    exit;
}

$room_type = $result_type->fetch_assoc();

// Hitung total kamar fisik dengan tipe kamar ini (untuk pemeriksaan stok)
$query_total_rooms = "SELECT COUNT(id) AS total_rooms FROM rooms WHERE room_type_id = $room_type_id";
$result_total_rooms = $koneksi->query($query_total_rooms);
$total_rooms = $result_total_rooms->fetch_assoc()['total_rooms'];

if ($total_rooms == 0) {
    // Jika tidak ada kamar fisik sama sekali, redirect
    header('Location: kamar_semua.php');
    exit;
}

// Data Kamar yang ditampilkan di form (mengambil salah satu kamar, hanya untuk info tampilan)
// Mengambil kamar pertama yang statusnya 'available', jika ada, sebagai default info kamar
$query_room_info = "
    SELECT r.id, r.room_number, r.floor
    FROM rooms r 
    WHERE r.room_type_id = $room_type_id
    ORDER BY FIELD(r.status, 'available', 'booked', 'maintenance'), r.room_number
    LIMIT 1
";
$result_room_info = $koneksi->query($query_room_info);

if ($result_room_info->num_rows == 0) {
    // Seharusnya tidak terjadi karena sudah di cek $total_rooms > 0
    header('Location: kamar_semua.php');
    exit;
}

$room_info = $result_room_info->fetch_assoc();

// Query untuk mendapatkan semua tanggal yang sudah *terisi penuh* (booked_out)
// Karena logika ini kompleks di PHP, kita akan membiarkan $booked_dates kosong, dan 
// HANYA mengandalkan validasi stok di sisi server saat POST.
$booked_dates = array();
// Logika pengisian $booked_dates diabaikan/dihapus untuk fokus pada validasi server-side
// dan untuk mematuhi permintaan user agar kode lebih ringkas.

// Proses form pemesanan
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validasi input
    $check_in = $koneksi->real_escape_string($_POST['check_in']);
    $check_out = $koneksi->real_escape_string($_POST['check_out']);

    // Validasi tanggal dasar
    $today = date('Y-m-d');
    $error_found = false;

    try {
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
    } catch (Exception $e) {
        // Menggunakan alert JS untuk error format tanggal
        echo "<script>alert('Format tanggal tidak valid.'); window.location.href='kamar_pesan.php?id={$room_type_id}';</script>";
        exit;
    }

    if (!$error_found) {
        $interval = $check_in_date->diff($check_out_date);
        $nights = $interval->days;

        if ($check_in < $today) {
            $error_msg = "Tanggal check-in tidak boleh kurang dari hari ini!";
            echo "<script>alert('{$error_msg}'); window.location.href='kamar_pesan.php?id={$room_type_id}';</script>";
            exit;
        } elseif ($check_out <= $check_in) {
            $error_msg = "Tanggal check-out harus lebih besar dari tanggal check-in!";
            echo "<script>alert('{$error_msg}'); window.location.href='kamar_pesan.php?id={$room_type_id}';</script>";
            exit;
        } elseif ($nights == 0) {
            $error_msg = "Durasi menginap minimal harus 1 malam.";
            echo "<script>alert('{$error_msg}'); window.location.href='kamar_pesan.php?id={$room_type_id}';</script>";
            exit;
        }
    }

    // Lakukan pemeriksaan konflik/stok hanya jika tidak ada error format/logika tanggal dasar
    if (!$error_found) {
        // ** (1) LOGIC BARU: CEK STOK KAMAR YANG TERSEDIA PADA PERIODE TANGGAL YANG DIMINTA **

        // 1. Hitung jumlah kamar fisik yang sudah *terisi* (booked/confirmed/dll) pada periode yang diminta
        // Query ini mencari booking aktif yang tumpang tindih dengan periode yang diminta.
        $query_booked_count = "
            SELECT COUNT(DISTINCT b.room_id) AS booked_count
            FROM bookings b
            JOIN rooms r ON b.room_id = r.id
            WHERE 
                r.room_type_id = $room_type_id AND 
                b.status NOT IN ('cancelled', 'failed') AND
                (
                    -- Tumpang tindih jika:
                    -- Kasus 1: Check-in baru berada di antara check-in/check-out yang sudah ada
                    ('$check_in' >= b.check_in AND '$check_in' < b.check_out) OR
                    -- Kasus 2: Check-out baru berada di antara check-in/check-out yang sudah ada
                    ('$check_out' > b.check_in AND '$check_out' <= b.check_out) OR
                    -- Kasus 3: Booking baru mencakup (mengelilingi) booking yang sudah ada
                    ('$check_in' <= b.check_in AND '$check_out' >= b.check_out)
                )
        ";

        $result_booked_count = $koneksi->query($query_booked_count);
        $booked_count = $result_booked_count->fetch_assoc()['booked_count'];

        $available_count = $total_rooms - $booked_count;

        // 2. Periksa apakah masih ada kamar yang tersedia
        if ($available_count <= 0) {
            // JIKA STOK HABIS: Tampilkan alert bahwa kamar tipe ini sudah full
            $error_msg = "Mohon maaf, semua kamar tipe **{$room_type['room_type']}** sudah dipesan pada periode tanggal ($check_in) sampai ($check_out). Silakan pilih tanggal lain.";
            echo "<script>alert('{$error_msg}'); window.location.href='kamar_pesan.php?id={$room_type_id}';</script>";
            exit;
        } else {
            // ** STOK TERSEDIA **
            // 3. Cari 1 ID kamar fisik yang *benar-benar* kosong (tidak ada booking tumpang tindih) pada periode ini
            $query_available_room_id = "
                SELECT r.id
                FROM rooms r
                WHERE r.room_type_id = $room_type_id 
                AND r.id NOT IN (
                    SELECT DISTINCT b.room_id
                    FROM bookings b
                    WHERE 
                        b.status NOT IN ('cancelled', 'failed') AND
                        (
                            ('$check_in' >= b.check_in AND '$check_in' < b.check_out) OR
                            ('$check_out' > b.check_in AND '$check_out' <= b.check_out) OR
                            ('$check_in' <= b.check_in AND '$check_out' >= b.check_out)
                        )
                )
                LIMIT 1
            ";

            $result_room_id = $koneksi->query($query_available_room_id);

            if ($result_room_id->num_rows == 0) {
                // Fallback error jika logika count tidak sesuai dengan pencarian room_id
                $error_msg = "Terjadi kesalahan sistem, tidak dapat menemukan kamar yang kosong meskipun stok tersedia.";
                echo "<script>alert('{$error_msg}'); window.location.href='kamar_pesan.php?id={$room_type_id}';</script>";
                exit;
            }

            $room_to_book = $result_room_id->fetch_assoc();
            $room_id_to_book = $room_to_book['id']; // ID kamar fisik yang akan di-booking

            // Lanjutkan proses booking
            // Hitung total harga
            $total_price = $room_type['price_per_night'] * $nights;

            // Generate booking code
            $booking_code = 'BK' . date('YmdHis') . rand(100, 999);

            // Mulai transaksi
            $koneksi->begin_transaction();

            try {
                // 1. Insert ke tabel bookings (status default 'pending')
                $query_booking = "INSERT INTO bookings (booking_code, user_id, room_id, check_in, check_out, total_price, status) 
                                  VALUES ('$booking_code', $user_id, $room_id_to_book, '$check_in', '$check_out', $total_price, 'pending')";

                if (!$koneksi->query($query_booking)) {
                    throw new Exception("Error saat menyimpan pemesanan: " . $koneksi->error);
                }

                // 2. Update status kamar fisik di tabel 'rooms' menjadi 'booked' sesuai permintaan user.
                // CATATAN: Dengan perubahan ini, kamar yang terpesan (meskipun untuk tanggal di masa depan) 
                // akan dianggap 'booked' secara umum, dan Admin perlu mengelola status ini secara manual.
                $query_update_room = "UPDATE rooms SET status = 'booked' WHERE id = $room_id_to_book";

                if (!$koneksi->query($query_update_room)) {
                    throw new Exception("Error saat update status kamar: " . $koneksi->error);
                }

                // Commit transaksi
                $koneksi->commit();

                $success = "Pemesanan berhasil dibuat dengan kode booking: **$booking_code**. Anda akan dialihkan ke halaman detail.";

                // Redirect ke halaman detail booking setelah 2 detik
                header("refresh:2;url=kamar_kode.php?kode=$booking_code");
            } catch (Exception $e) {
                // Rollback transaksi jika terjadi error
                $koneksi->rollback();
                $error = "Terjadi kesalahan sistem. Silakan coba lagi. Detail: " . $e->getMessage();
            }
        }
    }
}

// Format harga
$formatted_price = number_format($room_type['price_per_night'], 0, ',', '.');

// Tetapkan tanggal minimum check-in hanya sebagai hari ini.
$min_date = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Kamar - LuxStay</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
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

        .alert-booking {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .flatpickr-day.disabled {
            background-color: #ffcccc !important;
            color: #ff0000 !important;
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

                        <div class="alert-booking">
                            <i class="fas fa-info-circle me-2"></i> Tanggal dengan warna merah **mungkin** sudah tidak tersedia untuk dipesan (tergantung pada status ketersediaan di hari tersebut).
                        </div>

                        <div class="room-info">
                            <h3>Informasi Kamar Tipe <?= $room_type['room_type'] ?></h3>
                            <p class="mb-2">Total Kamar Tersedia untuk Tipe ini: **<?= $total_rooms ?>** unit</p>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Tipe Kamar:</strong> <?= $room_type['room_type'] ?></p>
                                    <p><strong>Kapasitas:</strong> <?= $room_type['capacity'] ?> orang</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Harga per Malam:</strong> <span class="price-tag">Rp <?= $formatted_price ?></span></p>
                                    <p class="text-muted">*(Anda akan mendapatkan salah satu dari <?= $total_rooms ?> kamar tipe ini yang tersedia)*</p>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="" id="bookingForm">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="check_in" class="form-label">Tanggal Check-in</label>
                                    <input type="text" class="form-control" id="check_in" name="check_in" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="check_out" class="form-label">Tanggal Check-out</label>
                                    <input type="text" class="form-control" id="check_out" name="check_out" required>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Pesanan Anda akan diproses setelah admin menyetujui pembayaran.
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Pesan Sekarang</button>
                                <a href="kamar_detail.php?id=<?= $room_type['room_type_id'] ?>" class="btn btn-outline-secondary">Kembali</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>LuxStay</h5>
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
                <p class="mb-0">&copy; 2023 LuxStay. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>
    <script>
        // Menggunakan array kosong karena logika penentuan tanggal yang penuh total (booked_out) 
        // yang kompleks hanya diurus di Server-Side saat POST untuk menghindari beban.
        const bookedDates = <?= json_encode($booked_dates) ?>;

        // Konversi tanggal yang sudah dipesan ke format yang dibutuhkan oleh flatpickr
        const disabledDates = bookedDates.map(date => date);
        console.log(disabledDates)

        // Inisialisasi flatpickr untuk check-in
        const checkInPicker = flatpickr("#check_in", {
            locale: "id",
            minDate: "today",
            disable: disabledDates,
            dateFormat: "Y-m-d",
            onChange: function(selectedDates, dateStr, instance) {
                // Set minimum date untuk check-out
                if (selectedDates.length > 0) {
                    const minCheckOut = new Date(selectedDates[0]);
                    minCheckOut.setDate(minCheckOut.getDate() + 1);

                    checkOutPicker.set('minDate', minCheckOut);

                    // Reset check-out jika lebih kecil dari check-in
                    const checkOutDate = checkOutPicker.selectedDates[0];
                    if (checkOutDate && checkOutDate <= selectedDates[0]) {
                        checkOutPicker.clear();
                    }
                }
            }
        });

        // Inisialisasi flatpickr untuk check-out
        const checkOutPicker = flatpickr("#check_out", {
            locale: "id",
            minDate: "today",
            disable: disabledDates,
            dateFormat: "Y-m-d"
        });

        // Validasi form dasar sebelum submit (Validasi stok sepenuhnya di server)
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const checkIn = document.getElementById('check_in').value;
            const checkOut = document.getElementById('check_out').value;

            if (!checkIn || !checkOut) {
                e.preventDefault();
                alert('Silakan pilih tanggal check-in dan check-out');
                return false;
            }

            const checkInDate = new Date(checkIn);
            const checkOutDate = new Date(checkOut);

            if (checkOutDate <= checkInDate) {
                e.preventDefault();
                alert('Tanggal check-out harus lebih besar dari tanggal check-in');
                return false;
            }

            // Logika client-side untuk cek tanggal yang disabled dihapus/diabaikan
            // karena logika stok yang sebenarnya ada di server.

            return true; // Lanjutkan ke server-side validation
        });
    </script>
</body>

</html>