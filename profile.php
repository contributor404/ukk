<?php
session_start();

include 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Query untuk mengambil data pengguna
// Menggunakan Prepared Statement untuk keamanan
$query = "SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?";
$stmt = $koneksi->prepare($query);

// Bind parameter (i = integer)
$stmt->bind_param("i", $user_id);

// Eksekusi statement
$stmt->execute();

// Ambil hasil
$result = $stmt->get_result();

// Cek apakah data pengguna ditemukan
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    // Jika user tidak ditemukan, set pesan error atau redirect
    $user = null;
    $error_message = "Data pengguna tidak ditemukan.";
}

// Tutup statement
$stmt->close();

// Tutup koneksi (opsional di akhir script, tapi baik untuk praktik yang bagus)
$koneksi->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <section class="vh-100" style="background-color: #f4f5f7;">
        <div class="container py-5 h-100">
            <div class="row d-flex justify-content-center align-items-center h-100">
                <div class="col col-lg-6 mb-4 mb-lg-0">

                    <?php if ($user): ?>
                    <div class="card mb-3 profile-card">
                        <div class="row g-0">
                            <div class="col-md-4 text-center text-white" style="border-top-left-radius: .5rem; border-bottom-left-radius: .5rem; background-color: #38bdf8;">
                                                                <h5 class="mt-3"><?php echo htmlspecialchars($user['name']); ?></h5>
                                <p class="small mb-4"><?php echo ucwords(htmlspecialchars($user['role'])); ?></p>
                                <i class="far fa-edit mb-5"></i>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body p-4">
                                    <h6 class="text-info">Informasi Profil</h6>
                                    <hr class="mt-0 mb-4">
                                    <div class="row pt-1">
                                        <div class="col-6 mb-3">
                                            <h6 class="small text-muted">Email</h6>
                                            <p class="text-dark"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h6 class="small text-muted">Telepon</h6>
                                            <p class="text-dark"><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></p>
                                        </div>
                                    </div>

                                    <h6 class="text-info mt-3">Detail Akun</h6>
                                    <hr class="mt-0 mb-4">
                                    <div class="row pt-1">
                                        <div class="col-6 mb-3">
                                            <h6 class="small text-muted">ID Pengguna</h6>
                                            <p class="text-dark"><?php echo htmlspecialchars($user['id']); ?></p>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <h6 class="small text-muted">Bergabung Sejak</h6>
                                            <p class="text-dark"><?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> 
</body>
</html>