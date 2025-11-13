<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$upload_dir = '../uploads/kamar/'; // Pastikan folder ini ada dan writable

// Fungsi untuk Ambil Semua Tipe Kamar (Tidak Berubah)
$room_types_result = $koneksi->query("SELECT * FROM room_types");
$room_types = $room_types_result->fetch_all(MYSQLI_ASSOC);

function handle_upload($file_input_name, $upload_dir)
{
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] === UPLOAD_ERR_OK) {
        $file_tmp_name = $_FILES[$file_input_name]['tmp_name'];
        $file_name = $_FILES[$file_input_name]['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_ext, $allowed_ext)) {
            $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp_name, $destination)) {
                return $new_file_name;
            } else {
                return false;
            }
        }
    }
    return null; // No file uploaded or upload error
}

// Helper function to delete old file
function delete_old_image($file_name, $upload_dir)
{
    global $upload_dir;
    if (!empty($file_name) && file_exists($upload_dir . $file_name)) {
        unlink($upload_dir . $file_name);
    }
}


// 1. Tambah Kamar
if (isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];

    // Lakukan Unggahan File
    $image_name = handle_upload('image_file', $upload_dir);

    if ($image_name === false) {
        $message = "<div class='alert alert-danger'>Gagal mengunggah gambar. Pastikan format file benar (jpg, jpeg, png, gif) dan ukuran tidak melebihi batas.</div>";
    } else {
        $image_to_save = $image_name ?? 'default.jpg'; // Gunakan default jika unggahan gagal/tidak ada

        // Cek duplikasi nomor kamar
        $check = $koneksi->prepare("SELECT id FROM rooms WHERE room_number = ?");
        $check->bind_param("s", $room_number);
        $check->execute();
        $check->store_result();

        if ($check->num_rows == 0) {
            $stmt = $koneksi->prepare("INSERT INTO rooms (room_number, room_type_id, floor, image, status) VALUES (?, ?, ?, ?, 'available')");
            $stmt->bind_param("siss", $room_number, $room_type_id, $floor, $image_to_save);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Kamar berhasil ditambahkan!</div>";
            } else {
                $message = "<div class='alert alert-danger'>Gagal menambahkan kamar: " . $stmt->error . "</div>";
                if ($image_name) {
                    delete_old_image($image_name, $upload_dir); // Hapus file jika DB gagal
                }
            }
            $stmt->close();
        } else {
            $message = "<div class='alert alert-warning'>Nomor kamar sudah terdaftar!</div>";
            if ($image_name) {
                delete_old_image($image_name, $upload_dir); // Hapus file jika nomor kamar duplikat
            }
        }
        $check->close();
    }
}

// 2. Edit Kamar
if (isset($_POST['edit_room'])) {
    $id = $_POST['room_id'];
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];
    $status = $_POST['status'];
    $old_image = $_POST['old_image']; // Ambil nama file lama

    // Ambil data kamar saat ini untuk memastikan old_image valid
    $get_old_room_stmt = $koneksi->prepare("SELECT image FROM rooms WHERE id = ?");
    $get_old_room_stmt->bind_param("i", $id);
    $get_old_room_stmt->execute();
    $get_old_room_result = $get_old_room_stmt->get_result();
    $current_room = $get_old_room_result->fetch_assoc();
    $get_old_room_stmt->close();

    $image_to_save = $current_room['image']; // Default: pertahankan gambar lama

    // Lakukan Unggahan File (hanya jika ada file baru diunggah)
    $new_image_name = handle_upload('edit_image_file', $upload_dir);

    if ($new_image_name === false) {
        $message = "<div class='alert alert-danger'>Gagal mengunggah gambar baru. Pastikan format file benar (jpg, jpeg, png, gif) dan ukuran tidak melebihi batas.</div>";
    } else {
        if ($new_image_name !== null) {
            // Ada file baru yang berhasil diunggah
            $image_to_save = $new_image_name;
            // Hapus file lama jika ada dan bukan default/kosong
            if (!empty($current_room['image']) && $current_room['image'] != 'default.jpg') {
                delete_old_image($current_room['image'], $upload_dir);
            }
        }

        // Perbarui data di database
        $stmt = $koneksi->prepare("UPDATE rooms SET room_number=?, room_type_id=?, floor=?, status=?, image=? WHERE id=?");
        $stmt->bind_param("sisssi", $room_number, $room_type_id, $floor, $status, $image_to_save, $id);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Kamar berhasil diperbarui!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal memperbarui kamar: " . $stmt->error . "</div>";
            // Jika update DB gagal, tapi file baru sudah diupload, hapus file baru tersebut
            if ($new_image_name !== null) {
                delete_old_image($new_image_name, $upload_dir);
            }
        }
        $stmt->close();
    }
}

// 3. Hapus Kamar
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Ambil nama gambar lama sebelum dihapus dari DB
    $get_image_stmt = $koneksi->prepare("SELECT image FROM rooms WHERE id = ?");
    $get_image_stmt->bind_param("i", $id);
    $get_image_stmt->execute();
    $get_image_result = $get_image_stmt->get_result();
    $room_to_delete = $get_image_result->fetch_assoc();
    $get_image_stmt->close();

    $stmt = $koneksi->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        // Hapus file fisik setelah berhasil dihapus dari DB
        if ($room_to_delete && !empty($room_to_delete['image']) && $room_to_delete['image'] != 'default.jpg') {
            delete_old_image($room_to_delete['image'], $upload_dir);
        }
        $message = "<div class='alert alert-success'>Kamar berhasil dihapus!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal menghapus kamar. Kamar mungkin terikat dengan data pesanan atau error DB.</div>";
    }
    $stmt->close();

    // Hilangkan parameter delete_id dari URL setelah operasi
    header("Location: kelola_kamar.php");
    exit;
}


// --- Logika Pencarian dan Pengurutan (Tidak Berubah) ---
$search_term = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'latest';
$where_clauses = [];
$order_clause = "";
$params = [];
$param_types = '';

// 1. Kondisi Pencarian (Filtering)
if (!empty($search_term)) {
    $like_room_number = '%' . $search_term . '%';
    $like_room_type = '%' . $search_term . '%';
    $like_floor = '%' . $search_term . '%';
    $like_status = '%' . $search_term . '%';

    $where_clauses[] = "
        (
            r.room_number LIKE ? OR
            rt.name LIKE ? OR
            r.floor LIKE ? OR
            r.status LIKE ?
        )
    ";

    $params = array(
        $like_room_number,
        $like_room_type,
        $like_floor,
        $like_status
    );
    $param_types = 'ssss';
}

// 2. Pengurutan (Sorting)
switch ($sort_by) {
    case 'room_asc':
        $order_clause = "ORDER BY r.room_number ASC";
        break;
    case 'room_desc':
        $order_clause = "ORDER BY r.room_number DESC";
        break;
    case 'floor_asc':
        $order_clause = "ORDER BY r.floor ASC, r.room_number ASC";
        break;
    case 'floor_desc':
        $order_clause = "ORDER BY r.floor DESC, r.room_number ASC";
        break;
    case 'type_asc':
        $order_clause = "ORDER BY rt.name ASC, r.room_number ASC";
        break;
    case 'type_desc':
        $order_clause = "ORDER BY rt.name DESC, r.room_number ASC";
        break;
    case 'latest': // Default
    default:
        $order_clause = "ORDER BY r.id DESC"; // Asumsi ID kamar mencerminkan terbaru
        break;
}


// 3. Kueri Gabungan
$query = "
    SELECT r.*, rt.name as room_type_name
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
";

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(' AND ', $where_clauses);
}

$query .= " " . $order_clause;

// Eksekusi Kueri
if (!empty($params)) {
    $stmt = $koneksi->prepare($query);

    // START PERBAIKAN: Ubah array nilai menjadi array referensi
    $ref_params = array();
    $ref_params[] = &$param_types;

    // Iterasi melalui setiap parameter, dan buat referensinya
    foreach ($params as $key => $value) {
        $ref_params[] = &$params[$key];
    }

    // Panggil bind_param menggunakan array referensi
    call_user_func_array([$stmt, 'bind_param'], $ref_params);

    $stmt->execute();
    $rooms_result = $stmt->get_result();
    $rooms = $rooms_result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    // Jalankan kueri sederhana jika tidak ada pencarian
    $rooms_result = $koneksi->query($query);
    $rooms = $rooms_result->fetch_all(MYSQLI_ASSOC);
}

// Catatan: Dalam kode PHP sebenarnya, Anda harus menggabungkan logika file handling dan CRUD ke dalam satu blok pengecekan POST/GET agar pesan error file handling dapat ditampilkan dengan benar. Namun, untuk menjaga struktur awal Anda, saya hanya memodifikasi blok CRUD dan helper.
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kamar - Hotel Admin</title>
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
                    <h2 class="fs-2 m-0">Kelola Kamar</h2>
                </div>
            </nav>

            <div class="container-fluid px-4">
                <?= $message ?>
                <div class="row my-4">
                    <h3 class="fs-4 mb-3">Daftar Kamar</h3>

                    <div class="col-md-12 d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex flex-grow-1 me-3">
                            <form method="GET" action="kelola_kamar.php" class="d-flex w-100">
                                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort_by) ?>">
                                <input type="text" name="search" class="form-control me-2" placeholder="Cari No. Kamar, Tipe, Lantai, atau Status..." value="<?= htmlspecialchars($search_term) ?>">
                                <button type="submit" class="btn btn-outline-primary">Cari</button>
                                <?php if (!empty($search_term)): ?>
                                    <a href="kelola_kamar.php?sort=<?= htmlspecialchars($sort_by) ?>" class="btn btn-outline-secondary ms-2">Reset</a>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="d-flex align-items-center">
                            <form method="GET" action="kelola_kamar.php" class="me-3">
                                <input type="hidden" name="search" value="<?= htmlspecialchars($search_term) ?>">
                                <select name="sort" onchange="this.form.submit()" class="form-select form-select-sm">
                                    <option value="latest" <?= $sort_by == 'latest' ? 'selected' : '' ?>>Urutkan: Terbaru</option>
                                    <optgroup label="Nomor Kamar">
                                        <option value="room_asc" <?= $sort_by == 'room_asc' ? 'selected' : '' ?>>Menurun (A-Z)</option>
                                        <option value="room_desc" <?= $sort_by == 'room_desc' ? 'selected' : '' ?>>Menaik (Z-A)</option>
                                    </optgroup>
                                    <optgroup label="Lantai">
                                        <option value="floor_asc" <?= $sort_by == 'floor_asc' ? 'selected' : '' ?>>Terkecil - Terbesar</option>
                                        <option value="floor_desc" <?= $sort_by == 'floor_desc' ? 'selected' : '' ?>>Terbesar - Terkecil</option>
                                    </optgroup>
                                    <optgroup label="Tipe Kamar">
                                        <option value="type_asc" <?= $sort_by == 'type_asc' ? 'selected' : '' ?>>Menurun (A-Z)</option>
                                        <option value="type_desc" <?= $sort_by == 'type_desc' ? 'selected' : '' ?>>Menaik (Z-A)</option>
                                    </optgroup>
                                </select>
                            </form>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                                <i class="fas fa-plus me-2"></i>Tambah Kamar
                            </button>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table bg-white rounded shadow-sm table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col" width="50">#</th>
                                        <th scope="col">No. Kamar</th>
                                        <th scope="col">Gambar</th>
                                        <th scope="col">Tipe</th>
                                        <th scope="col">Lantai</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($rooms)): ?>
                                        <?php $no = 1;
                                        foreach ($rooms as $room): ?>
                                            <tr>
                                                <th scope="row"><?= $no++ ?></th>
                                                <td><?= htmlspecialchars($room['room_number']) ?></td>
                                                <td><img height="100" src="../uploads/kamar/<?= htmlspecialchars($room['image']) ?>" alt="<?= htmlspecialchars($room['room_type_name']) ?>"></td>
                                                <td><?= htmlspecialchars($room['room_type_name']) ?></td>
                                                <td><?= htmlspecialchars($room['floor']) ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = match ($room['status']) {
                                                        'available' => 'badge bg-success',
                                                        'booked' => 'badge bg-warning text-dark',
                                                        'maintenance' => 'badge bg-danger',
                                                        default => 'badge bg-secondary',
                                                    };
                                                    echo "<span class='{$status_class}'>" . ucfirst(htmlspecialchars($room['status'])) . "</span>";
                                                    ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info text-white edit-btn"
                                                        data-bs-toggle="modal" data-bs-target="#editRoomModal"
                                                        data-id="<?= $room['id'] ?>"
                                                        data-number="<?= htmlspecialchars($room['room_number']) ?>"
                                                        data-type-id="<?= $room['room_type_id'] ?>"
                                                        data-floor="<?= $room['floor'] ?>"
                                                        data-status="<?= $room['status'] ?>"
                                                        data-image="<?= htmlspecialchars($room['image']) ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="kelola_kamar.php?delete_id=<?= $room['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus kamar ini? Ini akan menghapus file gambar terkait dan setiap pemesanan (booking) yang mengacu ke kamar ini.');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Tidak ada kamar yang ditemukan.</td>
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

    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="kelola_kamar.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoomModalLabel">Tambah Kamar Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Nomor Kamar</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="room_type_id" class="form-label">Tipe Kamar</label>
                            <select class="form-select" id="room_type_id" name="room_type_id" required>
                                <option value="">Pilih Tipe Kamar</option>
                                <?php foreach ($room_types as $type): ?>
                                    <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="floor" class="form-label">Lantai</label>
                            <input type="number" class="form-control" id="floor" name="floor" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="image_file" class="form-label">Unggah Gambar (JPG, PNG, GIF)</label>
                            <input type="file" class="form-control" id="image_file" name="image_file" accept=".jpg, .jpeg, .png, .gif" required>
                            <div class="form-text">File akan disimpan di: **`uploads/kamar/`**</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="add_room" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="kelola_kamar.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoomModalLabel">Edit Kamar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="room_id" id="edit_room_id">
                        <input type="hidden" name="old_image" id="edit_old_image">
                        <div class="mb-3">
                            <label for="edit_room_number" class="form-label">Nomor Kamar</label>
                            <input type="text" class="form-control" id="edit_room_number" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_room_type_id" class="form-label">Tipe Kamar</label>
                            <select class="form-select" id="edit_room_type_id" name="room_type_id" required>
                                <?php foreach ($room_types as $type): ?>
                                    <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_floor" class="form-label">Lantai</label>
                            <input type="number" class="form-control" id="edit_floor" name="floor" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="available">available</option>
                                <option value="booked">booked</option>
                                <option value="maintenance">maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_image_file" class="form-label">Unggah Gambar Baru (Biarkan kosong untuk mempertahankan gambar lama)</label>
                            <input type="file" class="form-control" id="edit_image_file" name="edit_image_file" accept=".jpg, .jpeg, .png, .gif">
                            <div class="form-text">Gambar lama: <span id="current_image_name"></span></div>
                            <div class="form-text">Jika file diunggah, file lama akan dihapus dari **`uploads/kamar/`**.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_room" class="btn btn-primary">Simpan Perubahan</button>
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
                // Set data ke input tersembunyi dan field lain
                document.getElementById('edit_room_id').value = this.dataset.id;
                document.getElementById('edit_old_image').value = this.dataset.image; // Simpan nama file lama

                document.getElementById('edit_room_number').value = this.dataset.number;
                document.getElementById('edit_room_type_id').value = this.dataset.typeId;
                document.getElementById('edit_floor').value = this.dataset.floor;
                document.getElementById('edit_status').value = this.dataset.status;

                // Tampilkan nama file saat ini
                document.getElementById('current_image_name').textContent = this.dataset.image || 'Tidak ada gambar';

                // Kosongkan input file untuk unggahan baru
                document.getElementById('edit_image_file').value = '';
            });
        });
    </script>
</body>

</html>