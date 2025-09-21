<?php

include "./bootstrap.php";
include "./global.php";
setLoc("index");
include "./navbar.php";

?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cari Hotel | luxy</title>
</head>

<body>
  <div class="container mt-4">
    <h2>Cari Tiket Hotel</h2>
    <form class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Kota / Tujuan</label>
        <input type="text" class="form-control" placeholder="Misal: Jakarta">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">Cari</button>
      </div>
    </form>
  </div>

  <div class="container mt-5">
    <h3>Rekomendasi Hotel</h3>
    <div class="row mt-5">
      <div class="col-md-4">
        <div class="card mb-3">
          <img src="https://placehold.co/400x400" class="card-img-top" alt="Hotel 1">
          <div class="card-body">
            <h5 class="card-title">Hotel Murah A</h5>
            <p class="card-text">Lokasi di Jakarta, dekat pusat kota.</p>
            <p><strong>Rp 350.000 / malam</strong></p>
            <a href="./confirm.php" class="btn btn-success">Pesan</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card mb-3">
          <img src="https://placehold.co/400x400" class="card-img-top" alt="Hotel 2">
          <div class="card-body">
            <h5 class="card-title">Hotel Nyaman B</h5>
            <p class="card-text">Fasilitas lengkap dan nyaman.</p>
            <p><strong>Rp 500.000 / malam</strong></p>
            <a href="#" class="btn btn-success">Pesan</a>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card mb-3">
          <img src="https://placehold.co/400x400" class="card-img-top" alt="Hotel 3">
          <div class="card-body">
            <h5 class="card-title">Hotel Mewah C</h5>
            <p class="card-text">Bintang 5 dengan fasilitas premium.</p>
            <p><strong>Rp 1.200.000 / malam</strong></p>
            <a href="#" class="btn btn-success">Pesan</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>