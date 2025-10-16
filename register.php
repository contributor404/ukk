<?php
session_start();
include 'koneksi.php';
include 'bootstrap.php';

$error = '';
$success = '';

// Cek apakah form register sudah disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $koneksi->real_escape_string($_POST['name']);
    $email = $koneksi->real_escape_string($_POST['email']);
    $phone = $koneksi->real_escape_string($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Semua field wajib diisi kecuali nomor telepon!";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        // Cek apakah email sudah terdaftar
        $check_query = "SELECT * FROM users WHERE email = '$email'";
        $check_result = $koneksi->query($check_query);
        
        if ($check_result->num_rows > 0) {
            $error = "Email sudah terdaftar! Silakan gunakan email lain.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru ke database
            $insert_query = "INSERT INTO users (name, email, phone, password, role) VALUES ('$name', '$email', '$phone', '$hashed_password', 'user')";
            
            if ($koneksi->query($insert_query)) {
                $success = "Registrasi berhasil! Silakan login.";
                // Redirect ke halaman login setelah 3 detik
                header("refresh:2;url=login.php");
            } else {
                $error = "Error: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Hotel Booking</title>
    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://source.unsplash.com/random/1600x900/?hotel');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .register-card {
            max-width: 500px;
            width: 100%;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            background-color: rgba(255, 255, 255, 0.9);
            margin: 40px 0;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-header h1 {
            color: #0d6efd;
            font-weight: bold;
        }
        
        .btn-register {
            padding: 12px;
            font-weight: bold;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container" style="margin-top: 100px;">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="register-card">
                    <div class="register-header">
                        <h1>Hotel Booking</h1>
                        <p class="text-muted">Daftar akun baru</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Masukkan nama lengkap" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor Telepon (Opsional)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="text" class="form-control" id="phone" name="phone" placeholder="Masukkan nomor telepon" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password (min. 6 karakter)" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Masukkan kembali password" required>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-register">Daftar</button>
                        </div>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p>Sudah punya akun? <a href="login.php">Masuk sekarang</a></p>
                        <a href="index.php" class="d-block mt-3"><i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>