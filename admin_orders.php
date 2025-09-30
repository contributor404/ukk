<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("admin");
include "./navbar.php";
include "./koneksi.php";

// cek login & role admin
if(!isset($_SESSION['email'])){
  header("Location: login.php"); exit;
}
$e = $db->real_escape_string($_SESSION['email']);
$r = $db->query("SELECT role,id FROM users WHERE email='$e' LIMIT 1");
if(!$r || !$r->num_rows){ header("Location: login.php"); exit; }
$role = $r->fetch_assoc()['role'];
if ($role !== 'admin') { echo "<div class='alert alert-danger'>Akses ditolak.</div>"; exit; }

// Approve flow dengan pemeriksaan stok + konflik kamar
if (isset($_GET['approve'])) {
  $oid = (int)$_GET['approve'];
  $o_r = $db->query("SELECT * FROM orders WHERE id=$oid LIMIT 1");
  if (!$o_r || !$o_r->num_rows) {
    $_msg = "Order tidak ditemukan.";
  } else {
    $order = $o_r->fetch_assoc();
    $hotel_id = (int)$order['hotel_id'];
    $hotel_q = $db->query("SELECT total_kamar FROM hotel WHERE id=$hotel_id LIMIT 1");
    if (!$hotel_q || !$hotel_q->num_rows) {
      $_msg = "Hotel untuk order ini tidak ditemukan.";
    } else {
      $hotel = $hotel_q->fetch_assoc();
      $total_kamar = (int)$hotel['total_kamar'];

      // Hitung berapa order sudah approved untuk hotel ini
      $cnt_r = $db->query("SELECT COUNT(*) AS cnt FROM orders WHERE hotel_id=$hotel_id AND status='approved'");
      $cnt_row = $cnt_r ? $cnt_r->fetch_assoc() : null;
      $approved_count = $cnt_row ? (int)$cnt_row['cnt'] : 0;

      if ($approved_count >= $total_kamar) {
        // tidak ada kamar lagi
        $db->query("UPDATE orders SET status='rejected' WHERE id=$oid");
        $_msg = "Tidak bisa approve: hotel sudah penuh. Order otomatis di-rejected.";
      } else {
        // Periksa apakah kamar_no yang tersimpan sudah bentrok dengan order lain yg sudah approved
        $current_kamar = $order['kamar_no'] ? (int)$order['kamar_no'] : null;

        // Ambil daftar kamar yang sudah dipakai oleh order approved (exclude current order)
        $used = [];
        $ru = $db->query("SELECT kamar_no FROM orders WHERE hotel_id=$hotel_id AND status='approved' AND id != $oid AND kamar_no IS NOT NULL");
        if ($ru) {
          while ($rr = $ru->fetch_assoc()) {
            $kno = (int)$rr['kamar_no'];
            if ($kno > 0) $used[$kno] = true;
          }
        }

        // Jika current_kamar kosong atau bentrok -> cari kamar kosong lain
        $assign_kamar = null;
        if ($current_kamar && !isset($used[$current_kamar])) {
          // kamar yang dicatat aman, gunakan itu
          $assign_kamar = $current_kamar;
        } else {
          // cari kamar available
          for ($i = 1; $i <= $total_kamar; $i++) {
            if (!isset($used[$i]) && ($current_kamar !== $i)) {
              $assign_kamar = $i;
              break;
            }
          }
        }

        if ($assign_kamar === null) {
          // unexpected: tidak menemukan kamar meskipun approved_count < total_kamar (race condition)
          $db->query("UPDATE orders SET status='rejected' WHERE id=$oid");
          $_msg = "Tidak bisa allocate kamar saat approval (konflik). Order di-rejected.";
        } else {
          // Update order dengan kamar yang dialokasikan dan set status approved
          $stmt = $db->prepare("UPDATE orders SET status='approved', kamar_no = ? WHERE id = ?");
          $stmt->bind_param("ii", $assign_kamar, $oid);
          $stmt->execute();
          $_msg = "Order #$oid berhasil di-approve. Kamar diberikan: $assign_kamar.";
        }
      }
    }
  }
}

// Mark paid (opsional)
if (isset($_GET['mark_paid'])) {
  $oid = (int)$_GET['mark_paid'];
  $db->query("UPDATE orders SET status='paid' WHERE id=$oid");
  $_msg = "Order $oid ditandai sebagai dibayar.";
}

// Ambil semua order untuk display
$orders_q = $db->query("SELECT o.*, h.name AS hotel_name, u.email AS user_email, u.nama AS user_name FROM orders o LEFT JOIN hotel h ON o.hotel_id=h.id LEFT JOIN users u ON o.user_email = u.email ORDER BY o.created_at DESC");

?>
<!doctype html>
<html><head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Orders</title>
</head><body>
<div class="container mt-4">
  <h2>Daftar Orders</h2>

  <?php if (isset($_msg)) { ?>
    <div class="alert alert-info"><?php echo htmlspecialchars($_msg); ?></div>
  <?php } ?>

  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>ID</th><th>Kode</th><th>Hotel</th><th>User</th><th>Nama Pemesan</th><th>Kamar No</th><th>Status</th><th>Created</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $orders_q->fetch_assoc()) { ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['code']) ?></td>
        <td><?= htmlspecialchars($row['hotel_name']) ?></td>
        <td><?= htmlspecialchars($row['user_email'] ?: $row['user_name']) ?></td>
        <td><?= htmlspecialchars($row['nama_pemesan']) ?></td>
        <td>
          <?php
            if ($row['kamar_no']) {
              echo "No. " . (int)$row['kamar_no'];
            } else {
              echo "<em>(belum dialokasikan)</em>";
            }
          ?>
        </td>
        <td><?= htmlspecialchars($row['status']) ?></td>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
        <td>
          <?php if($row['status']=='pending'){ ?>
            <a href="?approve=<?= $row['id'] ?>" class="btn btn-sm btn-success">Approve</a>
          <?php } elseif($row['status']=='approved'){ ?>
            <a href="?mark_paid=<?= $row['id'] ?>" class="btn btn-sm btn-primary">Mark Paid</a>
          <?php } ?>
        </td>
      </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
</body>
</html>
