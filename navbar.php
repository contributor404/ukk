<?php
if (!isset($db)) include "koneksi.php";

// ambil role & nama (simple)
$role = 'user';
$nama_user = '';

if (isset($_SESSION['email']) && isset($db)) {
  $e = $db->real_escape_string($_SESSION['email']);
  $q = $db->query("SELECT * FROM users WHERE email='$e' LIMIT 1");
  if ($q && $q->num_rows) {
    $u = $q->fetch_assoc();
    $role = $u['role'];
    $nama_user = $u['nama'];
  }
}

?>
<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php" style="font-size: 30px; font-weight: bold;">
      <?php echo (isset($loc) && $loc == "admin") ? "Admin Dashboard" : "LuxStay" ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
      aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if (isset($_SESSION['email']) && $role === 'admin'): ?>
          <!-- ADMIN MENUS: hanya untuk admin -->
          <li class="nav-item"><a class="nav-link" href="admin.php">Daftar Hotel</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_orders.php">Daftar Pesanan</a></li>
          <li class="nav-item"><a class="nav-link" href="admin_users.php">Daftar User</a></li>
          <li class="nav-item"><a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true"><?= htmlspecialchars($nama_user ?: $_SESSION['email']) ?></a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="login.php?logout=1">Logout</a></li>

        <?php elseif (isset($_SESSION['email'])): ?>
          <!-- USER BIASA: hanya menu user -->
          <li class="nav-item"><a class="nav-link" href="index.php">Cari Hotel</a></li>
          <li class="nav-item"><a class="nav-link" href="history.php">Riwayat</a></li>
          <li class="nav-item"><a class="nav-link disabled" href="#" tabindex="-1" aria-disabled="true"><?= htmlspecialchars($nama_user ?: $_SESSION['email']) ?></a></li>
          <li class="nav-item"><a class="nav-link text-danger" href="login.php?logout=1">Logout</a></li>

        <?php else: ?>
          <!-- PENGUNJUNG: belum login -->
          <li class="nav-item"><a class="nav-link" href="index.php">Cari Hotel</a></li>
          <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
        <?php endif; ?>

      </ul>
    </div>
  </div>
</nav>
