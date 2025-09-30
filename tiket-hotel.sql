-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 30 Sep 2025 pada 16.49
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
  `image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `harga` int(11) DEFAULT 0,
  `slug` varchar(200) DEFAULT NULL,
  `total_kamar` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `hotel`
--

INSERT INTO `hotel` (`id`, `name`, `image`, `description`, `harga`, `slug`, `total_kamar`) VALUES
(1, 'Hotel Sakura', 'https://picsum.photos/seed/hotel1/600/400', 'Hotel bergaya Jepang dengan suasana tenang dan nyaman.', 450000, 'hotel-sakura-1', 10),
(2, 'Grand Horizon', 'https://picsum.photos/seed/hotel2/600/400', 'Hotel mewah dengan pemandangan laut dan fasilitas lengkap.', 1200000, 'grand-horizon-2', 10),
(3, 'Sunrise Inn', 'https://picsum.photos/seed/hotel3/600/400', 'Penginapan sederhana dekat pusat kota, cocok untuk backpacker.', 250000, 'sunrise-inn-3', 10),
(4, 'Mountain View Lodge', 'https://picsum.photos/seed/hotel4/600/400', 'Hotel dengan pemandangan gunung indah dan udara segar.', 600000, 'mountain-view-lodge-4', 10),
(5, 'Ocean Breeze Resort', 'https://picsum.photos/seed/hotel5/600/400', 'Resor tepi pantai dengan kolam renang dan restoran seafood.', 950000, 'ocean-breeze-resort-5', 10),
(6, 'City Light Hotel', 'https://picsum.photos/seed/hotel6/600/400', 'Hotel modern di pusat kota dengan akses transportasi mudah.', 700000, 'city-light-hotel-6', 10),
(7, 'Green Valley Hotel', 'https://picsum.photos/seed/hotel7/600/400', 'Penginapan asri dengan taman hijau dan udara sejuk.', 400000, 'green-valley-hotel-7', 10),
(8, 'Royal Heritage', 'https://picsum.photos/seed/hotel8/600/400', 'Hotel klasik dengan desain elegan dan layanan premium.', 1500000, 'royal-heritage-8', 10),
(9, 'Blue Lagoon Inn', 'https://picsum.photos/seed/hotel9/600/400', 'Hotel kecil dekat danau dengan suasana romantis.', 550000, 'blue-lagoon-inn-9', 10),
(10, 'Golden Palm Resort', 'https://picsum.photos/seed/hotel10/600/400', 'Resor tropis dengan fasilitas spa, gym, dan private villa.', 2000000, 'golden-palm-resort-10', 10),
(11, 'Hotel OYO', 'images/1759239755_349_hmpp.gif', 'Gak perlu kata kata yang penting bukti nyata', 1200000, 'hotel-oyo-11', 9);

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
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

INSERT INTO `orders` (`id`, `code`, `hotel_id`, `user_email`, `nama_pemesan`, `phone`, `nights`, `total`, `payment_method`, `status`, `created_at`, `kamar_no`) VALUES
(16, 'ORD1759243286880', 1, 'user@gmail.com', 'user', '0', 2, 900000, 'Transfer Bank', 'paid', '2025-09-30 21:41:26', 1),
(17, 'ORD1759243566848', 11, 'user@gmail.com', 'user', '0', 4, 4800000, 'e-Wallet', 'pending', '2025-09-30 21:46:06', 1);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

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
