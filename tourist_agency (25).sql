-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2025 at 09:49 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tourist_agency`
--

-- --------------------------------------------------------

--
-- Table structure for table `about`
--

CREATE TABLE `about` (
  `id` int(11) NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `map_url` text NOT NULL,
  `about_text` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `about`
--

INSERT INTO `about` (`id`, `company_name`, `address`, `phone`, `email`, `map_url`, `about_text`, `created_at`, `updated_at`) VALUES
(1, 'TRAVIUQE', 'Chasara, Narayanganj, Bangladesh', '+88019XXXXXXXX', 'shimzo@gmail.com', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3655.3907433308436!2d90.49672352438745!3d23.62617364353597!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b10f2e4b60f5%3A0x87beeecd7f88883!2sChashara%2C%20Narayanganj!5e0!3m2!1sen!2sbd!4v1754198583266!5m2!1sen!2sbd', 'Have questions about our travel packages? Need assistance with your booking? Our team is here to help you plan your perfect getaway.', '2025-08-19 18:42:44', '2025-08-19 19:40:58');

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `email`, `password`, `created_at`, `updated_at`, `last_login`, `status`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$/Jf/wrMEodGtV3vWAcGoPu28PMfZTY8mbfG0dMqLUFffSvVxIqI8a', '2025-07-30 06:35:44', '2025-07-30 06:35:44', '2025-07-30 20:30:08', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `admin_sessions`
--

CREATE TABLE `admin_sessions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 8 hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_sessions`
--

INSERT INTO `admin_sessions` (`id`, `admin_id`, `token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(1, 1, 'a4bbcb452e8a6fb6eaa508439d4ce9b9d2d24b23d486ba37065de91af1e12ae5', '::1', 'PostmanRuntime/7.44.1', '2025-07-30 14:30:08', '2025-07-30 22:30:08');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `package_count` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `deposit_date` datetime DEFAULT NULL,
  `due_deadline` datetime DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `special_requests` text DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `due_amount` decimal(10,2) DEFAULT 0.00,
  `full_payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `number` varchar(15) NOT NULL,
  `pin` int(10) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_status` enum('pending','deposit_paid','fully_paid','cancelled') DEFAULT 'pending',
  `payment_approved` tinyint(1) DEFAULT 0,
  `approval_date` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `memo_number` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `cancelled_by_admin` tinyint(1) DEFAULT 0,
  `cancellation_reason` text DEFAULT NULL,
  `cancellation_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `customer_name`, `customer_email`, `package_id`, `booking_date`, `start_date`, `end_date`, `package_count`, `total_price`, `deposit_amount`, `deposit_date`, `due_deadline`, `status`, `special_requests`, `paid_amount`, `due_amount`, `full_payment_date`, `payment_method`, `number`, `pin`, `transaction_id`, `payment_status`, `payment_approved`, `approval_date`, `approved_by`, `payment_proof`, `memo_number`, `remarks`, `cancelled_by_admin`, `cancellation_reason`, `cancellation_date`) VALUES
(26, 3, 'shimanto', 'shimanto@gmail.com', 12, '2025-08-09', '2025-08-26', '2025-08-29', 1, 125000.00, 12500.00, '2025-08-09 10:33:51', NULL, 'cancelled', NULL, 12500.00, 112500.00, NULL, 'bkash', '', 0, '123456', 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 1, 'Test reason', '2025-08-13 22:33:10'),
(28, 3, 'shimanto', 'shimanto@gmail.com', 10, '2025-08-09', '2025-08-26', '2025-08-28', 1, 64000.00, 6400.00, '2025-08-09 11:00:28', NULL, 'cancelled', NULL, 6400.00, 57600.00, NULL, 'bkash', '01949854504', 12345, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 1, 'None', '2025-08-13 22:50:12'),
(29, 3, 'shimanto', 'shimanto@gmail.com', 12, '2025-08-09', '2025-08-26', '2025-08-29', 1, 125000.00, 12500.00, '2025-08-09 11:06:57', NULL, 'cancelled', NULL, 12500.00, 112500.00, NULL, 'bkash', '01949854504', 12323, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 1, 'None', '2025-08-13 22:56:18'),
(30, 3, 'shimanto', 'shimanto@gmail.com', 10, '2025-08-09', '2025-08-20', '2025-08-22', 1, 64000.00, 6400.00, '2025-08-09 11:44:41', NULL, 'confirmed', NULL, 6400.00, 57600.00, NULL, 'bkash', '01949854504', 12345, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(34, 4, 'moynul', 'moynulislamshimanto24@gmail.com', 1, '2025-08-09', '2025-08-14', '2025-08-17', 1, 150000.00, 15000.00, '2025-08-09 20:51:36', NULL, 'confirmed', NULL, 15000.00, 135000.00, NULL, 'bkash', '01949854504', 12234, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(38, 4, 'Moynul', 'moynulislamshimanto24@gmail.com', 2, '2025-08-12', '2025-08-14', '2025-08-17', 1, 180000.00, 180000.00, '2025-08-12 21:55:00', NULL, 'cancelled', NULL, 180000.00, 0.00, NULL, 'bkash', '01933333333', 12345, NULL, 'fully_paid', 1, '2025-08-13 00:58:01', 1, NULL, 'MEMO-20250812-00038', '', 1, 'None', '2025-08-13 23:09:38'),
(40, 4, 'Moynul', 'moynulislamshimanto24@gmail.com', 8, '2025-08-12', '2025-08-29', '2025-09-01', 1, 40000.00, 0.00, NULL, NULL, 'cancelled', NULL, 40000.00, 0.00, '2025-08-12 22:05:32', 'bkash', '01933333333', 12345, NULL, 'fully_paid', 1, '2025-08-13 00:58:23', 1, NULL, 'MEMO-20250812-00040', '', 1, 'No\n', '2025-08-13 23:11:14'),
(43, 4, 'Moynul', 'moynulislamshimanto24@gmail.com', 12, '2025-08-12', '2025-08-20', '2025-08-23', 1, 125000.00, 12500.00, '2025-08-12 23:15:19', NULL, 'cancelled', NULL, 125000.00, 0.00, '2025-08-12 23:15:44', 'bkash', '01933333333', 12345, NULL, 'fully_paid', 1, '2025-08-13 01:11:20', 1, NULL, 'MEMO-20250812-00043', '', 1, 'none', '2025-08-15 14:58:01'),
(44, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 3, '2025-08-13', '2025-08-26', '2025-08-29', 1, 130000.00, 0.00, NULL, NULL, 'cancelled', NULL, 130000.00, 0.00, '2025-08-13 01:15:50', 'bkash', '01933333333', 12345, NULL, 'fully_paid', 1, '2025-08-13 01:16:25', 1, NULL, 'MEMO-20250812-00044', '', 1, 'Ticket Short', '2025-08-13 23:04:49'),
(45, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 7, '2025-08-13', '2025-08-15', '2025-08-17', 1, 50000.00, 0.00, NULL, NULL, 'cancelled', NULL, 50000.00, 0.00, '2025-08-13 21:35:19', 'bkash', '01933333333', 12345, NULL, 'fully_paid', 1, '2025-08-13 21:35:39', 1, NULL, 'MEMO-20250813-00045', '', 1, 'None', '2025-08-13 23:03:44'),
(46, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 7, '2025-08-13', '2025-08-21', '2025-08-23', 1, 50000.00, 5000.00, '2025-08-13 22:59:15', NULL, 'cancelled', NULL, 5000.00, 45000.00, NULL, 'bkash', '01933333333', 12345, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 1, 'None', '2025-08-13 23:08:01'),
(47, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 1, '2025-08-13', '2025-08-22', '2025-08-25', 1, 150000.00, 15000.00, '2025-08-13 23:12:53', NULL, 'cancelled', NULL, 15000.00, 135000.00, NULL, 'bkash', '01933333333', 12348, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 1, 'None', '2025-08-13 23:13:49'),
(48, NULL, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 2, '2025-08-15', '2025-08-27', '2025-08-30', 1, 180000.00, 0.00, NULL, NULL, 'pending', NULL, 0.00, 0.00, NULL, NULL, '', 0, NULL, 'pending', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(49, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 1, '2025-08-15', '2025-09-05', '2025-09-08', 1, 150000.00, 15000.00, '2025-08-15 13:20:09', NULL, 'cancelled', NULL, 825000.00, -675000.00, '2025-08-15 14:47:52', 'bkash', '01933333333', 12345, NULL, 'fully_paid', 1, '2025-08-15 14:49:00', 1, NULL, 'MEMO-20250815-00049', '', 1, 'Flight short', '2025-08-15 15:01:06'),
(50, NULL, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 2, '2025-08-15', '2025-08-31', '2025-09-03', 1, 180000.00, 0.00, NULL, NULL, 'pending', NULL, 0.00, 0.00, NULL, NULL, '', 0, NULL, 'pending', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(51, NULL, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 12, '2025-08-15', '2025-09-04', '2025-09-07', 1, 125000.00, 0.00, NULL, NULL, 'pending', NULL, 0.00, 0.00, NULL, NULL, '', 0, NULL, 'pending', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(52, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 1, '2025-08-16', '2025-09-05', '2025-09-08', 1, 150000.00, 15000.00, '2025-08-16 09:22:40', NULL, 'confirmed', NULL, 150000.00, 0.00, '2025-08-16 09:23:25', 'bkash', '01933333333', 12345, NULL, 'fully_paid', 1, '2025-08-16 09:23:42', 1, NULL, 'MEMO-20250816-00052', '', 0, NULL, NULL),
(53, 3, 'shimanto', 'shimanto@gmail.com', 1, '2025-08-18', '2025-09-05', '2025-09-08', 1, 150000.00, 15000.00, '2025-08-18 22:57:16', '2025-08-25 22:57:16', 'confirmed', NULL, 15000.00, 135000.00, NULL, 'bkash', '01949854504', 12345, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, '\nPayment reminder sent: 2025-08-19 12:13:35', 0, NULL, NULL),
(56, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 1, '2025-08-18', '2025-09-10', '2025-09-13', 1, 150000.00, 15000.00, '2025-08-18 23:15:06', '2025-08-25 00:00:00', 'confirmed', NULL, 15000.00, 135000.00, NULL, 'bkash', '01949854504', 12343, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(57, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 1, '2025-08-18', '2025-09-11', '2025-09-14', 1, 150000.00, 15000.00, '2025-08-18 23:17:54', '2025-08-25 23:59:59', 'confirmed', NULL, 15000.00, 135000.00, NULL, 'bkash', '01949854504', 12345, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(58, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 1, '2025-08-18', '2025-09-18', '2025-09-21', 1, 150000.00, 15000.00, '2025-08-18 23:22:23', '2025-08-25 23:59:59', 'confirmed', NULL, 15000.00, 135000.00, NULL, 'bkash', '01933333333', 12334, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(59, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 2, '2025-08-18', '2025-09-19', '2025-09-22', 1, 180000.00, 18000.00, '2025-08-18 23:24:22', '2025-08-25 23:59:59', 'confirmed', NULL, 36000.00, 144000.00, NULL, 'bkash', '01933333333', 12222, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL),
(60, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 7, '2025-08-19', '2025-09-16', '2025-09-18', 1, 50000.00, 5000.00, '2025-08-19 09:24:08', '2025-08-26 23:59:59', 'confirmed', NULL, 50000.00, 0.00, '2025-08-19 10:58:58', 'bkash', '01949854504', 12345, NULL, 'fully_paid', 1, '2025-08-19 11:36:57', 1, NULL, 'MEMO-20250819-00060', '', 0, NULL, NULL),
(61, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 1, '2025-08-19', '2025-09-18', '2025-09-21', 1, 150000.00, 15000.00, '2025-08-19 09:38:28', '2025-08-26 23:59:59', 'confirmed', NULL, 15000.00, 135000.00, NULL, 'bkash', '01949854504', 12345, NULL, 'deposit_paid', 0, NULL, NULL, NULL, NULL, '2025-08-19', 0, NULL, NULL),
(62, 4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 2, '2025-08-19', '2025-09-16', '2025-09-19', 1, 180000.00, 0.00, NULL, NULL, 'confirmed', NULL, 180000.00, 0.00, '2025-08-19 12:30:36', 'bkash', '01949854504', 12345, NULL, 'fully_paid', 1, '2025-08-19 12:34:00', 1, NULL, 'MEMO-20250819-00062', '', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  `responded` tinyint(1) DEFAULT 0,
  `response` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_submissions`
--

INSERT INTO `contact_submissions` (`id`, `name`, `email`, `subject`, `message`, `submitted_at`, `is_read`, `responded`, `response`, `responded_at`, `responded_by`) VALUES
(1, 'John Doe', 'john@example.com', 'Test Message', 'Hello, this is a test.', '2025-08-19 16:23:45', 1, 0, NULL, NULL, NULL),
(2, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', 'no', 'no', '2025-08-19 16:24:08', 1, 1, 'oK', '2025-08-20 00:21:28', 1),
(3, 'Mofazzal Hossain', 'moynulislamshimanto24@gmail.com', 'Hossain', 'no more today', '2025-08-19 18:24:22', 1, 1, 'ok', '2025-08-20 00:37:25', 1);

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `description`, `location`, `price`, `image_url`, `is_featured`, `created_at`) VALUES
(1, 'Thailand', 'Experience the beautiful beaches and rich culture of Thailand', 'Bangkok, Phuket, Chiang Mai', 0.00, 'assets/img/package-1.jpg', 1, '2025-07-30 16:17:06'),
(2, 'Indonesia', 'Discover the tropical paradise of Bali and other Indonesian islands', 'Bali, Jakarta, Lombok', 0.00, 'assets/img/package-2.jpg', 1, '2025-07-30 16:17:06'),
(3, 'Malaysia', 'Explore the diverse landscapes from Kuala Lumpur to Langkawi', 'Kuala Lumpur, Penang, Langkawi', 0.00, 'assets/img/package-3.jpg', 1, '2025-07-30 16:17:06'),
(4, 'Thailand', 'Beautiful beaches and temples', 'Bangkok, Phuket', 0.00, 'assets/img/destinations/688cd048e4bac.jpg', 1, '2025-08-01 14:33:44'),
(5, 'India', 'ok', 'Goa', 0.00, 'assets/img/destinations/688cd33cdbea8.jpg', 1, '2025-08-01 14:46:20');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `hotel_name` varchar(250) NOT NULL,
  `hotel_location` varchar(250) DEFAULT NULL,
  `nights` int(11) NOT NULL,
  `event_name` varchar(250) NOT NULL,
  `event_description` varchar(250) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `duration` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `inclusions` text DEFAULT NULL,
  `exclusions` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT 'package-default.jpg',
  `is_featured` tinyint(1) DEFAULT 0,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `destination_id`, `name`, `hotel_name`, `hotel_location`, `nights`, `event_name`, `event_description`, `event_date`, `duration`, `price`, `inclusions`, `exclusions`, `image_url`, `is_featured`, `description`) VALUES
(1, 1, 'Thailand Adventure', 'Hotel Holulu ', 'Pataya Beach', 1, 'Candle Light Dinner in Beach', 'Open air dinner', '0000-00-00', '3 days', 150000.00, 'Accommodation, Breakfast, City Tour', 'Airfare, Personal Expenses', 'package-1.jpg', 1, NULL),
(2, 2, 'Bali Getaway', '', NULL, 0, '', NULL, NULL, '3 days', 180000.00, 'Resort Stay, Breakfast, Spa Session', 'Airfare, Dinner', 'package-2.jpg', 1, NULL),
(3, 3, 'Malaysian Explorer', '', '', 1, '', '', '0000-00-00', '3 days', 130000.00, 'Hotel Stay, Breakfast, Guided Tours', 'Airfare, Transportation', 'package-6893a3524de22.jpg', 1, NULL),
(7, 5, 'Goa', '', '', 1, '', '', '0000-00-00', '2 days', 50000.00, 'Bus Ticket, Breaktfast', '', 'package-6893a4437e787.jpg', 1, NULL),
(8, 5, 'Siliguri', '', '', 1, '', '', '0000-00-00', '3 days', 40000.00, 'Bus ticket, Breakfast', '', 'package-6893a44ddd55c.jpg', 1, NULL),
(10, 5, 'Agra Tajmahal', '', '', 1, '', '', '0000-00-00', '2 days', 64000.00, 'Breaktfast, Lunch, Dinner\n', 'Out Visit', 'package-6893a31948d0e.jpg', 1, NULL),
(12, 5, 'Darjiling', 'Hotel Darjeeling', 'Darjeeling', 1, 'Candle Light Dinner ', 'with 30+ items', '0000-00-00', '3 days', 125000.00, 'Breakfast, Lunch, Dinner', 'Out Visit', 'package-68939fb5b3879.jpg', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `package_events`
--

CREATE TABLE `package_events` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `event_description` text DEFAULT NULL,
  `event_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_hotels`
--

CREATE TABLE `package_hotels` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `hotel_name` varchar(100) NOT NULL,
  `hotel_location` varchar(100) DEFAULT NULL,
  `nights` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `support_content`
--

CREATE TABLE `support_content` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` enum('faq','contact','policy','terms') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_content`
--

INSERT INTO `support_content` (`id`, `title`, `content`, `category`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'How to book a package?', 'To book a package, first create an account, then browse our packages, select your desired package, choose your dates, and proceed to payment. You can pay the full amount or just a deposit to secure your booking.', 'faq', 1, '2025-08-19 16:01:19', '2025-08-19 16:01:19'),
(2, 'What payment methods do you accept?', 'We currently accept bKash, credit cards, and bank transfers. All payments are secure and encrypted.', 'faq', 1, '2025-08-19 16:01:19', '2025-08-19 16:01:19'),
(3, 'Cancellation Policy', 'You can cancel your booking up to 7 days before the travel date for a full refund. Cancellations between 3-7 days will receive a 50% refund. No refunds for cancellations within 48 hours of travel.', 'policy', 1, '2025-08-19 16:01:19', '2025-08-19 16:01:19'),
(4, 'Contact Information', 'Email: support@touristagency.com\nPhone: +880 1234 567890\nAddress: 123 Travel Street, Tourism Area, Dhaka, Bangladesh', 'contact', 1, '2025-08-19 16:01:19', '2025-08-19 16:01:19'),
(5, 'Terms of Service', 'By using our services, you agree to our terms and conditions. We reserve the right to modify itineraries due to weather conditions or other unforeseen circumstances for the safety and comfort of our guests.', 'terms', 1, '2025-08-19 16:01:19', '2025-08-19 16:01:19');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `rating` int(1) NOT NULL CHECK (`rating` between 1 and 5),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `user_id`, `content`, `rating`, `created_at`) VALUES
(1, 4, 'ok', 5, '2025-08-18 14:01:53'),
(2, 4, 'no\n', 5, '2025-08-18 14:04:46'),
(3, 4, 'ok\n', 5, '2025-08-18 14:14:28'),
(4, 4, 'ok', 3, '2025-08-18 14:17:22'),
(5, 4, 'none', 5, '2025-08-18 14:18:00'),
(6, 3, 'kooo', 5, '2025-08-18 14:30:02');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `phone` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '$2y$10$/Jf/wrMEodGtV3vWAcGoPu28PMfZTY8mbfG0dMqLUFffSvVxIqI8a', 'admin', NULL, '2025-07-28 14:55:21', '2025-07-28 14:55:21'),
(3, 'shimanto', 'shimanto@gmail.com', '$2y$10$s9g1TaHI6fTLreEv6ZlQLeYFxSxN.tS33iIL8pA9lNw5.XBWZZC6u', 'customer', NULL, '2025-07-29 14:56:33', '2025-07-29 14:56:33'),
(4, 'Moynul Islam', 'moynulislamshimanto24@gmail.com', '$2y$10$9X9j7Xw2oBkMDamasSxFEeEobtE8Tf66tKZWs1l9T3RQvNvI/uk2a', 'customer', '01949854504', '2025-07-29 15:15:57', '2025-08-11 18:52:01'),
(5, 'Sagor', 'moynulislamshimanto25@gmail.com', '$2y$10$uh8u8W5.lZJteUMZrx9W5eMSKVKfKLJo812.b8/qC8fNThCaGnTeG', 'customer', NULL, '2025-08-09 06:42:43', '2025-08-09 06:42:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `about`
--
ALTER TABLE `about`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `package_events`
--
ALTER TABLE `package_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `package_hotels`
--
ALTER TABLE `package_hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`);

--
-- Indexes for table `support_content`
--
ALTER TABLE `support_content`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `about`
--
ALTER TABLE `about`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `package_events`
--
ALTER TABLE `package_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `package_hotels`
--
ALTER TABLE `package_hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `support_content`
--
ALTER TABLE `support_content`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_sessions`
--
ALTER TABLE `admin_sessions`
  ADD CONSTRAINT `admin_sessions_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`);

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_events`
--
ALTER TABLE `package_events`
  ADD CONSTRAINT `package_events_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_hotels`
--
ALTER TABLE `package_hotels`
  ADD CONSTRAINT `package_hotels_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
