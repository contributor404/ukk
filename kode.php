<?php
session_start();
include "./koneksi.php";

// Pastikan user login
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit;
}

// Ambil order ID terakhir dari session / query string
$order_id = 0;
if (isset($_GET['id'])) {
  $order_id = (int)$_GET['id'];
} elseif (isset($_SESSION['last_order_id'])) {
  $order_id = (int)$_SESSION['last_order_id'];
}

if ($order_id <= 0) {
  echo "<div class='container mt-4'><div class='alert alert-danger'>Pesanan tidak ditemukan.</div></div>";
  exit;
}

// Ambil detail pesanan
$email = $db->real_escape_string($_SESSION['email']);
$q = $db->query("SELECT o.*, h.name AS hotel_name
                   FROM orders o
                   JOIN hotel h ON o.hotel_id = h.id
                   WHERE o.id=$order_id AND o.user_email='$email'
                   LIMIT 1");
if (!$q || !$q->num_rows) {
  echo "<div class='container mt-4'><div class='alert alert-danger'>Pesanan tidak valid atau bukan milikmu.</div></div>";
  exit;
}
$order = $q->fetch_assoc();
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detail Pesanan</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
  <h2>Detail Pemesanan</h2>
  <div class="card p-3">
    <p>Terima kasih, <strong><?= htmlspecialchars($order['nama_pemesan']) ?></strong>.</p>
    <p>Hotel: <strong><?= htmlspecialchars($order['hotel_name']) ?></strong></p>
    <p>Jumlah malam: <strong><?= (int)$order['nights'] ?></strong></p>
    <p>Total biaya: <strong>Rp <?= number_format((int)$order['total'],0,',','.') ?></strong></p>
    <p>Status: <span class="badge bg-warning text-dark"><?= htmlspecialchars($order['status']) ?></span></p>
    <p>Nomor kamar yang kamu dapatkan: <strong><?= (int)$order['kamar_no'] ?></strong></p>
    <p>Kode booking (akan aktif setelah admin approve): 
       <strong><?= $order['code'] ? htmlspecialchars($order['code']) : '-' ?></strong></p>

    <div class="mt-3">
      <a class="btn btn-primary" href="history.php">Lihat Riwayat</a>
      <a class="btn btn-secondary" href="index.php">Kembali Cari Hotel</a>
    </div>
  </div>
</div>
</body>
</html>
