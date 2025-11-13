<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// 2. Edit Pengguna (Blok ini sudah diperbaiki di respons sebelumnya, dipertahankan di sini)
if (isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    $sql = "UPDATE users SET name=?, email=?, phone=?, role=?";
    $param_types = "ssss";
    $bind_params = [$name, $email, $phone, $role];

    $sql .= " WHERE id=?";
    $param_types .= "i";
    $bind_params[] = $id;

    $stmt = $koneksi->prepare($sql);

    // Perbaikan bind_param menggunakan referensi
    $ref_params = array();
    $ref_params[] = &$param_types;

    foreach ($bind_params as $key => $value) {
        $ref_params[] = &$bind_params[$key];
    }

    call_user_func_array(array($stmt, 'bind_param'), $ref_params);

    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Pengguna berhasil diperbarui!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal memperbarui pengguna: " . $stmt->error . "</div>";
    }
    $stmt->close();
}


// 3. Hapus Pengguna (Dipertahankan)
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
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
    header("Location: kelola_user.php?message=" . urlencode(strip_tags($message)));
    exit;
}

// Tangani pesan dari redirect
if (isset($_GET['message'])) {
    $message = "<div class='alert alert-info'>" . htmlspecialchars($_GET['message']) . "</div>";
}

// --- Logika Pencarian dan Pengurutan Pengguna (BARU) ---
$search_term = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'latest';
$where_clauses = [];
$order_clause = "";
$params = [];
$param_types = '';

// a. Kondisi Pencarian (Filtering)
if (!empty($search_term)) {
    // Variabel referensi harus dibuat terpisah
    $like_name = '%' . $search_term . '%';
    $like_email = '%' . $search_term . '%';
    $like_phone = '%' . $search_term . '%';
    $like_role = '%' . $search_term . '%';

    $where_clauses[] = "
        (
            name LIKE ? OR
            email LIKE ? OR
            phone LIKE ? OR
            role LIKE ?
        )
    ";

    // Tambahkan 4 variabel 'like' sebagai parameter
    $params = array(
        $like_name,
        $like_email,
        $like_phone,
        $like_role
    );
    $param_types = 'ssss';
}

// b. Pengurutan (Sorting)
switch ($sort_by) {
    case 'name_asc':
        $order_clause = "ORDER BY name ASC";
        break;
    case 'name_desc':
        $order_clause = "ORDER BY name DESC";
        break;
    case 'role_asc':
        $order_clause = "ORDER BY role ASC, name ASC";
        break;
    case 'role_desc':
        $order_clause = "ORDER BY role DESC, name ASC";
        break;
    case 'latest': // Default
    default:
        $order_clause = "ORDER BY created_at DESC";
        break;
}

// c. Kueri Gabungan
$query = "SELECT id, name, email, phone, role, created_at FROM users";

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " " . $order_clause;

// d. Eksekusi Kueri dengan Penanganan Referensi (MENGHINDARI WARNING bind_param)
if (!empty($params)) {
    $stmt = $koneksi->prepare($query);

    // Siapkan array referensi untuk bind_param
    $ref_params = array();
    $ref_params[] = &$param_types;

    foreach ($params as $key => $value) {
        $ref_params[] = &$params[$key];
    }

    // Panggil bind_param menggunakan array referensi
    call_user_func_array(array($stmt, 'bind_param'), $ref_params);

    $stmt->execute();
    $users_result = $stmt->get_result();
    $users = $users_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Jalankan kueri sederhana jika tidak ada pencarian
    $users_result = $koneksi->query($query);
    $users = $users_result->fetch_all(MYSQLI_ASSOC);
}


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

                    <div class="col-md-12 d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex flex-grow-1 me-3">
                            <form method="GET" action="kelola_user.php" class="d-flex w-100">
                                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                                <input type="text" name="search" class="form-control me-2" placeholder="Cari Nama, Email, Telepon, atau Role..." value="<?= htmlspecialchars($search_term) ?>">
                                <button type="submit" class="btn btn-outline-primary">Cari</button>
                                <?php if (!empty($search_term)): ?>
                                    <a href="kelola_user.php?sort=<?= htmlspecialchars($sort_by) ?>" class="btn btn-outline-secondary ms-2">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="d-flex align-items-center">
                            <form method="GET" action="kelola_user.php" class="me-3">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search_term) ?>">
                                <select name="sort" onchange="this.form.submit()" class="form-select form-select-sm">
                                    <option value="latest" <?= $sort_by == 'latest' ? 'selected' : '' ?>>Urutkan: Terbaru</option>
                                    <optgroup label="Nama">
                                        <option value="name_asc" <?= $sort_by == 'name_asc' ? 'selected' : '' ?>>Nama (A-Z)</option>
                                        <option value="name_desc" <?= $sort_by == 'name_desc' ? 'selected' : '' ?>>Nama (Z-A)</option>
                                    </optgroup>
                                    <optgroup label="Role">
                                        <option value="role_asc" <?= $sort_by == 'role_asc' ? 'selected' : '' ?>>Role (Admin dulu)</option>
                                        <option value="role_desc" <?= $sort_by == 'role_desc' ? 'selected' : '' ?>>Role (User dulu)</option>
                                    </optgroup>
                                </select>
                            </form>
                        </div>
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
                                    <?php if (!empty($users)): ?>
                                        <?php $no = 1;
                                        foreach ($users as $user): ?>
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
                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <a href="kelola_user.php?delete_id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus pengguna ini?');">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Tidak ada pengguna yang ditemukan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
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

        toggleButton.onclick = function() {
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