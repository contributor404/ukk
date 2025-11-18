<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';
$upload_dir = '../uploads/kamar/';

// Fungsi untuk Ambil Semua Tipe Kamar (Tidak Berubah)
$room_types_result = $koneksi->query("SELECT * FROM room_types");
$room_types = $room_types_result->fetch_all(MYSQLI_ASSOC);

/**
 * Mengunggah banyak file.
 * @param string $file_input_name Nama input file (misalnya 'image_files').
 * @param string $upload_dir Direktori unggahan.
 * @return array|false Daftar nama file baru yang diunggah, atau false jika ada error.
 */
function handle_multi_upload($file_input_name, $upload_dir)
{
    $uploaded_files = [];
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    // Periksa apakah input file diatur dan merupakan array
    if (isset($_FILES[$file_input_name]) && is_array($_FILES[$file_input_name]['name'])) {
        $file_count = count($_FILES[$file_input_name]['name']);

        for ($i = 0; $i < $file_count; $i++) {
            // Hanya proses file yang diunggah tanpa error
            if ($_FILES[$file_input_name]['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp_name = $_FILES[$file_input_name]['tmp_name'][$i];
                $file_name = $_FILES[$file_input_name]['name'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, $allowed_ext)) {
                    $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($file_tmp_name, $destination)) {
                        $uploaded_files[] = $new_file_name;
                    } else {
                        // Jika gagal upload, hapus file yang sudah terlanjur terupload di iterasi sebelumnya
                        foreach ($uploaded_files as $f) {
                            delete_old_image($f, $upload_dir);
                        }
                        return false; // Gagal di tengah jalan
                    }
                }
            }
        }
    }
    return $uploaded_files;
}

// Helper function to delete old file
function delete_old_image($file_name, $upload_dir)
{
    // Mengambil semua nama file dari string yang dipisahkan koma
    $file_list = explode(',', $file_name);

    foreach ($file_list as $f) {
        $f = trim($f);
        if (!empty($f) && $f != 'default.jpg' && file_exists($upload_dir . $f)) {
            unlink($upload_dir . $f);
        }
    }
}


// 1. Tambah Kamar
if (isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];

    // Lakukan Unggahan Multi File
    $image_names_array = handle_multi_upload('image_files', $upload_dir);

    if ($image_names_array === false) {
        $message = "<div class='alert alert-danger'>Gagal mengunggah beberapa gambar. Pastikan format file benar (jpg, jpeg, png, gif) dan ukuran tidak melebihi batas.</div>";
    } elseif (empty($image_names_array)) {
        // Jika tidak ada file yang diunggah
        $image_to_save = 'default.jpg';
        $message = "<div class='alert alert-warning'>Tidak ada gambar diunggah. Menggunakan gambar default.</div>";
    } else {
        // Gabungkan nama file menjadi string yang dipisahkan koma
        $image_to_save = implode(',', $image_names_array);

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
                // Hapus file jika DB gagal (hanya jika bukan 'default.jpg')
                if ($image_to_save != 'default.jpg') {
                    delete_old_image($image_to_save, $upload_dir);
                }
            }
            $stmt->close();
        } else {
            $message = "<div class='alert alert-warning'>Nomor kamar sudah terdaftar!</div>";
            // Hapus file jika nomor kamar duplikat
            if ($image_to_save != 'default.jpg') {
                delete_old_image($image_to_save, $upload_dir);
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
    $old_image_string = $_POST['old_image']; // Ambil string file lama (dipisahkan koma)

    // Ambil data kamar saat ini
    $get_old_room_stmt = $koneksi->prepare("SELECT image FROM rooms WHERE id = ?");
    $get_old_room_stmt->bind_param("i", $id);
    $get_old_room_stmt->execute();
    $current_room = $get_old_room_stmt->get_result()->fetch_assoc();
    $get_old_room_stmt->close();

    $image_to_save = $current_room['image']; // Default: pertahankan string gambar lama

    // Lakukan Unggahan Multi File (hanya jika ada file baru diunggah)
    $new_image_names_array = handle_multi_upload('edit_image_files', $upload_dir);

    if ($new_image_names_array === false) {
        $message = "<div class='alert alert-danger'>Gagal mengunggah gambar baru. Pastikan format file benar (jpg, jpeg, png, gif) dan ukuran tidak melebihi batas.</div>";
    } else {
        if (!empty($new_image_names_array)) {
            // Ada file baru yang berhasil diunggah
            $new_image_string = implode(',', $new_image_names_array);
            $image_to_save = $new_image_string; // Timpa semua gambar lama dengan yang baru

            // Hapus file lama jika ada dan bukan default/kosong
            delete_old_image($current_room['image'], $upload_dir);
        }

        // Perbarui data di database
        $stmt = $koneksi->prepare("UPDATE rooms SET room_number=?, room_type_id=?, floor=?, status=?, image=? WHERE id=?");
        $stmt->bind_param("sisssi", $room_number, $room_type_id, $floor, $status, $image_to_save, $id);

        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Kamar berhasil diperbarui!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal memperbarui kamar: " . $stmt->error . "</div>";
            // Jika update DB gagal, tapi file baru sudah diupload, hapus file baru tersebut
            if (!empty($new_image_names_array)) {
                delete_old_image($new_image_string, $upload_dir);
            }
        }
        $stmt->close();
    }
}

// 3. Hapus Kamar
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // Ambil string gambar lama sebelum dihapus dari DB
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
        if ($room_to_delete && !empty($room_to_delete['image'])) {
            delete_old_image($room_to_delete['image'], $upload_dir); // Menggunakan fungsi delete_old_image yang sudah diubah
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
// ... (Logika pencarian dan pengurutan tetap sama) ...


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
?>

<!DOCTYPE html>
<html lang="id">

<head>
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
                                        foreach ($rooms as $room):
                                            // Ambil gambar pertama dari string yang dipisahkan koma
                                            $images = explode(',', $room['image']);
                                            $first_image = trim($images[0]);
                                            $image_src = "../uploads/kamar/" . htmlspecialchars($first_image);
                                        ?>
                                            <tr>
                                                <th scope="row"><?= $no++ ?></th>
                                                <td><?= htmlspecialchars($room['room_number']) ?></td>
                                                <td><img height="100" src="<?= $image_src ?>" alt="<?= htmlspecialchars($room['room_type_name']) ?>"></td>
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
                                                        data-image="<?= htmlspecialchars($room['image']) ?>"> <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="kelola_kamar.php?delete_id=<?= $room['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus kamar ini? Ini akan menghapus semua file gambar terkait dan setiap pemesanan (booking) yang mengacu ke kamar ini.');">
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
                            <label for="image_files" class="form-label">Unggah Gambar (Maks. 4 File - JPG, PNG, GIF)</label>
                            <input type="file" class="form-control" id="image_files" name="image_files[]" accept=".jpg, .jpeg, .png, .gif" multiple required>
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
                            <label for="edit_image_files" class="form-label">Unggah Gambar Baru (Maks. 4 File - Biarkan kosong untuk mempertahankan gambar lama)</label>
                            <input type="file" class="form-control" id="edit_image_files" name="edit_image_files[]" accept=".jpg, .jpeg, .png, .gif" multiple>
                            <div class="form-text">Gambar lama: <span id="current_image_name"></span></div>
                            <div class="form-text">Jika file diunggah, **SEMUA** file lama akan diganti. File baru akan disimpan di **`uploads/kamar/`**.</div>
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
                document.getElementById('edit_old_image').value = this.dataset.image; // Simpan string file lama (dipisahkan koma)

                document.getElementById('edit_room_number').value = this.dataset.number;
                document.getElementById('edit_room_type_id').value = this.dataset.typeId;
                document.getElementById('edit_floor').value = this.dataset.floor;
                document.getElementById('edit_status').value = this.dataset.status;

                // Tampilkan nama file saat ini (string koma-separated)
                document.getElementById('current_image_name').textContent = this.dataset.image || 'Tidak ada gambar';

                // Kosongkan input file untuk unggahan baru
                document.getElementById('edit_image_files').value = '';
            });
        });
    </script>
</body>

</html>