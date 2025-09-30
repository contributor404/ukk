<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("history");
include "./navbar.php";
include "./koneksi.php";

// paksa login dulu
if (!isset($_SESSION['email'])) {
  header('Location: login.php'); exit;
}

$email = $db->real_escape_string($_SESSION['email']);

// Ambil semua order user ini
$orders = $db->query("SELECT o.*, h.name AS hotel_name, h.slug AS hotel_slug, h.total_kamar FROM orders o LEFT JOIN hotel h ON o.hotel_id = h.id WHERE o.user_email = '$email' ORDER BY o.created_at DESC");

?>
<!doctype html>
<html><head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Riwayat Booking</title>
</head><body>
<div class="container mt-4">
  <h2>Riwayat Booking</h2>

  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>ID</th><th>Kode</th><th>Hotel</th><th>Check-in noches</th><th>Total</th><th>Kamar No</th><th>Status</th><th>Waktu</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($orders && $orders->num_rows) {
        while ($row = $orders->fetch_assoc()) {
          $kamar_text = $row['kamar_no'] ? "No. " . (int)$row['kamar_no'] : "<em>(belum dialokasikan)</em>";
      ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['code']) ?></td>
        <td><?= htmlspecialchars($row['hotel_name']) ?></td>
        <td><?= (int)$row['nights'] ?></td>
        <td><?= (int)$row['total'] ?></td>
        <td><?= $kamar_text ?></td>
        <td><?= htmlspecialchars($row['status']) ?></td>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
      </tr>
      <?php }
      } else { ?>
      <tr><td colspan="8" class="text-center">Belum ada pemesanan.</td></tr>
      <?php } ?>
    </tbody>
  </table>
</div>
</body>
</html>
