-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2026 at 09:56 AM
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
-- Database: `transport_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_records`
--

CREATE TABLE `maintenance_records` (
  `id` int(11) NOT NULL,
  `service_date` date NOT NULL,
  `vehicle_no` varchar(50) NOT NULL,
  `odometer_km` decimal(10,2) DEFAULT NULL,
  `work_type` enum('Servicing (Full)','Servicing (Half)','Welding','Main Tank Work','Tyre Work','Balancing Work','Battery Work','Oil Change','Clutch / Brake Work','Electrical Work','Denting / Painting','Puncture Repair','Engine Work','Suspension Work','Other') NOT NULL,
  `work_type_other` varchar(150) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `parts_replaced` varchar(255) DEFAULT NULL,
  `garage_name` varchar(150) DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `bill_number` varchar(50) DEFAULT NULL,
  `parts_cost` decimal(10,2) DEFAULT NULL,
  `labour_cost` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `payment_mode` enum('Cash','UPI','Bank Transfer','Cheque','Credit') DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `next_service_due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_records`
--

INSERT INTO `maintenance_records` (`id`, `service_date`, `vehicle_no`, `odometer_km`, `work_type`, `work_type_other`, `description`, `parts_replaced`, `garage_name`, `location`, `bill_number`, `parts_cost`, `labour_cost`, `total_cost`, `payment_mode`, `status`, `next_service_due_date`, `created_at`, `updated_at`) VALUES
(1, '2026-07-10', 'MH12AB1234', 15480.00, 'Servicing (Full)', NULL, 'Full service including oil, filters, and general checkup', 'Engine oil, oil filter, air filter', 'Shree Auto Garage', 'Pune', 'BILL-4001', 1800.00, 700.00, 2500.00, 'UPI', 'Completed', '2026-10-10', '2026-07-30 11:29:13', '2026-07-30 11:29:13'),
(2, '2026-07-14', 'MH14CD5678', 22190.00, 'Tyre Work', NULL, 'Rear left tyre punctured, replaced with new tyre', 'One rear tyre', 'Highway Tyre Point', 'Nashik', 'BILL-4002', 6200.00, 300.00, 6500.00, 'Cash', 'Completed', NULL, '2026-07-30 11:29:13', '2026-07-30 11:29:13'),
(3, '2026-07-18', 'MH04EF9012', 8900.00, 'Other', 'AC Compressor Repair', 'Cabin AC not cooling, compressor repaired', 'AC compressor seal kit', 'Cool Care Auto', 'Mumbai', NULL, 2200.00, 500.00, 2700.00, 'Bank Transfer', 'In Progress', NULL, '2026-07-30 11:29:13', '2026-07-30 11:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `transport_records`
--

CREATE TABLE `transport_records` (
  `id` int(11) NOT NULL,
  `vehicle_no` varchar(50) NOT NULL,
  `driver_name` varchar(100) NOT NULL,
  `driver_contact` varchar(20) NOT NULL,
  `source` varchar(150) NOT NULL,
  `destination` varchar(150) NOT NULL,
  `departure_date` date NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `status` enum('Scheduled','In Transit','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `remarks` varchar(255) DEFAULT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `lr_number` varchar(50) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `gst_number` varchar(20) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `rate` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_records`
--

INSERT INTO `transport_records` (`id`, `vehicle_no`, `driver_name`, `driver_contact`, `source`, `destination`, `departure_date`, `departure_time`, `arrival_time`, `status`, `remarks`, `supplier`, `lr_number`, `invoice_number`, `gst_number`, `quantity`, `rate`, `created_at`, `updated_at`) VALUES
(1, 'MH12AB1234', 'Ramesh Patil', '9876543210', 'Pune', 'Mumbai', '2026-07-03', '06:00:00', '09:30:00', 'Completed', 'On time', 'ABC Traders', 'LR-1001', 'INV-2001', '27ABCDE1234F1Z5', 12.50, 4500.00, '2026-07-30 11:20:16', '2026-07-30 11:20:16'),
(2, 'MH14CD5678', 'Suresh Yadav', '9823456789', 'Pune', 'Nashik', '2026-07-03', '08:00:00', NULL, 'In Transit', 'Traffic near Sinnar', 'XYZ Logistics', 'LR-1002', 'INV-2002', '27XYZAB5678G1Z2', 8.00, 3200.00, '2026-07-30 11:20:16', '2026-07-30 11:20:16'),
(3, 'MH04EF9012', 'Anil Kumar', '9765432109', 'Mumbai', 'Pune', '2026-07-04', '14:00:00', NULL, 'Scheduled', NULL, 'ABC Traders', 'LR-1003', 'INV-2003', '27ABCDE1234F1Z5', 15.00, 5000.00, '2026-07-30 11:20:16', '2026-07-30 11:20:16'),
(4, 'MH12AB1234', 'Ramesh Patil', '9876543210', 'Pune', 'Mumbai', '2026-07-03', '06:00:00', '09:30:00', 'Completed', 'On time', 'ABC Traders', 'LR-1001', 'INV-2001', '27ABCDE1234F1Z5', 12.50, 4500.00, '2026-07-30 11:28:25', '2026-07-30 11:28:25'),
(5, 'MH14CD5678', 'Suresh Yadav', '9823456789', 'Pune', 'Nashik', '2026-07-03', '08:00:00', NULL, 'In Transit', 'Traffic near Sinnar', 'XYZ Logistics', 'LR-1002', 'INV-2002', '27XYZAB5678G1Z2', 8.00, 3200.00, '2026-07-30 11:28:25', '2026-07-30 11:28:25'),
(6, 'MH04EF9012', 'Anil Kumar', '9765432109', 'Mumbai', 'Pune', '2026-07-04', '14:00:00', NULL, 'Scheduled', NULL, 'ABC Traders', 'LR-1003', 'INV-2003', '27ABCDE1234F1Z5', 15.00, 5000.00, '2026-07-30 11:28:25', '2026-07-30 11:28:25'),
(7, 'MH12AB1234', 'Ramesh Patil', '9876543210', 'Pune', 'Mumbai', '2026-07-03', '06:00:00', '09:30:00', 'Completed', 'On time', 'ABC Traders', 'LR-1001', 'INV-2001', '27ABCDE1234F1Z5', 12.50, 4500.00, '2026-07-30 11:29:13', '2026-07-30 11:29:13'),
(8, 'MH14CD5678', 'Suresh Yadav', '9823456789', 'Pune', 'Nashik', '2026-07-03', '08:00:00', NULL, 'In Transit', 'Traffic near Sinnar', 'XYZ Logistics', 'LR-1002', 'INV-2002', '27XYZAB5678G1Z2', 8.00, 3200.00, '2026-07-30 11:29:13', '2026-07-30 11:29:13'),
(9, 'MH04EF9012', 'Anil Kumar', '9765432109', 'Mumbai', 'Pune', '2026-07-04', '14:00:00', NULL, 'Scheduled', NULL, 'ABC Traders', 'LR-1003', 'INV-2003', '27ABCDE1234F1Z5', 15.00, 5000.00, '2026-07-30 11:29:13', '2026-07-30 11:29:13');

-- --------------------------------------------------------

--
-- Table structure for table `trip_logs`
--

CREATE TABLE `trip_logs` (
  `id` int(11) NOT NULL,
  `trip_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `lr_number` varchar(50) NOT NULL,
  `vehicle_no` varchar(50) NOT NULL,
  `location` varchar(150) NOT NULL,
  `sakharwadi_diesel` decimal(10,2) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `advance` decimal(10,2) DEFAULT NULL,
  `driver_name` varchar(100) NOT NULL,
  `before_diesel` decimal(10,2) DEFAULT NULL,
  `after_diesel` decimal(10,2) DEFAULT NULL,
  `before_km` decimal(10,2) DEFAULT NULL,
  `after_km` decimal(10,2) DEFAULT NULL,
  `total_km` decimal(10,2) DEFAULT NULL,
  `def_qty` decimal(10,2) NOT NULL,
  `kl_qty` decimal(10,2) NOT NULL,
  `fasttag_exp` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trip_logs`
--

INSERT INTO `trip_logs` (`id`, `trip_date`, `return_date`, `lr_number`, `vehicle_no`, `location`, `sakharwadi_diesel`, `rate`, `amount`, `advance`, `driver_name`, `before_diesel`, `after_diesel`, `before_km`, `after_km`, `total_km`, `def_qty`, `kl_qty`, `fasttag_exp`, `created_at`, `updated_at`) VALUES
(1, '2026-07-05', '2026-07-06', 'LR-3001', 'MH12AB1234', 'Sakharwadi', 120.00, 92.50, 11100.00, 2000.00, 'Ramesh Patil', 40.00, 20.00, 15230.00, 15480.00, 250.00, 8.00, 18.50, 150.00, '2026-07-30 11:20:16', '2026-07-30 11:20:16'),
(2, '2026-07-06', NULL, 'LR-3002', 'MH14CD5678', 'Sakharwadi', 95.00, 92.50, 8787.50, 1500.00, 'Suresh Yadav', 35.00, 15.00, 22010.00, 22190.00, 180.00, 6.50, 14.00, 100.00, '2026-07-30 11:20:16', '2026-07-30 11:20:16'),
(3, '2026-07-05', '2026-07-06', 'LR-3001', 'MH12AB1234', 'Sakharwadi', 120.00, 92.50, 11100.00, 2000.00, 'Ramesh Patil', 40.00, 20.00, 15230.00, 15480.00, 250.00, 8.00, 18.50, 150.00, '2026-07-30 11:28:25', '2026-07-30 11:28:25'),
(4, '2026-07-06', NULL, 'LR-3002', 'MH14CD5678', 'Sakharwadi', 95.00, 92.50, 8787.50, 1500.00, 'Suresh Yadav', 35.00, 15.00, 22010.00, 22190.00, 180.00, 6.50, 14.00, 100.00, '2026-07-30 11:28:25', '2026-07-30 11:28:25'),
(5, '2026-07-05', '2026-07-06', 'LR-3001', 'MH12AB1234', 'Sakharwadi', 120.00, 92.50, 11100.00, 2000.00, 'Ramesh Patil', 40.00, 20.00, 15230.00, 15480.00, 250.00, 8.00, 18.50, 150.00, '2026-07-30 11:29:13', '2026-07-30 11:29:13'),
(6, '2026-07-06', NULL, 'LR-3002', 'MH14CD5678', 'Sakharwadi', 95.00, 92.50, 8787.50, 1500.00, 'Suresh Yadav', 35.00, 15.00, 22010.00, 22190.00, 180.00, 6.50, 14.00, 100.00, '2026-07-30 11:29:13', '2026-07-30 11:29:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transport_records`
--
ALTER TABLE `transport_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trip_logs`
--
ALTER TABLE `trip_logs`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `maintenance_records`
--
ALTER TABLE `maintenance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `transport_records`
--
ALTER TABLE `transport_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `trip_logs`
--
ALTER TABLE `trip_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
