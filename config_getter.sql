-- phpMyAdmin SQL Dump
-- version 3.3.7deb5build0.10.10.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 23, 2012 at 03:56 PM
-- Server version: 5.1.49
-- PHP Version: 5.3.3-1ubuntu9.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `config_getter`
--

-- --------------------------------------------------------

--
-- Table structure for table `devices`
--

CREATE TABLE IF NOT EXISTS `devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL COMMENT 'display name for the device',
  `ip_address` varchar(15) NOT NULL,
  `community` varchar(200) DEFAULT NULL COMMENT 'per-device SNMP read/write community string (NULL uses default)',
  `tftp_server` varchar(15) DEFAULT NULL COMMENT 'per-device TFTP server IP address (NULL uses default)',
  `last_upload` timestamp NULL DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `enabled` (`enabled`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='device list' AUTO_INCREMENT=4 ;

--
-- Dumping data for table `devices`
--

INSERT INTO `devices` (`id`, `name`, `ip_address`, `community`, `tftp_server`, `last_upload`, `enabled`) VALUES
(1, 'Device 1', '192.168.1.2', NULL, NULL, '2012-01-23 15:54:25', 1),
(2, 'Device 2 (with custom community string)', '192.168.1.3', 'ReadWrte123', NULL, '2012-01-23 15:54:59', 1),
(3, 'Device 3 (with custom TFTP server)', '192.168.1.4', NULL, '192.168.1.254', '2012-01-23 15:55:27', 1);

-- --------------------------------------------------------

--
-- Table structure for table `log`
--

CREATE TABLE IF NOT EXISTS `log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `message` varchar(1024) NOT NULL,
  `detail` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date_added` (`date_added`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `log`
--

