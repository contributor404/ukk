<?php
session_start();

include "./bootstrap.php";
include "./koneksi.php";

if (isset($_GET["logout"])) {
  $_SESSION["email"] = null;
  $_SESSION["user_id"] = null;
  header('Location: login.php');
}

$next = '';
if (isset($_REQUEST['next'])) $next = $_REQUEST['next'];
if (isset($_POST['login'])) {
  $email = $db->real_escape_string(trim($_POST['email']));
  $pass = $db->real_escape_string(trim($_POST['password']));
  $q = $db->query("SELECT * FROM users WHERE (email='$email' OR username='$email') AND password=md5('$pass') LIMIT 1");
  if ($q && $q->num_rows) {
    $u = $q->fetch_assoc();
    $_SESSION['email'] = $u['email'];
    $_SESSION['user_id'] = $u['id'];
    // redirect berdasarkan role atau next
    if (isset($u['role']) && $u['role'] === 'admin') {
      header('Location: admin.php');
      exit;
    } else {
      if ($next) {
        header('Location: ' . $next);
        exit;
      } else {
        header('Location: index.php');
        exit;
      }
    }
  } else {
    echo '<div class="alert alert-danger">User tidak ditemukan.</div>';
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
        <input type="hidden" name="next" value="<?= isset($_GET['next']) ? htmlspecialchars($_GET['next']) : '' ?>">
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