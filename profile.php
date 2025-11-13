<?php
session_start();

// Ganti 'koneksi.php' dengan path yang benar ke file koneksi Anda
include 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = ''; // Variabel untuk menampung pesan sukses/error
$error_message = '';
$user = null; // Inisialisasi variabel user

// Direktori tempat menyimpan foto profil
$target_dir = "uploads/profile_pictures/";
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true);
}

// --- FUNGSI UTILITY ---
function get_user_data($koneksi, $user_id)
{
    $query = "SELECT id, name, email, phone, role, created_at, profile_pic FROM users WHERE id = ?";
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();
    return $user_data;
}

// Ambil data pengguna saat ini
$user = get_user_data($koneksi, $user_id);

// --- LOGIKA UPDATE DATA PENGGUNA (Nama, Email, Telepon) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {

    $new_name = trim($_POST['name'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_phone = trim($_POST['phone'] ?? '');

    if (empty($new_name) || empty($new_email)) {
        $message = "<div class='alert alert-danger'>Nama dan Email wajib diisi!</div>";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert alert-danger'>Format email tidak valid.</div>";
    } else {
        // Cek duplikasi email (kecuali email milik sendiri)
        $check_email_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $koneksi->prepare($check_email_query);
        $stmt->bind_param("si", $new_email, $user_id);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Email sudah digunakan oleh pengguna lain.</div>";
        } else {
            // Update data
            $update_query = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
            $stmt = $koneksi->prepare($update_query);
            $stmt->bind_param("sssi", $new_name, $new_email, $new_phone, $user_id);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Profil berhasil diperbarui!</div>";
                // Muat ulang data pengguna
                $user = get_user_data($koneksi, $user_id);
            } else {
                $message = "<div class='alert alert-danger'>Gagal memperbarui profil: " . $stmt->error . "</div>";
            }
        }
        $stmt->close();
    }
}

// --- LOGIKA GANTI KATA SANDI ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $message = "<div class='alert alert-danger'>Semua kolom kata sandi wajib diisi.</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger'>Kata sandi baru dan konfirmasi tidak cocok.</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='alert alert-danger'>Kata sandi baru minimal 6 karakter.</div>";
    } else {
        // Ambil hash kata sandi lama dari database (Asumsi kolom password di users adalah 'password')
        $check_pass_query = "SELECT password FROM users WHERE id = ?";
        $stmt = $koneksi->prepare($check_pass_query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res && password_verify($old_password, $res['password'])) {
            // Hash kata sandi baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update kata sandi
            $update_pass_query = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $koneksi->prepare($update_pass_query);
            $stmt->bind_param("si", $hashed_password, $user_id);

            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Kata sandi berhasil diubah!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Gagal mengubah kata sandi: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='alert alert-danger'>Kata sandi lama salah.</div>";
        }
    }
}

// --- LOGIKA GANTI FOTO PROFIL ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_photo']) && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "<div class='alert alert-danger'>Gagal upload foto: Kode error " . $file['error'] . "</div>";
    } else {
        $imageFileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $valid_extensions = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($imageFileType, $valid_extensions)) {
            $message = "<div class='alert alert-danger'>Hanya format JPG, JPEG, PNG, & WEBP yang diizinkan.</div>";
        } elseif ($file['size'] > 5000000) { // 5MB
            $message = "<div class='alert alert-danger'>Ukuran file terlalu besar (Maks 5MB).</div>";
        } else {
            // Hasilkan nama file unik
            $new_file_name = $user_id . '_' . time() . '.' . $imageFileType;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {

                // Hapus foto lama jika ada dan bukan foto default
                if ($user['profile_pic'] && $user['profile_pic'] !== 'default.png' && file_exists($target_dir . $user['profile_pic'])) {
                    unlink($target_dir . $user['profile_pic']);
                }

                // Update database
                $update_photo_query = "UPDATE users SET profile_pic = ? WHERE id = ?";
                $stmt = $koneksi->prepare($update_photo_query);
                $stmt->bind_param("si", $new_file_name, $user_id);

                if ($stmt->execute()) {
                    $message = "<div class='alert alert-success'>Foto profil berhasil diubah!</div>";
                    // Muat ulang data pengguna
                    $user = get_user_data($koneksi, $user_id);
                } else {
                    $message = "<div class='alert alert-danger'>Gagal menyimpan path foto ke database.</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert alert-danger'>Terjadi kesalahan saat memindahkan file.</div>";
            }
        }
    }
}

// Tutup koneksi di akhir script
$koneksi->close();

if (!$user) {
    // Jika data pengguna tidak ditemukan setelah semua proses, tampilkan error
    $error_message = "Data pengguna tidak ditemukan. Silakan login ulang.";
}

// Tentukan path foto profil
$profile_pic_path = $target_dir . ($user['profile_pic'] ?? 'default.png');
// Cek apakah file foto benar-benar ada, jika tidak, pakai default
if (!file_exists($profile_pic_path) || is_dir($profile_pic_path)) {
    $profile_pic_path = $target_dir . 'default.png'; // Pastikan default.png tersedia di folder uploads/profile_pictures/
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Pengguna</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .profile-pic-container {
            width: 150px;
            height: 150px;
            margin: 0 auto 20px auto;
            border-radius: 50%;
            overflow: hidden;
            border: 4px solid #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #eee;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .nav-link.active {
            background-color: #0d6efd !important;
            color: white !important;
        }
    </style>
</head>

<body>

    <section class="py-5" style="background-color: #f4f5f7; min-height: 100vh;">
        <div class="container">
            <div class="row d-flex justify-content-center">
                <div class="col col-lg-8">

                    <h2 class="mb-4 text-center text-dark">Pengaturan Profil</h2>

                    <?php if ($message): ?>
                        <div class="mb-3"><?= $message ?></div>
                    <?php endif; ?>

                    <?php if ($user): ?>
                        <div class="card profile-card">
                            <div class="card-body p-4">

                                <div class="text-center mb-4">
                                    <div class="profile-pic-container">
                                        <img src="<?= htmlspecialchars($profile_pic_path) ?>" alt="Foto Profil" class="profile-pic">
                                    </div>
                                    <h4><?= htmlspecialchars($user['name']) ?></h4>
                                    <p class="text-muted"><?= ucwords(htmlspecialchars($user['role'])) ?></p>
                                </div>

                                <ul class="nav nav-tabs nav-justified mb-4 no-print" id="profileTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="data-tab" data-bs-toggle="tab" data-bs-target="#data-tab-pane" type="button" role="tab" aria-controls="data-tab-pane" aria-selected="true">
                                            <i class="fas fa-user-edit me-2"></i> Data Akun
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-tab-pane" type="button" role="tab" aria-controls="password-tab-pane" aria-selected="false">
                                            <i class="fas fa-lock me-2"></i> Ganti Kata Sandi
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="photo-tab" data-bs-toggle="tab" data-bs-target="#photo-tab-pane" type="button" role="tab" aria-controls="photo-tab-pane" aria-selected="false">
                                            <i class="fas fa-camera me-2"></i> Foto Profil
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="profileTabsContent">

                                    <div class="tab-pane fade show active" id="data-tab-pane" role="tabpanel" aria-labelledby="data-tab" tabindex="0">
                                        <form method="POST" action="">
                                            <input type="hidden" name="update_profile" value="1">

                                            <div class="mb-3">
                                                <label for="name" class="form-label">Nama Lengkap</label>
                                                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="phone" class="form-label">Telepon</label>
                                                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-save me-2"></i> Simpan Perubahan Data</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="tab-pane fade" id="password-tab-pane" role="tabpanel" aria-labelledby="password-tab" tabindex="0">
                                        <form method="POST" action="">
                                            <input type="hidden" name="change_password" value="1">

                                            <div class="mb-3">
                                                <label for="old_password" class="form-label">Kata Sandi Lama</label>
                                                <input type="password" class="form-control" id="old_password" name="old_password" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="new_password" class="form-label">Kata Sandi Baru</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <div class="form-text">Minimal 6 karakter.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="confirm_password" class="form-label">Konfirmasi Kata Sandi Baru</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-warning mt-3"><i class="fas fa-key me-2"></i> Ganti Kata Sandi</button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="tab-pane fade text-center" id="photo-tab-pane" role="tabpanel" aria-labelledby="photo-tab" tabindex="0">
                                        <form method="POST" action="" enctype="multipart/form-data">
                                            <input type="hidden" name="update_photo" value="1">

                                            <div class="mb-3">
                                                <div class="profile-pic-container mx-auto" style="width: 180px; height: 180px;">
                                                    <img src="<?= htmlspecialchars($profile_pic_path) ?>" alt="Foto Profil Saat Ini" class="profile-pic">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="profile_pic" class="form-label">Unggah Foto Baru</label>
                                                <input class="form-control" type="file" id="profile_pic" name="profile_pic" accept="image/png, image/jpeg, image/webp" required>
                                                <div class="form-text">Maks. 5MB. Format: JPG, PNG, WEBP.</div>
                                            </div>

                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-info text-white mt-3"><i class="fas fa-upload me-2"></i> Unggah Foto Profil</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="text-center mt-4 border-top pt-3">
                                    <?php 
                                        if ($user["role"] === "admin") {
                                            echo '<a href="admin/index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Kembali ke Dashboard</a>';
                                        } else {
                                            echo '<a href="index.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Kembali ke Beranda</a>';
                                        }
                                    ?>
                                </div>

                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger" role="alert">
                            <?= $error_message; ?>
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