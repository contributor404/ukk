<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("index");
include "./navbar.php";
include "./koneksi.php";

$hotels = $db->query("SELECT * FROM hotel ORDER BY id ASC");
?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Hotel</title>
</head>

<body>
  <div class="container mt-4">
    <h2>Daftar Hotel</h2>
    <div class="row">
      <?php while ($h = $hotels->fetch_assoc()): ?>
        <div class="col-md-4 mb-3">
          <div class="card">
            <img src="<?= $h['image'] ?: 'https://placehold.co/600x400' ?>" style="width:100%;height:180px;object-fit:cover">
            <div class="card-body">
              <h5><?= htmlspecialchars($h['name']) ?></h5>
              <p>Rp <?= number_format((int)$h['harga'], 0, ',', '.') ?></p>
              <p><?= htmlspecialchars(substr($h['description'], 0, 80)) ?>...</p>
              <?php if (isset($_SESSION['email'])): ?>
                <a href="confirm.php?id=<?= $h['id'] ?>" class="btn btn-primary">Pesan</a>
              <?php else: ?>
                <a href="login.php?next=<?= urlencode('confirm.php?id=' . $h['id']) ?>" class="btn btn-secondary">Login untuk Pesan</a>
              <?php endif; ?>

            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</body>

</html>