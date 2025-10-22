<?php
session_start();
include '../koneksi.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = '';

// Fungsi untuk Ambil Semua Tipe Kamar
$room_types_result = $koneksi->query("SELECT * FROM room_types");
$room_types = $room_types_result->fetch_all(MYSQLI_ASSOC);

// --- Logika CRUD Kamar ---

// 1. Tambah Kamar
if (isset($_POST['add_room'])) {
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];
    $image = $_POST['image']; // Menggunakan URL gambar sementara

    // Cek duplikasi nomor kamar
    $check = $koneksi->prepare("SELECT id FROM rooms WHERE room_number = ?");
    $check->bind_param("s", $room_number);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 0) {
        $stmt = $koneksi->prepare("INSERT INTO rooms (room_number, room_type_id, floor, image, status) VALUES (?, ?, ?, ?, 'available')");
        $stmt->bind_param("siss", $room_number, $room_type_id, $floor, $image);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Kamar berhasil ditambahkan!</div>";
        } else {
            $message = "<div class='alert alert-danger'>Gagal menambahkan kamar: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-warning'>Nomor kamar sudah terdaftar!</div>";
    }
    $check->close();
}

// 2. Edit Kamar
if (isset($_POST['edit_room'])) {
    $id = $_POST['room_id'];
    $room_number = $_POST['room_number'];
    $room_type_id = $_POST['room_type_id'];
    $floor = $_POST['floor'];
    $status = $_POST['status'];
    $image = $_POST['image'];

    $stmt = $koneksi->prepare("UPDATE rooms SET room_number=?, room_type_id=?, floor=?, status=?, image=? WHERE id=?");
    $stmt->bind_param("sissss", $room_number, $room_type_id, $floor, $status, $image, $id);
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Kamar berhasil diperbarui!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal memperbarui kamar: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// 3. Hapus Kamar
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $koneksi->prepare("DELETE FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Kamar berhasil dihapus!</div>";
    } else {
        $message = "<div class='alert alert-danger'>Gagal menghapus kamar. Kamar mungkin terikat dengan data pesanan.</div>";
    }
    $stmt->close();
    // Hilangkan parameter delete_id dari URL setelah operasi
    header("Location: kelola_kamar.php");
    exit;
}


// 4. Ambil Daftar Kamar (Tampil)
$query = "
    SELECT r.*, rt.name as room_type_name
    FROM rooms r
    JOIN room_types rt ON r.room_type_id = rt.id
    ORDER BY r.floor, r.room_number
";
$rooms_result = $koneksi->query($query);
$rooms = $rooms_result->fetch_all(MYSQLI_ASSOC);

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
                    <div class="col text-end mb-3">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                            <i class="fas fa-plus me-2"></i>Tambah Kamar
                        </button>
                    </div>
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table bg-white rounded shadow-sm table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col" width="50">#</th>
                                        <th scope="col">No. Kamar</th>
                                        <th scope="col">Tipe</th>
                                        <th scope="col">Lantai</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" width="150">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($rooms as $room): ?>
                                        <tr>
                                            <th scope="row"><?= $no++ ?></th>
                                            <td><?= htmlspecialchars($room['room_number']) ?></td>
                                            <td><?= htmlspecialchars($room['room_type_name']) ?></td>
                                            <td><?= htmlspecialchars($room['floor']) ?></td>
                                            <td>
                                                <?php
                                                    $status_class = match($room['status']) {
                                                        'available' => 'badge bg-success',
                                                        'booked' => 'badge bg-warning',
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
                                                <a href="kelola_kamar.php?delete_id=<?= $room['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus kamar ini?');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
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

    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="kelola_kamar.php" method="POST">
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
                            <label for="image" class="form-label">URL Gambar</label>
                            <input type="url" class="form-control" id="image" name="image" placeholder="Contoh: https://linkgambar.com/kamar1.jpg" required>
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
                <form action="kelola_kamar.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoomModalLabel">Edit Kamar</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="room_id" id="edit_room_id">
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
                            <label for="edit_image" class="form-label">URL Gambar</label>
                            <input type="url" class="form-control" id="edit_image" name="image" required>
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

        toggleButton.onclick = function () {
            el.classList.toggle("toggled");
        };

        // Populate Edit Modal
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_room_id').value = this.dataset.id;
                document.getElementById('edit_room_number').value = this.dataset.number;
                document.getElementById('edit_room_type_id').value = this.dataset.typeId;
                document.getElementById('edit_floor').value = this.dataset.floor;
                document.getElementById('edit_status').value = this.dataset.status;
                document.getElementById('edit_image').value = this.dataset.image;
            });
        });
    </script>
</body>
</html>