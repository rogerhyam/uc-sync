-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Oct 27, 2016 at 01:39 PM
-- Server version: 5.5.49-0ubuntu0.14.04.1
-- PHP Version: 5.5.9-1ubuntu4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `ucsync`
--
CREATE DATABASE IF NOT EXISTS `ucsync` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `ucsync`;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uc_id` int(11) NOT NULL COMMENT 'The id in the universal calendar',
  `repeat` int(11) NOT NULL COMMENT 'the repeat number of this event 0 is first',
  `venue_id` int(11) NOT NULL COMMENT 'id of venue in uc',
  `garden_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL COMMENT 'local path of image',
  `flags` varchar(1000) NOT NULL COMMENT 'all the flags and tags pipe separated',
  `start_timestamp` int(11) NOT NULL,
  `end_timestamp` int(11) NOT NULL,
  `month` int(4) NOT NULL,
  `day_of_month` int(4) NOT NULL,
  `day_of_week` int(11) NOT NULL,
  `raw` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7323 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
