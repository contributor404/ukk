-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Okt 2025 pada 14.53
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tiket-hotel`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `hotel`
--

CREATE TABLE `hotel` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `alamat` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `harga` int(11) DEFAULT 0,
  `total_kamar` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `hotel`
--

INSERT INTO `hotel` (`id`, `name`, `alamat`, `image`, `description`, `harga`, `total_kamar`) VALUES
(1, 'Hotel Oyo jir', 'Jl. Merdeka No. 1, Jakarta', 'https://picsum.photos/seed/hotel1/600/400', 'Kamar anti peluru cocok untuk anggota dpr yang kabur dari pendemo', 450000, 9),
(2, 'Grand Horizon', 'Jl. Diponegoro No. 23, Bandung', 'https://picsum.photos/seed/hotel2/600/400', 'Hotel mewah dengan pemandangan laut dan fasilitas lengkap.', 1200000, 9),
(3, 'Sunrise Inn', 'Jl. Sudirman No. 45, Surabaya', 'https://picsum.photos/seed/hotel3/600/400', 'Penginapan sederhana dekat pusat kota, cocok untuk backpacker.', 250000, 10),
(4, 'Mountain View', 'Jl. Malioboro No. 99, Yogyakarta', 'https://picsum.photos/seed/hotel4/600/400', 'Hotel dengan', 600000, 10),
(5, 'Ocean Breeze', 'Jl. Gatot Subroto No. 12, Medan', 'https://picsum.photos/seed/hotel5/600/400', 'Resor tepi pantai dengan kolam renang dan restoran seafood.', 950000, 10),
(6, 'City Light Hotel', 'Jl. Asia Afrika No. 77, Bandung', 'https://picsum.photos/seed/hotel6/600/400', 'Hotel modern di pusat kota dengan akses transportasi mudah.', 700000, 10),
(7, 'Green Valley Hotel', 'Jl. Ahmad Yani No. 56, Semarang', 'https://picsum.photos/seed/hotel7/600/400', 'Penginapan asri dengan taman hijau dan udara sejuk.', 400000, 10),
(8, 'Royal Heritage', 'Jl. Gajah Mada No. 34, Denpasar', 'https://picsum.photos/seed/hotel8/600/400', 'Hotel klasik dengan desain elegan dan layanan premium.', 1500000, 10),
(9, 'Blue Lagoon Inn', 'Jl. Panglima Sudirman No. 88, Malang', 'https://picsum.photos/seed/hotel9/600/400', 'Hotel kecil dekat danau dengan suasana romantis.', 550000, 10),
(10, 'Golden Palm Resort', 'Jl. Veteran No. 21, Makassar', 'https://picsum.photos/seed/hotel10/600/400', 'Anjay mabar wkwk, vibe coder jir', 2000000, 10);

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `expired_date` datetime DEFAULT NULL,
  `hotel_id` int(11) NOT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `nama_pemesan` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `nights` int(11) DEFAULT 1,
  `total` int(11) DEFAULT 0,
  `payment_method` varchar(50) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `kamar_no` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `code`, `expired_date`, `hotel_id`, `user_email`, `nama_pemesan`, `phone`, `nights`, `total`, `payment_method`, `status`, `created_at`, `kamar_no`) VALUES
(18, 'ORD1759317260375', '2025-10-02 18:14:20', 1, 'user@gmail.com', 'user', '0', 5, 2250000, 'e-Wallet', 'paid', '2025-10-01 18:14:20', 1),
(19, 'ORD1759651961470', '2025-10-06 15:12:41', 2, 'user@gmail.com', 'user', '0', 3, 3600000, 'Transfer Bank', 'paid', '2025-10-05 15:12:41', 1);

--
-- Trigger `orders`
--
DELIMITER $$
CREATE TRIGGER `trg_check_hotel_stock` BEFORE INSERT ON `orders` FOR EACH ROW BEGIN
    DECLARE available INT;

    SELECT total_kamar INTO available FROM hotel WHERE id = NEW.hotel_id FOR UPDATE;

    IF available <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Stok kamar habis untuk hotel ini';
    ELSE
        -- kurangi stok
        UPDATE hotel
        SET total_kamar = total_kamar - 1
        WHERE id = NEW.hotel_id;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `phone`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin', '', 'admin@gmail.com', '21232f297a57a5a743894a0e4a801fc3', 'admin', '2025-09-30 13:19:44'),
(2, 'user', 'user', '', 'user@gmail.com', 'ee11cbb19052e40b07aac0ca060c23ee', 'user', '2025-09-30 13:19:44');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `hotel`
--
ALTER TABLE `hotel`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `hotel`
--
ALTER TABLE `hotel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`hotel_id`) REFERENCES `hotel` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
