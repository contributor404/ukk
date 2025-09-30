<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("confirm");
include "./navbar.php";
include "./koneksi.php";

// paksa login dulu sebelum bisa pesan
if (!isset($_SESSION['email'])) {
  $next = 'confirm.php?id=' . $hotel['id'];
  header('Location: login.php?next=' . urlencode($next));
  exit;
}

if (!isset($_GET['id'])) {
  echo '<div class="container mt-4"><div class="alert alert-danger">Hotel tidak dipilih.</div></div>';
  exit;
}
$id = (int)$_GET['id'];
$hotel_q = $db->query("SELECT * FROM hotel WHERE id=$id LIMIT 1");
if (!$hotel_q || !$hotel_q->num_rows) {
  echo '<div class="container mt-4"><div class="alert alert-danger">Hotel tidak ditemukan.</div></div>';
  exit;
}
$hotel = $hotel_q->fetch_assoc();

$user = null;
if (isset($_SESSION['email'])) {
  $e = $db->real_escape_string($_SESSION['email']);
  $u = $db->query("SELECT * FROM users WHERE email='$e' LIMIT 1");
  if ($u && $u->num_rows) $user = $u->fetch_assoc();
}

$already = false;
if (isset($_SESSION['email'])) {
  $em = $db->real_escape_string($_SESSION['email']);
  $check = $db->query("SELECT * FROM orders WHERE hotel_id={$hotel['id']} AND user_email='$em' LIMIT 1");
  if ($check && $check->num_rows) {
    $already = true;
  }
}

if ($already) {
  echo '<div class="container mt-4">';
  echo '<div class="alert alert-warning">Kamu sudah pernah booking hotel ini sebelumnya. Tidak bisa booking lagi.</div>';
  echo '<a class="btn btn-secondary" href="history.php">Lihat Riwayat Booking</a> ';
  echo '<a class="btn btn-primary" href="index.php">Kembali</a>';
  echo '</div>';
  return;
}
?>

<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Konfirmasi - <?= htmlspecialchars($hotel['name']) ?></title>
</head>
<body>
<div class="container mt-4">
  <h2>Konfirmasi Pemesanan</h2>
  <div class="card p-3 mb-3">
    <div style="display:flex;gap:12px;align-items:center">
      <img src="<?= $hotel['image'] ?: 'https://placehold.co/200x120' ?>" style="width:200px;height:120px;object-fit:cover">
      <div>
        <h4><?= htmlspecialchars($hotel['name']) ?></h4>
        <p>Harga / malam: Rp <?= number_format((int)$hotel['harga'],0,',','.') ?></p>
      </div>
    </div>
  </div>

  <form action="order.php" method="POST">
    <input type="hidden" name="hotel_id" value="<?= $hotel['id'] ?>">
    <div class="mb-3">
      <label>Jumlah Malam</label>
      <input type="number" name="nights" class="form-control" value="1" min="1" required>
    </div>
    <div class="mb-3">
      <label>Nama Pemesan</label>
      <input type="text" name="nama_pemesan" class="form-control" value="<?= $user ? htmlspecialchars($user['nama']) : '' ?>" required>
    </div>
    <div class="mb-3">
      <label>Email</label>
      <input type="email" name="email_pemesan" class="form-control" value="<?= $user ? htmlspecialchars($user['email']) : '' ?>" required>
    </div>
    <div class="mb-3">
      <label>Nomor HP</label>
      <input type="text" name="phone" class="form-control" placeholder="08xxxx" required>
    </div>
    <div class="mb-3">
      <label>Metode Pembayaran</label>
      <select name="payment_method" class="form-select" required>
        <option value="">Pilih...</option>
        <option>Transfer Bank</option>
        <option>Kartu Kredit</option>
        <option>e-Wallet</option>
      </select>
    </div>
    <button type="submit" name="booking" class="btn btn-success">Konfirmasi & Buat Kode</button>
  </form>

</div>
</body>
</html>