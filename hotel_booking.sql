-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 18, 2025 at 03:27 PM
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
  `booking_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `total_price` decimal(50,2) NOT NULL,
  `status` enum('pending','confirmed','cancelled','checked_in','checked_out','paid','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` int NOT NULL,
  `tanggal` date DEFAULT NULL,
  `keterangan` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jumlah` decimal(10,2) DEFAULT NULL,
  `kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengeluaran`
--

INSERT INTO `pengeluaran` (`id`, `tanggal`, `keterangan`, `jumlah`, `kategori`, `user_id`) VALUES
(2, '2025-11-18', 'Pembayaran listrik', '1000000.00', 'Operasional', 2);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `room_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `room_type_id` int NOT NULL,
  `floor` int NOT NULL,
  `status` enum('available','maintenance','booked') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `image`, `room_number`, `room_type_id`, `floor`, `status`, `created_at`) VALUES
(101, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '101', 1, 1, 'available', '2025-11-19 09:20:00'),
(102, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '102', 1, 1, 'available', '2025-11-19 09:20:01'),
(103, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '103', 1, 1, 'available', '2025-11-19 09:20:02'),
(104, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '104', 1, 1, 'available', '2025-11-19 09:20:03'),
(105, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '105', 9, 1, 'available', '2025-11-19 09:20:45'),
(106, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '106', 9, 1, 'available', '2025-11-19 09:20:46'),
(107, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '107', 9, 1, 'available', '2025-11-19 09:20:47'),
(108, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '108', 10, 1, 'available', '2025-11-19 09:20:51'),
(109, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '109', 10, 1, 'available', '2025-11-19 09:20:52'),
(110, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '110', 10, 1, 'available', '2025-11-19 09:20:53'),
(111, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '111', 10, 1, 'available', '2025-11-19 09:20:54'),
(112, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '112', 10, 1, 'available', '2025-11-19 09:20:55'),
(113, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '113', 10, 1, 'available', '2025-11-19 09:20:56'),
(114, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '114', 13, 1, 'available', '2025-11-19 09:21:10'),
(115, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '115', 13, 1, 'available', '2025-11-19 09:21:13'),
(116, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '116', 13, 1, 'available', '2025-11-19 09:21:16'),
(201, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '201', 1, 2, 'available', '2025-11-19 09:20:04'),
(202, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '202', 1, 2, 'available', '2025-11-19 09:20:05'),
(203, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '203', 1, 2, 'available', '2025-11-19 09:20:06'),
(204, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '204', 4, 2, 'available', '2025-11-19 09:20:20'),
(205, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '205', 4, 2, 'available', '2025-11-19 09:20:21'),
(206, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '206', 4, 2, 'available', '2025-11-19 09:20:22'),
(207, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '207', 4, 2, 'available', '2025-11-19 09:20:23'),
(208, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '208', 4, 2, 'available', '2025-11-19 09:20:24'),
(209, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '209', 4, 2, 'available', '2025-11-19 09:20:25'),
(210, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '210', 9, 2, 'available', '2025-11-19 09:20:48'),
(211, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '211', 9, 2, 'available', '2025-11-19 09:20:49'),
(212, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '212', 9, 2, 'available', '2025-11-19 09:20:50'),
(213, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '213', 13, 2, 'available', '2025-11-19 09:21:11'),
(214, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '214', 13, 2, 'available', '2025-11-19 09:21:14'),
(301, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '301', 2, 3, 'available', '2025-11-19 09:20:07'),
(302, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '302', 2, 3, 'available', '2025-11-19 09:20:08'),
(303, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '303', 2, 3, 'available', '2025-11-19 09:20:09'),
(304, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '304', 2, 3, 'available', '2025-11-19 09:20:10'),
(305, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '305', 2, 3, 'available', '2025-11-19 09:20:11'),
(306, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '306', 2, 3, 'available', '2025-11-19 09:20:12'),
(307, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '307', 2, 3, 'available', '2025-11-19 09:20:13'),
(308, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '308', 12, 3, 'available', '2025-11-19 09:21:03'),
(309, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '309', 12, 3, 'available', '2025-11-19 09:21:06'),
(310, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '310', 12, 3, 'available', '2025-11-19 09:21:09'),
(311, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '311', 13, 3, 'available', '2025-11-19 09:21:12'),
(312, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '312', 13, 3, 'available', '2025-11-19 09:21:15'),
(401, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '401', 3, 4, 'available', '2025-11-19 09:20:14'),
(402, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '402', 3, 4, 'available', '2025-11-19 09:20:15'),
(403, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '403', 3, 4, 'available', '2025-11-19 09:20:16'),
(404, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '404', 3, 4, 'available', '2025-11-19 09:20:17'),
(405, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '405', 3, 4, 'available', '2025-11-19 09:20:18'),
(406, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '406', 3, 4, 'available', '2025-11-19 09:20:19'),
(407, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '407', 7, 4, 'available', '2025-11-19 09:20:33'),
(408, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '408', 7, 4, 'available', '2025-11-19 09:20:34'),
(409, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '409', 7, 4, 'available', '2025-11-19 09:20:35'),
(410, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '410', 7, 4, 'available', '2025-11-19 09:20:36'),
(411, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '411', 7, 4, 'available', '2025-11-19 09:20:37'),
(412, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '412', 7, 4, 'available', '2025-11-19 09:20:38'),
(413, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '413', 12, 4, 'available', '2025-11-19 09:21:04'),
(414, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '414', 12, 4, 'available', '2025-11-19 09:21:07'),
(501, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '501', 6, 5, 'available', '2025-11-19 09:20:26'),
(502, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '502', 6, 5, 'available', '2025-11-19 09:20:27'),
(503, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '503', 6, 5, 'available', '2025-11-19 09:20:28'),
(504, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '504', 6, 5, 'available', '2025-11-19 09:20:29'),
(505, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '505', 6, 5, 'available', '2025-11-19 09:20:30'),
(506, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '506', 6, 5, 'available', '2025-11-19 09:20:31'),
(507, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '507', 6, 5, 'available', '2025-11-19 09:20:32'),
(508, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '508', 12, 5, 'available', '2025-11-19 09:21:05'),
(509, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '509', 12, 5, 'available', '2025-11-19 09:21:08'),
(601, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '601', 11, 6, 'available', '2025-11-19 09:20:57'),
(602, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '602', 11, 6, 'available', '2025-11-19 09:20:58'),
(603, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '603', 11, 6, 'available', '2025-11-19 09:20:59'),
(604, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '604', 11, 6, 'available', '2025-11-19 09:21:00'),
(605, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '605', 11, 6, 'available', '2025-11-19 09:21:01'),
(606, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '606', 11, 6, 'available', '2025-11-19 09:21:02'),
(701, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '701', 8, 7, 'available', '2025-11-19 09:20:39'),
(702, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '702', 8, 7, 'available', '2025-11-19 09:20:40'),
(703, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '703', 8, 7, 'available', '2025-11-19 09:20:41'),
(704, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '704', 8, 7, 'available', '2025-11-19 09:20:42'),
(705, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '705', 8, 7, 'available', '2025-11-19 09:20:43'),
(706, '691c831d08df1_1763476253.jpg,691c831d09253_1763476253.jpg,691c831d09513_1763476253.jpg,691c831d097fe_1763476253.jpg', '706', 8, 7, 'available', '2025-11-19 09:20:44'),
(707, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '707', 14, 7, 'available', '2025-11-19 09:21:17'),
(708, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '708', 14, 7, 'available', '2025-11-19 09:21:18'),
(709, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '709', 14, 7, 'available', '2025-11-19 09:21:19'),
(710, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '710', 14, 7, 'available', '2025-11-19 09:21:20'),
(711, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '711', 14, 7, 'available', '2025-11-19 09:21:21'),
(712, '691c81f656d6b_1763475958.jpg,691c81f6571a0_1763475958.jpg,691c81f6574de_1763475958.jpg,691c81f657f5b_1763475958.jpg', '712', 14, 7, 'available', '2025-11-19 09:21:22');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `price_per_night` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL,
  `facilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`id`, `name`, `description`, `price_per_night`, `capacity`, `facilities`, `created_at`) VALUES
(1, 'Standard Room', 'Comfortable room with basic amenities for a pleasant stay.', '500000.00', 2, 'WiFi,TV,AC,Kamar Mandi Dalam', '2025-10-11 15:26:31'),
(2, 'Deluxe Room', 'Spacious room with premium amenities and city view.', '750000.00', 2, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan', '2025-10-11 15:26:31'),
(3, 'Suite Room', 'Luxurious suite with separate living area and premium services.', '1200000.00', 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan,Ruang Tamu,Pemandangan Kota', '2025-10-11 15:26:31'),
(4, 'Family Room', 'Perfect for families, with extra space and amenities for children.', '900000.00', 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan', '2025-10-11 15:26:31'),
(6, 'Executive Room', 'Elegant room for business travelers with work desk and lounge access.', '850000.00', 2, 'WiFi,TV,AC,Kamar Mandi Dalam,Sarapan,Meja Kerja,Lounge Access', '2025-10-11 15:26:31'),
(7, 'Junior Suite', 'Stylish suite with compact living area and minibar.', '1100000.00', 3, 'WiFi,TV,AC,Kamar Mandi Dalam,Minibar,Ruang Tamu Mini', '2025-10-11 15:26:31'),
(8, 'Presidential Suite', 'Top-tier suite with luxury facilities, dining area, and Jacuzzi.', '2500000.00', 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan,Ruang Tamu,Bathtub Jacuzzi', '2025-10-11 15:26:31'),
(9, 'Twin Room', 'Comfortable twin beds for friends or colleagues.', '600000.00', 2, 'WiFi,TV,AC,Kamar Mandi Dalam', '2025-10-11 15:26:31'),
(10, 'Single Room', 'Simple room for solo traveler with essential amenities.', '400000.00', 1, 'WiFi,TV,AC,Kamar Mandi Dalam', '2025-10-11 15:26:31'),
(11, 'Honeymoon Suite', 'Romantic suite with special decoration and beautiful view.', '1300000.00', 2, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Bathtub,Balkon,Pemandangan', '2025-10-11 15:26:31'),
(12, 'Ocean View Room', 'Room with stunning ocean view and king-size bed.', '950000.00', 2, 'WiFi,TV,AC,Kamar Mandi Dalam,Sarapan,Balkon,View Laut', '2025-10-11 15:26:31'),
(13, 'Garden View Room', 'Room overlooking the hotel garden for a relaxing stay.', '850000.00', 2, 'WiFi,TV,AC,Kamar Mandi Dalam,Sarapan,View Taman', '2025-10-11 15:26:31'),
(14, 'Penthouse Suite', 'Exclusive top-floor suite with panoramic city view and private balcony.', '3000000.00', 4, 'WiFi,TV,AC,Minibar,Kamar Mandi Dalam,Sarapan,Jacuzzi,Balkon Pribadi,View Kota', '2025-10-11 15:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `profile_pic` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `profile_pic`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'Fahmi', '1_1763517462.png', 'fahmixd404@gmail.com', '08345987239485', '$2y$10$mpcuV69DVeWJb4tUQqOJredW3iIV9ufvhwjoYt3J8w/KyPA4YFzOa', 'user', '2025-10-20 07:42:20'),
(2, 'Admin', '2_1763015594.jpg', 'admin@gmail.com', '08345987239485', '$2y$10$oPJ3O.qH6An1PwiJPgWwgO4qfFBxoO.hKWbeK98rW5B2OKobTYaxa', 'admin', '2025-10-20 07:42:20'),
(3, 'Mufly', '', 'mufly@smk.belajar.id', '23453', '$2y$10$V4qPGCo/bSef/iApgaGtvuT2CUP.aoBznykNeDY5cf3cOrqQPiLDO', 'user', '2025-10-20 09:08:10');

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
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=713;

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
-- Constraints for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD CONSTRAINT `user_id_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `fk_room_type` FOREIGN KEY (`room_type_id`) REFERENCES `room_types` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
