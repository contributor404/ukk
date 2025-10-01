<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("history");
include "./navbar.php";
include "./koneksi.php";

// paksa login dulu
if (!isset($_SESSION['email'])) {
  header('Location: login.php');
  exit;
}

$email = $db->real_escape_string($_SESSION['email']);

// Ambil semua order user ini
$orders = $db->query("SELECT o.*, h.name AS hotel_name, h.total_kamar, h.alamat
                      FROM orders o 
                      LEFT JOIN hotel h ON o.hotel_id = h.id 
                      WHERE o.user_email = '$email'
                      ORDER BY o.created_at DESC");

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Riwayat Booking</title>
  <style>
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, .6);
      z-index: 9999;
    }

    .modal-content {
      background: #fff;
      width: 600px;
      margin: 50px auto;
      padding: 20px;
      border-radius: 8px;
      position: relative;
    }

    .modal-close {
      position: absolute;
      top: 10px;
      right: 15px;
      cursor: pointer;
      font-size: 18px;
    }

    .print-area {
      font-family: Arial;
      max-width: 500px;
      margin: auto;
    }

    .print-area table {
      width: 100%;
      border-collapse: collapse;
    }

    .print-area td {
      padding: 4px 6px;
      vertical-align: top;
    }

    .btn {
      padding: 5px 10px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .btn-success {
      background: green;
      color: #fff;
    }

    .btn-primary {
      background: blue;
      color: #fff;
    }

    .btn-secondary {
      background: #777;
      color: #fff;
    }
  </style>
</head>

<body>
  <div class="container mt-4">
    <h2>Riwayat Booking</h2>

    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>ID</th>
          <th>Kode</th>
          <th>Hotel</th>
          <th>Check-in noches</th>
          <th>Total</th>
          <th>Kamar No</th>
          <th>Status</th>
          <th>Waktu</th>
          <th>Expired</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($orders && $orders->num_rows) {
          while ($row = $orders->fetch_assoc()) {
            $kamar_text = $row['kamar_no'] ? "No. " . (int)$row['kamar_no'] : "<em>(belum dialokasikan)</em>";
            $now = new DateTime();
            $target = new DateTime(htmlspecialchars($row['expired_date']));
            // Hitung selisih
            $diff = $now->diff($target);
            $row["expired"] = $diff->format('%a hari, %h jam, %i menit, %s detik');
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
              <td><?= htmlspecialchars($row["expired"]) ?></td>
              <td>
                <?php if (strtolower($row['status']) === "approved" || strtolower($row['status']) === "paid") { ?>
                  <button class="btn btn-success btn-sm" onclick='showStruk(<?= json_encode($row) ?>)'>Cetak</button>
                <?php } else { ?>
                  <span class="text-muted">Menunggu</span>
                <?php } ?>
              </td>
            </tr>
          <?php }
        } else { ?>
          <tr>
            <td colspan="9" class="text-center">Belum ada pemesanan.</td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

  <!-- Modal Struk -->
  <div id="strukModal" class="modal">
    <div class="modal-content">
      <span class="modal-close" onclick="closeStruk()">✖</span>
      <div id="strukContent"></div>
      <div style="text-align:center; margin-top:20px;">
        <button onclick="printStruk()" class="btn btn-primary">Print</button>
        <button onclick="closeStruk()" class="btn btn-secondary">Tutup</button>
      </div>
    </div>
  </div>

  <script>
    function showStruk(order) {
      // order di sini adalah object PHP yang di-encode jadi JSON
      let html = `
    <div class="print-area">
      <h2 style="text-align:center;">Struk Booking Hotel</h2>
      <table>
        <tr><td><b>Kode</b></td><td>${order.code}</td></tr>
        <tr><td><b>Hotel</b></td><td>${order.hotel_name}</td></tr>
        <tr><td><b>Alamat</b></td><td>${order.alamat ?? '-'}</td></tr>
        <tr><td><b>Nama Pemesan</b></td><td>${order.nama_pemesan ?? '-'}</td></tr>
        <tr><td><b>Email</b></td><td>${order.user_email}</td></tr>
        <tr><td><b>Telepon</b></td><td>${order.phone ?? '-'}</td></tr>
        <tr><td><b>Malam</b></td><td>${order.nights}</td></tr>
        <tr><td><b>Total</b></td><td>Rp ${parseInt(order.total).toLocaleString()}</td></tr>
        <tr><td><b>Status</b></td><td>${order.status}</td></tr>
        <tr><td><b>Expired</b></td><td>${order.expired}</td></tr>
        <tr><td><b>Waktu Pesan</b></td><td>${order.created_at}</td></tr>
      </table>
      <p style="text-align:center; font-size:12px; margin-top:20px;">
        Terima kasih, struk ini sah tanpa tanda tangan
      </p>
    </div>`;
      document.getElementById("strukContent").innerHTML = html;
      document.getElementById("strukModal").style.display = "block";
    }

    function closeStruk() {
      document.getElementById("strukModal").style.display = "none";
    }

    function printStruk() {
      var printContent = document.getElementById("strukContent").innerHTML;
      var w = window.open('', '', 'width=800,height=600');
      w.document.write('<html><head><title>Cetak Struk</title></head><body>');
      w.document.write(printContent);
      w.document.write('</body></html>');
      w.document.close();
      w.print();
    }
  </script>
</body>

</html>