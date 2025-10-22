<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// --- Logika CRUD Pengguna ---

// 1. Tambah Pengguna
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];

    // Cek duplikasi email
    $check = $koneksi->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 0) {
        $stmt = $koneksi->prepare("INSERT INTO users (name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $password, $role);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Pengguna berhasil ditambahkan!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menambahkan pengguna: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Email sudah terdaftar!</div>";
    }
    $check->close();
}

// 2. Edit Pengguna
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    $sql = "UPDATE users SET name=?, email=?, phone=?, role=? ";
    $params = "ssssi";
    $bind_params = [$name, $email, $phone, $role];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password=? ";
        $params = "sssssi";
        $bind_params[] = $hashed_password;
    }
    
    $sql .= "WHERE id=?";
    $bind_params[] = $id;

    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param($params, ...$bind_params);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Pengguna berhasil diperbarui!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal memperbarui pengguna: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// 3. Hapus Pengguna
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    // Pencegahan agar Admin tidak menghapus dirinya sendiri (ID 2 berdasarkan hotel_booking.sql)
    if ($id == 2) { 
        $message = "<div class='alert alert-danger'>Admin Utama tidak dapat dihapus!</div>";
    } else {
        $stmt = $koneksi->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Pengguna berhasil dihapus!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menghapus pengguna. Data pesanan mungkin terikat.</div>";
        }
        $stmt->close();
    }
    // Refresh halaman untuk menghindari eksekusi ulang delete
    header("Location: kelola_user.php?message=" . urlencode(strip_tags($message)));
    exit;
}

// Tangani pesan dari redirect
if (isset($_GET['message'])) {
    $message = "<div class='alert alert-info'>" . htmlspecialchars($_GET['message']) . "</div>";
}

// 4. Ambil Daftar Pengguna (Tampil)
$query = "SELECT id, name, email, phone, role, created_at FROM users ORDER BY created_at DESC";
$users_result = $koneksi->query($query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Hotel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <?php include 'sidebar.php'; ?>

        <div id="page-content-wrapper" class="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-transparent py-4 px-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-align-left primary-text fs-4 me-3" id="menu-toggle"></i>
                    <h2 class="fs-2 m-0">Kelola Pengguna</h2>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <?= $message ?>
                <div class="row my-4">
                    <h3 class="fs-4 mb-3">Daftar Pengguna dan Admin</h3>
                    <div class="col text-end mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Tambah Pengguna/Admin
                        </button>
                    </div>
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table bg-white rounded shadow-sm table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col" width="50">#</th>
                                        <th scope="col">Nama</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Telepon</th>
                                        <th scope="col">Role</th>
                                        <th scope="col">Terdaftar</th>
                                        <th scope="col" width="100">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($users as $user): ?>
                                        <tr>
                                            <th scope="row"><?= $no++ ?></th>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= htmlspecialchars($user['phone']) ?></td>
                                            <td>
                                                <span class="badge <?= $user['role'] == 'admin' ? 'bg-danger' : 'bg-success' ?>">
                                                    <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info text-white edit-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editUserModal"
                                                        data-id="<?= $user['id'] ?>"
                                                        data-name="<?= htmlspecialchars($user['name']) ?>"
                                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                                        data-phone="<?= htmlspecialchars($user['phone']) ?>"
                                                        data-role="<?= htmlspecialchars($user['role']) ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): // Admin tidak bisa menghapus dirinya sendiri ?>
                                                <a href="kelola_user.php?delete_id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="kelola_user.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Tambah Pengguna Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Telepon</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_user" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="kelola_user.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nama</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_phone" class="form-label">Telepon</label>
                            <input type="text" class="form-control" id="edit_phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Password (Kosongkan jika tidak ingin diubah)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_user" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle Sidebar
        var el = document.getElementById("wrapper");
        var toggleButton = document.getElementById("menu-toggle");

        toggleButton.onclick = function () {
            el.classList.toggle("toggled");
        };

        // Populate Edit Modal
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_user_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_email').value = this.dataset.email;
                document.getElementById('edit_phone').value = this.dataset.phone;
                document.getElementById('edit_role').value = this.dataset.role;
                document.getElementById('edit_password').value = ''; // Selalu kosongkan password
            });
        });
    </script>
</body>
</html>