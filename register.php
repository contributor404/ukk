<?php
include "./bootstrap.php";
include "./koneksi.php";

if (isset($_POST["register"])) {
  $name = $_POST["name"];
  $username = $_POST["username"];
  $email = $_POST["email"];
  $password = $_POST["password"];

  $hash_password = password_hash($password, PASSWORD_BCRYPT);

  $db->query("INSERT INTO `users` (`nama`, `username`, `email`, `password`, `role`) VALUES ('$name','$username','$email','$hash_password','user')");

  header("Location: login.php");
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Luxy</title>
  <style>
    body {
      display: flex;
      width: 100vw;
      height: 100vh;
      justify-content: center;
      align-items: center;
    }

    .btn-full {
      width: 100%;
    }
  </style>
</head>

<body>
  <div class="card" style="width: 30rem;">
    <div class="card-body">
      <h3 class="card-title text-center mb-5 mt-4">Register</h3>
      <form action="" method="POST">
        <div class="mb-3">
          <label for="name" class="form-label">Nama Lengkap</label>
          <input type="name" class="form-control" name="name" id="name" required>
        </div>
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="username" class="form-control" name="username" id="username" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Alamat Email</label>
          <input type="email" class="form-control" name="email" id="email" aria-describedby="emailHelp" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" name="password" id="password" required>
        </div>
        <hr />
        <p>Sudah punya akun? <a href="./login.php">Login</a></p>
        <button type="submit" name="register" class="btn btn-primary btn-full">Daftar</button>
      </form>
    </div>
  </div>
</body>

</html>