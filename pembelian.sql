-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 20, 2026 at 11:11 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cirebon`
--

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `id` int(11) NOT NULL,
  `kode_pengajuan` varchar(20) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `harga` double NOT NULL,
  `total` double NOT NULL,
  `keterangan` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'diajukan',
  `tanggal_pengajuan` datetime NOT NULL,
  `tanggal_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pembelian`
--

INSERT INTO `pembelian` (`id`, `kode_pengajuan`, `nama_barang`, `kategori_id`, `jumlah`, `harga`, `total`, `keterangan`, `status`, `tanggal_pengajuan`, `tanggal_update`) VALUES
(1, 'PB-001', 'Laptop Dell XPS 15', 1, 2, 15000000, 30000000, 'Untuk bagian IT', 'disetujui', '2026-01-15 09:30:00', '2026-01-20 11:11:00'),
(2, 'PB-002', 'Printer Epson L3150', 1, 3, 2500000, 7500000, 'Untuk semua departemen', 'diproses', '2026-01-16 10:15:00', '2026-01-20 11:11:00'),
(3, 'PB-003', 'Meja Kerja', 3, 5, 800000, 4000000, 'Untuk ruang baru', 'diajukan', '2026-01-18 14:20:00', '2026-01-20 11:11:00'),
(4, 'PB-004', 'ATK Bulanan', 4, 50, 50000, 2500000, 'Stok bulan Januari', 'selesai', '2026-01-10 08:45:00', '2026-01-20 11:11:00'),
(5, 'PB-005', 'Kursi Ergonomis', 3, 10, 1200000, 12000000, 'Untuk meningkatkan kenyamanan kerja', 'ditolak', '2026-01-19 11:10:00', '2026-01-20 11:11:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pengajuan` (`kode_pengajuan`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;