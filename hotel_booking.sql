-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 11 Okt 2025 pada 11.52
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
-- Database: `hotel_booking`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_code` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','checked_in','checked_out') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `room_id`, `check_in`, `check_out`, `total_price`, `status`, `created_at`) VALUES
(1, 'BK20251011113214982', 2, 9, '2025-10-18', '2025-10-22', 2600000.00, 'pending', '2025-10-11 16:32:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `payment_method` enum('cash','credit_card','transfer','ewallet') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','failed') DEFAULT 'pending',
  `payment_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `payment_method`, `amount`, `status`, `payment_date`) VALUES
(1, 1, 'transfer', 2600000.00, 'pending', '2025-10-11 16:32:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `image` varchar(100) NOT NULL,
  `room_number` varchar(10) NOT NULL,
  `room_type_id` int(11) NOT NULL,
  `floor` int(11) NOT NULL,
  `status` enum('available','maintenance','booked') DEFAULT 'available',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rooms`
--

INSERT INTO `rooms` (`id`, `image`, `room_number`, `room_type_id`, `floor`, `status`, `created_at`) VALUES
(1, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '101', 1, 1, 'available', '2025-10-11 15:29:12'),
(2, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '102', 1, 1, 'available', '2025-10-11 15:29:12'),
(3, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '201', 2, 2, 'available', '2025-10-11 15:29:12'),
(4, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '202', 2, 2, 'available', '2025-10-11 15:29:12'),
(5, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '301', 3, 3, 'available', '2025-10-11 15:29:12'),
(6, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '302', 3, 3, 'available', '2025-10-11 15:29:12'),
(7, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '401', 4, 4, 'available', '2025-10-11 15:29:12'),
(8, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '402', 4, 4, 'available', '2025-10-11 15:29:12'),
(9, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '501', 5, 5, 'booked', '2025-10-11 15:29:12'),
(10, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '502', 5, 5, 'available', '2025-10-11 15:29:12'),
(11, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '601', 6, 6, 'available', '2025-10-11 15:29:12'),
(12, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '602', 6, 6, 'available', '2025-10-11 15:29:12'),
(13, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '701', 7, 7, 'available', '2025-10-11 15:29:12'),
(14, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '702', 7, 7, 'available', '2025-10-11 15:29:12'),
(15, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '801', 8, 8, 'available', '2025-10-11 15:29:12'),
(16, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '802', 8, 8, 'available', '2025-10-11 15:29:12'),
(17, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '901', 9, 9, 'available', '2025-10-11 15:29:12'),
(18, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '902', 9, 9, 'available', '2025-10-11 15:29:12'),
(19, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '1001', 10, 10, 'available', '2025-10-11 15:29:12'),
(20, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '1002', 10, 10, 'available', '2025-10-11 15:29:12'),
(21, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '1003', 11, 11, 'available', '2025-10-11 15:29:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `room_availability`
--

CREATE TABLE `room_availability` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `is_booked` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `room_types`
--

CREATE TABLE `room_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int(11) NOT NULL,
  `facilities` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `price_per_night`, `capacity`, `facilities`, `created_at`) VALUES
(1, 'Standard Room', 'Comfortable room with basic amenities for a pleasant stay.', 500000.00, 2, 'WiFi,TV,AC,Kamar Mandi Dalam', '2025-10-11 15:26:31'),
(2, 'Deluxe Room', 'Spacious room with premium amenities and city view.', 750000.00, 2, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan', '2025-10-11 15:26:31'),
(3, 'Suite Room', 'Luxurious suite with separate living area and premium services.', 1200000.00, 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan,Ruang Tamu,Pemandangan Kota', '2025-10-11 15:26:31'),
(4, 'Family Room', 'Perfect for families, with extra space and amenities for children.', 900000.00, 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan', '2025-10-11 15:26:31'),
(5, 'Superior Room', 'Upgraded room with larger space and modern interior.', 650000.00, 2, 'WiFi,TV,AC,Kamar Mandi Dalam,Sarapan', '2025-10-11 15:26:31'),
(6, 'Executive Room', 'Elegant room for business travelers with work desk and lounge access.', 850000.00, 2, 'WiFi,TV,AC,Kamar Mandi Dalam,Sarapan,Meja Kerja,Lounge Access', '2025-10-11 15:26:31'),
(7, 'Junior Suite', 'Stylish suite with compact living area and minibar.', 1100000.00, 3, 'WiFi,TV,AC,Kamar Mandi Dalam,Minibar,Ruang Tamu Mini', '2025-10-11 15:26:31'),
(8, 'Presidential Suite', 'Top-tier suite with luxury facilities, dining area, and Jacuzzi.', 2500000.00, 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan,Ruang Tamu,Bathtub Jacuzzi', '2025-10-11 15:26:31'),
(9, 'Twin Room', 'Comfortable twin beds for friends or colleagues.', 600000.00, 2, 'WiFi,TV,AC,Kamar Mandi Dalam', '2025-10-11 15:26:31'),
(10, 'Single Room', 'Simple room for solo traveler with essential amenities.', 400000.00, 1, 'WiFi,TV,AC,Kamar Mandi Dalam', '2025-10-11 15:26:31'),
(11, 'Honeymoon Suite', 'Romantic suite with special decoration and beautiful view.', 1300000.00, 2, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Bathtub,Balkon,Pemandangan', '2025-10-11 15:26:31'),
(12, 'Ocean View Room', 'Room with stunning ocean view and king-size bed.', 950000.00, 2, 'WiFi,TV,AC,Kamar Mandi Dalam,Sarapan,Balkon,View Laut', '2025-10-11 15:26:31'),
(13, 'Garden View Room', 'Room overlooking the hotel garden for a relaxing stay.', 850000.00, 2, 'WiFi,TV,AC,Kamar Mandi Dalam,Sarapan,View Taman', '2025-10-11 15:26:31'),
(14, 'Penthouse Suite', 'Exclusive top-floor suite with panoramic city view and private balcony.', 3000000.00, 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan,Jacuzzi,Balkon Pribadi,View Kota', '2025-10-11 15:26:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Admin', 'admin@gmail.com', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-10-11 15:35:10'),
(2, 'Fahmi XD', 'fahmixd404@gmail.com', '0847574545454', '$2y$10$JRFAbmB5XurkcjkG/dP2iOgI1cbwbMtv2SsAggU1mgjNs.ZsLxXu2', 'user', '2025-10-11 15:47:39');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `fk_booking_room` (`room_id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_booking` (`booking_id`);

--
-- Indeks untuk tabel `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_room_type` (`room_type_id`);

--
-- Indeks untuk tabel `room_availability`
--
ALTER TABLE `room_availability`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_availability_room` (`room_id`);

--
-- Indeks untuk tabel `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT untuk tabel `room_availability`
--
ALTER TABLE `room_availability`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `room_availability`
--
ALTER TABLE `room_availability`
  ADD CONSTRAINT `fk_availability_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
