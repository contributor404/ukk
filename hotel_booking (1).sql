-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 20, 2025 at 06:18 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

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
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int NOT NULL,
  `booking_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','checked_in','checked_out','paid','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_code`, `user_id`, `room_id`, `check_in`, `check_out`, `total_price`, `status`, `created_at`) VALUES
(16, 'BK20251020045843900', 1, 24, '2025-10-20', '2025-10-22', 1200000.00, 'confirmed', '2025-10-20 11:58:43');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `booking_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','failed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `status`, `payment_date`) VALUES
(15, 16, 1200000.00, 'paid', '2025-10-20 11:59:13');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `image` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `room_number` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `room_type_id` int NOT NULL,
  `floor` int NOT NULL,
  `status` enum('available','maintenance','booked') COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `image`, `room_number`, `room_type_id`, `floor`, `status`, `created_at`) VALUES
(24, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '01', 9, 1, 'booked', '2025-10-20 11:10:15'),
(25, 'https://i.pinimg.com/736x/42/b6/8c/42b68cd2490f7a0467234a71b4d4d6fb.jpg', '2', 9, 1, 'available', '2025-10-20 11:11:05');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL,
  `facilities` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `price_per_night`, `capacity`, `facilities`, `created_at`) VALUES
(1, 'Standard Room', 'Comfortable room with basic amenities for a pleasant stay.', 500000.00, 2, 'WiFi,TV,AC,Kamar Mandi Dalam', '2025-10-11 15:26:31'),
(2, 'Deluxe Room', 'Spacious room with premium amenities and city view.', 750000.00, 2, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan', '2025-10-11 15:26:31'),
(3, 'Suite Room', 'Luxurious suite with separate living area and premium services.', 1200000.00, 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan,Ruang Tamu,Pemandangan Kota', '2025-10-11 15:26:31'),
(4, 'Family Room', 'Perfect for families, with extra space and amenities for children.', 900000.00, 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan', '2025-10-11 15:26:31'),
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Fahmi XD', 'fahmixd404@gmail.com', '08345987239485', '$2y$10$mpcuV69DVeWJb4tUQqOJredW3iIV9ufvhwjoYt3J8w/KyPA4YFzOa', 'user', '2025-10-20 07:42:20'),
(2, 'Admin', 'admin@gmail.com', '08345987239485', '$2y$10$mpcuV69DVeWJb4tUQqOJredW3iIV9ufvhwjoYt3J8w/KyPA4YFzOa', 'admin', '2025-10-20 07:42:20'),
(3, 'Mufly', 'kisti.59@smk.belajar.id', '23453', '$2y$10$V4qPGCo/bSef/iApgaGtvuT2CUP.aoBznykNeDY5cf3cOrqQPiLDO', 'user', '2025-10-20 09:08:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking_user` (`user_id`),
  ADD KEY `fk_booking_room` (`room_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_payment_booking` (`booking_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_room_type` (`room_type_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
