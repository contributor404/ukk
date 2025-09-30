<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("admin_users");
include "./navbar.php";
include "./koneksi.php";

// cek login & role admin
if (!isset($_SESSION['email'])) {
  header('Location: login.php'); exit;
}
$e = $db->real_escape_string($_SESSION['email']);
$r = $db->query("SELECT role FROM users WHERE email='$e' LIMIT 1");
if (!$r || !$r->num_rows) {
  header('Location: login.php'); exit;
}
$role = $r->fetch_assoc()['role'];
if ($role !== 'admin') {
  // user bukan admin -> redirect ke homepage atau tampil pesan
  header('Location: index.php'); exit;
}

// cek role admin
$userRole = null;
if (isset($_SESSION["email"])) {
  $email = $db->real_escape_string($_SESSION["email"]);
  $u = $db->query("SELECT * FROM users WHERE email='$email' LIMIT 1");
  if ($u && $u->num_rows) $userRole = $u->fetch_assoc()["role"];
}
if ($userRole !== 'admin') { echo '<div class="container mt-4"><div class="alert alert-danger">Hanya admin.</div></div>'; exit; }

$users = $db->query("SELECT id, nama, email, role, created_at FROM users ORDER BY id ASC");
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin - Users</title></head><body>
<div class="container mt-4">
  <h2>Daftar User</h2>
  <table class="table table-bordered">
    <thead class="table-light"><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Created</th></tr></thead>
    <tbody>
      <?php while($r = $users->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['nama']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= htmlspecialchars($r['role']) ?></td>
          <td><?= htmlspecialchars($r['created_at']) ?></td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
</body></html>
