<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("admin_check");
include "./navbar.php";
include "./koneksi.php";

// kalau admin masukkan kode booking lewat form
if (isset($_POST['booking_code'])) {
  $code = $db->real_escape_string($_POST['booking_code']);
  $query = $db->query("SELECT * FROM orders WHERE code = '$code'");

  if ($query->num_rows > 0) {
    $order = $query->fetch_assoc();
    if (strtotime($order['expired_date']) > time()) {
      $status = "✅ Kode booking masih valid";
    } else {
      $status = "❌ Kode booking sudah expired";
    }
  } else {
    $status = "❌ Kode booking tidak ditemukan";
  }
}
?>

<!DOCTYPE html>
<html>

<head>
  <title>Admin Cek Kode Booking</title>
</head>

<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-lg rounded-3">
          <div class="card-body">
            <h3 class="text-center mb-4">🔎 Cek Validasi Kode Booking</h3>
            <form method="POST">
              <div class="mb-3">
                <input type="text" name="booking_code" class="form-control" placeholder="Masukkan kode booking" required>
              </div>
              <button type="submit" class="btn btn-primary w-100">Cek</button>
            </form>
            <?php if (isset($status)) { ?>
              <div class="mt-4 text-center">
                <?= $status ?>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>

</html>