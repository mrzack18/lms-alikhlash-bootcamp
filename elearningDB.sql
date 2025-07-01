-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 01, 2025 at 08:44 AM
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
-- Database: `elearningdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `User_ID` int NOT NULL,
  `Nama_User` varchar(100) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Tanggal_Daftar` date DEFAULT NULL,
  `Foto_Profile` varchar(255) DEFAULT NULL,
  `Role` enum('admin','mentor','peserta') DEFAULT 'peserta',
  `Kelas_ID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`User_ID`, `Nama_User`, `Email`, `Password`, `Tanggal_Daftar`, `Foto_Profile`, `Role`, `Kelas_ID`) VALUES
(1, 'Admin 1', 'admin1@example.com', 'admin123', '2025-06-29', NULL, 'admin', NULL),
(2, 'Ahmad Nur Ahsan', 'mentor1@example.com', '$2y$10$K1QtWGR5l5YuMWMcXjT8O.s8g0FH5s1dmHG5xkMHWMcaX.QDvpWhm', '2025-06-29', NULL, 'mentor', NULL),
(3, 'Rafi Ardiansyah', 'peserta1@example.com', '$2y$10$K1QtWGR5l5YuMWMcXjT8O.s8g0FH5s1dmHG5xkMHWMcaX.QDvpWhm', '2025-06-29', '6863518e312ba_8931a6fee650c37e.jpg', 'peserta', 1),
(4, 'Peserta 2', 'peserta2@example.com', 'peserta456', '2025-06-29', NULL, 'peserta', NULL),
(7, 'Ucup Surucup', 'ucup@gmail.com', '$2y$10$vDWqs7hftIjIbELI1me3RuUEQCcsw0mmdPsWJmKUxCBlQTOkth.n2', '2025-06-29', '1751215460_ucup.jpeg', 'peserta', 2),
(8, 'Andi Surandi', 'andi@gmail.com', '$2y$10$m5o0wrWloAjoNmMoIf2SbuazAYwBLOGIQG5Wg3737m2Da6xN/KmoK', '2025-06-29', NULL, 'peserta', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `fk_user_kelas` (`Kelas_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_kelas` FOREIGN KEY (`Kelas_ID`) REFERENCES `kelas` (`Kelas_ID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
