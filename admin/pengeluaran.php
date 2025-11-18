<?php
session_start();
// Ubah path ini sesuai lokasi file koneksi Anda
include '../koneksi.php';

// --- Variabel Konfigurasi & Inisialisasi ---
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$message = '';
$kategori_list = ['Operasional', 'Gaji Karyawan', 'Perawatan & Perbaikan', 'Pembelian Inventaris', 'Pemasaran', 'Lain-lain'];
$action = $_GET['action'] ?? 'read'; // Default action adalah 'read' (menampilkan daftar)
$id_edit = $_GET['id'] ?? null;

// Kosongkan variabel input default untuk form tambah/edit
$tanggal = date('Y-m-d');
$keterangan = '';
$jumlah = '';
$kategori = '';
$halaman_judul = "Daftar Pengeluaran";
$tombol_aksi_text = "Simpan Pengeluaran Baru";

// Logika untuk menampilkan pesan sukses/error dari sesi (setelah redirect/operasi berhasil)
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// --- FUNGSI SANITASI DAN VALIDASI ---
function validate_input($data, $kategori_list)
{
    global $koneksi;

    $errors = [];

    // 1. Ambil dan Sanitasi Input
    $tanggal = $data['tanggal'] ?? '';
    $keterangan = trim($data['keterangan'] ?? '');
    $jumlah = $data['jumlah'] ?? 0;
    $kategori = $data['kategori'] ?? '';

    // 2. Validasi
    if (empty($tanggal)) {
        $errors[] = "Tanggal wajib diisi.";
    }
    if (empty($keterangan)) {
        $errors[] = "Keterangan wajib diisi.";
    }
    if (empty($kategori)) {
        $errors[] = "Kategori wajib diisi.";
    } elseif (!in_array($kategori, $kategori_list)) {
        $errors[] = "Kategori yang dipilih tidak valid.";
    }

    // Menggunakan filter_var untuk validasi dan sanitasi jumlah
    $jumlah_float = filter_var($jumlah, FILTER_VALIDATE_FLOAT);

    if ($jumlah_float === false || $jumlah_float <= 0) {
        $errors[] = "Jumlah harus berupa angka yang valid dan lebih dari nol.";
    }

    return [
        'tanggal' => $tanggal,
        'keterangan' => $keterangan,
        'jumlah' => $jumlah_float,
        'kategori' => $kategori,
        'errors' => $errors
    ];
}


// --- 1. Logika HAPUS (DELETE) ---
if ($action === 'delete' && $id_edit) {
    // Pastikan ID adalah integer
    if (is_numeric($id_edit)) {
        $query_delete = "DELETE FROM pengeluaran WHERE id = ? AND user_id = ?";
        if ($stmt = $koneksi->prepare($query_delete)) {
            $stmt->bind_param("ii", $id_edit, $user_id);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $_SESSION['message'] = "<div class='alert alert-success'>Pengeluaran dengan ID **$id_edit** berhasil dihapus.</div>";
                } else {
                    $_SESSION['message'] = "<div class='alert alert-warning'>Data tidak ditemukan atau Anda tidak memiliki izin.</div>";
                }
            } else {
                $_SESSION['message'] = "<div class='alert alert-danger'>Gagal menghapus data: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $_SESSION['message'] = "<div class='alert alert-danger'>Gagal menyiapkan query hapus: " . $koneksi->error . "</div>";
        }
    } else {
        $_SESSION['message'] = "<div class='alert alert-danger'>ID Pengeluaran tidak valid.</div>";
    }
    header('Location: pengeluaran.php');
    exit;
}


// --- 2. Logika FORM: Tambah (CREATE) atau Edit (UPDATE) ---
if ($action === 'add' || $action === 'edit') {
    $halaman_judul = ($action === 'add') ? "Tambah Pengeluaran Baru" : "Edit Pengeluaran";
    $tombol_aksi_text = ($action === 'add') ? "Simpan Pengeluaran Baru" : "Perbarui Pengeluaran";

    // A. Logika Ambil Data untuk Form EDIT
    if ($action === 'edit' && $id_edit) {
        $query_select = "SELECT tanggal, keterangan, jumlah, kategori FROM pengeluaran WHERE id = ? AND user_id = ?";
        if ($stmt = $koneksi->prepare($query_select)) {
            $stmt->bind_param("ii", $id_edit, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $pengeluaran = $result->fetch_assoc();
                $tanggal = $pengeluaran['tanggal'];
                $keterangan = $pengeluaran['keterangan'];
                $jumlah = $pengeluaran['jumlah'];
                $kategori = $pengeluaran['kategori'];
            } else {
                $_SESSION['message'] = "<div class='alert alert-danger'>Data pengeluaran tidak ditemukan.</div>";
                header('Location: pengeluaran.php');
                exit;
            }
            $stmt->close();
        }
    }

    // B. Logika Pemrosesan Form POST
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_pengeluaran'])) {

        $validated = validate_input($_POST, $kategori_list);

        // Simpan kembali nilai form jika validasi gagal
        $tanggal = $validated['tanggal'];
        $keterangan = $validated['keterangan'];
        $jumlah = $validated['jumlah'];
        $kategori = $validated['kategori'];

        if (!empty($validated['errors'])) {
            $message = "<div class='alert alert-danger'><ul><li>" . implode("</li><li>", $validated['errors']) . "</li></ul></div>";
        } else {
            // Jika validasi sukses, jalankan INSERT atau UPDATE
            if ($action === 'add') {
                $query_sql = "INSERT INTO pengeluaran (tanggal, keterangan, jumlah, kategori, user_id) VALUES (?, ?, ?, ?, ?)";
                $bind_params = [$tanggal, $keterangan, $jumlah, $kategori, $user_id];
                $bind_types = "ssdsi";
                $success_msg = "Data pengeluaran **" . htmlspecialchars($keterangan) . "** berhasil ditambahkan!";
            } else { // action === 'edit'
                $query_sql = "UPDATE pengeluaran SET tanggal = ?, keterangan = ?, jumlah = ?, kategori = ? WHERE id = ? AND user_id = ?";
                $bind_params = [$tanggal, $keterangan, $jumlah, $kategori, $id_edit, $user_id];
                $bind_types = "ssdsii";
                $success_msg = "Data pengeluaran **" . htmlspecialchars($keterangan) . "** berhasil diperbarui!";
            }

            if ($stmt = $koneksi->prepare($query_sql)) {
                // Binding parameter secara dinamis
                $bind_ref = [];
                foreach ($bind_params as $key => $value) {
                    $bind_ref[$key] = &$bind_params[$key];
                }
                array_unshift($bind_ref, $bind_types);

                call_user_func_array([$stmt, 'bind_param'], $bind_ref);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "<div class='alert alert-success'>$success_msg</div>";
                    header('Location: pengeluaran.php');
                    exit;
                } else {
                    $message = "<div class='alert alert-danger'>Gagal menyimpan data: " . $stmt->error . "</div>";
                }
                $stmt->close();
            } else {
                $message = "<div class='alert alert-danger'>Gagal menyiapkan query: " . $koneksi->error . "</div>";
            }
        }
    }
}


// --- 3. Logika TAMPILKAN DAFTAR (READ) ---
$pengeluaran_data = [];
$total_pengeluaran = 0;

if ($action === 'read') {
    $query_read = "SELECT id, tanggal, keterangan, jumlah, kategori FROM pengeluaran WHERE user_id = ? ORDER BY tanggal DESC, id DESC";
    if ($stmt = $koneksi->prepare($query_read)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $pengeluaran_data[] = $row;
            $total_pengeluaran += $row['jumlah'];
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>Gagal menyiapkan query: " . $koneksi->error . "</div>";
    }
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $halaman_judul ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>

<body class="bg-light">
    <?php include "sidebar.php" ?>

    <div class="mt-5 content">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i> <?= $halaman_judul ?></h4>
                    </div>
                    <div class="card-body p-4">
                        <?= $message ?>

                        <?php if ($action === 'read'): // TAMPILKAN DAFTAR 
                        ?>

                            <div class="d-flex justify-content-between mb-3 align-items-center">
                                <a href="pengeluaran.php?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Tambah Pengeluaran Baru
                                </a>
                                <div class="alert alert-info py-2 px-3 m-0">
                                    **Total Pengeluaran:** **Rp <?= number_format($total_pengeluaran, 2, ',', '.') ?>**
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Tanggal</th>
                                            <th>Kategori</th>
                                            <th>Keterangan</th>
                                            <th class="text-end">Jumlah (Rp)</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($pengeluaran_data) > 0): ?>
                                            <?php $no = 1;
                                            foreach ($pengeluaran_data as $data): ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= date('d-m-Y', strtotime($data['tanggal'])) ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($data['kategori']) ?></span></td>
                                                    <td><?= htmlspecialchars($data['keterangan']) ?></td>
                                                    <td class="text-end"><?= number_format($data['jumlah'], 2, ',', '.') ?></td>
                                                    <td class="text-center">
                                                        <a href="pengeluaran.php?action=edit&id=<?= $data['id'] ?>" class="btn btn-sm btn-warning me-2 text-white" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="pengeluaran.php?action=delete&id=<?= $data['id'] ?>" class="btn btn-sm btn-danger" title="Hapus"
                                                            onclick="return confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Belum ada data pengeluaran yang tercatat.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        <?php elseif ($action === 'add' || $action === 'edit'): // TAMPILKAN FORM TAMBAH/EDIT 
                        ?>

                            <form method="POST" action="pengeluaran.php?action=<?= $action ?><?= ($action === 'edit') ? '&id=' . htmlspecialchars($id_edit) : '' ?>">

                                <div class="mb-3">
                                    <label for="tanggal" class="form-label fw-bold">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggal" name="tanggal"
                                        value="<?= htmlspecialchars($tanggal) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="kategori" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                                    <select class="form-select" id="kategori" name="kategori" required>
                                        <option value="" disabled selected>Pilih Kategori Pengeluaran</option>
                                        <?php foreach ($kategori_list as $kat): ?>
                                            <option value="<?= htmlspecialchars($kat) ?>"
                                                <?= ($kategori === $kat) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($kat) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="jumlah" class="form-label fw-bold">Jumlah (Rp) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah"
                                        step="0.01" min="0.01" placeholder="Cth: 500000.00"
                                        value="<?= htmlspecialchars($jumlah) ?>" required>
                                    <div class="form-text">Masukkan jumlah dengan format desimal (cth: 500000.00).</div>
                                </div>

                                <div class="mb-3">
                                    <label for="keterangan" class="form-label fw-bold">Keterangan/Deskripsi <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3"
                                        placeholder="Contoh: Pembelian perlengkapan kebersihan kamar" required><?= htmlspecialchars($keterangan) ?></textarea>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" name="submit_pengeluaran" class="btn btn-<?= ($action === 'add') ? 'primary' : 'warning text-white' ?> btn-lg">
                                        <i class="fas fa-save me-2"></i> <?= $tombol_aksi_text ?>
                                    </button>
                                    <a href="pengeluaran.php" class="btn btn-secondary mt-2">
                                        <i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar
                                    </a>
                                </div>
                            </form>

                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>