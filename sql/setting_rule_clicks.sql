-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: afragola-db.id.domainesia.com:3306
-- Generation Time: Aug 08, 2026 at 06:58 PM
-- Server version: 8.0.46-0ubuntu0.24.04.3
-- PHP Version: 8.4.24

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kumpulbl_kbc`
--

-- --------------------------------------------------------

--
-- Table structure for table `setting_rule_clicks`
--

CREATE TABLE `setting_rule_clicks` (
  `id` int NOT NULL,
  `rule_name` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `threshold` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `setting_rule_clicks`
--

INSERT INTO `setting_rule_clicks` (`id`, `rule_name`, `threshold`, `description`) VALUES
(1, 'aa', 2, 'Max clicks by same IP and user cookie in 1 minute / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 1 menit'),
(2, 'ab', 2, 'Max clicks by same IP and browser in 2 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 2 menit'),
(3, 'ac', 3, 'Max clicks by same IP and browser in 5 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 5 menit'),
(4, 'ad', 3, 'Max clicks by same IP and user cookie in 10 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 10 menit'),
(5, 'ae', 4, 'Max clicks by same IP and browser in 15 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 15 menit'),
(6, 'af', 4, 'Max clicks by same IP and browser in 20 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 20 menit'),
(7, 'ag', 4, 'Max clicks by same IP and user cookie in 25 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 25 menit'),
(8, 'ah', 5, 'Max clicks by same IP and browser in 30 minutes / Jumlah klik maksimum oleh IP dan browser yang sama dalam 30 menit'),
(9, 'ai', 5, 'Max clicks by same IP and user cookie in 35 minutes / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 35 menit'),
(10, 'aj', 1, 'Max clicks by same IP and user cookie in 20 seconds / Jumlah klik maksimum oleh IP dan cookie pengguna yang sama dalam 20 detik'),
(11, 'ak', 5, 'Max clicks by same IP and browser in 1 hour / Jumlah klik maksimum oleh IP dan browser yang sama dalam 1 jam'),
(12, 'al', 6, 'Max clicks by same IP and browser in 2 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 2 jam'),
(13, 'am', 6, 'Max clicks by same IP and browser in 4 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 4 jam'),
(14, 'an', 5, 'Max clicks by same IP and browser in 6 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 6 jam'),
(15, 'ao', 2, 'Max clicks by same IP and browser in 12 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 12 jam'),
(16, 'ap', 5, 'Max clicks by same IP and browser in 24 hours / Jumlah klik maksimum oleh IP dan browser yang sama dalam 24 jam');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `setting_rule_clicks`
--
ALTER TABLE `setting_rule_clicks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rule_name` (`rule_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `setting_rule_clicks`
--
ALTER TABLE `setting_rule_clicks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
