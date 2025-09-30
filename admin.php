<?php
session_start();
include "./bootstrap.php";
include "./global.php";
setLoc("admin");
include "./navbar.php";
include "./koneksi.php";

// cek login & role admin (sederhana)
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
  header('Location: index.php'); exit;
}

/**
 * Fungsi kecil untuk membuat slug dari nama + id
 */
function make_slug($name, $id) {
  $s = strtolower($name);
  $s = preg_replace('/[^a-z0-9]+/i', '-', $s);
  $s = trim($s, '-');
  return $s . '-' . $id;
}

/** helper upload sederhana **/
function upload_image($file) {
  // file = $_FILES['image_file']
  if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return ''; // tidak ada file
  // cek tipe sederhana
  $allowed = ['image/jpeg','image/png','image/gif'];
  if (!in_array($file['type'], $allowed)) return '';
  // buat folder images kalau belum ada
  $dir = __DIR__ . '/images';
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  // buat nama file unik
  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $safe = preg_replace('/[^a-z0-9\-_\.]/i','', pathinfo($file['name'], PATHINFO_FILENAME));
  $fname = time() . '_' . rand(100,999) . '_' . $safe . '.' . $ext;
  $target = $dir . '/' . $fname;
  if (move_uploaded_file($file['tmp_name'], $target)) {
    // return path relatif yang disimpan ke DB (sesuaikan dengan struktur project)
    return 'images/' . $fname;
  }
  return '';
}

/* HANDLE ADD */
if (isset($_POST['action']) && $_POST['action'] === 'add') {
  $name = $db->real_escape_string(trim($_POST['name']));
  $description = $db->real_escape_string(trim($_POST['description']));
  $harga = (int) $_POST['harga'];

  // upload image jika ada
  $imgPath = '';
  if (isset($_FILES['image_file'])) {
    $imgPath = upload_image($_FILES['image_file']);
  }

  // simpan (slug sementara kosong)
  $imgSql = $imgPath ? "'$imgPath'" : "''";
  $db->query("INSERT INTO hotel (name,image,description,harga,slug) VALUES ('$name',$imgSql,'$description',$harga,'')");
  $newId = $db->insert_id;
  $slug = make_slug($name, $newId);
  $db->query("UPDATE hotel SET slug='$slug' WHERE id=$newId");
  header("Location: admin.php?msg=added");
  exit;
}

/* HANDLE EDIT */
if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id'])) {
  $id = (int)$_POST['id'];
  $name = $db->real_escape_string(trim($_POST['name']));
  $description = $db->real_escape_string(trim($_POST['description']));
  $harga = (int) $_POST['harga'];

  // ambil data lama untuk cek gambar
  $old = $db->query("SELECT image FROM hotel WHERE id=$id LIMIT 1");
  $oldImage = '';
  if ($old && $old->num_rows) $oldImage = $old->fetch_assoc()['image'];

  // upload gambar baru jika ada
  $newImage = '';
  if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $newImage = upload_image($_FILES['image_file']);
    // jika upload sukses dan ada old image di folder images, hapus old
    if ($newImage && $oldImage && strpos($oldImage, 'images/') === 0) {
      @unlink(__DIR__ . '/' . $oldImage);
    }
  }

  // siapkan bagian update image
  if ($newImage) {
    $db->query("UPDATE hotel SET name='$name', image='$newImage', description='$description', harga=$harga WHERE id=$id");
  } else {
    $db->query("UPDATE hotel SET name='$name', description='$description', harga=$harga WHERE id=$id");
  }

  $slug = make_slug($name, $id);
  $db->query("UPDATE hotel SET slug='$slug' WHERE id=$id");
  header("Location: admin.php?msg=edited");
  exit;
}

/* HANDLE DELETE */
if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  // ambil image dulu, hapus file kalau ada
  $r = $db->query("SELECT image FROM hotel WHERE id=$id LIMIT 1");
  if ($r && $r->num_rows) {
    $img = $r->fetch_assoc()['image'];
    if ($img && strpos($img, 'images/') === 0) {
      @unlink(__DIR__ . '/' . $img);
    }
  }
  $db->query("DELETE FROM hotel WHERE id=$id");
  header("Location: admin.php?msg=deleted");
  exit;
}

/* Ambil data hotel */
$hotels = $db->query("SELECT * FROM hotel ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - Hotel</title>
</head>
<body>
  <div class="container mt-4">
    <h2>Admin Dashboard - Hotel</h2>

    <div class="mb-3">
      <a href="admin.php" class="btn btn-secondary">Daftar Hotel</a>
      <a href="admin.php?add=1" class="btn btn-primary">Tambah Hotel</a>
    </div>

    <?php if (isset($_GET['add']) && $_GET['add']==1): ?>
      <!-- FORM TAMBAH -->
      <div class="card p-3 mb-4">
        <h4>Tambah Hotel</h4>
        <form method="POST" action="admin.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Nama Hotel</label>
            <input name="name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Upload Gambar (pilih dari komputer)</label>
            <input name="image_file" type="file" accept="image/*" class="form-control">
            <div class="form-text">Tipe yg diterima: jpg, png, gif. Max ukuran disarankan 2MB.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Harga per Malam (angka)</label>
            <input name="harga" type="number" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control"></textarea>
          </div>
          <button class="btn btn-success">Tambah</button>
        </form>
      </div>

    <?php elseif (isset($_GET['edit']) && is_numeric($_GET['edit'])):
        $eid = (int)$_GET['edit'];
        $r = $db->query("SELECT * FROM hotel WHERE id=$eid LIMIT 1");
        $h = $r->fetch_assoc();
      ?>
      <!-- FORM EDIT -->
      <div class="card p-3 mb-4">
        <h4>Edit Hotel #<?= $h['id'] ?></h4>
        <form method="POST" action="admin.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" value="<?= $h['id'] ?>">
          <div class="mb-3">
            <label class="form-label">Nama Hotel</label>
            <input name="name" value="<?= htmlspecialchars($h['name']) ?>" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Gambar Saat Ini</label><br>
            <img src="<?= $h['image'] ?: 'https://placehold.co/200x120' ?>" style="width:200px;height:120px;object-fit:cover;margin-bottom:8px"><br>
            <label class="form-label">Ganti Gambar (opsional)</label>
            <input name="image_file" type="file" accept="image/*" class="form-control">
            <div class="form-text">Kalau tidak pilih file, gambar lama tetap dipakai.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Harga per Malam (angka)</label>
            <input name="harga" value="<?= (int)$h['harga'] ?>" type="number" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($h['description']) ?></textarea>
          </div>
          <button class="btn btn-primary">Simpan Perubahan</button>
        </form>
      </div>

    <?php else: ?>
      <!-- LIST HOTEL -->
      <div class="card p-3">
        <h4>Daftar Hotel</h4>
        <table class="table table-bordered">
          <thead class="table-light">
            <tr><th>ID</th><th>Nama</th><th>Harga</th><th>Slug</th><th>Kamar</th><th>Aksi</th></tr>
          </thead>
          <tbody>
            <?php while($row = $hotels->fetch_assoc()): ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td>
                  <img src="<?= $row['image'] ?: 'https://placehold.co/80x80' ?>" style="width:80px;height:50px;object-fit:cover;margin-right:8px">
                  <?= htmlspecialchars($row['name']) ?>
                </td>
                <td>Rp <?= number_format((int)$row['harga'],0,',','.') ?></td>
                <td><?= htmlspecialchars($row['slug']) ?></td>
                <td><?= htmlspecialchars($row['total_kamar']) ?></td>
                <td>
                  <a class="btn btn-sm btn-warning" href="admin.php?edit=<?= $row['id'] ?>">Edit</a>
                  <a class="btn btn-sm btn-danger" href="admin.php?delete=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus?')">Hapus</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>
</body>
</html>
