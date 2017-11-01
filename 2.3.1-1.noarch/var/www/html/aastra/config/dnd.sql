-- phpMyAdmin SQL Dump
-- version 4.0.10.20
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 26, 2017 at 08:30 AM
-- Server version: 5.1.73
-- PHP Version: 5.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dnds`
--
CREATE DATABASE IF NOT EXISTS `dnds` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `dnds`;

-- --------------------------------------------------------

--
-- Table structure for table `dnds`
--

DROP TABLE IF EXISTS `dnds`;
CREATE TABLE IF NOT EXISTS `dnds` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user` int(11) NOT NULL,
  `StartDateTime` datetime NOT NULL,
  `EndDateTime` datetime DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Triggers `dnds`
--
DROP TRIGGER IF EXISTS `calculate_duration`;
DELIMITER //
CREATE TRIGGER `calculate_duration` BEFORE UPDATE ON `dnds`
 FOR EACH ROW SET NEW.duration = TIMESTAMPDIFF(SECOND, OLD.StartDateTime, NEW.EndDateTime)
//
DELIMITER ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
