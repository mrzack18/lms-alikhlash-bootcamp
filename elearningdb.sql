-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 01, 2025 at 09:46 AM
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
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `Kelas_ID` int NOT NULL,
  `Nama_Kelas` varchar(100) NOT NULL,
  `Deskripsi_Kelas` text,
  `Tgl_Mulai` date DEFAULT NULL,
  `Tgl_Akhir` date DEFAULT NULL,
  `Status_Kelas` enum('Aktif','Nonaktif') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`Kelas_ID`, `Nama_Kelas`, `Deskripsi_Kelas`, `Tgl_Mulai`, `Tgl_Akhir`, `Status_Kelas`) VALUES
(1, 'Kelas Web Dasar', 'Belajar dasar HTML, CSS, dan JS', '2025-07-01', '2025-08-01', 'Aktif'),
(2, 'Data Engineer', 'Pelatihan untuk menguasai visualisasi data, ETL, dan alat data engineering modern.', '2025-07-01', '2025-09-30', 'Aktif');

-- --------------------------------------------------------

--
-- Table structure for table `mentor`
--

CREATE TABLE `mentor` (
  `Mentor_ID` int NOT NULL,
  `User_ID` int NOT NULL,
  `Keahlian` varchar(100) DEFAULT NULL,
  `LinkedIn` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `mentor`
--

INSERT INTO `mentor` (`Mentor_ID`, `User_ID`, `Keahlian`, `LinkedIn`) VALUES
(1, 2, 'Kelas Web Dasar', 'https://linkedin.com/in/mentor1');

-- --------------------------------------------------------

--
-- Table structure for table `modul`
--

CREATE TABLE `modul` (
  `Modul_ID` int NOT NULL,
  `Kelas_ID` int NOT NULL,
  `Nama_Modul` varchar(100) NOT NULL,
  `Deskripsi_Modul` text,
  `Tgl_Dikirim` date DEFAULT NULL,
  `Url_Modul` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `modul`
--

INSERT INTO `modul` (`Modul_ID`, `Kelas_ID`, `Nama_Modul`, `Deskripsi_Modul`, `Tgl_Dikirim`, `Url_Modul`) VALUES
(3, 1, 'Pemrograman Dasar Python', 'Kelas Pemrograman Dasar Python merupakan program pembelajaran yang dirancang untuk pemula yang ingin memulai perjalanan mereka dalam dunia pemrograman komputer. Bahasa Python dipilih karena sintaksnya yang sederhana dan mudah dipahami, menjadikannya sangat cocok untuk peserta tanpa latar belakang teknis sekalipun. Dalam kelas ini, peserta akan mempelajari konsep dasar pemrograman seperti variabel, tipe data, operator, kontrol alur (percabangan dan perulangan), fungsi, serta struktur data dasar seperti list dan dictionary. Selain itu, peserta juga akan diperkenalkan pada cara membaca dan menulis file, serta memahami dasar penanganan error (error handling). Melalui kombinasi penjelasan materi, latihan praktik, dan proyek akhir sederhana, kelas ini bertujuan membekali peserta dengan kemampuan untuk menulis program Python secara mandiri dan memecahkan masalah melalui pendekatan logis dan terstruktur.', '2025-06-30', '1751222413_A_1010933404324.pdf');

-- --------------------------------------------------------

--
-- Table structure for table `pengumpulan_tugas`
--

CREATE TABLE `pengumpulan_tugas` (
  `Pengumpulan_ID` int NOT NULL,
  `Tugas_ID` int NOT NULL,
  `User_ID` int NOT NULL,
  `Waktu_Kumpul` datetime DEFAULT NULL,
  `File_Jawaban` varchar(255) DEFAULT NULL,
  `Link_Jawaban` varchar(255) DEFAULT NULL,
  `Catatan_Mentor` text,
  `Nilai` decimal(5,2) DEFAULT NULL,
  `Status_ID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peserta`
--

CREATE TABLE `peserta` (
  `Peserta_ID` int NOT NULL,
  `User_ID` int NOT NULL,
  `Alamat` varchar(255) DEFAULT NULL,
  `No_Hp` varchar(20) DEFAULT NULL,
  `Asal_Sekolah` varchar(100) DEFAULT NULL,
  `Status_Lulus` enum('Lulus','Belum Lulus') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `peserta`
--

INSERT INTO `peserta` (`Peserta_ID`, `User_ID`, `Alamat`, `No_Hp`, `Asal_Sekolah`, `Status_Lulus`) VALUES
(1, 3, 'Kp. Kiaralawang', '-', 'SMP 2 Bayongbong', 'Lulus');

-- --------------------------------------------------------

--
-- Table structure for table `sertifikat`
--

CREATE TABLE `sertifikat` (
  `Sertifikat_ID` int NOT NULL,
  `User_ID` int NOT NULL,
  `Kelas_ID` int NOT NULL,
  `Tgl_Daftar_Sertifikat` date DEFAULT NULL,
  `Nilai_Akhir` decimal(5,2) DEFAULT NULL,
  `Status_ID` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sertifikat`
--

INSERT INTO `sertifikat` (`Sertifikat_ID`, `User_ID`, `Kelas_ID`, `Tgl_Daftar_Sertifikat`, `Nilai_Akhir`, `Status_ID`) VALUES
(1, 3, 1, '2025-08-01', '87.50', 2);

-- --------------------------------------------------------

--
-- Table structure for table `status_sertifikat`
--

CREATE TABLE `status_sertifikat` (
  `Status_ID` int NOT NULL,
  `Status_Sertifikat` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `status_sertifikat`
--

INSERT INTO `status_sertifikat` (`Status_ID`, `Status_Sertifikat`) VALUES
(1, 'Dalam Proses'),
(2, 'Sudah Dicetak');

-- --------------------------------------------------------

--
-- Table structure for table `status_tugas`
--

CREATE TABLE `status_tugas` (
  `Status_ID` int NOT NULL,
  `Status_Tugas` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `status_tugas`
--

INSERT INTO `status_tugas` (`Status_ID`, `Status_Tugas`) VALUES
(1, 'Belum Dinilai'),
(2, 'Sudah Dinilai');

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `Tugas_ID` int NOT NULL,
  `Modul_ID` int NOT NULL,
  `Judul_Tugas` varchar(100) NOT NULL,
  `Deskripsi_Tugas` text,
  `File_Lampiran` varchar(255) DEFAULT NULL,
  `Link_Lampiran` varchar(255) DEFAULT NULL,
  `Tgl_Dibuat` datetime DEFAULT NULL,
  `Batas_Kumpul` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
(2, 'Ahmad Nur Ahsan', 'ahsan@gmail.com', '$2y$10$K1QtWGR5l5YuMWMcXjT8O.s8g0FH5s1dmHG5xkMHWMcaX.QDvpWhm', '2025-06-29', NULL, 'mentor', NULL),
(3, 'Rafi Ardiansyah', 'rafi@gmail.com', '$2y$10$K1QtWGR5l5YuMWMcXjT8O.s8g0FH5s1dmHG5xkMHWMcaX.QDvpWhm', '2025-06-29', '6863518e312ba_8931a6fee650c37e.jpg', 'peserta', 1),
(9, 'Nirman', 'nirman@gmail.com', '$2y$10$SJSE57qCnTs16iHDXPAIlu3AP1cjxcn55umDKX.enE0a.qv/pEMni', '2025-07-01', NULL, 'peserta', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`Kelas_ID`);

--
-- Indexes for table `mentor`
--
ALTER TABLE `mentor`
  ADD PRIMARY KEY (`Mentor_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `modul`
--
ALTER TABLE `modul`
  ADD PRIMARY KEY (`Modul_ID`),
  ADD KEY `Kelas_ID` (`Kelas_ID`);

--
-- Indexes for table `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  ADD PRIMARY KEY (`Pengumpulan_ID`),
  ADD KEY `Tugas_ID` (`Tugas_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Status_ID` (`Status_ID`);

--
-- Indexes for table `peserta`
--
ALTER TABLE `peserta`
  ADD PRIMARY KEY (`Peserta_ID`),
  ADD KEY `User_ID` (`User_ID`);

--
-- Indexes for table `sertifikat`
--
ALTER TABLE `sertifikat`
  ADD PRIMARY KEY (`Sertifikat_ID`),
  ADD KEY `User_ID` (`User_ID`),
  ADD KEY `Kelas_ID` (`Kelas_ID`),
  ADD KEY `Status_ID` (`Status_ID`);

--
-- Indexes for table `status_sertifikat`
--
ALTER TABLE `status_sertifikat`
  ADD PRIMARY KEY (`Status_ID`);

--
-- Indexes for table `status_tugas`
--
ALTER TABLE `status_tugas`
  ADD PRIMARY KEY (`Status_ID`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`Tugas_ID`),
  ADD KEY `Modul_ID` (`Modul_ID`);

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
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `Kelas_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `mentor`
--
ALTER TABLE `mentor`
  MODIFY `Mentor_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `modul`
--
ALTER TABLE `modul`
  MODIFY `Modul_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  MODIFY `Pengumpulan_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `peserta`
--
ALTER TABLE `peserta`
  MODIFY `Peserta_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sertifikat`
--
ALTER TABLE `sertifikat`
  MODIFY `Sertifikat_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `status_sertifikat`
--
ALTER TABLE `status_sertifikat`
  MODIFY `Status_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `status_tugas`
--
ALTER TABLE `status_tugas`
  MODIFY `Status_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `Tugas_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `User_ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `mentor`
--
ALTER TABLE `mentor`
  ADD CONSTRAINT `mentor_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `modul`
--
ALTER TABLE `modul`
  ADD CONSTRAINT `modul_ibfk_1` FOREIGN KEY (`Kelas_ID`) REFERENCES `kelas` (`Kelas_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pengumpulan_tugas`
--
ALTER TABLE `pengumpulan_tugas`
  ADD CONSTRAINT `pengumpulan_tugas_ibfk_1` FOREIGN KEY (`Tugas_ID`) REFERENCES `tugas` (`Tugas_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pengumpulan_tugas_ibfk_2` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pengumpulan_tugas_ibfk_3` FOREIGN KEY (`Status_ID`) REFERENCES `status_tugas` (`Status_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `peserta`
--
ALTER TABLE `peserta`
  ADD CONSTRAINT `peserta_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sertifikat`
--
ALTER TABLE `sertifikat`
  ADD CONSTRAINT `sertifikat_ibfk_1` FOREIGN KEY (`User_ID`) REFERENCES `user` (`User_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sertifikat_ibfk_2` FOREIGN KEY (`Kelas_ID`) REFERENCES `kelas` (`Kelas_ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `sertifikat_ibfk_3` FOREIGN KEY (`Status_ID`) REFERENCES `status_sertifikat` (`Status_ID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`Modul_ID`) REFERENCES `modul` (`Modul_ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_kelas` FOREIGN KEY (`Kelas_ID`) REFERENCES `kelas` (`Kelas_ID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
