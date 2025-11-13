<?php
session_start();
// Ubah path ini sesuai lokasi file koneksi Anda (Asumsi: koneksi.php berada di folder atas)
include '../koneksi.php';

// --- Variabel Inisialisasi ---
// Asumsi ID pengguna diambil dari sesi. Default ke 1 jika tidak ada sesi login.
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;
$message = '';
$kategori_list = ['Operasional', 'Gaji Karyawan', 'Perawatan & Perbaikan', 'Pembelian Inventaris', 'Pemasaran', 'Lain-lain'];

// Kosongkan variabel input untuk nilai default atau untuk menahan nilai POST jika gagal validasi
$tanggal = date('Y-m-d');
$keterangan = '';
$jumlah = '';
$kategori = '';

// --- Logika Pemrosesan Form POST ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tambah_pengeluaran'])) {

    // 1. Ambil dan Sanitasi Input
    $tanggal = $_POST['tanggal'] ?? '';
    $keterangan = trim($_POST['keterangan'] ?? '');
    $jumlah = $_POST['jumlah'] ?? 0;
    $kategori = $_POST['kategori'] ?? '';

    // 2. Validasi Input
    if (empty($tanggal) || empty($keterangan) || $jumlah <= 0 || empty($kategori)) {
        $message = "<div class='alert alert-danger'>Semua kolom wajib diisi, dan **Jumlah** harus lebih dari nol.</div>";
    } elseif (!is_numeric($jumlah)) {
        $message = "<div class='alert alert-danger'>Jumlah harus berupa angka yang valid.</div>";
    } elseif (!in_array($kategori, $kategori_list)) {
        $message = "<div class='alert alert-danger'>Kategori yang dipilih tidak valid.</div>";
    } else {
        // 3. Persiapan dan Eksekusi Query menggunakan Prepared Statement
        $query = "INSERT INTO pengeluaran (tanggal, keterangan, jumlah, kategori, user_id) VALUES (?, ?, ?, ?, ?)";

        // Pastikan $koneksi adalah objek mysqli yang valid
        if ($stmt = $koneksi->prepare($query)) {
            // Parameter types: s=string, d=double (untuk DECIMAL), i=integer
            $stmt->bind_param("ssdsi", $tanggal, $keterangan, $jumlah, $kategori, $user_id);

            if ($stmt->execute()) {
                $formatted_jumlah = number_format($jumlah, 2, ',', '.');
                $message = "<div class='alert alert-success'>Data pengeluaran **" . htmlspecialchars($keterangan) . "** (Rp $formatted_jumlah) berhasil ditambahkan!</div>";

                // Reset form input untuk form baru setelah sukses
                $tanggal = date('Y-m-d');
                $keterangan = '';
                $jumlah = '';
                $kategori = '';
            } else {
                $message = "<div class='alert alert-danger'>Gagal menyimpan data: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='alert alert-danger'>Gagal menyiapkan query: " . $koneksi->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Data Pengeluaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-style.css">
</head>

<body class="bg-light">
    <?php include "sidebar.php" ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-3">
                        <h4 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i> Pengeluaran Baru</h4>
                    </div>
                    <div class="card-body p-4">
                        <?= $message ?>

                        <form method="POST" action="">

                            <div class="mb-3">
                                <label for="tanggal" class="form-label fw-bold">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal"
                                    value="<?= htmlspecialchars($_POST['tanggal'] ?? $tanggal) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="kategori" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select" id="kategori" name="kategori" required>
                                    <option value="" disabled selected>Pilih Kategori Pengeluaran</option>
                                    <?php foreach ($kategori_list as $kat): ?>
                                        <?php
                                        // Menentukan nilai yang dipilih, menggunakan POST jika ada, atau variabel reset jika tidak ada POST atau setelah sukses
                                        $selected_value = $_POST['kategori'] ?? $kategori;
                                        ?>
                                        <option value="<?= htmlspecialchars($kat) ?>"
                                            <?= ($selected_value === $kat) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($kat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="jumlah" class="form-label fw-bold">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="jumlah" name="jumlah"
                                    step="0.01" min="0.01" placeholder="Cth: 500000.00"
                                    value="<?= htmlspecialchars($_POST['jumlah'] ?? $jumlah) ?>" required>
                                <div class="form-text">Masukkan jumlah dengan dua angka desimal (cth: 500000.00).</div>
                            </div>

                            <div class="mb-3">
                                <label for="keterangan" class="form-label fw-bold">Keterangan/Deskripsi <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="3"
                                    placeholder="Contoh: Pembelian perlengkapan kebersihan kamar" required><?= htmlspecialchars($_POST['keterangan'] ?? $keterangan) ?></textarea>
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" name="tambah_pengeluaran" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i> Simpan Pengeluaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>