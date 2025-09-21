<?php
include "./bootstrap.php";
include "./koneksi.php";

if (isset($_POST["login"])) {
  $email = $_POST["email"];
  $password = $_POST["password"];

  $user = $db->query("SELECT * FROM `users` WHERE username='$email' OR email='$email'");

  if ($user->num_rows > 0) {
    $user_assoc = $user->fetch_assoc();
    $password_db = $user_assoc["password"];
    $isVerify = password_verify($password, $password_db);
    if ($isVerify) {
      header("Location: index.php");
    }
  }
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
      <h3 class="card-title text-center mt-4">Login untuk Melanjutkan</h3>
      <?php
      if ((isset($user) && $user->num_rows == 0) || (isset($isVerify) && !$isVerify)) echo '<p class="text-danger text-center">Email / Password salah!</p>';
      ?>
      <form class="mt-5" action="" method="POST">
        <div class="mb-3">
          <label for="email" class="form-label">Email / Username</label>
          <input type="text" class="form-control" name="email" id="email" aria-describedby="emailHelp" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" name="password" id="password" required>
        </div>
        <hr />
        <p>Belum punya akun? <a href="./register.php">Daftar</a></p>
        <button type="submit" name="login" class="btn btn-primary btn-full">Submit</button>
      </form>
    </div>
  </div>
</body>

</html>