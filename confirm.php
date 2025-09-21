<?php

include "./bootstrap.php";
include "./global.php";
setLoc("confirm");
include "./navbar.php";

?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Konfirmasi Pesanan</title>
</head>

<body>
  <div class="container mt-4">
    <h2>Konfirmasi Pesanan</h2>
    <div class="row">
      <div class="col-md-6">
        <div class="card mb-3">
          <img src="https://placehold.co/400x400" class="card-img-top" alt="Hotel Dipilih">
          <div class="card-body">
            <h5 class="card-title">Hotel Nyaman B</h5>
            <p class="card-text">Lokasi: Bandung, dekat pusat kota.</p>
            <p><strong>Check-in:</strong> 25 Sept 2025</p>
            <p><strong>Check-out:</strong> 27 Sept 2025</p>
            <p><strong>Total Harga:</strong> Rp 1.000.000</p>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <form action="./kode.php" method="GET">
          <div class="mb-3">
            <label class="form-label">Jumlah Malam</label>
            <input type="number" class="form-control" min="1" placeholder="Misal: 2" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" class="form-control" placeholder="Isi nama sesuai KTP" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" placeholder="email@example.com" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Nomor HP</label>
            <input type="text" class="form-control" placeholder="08xxxxxxxxxx" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Metode Pembayaran</label>
            <select class="form-select">
              <option>Pilih...</option>
              <option>Transfer Bank</option>
              <option>Kartu Kredit</option>
              <option>e-Wallet</option>
            </select>
          </div>
          <button type="submit" class="btn btn-success w-100">Konfirmasi & Bayar</button>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>