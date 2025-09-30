<?php
session_start();
include "./koneksi.php";

// Pastikan user login
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

if (isset($_POST['hotel_id'])) {
  $hid = (int)$_POST['hotel_id'];

  // ambil data hotel (total_kamar)
  $q = $db->query("SELECT * FROM hotel WHERE id = $hid LIMIT 1");
  if (!$q || !$q->num_rows) {
    echo "<div class='alert alert-danger'>Hotel tidak ditemukan.</div>";
    exit;
  }
  $hotel = $q->fetch_assoc();
  $total_kamar = (int)$hotel['total_kamar'];
  if ($total_kamar <= 0) {
    echo "<div class='alert alert-danger'>Konfigurasi hotel tidak valid (total kamar = 0).</div>";
    exit;
  }

  // Ambil daftar kamar yang sudah dipakai oleh order dengan status yang mengunci kamar
  // Kita anggap status: pending, approved, paid semuanya "mengunci" kamar sehingga tidak bisa double-book.
  $used = [];
  $r = $db->query("SELECT kamar_no FROM orders WHERE hotel_id = $hid AND kamar_no IS NOT NULL AND status IN ('pending','approved','paid')");
  if ($r) {
    while ($row = $r->fetch_assoc()) {
      $kno = (int)$row['kamar_no'];
      if ($kno > 0) $used[$kno] = true;
    }
  }

  // Cari kamar tersedia (smallest index yang belum dipakai)
  $kamar_tersedia = null;
  for ($i = 1; $i <= $total_kamar; $i++) {
    if (!isset($used[$i])) {
      $kamar_tersedia = $i;
      break;
    }
  }

  if ($kamar_tersedia === null) {
    echo "<div class='alert alert-danger'>Maaf, semua kamar hotel ini sudah penuh saat ini.</div>";
    exit;
  }

  // Simpan pesanan dengan kamar_no yang dipilih (status pending)
  $uid = (int)$_SESSION['user_id'];

  // Generate kode unik sederhana (bisa diganti sesuai kebutuhan)
  $code = 'ORD' . time() . rand(100, 999);

  $stmt = $db->prepare("INSERT INTO orders (code, hotel_id, user_email, nama_pemesan, phone, nights, total, payment_method, status, kamar_no) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
  // ambil email/nama dari users tabel jika tersedia
  $email = '';
  $nama = '';
  $phone = '';
  $uq = $db->query("SELECT email, nama, phone FROM users WHERE id = $uid LIMIT 1");
  if ($uq && $uq->num_rows) {
    $uu = $uq->fetch_assoc();
    $email = $uu['email'];
    $nama = $uu['nama'];
    $phone = $uu['phone'];
  }

  // ambil jumlah malam
  $nights = isset($_POST['nights']) ? (int)$_POST['nights'] : 1;

  // total = harga hotel * malam
  $harga = (int)$hotel['harga'];
  $total = $harga * $nights;

  echo $total;
  echo $harga;

  // metode pembayaran
  $payment_method = isset($_POST['payment_method']) ? $db->real_escape_string($_POST['payment_method']) : null;

  $stmt->bind_param("sissiiisi", $code, $hid, $email, $nama, $phone, $nights, $total, $payment_method, $kamar_tersedia);
  $ok = $stmt->execute();

  if (!$ok) {
    echo "<div class='alert alert-danger'>Gagal menyimpan pesanan. Silakan coba lagi.</div>";
    exit;
  }

  // Beri tahu user nomor kamar yang diberikan (karena user minta info lokasi/index kamar)
  // Tampilkan halaman ringkasan singkat (atau arahkan ke history.php)
  $_SESSION['last_order_id'] = $stmt->insert_id;

  // Harus ke kode dulu untuk detail pemesanan ( tapi logika kodenya jangan diubah, contohnya pending jangan generate dulu kode bookingya, biarin saja )
  header("Location: kode.php");
  exit;
}
