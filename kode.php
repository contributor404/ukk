<?php

include "./bootstrap.php";
include "./global.php";
setLoc("kode");
include "./navbar.php";

?>


<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kode Booking</title>
</head>

<body>
  <div class="container mt-5 text-center">
    <div class="card p-4 shadow-sm">
      <h2 class="text-success">Pesanan Berhasil!</h2>
      <p class="mt-2">Terima kasih sudah memesan melalui <strong>Luxy</strong></p>

      <h4 class="mt-4">Kode Booking Anda:</h4>
      <div class="alert alert-info fs-3 fw-bold">HF123456</div>

      <p><strong>Hotel:</strong> Hotel Nyaman B</p>
      <p><strong>Check-in:</strong> 25 Sept 2025 &nbsp; | &nbsp; <strong>Check-out:</strong> 27 Sept 2025</p>
      <p><strong>Total:</strong> Rp 1.000.000</p>

      <div class="mt-4">
        <a href="./index.php" class="btn btn-secondary me-2">Kembali ke Halaman Utama</a>
        <button onclick="window.print()" class="btn btn-primary">Print</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>