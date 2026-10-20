-- HealthLogs Clean Deployment SQL Dump
CREATE DATABASE IF NOT EXISTS `healthlogs`;
USE `healthlogs`;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 05, 2026 at 03:00 PM
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
-- Database: `healthlogs`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `username` varchar(60) DEFAULT NULL,
  `user_role` varchar(50) DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` varchar(60) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--


-- --------------------------------------------------------

--
-- Table structure for table `analytics_daily`
--

CREATE TABLE `analytics_daily` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `metric_name` varchar(120) NOT NULL,
  `metric_date` date NOT NULL,
  `metric_value` decimal(12,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `arima_parameters`
--

CREATE TABLE `arima_parameters` (
  `id` int(10) UNSIGNED NOT NULL,
  `run_id` int(10) UNSIGNED NOT NULL,
  `model_order` varchar(50) DEFAULT NULL,
  `seasonal_order` varchar(50) DEFAULT NULL,
  `aic` decimal(12,4) DEFAULT NULL,
  `bic` decimal(12,4) DEFAULT NULL,
  `log_likelihood` decimal(12,4) DEFAULT NULL,
  `sigma2` decimal(12,6) DEFAULT NULL,
  `mae` decimal(12,4) DEFAULT NULL,
  `rmse` decimal(12,4) DEFAULT NULL,
  `mape` decimal(12,4) DEFAULT NULL,
  `parameters_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `arima_parameters`
--

INSERT INTO `arima_parameters` (`id`, `run_id`, `model_order`, `seasonal_order`, `aic`, `bic`, `log_likelihood`, `sigma2`, `mae`, `rmse`, `mape`, `parameters_json`) VALUES
(1, 1, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(2, 3, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(3, 7, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(4, 10, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(5, 12, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(6, 14, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(7, 18, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(8, 20, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(9, 22, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(10, 25, '(0, 0, 0)', '(0, 0, 1, 7)', 2260.5539, 2270.1328, -1127.2770, 16104.668528, NULL, NULL, NULL, '{\"intercept\":217.6072093127813,\"ma.S.L7\":0.11232157571316786,\"sigma2\":16104.668527504513}'),
(11, 32, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(12, 36, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, NULL, NULL, NULL, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(13, 48, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(14, 50, NULL, NULL, 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(15, 52, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(16, 69, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(17, 75, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(18, 80, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(19, 84, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(20, 87, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(21, 90, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(22, 126, '(0, 0, 0)', '(0, 0, 1, 7)', 2260.5539, 2270.1328, -1127.2770, 16104.668528, 102.9113, 126.9432, 106.5600, '{\"intercept\":217.6072093127813,\"ma.S.L7\":0.11232157571316786,\"sigma2\":16104.668527504513}'),
(23, 127, '(0, 0, 0)', '(0, 0, 1, 7)', 2260.5539, 2270.1328, -1127.2770, 16104.668528, 102.9113, 126.9432, 106.5600, '{\"intercept\":217.6072093127813,\"ma.S.L7\":0.11232157571316786,\"sigma2\":16104.668527504513}'),
(24, 130, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(25, 131, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(26, 132, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(27, 133, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(28, 134, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(29, 139, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(30, 141, '(0, 0, 0)', '(0, 0, 1, 7)', 2260.5539, 2270.1328, -1127.2770, 16104.668528, 102.9113, 126.9432, 106.5600, '{\"intercept\":217.6072093127813,\"ma.S.L7\":0.11232157571316786,\"sigma2\":16104.668527504513}'),
(31, 155, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(32, 162, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(33, 164, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(34, 166, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(35, 173, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(36, 178, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(37, 180, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(38, 182, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}'),
(39, 189, '(1, 1, 1)', '(0, 0, 1, 7)', 1095.0885, 1107.8380, -543.5442, 25.233881, 3.1954, 5.0094, 58.0400, '{\"ar.L1\":-0.21013000053111833,\"ma.L1\":-0.7423313619188427,\"ma.S.L7\":0.15177689250935214,\"sigma2\":25.23388097547265}');

-- --------------------------------------------------------

--
-- Table structure for table `forecast_results`
--

CREATE TABLE `forecast_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `run_id` int(10) UNSIGNED NOT NULL,
  `forecast_date` date NOT NULL,
  `forecast_value` decimal(12,2) NOT NULL,
  `lower_bound` decimal(12,2) NOT NULL,
  `upper_bound` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forecast_results`
--


-- --------------------------------------------------------

--
-- Table structure for table `forecast_runs`
--

CREATE TABLE `forecast_runs` (
  `id` int(10) UNSIGNED NOT NULL,
  `series_key` varchar(120) NOT NULL,
  `horizon` int(11) NOT NULL,
  `model_type` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL,
  `error_message` text DEFAULT NULL,
  `training_points` int(11) DEFAULT NULL,
  `history_points` int(11) DEFAULT NULL,
  `execution_time_seconds` decimal(6,3) DEFAULT NULL,
  `mae` decimal(12,4) DEFAULT NULL,
  `rmse` decimal(12,4) DEFAULT NULL,
  `mape` decimal(12,4) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `forecast_runs`
--

INSERT INTO `forecast_runs` (`id`, `series_key`, `horizon`, `model_type`, `status`, `error_message`, `training_points`, `history_points`, `execution_time_seconds`, `mae`, `rmse`, `mape`, `created_at`) VALUES
(1, 'visits_total', 3, 'ARIMA', 'success', NULL, 180, 477, 5.744, NULL, NULL, NULL, '2026-06-17 22:10:49'),
(2, 'visits_total', 30, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.006, NULL, NULL, NULL, '2026-06-17 22:14:12'),
(3, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 11.531, NULL, NULL, NULL, '2026-06-17 22:14:16'),
(4, 'visits_total', 30, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.006, NULL, NULL, NULL, '2026-06-17 22:14:28'),
(5, 'visits_total', 30, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.015, NULL, NULL, NULL, '2026-06-17 22:37:41'),
(6, 'visits_total', 20, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.004, NULL, NULL, NULL, '2026-06-17 22:37:47'),
(7, 'visits_total', 20, 'ARIMA', 'success', NULL, 180, 477, 10.681, NULL, NULL, NULL, '2026-06-17 22:37:49'),
(8, 'visits_total', 20, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.015, NULL, NULL, NULL, '2026-06-17 22:38:00'),
(9, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 89, 0.019, NULL, NULL, NULL, '2026-06-17 22:42:26'),
(10, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 9.846, NULL, NULL, NULL, '2026-06-17 22:42:28'),
(11, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 89, 0.019, NULL, NULL, NULL, '2026-06-17 22:42:38'),
(12, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 9.674, NULL, NULL, NULL, '2026-06-17 22:43:00'),
(13, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 89, 0.017, NULL, NULL, NULL, '2026-06-17 22:43:11'),
(14, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 18.282, NULL, NULL, NULL, '2026-06-17 22:53:37'),
(15, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 89, 0.017, NULL, NULL, NULL, '2026-06-17 22:53:56'),
(16, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 89, 0.005, NULL, NULL, NULL, '2026-06-17 23:00:30'),
(17, 'visits_total', 10, 'Fast Forecast', 'success', NULL, 56, 89, 0.028, NULL, NULL, NULL, '2026-06-17 23:00:34'),
(18, 'visits_total', 10, 'ARIMA', 'success', NULL, 180, 477, 10.498, NULL, NULL, NULL, '2026-06-17 23:00:35'),
(19, 'visits_total', 10, 'Fast Forecast', 'success', NULL, 56, 89, 0.006, NULL, NULL, NULL, '2026-06-17 23:00:45'),
(20, 'visits_total', 10, 'ARIMA', 'success', NULL, 180, 477, 8.393, NULL, NULL, NULL, '2026-06-17 23:07:20'),
(21, 'visits_total', 10, 'Fast Forecast', 'success', NULL, 56, 89, 0.019, NULL, NULL, NULL, '2026-06-17 23:07:29'),
(22, 'visits_total', 10, 'ARIMA', 'success', NULL, 180, 477, 9.624, NULL, NULL, NULL, '2026-06-17 23:10:19'),
(23, 'visits_total', 10, 'Fast Forecast', 'success', NULL, 56, 89, 0.010, NULL, NULL, NULL, '2026-06-17 23:10:29'),
(24, 'medicine_total', 10, 'Fast Forecast', 'success', NULL, 56, 90, 0.027, NULL, NULL, NULL, '2026-06-17 23:10:57'),
(25, 'medicine_total', 10, 'ARIMA', 'success', NULL, 180, 361, 6.984, NULL, NULL, NULL, '2026-06-17 23:11:07'),
(26, 'medicine_total', 10, 'Fast Forecast', 'success', NULL, 56, 90, 0.008, NULL, NULL, NULL, '2026-06-17 23:11:14'),
(27, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 84, 0.067, NULL, NULL, NULL, '2026-06-22 10:05:51'),
(28, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 84, 0.010, NULL, NULL, NULL, '2026-06-22 10:06:01'),
(29, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 84, 0.012, NULL, NULL, NULL, '2026-06-22 10:06:03'),
(30, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 84, 0.007, NULL, NULL, NULL, '2026-06-22 10:06:10'),
(31, 'visits_total', 1, 'Fast Forecast', 'success', NULL, 56, 84, 0.014, NULL, NULL, NULL, '2026-06-22 10:06:32'),
(32, 'visits_total', 1, 'ARIMA', 'success', NULL, 180, 477, 27.180, NULL, NULL, NULL, '2026-06-22 10:06:33'),
(33, 'visits_total', 1, 'Fast Forecast', 'success', NULL, 56, 84, 0.010, NULL, NULL, NULL, '2026-06-22 10:07:00'),
(34, 'visits_total', 1, 'Fast Forecast', 'success', NULL, 56, 84, 0.013, NULL, NULL, NULL, '2026-06-22 10:07:39'),
(35, 'visits_total', 2, 'Fast Forecast', 'success', NULL, 56, 84, 0.008, NULL, NULL, NULL, '2026-06-22 10:07:42'),
(36, 'visits_total', 2, 'ARIMA', 'success', NULL, 180, 477, 24.726, NULL, NULL, NULL, '2026-06-22 10:07:42'),
(37, 'visits_total', 2, 'Fast Forecast', 'success', NULL, 56, 84, 0.010, NULL, NULL, NULL, '2026-06-22 10:08:08'),
(38, 'visits_total', 14, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.025, NULL, NULL, NULL, '2026-09-03 16:28:50'),
(39, 'visits_total', 14, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.009, NULL, NULL, NULL, '2026-09-03 16:43:45'),
(40, 'visits_total', 14, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.023, NULL, NULL, NULL, '2026-09-03 16:46:53'),
(41, 'visits_total', 14, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.021, NULL, NULL, NULL, '2026-09-03 16:52:54'),
(42, 'visits_total', 14, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.023, NULL, NULL, NULL, '2026-09-03 18:21:17'),
(43, 'visits_total', 14, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.074, NULL, NULL, NULL, '2026-09-04 18:58:55'),
(44, 'visits_total', 30, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.005, NULL, NULL, NULL, '2026-09-04 19:03:51'),
(45, 'visits_total', 30, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.006, NULL, NULL, NULL, '2026-09-04 19:06:39'),
(46, 'visits_total', 30, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.016, NULL, NULL, NULL, '2026-09-04 19:07:19'),
(47, 'visits_total', 10, 'Fast Forecast', 'failed', 'Not enough history yet. At least 14 days of data are required.', NULL, NULL, 0.007, NULL, NULL, NULL, '2026-09-04 19:07:27'),
(48, 'visits_total', 10, 'ARIMA', 'success', NULL, 180, 477, 10.363, 3.1954, 5.0094, 58.0400, '2026-09-04 19:07:28'),
(49, 'visits_total', 10, 'Fast Forecast', 'success', NULL, 56, 165, 0.030, 4.8393, 5.5326, 44.6200, '2026-09-04 19:07:38'),
(50, 'visits_total', 5, 'ARIMA', 'success', NULL, 180, 477, 10.184, 3.1954, 5.0094, 58.0400, '2026-09-04 19:07:59'),
(51, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.032, 4.8393, 5.5326, 44.6200, '2026-09-04 19:08:09'),
(52, 'visits_total', 14, 'ARIMA', 'success', NULL, 180, 477, 8.723, 3.1954, 5.0094, 58.0400, '2026-09-04 19:08:10'),
(53, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.016, 4.8393, 5.5326, 44.6200, '2026-09-04 19:08:19'),
(54, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-04 19:10:21'),
(55, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.019, 4.8393, 5.5326, 44.6200, '2026-09-04 19:12:34'),
(56, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-04 19:14:57'),
(57, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-04 19:17:51'),
(58, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-04 19:18:05'),
(59, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.012, 4.8393, 5.5326, 44.6200, '2026-09-04 19:18:15'),
(60, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-04 19:19:43'),
(61, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.007, 4.8393, 5.5326, 44.6200, '2026-09-04 19:28:30'),
(62, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-04 19:28:39'),
(63, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.023, 4.8393, 5.5326, 44.6200, '2026-09-04 19:31:56'),
(64, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.018, 4.8393, 5.5326, 44.6200, '2026-09-04 19:32:43'),
(65, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.016, 4.8393, 5.5326, 44.6200, '2026-09-07 10:45:40'),
(66, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.029, 4.8393, 5.5326, 44.6200, '2026-09-07 10:48:51'),
(67, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-07 10:53:20'),
(68, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.053, 4.8393, 5.5326, 44.6200, '2026-09-07 12:36:06'),
(69, 'visits_total', 15, 'ARIMA', 'success', NULL, 180, 477, 30.021, 3.1954, 5.0094, 58.0400, '2026-09-07 12:36:15'),
(70, 'visits_total', 15, 'Fast Forecast', 'success', NULL, 56, 165, 0.017, 4.8393, 5.5326, 44.6200, '2026-09-07 12:36:45'),
(71, 'visits_total', 15, 'Fast Forecast', 'success', NULL, 56, 165, 0.018, 4.8393, 5.5326, 44.6200, '2026-09-07 12:36:46'),
(72, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.012, 4.8393, 5.5326, 44.6200, '2026-09-07 13:10:02'),
(73, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-07 13:37:27'),
(74, 'visits_total', 15, 'Fast Forecast', 'success', NULL, 56, 165, 0.028, 4.8393, 5.5326, 44.6200, '2026-09-07 13:37:31'),
(75, 'visits_total', 15, 'ARIMA', 'success', NULL, 180, 477, 18.883, 3.1954, 5.0094, 58.0400, '2026-09-07 13:37:32'),
(76, 'visits_total', 15, 'Fast Forecast', 'success', NULL, 56, 165, 0.014, 4.8393, 5.5326, 44.6200, '2026-09-07 13:37:52'),
(77, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.007, 4.8393, 5.5326, 44.6200, '2026-09-07 13:39:37'),
(78, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.010, 4.8393, 5.5326, 44.6200, '2026-09-07 14:41:43'),
(79, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-07 14:41:48'),
(80, 'visits_total', 14, 'ARIMA', 'success', NULL, 180, 477, 13.928, 3.1954, 5.0094, 58.0400, '2026-09-07 14:41:48'),
(81, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-07 14:42:03'),
(82, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-07 15:04:55'),
(83, 'visits_total', 60, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-07 15:05:03'),
(84, 'visits_total', 60, 'ARIMA', 'success', NULL, 180, 477, 26.327, 3.1954, 5.0094, 58.0400, '2026-09-07 15:05:03'),
(85, 'visits_total', 60, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-07 15:05:31'),
(86, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.007, 4.8393, 5.5326, 44.6200, '2026-09-07 15:06:02'),
(87, 'visits_total', 14, 'ARIMA', 'success', NULL, 180, 477, 12.752, 3.1954, 5.0094, 58.0400, '2026-09-07 15:06:02'),
(88, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.011, 4.8393, 5.5326, 44.6200, '2026-09-07 15:06:15'),
(89, 'visits_total', 7, 'Fast Forecast', 'success', NULL, 56, 165, 0.016, 4.8393, 5.5326, 44.6200, '2026-09-07 15:10:50'),
(90, 'visits_total', 7, 'ARIMA', 'success', NULL, 180, 477, 13.537, 3.1954, 5.0094, 58.0400, '2026-09-07 15:10:50'),
(91, 'visits_total', 7, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-07 15:11:04'),
(92, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.045, 4.8393, 5.5326, 44.6200, '2026-09-09 18:57:42'),
(93, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-09 19:22:20'),
(94, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.014, 4.8393, 5.5326, 44.6200, '2026-09-09 19:23:02'),
(95, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.011, 4.8393, 5.5326, 44.6200, '2026-09-09 19:34:40'),
(96, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.021, 4.8393, 5.5326, 44.6200, '2026-09-09 19:38:02'),
(97, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-09 19:46:24'),
(98, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-09 19:46:29'),
(99, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.036, 4.8393, 5.5326, 44.6200, '2026-09-09 19:46:33'),
(100, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.005, 4.8393, 5.5326, 44.6200, '2026-09-09 19:52:17'),
(101, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.012, 4.8393, 5.5326, 44.6200, '2026-09-09 19:55:29'),
(102, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-09 20:04:48'),
(103, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.028, 4.8393, 5.5326, 44.6200, '2026-09-09 20:04:58'),
(104, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-09 20:07:25'),
(105, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.017, 4.8393, 5.5326, 44.6200, '2026-09-09 20:10:33'),
(106, 'medicine_total', 30, 'Fast Forecast', 'success', NULL, 56, 181, 0.019, 96.9777, 128.1933, 95.1000, '2026-09-09 20:10:37'),
(107, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.007, 4.8393, 5.5326, 44.6200, '2026-09-09 20:12:15'),
(108, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.020, 4.8393, 5.5326, 44.6200, '2026-09-09 20:18:35'),
(109, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.011, 4.8393, 5.5326, 44.6200, '2026-09-09 20:18:53'),
(110, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.027, 4.8393, 5.5326, 44.6200, '2026-09-09 20:23:00'),
(111, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.014, 4.8393, 5.5326, 44.6200, '2026-09-09 20:23:29'),
(112, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.012, 4.8393, 5.5326, 44.6200, '2026-09-09 20:30:03'),
(113, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.029, 4.8393, 5.5326, 44.6200, '2026-09-09 20:41:04'),
(114, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.007, 4.8393, 5.5326, 44.6200, '2026-09-09 20:47:02'),
(115, 'medicine_total', 30, 'Fast Forecast', 'success', NULL, 56, 181, 0.017, 96.9777, 128.1933, 95.1000, '2026-09-09 20:48:22'),
(116, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-09 21:01:35'),
(117, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-09 21:11:30'),
(118, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.050, 4.8393, 5.5326, 44.6200, '2026-09-10 15:17:47'),
(119, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-10 15:43:25'),
(120, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-10 15:43:29'),
(121, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-10 15:43:32'),
(122, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.010, 4.8393, 5.5326, 44.6200, '2026-09-10 15:43:41'),
(123, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.026, 4.8393, 5.5326, 44.6200, '2026-09-10 15:44:15'),
(124, 'visits_total', 10, 'Fast Forecast', 'success', NULL, 56, 165, 0.028, 4.8393, 5.5326, 44.6200, '2026-09-10 15:44:37'),
(125, 'medicine_total', 10, 'Fast Forecast', 'success', NULL, 56, 181, 0.026, 96.9777, 128.1933, 95.1000, '2026-09-10 15:44:37'),
(126, 'medicine_total', 10, 'ARIMA', 'success', NULL, 180, 361, 19.812, 102.9113, 126.9432, 106.5600, '2026-09-10 15:44:38'),
(127, 'medicine_total', 30, 'ARIMA', 'success', NULL, 180, 361, 8.446, 102.9113, 126.9432, 106.5600, '2026-09-10 15:45:12'),
(128, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-10 15:45:21'),
(129, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-10 15:45:27'),
(130, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 7.372, 3.1954, 5.0094, 58.0400, '2026-09-10 15:45:34'),
(131, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 8.442, 3.1954, 5.0094, 58.0400, '2026-09-10 15:46:15'),
(132, 'visits_total', 90, 'ARIMA', 'success', NULL, 180, 477, 6.515, 3.1954, 5.0094, 58.0400, '2026-09-10 15:47:39'),
(133, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 7.077, 3.1954, 5.0094, 58.0400, '2026-09-10 15:57:58'),
(134, 'visits_total', 90, 'ARIMA', 'success', NULL, 180, 477, 7.783, 3.1954, 5.0094, 58.0400, '2026-09-10 15:59:51'),
(135, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.014, 4.8393, 5.5326, 44.6200, '2026-09-10 15:59:59'),
(136, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.030, 4.8393, 5.5326, 44.6200, '2026-09-10 16:00:03'),
(137, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.019, 4.8393, 5.5326, 44.6200, '2026-09-10 16:00:06'),
(138, 'visits_total', 15, 'Fast Forecast', 'success', NULL, 56, 165, 0.015, 4.8393, 5.5326, 44.6200, '2026-09-10 16:00:17'),
(139, 'visits_total', 15, 'ARIMA', 'success', NULL, 180, 477, 7.064, 3.1954, 5.0094, 58.0400, '2026-09-10 16:00:17'),
(140, 'medicine_total', 15, 'Fast Forecast', 'success', NULL, 56, 181, 0.010, 96.9777, 128.1933, 95.1000, '2026-09-10 16:01:01'),
(141, 'medicine_total', 15, 'ARIMA', 'success', NULL, 180, 361, 6.157, 102.9113, 126.9432, 106.5600, '2026-09-10 16:01:04'),
(142, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.029, 4.8393, 5.5326, 44.6200, '2026-09-10 20:27:04'),
(143, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.028, 4.8393, 5.5326, 44.6200, '2026-09-10 21:29:32'),
(144, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.013, 4.8393, 5.5326, 44.6200, '2026-09-10 21:52:57'),
(145, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.027, 4.8393, 5.5326, 44.6200, '2026-09-12 18:54:33'),
(146, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.052, 4.8393, 5.5326, 44.6200, '2026-09-12 19:18:20'),
(147, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.015, 4.8393, 5.5326, 44.6200, '2026-09-12 19:18:26'),
(148, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.020, 4.8393, 5.5326, 44.6200, '2026-09-16 20:54:29'),
(149, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-16 20:54:35'),
(150, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.030, 4.8393, 5.5326, 44.6200, '2026-09-16 20:54:59'),
(151, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-16 20:55:21'),
(152, 'visits_total', 30, 'ARIMA', 'failed', 'Forecasting script failed. \'\"python\"\' is not recognized as an internal or external command,\noperable program or batch file.', NULL, NULL, 0.457, NULL, NULL, NULL, '2026-09-16 20:55:25'),
(153, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.059, 4.8393, 5.5326, 44.6200, '2026-09-16 21:01:30'),
(154, 'visits_total', 15, 'Fast Forecast', 'success', NULL, 56, 165, 0.023, 4.8393, 5.5326, 44.6200, '2026-09-16 21:01:36'),
(155, 'visits_total', 15, 'ARIMA', 'success', NULL, 180, 477, 15.118, 3.1954, 5.0094, 58.0400, '2026-09-16 21:01:36'),
(156, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-16 21:09:40'),
(157, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.017, 4.8393, 5.5326, 44.6200, '2026-09-16 21:11:47'),
(158, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.011, 4.8393, 5.5326, 44.6200, '2026-09-16 21:17:07'),
(159, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-16 21:19:46'),
(160, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.013, 4.8393, 5.5326, 44.6200, '2026-09-16 21:58:15'),
(161, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.006, 4.8393, 5.5326, 44.6200, '2026-09-16 21:59:13'),
(162, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 9.435, 3.1954, 5.0094, 58.0400, '2026-09-16 21:59:18'),
(163, 'visits_total', 25, 'Fast Forecast', 'success', NULL, 56, 165, 0.023, 4.8393, 5.5326, 44.6200, '2026-09-16 21:59:35'),
(164, 'visits_total', 25, 'ARIMA', 'success', NULL, 180, 477, 6.444, 3.1954, 5.0094, 58.0400, '2026-09-16 21:59:36'),
(165, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.017, 4.8393, 5.5326, 44.6200, '2026-09-16 21:59:51'),
(166, 'visits_total', 14, 'ARIMA', 'success', NULL, 180, 477, 6.413, 3.1954, 5.0094, 58.0400, '2026-09-16 21:59:51'),
(167, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.022, 4.8393, 5.5326, 44.6200, '2026-09-17 20:17:55'),
(168, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.070, 4.8393, 5.5326, 44.6200, '2026-09-21 18:25:46'),
(169, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.011, 4.8393, 5.5326, 44.6200, '2026-09-21 18:28:24'),
(170, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-21 18:33:19'),
(171, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.011, 4.8393, 5.5326, 44.6200, '2026-09-21 18:45:37'),
(172, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.008, 4.8393, 5.5326, 44.6200, '2026-09-21 19:13:46'),
(173, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 40.878, 3.1954, 5.0094, 58.0400, '2026-09-21 19:13:50'),
(174, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.035, 4.8393, 5.5326, 44.6200, '2026-09-21 19:35:13'),
(175, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.021, 4.8393, 5.5326, 44.6200, '2026-09-21 19:35:17'),
(176, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.014, 4.8393, 5.5326, 44.6200, '2026-09-21 19:36:43'),
(177, 'visits_total', 25, 'Fast Forecast', 'success', NULL, 56, 165, 0.018, 4.8393, 5.5326, 44.6200, '2026-09-21 19:41:10'),
(178, 'visits_total', 25, 'ARIMA', 'success', NULL, 180, 477, 15.550, 3.1954, 5.0094, 58.0400, '2026-09-21 19:41:10'),
(179, 'visits_total', 15, 'Fast Forecast', 'success', NULL, 56, 165, 0.028, 4.8393, 5.5326, 44.6200, '2026-09-21 19:41:39'),
(180, 'visits_total', 15, 'ARIMA', 'success', NULL, 180, 477, 6.378, 3.1954, 5.0094, 58.0400, '2026-09-21 19:41:39'),
(181, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.012, 4.8393, 5.5326, 44.6200, '2026-09-21 19:41:54'),
(182, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 6.394, 3.1954, 5.0094, 58.0400, '2026-09-21 19:41:54'),
(183, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.068, 4.8393, 5.5326, 44.6200, '2026-09-30 19:13:59'),
(184, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.010, 4.8393, 5.5326, 44.6200, '2026-09-30 19:14:07'),
(185, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-30 19:14:21'),
(186, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.012, 4.8393, 5.5326, 44.6200, '2026-09-30 19:17:39'),
(187, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.009, 4.8393, 5.5326, 44.6200, '2026-09-30 19:52:36'),
(188, 'visits_total', 30, 'Fast Forecast', 'success', NULL, 56, 165, 0.007, 4.8393, 5.5326, 44.6200, '2026-09-30 19:52:40'),
(189, 'visits_total', 30, 'ARIMA', 'success', NULL, 180, 477, 16.336, 3.1954, 5.0094, 58.0400, '2026-09-30 19:53:04'),
(190, 'visits_total', 14, 'Fast Forecast', 'success', NULL, 56, 165, 0.018, 4.8393, 5.5326, 44.6200, '2026-10-05 20:57:11');

-- --------------------------------------------------------

--
-- Table structure for table `fp_records`
--

CREATE TABLE `fp_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `client_code` varchar(40) NOT NULL,
  `registration_date` date NOT NULL,
  `client_type` enum('new_acceptor','current_user','restart','changing_method','changing_clinic') NOT NULL DEFAULT 'new_acceptor',
  `method_accepted` enum('pills_coc','pills_pop','injectable_dmpa','implant','iud_interval','iud_postpartum','condom','btl','nsv','natural_lam','natural_sdm','natural_stm','other') NOT NULL DEFAULT 'pills_coc',
  `source` enum('public','private') NOT NULL DEFAULT 'public',
  `previous_method` varchar(80) DEFAULT NULL,
  `partner_name` varchar(120) DEFAULT NULL,
  `partner_occupation` varchar(100) DEFAULT NULL,
  `num_living_children` int(10) UNSIGNED DEFAULT 0,
  `plan_more_children` enum('yes','no','undecided') DEFAULT 'undecided',
  `drop_out_reason` enum('side_effects','medical_reasons','desire_pregnancy','loss_to_follow_up','relocation','other') DEFAULT NULL,
  `drop_out_date` date DEFAULT NULL,
  `status` enum('active','inactive','dropped_out') NOT NULL DEFAULT 'active',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fp_records`
--

INSERT INTO `fp_records` (`id`, `patient_id`, `client_code`, `registration_date`, `client_type`, `method_accepted`, `source`, `previous_method`, `partner_name`, `partner_occupation`, `num_living_children`, `plan_more_children`, `drop_out_reason`, `drop_out_date`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'FP-2026-0001', '2026-03-16', 'new_acceptor', 'pills_coc', 'public', NULL, 'Juan Dela Cruz', 'Farmer', 3, 'yes', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(2, 2, 'FP-2026-0002', '2026-04-15', 'current_user', 'pills_pop', 'public', NULL, 'Mark Santos', 'Driver', 2, 'no', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(3, 3, 'FP-2026-0003', '2026-08-03', 'current_user', 'injectable_dmpa', 'public', NULL, 'Reynaldo Reyes', 'Construction Worker', 2, 'undecided', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(4, 4, 'FP-2026-0004', '2026-05-19', 'changing_method', 'implant', 'public', 'Pills', 'Eduardo Ramos', 'Carpenter', 2, 'yes', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(5, 5, 'FP-2026-0005', '2026-04-03', 'restart', 'iud_interval', 'public', NULL, 'Michael Bautista', 'Vendor', 1, 'no', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(6, 6, 'FP-2026-0006', '2026-05-24', 'new_acceptor', 'condom', 'public', NULL, 'Christian Aquino', 'Electrician', 3, 'undecided', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(7, 7, 'FP-2026-0007', '2026-04-10', 'current_user', 'natural_lam', 'public', NULL, 'Antonio Mendoza', 'Security Guard', 1, 'yes', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(8, 8, 'FP-2026-0008', '2026-05-22', 'current_user', 'pills_coc', 'public', NULL, 'Juan Dela Cruz', 'Farmer', 3, 'no', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(9, 9, 'FP-2026-0009', '2026-07-23', 'changing_method', 'pills_pop', 'public', 'Pills', 'Mark Santos', 'Driver', 2, 'undecided', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(10, 10, 'FP-2026-0010', '2026-07-28', 'restart', 'injectable_dmpa', 'public', NULL, 'Reynaldo Reyes', 'Construction Worker', 3, 'yes', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(11, 11, 'FP-2026-0011', '2026-07-05', 'new_acceptor', 'implant', 'public', NULL, 'Eduardo Ramos', 'Carpenter', 1, 'no', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(12, 12, 'FP-2026-0012', '2026-06-26', 'current_user', 'iud_interval', 'public', NULL, 'Michael Bautista', 'Vendor', 3, 'undecided', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(13, 13, 'FP-2026-0013', '2026-04-07', 'current_user', 'condom', 'public', NULL, 'Christian Aquino', 'Electrician', 4, 'yes', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(14, 14, 'FP-2026-0014', '2026-06-16', 'changing_method', 'natural_lam', 'public', 'Pills', 'Antonio Mendoza', 'Security Guard', 4, 'no', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(15, 15, 'FP-2026-0015', '2026-07-19', 'restart', 'pills_coc', 'public', NULL, 'Juan Dela Cruz', 'Farmer', 3, 'undecided', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(16, 16, 'FP-2026-0016', '2026-05-26', 'new_acceptor', 'pills_pop', 'public', NULL, 'Mark Santos', 'Driver', 3, 'yes', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(17, 17, 'FP-2026-0017', '2026-05-04', 'current_user', 'injectable_dmpa', 'public', NULL, 'Reynaldo Reyes', 'Construction Worker', 3, 'no', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(18, 18, 'FP-2026-0018', '2026-04-03', 'current_user', 'implant', 'public', NULL, 'Eduardo Ramos', 'Carpenter', 4, 'undecided', NULL, NULL, 'active', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(19, 19, 'FP-2026-0019', '2026-05-28', 'changing_method', 'iud_interval', 'public', 'Pills', 'Michael Bautista', 'Vendor', 2, 'yes', 'side_effects', '2026-08-26', 'dropped_out', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(20, 20, 'FP-2026-0020', '2026-06-18', 'restart', 'condom', 'public', NULL, 'Christian Aquino', 'Electrician', 4, 'no', 'relocation', '2026-08-26', 'dropped_out', 'Regular client; completed orientation on RPRH and proper contraceptive use.', '2026-09-10 20:40:06', NULL),
(21, 34, 'FP-2026-0021', '2026-09-10', 'changing_method', 'condom', 'public', NULL, 'asdsd', 'sadsad', 2, 'no', NULL, NULL, 'active', 'dsadasd', '2026-09-10 20:43:14', NULL),
(22, 33, 'FP-2026-0022', '2026-09-10', 'new_acceptor', 'pills_coc', 'public', NULL, 'dsasd', 'dasd', 2, 'no', NULL, NULL, 'active', 'dasd', '2026-09-10 20:48:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fp_visits`
--

CREATE TABLE `fp_visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fp_record_id` bigint(20) UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `method_prescribed` varchar(80) NOT NULL,
  `quantity_dispensed` int(10) UNSIGNED DEFAULT 1,
  `next_appointment_date` date DEFAULT NULL,
  `bp_systolic` int(10) UNSIGNED DEFAULT NULL,
  `bp_diastolic` int(10) UNSIGNED DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `findings_complaints` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fp_visits`
--

INSERT INTO `fp_visits` (`id`, `fp_record_id`, `visit_date`, `method_prescribed`, `quantity_dispensed`, `next_appointment_date`, `bp_systolic`, `bp_diastolic`, `weight_kg`, `findings_complaints`, `recorded_by`, `created_at`) VALUES
(1, 1, '2026-03-16', 'Pills (COC - Combined)', 1, '2026-09-05', 111, 70, 65.60, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(2, 2, '2026-04-15', 'Pills (POP - Progestin Only)', 1, '2026-05-15', 118, 81, 49.40, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(3, 2, '2026-05-15', 'Pills (POP - Progestin Only)', 1, '2026-06-14', 126, 76, 57.70, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(4, 2, '2026-06-14', 'Pills (POP - Progestin Only)', 1, '2026-09-15', 130, 80, 64.10, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(5, 3, '2026-08-03', 'DMPA Injectable (Depo-Provera)', 1, '2026-11-01', 122, 82, 49.00, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(6, 3, '2026-11-01', 'DMPA Injectable (Depo-Provera)', 1, '2027-01-30', 113, 76, 59.90, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(7, 3, '2027-01-30', 'DMPA Injectable (Depo-Provera)', 1, '2026-10-10', 129, 76, 66.20, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(8, 4, '2026-05-19', 'Subdermal Implant Insertion/Check', 1, '2027-05-19', 128, 71, 50.10, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(9, 4, '2027-05-19', 'Subdermal Implant Insertion/Check', 1, '2026-10-10', 119, 76, 53.70, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(10, 5, '2026-04-03', 'IUD (Interval Check)', 1, '2026-09-05', 126, 83, 53.20, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(11, 6, '2026-05-24', 'Condoms (Pack of 10)', 10, '2026-09-15', 130, 76, 56.40, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(12, 7, '2026-04-10', 'LAM Counseling & Check', 1, '2026-06-09', 122, 84, 66.10, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(13, 7, '2026-06-09', 'LAM Counseling & Check', 1, '2026-08-08', 130, 85, 51.90, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(14, 7, '2026-08-08', 'LAM Counseling & Check', 1, '2026-10-10', 116, 78, 48.30, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(15, 8, '2026-05-22', 'Pills (COC - Combined)', 1, '2026-06-21', 127, 78, 49.60, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(16, 8, '2026-06-21', 'Pills (COC - Combined)', 1, '2026-07-21', 111, 84, 49.50, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(17, 8, '2026-07-21', 'Pills (COC - Combined)', 1, '2026-10-10', 123, 81, 63.20, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(18, 9, '2026-07-23', 'Pills (POP - Progestin Only)', 1, '2026-08-22', 126, 77, 65.50, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(19, 9, '2026-08-22', 'Pills (POP - Progestin Only)', 1, '2026-09-21', 124, 74, 49.40, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(20, 9, '2026-09-21', 'Pills (POP - Progestin Only)', 1, '2026-09-05', 121, 82, 52.10, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(21, 10, '2026-07-28', 'DMPA Injectable (Depo-Provera)', 1, '2026-10-26', 112, 73, 61.40, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(22, 10, '2026-10-26', 'DMPA Injectable (Depo-Provera)', 1, '2027-01-24', 110, 72, 50.90, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(23, 10, '2027-01-24', 'DMPA Injectable (Depo-Provera)', 1, '2026-09-15', 117, 82, 62.20, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(24, 11, '2026-07-05', 'Subdermal Implant Insertion/Check', 1, '2027-07-05', 122, 75, 52.90, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(25, 11, '2027-07-05', 'Subdermal Implant Insertion/Check', 1, '2026-10-10', 110, 79, 67.40, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(26, 12, '2026-06-26', 'IUD (Interval Check)', 1, '2026-12-23', 115, 77, 54.40, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(27, 12, '2026-12-23', 'IUD (Interval Check)', 1, '2026-10-10', 114, 82, 64.50, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(28, 13, '2026-04-07', 'Condoms (Pack of 10)', 10, '2026-09-05', 120, 80, 63.40, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(29, 14, '2026-06-16', 'LAM Counseling & Check', 1, '2026-09-15', 114, 72, 60.10, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(30, 15, '2026-07-19', 'Pills (COC - Combined)', 1, '2026-08-18', 128, 80, 57.70, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(31, 15, '2026-08-18', 'Pills (COC - Combined)', 1, '2026-10-10', 128, 73, 59.90, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(32, 16, '2026-05-26', 'Pills (POP - Progestin Only)', 1, '2026-10-10', 128, 80, 57.00, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(33, 17, '2026-05-04', 'DMPA Injectable (Depo-Provera)', 1, '2026-08-02', 124, 85, 50.00, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(34, 17, '2026-08-02', 'DMPA Injectable (Depo-Provera)', 1, '2026-09-05', 127, 71, 65.30, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(35, 18, '2026-04-03', 'Subdermal Implant Insertion/Check', 1, '2026-09-15', 119, 74, 55.00, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(36, 19, '2026-05-28', 'IUD (Interval Check)', 1, '2026-11-24', 117, 84, 51.90, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(37, 19, '2026-11-24', 'IUD (Interval Check)', 1, '2027-05-23', 117, 75, 49.90, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(38, 20, '2026-06-18', 'Condoms (Pack of 10)', 10, '2026-07-18', 116, 79, 59.30, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06'),
(39, 20, '2026-07-18', 'Condoms (Pack of 10)', 10, '2026-08-17', 119, 70, 56.60, 'Patient has no significant complaints. Blood pressure and vitals stable.', 2, '2026-09-10 20:40:06');

-- --------------------------------------------------------

--
-- Table structure for table `health_services`
--

CREATE TABLE `health_services` (
  `id` int(10) UNSIGNED NOT NULL,
  `service_code` varchar(50) NOT NULL,
  `service_name` varchar(120) NOT NULL,
  `category` enum('immunization','maternal','family_planning','ncd','child_health','general_consultation','infectious_disease','wellness') NOT NULL,
  `description` text DEFAULT NULL,
  `target_classification` varchar(120) DEFAULT 'all',
  `estimated_duration_minutes` int(10) UNSIGNED DEFAULT 15,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `health_services`
--

INSERT INTO `health_services` (`id`, `service_code`, `service_name`, `category`, `description`, `target_classification`, `estimated_duration_minutes`, `is_active`, `created_at`) VALUES
(1, 'EPI_VACCINE', 'Expanded Program on Immunization (EPI Routine Vaccines)', 'immunization', 'Routine childhood immunizations: BCG, HepB, Pentavalent (DPT-HepB-Hib), OPV, IPV, PCV, and MMR.', 'infant,under_five', 15, 1, '2026-09-30 19:42:44'),
(2, 'PRENATAL_CARE', 'Maternal & Prenatal Health Check-up', 'maternal', 'Comprehensive prenatal examination, gestational age, fundal height, fetal heart tone, tetanus toxoid, and micronutrient supplementation.', 'pregnant', 20, 1, '2026-09-30 19:42:44'),
(3, 'POSTNATAL_CARE', 'Postnatal & Lactation Counseling', 'maternal', 'Postpartum maternal assessment, lochia check, episiotomy/wound evaluation, and exclusive breastfeeding counseling.', 'postpartum', 20, 1, '2026-09-30 19:42:44'),
(4, 'FAMILY_PLANNING', 'Family Planning Counseling & Contraceptive Dispensing', 'family_planning', 'Reproductive health counseling, oral contraceptive pills (COC/POP), DMPA injectables, condoms, and IUD services.', 'adult,adolescent', 20, 1, '2026-09-30 19:42:44'),
(5, 'CHILD_NUTRITION', 'Under-5 Growth Monitoring & Nutrition', 'child_health', 'Weight-for-age, height-for-age, MUAC screening, biannual Vitamin A supplementation, and routine deworming.', 'infant,under_five', 15, 1, '2026-09-30 19:42:44'),
(6, 'NCD_CHECKUP', 'Hypertension & Diabetes Screening & Maintenance', 'ncd', 'Blood pressure monitoring, random/fasting blood sugar testing, CVD risk scoring, lifestyle counseling, and maintenance medicine refill.', 'senior,adult', 20, 1, '2026-09-30 19:42:44'),
(7, 'GEN_CONSULTATION', 'General Outpatient Medical Consultation', 'general_consultation', 'Primary healthcare consultation, assessment of acute illnesses (cough, colds, fever, diarrhea), physical examination, and treatment.', 'all', 15, 1, '2026-09-30 19:42:44'),
(8, 'TB_DOTS', 'TB-DOTS Screening & Respiratory Assessment', 'infectious_disease', 'Screening for persistent cough >=2 weeks, sputum specimen collection/referral, DOTS adherence monitoring, and contact tracing.', 'all', 20, 1, '2026-09-30 19:42:44'),
(9, 'WOUND_CARE', 'First Aid, Wound Dressing & Minor Injury Care', 'general_consultation', 'Cleaning, disinfection, dressing of minor wounds/lacerations, burn management, and tetanus prophylaxis referral.', 'all', 15, 1, '2026-09-30 19:42:44'),
(10, 'SENIOR_WELLNESS', 'Senior Citizen Preventive Health & Wellness', 'wellness', 'Geriatric health assessment, visual screening, fall prevention, cognitive screening, and annual flu/pneumococcal vaccine coordination.', 'senior', 25, 1, '2026-09-30 19:42:44');

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `household_code` varchar(40) NOT NULL,
  `head_name` varchar(120) DEFAULT NULL,
  `address_line` varchar(255) NOT NULL,
  `barangay` varchar(120) NOT NULL,
  `city_municipality` varchar(120) NOT NULL DEFAULT 'Ilagan',
  `province` varchar(120) NOT NULL DEFAULT 'Isabela',
  `postal_code` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `household_code`, `head_name`, `address_line`, `barangay`, `city_municipality`, `province`, `postal_code`, `created_at`, `updated_at`) VALUES
(1, 'HH-2026-0001', 'Luz Castro', 'Rizal St. 297', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(2, 'HH-2026-0002', 'Pedro Francisco', 'Luna St. 473', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(3, 'HH-2026-0003', 'Margarita Gonzales', 'Bonifacio Ave. 324', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(4, 'HH-2026-0004', 'Daniel Valdez', 'Roxas St. 367', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(5, 'HH-2026-0005', 'Isabel Flores', 'Magsaysay St. 440', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(6, 'HH-2026-0006', 'Gabriel Castro', 'Osmena Ave. 390', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(7, 'HH-2026-0007', 'Elena Rivera', 'Mabini St. 79', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(8, 'HH-2026-0008', 'Ramon Aguilar', 'Roxas St. 167', 'Purok 5', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(9, 'HH-2026-0009', 'Carmen Mercado', 'Roxas St. 168', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(10, 'HH-2026-0010', 'Gabriel Pascual', 'Bonifacio Ave. 53', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(11, 'HH-2026-0011', 'Elena Rivera', 'Mabini St. 289', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(12, 'HH-2026-0012', 'Antonio Santiago', 'Luna St. 135', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(13, 'HH-2026-0013', 'Gloria Castro', 'Magsaysay St. 298', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(14, 'HH-2026-0014', 'Pedro Torres', 'Osmena Ave. 78', 'Purok 6', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(15, 'HH-2026-0015', 'Sofia Navarro', 'Luna St. 263', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(16, 'HH-2026-0016', 'Antonio Santiago', 'Luna St. 28', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(17, 'HH-2026-0017', 'Esperanza Martinez', 'Roxas St. 300', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(18, 'HH-2026-0018', 'Fernando Dela Cruz', 'Del Pilar St. 263', 'Purok 5', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(19, 'HH-2026-0019', 'Josefa Lopez', 'Rizal St. 157', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(20, 'HH-2026-0020', 'Mario Ramos', 'Del Pilar St. 16', 'Purok 5', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:16:00', '2026-09-16 21:37:29'),
(23, 'HH-2026-0021', 'Catalina Mendoza', 'Magsaysay St. 47', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(24, 'HH-2026-0022', 'Mario Mendoza', 'Quezon Blvd. 217', 'Purok 6', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(25, 'HH-2026-0023', 'Angela Santos', 'Bonifacio Ave. 442', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(26, 'HH-2026-0024', 'Carlos Santos', 'Osmena Ave. 298', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(27, 'HH-2026-0025', 'Catalina Dela Cruz', 'Roxas St. 395', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(28, 'HH-2026-0026', 'Antonio Fernandez', 'Rizal St. 82', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(29, 'HH-2026-0027', 'Esperanza Soriano', 'Quezon Blvd. 444', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(30, 'HH-2026-0028', 'Ramon Torres', 'Aguinaldo Ave. 135', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(31, 'HH-2026-0029', 'Isabel Aquino', 'Luna St. 154', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(32, 'HH-2026-0030', 'Ricardo Soriano', 'Roxas St. 160', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(33, 'HH-2026-0031', 'Patricia Martinez', 'Luna St. 451', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(34, 'HH-2026-0032', 'Fernando Navarro', 'Bonifacio Ave. 15', 'Purok 6', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(35, 'HH-2026-0033', 'Angela Reyes', 'Quezon Blvd. 202', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(36, 'HH-2026-0034', 'Eduardo Martinez', 'Quezon Blvd. 442', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(37, 'HH-2026-0035', 'Rosa Mercado', 'Quezon Blvd. 437', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(38, 'HH-2026-0036', 'Luis Ramos', 'Rizal St. 22', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(39, 'HH-2026-0037', 'Carmen Martinez', 'Luna St. 345', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(40, 'HH-2026-0038', 'Mario Rivera', 'Roxas St. 118', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(41, 'HH-2026-0039', 'Teresa Castro', 'Del Pilar St. 381', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(42, 'HH-2026-0040', 'Eduardo Bautista', 'Luna St. 152', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:32', '2026-09-16 21:37:29'),
(43, 'HH-2026-0041', 'Dolores Fernandez', 'Bonifacio Ave. 15', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(44, 'HH-2026-0042', 'Jose Castro', 'Quezon Blvd. 124', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(45, 'HH-2026-0043', 'Maria Fernandez', 'Quezon Blvd. 490', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(46, 'HH-2026-0044', 'Daniel Santiago', 'Bonifacio Ave. 206', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(47, 'HH-2026-0045', 'Angela Francisco', 'Del Pilar St. 172', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(48, 'HH-2026-0046', 'Ramon Flores', 'Quezon Blvd. 35', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(49, 'HH-2026-0047', 'Angela Pascual', 'Rizal St. 63', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(50, 'HH-2026-0048', 'Emilio Rivera', 'Del Pilar St. 488', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(51, 'HH-2026-0049', 'Gloria Valdez', 'Roxas St. 267', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(52, 'HH-2026-0050', 'Mario Navarro', 'Magsaysay St. 128', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(53, 'HH-2026-0051', 'Isabel Santiago', 'Aguinaldo Ave. 394', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(54, 'HH-2026-0052', 'Carlos Flores', 'Del Pilar St. 138', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(55, 'HH-2026-0053', 'Victoria Bautista', 'Roxas St. 123', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(56, 'HH-2026-0054', 'Roberto Bautista', 'Aguinaldo Ave. 473', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(57, 'HH-2026-0055', 'Maria Aquino', 'Magsaysay St. 311', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(58, 'HH-2026-0056', 'Francisco Dela Cruz', 'Mabini St. 217', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(59, 'HH-2026-0057', 'Cristina Soriano', 'Rizal St. 391', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(60, 'HH-2026-0058', 'Eduardo Torres', 'Osmena Ave. 218', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(61, 'HH-2026-0059', 'Rosa Francisco', 'Mabini St. 346', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(62, 'HH-2026-0060', 'Eduardo Flores', 'Bonifacio Ave. 140', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:18:39', '2026-09-16 21:37:29'),
(63, 'HH-2026-0061', 'Maria Bautista', 'Quezon Blvd. 100', 'Purok 6', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(64, 'HH-2026-0062', 'Carlos Mendoza', 'Bonifacio Ave. 371', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(65, 'HH-2026-0063', 'Cristina Santiago', 'Magsaysay St. 179', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(66, 'HH-2026-0064', 'Roberto Pascual', 'Roxas St. 14', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(67, 'HH-2026-0065', 'Dolores Martinez', 'Quezon Blvd. 261', 'Purok 6', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(68, 'HH-2026-0066', 'Manuel Aguilar', 'Luna St. 139', 'Purok 5', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(69, 'HH-2026-0067', 'Patricia Aquino', 'Quezon Blvd. 315', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(70, 'HH-2026-0068', 'Fernando Lopez', 'Rizal St. 181', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(71, 'HH-2026-0069', 'Gloria Martinez', 'Roxas St. 12', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(72, 'HH-2026-0070', 'Mario Lopez', 'Quezon Blvd. 20', 'Purok 6', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(73, 'HH-2026-0071', 'Patricia Gonzales', 'Magsaysay St. 433', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(74, 'HH-2026-0072', 'Manuel Santiago', 'Del Pilar St. 59', 'Purok 6', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(75, 'HH-2026-0073', 'Josefa Martinez', 'Quezon Blvd. 208', 'Purok 2', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(76, 'HH-2026-0074', 'Luis Soriano', 'Aguinaldo Ave. 199', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(77, 'HH-2026-0075', 'Beatriz Flores', 'Luna St. 198', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(78, 'HH-2026-0076', 'Pedro Mendoza', 'Aguinaldo Ave. 277', 'Purok 3', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(79, 'HH-2026-0077', 'Dolores Castro', 'Bonifacio Ave. 351', 'Purok 7', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(80, 'HH-2026-0078', 'Emilio Gonzales', 'Del Pilar St. 403', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(81, 'HH-2026-0079', 'Catalina Francisco', 'Bonifacio Ave. 191', 'Purok 4', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(82, 'HH-2026-0080', 'Rodrigo Rivera', 'Luna St. 253', 'Purok 1', 'Ilagan', 'Isabela', NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29');

-- --------------------------------------------------------

--
-- Table structure for table `immunization_records`
--

CREATE TABLE `immunization_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `vaccine_id` int(10) UNSIGNED NOT NULL,
  `dose_no` int(10) UNSIGNED NOT NULL,
  `administered_on` date NOT NULL,
  `administered_at` datetime NOT NULL,
  `lot_no` varchar(60) DEFAULT NULL,
  `administered_by` int(10) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `immunization_records`
--

INSERT INTO `immunization_records` (`id`, `patient_id`, `vaccine_id`, `dose_no`, `administered_on`, `administered_at`, `lot_no`, `administered_by`, `notes`, `created_at`) VALUES
(14, 40, 1, 1, '2026-09-21', '2026-09-21 18:49:00', NULL, NULL, 'dasdsd', '2026-09-21 18:50:09'),
(15, 40, 2, 1, '2026-09-21', '2026-09-21 18:50:00', NULL, NULL, NULL, '2026-09-21 18:50:48'),
(16, 40, 3, 1, '2026-09-21', '2026-09-21 18:51:00', NULL, NULL, NULL, '2026-09-21 18:51:36');

-- --------------------------------------------------------

--
-- Table structure for table `immunization_schedule`
--

CREATE TABLE `immunization_schedule` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `vaccine_id` int(10) UNSIGNED NOT NULL,
  `dose_no` int(10) UNSIGNED NOT NULL,
  `scheduled_date` date NOT NULL,
  `status` enum('scheduled','completed','missed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `immunization_schedule`
--

INSERT INTO `immunization_schedule` (`id`, `patient_id`, `vaccine_id`, `dose_no`, `scheduled_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 73, 1, 1, '2025-11-17', 'completed', '2026-03-18 10:20:04', NULL),
(2, 73, 2, 1, '2025-11-16', 'completed', '2026-03-18 10:20:04', NULL),
(3, 73, 3, 1, '2026-01-16', 'completed', '2026-03-18 10:20:04', NULL),
(4, 73, 3, 2, '2026-03-09', 'scheduled', '2026-03-18 10:20:04', NULL),
(5, 73, 3, 3, '2026-05-09', 'scheduled', '2026-03-18 10:20:04', NULL),
(6, 73, 4, 1, '2026-01-13', 'completed', '2026-03-18 10:20:04', NULL),
(7, 73, 4, 2, '2026-03-09', 'missed', '2026-03-18 10:20:04', NULL),
(8, 73, 4, 3, '2026-05-09', 'scheduled', '2026-03-18 10:20:04', NULL),
(9, 97, 5, 1, '2025-08-27', 'completed', '2026-03-18 10:20:04', NULL),
(10, 97, 5, 2, '2025-10-17', 'completed', '2026-03-18 10:20:04', NULL),
(11, 35, 5, 1, '2025-08-01', 'missed', '2026-03-18 10:20:04', NULL),
(12, 35, 5, 2, '2025-10-10', 'completed', '2026-03-18 10:20:04', NULL),
(13, 40, 5, 1, '2025-04-20', 'completed', '2026-03-18 10:20:04', NULL),
(14, 40, 5, 2, '2025-06-18', 'completed', '2026-03-18 10:20:04', NULL),
(16, 82, 1, 1, '2026-09-26', 'completed', '2026-09-10 21:31:16', '2026-09-10 21:31:37');

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(160) NOT NULL,
  `generic_name` varchar(160) DEFAULT NULL,
  `formulation` varchar(80) DEFAULT NULL,
  `strength` varchar(60) DEFAULT NULL,
  `unit` varchar(30) NOT NULL,
  `reorder_level` int(10) UNSIGNED DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicines`
--

INSERT INTO `medicines` (`id`, `name`, `generic_name`, `formulation`, `strength`, `unit`, `reorder_level`, `created_at`) VALUES
(1, 'Amoxicillin', 'Amoxicillin', 'Capsule', '500mg', 'capsule', 200, '2026-03-18 10:20:05'),
(2, 'Amoxicillin', 'Amoxicillin', 'Suspension', '250mg/5mL', 'bottle', 50, '2026-03-18 10:20:05'),
(3, 'Cefalexin', 'Cefalexin', 'Capsule', '500mg', 'capsule', 150, '2026-03-18 10:20:05'),
(4, 'Cotrimoxazole', 'Sulfamethoxazole + Trimethoprim', 'Tablet', '400mg+80mg', 'tablet', 200, '2026-03-18 10:20:05'),
(5, 'Erythromycin', 'Erythromycin', 'Tablet', '500mg', 'tablet', 100, '2026-03-18 10:20:05'),
(6, 'Paracetamol', 'Paracetamol', 'Tablet', '500mg', 'tablet', 500, '2026-03-18 10:20:05'),
(7, 'Paracetamol', 'Paracetamol', 'Syrup', '120mg/5mL', 'bottle', 100, '2026-03-18 10:20:05'),
(8, 'Ibuprofen', 'Ibuprofen', 'Tablet', '400mg', 'tablet', 300, '2026-03-18 10:20:05'),
(9, 'Mefenamic Acid', 'Mefenamic Acid', 'Capsule', '500mg', 'capsule', 200, '2026-03-18 10:20:05'),
(10, 'Amlodipine', 'Amlodipine', 'Tablet', '5mg', 'tablet', 300, '2026-03-18 10:20:05'),
(11, 'Amlodipine', 'Amlodipine', 'Tablet', '10mg', 'tablet', 300, '2026-03-18 10:20:05'),
(12, 'Losartan', 'Losartan', 'Tablet', '50mg', 'tablet', 250, '2026-03-18 10:20:05'),
(13, 'Atenolol', 'Atenolol', 'Tablet', '50mg', 'tablet', 200, '2026-03-18 10:20:05'),
(14, 'Metformin', 'Metformin', 'Tablet', '500mg', 'tablet', 400, '2026-03-18 10:20:05'),
(15, 'Metformin', 'Metformin', 'Tablet', '850mg', 'tablet', 300, '2026-03-18 10:20:05'),
(16, 'Glibenclamide', 'Glibenclamide', 'Tablet', '5mg', 'tablet', 200, '2026-03-18 10:20:05'),
(17, 'Ferrous Sulfate', 'Ferrous Sulfate', 'Tablet', '325mg', 'tablet', 500, '2026-03-18 10:20:05'),
(18, 'Folic Acid', 'Folic Acid', 'Tablet', '5mg', 'tablet', 300, '2026-03-18 10:20:05'),
(19, 'Vitamin B Complex', 'B-Complex', 'Capsule', 'Multi', 'capsule', 250, '2026-03-18 10:20:05'),
(20, 'Ascorbic Acid', 'Vitamin C', 'Tablet', '500mg', 'tablet', 400, '2026-03-18 10:20:05'),
(21, 'Multivitamins', 'Multivitamins', 'Capsule', 'Multi', 'capsule', 300, '2026-03-18 10:20:05'),
(22, 'Salbutamol', 'Salbutamol', 'Tablet', '2mg', 'tablet', 150, '2026-03-18 10:20:05'),
(23, 'Salbutamol', 'Salbutamol', 'Nebule', '2.5mg/2.5mL', 'nebule', 100, '2026-03-18 10:20:05'),
(24, 'Cetirizine', 'Cetirizine', 'Tablet', '10mg', 'tablet', 300, '2026-03-18 10:20:05'),
(25, 'Carbocisteine', 'Carbocisteine', 'Capsule', '500mg', 'capsule', 200, '2026-03-18 10:20:05'),
(26, 'Omeprazole', 'Omeprazole', 'Capsule', '20mg', 'capsule', 200, '2026-03-18 10:20:05'),
(27, 'Aluminum Hydroxide', 'Aluminum Hydroxide', 'Suspension', '200mg/5mL', 'bottle', 80, '2026-03-18 10:20:05'),
(28, 'Loperamide', 'Loperamide', 'Capsule', '2mg', 'capsule', 150, '2026-03-18 10:20:05'),
(29, 'ORS', 'Oral Rehydration Salts', 'Sachet', '27.9g', 'sachet', 500, '2026-03-18 10:20:05'),
(30, 'Zinc Sulfate', 'Zinc Sulfate', 'Tablet', '20mg', 'tablet', 300, '2026-03-18 10:20:05'),
(31, 'Isoniazid', 'Isoniazid', 'Tablet', '300mg', 'tablet', 200, '2026-03-18 10:20:05'),
(32, 'Rifampicin', 'Rifampicin', 'Capsule', '450mg', 'capsule', 150, '2026-03-18 10:20:05'),
(33, 'Ethambutol', 'Ethambutol', 'Tablet', '400mg', 'tablet', 150, '2026-03-18 10:20:05'),
(34, 'Pyrazinamide', 'Pyrazinamide', 'Tablet', '500mg', 'tablet', 150, '2026-03-18 10:20:05'),
(35, 'Betamethasone', 'Betamethasone', 'Cream', '0.1%', 'tube', 50, '2026-03-18 10:20:05'),
(36, 'Clotrimazole', 'Clotrimazole', 'Cream', '1%', 'tube', 60, '2026-03-18 10:20:05'),
(37, 'Povidone Iodine', 'Povidone Iodine', 'Solution', '10%', 'bottle', 80, '2026-03-18 10:20:05'),
(38, 'Alcohol', 'Ethyl Alcohol', 'Solution', '70%', 'bottle', 100, '2026-03-18 10:20:05'),
(39, 'Metoprolol', 'Metoprolol Tartrate', 'Tablet', '50mg', 'box', 2, '2026-09-10 15:25:46'),
(40, 'Dehydrosol', 'Oral Rehydration Salts', 'Sachet', 'Standard ORS', 'box', 2, '2026-09-10 15:25:46'),
(41, 'Lagundi', 'Vitex negundo L.', 'Tablet', '300mg', 'box', 2, '2026-09-10 15:25:46'),
(42, 'Lagundi Syrup', 'Vitex negundo L.', 'Syrup', '300mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(43, 'Clonidine', 'Clonidine Hydrochloride', 'Tablet', '75mcg', 'box', 1, '2026-09-10 15:25:46'),
(44, 'Dicycloverine', 'Dicycloverine Hydrochloride', 'Tablet', '10mg', 'box', 2, '2026-09-10 15:25:46'),
(45, 'Ferrous Sulfate', 'Ferrous Sulfate', 'Tablet', '500mg', 'box', 2, '2026-09-10 15:25:46'),
(46, 'Ascorbic Acid Syrup', 'Vitamin C', 'Syrup', '100mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(47, 'Multivitamins Syrup', 'Multivitamins', 'Syrup', '60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(48, 'Paracetamol (4Fever) Syrup', 'Paracetamol', 'Syrup', '120mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(49, 'Metoclopramide Hydrochloride Syrup', 'Metoclopramide HCl', 'Syrup', '5mg/5mL 60mL', 'bottle', 3, '2026-09-10 15:25:46'),
(50, 'Paracetamol (4Fever) Drops', 'Paracetamol', 'Drops', '100mg/mL 15mL', 'bottle', 5, '2026-09-10 15:25:46'),
(51, 'Lagundi', 'Vitex negundo L.', 'Tablet', '600mg', 'box', 2, '2026-09-10 15:25:46'),
(52, 'Paracetamol (Rapidol)', 'Paracetamol', 'Tablet', '500mg', 'box', 3, '2026-09-10 15:25:46'),
(53, 'Ancovit-B Vitamins', 'Vitamin B-Complex', 'Tablet', 'B1+B6+B12', 'box', 2, '2026-09-10 15:25:46'),
(54, 'Cinnarizine', 'Cinnarizine', 'Tablet', '25mg', 'box', 2, '2026-09-10 15:25:46'),
(55, 'Metoclopramide', 'Metoclopramide HCl', 'Tablet', '10mg', 'box', 2, '2026-09-10 15:25:46'),
(56, 'Ascorbic Acid Drops', 'Vitamin C', 'Drops', '100mg/mL 15mL', 'bottle', 5, '2026-09-10 15:25:46'),
(57, 'Multivitamins Drops', 'Multivitamins', 'Drops', '15mL', 'bottle', 5, '2026-09-10 15:25:46'),
(58, 'Cetirizine Drops', 'Cetirizine HCl', 'Drops', '2.5mg/mL 10mL', 'bottle', 5, '2026-09-10 15:25:46'),
(59, 'Dicycloverine Syrup', 'Dicycloverine HCl', 'Syrup', '10mg/5mL 60mL', 'bottle', 3, '2026-09-10 15:25:46'),
(60, 'Cetirizine Syrup', 'Cetirizine HCl', 'Syrup', '5mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(61, 'Mefenamic Acid', 'Mefenamic Acid', 'Capsule', '250mg', 'box', 2, '2026-09-10 15:25:46'),
(62, 'Lagundi (Offlem Forte)', 'Vitex negundo L.', 'Tablet', '600mg', 'box', 2, '2026-09-10 15:25:46'),
(63, 'Lagundi (Offlem)', 'Vitex negundo L.', 'Tablet', '600mg', 'box', 2, '2026-09-10 15:25:46'),
(64, 'Iron + Folic Acid', 'Ferrous Fumarate + Folic Acid', 'Tablet', '60mg/250mcg', 'box', 2, '2026-09-10 15:25:46'),
(65, 'Vitamin B1 + B6 + B12', 'Vitamin B-Complex', 'Tablet', 'B1+B6+B12', 'box', 2, '2026-09-10 15:25:46'),
(66, 'Gauze Sponge', 'Sterile Gauze Sponge', 'Supply', '4x4', 'box', 2, '2026-09-10 15:25:46'),
(67, 'Povidone Iodine', 'Povidone Iodine', 'Solution', '10% 30mL', 'bottle', 3, '2026-09-10 15:25:46'),
(68, 'Amoxicillin Drops', 'Amoxicillin', 'Drops', '100mg/mL 10mL', 'bottle', 5, '2026-09-10 15:25:46'),
(69, 'Carbocisteine Drops', 'Carbocisteine', 'Drops', '50mg/mL 15mL', 'bottle', 5, '2026-09-10 15:25:46'),
(70, 'Paracetamol Drops', 'Paracetamol', 'Drops', '100mg/mL 15mL', 'bottle', 5, '2026-09-10 15:25:46'),
(71, 'Micropore Tape', 'Surgical Paper Tape', 'Supply', '1 inch', 'roll', 2, '2026-09-10 15:25:46'),
(72, 'Amoxicillin Suspension', 'Amoxicillin', 'Suspension', '250mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(73, 'Paracetamol Syrup', 'Paracetamol', 'Syrup', '120mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(74, 'Lagundi (Offlem) Syrup', 'Vitex negundo L.', 'Syrup', '300mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(75, 'Ascorbic Acid (Myrevit-C)', 'Vitamin C', 'Syrup', '100mg/5mL 60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(76, 'Multivitamins (Multilem PNT)', 'Multivitamins', 'Syrup', '60mL', 'bottle', 5, '2026-09-10 15:25:46'),
(77, 'Iron + Folic Acid', 'Ferrous Fumarate + Folic Acid', 'Tablet', '600mg', 'box', 2, '2026-09-10 15:25:46');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_batches`
--

CREATE TABLE `medicine_batches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `medicine_id` bigint(20) UNSIGNED NOT NULL,
  `batch_no` varchar(80) NOT NULL,
  `expiry_date` date NOT NULL,
  `received_date` date NOT NULL,
  `quantity_received` int(10) UNSIGNED NOT NULL,
  `current_stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicine_batches`
--

INSERT INTO `medicine_batches` (`id`, `medicine_id`, `batch_no`, `expiry_date`, `received_date`, `quantity_received`, `current_stock`, `created_at`) VALUES
(1, 1, 'BATCH-2026-2489', '2026-04-18', '2025-04-18', 1000, 0, '2026-03-18 10:20:05'),
(2, 1, 'BATCH-2026-8899', '2027-08-18', '2025-08-18', 600, 0, '2026-03-18 10:20:05'),
(3, 2, 'BATCH-2026-7391', '2028-09-18', '2025-09-18', 200, 0, '2026-03-18 10:20:05'),
(4, 2, 'BATCH-2026-6880', '2029-02-18', '2026-02-18', 250, 0, '2026-03-18 10:20:05'),
(5, 2, 'BATCH-2026-9602', '2026-05-18', '2025-05-18', 200, 0, '2026-03-18 10:20:05'),
(6, 3, 'BATCH-2026-9874', '2027-07-18', '2025-07-18', 300, 0, '2026-03-18 10:20:05'),
(7, 3, 'BATCH-2026-3759', '2026-10-18', '2025-10-18', 450, 0, '2026-03-18 10:20:05'),
(8, 3, 'BATCH-2026-3883', '2026-05-18', '2025-05-18', 450, 0, '2026-03-18 10:20:05'),
(9, 3, 'BATCH-2026-7187', '2026-12-18', '2025-12-18', 750, 0, '2026-03-18 10:20:05'),
(10, 4, 'BATCH-2026-4974', '2027-02-18', '2026-02-18', 800, 0, '2026-03-18 10:20:05'),
(11, 4, 'BATCH-2026-1743', '2027-02-18', '2026-02-18', 400, 0, '2026-03-18 10:20:05'),
(12, 4, 'BATCH-2026-4727', '2029-02-18', '2026-02-18', 600, 0, '2026-03-18 10:20:05'),
(13, 5, 'BATCH-2026-4361', '2028-08-18', '2025-08-18', 200, 0, '2026-03-18 10:20:05'),
(14, 5, 'BATCH-2026-5377', '2027-01-18', '2026-01-18', 200, 0, '2026-03-18 10:20:05'),
(15, 5, 'BATCH-2026-5349', '2027-12-18', '2025-12-18', 400, 0, '2026-03-18 10:20:05'),
(16, 6, 'BATCH-2026-2930', '2026-08-18', '2025-08-18', 1500, 0, '2026-03-18 10:20:05'),
(17, 6, 'BATCH-2026-2804', '2026-09-18', '2025-09-18', 1000, 0, '2026-03-18 10:20:05'),
(18, 6, 'BATCH-2026-8613', '2026-03-18', '2025-03-18', 1500, 0, '2026-03-18 10:20:05'),
(19, 6, 'BATCH-2026-2086', '2026-06-18', '2025-06-18', 1000, 0, '2026-03-18 10:20:05'),
(20, 7, 'BATCH-2026-9373', '2028-05-18', '2025-05-18', 400, 0, '2026-03-18 10:20:05'),
(21, 7, 'BATCH-2026-5557', '2027-02-18', '2026-02-18', 200, 0, '2026-03-18 10:20:05'),
(22, 7, 'BATCH-2026-6909', '2027-05-18', '2025-05-18', 500, 0, '2026-03-18 10:20:05'),
(23, 7, 'BATCH-2026-4861', '2028-04-18', '2025-04-18', 400, 0, '2026-03-18 10:20:05'),
(24, 8, 'BATCH-2026-3931', '2027-01-18', '2026-01-18', 1500, 0, '2026-03-18 10:20:05'),
(25, 8, 'BATCH-2026-6405', '2027-05-18', '2025-05-18', 1200, 0, '2026-03-18 10:20:05'),
(26, 8, 'BATCH-2026-3839', '2027-04-18', '2025-04-18', 1500, 0, '2026-03-18 10:20:05'),
(27, 9, 'BATCH-2026-4603', '2028-08-18', '2025-08-18', 600, 0, '2026-03-18 10:20:05'),
(28, 9, 'BATCH-2026-1742', '2029-01-18', '2026-01-18', 400, 0, '2026-03-18 10:20:05'),
(29, 9, 'BATCH-2026-4338', '2027-06-18', '2025-06-18', 800, 0, '2026-03-18 10:20:05'),
(30, 9, 'BATCH-2026-5291', '2028-09-18', '2025-09-18', 600, 0, '2026-03-18 10:20:05'),
(31, 10, 'BATCH-2026-5258', '2026-07-18', '2025-07-18', 1200, 0, '2026-03-18 10:20:05'),
(32, 10, 'BATCH-2026-2454', '2029-02-18', '2026-02-18', 1200, 0, '2026-03-18 10:20:05'),
(33, 10, 'BATCH-2026-7498', '2026-11-18', '2025-11-18', 1500, 0, '2026-03-18 10:20:05'),
(34, 10, 'BATCH-2026-9587', '2028-08-18', '2025-08-18', 1500, 0, '2026-03-18 10:20:05'),
(35, 11, 'BATCH-2026-9825', '2028-02-18', '2026-02-18', 900, 0, '2026-03-18 10:20:05'),
(36, 11, 'BATCH-2026-2147', '2028-11-18', '2025-11-18', 900, 0, '2026-03-18 10:20:05'),
(37, 12, 'BATCH-2026-6035', '2027-07-18', '2025-07-18', 1000, 0, '2026-03-18 10:20:05'),
(38, 12, 'BATCH-2026-8715', '2027-10-18', '2025-10-18', 1250, 0, '2026-03-18 10:20:05'),
(39, 12, 'BATCH-2026-9190', '2027-01-18', '2026-01-18', 1000, 0, '2026-03-18 10:20:05'),
(40, 12, 'BATCH-2026-9005', '2027-12-18', '2025-12-18', 750, 0, '2026-03-18 10:20:05'),
(41, 13, 'BATCH-2026-9140', '2027-08-18', '2025-08-18', 1000, 0, '2026-03-18 10:20:05'),
(42, 13, 'BATCH-2026-2599', '2026-12-18', '2025-12-18', 600, 0, '2026-03-18 10:20:05'),
(43, 13, 'BATCH-2026-3174', '2026-08-18', '2025-08-18', 400, 0, '2026-03-18 10:20:05'),
(44, 14, 'BATCH-2026-6079', '2028-02-18', '2026-02-18', 1200, 0, '2026-03-18 10:20:06'),
(45, 14, 'BATCH-2026-1538', '2026-10-18', '2025-10-18', 1200, 0, '2026-03-18 10:20:06'),
(46, 15, 'BATCH-2026-2114', '2027-12-18', '2025-12-18', 900, 0, '2026-03-18 10:20:06'),
(47, 15, 'BATCH-2026-5290', '2028-03-18', '2025-03-18', 1500, 0, '2026-03-18 10:20:06'),
(48, 15, 'BATCH-2026-9741', '2028-10-18', '2025-10-18', 1200, 0, '2026-03-18 10:20:06'),
(49, 15, 'BATCH-2026-9119', '2027-10-18', '2025-10-18', 600, 0, '2026-03-18 10:20:06'),
(50, 16, 'BATCH-2026-8092', '2028-03-18', '2025-03-18', 800, 0, '2026-03-18 10:20:06'),
(51, 16, 'BATCH-2026-9539', '2027-07-18', '2025-07-18', 600, 0, '2026-03-18 10:20:06'),
(52, 16, 'BATCH-2026-9257', '2028-09-18', '2025-09-18', 600, 0, '2026-03-18 10:20:06'),
(53, 17, 'BATCH-2026-2146', '2026-04-18', '2025-04-18', 2500, 0, '2026-03-18 10:20:06'),
(54, 17, 'BATCH-2026-9899', '2027-02-18', '2026-02-18', 1500, 0, '2026-03-18 10:20:06'),
(55, 18, 'BATCH-2026-3348', '2028-07-18', '2025-07-18', 900, 0, '2026-03-18 10:20:06'),
(56, 18, 'BATCH-2026-3249', '2028-11-18', '2025-11-18', 600, 0, '2026-03-18 10:20:06'),
(57, 18, 'BATCH-2026-2038', '2028-12-18', '2025-12-18', 600, 0, '2026-03-18 10:20:06'),
(58, 19, 'BATCH-2026-3657', '2026-09-18', '2025-09-18', 500, 0, '2026-03-18 10:20:06'),
(59, 19, 'BATCH-2026-6138', '2027-01-18', '2026-01-18', 750, 0, '2026-03-18 10:20:06'),
(60, 19, 'BATCH-2026-6097', '2027-05-18', '2025-05-18', 1000, 0, '2026-03-18 10:20:06'),
(61, 20, 'BATCH-2026-2732', '2026-09-18', '2025-09-18', 2000, 0, '2026-03-18 10:20:06'),
(62, 20, 'BATCH-2026-8207', '2027-01-18', '2026-01-18', 1200, 0, '2026-03-18 10:20:06'),
(63, 20, 'BATCH-2026-8671', '2028-08-18', '2025-08-18', 2000, 0, '2026-03-18 10:20:06'),
(64, 20, 'BATCH-2026-1956', '2027-01-18', '2026-01-18', 2000, 0, '2026-03-18 10:20:06'),
(65, 21, 'BATCH-2026-3714', '2028-09-18', '2025-09-18', 900, 0, '2026-03-18 10:20:06'),
(66, 21, 'BATCH-2026-9075', '2028-10-18', '2025-10-18', 1500, 0, '2026-03-18 10:20:06'),
(67, 21, 'BATCH-2026-4539', '2028-08-18', '2025-08-18', 1500, 0, '2026-03-18 10:20:06'),
(68, 22, 'BATCH-2026-3838', '2028-06-18', '2025-06-18', 300, 0, '2026-03-18 10:20:06'),
(69, 22, 'BATCH-2026-1924', '2027-02-18', '2026-02-18', 600, 0, '2026-03-18 10:20:06'),
(70, 22, 'BATCH-2026-3805', '2027-03-18', '2025-03-18', 750, 0, '2026-03-18 10:20:06'),
(71, 22, 'BATCH-2026-3913', '2028-02-18', '2026-02-18', 600, 0, '2026-03-18 10:20:06'),
(72, 23, 'BATCH-2026-1700', '2027-02-18', '2026-02-18', 500, 0, '2026-03-18 10:20:06'),
(73, 23, 'BATCH-2026-5162', '2027-12-18', '2025-12-18', 400, 0, '2026-03-18 10:20:06'),
(74, 24, 'BATCH-2026-9740', '2027-02-18', '2026-02-18', 600, 0, '2026-03-18 10:20:06'),
(75, 24, 'BATCH-2026-4961', '2028-09-18', '2025-09-18', 1500, 0, '2026-03-18 10:20:06'),
(76, 24, 'BATCH-2026-2563', '2027-07-18', '2025-07-18', 900, 0, '2026-03-18 10:20:06'),
(77, 25, 'BATCH-2026-3167', '2028-11-18', '2025-11-18', 400, 0, '2026-03-18 10:20:06'),
(78, 25, 'BATCH-2026-9622', '2027-09-18', '2025-09-18', 600, 0, '2026-03-18 10:20:06'),
(79, 25, 'BATCH-2026-7392', '2028-10-18', '2025-10-18', 800, 0, '2026-03-18 10:20:06'),
(80, 25, 'BATCH-2026-2165', '2028-11-18', '2025-11-18', 1000, 0, '2026-03-18 10:20:06'),
(81, 26, 'BATCH-2026-7905', '2027-06-18', '2025-06-18', 1000, 0, '2026-03-18 10:20:06'),
(82, 26, 'BATCH-2026-5531', '2026-12-18', '2025-12-18', 800, 0, '2026-03-18 10:20:06'),
(83, 26, 'BATCH-2026-1132', '2027-04-18', '2025-04-18', 1000, 0, '2026-03-18 10:20:06'),
(84, 27, 'BATCH-2026-9426', '2026-08-18', '2025-08-18', 240, 0, '2026-03-18 10:20:06'),
(85, 27, 'BATCH-2026-5986', '2026-08-18', '2025-08-18', 400, 0, '2026-03-18 10:20:06'),
(86, 27, 'BATCH-2026-6424', '2027-09-18', '2025-09-18', 400, 0, '2026-03-18 10:20:06'),
(87, 28, 'BATCH-2026-5519', '2028-11-18', '2025-11-18', 300, 0, '2026-03-18 10:20:06'),
(88, 28, 'BATCH-2026-3173', '2026-08-18', '2025-08-18', 450, 0, '2026-03-18 10:20:06'),
(89, 28, 'BATCH-2026-7235', '2027-06-18', '2025-06-18', 600, 0, '2026-03-18 10:20:06'),
(90, 28, 'BATCH-2026-3213', '2027-01-18', '2026-01-18', 450, 0, '2026-03-18 10:20:06'),
(91, 29, 'BATCH-2026-3415', '2026-08-18', '2025-08-18', 1500, 0, '2026-03-18 10:20:06'),
(92, 29, 'BATCH-2026-2403', '2028-07-18', '2025-07-18', 2500, 0, '2026-03-18 10:20:06'),
(93, 29, 'BATCH-2026-2287', '2026-12-18', '2025-12-18', 1500, 0, '2026-03-18 10:20:06'),
(94, 30, 'BATCH-2026-5800', '2028-09-18', '2025-09-18', 1200, 0, '2026-03-18 10:20:06'),
(95, 30, 'BATCH-2026-3159', '2026-06-18', '2025-06-18', 1200, 0, '2026-03-18 10:20:06'),
(96, 30, 'BATCH-2026-8907', '2027-01-18', '2026-01-18', 1500, 0, '2026-03-18 10:20:06'),
(97, 31, 'BATCH-2026-5619', '2026-10-18', '2025-10-18', 1000, 0, '2026-03-18 10:20:06'),
(98, 31, 'BATCH-2026-3535', '2026-10-18', '2025-10-18', 1000, 0, '2026-03-18 10:20:06'),
(99, 32, 'BATCH-2026-6103', '2026-06-18', '2025-06-18', 450, 0, '2026-03-18 10:20:06'),
(100, 32, 'BATCH-2026-8798', '2027-12-18', '2025-12-18', 450, 0, '2026-03-18 10:20:06'),
(101, 32, 'BATCH-2026-6984', '2027-07-18', '2025-07-18', 450, 0, '2026-03-18 10:20:06'),
(102, 32, 'BATCH-2026-4071', '2026-11-18', '2025-11-18', 450, 0, '2026-03-18 10:20:06'),
(103, 33, 'BATCH-2026-6061', '2028-03-18', '2025-03-18', 750, 0, '2026-03-18 10:20:06'),
(104, 33, 'BATCH-2026-6047', '2028-05-18', '2025-05-18', 750, 0, '2026-03-18 10:20:06'),
(105, 34, 'BATCH-2026-3265', '2026-11-18', '2025-11-18', 300, 0, '2026-03-18 10:20:06'),
(106, 34, 'BATCH-2026-2623', '2028-02-18', '2026-02-18', 300, 0, '2026-03-18 10:20:06'),
(107, 34, 'BATCH-2026-1995', '2027-09-18', '2025-09-18', 600, 0, '2026-03-18 10:20:06'),
(108, 34, 'BATCH-2026-3601', '2028-03-18', '2025-03-18', 300, 0, '2026-03-18 10:20:06'),
(109, 35, 'BATCH-2026-9765', '2026-11-18', '2025-11-18', 100, 0, '2026-03-18 10:20:06'),
(110, 35, 'BATCH-2026-5481', '2027-10-18', '2025-10-18', 150, 0, '2026-03-18 10:20:06'),
(111, 36, 'BATCH-2026-3686', '2026-04-18', '2025-04-18', 180, 0, '2026-03-18 10:20:06'),
(112, 36, 'BATCH-2026-5199', '2026-10-18', '2025-10-18', 120, 0, '2026-03-18 10:20:06'),
(113, 37, 'BATCH-2026-3558', '2027-06-18', '2025-06-18', 240, 0, '2026-03-18 10:20:06'),
(114, 37, 'BATCH-2026-3443', '2026-07-18', '2025-07-18', 160, 0, '2026-03-18 10:20:06'),
(115, 37, 'BATCH-2026-1395', '2026-04-18', '2025-04-18', 240, 0, '2026-03-18 10:20:06'),
(116, 38, 'BATCH-2026-8911', '2026-06-18', '2025-06-18', 500, 0, '2026-03-18 10:20:06'),
(117, 38, 'BATCH-2026-4033', '2026-07-18', '2025-07-18', 300, 0, '2026-03-18 10:20:06'),
(118, 38, 'BATCH-2026-1148', '2028-11-18', '2025-11-18', 500, 0, '2026-03-18 10:20:06'),
(119, 38, 'BATCH-2026-2089', '2026-03-18', '2025-03-18', 200, 0, '2026-03-18 10:20:06'),
(120, 27, '001', '2026-03-28', '2026-03-19', 3, 0, '2026-03-19 11:42:26'),
(121, 25, 'BRGY23-CARB500', '2025-06-30', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(122, 9, 'BRGY23-MEF500', '2025-06-30', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(123, 39, 'BRGY23-MET50', '2025-08-31', '2023-06-15', 5, 5, '2026-09-10 15:25:46'),
(124, 10, 'BRGY23-AML5', '2025-09-30', '2023-06-15', 5, 5, '2026-09-10 15:25:46'),
(125, 20, 'BRGY23-ASC500', '2025-07-31', '2023-06-15', 2, 2, '2026-09-10 15:25:46'),
(126, 40, 'BRGY23-DEHYD', '2025-12-31', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(127, 41, 'BRGY23-LAG300', '2025-05-31', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(128, 42, 'BRGY23-LAGSYR', '2025-06-30', '2023-06-15', 4, 4, '2026-09-10 15:25:46'),
(129, 43, 'BRGY23-CLON', '2025-10-31', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(130, 44, 'BRGY23-DICY10', '2025-11-30', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(131, 45, 'BRGY23-FER500', '2025-08-31', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(132, 46, 'BRGY23-ASCSYR', '2025-09-30', '2023-06-15', 10, 10, '2026-09-10 15:25:46'),
(133, 47, 'BRGY23-MULTSYR', '2025-08-31', '2023-06-15', 10, 10, '2026-09-10 15:25:46'),
(134, 48, 'BRGY23-PAR4F60', '2025-10-31', '2023-06-15', 4, 4, '2026-09-10 15:25:46'),
(135, 49, 'BRGY23-MET60', '2025-07-31', '2023-06-15', 4, 4, '2026-09-10 15:25:46'),
(136, 50, 'BRGY23-PAR4F15', '2025-11-30', '2023-06-15', 6, 6, '2026-09-10 15:25:46'),
(137, 51, 'BRGY23-LAG600', '2025-09-30', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(138, 52, 'BRGY23-RAP500', '2025-12-31', '2023-06-15', 2, 2, '2026-09-10 15:25:46'),
(139, 53, 'BRGY23-ANCOVB', '2025-10-31', '2023-06-15', 2, 2, '2026-09-10 15:25:46'),
(140, 54, 'BRGY23-CIN25', '2025-08-31', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(141, 55, 'BRGY23-MET10', '2025-06-30', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(142, 24, 'BRGY23-CET10', '2025-11-30', '2023-06-15', 1, 1, '2026-09-10 15:25:46'),
(143, 12, 'BRGY23-LOS50', '2025-12-31', '2023-06-15', 5, 5, '2026-09-10 15:25:46'),
(144, 56, 'BRGY23-ASCDRP', '2025-10-31', '2023-06-15', 10, 10, '2026-09-10 15:25:46'),
(145, 57, 'BRGY23-MULTDRP', '2025-09-30', '2023-06-15', 10, 10, '2026-09-10 15:25:46'),
(146, 58, 'BRGY23-CETDRP', '2025-12-31', '2023-06-15', 6, 6, '2026-09-10 15:25:46'),
(147, 59, 'BRGY23-DICSYR', '2025-07-31', '2023-06-15', 4, 4, '2026-09-10 15:25:46'),
(148, 60, 'BRGY23-CETSYR', '2025-11-30', '2023-06-15', 4, 4, '2026-09-10 15:25:46'),
(149, 12, 'TANGCUL24-LOS50', '2026-09-30', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(150, 10, 'TANGCUL24-AML5', '2026-09-30', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(151, 43, 'TANGCUL24-CLON', '2025-12-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(152, 54, 'TANGCUL24-CIN25', '2026-10-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(153, 3, 'TANGCUL24-CEF500', '2027-03-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(154, 9, 'TANGCUL24-MEF500', '2027-01-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(155, 61, 'TANGCUL24-MEF250', '2026-12-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(156, 62, 'TANGCUL24-LAGFORTE', '2026-04-30', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(157, 63, 'TANGCUL24-LAG600', '2026-01-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(158, 24, 'TANGCUL24-CET10', '2026-10-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(159, 6, 'TANGCUL24-PAR500', '2027-06-30', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(160, 21, 'TANGCUL24-MULTCAP', '2025-09-30', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(161, 20, 'TANGCUL24-ASC500', '2027-04-30', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(162, 64, 'TANGCUL24-IRONFOLIC', '2026-01-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(163, 65, 'TANGCUL24-VITB', '2025-11-30', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(164, 44, 'TANGCUL24-DICY10', '2026-12-31', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(165, 66, 'TANGCUL24-GAUZE', '2027-09-30', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(166, 67, 'TANGCUL24-POV30', '2026-06-30', '2024-03-15', 3, 3, '2026-09-10 15:25:46'),
(167, 68, 'TANGCUL24-AMXDRP', '2027-02-28', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(168, 56, 'TANGCUL24-ASCDRP', '2025-11-30', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(169, 58, 'TANGCUL24-CETDRP', '2025-12-31', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(170, 69, 'TANGCUL24-CARBDRP', '2025-11-30', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(171, 70, 'TANGCUL24-PARDRP', '2025-12-31', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(172, 71, 'TANGCUL24-MICROPORE', '2026-11-30', '2024-03-15', 1, 1, '2026-09-10 15:25:46'),
(173, 72, '001', '2025-03-31', '2024-03-15', 11, 10, '2026-09-10 15:25:46'),
(174, 60, 'TANGCUL24-CETSYR', '2027-09-30', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(175, 73, 'TANGCUL24-PARSYR', '2026-01-31', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(176, 74, 'TANGCUL24-LAGSYR', '2027-01-31', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(177, 75, 'TANGCUL24-MYREVC', '2025-12-31', '2024-03-15', 10, 10, '2026-09-10 15:25:46'),
(178, 76, 'TANGCUL24-MULTILEM', '2027-01-31', '2024-03-15', 4, 4, '2026-09-10 15:25:46'),
(179, 77, 'TANGCUL24-IRON600', '2027-01-31', '2024-03-15', 2, 2, '2026-09-10 15:25:46');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_transactions`
--

CREATE TABLE `medicine_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `medicine_id` bigint(20) UNSIGNED NOT NULL,
  `batch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_datetime` datetime NOT NULL,
  `transaction_type` enum('received','dispensed','adjustment','expired','returned') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `medicine_transactions`
--


-- --------------------------------------------------------

--
-- Table structure for table `ncd_records`
--

CREATE TABLE `ncd_records` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `ncd_code` varchar(40) NOT NULL,
  `registration_date` date NOT NULL,
  `diagnosis_type` enum('hypertension','diabetes','hypertension_diabetes','cardiovascular','asthma_copd','chronic_kidney','cancer','other') NOT NULL DEFAULT 'hypertension',
  `date_diagnosed` date NOT NULL,
  `philpen_risk_level` enum('low','moderate','high','very_high') NOT NULL DEFAULT 'low',
  `is_smoker` enum('never','current','former') NOT NULL DEFAULT 'never',
  `is_alcohol_drinker` enum('never','occasional','regular','heavy') NOT NULL DEFAULT 'never',
  `maintenance_meds` varchar(255) DEFAULT NULL,
  `target_bp` varchar(20) DEFAULT '<140/90',
  `target_fbs` varchar(20) DEFAULT '<126 mg/dL',
  `status` enum('active','controlled','uncontrolled','inactive','transferred','deceased') NOT NULL DEFAULT 'active',
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ncd_records`
--

INSERT INTO `ncd_records` (`id`, `patient_id`, `ncd_code`, `registration_date`, `diagnosis_type`, `date_diagnosed`, `philpen_risk_level`, `is_smoker`, `is_alcohol_drinker`, `maintenance_meds`, `target_bp`, `target_fbs`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 'NCD-2026-0001', '2025-03-17', 'hypertension', '2025-03-12', 'low', 'never', 'never', 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD', '<140/90', '<100 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(2, 2, 'NCD-2026-0002', '2024-11-17', 'diabetes', '2024-11-12', 'moderate', 'former', 'occasional', 'Metformin 500mg 1 tab BID after meals', '<130/80', '<126 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(3, 3, 'NCD-2026-0003', '2025-08-17', 'hypertension_diabetes', '2025-08-12', 'moderate', 'current', 'never', 'Losartan 50mg OD, Metformin 500mg BID, Gliclazide 80mg OD', '<130/80', '<126 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(4, 4, 'NCD-2026-0004', '2026-05-17', 'asthma_copd', '2026-05-12', 'high', 'never', 'regular', 'Salbutamol 100mcg MDI 2 puffs PRN, Montelukast 10mg OD', '<140/90', '<100 mg/dL', 'uncontrolled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(5, 5, 'NCD-2026-0005', '2025-03-17', 'cardiovascular', '2025-03-12', 'high', 'never', 'never', 'Aspirin 80mg OD, Atorvastatin 20mg OD, Carvedilol 6.25mg BID', '<130/80', '<100 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(6, 6, 'NCD-2026-0006', '2025-09-17', 'chronic_kidney', '2025-09-12', 'very_high', 'never', 'occasional', 'Losartan 25mg OD, Sodium Bicarbonate 650mg TID', '<130/80', '<100 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(7, 7, 'NCD-2026-0007', '2025-04-17', 'hypertension', '2025-04-12', 'low', 'former', 'never', 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD', '<140/90', '<100 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(8, 8, 'NCD-2026-0008', '2025-07-17', 'diabetes', '2025-07-12', 'moderate', 'current', 'regular', 'Metformin 500mg 1 tab BID after meals', '<130/80', '<126 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(9, 9, 'NCD-2026-0009', '2025-03-17', 'hypertension_diabetes', '2025-03-12', 'moderate', 'never', 'never', 'Losartan 50mg OD, Metformin 500mg BID, Gliclazide 80mg OD', '<130/80', '<126 mg/dL', 'uncontrolled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(10, 10, 'NCD-2026-0010', '2025-03-17', 'asthma_copd', '2025-03-12', 'high', 'never', 'occasional', 'Salbutamol 100mcg MDI 2 puffs PRN, Montelukast 10mg OD', '<140/90', '<100 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(11, 11, 'NCD-2026-0011', '2025-12-17', 'cardiovascular', '2025-12-12', 'high', 'never', 'never', 'Aspirin 80mg OD, Atorvastatin 20mg OD, Carvedilol 6.25mg BID', '<130/80', '<100 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(12, 12, 'NCD-2026-0012', '2025-04-17', 'chronic_kidney', '2025-04-12', 'very_high', 'former', 'regular', 'Losartan 25mg OD, Sodium Bicarbonate 650mg TID', '<130/80', '<100 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(13, 13, 'NCD-2026-0013', '2026-01-17', 'hypertension', '2026-01-12', 'low', 'current', 'never', 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD', '<140/90', '<100 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(14, 14, 'NCD-2026-0014', '2026-01-17', 'diabetes', '2026-01-12', 'moderate', 'never', 'occasional', 'Metformin 500mg 1 tab BID after meals', '<130/80', '<126 mg/dL', 'uncontrolled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(15, 15, 'NCD-2026-0015', '2024-12-17', 'hypertension_diabetes', '2024-12-12', 'moderate', 'never', 'never', 'Losartan 50mg OD, Metformin 500mg BID, Gliclazide 80mg OD', '<130/80', '<126 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(16, 16, 'NCD-2026-0016', '2025-05-17', 'asthma_copd', '2025-05-12', 'high', 'never', 'regular', 'Salbutamol 100mcg MDI 2 puffs PRN, Montelukast 10mg OD', '<140/90', '<100 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:43', NULL),
(17, 17, 'NCD-2026-0017', '2025-11-17', 'cardiovascular', '2025-11-12', 'high', 'former', 'never', 'Aspirin 80mg OD, Atorvastatin 20mg OD, Carvedilol 6.25mg BID', '<130/80', '<100 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(18, 18, 'NCD-2026-0018', '2025-09-17', 'chronic_kidney', '2025-09-12', 'very_high', 'current', 'occasional', 'Losartan 25mg OD, Sodium Bicarbonate 650mg TID', '<130/80', '<100 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(19, 19, 'NCD-2026-0019', '2025-01-17', 'hypertension', '2025-01-12', 'low', 'never', 'never', 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD', '<140/90', '<100 mg/dL', 'uncontrolled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(20, 20, 'NCD-2026-0020', '2025-09-17', 'diabetes', '2025-09-12', 'moderate', 'never', 'regular', 'Metformin 500mg 1 tab BID after meals', '<130/80', '<126 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(21, 21, 'NCD-2026-0021', '2025-11-17', 'hypertension_diabetes', '2025-11-12', 'moderate', 'never', 'never', 'Losartan 50mg OD, Metformin 500mg BID, Gliclazide 80mg OD', '<130/80', '<126 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(22, 22, 'NCD-2026-0022', '2026-01-17', 'asthma_copd', '2026-01-12', 'high', 'former', 'occasional', 'Salbutamol 100mcg MDI 2 puffs PRN, Montelukast 10mg OD', '<140/90', '<100 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(23, 23, 'NCD-2026-0023', '2026-03-17', 'cardiovascular', '2026-03-12', 'high', 'current', 'never', 'Aspirin 80mg OD, Atorvastatin 20mg OD, Carvedilol 6.25mg BID', '<130/80', '<100 mg/dL', 'controlled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(24, 24, 'NCD-2026-0024', '2026-03-17', 'chronic_kidney', '2026-03-12', 'very_high', 'never', 'regular', 'Losartan 25mg OD, Sodium Bicarbonate 650mg TID', '<130/80', '<100 mg/dL', 'uncontrolled', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL),
(25, 25, 'NCD-2026-0025', '2025-06-17', 'hypertension', '2025-06-12', 'low', 'never', 'never', 'Losartan 50mg 1 tab OD, Amlodipine 5mg 1 tab OD', '<140/90', '<100 mg/dL', 'active', 'PhilPEN screened. Patient registered under Barangay Health Station NCD Club.', '2026-09-12 18:51:44', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ncd_visits`
--

CREATE TABLE `ncd_visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ncd_record_id` bigint(20) UNSIGNED NOT NULL,
  `visit_date` date NOT NULL,
  `bp_systolic` int(10) UNSIGNED DEFAULT NULL,
  `bp_diastolic` int(10) UNSIGNED DEFAULT NULL,
  `blood_sugar_mgdl` decimal(6,2) DEFAULT NULL,
  `blood_sugar_type` enum('fbs','rbs','hba1c','none') DEFAULT 'none',
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `bmi` decimal(4,1) DEFAULT NULL,
  `waist_cm` decimal(5,2) DEFAULT NULL,
  `medications_dispensed` varchar(255) DEFAULT NULL,
  `quantity_dispensed` int(10) UNSIGNED DEFAULT 0,
  `treatment_adherence` enum('good','fair','poor') NOT NULL DEFAULT 'good',
  `findings_complaints` text DEFAULT NULL,
  `management_plan` text DEFAULT NULL,
  `next_appointment_date` date DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ncd_visits`
--

INSERT INTO `ncd_visits` (`id`, `ncd_record_id`, `visit_date`, `bp_systolic`, `bp_diastolic`, `blood_sugar_mgdl`, `blood_sugar_type`, `weight_kg`, `height_cm`, `bmi`, `waist_cm`, `medications_dispensed`, `quantity_dispensed`, `treatment_adherence`, `findings_complaints`, `management_plan`, `next_appointment_date`, `recorded_by`, `created_at`) VALUES
(1, 1, '2026-09-08', 152, 88, 111.00, 'rbs', 51.70, 165.00, 19.0, 90.50, 'Losartan 50mg #30, Amlodipine 5mg #30', 30, 'poor', 'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-08-31', 2, '2026-09-12 18:51:43'),
(2, 2, '2026-09-02', 112, 71, 117.00, 'fbs', 65.30, 165.00, 24.0, 95.70, 'Metformin 500mg #60', 30, 'good', 'Reports occasional morning dizziness and nape heaviness after skipping medication.', 'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.', '2026-09-30', 2, '2026-09-12 18:51:43'),
(3, 3, '2026-07-12', 136, 84, 122.00, 'fbs', 68.70, 165.00, 25.2, 96.60, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'good', 'Patient reports good compliance with diet and daily maintenance medications. Feels well.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-08-11', 2, '2026-09-12 18:51:43'),
(4, 3, '2026-08-10', 127, 71, 168.00, 'fbs', 60.10, 165.00, 22.1, 78.30, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'good', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-09-09', 2, '2026-09-12 18:51:43'),
(5, 3, '2026-09-03', 140, 98, 140.00, 'fbs', 76.00, 165.00, 27.9, 86.90, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'good', 'Monthly blood pressure monitoring. Advised low salt, low fat diet and 30 mins daily walking.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-10-04', 2, '2026-09-12 18:51:43'),
(6, 4, '2026-09-08', 122, 75, 93.00, 'rbs', 66.80, 174.00, 22.1, 80.50, 'Salbutamol MDI 1 cannister, Montelukast 10mg #30', 30, 'poor', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-09-19', 2, '2026-09-12 18:51:43'),
(7, 5, '2026-09-09', 118, 73, NULL, 'none', 80.80, 170.00, 28.0, 82.20, 'Aspirin 80mg #30, Atorvastatin 20mg #30', 30, 'fair', 'Monthly blood pressure monitoring. Advised low salt, low fat diet and 30 mins daily walking.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-10-10', 2, '2026-09-12 18:51:43'),
(8, 6, '2026-08-04', 111, 81, NULL, 'none', 78.80, 157.00, 32.0, 85.40, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'poor', 'Follow-up for asthma symptoms. Inhaler technique re-demonstrated. No nocturnal wheezing noted.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-09-03', 2, '2026-09-12 18:51:43'),
(9, 6, '2026-09-04', 135, 77, NULL, 'none', 83.60, 157.00, 33.9, 96.90, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'poor', 'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.', 'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.', '2026-09-02', 2, '2026-09-12 18:51:43'),
(10, 7, '2026-09-04', 143, 95, 101.00, 'rbs', 60.10, 175.00, 19.6, 93.50, 'Losartan 50mg #30, Amlodipine 5mg #30', 30, 'good', 'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.', 'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.', '2026-09-21', 2, '2026-09-12 18:51:43'),
(11, 8, '2026-09-05', 121, 72, 117.00, 'fbs', 58.60, 168.00, 20.8, 86.90, 'Metformin 500mg #60', 30, 'good', 'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-09-27', 2, '2026-09-12 18:51:43'),
(12, 9, '2026-07-04', 157, 90, 114.00, 'fbs', 62.20, 163.00, 23.4, 93.70, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'poor', 'Reports occasional morning dizziness and nape heaviness after skipping medication.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-08-03', 2, '2026-09-12 18:51:43'),
(13, 9, '2026-08-07', 134, 76, 192.00, 'fbs', 72.40, 163.00, 27.2, 75.80, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'poor', 'Patient reports good compliance with diet and daily maintenance medications. Feels well.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-09-06', 2, '2026-09-12 18:51:43'),
(14, 9, '2026-09-08', 132, 78, 110.00, 'fbs', 51.50, 163.00, 19.4, 92.00, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'poor', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-10-01', 2, '2026-09-12 18:51:43'),
(15, 10, '2026-08-03', 134, 78, 106.00, 'rbs', 60.50, 169.00, 21.2, 74.20, 'Salbutamol MDI 1 cannister, Montelukast 10mg #30', 30, 'good', 'Patient reports good compliance with diet and daily maintenance medications. Feels well.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-09-02', 2, '2026-09-12 18:51:43'),
(16, 10, '2026-09-08', 115, 84, 99.00, 'rbs', 55.50, 169.00, 19.4, 89.20, 'Salbutamol MDI 1 cannister, Montelukast 10mg #30', 30, 'good', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-10-10', 2, '2026-09-12 18:51:43'),
(17, 11, '2026-09-06', 132, 74, NULL, 'none', 84.50, 166.00, 30.7, 78.40, 'Aspirin 80mg #30, Atorvastatin 20mg #30', 30, 'poor', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-08-28', 2, '2026-09-12 18:51:43'),
(18, 12, '2026-07-05', 123, 70, NULL, 'none', 70.10, 153.00, 29.9, 75.80, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'good', 'Monthly blood pressure monitoring. Advised low salt, low fat diet and 30 mins daily walking.', 'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.', '2026-08-04', 2, '2026-09-12 18:51:43'),
(19, 12, '2026-08-03', 120, 74, NULL, 'none', 74.20, 153.00, 31.7, 90.00, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'good', 'Follow-up for asthma symptoms. Inhaler technique re-demonstrated. No nocturnal wheezing noted.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-09-02', 2, '2026-09-12 18:51:43'),
(20, 12, '2026-09-02', 128, 80, NULL, 'none', 58.70, 153.00, 25.1, 92.30, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'good', 'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-10-06', 2, '2026-09-12 18:51:43'),
(21, 13, '2026-07-11', 140, 96, 92.00, 'rbs', 81.10, 166.00, 29.4, 90.20, 'Losartan 50mg #30, Amlodipine 5mg #30', 30, 'fair', 'Follow-up for asthma symptoms. Inhaler technique re-demonstrated. No nocturnal wheezing noted.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-08-10', 2, '2026-09-12 18:51:43'),
(22, 13, '2026-08-11', 145, 94, 90.00, 'rbs', 67.60, 166.00, 24.5, 96.10, 'Losartan 50mg #30, Amlodipine 5mg #30', 30, 'fair', 'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-09-10', 2, '2026-09-12 18:51:43'),
(23, 13, '2026-09-09', 128, 72, 100.00, 'rbs', 67.90, 166.00, 24.6, 76.90, 'Losartan 50mg #30, Amlodipine 5mg #30', 30, 'fair', 'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-09-26', 2, '2026-09-12 18:51:43'),
(24, 14, '2026-08-06', 133, 84, 155.00, 'fbs', 51.30, 164.00, 19.1, 86.10, 'Metformin 500mg #60', 30, 'poor', 'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-09-05', 2, '2026-09-12 18:51:43'),
(25, 14, '2026-09-06', 111, 70, 195.00, 'fbs', 61.20, 164.00, 22.8, 75.10, 'Metformin 500mg #60', 30, 'poor', 'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-09-18', 2, '2026-09-12 18:51:43'),
(26, 15, '2026-08-05', 133, 78, 126.00, 'fbs', 64.60, 171.00, 22.1, 74.60, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'good', 'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-09-04', 2, '2026-09-12 18:51:43'),
(27, 15, '2026-09-03', 154, 98, 149.00, 'fbs', 54.40, 171.00, 18.6, 96.20, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'good', 'Reports occasional morning dizziness and nape heaviness after skipping medication.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-10-09', 2, '2026-09-12 18:51:43'),
(28, 16, '2026-09-09', 115, 70, 108.00, 'rbs', 72.80, 175.00, 23.8, 93.00, 'Salbutamol MDI 1 cannister, Montelukast 10mg #30', 30, 'poor', 'Reports occasional morning dizziness and nape heaviness after skipping medication.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-09-08', 2, '2026-09-12 18:51:44'),
(29, 17, '2026-08-05', 122, 71, NULL, 'none', 74.40, 166.00, 27.0, 79.60, 'Aspirin 80mg #30, Atorvastatin 20mg #30', 30, 'fair', 'Patient reports good compliance with diet and daily maintenance medications. Feels well.', 'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.', '2026-09-04', 2, '2026-09-12 18:51:44'),
(30, 17, '2026-09-05', 133, 76, NULL, 'none', 55.90, 166.00, 20.3, 89.60, 'Aspirin 80mg #30, Atorvastatin 20mg #30', 30, 'fair', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-10-10', 2, '2026-09-12 18:51:44'),
(31, 18, '2026-09-07', 114, 72, NULL, 'none', 58.60, 158.00, 23.5, 86.20, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'good', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-09-18', 2, '2026-09-12 18:51:44'),
(32, 19, '2026-09-07', 132, 82, 95.00, 'rbs', 82.30, 152.00, 35.6, 88.40, 'Losartan 50mg #30, Amlodipine 5mg #30', 30, 'poor', 'Monthly blood pressure monitoring. Advised low salt, low fat diet and 30 mins daily walking.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-09-18', 2, '2026-09-12 18:51:44'),
(33, 20, '2026-07-05', 131, 73, 139.00, 'fbs', 67.70, 166.00, 24.6, 73.90, 'Metformin 500mg #60', 30, 'good', 'Follow-up for asthma symptoms. Inhaler technique re-demonstrated. No nocturnal wheezing noted.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-08-04', 2, '2026-09-12 18:51:44'),
(34, 20, '2026-08-10', 121, 70, 112.00, 'fbs', 72.80, 166.00, 26.4, 84.20, 'Metformin 500mg #60', 30, 'good', 'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-09-09', 2, '2026-09-12 18:51:44'),
(35, 20, '2026-09-03', 111, 78, 149.00, 'fbs', 50.40, 166.00, 18.3, 75.20, 'Metformin 500mg #60', 30, 'good', 'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.', 'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.', '2026-09-26', 2, '2026-09-12 18:51:44'),
(36, 21, '2026-09-05', 142, 98, 144.00, 'fbs', 59.30, 156.00, 24.4, 94.30, 'Losartan 50mg #30, Metformin 500mg #60, Gliclazide 80mg #30', 30, 'poor', 'Follow-up after missed appointment last week. BP is elevated. Re-counseled on strict adherence.', 'Dispensed 30-day maintenance medications. Advised to continue low-sodium diet and return in 1 month.', '2026-08-29', 2, '2026-09-12 18:51:44'),
(37, 22, '2026-07-11', 118, 80, 94.00, 'rbs', 64.80, 159.00, 25.6, 87.90, 'Salbutamol MDI 1 cannister, Montelukast 10mg #30', 30, 'good', 'Routine BP checkup and maintenance medication refill. No current chest pain or shortness of breath.', 'Counselled on lifestyle modifications: reduced salt, avoid salty processed food, daily physical activity. Return in 30 days.', '2026-08-10', 2, '2026-09-12 18:51:44'),
(38, 22, '2026-08-07', 119, 71, 97.00, 'rbs', 76.20, 159.00, 30.1, 78.50, 'Salbutamol MDI 1 cannister, Montelukast 10mg #30', 30, 'good', 'Reports occasional morning dizziness and nape heaviness after skipping medication.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-09-06', 2, '2026-09-12 18:51:44'),
(39, 22, '2026-09-09', 128, 84, 102.00, 'rbs', 70.90, 159.00, 28.0, 92.50, 'Salbutamol MDI 1 cannister, Montelukast 10mg #30', 30, 'good', 'Patient reports good compliance with diet and daily maintenance medications. Feels well.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-10-10', 2, '2026-09-12 18:51:44'),
(40, 23, '2026-09-06', 111, 83, NULL, 'none', 61.20, 173.00, 20.4, 85.60, 'Aspirin 80mg #30, Atorvastatin 20mg #30', 30, 'good', 'Reports occasional morning dizziness and nape heaviness after skipping medication.', 'Medications refilled. FBS scheduled on next follow-up. Advised hydration and proper foot care.', '2026-10-06', 2, '2026-09-12 18:51:44'),
(41, 24, '2026-08-06', 128, 74, NULL, 'none', 74.10, 157.00, 30.1, 75.60, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'poor', 'Patient reports good compliance with diet and daily maintenance medications. Feels well.', 'Continued current maintenance regimen. Monitored adherence. Next clinic follow-up scheduled.', '2026-09-05', 2, '2026-09-12 18:51:44'),
(42, 24, '2026-09-09', 113, 71, NULL, 'none', 73.40, 157.00, 29.8, 92.20, 'Losartan 25mg #30, Sodium Bicarb #90', 30, 'poor', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-10-06', 2, '2026-09-12 18:51:44'),
(43, 25, '2026-09-07', 160, 92, 100.00, 'rbs', 61.40, 166.00, 22.3, 75.70, 'Losartan 50mg #30, Amlodipine 5mg #30', 30, 'fair', 'Fasting blood sugar monitoring. Complains of mild numbness on bilateral lower extremities.', 'Advised medication compliance without missing doses. Low purine, low sodium diet emphasized.', '2026-09-27', 2, '2026-09-12 18:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `household_id` bigint(20) UNSIGNED DEFAULT NULL,
  `philhealth_no` varchar(30) DEFAULT NULL,
  `national_id` varchar(30) DEFAULT NULL,
  `first_name` varchar(80) NOT NULL,
  `middle_name` varchar(80) DEFAULT NULL,
  `last_name` varchar(80) NOT NULL,
  `suffix` varchar(10) DEFAULT NULL,
  `sex` enum('male','female') NOT NULL,
  `birth_date` date NOT NULL,
  `blood_type` varchar(5) DEFAULT NULL,
  `contact_no` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `barangay` varchar(120) NOT NULL,
  `status` enum('active','inactive','deceased') NOT NULL DEFAULT 'active',
  `classification` enum('infant','under_five','school_age','adolescent','pregnant','postpartum','adult','senior','pwd','indigent_4ps') NOT NULL DEFAULT 'adult',
  `is_pwd` tinyint(1) NOT NULL DEFAULT 0,
  `is_4ps` tinyint(1) NOT NULL DEFAULT 0,
  `civil_status` enum('single','married','widowed','separated','child') NOT NULL DEFAULT 'single',
  `philhealth_category` enum('indigent_4ps','formal_economy','informal_economy','senior_citizen','lifetime_member','non_member') NOT NULL DEFAULT 'non_member',
  `emergency_contact_name` varchar(120) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`id`, `household_id`, `philhealth_no`, `national_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `sex`, `birth_date`, `blood_type`, `contact_no`, `email`, `address_line`, `barangay`, `status`, `classification`, `is_pwd`, `is_4ps`, `civil_status`, `philhealth_category`, `emergency_contact_name`, `emergency_contact_phone`, `created_at`, `updated_at`) VALUES
(1, NULL, '109788910898', NULL, 'Rodrigo', 'Hernandez', 'Santos', NULL, 'male', '1985-05-25', 'AB-', '09948983531', NULL, 'Osmena Ave. 228', 'Purok 1', 'active', 'adult', 0, 1, 'single', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(2, NULL, '804989543691', NULL, 'Maria', 'Castro', 'Fernandez', NULL, 'female', '1982-05-29', 'B-', NULL, NULL, 'Mabini St. 36', 'Purok 2', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(3, NULL, NULL, NULL, 'Isabel', 'Valdez', 'Santiago', 'Jr.', 'female', '1992-09-26', 'B+', '09431487124', NULL, 'Aguinaldo Ave. 427', 'Purok 1', 'active', 'adult', 0, 1, 'married', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(4, 66, '422583020067', NULL, 'Maria', 'Ramos', 'Flores', NULL, 'female', '1967-07-10', NULL, '09562576339', NULL, 'Magsaysay St. 413', 'Purok 3', 'active', 'adult', 1, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(5, NULL, '755121371306', NULL, 'Victoria', 'Ocampo', 'Dela Cruz', NULL, 'female', '1976-12-15', 'O+', '09255770291', NULL, 'Bonifacio Ave. 15', 'Purok 4', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(6, 70, NULL, NULL, 'Margarita', 'Castro', 'Torres', NULL, 'female', '1969-04-03', NULL, '09666061606', 'margarita.torres945@gmail.com', 'Del Pilar St. 436', 'Purok 1', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(7, 66, NULL, NULL, 'Sofia', 'Ramos', 'Rivera', NULL, 'female', '2004-03-02', 'A+', '09760833662', NULL, 'Del Pilar St. 171', 'Purok 2', 'active', 'adult', 0, 1, 'married', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(8, NULL, NULL, NULL, 'Dolores', 'Domingo', 'Soriano', 'III', 'female', '2011-01-29', NULL, '09624513418', 'dolores.soriano65@gmail.com', 'Bonifacio Ave. 224', 'Purok 5', 'active', 'adolescent', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(9, NULL, NULL, NULL, 'Dolores', 'Ramos', 'Reyes', NULL, 'female', '2002-05-25', 'B+', '09583811359', 'dolores.reyes614@gmail.com', 'Luna St. 232', 'Purok 5', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(10, 73, NULL, NULL, 'Jose', 'Mendoza', 'Francisco', NULL, 'male', '1997-04-23', 'O-', '09685714532', 'jose.francisco998@gmail.com', 'Aguinaldo Ave. 417', 'Purok 3', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(11, 69, NULL, NULL, 'Sofia', 'Hernandez', 'Soriano', 'II', 'female', '2012-06-29', 'AB-', '09631364586', NULL, 'Luna St. 487', 'Purok 6', 'active', 'adolescent', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(12, 79, '400111958910', NULL, 'Teresa', 'Ramos', 'Soriano', NULL, 'female', '1948-02-19', 'B-', NULL, 'teresa.soriano494@gmail.com', 'Mabini St. 159', 'Purok 1', 'active', 'senior', 0, 1, 'widowed', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(13, NULL, '789032444254', NULL, 'Ramon', 'Mercado', 'Gonzales', NULL, 'male', '1998-05-15', 'B+', NULL, 'ramon.gonzales102@gmail.com', 'Roxas St. 317', 'Purok 7', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(14, 80, '979672373266', NULL, 'Eduardo', 'Mercado', 'Martinez', NULL, 'male', '1953-02-10', NULL, NULL, NULL, 'Osmena Ave. 158', 'Purok 5', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(15, 71, '781456691829', NULL, 'Ana', 'Rivera', 'Aguilar', NULL, 'female', '1991-01-05', 'AB+', '09256327687', NULL, 'Luna St. 51', 'Purok 5', 'active', 'pregnant', 1, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(16, NULL, NULL, NULL, 'Luz', 'Santiago', 'Francisco', 'II', 'female', '2000-02-08', NULL, '09319469342', 'luz.francisco331@gmail.com', 'Roxas St. 136', 'Purok 7', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(17, 68, NULL, NULL, 'Elena', 'Castillo', 'Valdez', NULL, 'female', '1981-07-20', 'B+', NULL, NULL, 'Quezon Blvd. 91', 'Purok 3', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(18, 63, '365346760539', NULL, 'Cristina', 'Bautista', 'Castro', NULL, 'female', '1981-08-27', NULL, '09357136844', NULL, 'Osmena Ave. 31', 'Purok 4', 'active', 'pregnant', 0, 1, 'married', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(19, NULL, '210159131098', NULL, 'Francisco', 'Rivera', 'Santos', 'Sr.', 'male', '2021-06-20', 'O-', NULL, 'francisco.santos484@gmail.com', 'Quezon Blvd. 113', 'Purok 5', 'active', 'school_age', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(20, 63, NULL, NULL, 'Gloria', 'Valdez', 'Dela Cruz', 'III', 'female', '1987-05-01', NULL, '09552047826', 'gloria.delacruz36@gmail.com', 'Rizal St. 499', 'Purok 7', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(21, 74, NULL, NULL, 'Teresa', 'Castillo', 'Mendoza', 'Jr.', 'female', '1963-10-03', 'A-', '09867886196', NULL, 'Quezon Blvd. 406', 'Purok 1', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(22, 81, NULL, NULL, 'Pedro', 'Hernandez', 'Torres', NULL, 'male', '2021-07-30', 'AB-', NULL, NULL, 'Del Pilar St. 394', 'Purok 3', 'active', 'school_age', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(23, 69, NULL, NULL, 'Victoria', 'Bautista', 'Aquino', NULL, 'female', '2018-08-14', 'O-', '09492375896', NULL, 'Magsaysay St. 267', 'Purok 1', 'active', 'school_age', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(24, 77, '913702217651', NULL, 'Rafael', 'Santos', 'Castro', 'III', 'male', '1974-04-25', 'A+', NULL, NULL, 'Magsaysay St. 50', 'Purok 3', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(25, NULL, NULL, NULL, 'Fernando', 'Santos', 'Navarro', NULL, 'male', '2007-11-21', 'O+', NULL, NULL, 'Roxas St. 464', 'Purok 3', 'active', 'adolescent', 0, 1, 'single', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(26, NULL, '357564473003', NULL, 'Cristina', 'Bautista', 'Soriano', NULL, 'female', '1995-12-29', 'O+', NULL, 'cristina.soriano209@gmail.com', 'Bonifacio Ave. 144', 'Purok 6', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(27, 81, NULL, NULL, 'Teresa', 'Bautista', 'Valdez', NULL, 'female', '1984-10-27', NULL, NULL, 'teresa.valdez857@gmail.com', 'Del Pilar St. 149', 'Purok 2', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(28, NULL, '875635460155', NULL, 'Gabriel', 'Ramos', 'Ramos', NULL, 'male', '1980-05-28', NULL, NULL, NULL, 'Magsaysay St. 4', 'Purok 4', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(29, 78, '943846174021', NULL, 'Juan', 'Bautista', 'Ramos', NULL, 'male', '2012-07-28', 'A+', '09233358751', NULL, 'Roxas St. 448', 'Purok 3', 'active', 'adolescent', 1, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(30, NULL, '964723062444', NULL, 'Mario', 'Castillo', 'Villanueva', NULL, 'male', '1970-07-04', 'AB+', NULL, NULL, 'Luna St. 397', 'Purok 3', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(31, 72, '228115814724', NULL, 'Rosa', 'Flores', 'Santiago', 'II', 'female', '2023-04-21', 'O-', '09817860046', NULL, 'Aguinaldo Ave. 424', 'Purok 4', 'active', 'under_five', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(32, NULL, NULL, NULL, 'Mario', 'Santos', 'Santos', 'Jr.', 'male', '1979-01-18', 'O+', NULL, NULL, 'Bonifacio Ave. 199', 'Purok 4', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(33, 71, '343634326002', NULL, 'Luz', 'Castro', 'Villanueva', NULL, 'female', '1959-08-23', 'B-', '09147878195', NULL, 'Aguinaldo Ave. 420', 'Purok 2', 'active', 'senior', 0, 1, 'married', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(34, NULL, NULL, NULL, 'Beatriz', 'Rivera', 'Castro', NULL, 'female', '1988-07-28', 'A+', '09865667985', NULL, 'Rizal St. 245', 'Purok 1', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(35, NULL, NULL, NULL, 'Miguel', 'Castro', 'Pascual', 'III', 'male', '2024-11-01', NULL, NULL, 'miguel.pascual902@gmail.com', 'Quezon Blvd. 376', 'Purok 1', 'active', 'under_five', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(36, 70, NULL, NULL, 'Ricardo', 'Reyes', 'Valdez', NULL, 'male', '1970-03-11', 'B-', '09947050678', NULL, 'Aguinaldo Ave. 252', 'Purok 5', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(37, NULL, NULL, NULL, 'Daniel', 'Santos', 'Rivera', NULL, 'male', '1982-04-27', 'O-', NULL, 'daniel.rivera714@gmail.com', 'Luna St. 455', 'Purok 6', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(38, NULL, '896604079503', NULL, 'Gabriel', 'Flores', 'Santos', NULL, 'male', '1980-07-01', 'AB-', '09508612282', NULL, 'Luna St. 475', 'Purok 1', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(39, 73, '796988651638', NULL, 'Josefa', 'Hernandez', 'Lopez', NULL, 'female', '1991-02-04', 'A+', NULL, NULL, 'Magsaysay St. 195', 'Purok 3', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(40, NULL, '406608195671', NULL, 'Daniel', 'Valdez', 'Bautista', NULL, 'male', '2024-07-18', 'B+', '09946452850', NULL, 'Del Pilar St. 348', 'Purok 3', 'active', 'under_five', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(41, 74, NULL, NULL, 'Mario', 'Hernandez', 'Ramos', NULL, 'male', '2009-07-30', 'A+', '09413752150', 'mario.ramos147@gmail.com', 'Rizal St. 158', 'Purok 2', 'active', 'adolescent', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(42, 69, '181826669183', NULL, 'Teresa', 'Reyes', 'Mercado', 'III', 'female', '1976-10-01', 'AB-', NULL, 'teresa.mercado541@gmail.com', 'Del Pilar St. 437', 'Purok 1', 'active', 'adult', 0, 1, 'single', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(43, 79, NULL, NULL, 'Carmen', 'Cruz', 'Ramos', NULL, 'female', '2022-07-05', 'AB-', '09334906659', NULL, 'Magsaysay St. 300', 'Purok 2', 'active', 'under_five', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(44, 65, '326767686074', NULL, 'Angela', 'Valdez', 'Villanueva', 'III', 'female', '1982-04-15', 'A+', '09225177536', 'angela.villanueva475@gmail.com', 'Magsaysay St. 494', 'Purok 4', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(45, 80, '190075975778', NULL, 'Beatriz', 'Morales', 'Soriano', 'Sr.', 'female', '1964-07-08', 'A+', '09845957576', NULL, 'Bonifacio Ave. 409', 'Purok 2', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(46, NULL, NULL, NULL, 'Josefa', 'Pascual', 'Rivera', 'III', 'female', '1984-05-26', 'A+', '09843994339', NULL, 'Del Pilar St. 63', 'Purok 2', 'active', 'pregnant', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(47, 64, NULL, NULL, 'Catalina', 'Castillo', 'Valdez', NULL, 'female', '1986-10-13', 'A+', '09931926964', NULL, 'Mabini St. 432', 'Purok 3', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(48, 67, NULL, NULL, 'Isabel', 'Hernandez', 'Aguilar', 'II', 'female', '1982-10-22', 'O-', NULL, 'isabel.aguilar92@gmail.com', 'Roxas St. 172', 'Purok 4', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(49, 77, '978511075304', NULL, 'Isabel', 'Torres', 'Valdez', 'Sr.', 'female', '1992-09-12', NULL, NULL, NULL, 'Luna St. 117', 'Purok 3', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(50, 77, NULL, NULL, 'Fernando', 'Castillo', 'Castro', NULL, 'male', '1967-09-07', 'AB+', '09268567369', NULL, 'Bonifacio Ave. 20', 'Purok 1', 'deceased', 'adult', 0, 1, 'single', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(51, 70, '869827407923', NULL, 'Patricia', 'Reyes', 'Santos', 'Jr.', 'female', '1995-06-30', 'AB-', '09296891239', 'patricia.santos50@gmail.com', 'Mabini St. 153', 'Purok 5', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(52, 64, '704797134486', NULL, 'Roberto', 'Bautista', 'Santos', NULL, 'male', '1988-03-14', 'AB-', NULL, 'roberto.santos232@gmail.com', 'Magsaysay St. 373', 'Purok 3', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(53, 72, '136043760282', NULL, 'Luis', 'Flores', 'Aguilar', 'III', 'male', '1968-04-04', 'O+', '09899924527', NULL, 'Osmena Ave. 372', 'Purok 5', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(54, 66, '656758504216', NULL, 'Fernando', 'Cruz', 'Pascual', NULL, 'male', '2019-10-06', 'AB-', '09966941390', NULL, 'Bonifacio Ave. 446', 'Purok 6', 'active', 'school_age', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(55, 67, NULL, NULL, 'Pedro', 'Ramos', 'Santiago', NULL, 'male', '2008-03-14', NULL, '09983527073', NULL, 'Rizal St. 294', 'Purok 3', 'active', 'adolescent', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(56, 66, '772526426898', NULL, 'Mario', 'Rivera', 'Aquino', 'Jr.', 'male', '1992-04-01', 'AB-', '09681819839', NULL, 'Luna St. 108', 'Purok 3', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(57, NULL, NULL, NULL, 'Beatriz', 'Aquino', 'Rivera', NULL, 'female', '1996-01-21', 'O-', '09755294578', NULL, 'Aguinaldo Ave. 402', 'Purok 1', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(58, 67, '595428172680', NULL, 'Daniel', 'Torres', 'Aquino', NULL, 'male', '1967-09-30', 'B+', NULL, 'daniel.aquino213@gmail.com', 'Rizal St. 198', 'Purok 6', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(59, 66, NULL, NULL, 'Luz', 'Mendoza', 'Dela Cruz', 'Jr.', 'female', '1963-05-20', 'A+', '09682635234', NULL, 'Osmena Ave. 112', 'Purok 3', 'active', 'senior', 0, 0, 'widowed', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(60, NULL, NULL, NULL, 'Antonio', 'Hernandez', 'Dela Cruz', 'Jr.', 'male', '1997-05-14', 'B+', '09288699711', NULL, 'Mabini St. 354', 'Purok 4', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(61, NULL, NULL, NULL, 'Juan', 'Valdez', 'Flores', 'II', 'male', '1984-12-17', 'A-', NULL, NULL, 'Luna St. 471', 'Purok 1', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(62, 80, '414551075956', NULL, 'Gloria', 'Santiago', 'Lopez', NULL, 'female', '1960-04-13', NULL, '09549050980', NULL, 'Magsaysay St. 353', 'Purok 7', 'active', 'senior', 1, 0, 'widowed', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(63, 71, NULL, NULL, 'Antonio', 'Castillo', 'Soriano', NULL, 'male', '1983-08-14', 'B+', '09476775498', NULL, 'Bonifacio Ave. 55', 'Purok 2', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(64, NULL, '880982817477', NULL, 'Dolores', 'Reyes', 'Rivera', 'II', 'female', '1962-05-21', 'B+', '09911573532', 'dolores.rivera868@gmail.com', 'Magsaysay St. 23', 'Purok 3', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(65, 70, '900659456182', NULL, 'Margarita', 'Reyes', 'Aquino', 'Sr.', 'female', '2017-05-21', 'A-', '09298643730', 'margarita.aquino669@gmail.com', 'Rizal St. 187', 'Purok 2', 'active', 'school_age', 0, 1, 'child', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(66, NULL, '198213081664', NULL, 'Esperanza', 'Mendoza', 'Fernandez', 'Jr.', 'female', '2016-11-24', 'O-', NULL, NULL, 'Bonifacio Ave. 295', 'Purok 1', 'active', 'school_age', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(67, 76, NULL, NULL, 'Margarita', 'Hernandez', 'Soriano', 'III', 'female', '2000-10-16', NULL, '09586996823', NULL, 'Magsaysay St. 216', 'Purok 4', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(68, 78, '974480866811', NULL, 'Patricia', 'Hernandez', 'Rivera', 'II', 'female', '1972-12-26', 'AB+', NULL, NULL, 'Luna St. 454', 'Purok 1', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(69, NULL, NULL, NULL, 'Rafael', 'Mercado', 'Reyes', 'II', 'male', '2014-02-05', 'B-', '09879204367', NULL, 'Bonifacio Ave. 212', 'Purok 2', 'active', 'adolescent', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(70, 71, '983836641380', NULL, 'Miguel', 'Santos', 'Soriano', NULL, 'male', '1984-10-11', 'AB+', '09236391396', NULL, 'Del Pilar St. 53', 'Purok 2', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(71, NULL, NULL, NULL, 'Juan', 'Castillo', 'Rivera', NULL, 'male', '2017-04-21', NULL, '09496375990', NULL, 'Luna St. 391', 'Purok 1', 'active', 'school_age', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(72, NULL, '319901980847', NULL, 'Catalina', 'Reyes', 'Castro', 'II', 'female', '1996-09-02', NULL, '09868690958', NULL, 'Mabini St. 436', 'Purok 2', 'inactive', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(73, NULL, NULL, NULL, 'Daniel', 'Castro', 'Villanueva', 'III', 'male', '2025-11-09', 'O+', '09904598238', NULL, 'Roxas St. 4', 'Purok 6', 'active', 'infant', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(74, 82, NULL, NULL, 'Victoria', 'Torres', 'Gonzales', 'III', 'female', '1974-01-25', 'O-', '09723968439', NULL, 'Aguinaldo Ave. 213', 'Purok 3', 'inactive', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(75, 76, NULL, NULL, 'Cristina', 'Mercado', 'Villanueva', NULL, 'female', '1972-02-15', 'AB+', NULL, NULL, 'Quezon Blvd. 308', 'Purok 1', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(76, NULL, NULL, NULL, 'Carlos', 'Castillo', 'Pascual', NULL, 'male', '2011-06-06', 'B+', '09363527394', NULL, 'Roxas St. 28', 'Purok 3', 'active', 'adolescent', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(77, NULL, NULL, NULL, 'Dolores', 'Santos', 'Mendoza', 'Jr.', 'female', '1964-06-17', 'O+', NULL, 'dolores.mendoza809@gmail.com', 'Del Pilar St. 296', 'Purok 6', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(78, 72, NULL, NULL, 'Mario', 'Santos', 'Mercado', NULL, 'male', '1956-07-12', 'AB-', '09476734083', NULL, 'Rizal St. 35', 'Purok 1', 'active', 'senior', 0, 1, 'married', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(79, NULL, '321639094377', NULL, 'Manuel', 'Hernandez', 'Lopez', 'III', 'male', '1962-11-12', 'B+', NULL, 'manuel.lopez167@gmail.com', 'Luna St. 158', 'Purok 2', 'active', 'senior', 0, 0, 'widowed', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(80, 70, '387610923624', NULL, 'Francisco', 'Reyes', 'Aguilar', 'Jr.', 'male', '2001-11-19', 'A+', '09609649533', NULL, 'Luna St. 175', 'Purok 7', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(81, 71, '453692868102', NULL, 'Manuel', 'Reyes', 'Aquino', NULL, 'male', '1966-06-02', NULL, '09975775263', 'manuel.aquino13@gmail.com', 'Osmena Ave. 33', 'Purok 6', 'active', 'senior', 1, 0, 'widowed', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(82, 69, '279163189895', NULL, 'Juan', 'Aquino', 'Aguilar', NULL, 'male', '2022-05-04', 'B-', '09731009607', NULL, 'Luna St. 347', 'Purok 6', 'active', 'under_five', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(83, NULL, NULL, NULL, 'Angela', 'Pascual', 'Castro', NULL, 'female', '1998-10-25', 'B-', '09448629934', NULL, 'Rizal St. 102', 'Purok 5', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(84, NULL, '766891922789', NULL, 'Victoria', 'Torres', 'Garcia', NULL, 'female', '1970-05-13', 'B+', '09711045528', 'victoria.garcia414@gmail.com', 'Del Pilar St. 99', 'Purok 5', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(85, NULL, NULL, NULL, 'Victoria', 'Reyes', 'Valdez', NULL, 'female', '2002-07-16', 'B+', '09710767389', 'victoria.valdez717@gmail.com', 'Del Pilar St. 417', 'Purok 6', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(86, NULL, NULL, NULL, 'Dolores', 'Reyes', 'Lopez', NULL, 'female', '2017-10-08', 'A+', '09862459234', NULL, 'Magsaysay St. 285', 'Purok 5', 'active', 'school_age', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(87, 63, NULL, NULL, 'Roberto', 'Ramos', 'Francisco', NULL, 'male', '2008-01-21', 'B-', NULL, 'roberto.francisco733@gmail.com', 'Quezon Blvd. 144', 'Purok 6', 'active', 'adolescent', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(88, 73, NULL, NULL, 'Rosa', 'Castro', 'Mendoza', 'II', 'female', '2004-07-21', 'A-', '09953233690', 'rosa.mendoza693@gmail.com', 'Quezon Blvd. 448', 'Purok 2', 'active', 'adult', 0, 1, 'married', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(89, NULL, '813976850821', NULL, 'Luis', 'Cruz', 'Rivera', 'Jr.', 'male', '1967-04-10', 'B+', '09105907963', 'luis.rivera5@gmail.com', 'Mabini St. 442', 'Purok 7', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(90, NULL, '524060275768', NULL, 'Luis', 'Flores', 'Francisco', NULL, 'male', '1983-07-17', 'AB-', '09551863519', NULL, 'Del Pilar St. 411', 'Purok 3', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(91, 79, '889911128221', NULL, 'Gabriel', 'Mercado', 'Francisco', NULL, 'male', '1957-10-28', 'O-', NULL, NULL, 'Del Pilar St. 497', 'Purok 1', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(92, NULL, '637973244517', NULL, 'Ricardo', 'Flores', 'Navarro', 'Sr.', 'male', '2006-07-05', 'A+', '09474209847', 'ricardo.navarro906@gmail.com', 'Quezon Blvd. 175', 'Purok 5', 'active', 'adult', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(93, 78, NULL, NULL, 'Luis', 'Castro', 'Torres', NULL, 'male', '1973-04-05', 'B+', '09696158559', NULL, 'Rizal St. 365', 'Purok 6', 'inactive', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(94, 75, '391751487836', NULL, 'Antonio', 'Castro', 'Francisco', NULL, 'male', '1971-08-07', NULL, '09326631141', NULL, 'Bonifacio Ave. 412', 'Purok 1', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(95, 66, NULL, NULL, 'Rosa', 'Ramos', 'Castro', 'Sr.', 'female', '2000-04-30', 'O+', '09180632973', 'rosa.castro399@gmail.com', 'Bonifacio Ave. 178', 'Purok 3', 'active', 'adult', 0, 1, 'married', 'indigent_4ps', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(96, NULL, '517325963354', NULL, 'Cristina', 'Castro', 'Aquino', NULL, 'female', '1975-06-17', 'AB-', NULL, 'cristina.aquino363@gmail.com', 'Rizal St. 309', 'Purok 3', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(97, 68, '162714767816', NULL, 'Carmen', 'Santiago', 'Flores', 'II', 'female', '2024-11-17', 'B+', '09836900291', NULL, 'Osmena Ave. 401', 'Purok 2', 'active', 'under_five', 0, 0, 'child', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(98, NULL, '892872645324', NULL, 'Rafael', 'Rivera', 'Torres', NULL, 'male', '1965-06-29', 'AB+', '09797005123', NULL, 'Quezon Blvd. 64', 'Purok 2', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(99, 82, '795859974661', NULL, 'Juan', 'Santos', 'Valdez', NULL, 'male', '1956-01-13', 'AB+', '09813183954', NULL, 'Quezon Blvd. 97', 'Purok 3', 'active', 'senior', 0, 0, 'married', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-30 19:42:44'),
(100, NULL, NULL, NULL, 'Carmen', 'Cruz', 'Valdez', NULL, 'female', '1970-11-19', 'AB-', '09175004084', NULL, 'Luna St. 140', 'Purok 6', 'active', 'adult', 0, 0, 'single', 'non_member', NULL, NULL, '2026-03-18 10:20:02', '2026-09-16 21:37:29'),
(107, NULL, NULL, NULL, 'Baby', 'Papa', 'Ama', NULL, 'male', '2026-09-10', NULL, NULL, NULL, 'dasdasd', 'Purok 4', 'active', 'infant', 0, 0, 'child', 'non_member', NULL, NULL, '2026-09-10 21:46:57', '2026-09-30 19:42:44');

-- --------------------------------------------------------

--
-- Table structure for table `patient_allergies`
--

CREATE TABLE `patient_allergies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `allergen` varchar(120) NOT NULL,
  `reaction` varchar(255) DEFAULT NULL,
  `noted_on` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient_allergies`
--

INSERT INTO `patient_allergies` (`id`, `patient_id`, `allergen`, `reaction`, `noted_on`, `created_at`) VALUES
(1, 99, 'Soy', 'Mild to moderate reaction', '2025-04-25', '2026-03-18 10:20:02'),
(2, 98, 'Pollen', 'Mild to moderate reaction', '2025-05-12', '2026-03-18 10:20:02'),
(3, 97, 'Soy', 'Mild to moderate reaction', '2025-09-22', '2026-03-18 10:20:02'),
(4, 90, 'Aspirin', 'Mild to moderate reaction', '2025-08-28', '2026-03-18 10:20:02'),
(5, 87, 'Shellfish', 'Mild to moderate reaction', '2025-12-29', '2026-03-18 10:20:02'),
(6, 80, 'Peanuts', 'Mild to moderate reaction', '2025-06-03', '2026-03-18 10:20:02'),
(7, 67, 'Wheat', 'Mild to moderate reaction', '2026-01-11', '2026-03-18 10:20:02'),
(8, 65, 'Wheat', 'Mild to moderate reaction', '2025-09-22', '2026-03-18 10:20:02'),
(9, 59, 'Wheat', 'Mild to moderate reaction', '2025-08-22', '2026-03-18 10:20:02'),
(10, 55, 'Pollen', 'Mild to moderate reaction', '2025-10-18', '2026-03-18 10:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `patient_appointments`
--

CREATE TABLE `patient_appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appointment_code` varchar(40) NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `service_id` int(10) UNSIGNED NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL DEFAULT '08:30:00',
  `status` enum('scheduled','rescheduled','completed','cancelled','missed') NOT NULL DEFAULT 'scheduled',
  `reason` varchar(255) DEFAULT NULL,
  `reschedule_reason` varchar(255) DEFAULT NULL,
  `previous_appointment_date` date DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `clinical_notes` text DEFAULT NULL,
  `assigned_personnel` varchar(120) DEFAULT 'Barangay Health Worker',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient_appointments`
--

INSERT INTO `patient_appointments` (`id`, `appointment_code`, `patient_id`, `service_id`, `appointment_date`, `appointment_time`, `status`, `reason`, `reschedule_reason`, `previous_appointment_date`, `cancellation_reason`, `completed_at`, `clinical_notes`, `assigned_personnel`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'APT-2026-001', 1, 6, '2026-09-30', '08:30:00', 'scheduled', 'Quarterly Hypertension Blood Pressure check and Maintenance Amlodipine refill', NULL, NULL, NULL, NULL, NULL, 'BHW Elena Bautista', 2, '2026-09-30 19:43:06', NULL),
(3, 'APT-2026-002', 2, 4, '2026-09-30', '09:15:00', 'scheduled', 'DMPA Injectable 3-month cycle resupply and vitals assessment', NULL, NULL, NULL, NULL, NULL, 'Midwife Corazon Rivera, RM', 2, '2026-09-30 19:43:06', NULL),
(4, 'APT-2026-003', 18, 2, '2026-09-30', '10:00:00', 'scheduled', 'Third trimester prenatal follow-up, fundic height check, and iron supplement release', NULL, NULL, NULL, NULL, NULL, 'Midwife Corazon Rivera, RM', 2, '2026-09-30 19:43:06', NULL),
(5, 'APT-2026-004', 7, 1, '2026-10-01', '08:45:00', 'scheduled', 'Pentavalent 3rd dose + OPV routine immunization for baby', NULL, NULL, NULL, NULL, NULL, 'Nurse Maricar Ramos, RN', 2, '2026-09-30 19:43:06', NULL),
(6, 'APT-2026-005', 15, 10, '2026-10-02', '09:00:00', 'scheduled', 'Annual Senior citizen preventive health evaluation and joint pain consultation', NULL, NULL, NULL, NULL, NULL, 'Nurse Maricar Ramos, RN', 2, '2026-09-30 19:43:06', NULL),
(7, 'APT-2026-006', 12, 5, '2026-10-03', '10:30:00', 'scheduled', 'Under-5 growth monitoring, weight check, and Vitamin A capsule administration', NULL, NULL, NULL, NULL, NULL, 'BHW Rosanna Santos', 2, '2026-09-30 19:43:06', NULL),
(8, 'APT-2026-007', 3, 7, '2026-10-04', '11:00:00', 'scheduled', 'Follow-up for seasonal allergic rhinitis and dry cough', NULL, NULL, NULL, NULL, NULL, 'BHW Elena Bautista', 2, '2026-09-30 19:43:06', NULL),
(9, 'APT-2026-008', 4, 6, '2026-10-05', '09:30:00', 'rescheduled', 'Fasting Blood Sugar monitoring and Metformin prescription review', 'Patient had agricultural harvest commitment on initial date', '2026-09-28', NULL, NULL, NULL, 'BHW Elena Bautista', 2, '2026-09-30 19:43:06', NULL),
(10, 'APT-2026-009', 5, 9, '2026-09-29', '14:00:00', 'completed', 'Post-wound dressing change and healing evaluation on left forearm', NULL, NULL, NULL, '2026-09-29 14:35:00', 'Wound clean with healthy granulation tissue. No signs of infection. Betadine dressing reapplied. Advised to keep dry.', 'Nurse Maricar Ramos, RN', 2, '2026-09-30 19:43:06', NULL),
(11, 'APT-2026-010', 6, 8, '2026-09-28', '10:00:00', 'completed', 'Presumptive TB screening after 2-week productive cough', NULL, NULL, NULL, '2026-09-28 10:45:00', 'Collected 2 sputum samples for GeneXpert referral to City Health Office. Advised on mask wearing and respiratory hygiene.', 'Nurse Maricar Ramos, RN', 2, '2026-09-30 19:43:06', NULL),
(12, 'APT-2026-011', 8, 7, '2026-09-27', '13:30:00', 'cancelled', 'Routine physical assessment for barangay clearance', NULL, NULL, 'Patient relocated to municipality center and will secure clearance there', NULL, NULL, 'BHW Rosanna Santos', 2, '2026-09-30 19:43:06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `patient_conditions`
--

CREATE TABLE `patient_conditions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `condition_name` varchar(120) NOT NULL,
  `status` enum('active','resolved','chronic') NOT NULL DEFAULT 'active',
  `diagnosed_on` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `patient_conditions`
--

INSERT INTO `patient_conditions` (`id`, `patient_id`, `condition_name`, `status`, `diagnosed_on`, `notes`, `created_at`) VALUES
(1, 100, 'Arthritis', 'chronic', '2025-08-04', 'Under monitoring', '2026-03-18 10:20:02'),
(2, 97, 'Migraine', 'chronic', '2023-07-22', 'Under monitoring', '2026-03-18 10:20:02'),
(3, 95, 'Hypothyroidism', 'chronic', '2022-02-25', 'Under monitoring', '2026-03-18 10:20:02'),
(4, 94, 'Diabetes Type 2', 'active', '2022-02-07', 'Under monitoring', '2026-03-18 10:20:02'),
(5, 92, 'Arthritis', 'chronic', '2025-03-25', 'Under monitoring', '2026-03-18 10:20:02'),
(6, 91, 'Migraine', 'active', '2025-01-05', 'Under monitoring', '2026-03-18 10:20:02'),
(7, 90, 'Diabetes Type 2', 'chronic', '2025-06-13', 'Under monitoring', '2026-03-18 10:20:02'),
(8, 85, 'Arthritis', 'chronic', '2024-05-23', 'Under monitoring', '2026-03-18 10:20:02'),
(9, 83, 'Arthritis', 'chronic', '2024-08-02', 'Under monitoring', '2026-03-18 10:20:02'),
(10, 81, 'Hypertension', 'active', '2025-04-02', 'Under monitoring', '2026-03-18 10:20:02'),
(11, 78, 'Asthma', 'active', '2024-11-09', 'Under monitoring', '2026-03-18 10:20:02'),
(12, 77, 'Hypertension', 'chronic', '2025-04-18', 'Under monitoring', '2026-03-18 10:20:02'),
(13, 73, 'Allergic Rhinitis', 'chronic', '2025-06-22', 'Under monitoring', '2026-03-18 10:20:02'),
(14, 71, 'Arthritis', 'active', '2023-12-13', 'Under monitoring', '2026-03-18 10:20:02'),
(15, 66, 'Arthritis', 'chronic', '2021-08-18', 'Under monitoring', '2026-03-18 10:20:02'),
(16, 61, 'Arthritis', 'active', '2022-12-31', 'Under monitoring', '2026-03-18 10:20:02'),
(17, 59, 'Arthritis', 'chronic', '2021-12-19', 'Under monitoring', '2026-03-18 10:20:02'),
(18, 57, 'Migraine', 'chronic', '2022-08-08', 'Under monitoring', '2026-03-18 10:20:02'),
(19, 53, 'Hypertension', 'active', '2022-06-22', 'Under monitoring', '2026-03-18 10:20:02'),
(20, 52, 'Hypertension', 'active', '2023-12-24', 'Under monitoring', '2026-03-18 10:20:02'),
(21, 49, 'Diabetes Type 2', 'active', '2025-12-08', 'Under monitoring', '2026-03-18 10:20:02');

-- --------------------------------------------------------

--
-- Table structure for table `postnatal_visits`
--

CREATE TABLE `postnatal_visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pregnancy_id` bigint(20) UNSIGNED NOT NULL,
  `visit_datetime` datetime NOT NULL,
  `mother_condition` varchar(255) DEFAULT NULL,
  `baby_condition` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `postnatal_visits`
--

INSERT INTO `postnatal_visits` (`id`, `pregnancy_id`, `visit_datetime`, `mother_condition`, `baby_condition`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 1, '2025-09-16 00:00:00', 'Wound healing normally', 'Weight gain appropriate', 'Routine postnatal checkup', 2, '2026-03-18 10:20:05'),
(2, 1, '2025-11-03 00:00:00', 'Recovering well', 'Feeding well', 'Routine postnatal checkup', 2, '2026-03-18 10:20:05'),
(3, 4, '2025-11-23 00:00:00', 'Wound healing normally', 'No jaundice', 'Routine postnatal checkup', 3, '2026-03-18 10:20:05'),
(4, 6, '2026-01-20 00:00:00', 'Breastfeeding established', 'Development normal', 'Routine postnatal checkup', 3, '2026-03-18 10:20:05'),
(5, 6, '2026-03-09 00:00:00', 'Recovering well', 'Weight gain appropriate', 'Routine postnatal checkup', 2, '2026-03-18 10:20:05'),
(6, 9, '2025-11-19 00:00:00', 'No complications', 'No jaundice', 'Routine postnatal checkup', 2, '2026-03-18 10:20:05'),
(7, 9, '2025-12-24 00:00:00', 'Breastfeeding established', 'Feeding well', 'Routine postnatal checkup', 3, '2026-03-18 10:20:05'),
(8, 9, '2026-02-10 00:00:00', 'No complications', 'Feeding well', 'Routine postnatal checkup', 2, '2026-03-18 10:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `pregnancies`
--

CREATE TABLE `pregnancies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `lmp_date` date NOT NULL,
  `edd_date` date NOT NULL,
  `gravida` int(10) UNSIGNED DEFAULT NULL,
  `para` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('ongoing','delivered','terminated') NOT NULL DEFAULT 'ongoing',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pregnancies`
--

INSERT INTO `pregnancies` (`id`, `patient_id`, `lmp_date`, `edd_date`, `gravida`, `para`, `status`, `created_at`, `updated_at`) VALUES
(1, 67, '2024-10-29', '2025-08-05', 1, 1, 'delivered', '2026-03-18 10:20:05', NULL),
(2, 46, '2025-11-14', '2026-08-21', 5, 4, 'ongoing', '2026-03-18 10:20:05', NULL),
(3, 57, '2025-06-14', '2026-03-21', 2, 1, 'delivered', '2026-03-18 10:20:05', NULL),
(4, 7, '2025-01-05', '2025-10-12', 4, 1, 'delivered', '2026-03-18 10:20:05', NULL),
(5, 15, '2025-11-10', '2026-08-17', 5, 2, 'ongoing', '2026-03-18 10:20:05', NULL),
(6, 83, '2025-03-04', '2025-12-09', 1, 1, 'delivered', '2026-03-18 10:20:05', NULL),
(7, 48, '2025-04-19', '2026-01-24', 4, 1, 'terminated', '2026-03-18 10:20:05', NULL),
(8, 3, '2025-07-26', '2026-05-02', 2, 0, 'terminated', '2026-03-18 10:20:05', NULL),
(9, 95, '2025-02-05', '2025-11-12', 5, 5, 'delivered', '2026-03-18 10:20:05', NULL),
(10, 18, '2025-10-12', '2026-07-19', 5, 1, 'ongoing', '2026-03-18 10:20:05', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `prenatal_visits`
--

CREATE TABLE `prenatal_visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pregnancy_id` bigint(20) UNSIGNED NOT NULL,
  `visit_datetime` datetime NOT NULL,
  `gestational_age_weeks` int(10) UNSIGNED DEFAULT NULL,
  `bp_systolic` int(10) UNSIGNED DEFAULT NULL,
  `bp_diastolic` int(10) UNSIGNED DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `prenatal_visits`
--

INSERT INTO `prenatal_visits` (`id`, `pregnancy_id`, `visit_datetime`, `gestational_age_weeks`, `bp_systolic`, `bp_diastolic`, `weight_kg`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 1, '2024-11-26 00:00:00', 4, 139, 69, 81.71, 'No complications noted', 2, '2026-03-18 10:20:05'),
(2, 1, '2024-12-24 00:00:00', 8, 125, 84, 80.12, 'Routine monitoring', 2, '2026-03-18 10:20:05'),
(3, 1, '2025-01-21 00:00:00', 12, 110, 88, 60.98, 'Normal prenatal checkup', 2, '2026-03-18 10:20:05'),
(4, 1, '2025-02-18 00:00:00', 16, 101, 61, 70.67, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(5, 1, '2025-03-18 00:00:00', 20, 137, 84, 51.67, 'Patient counseled on delivery preparation', 2, '2026-03-18 10:20:05'),
(6, 1, '2025-04-15 00:00:00', 24, 126, 76, 66.01, 'Normal prenatal checkup', 2, '2026-03-18 10:20:05'),
(7, 1, '2025-05-13 00:00:00', 28, 107, 83, 63.07, 'Patient doing well', 3, '2026-03-18 10:20:05'),
(8, 1, '2025-06-10 00:00:00', 32, 104, 69, 48.85, 'Advised on nutrition', 3, '2026-03-18 10:20:05'),
(9, 2, '2025-12-12 00:00:00', 4, 97, 69, 78.93, 'Patient doing well', 3, '2026-03-18 10:20:05'),
(10, 2, '2026-01-09 00:00:00', 8, 111, 63, 71.23, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(11, 2, '2026-02-06 00:00:00', 12, 113, 87, 65.56, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(12, 2, '2026-03-06 00:00:00', 16, 107, 78, 83.39, 'Fetal heartbeat normal', 2, '2026-03-18 10:20:05'),
(13, 3, '2025-07-12 00:00:00', 4, 104, 68, 62.29, 'Patient doing well', 2, '2026-03-18 10:20:05'),
(14, 3, '2025-08-09 00:00:00', 8, 130, 69, 81.70, 'Fetal heartbeat normal', 2, '2026-03-18 10:20:05'),
(15, 3, '2025-09-06 00:00:00', 12, 112, 78, 53.69, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(16, 3, '2025-10-04 00:00:00', 16, 128, 89, 78.08, 'Advised on nutrition', 3, '2026-03-18 10:20:05'),
(17, 3, '2025-11-01 00:00:00', 20, 108, 71, 46.61, 'Fetal heartbeat normal', 3, '2026-03-18 10:20:05'),
(18, 3, '2025-11-29 00:00:00', 24, 110, 66, 64.64, 'Routine monitoring', 2, '2026-03-18 10:20:05'),
(19, 3, '2025-12-27 00:00:00', 28, 138, 78, 75.03, 'No complications noted', 3, '2026-03-18 10:20:05'),
(20, 3, '2026-01-24 00:00:00', 32, 135, 75, 62.56, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(21, 4, '2025-02-02 00:00:00', 4, 98, 62, 62.54, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(22, 4, '2025-03-02 00:00:00', 8, 97, 74, 75.94, 'Advised on nutrition', 3, '2026-03-18 10:20:05'),
(23, 4, '2025-03-30 00:00:00', 12, 111, 84, 57.29, 'Fetal heartbeat normal', 2, '2026-03-18 10:20:05'),
(24, 4, '2025-04-27 00:00:00', 16, 120, 86, 52.57, 'Patient doing well', 3, '2026-03-18 10:20:05'),
(25, 4, '2025-05-25 00:00:00', 20, 136, 72, 51.77, 'Advised on nutrition', 3, '2026-03-18 10:20:05'),
(26, 4, '2025-06-22 00:00:00', 24, 119, 89, 71.52, 'Patient doing well', 2, '2026-03-18 10:20:05'),
(27, 4, '2025-07-20 00:00:00', 28, 133, 80, 47.31, 'Patient counseled on delivery preparation', 3, '2026-03-18 10:20:05'),
(28, 4, '2025-08-17 00:00:00', 32, 122, 62, 65.77, 'Patient doing well', 2, '2026-03-18 10:20:05'),
(29, 4, '2025-09-14 00:00:00', 36, 125, 82, 67.39, 'Normal prenatal checkup', 2, '2026-03-18 10:20:05'),
(30, 5, '2025-12-08 00:00:00', 4, 97, 80, 47.77, 'Patient doing well', 3, '2026-03-18 10:20:05'),
(31, 5, '2026-01-05 00:00:00', 8, 128, 80, 54.29, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(32, 6, '2025-04-01 00:00:00', 4, 108, 73, 51.98, 'Advised on nutrition', 2, '2026-03-18 10:20:05'),
(33, 6, '2025-04-29 00:00:00', 8, 92, 89, 73.96, 'Fetal heartbeat normal', 2, '2026-03-18 10:20:05'),
(34, 6, '2025-05-27 00:00:00', 12, 122, 70, 76.46, 'Fetal heartbeat normal', 2, '2026-03-18 10:20:05'),
(35, 6, '2025-06-24 00:00:00', 16, 99, 67, 71.42, 'Fetal heartbeat normal', 3, '2026-03-18 10:20:05'),
(36, 6, '2025-07-22 00:00:00', 20, 96, 80, 65.58, 'Patient counseled on delivery preparation', 2, '2026-03-18 10:20:05'),
(37, 6, '2025-08-19 00:00:00', 24, 129, 90, 85.23, 'Fetal heartbeat normal', 2, '2026-03-18 10:20:05'),
(38, 6, '2025-09-16 00:00:00', 28, 140, 82, 67.30, 'Patient counseled on delivery preparation', 2, '2026-03-18 10:20:05'),
(39, 6, '2025-10-14 00:00:00', 32, 126, 65, 75.52, 'No complications noted', 3, '2026-03-18 10:20:05'),
(40, 6, '2025-11-11 00:00:00', 36, 105, 86, 62.05, 'Patient counseled on delivery preparation', 3, '2026-03-18 10:20:05'),
(41, 9, '2025-03-05 00:00:00', 4, 120, 86, 51.06, 'Routine monitoring', 2, '2026-03-18 10:20:05'),
(42, 9, '2025-04-02 00:00:00', 8, 122, 74, 50.07, 'Routine monitoring', 2, '2026-03-18 10:20:05'),
(43, 9, '2025-04-30 00:00:00', 12, 93, 75, 85.40, 'Patient doing well', 2, '2026-03-18 10:20:05'),
(44, 9, '2025-05-28 00:00:00', 16, 139, 85, 85.82, 'Routine monitoring', 3, '2026-03-18 10:20:05'),
(45, 9, '2025-06-25 00:00:00', 20, 133, 72, 64.58, 'Routine monitoring', 2, '2026-03-18 10:20:05'),
(46, 9, '2025-07-23 00:00:00', 24, 110, 76, 51.65, 'Normal prenatal checkup', 2, '2026-03-18 10:20:05'),
(47, 9, '2025-08-20 00:00:00', 28, 99, 67, 85.21, 'No complications noted', 3, '2026-03-18 10:20:05'),
(48, 9, '2025-09-17 00:00:00', 32, 110, 61, 83.02, 'Fetal heartbeat normal', 3, '2026-03-18 10:20:05'),
(49, 9, '2025-10-15 00:00:00', 36, 122, 66, 63.80, 'No complications noted', 2, '2026-03-18 10:20:05'),
(50, 10, '2025-11-09 00:00:00', 4, 137, 84, 73.90, 'Normal prenatal checkup', 2, '2026-03-18 10:20:05'),
(51, 10, '2025-12-07 00:00:00', 8, 113, 81, 68.75, 'No complications noted', 2, '2026-03-18 10:20:05'),
(52, 10, '2026-01-04 00:00:00', 12, 128, 67, 57.82, 'No complications noted', 2, '2026-03-18 10:20:05'),
(53, 10, '2026-02-01 00:00:00', 16, 120, 84, 75.04, 'Patient counseled on delivery preparation', 3, '2026-03-18 10:20:05');

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `reminder_type` enum('immunization','prenatal','postnatal','family_planning','non_communicable','general') NOT NULL,
  `due_date` date NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` enum('pending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reminders`
--

INSERT INTO `reminders` (`id`, `patient_id`, `reminder_type`, `due_date`, `message`, `status`, `sent_at`, `created_at`) VALUES
(7, 1, 'family_planning', '2026-09-05', 'Good day Rodrigo! Reminder from Barangay Health Center: Your Family Planning supply refill (Pills (COC - Combined)) is scheduled on Sep 05, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(8, 2, 'family_planning', '2026-09-15', 'Good day Maria! Reminder from Barangay Health Center: Your Family Planning supply refill (Pills (POP - Progestin Only)) is scheduled on Sep 15, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(9, 3, 'family_planning', '2026-10-10', 'Good day Isabel! Reminder from Barangay Health Center: Your Family Planning supply refill (DMPA Injectable (Depo-Provera)) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(10, 4, 'family_planning', '2026-10-10', 'Good day Maria! Reminder from Barangay Health Center: Your Family Planning supply refill (Subdermal Implant Insertion/Check) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(11, 5, 'family_planning', '2026-09-05', 'Good day Victoria! Reminder from Barangay Health Center: Your Family Planning supply refill (IUD (Interval Check)) is scheduled on Sep 05, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(12, 6, 'family_planning', '2026-09-15', 'Good day Margarita! Reminder from Barangay Health Center: Your Family Planning supply refill (Condoms (Pack of 10)) is scheduled on Sep 15, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(13, 7, 'family_planning', '2026-10-10', 'Good day Sofia! Reminder from Barangay Health Center: Your Family Planning supply refill (LAM Counseling & Check) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(14, 8, 'family_planning', '2026-10-10', 'Good day Dolores! Reminder from Barangay Health Center: Your Family Planning supply refill (Pills (COC - Combined)) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(15, 9, 'family_planning', '2026-09-05', 'Good day Dolores! Reminder from Barangay Health Center: Your Family Planning supply refill (Pills (POP - Progestin Only)) is scheduled on Sep 05, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(16, 10, 'family_planning', '2026-09-15', 'Good day Jose! Reminder from Barangay Health Center: Your Family Planning supply refill (DMPA Injectable (Depo-Provera)) is scheduled on Sep 15, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(17, 11, 'family_planning', '2026-10-10', 'Good day Sofia! Reminder from Barangay Health Center: Your Family Planning supply refill (Subdermal Implant Insertion/Check) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(18, 12, 'family_planning', '2026-10-10', 'Good day Teresa! Reminder from Barangay Health Center: Your Family Planning supply refill (IUD (Interval Check)) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(19, 13, 'family_planning', '2026-09-05', 'Good day Ramon! Reminder from Barangay Health Center: Your Family Planning supply refill (Condoms (Pack of 10)) is scheduled on Sep 05, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(20, 14, 'family_planning', '2026-09-15', 'Good day Eduardo! Reminder from Barangay Health Center: Your Family Planning supply refill (LAM Counseling & Check) is scheduled on Sep 15, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(21, 15, 'family_planning', '2026-10-10', 'Good day Ana! Reminder from Barangay Health Center: Your Family Planning supply refill (Pills (COC - Combined)) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(22, 16, 'family_planning', '2026-10-10', 'Good day Luz! Reminder from Barangay Health Center: Your Family Planning supply refill (Pills (POP - Progestin Only)) is scheduled on Oct 10, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(23, 17, 'family_planning', '2026-09-05', 'Good day Elena! Reminder from Barangay Health Center: Your Family Planning supply refill (DMPA Injectable (Depo-Provera)) is scheduled on Sep 05, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(24, 18, 'family_planning', '2026-09-15', 'Good day Cristina! Reminder from Barangay Health Center: Your Family Planning supply refill (Subdermal Implant Insertion/Check) is scheduled on Sep 15, 2026.', 'pending', NULL, '2026-09-10 20:40:06'),
(25, 1, 'non_communicable', '2026-08-31', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Hypertension (Stage 1 / 2)) is scheduled on Aug 31, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(26, 3, 'non_communicable', '2026-10-04', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Hypertension & Type 2 Diabetes) is scheduled on Oct 04, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(27, 4, 'non_communicable', '2026-09-19', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Bronchial Asthma / Chronic Airway Disease) is scheduled on Sep 19, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(28, 5, 'non_communicable', '2026-10-10', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Coronary Artery Disease / Post-Stroke) is scheduled on Oct 10, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(29, 6, 'non_communicable', '2026-09-02', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Chronic Kidney Disease (Stage 2-3)) is scheduled on Sep 02, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(30, 7, 'non_communicable', '2026-09-21', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Hypertension (Stage 1 / 2)) is scheduled on Sep 21, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(31, 8, 'non_communicable', '2026-09-27', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Type 2 Diabetes Mellitus) is scheduled on Sep 27, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(32, 9, 'non_communicable', '2026-10-01', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Hypertension & Type 2 Diabetes) is scheduled on Oct 01, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(33, 10, 'non_communicable', '2026-10-10', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Bronchial Asthma / Chronic Airway Disease) is scheduled on Oct 10, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(34, 11, 'non_communicable', '2026-08-28', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Coronary Artery Disease / Post-Stroke) is scheduled on Aug 28, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(35, 15, 'non_communicable', '2026-10-09', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Hypertension & Type 2 Diabetes) is scheduled on Oct 09, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:43'),
(36, 16, 'non_communicable', '2026-09-08', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Bronchial Asthma / Chronic Airway Disease) is scheduled on Sep 08, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:44'),
(37, 18, 'non_communicable', '2026-09-18', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Chronic Kidney Disease (Stage 2-3)) is scheduled on Sep 18, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:44'),
(38, 20, 'non_communicable', '2026-09-26', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Type 2 Diabetes Mellitus) is scheduled on Sep 26, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:44'),
(39, 21, 'non_communicable', '2026-08-29', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Hypertension & Type 2 Diabetes) is scheduled on Aug 29, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:44'),
(40, 23, 'non_communicable', '2026-10-06', 'Good day! Reminder from Barangay Health Station: Your NCD checkup and maintenance medication refill (Coronary Artery Disease / Post-Stroke) is scheduled on Oct 06, 2026. Please bring your monitoring card.', 'pending', NULL, '2026-09-12 18:51:44'),
(41, 73, 'immunization', '2026-03-09', 'Reminder: Pentavalent dose 2 scheduled for Daniel Villanueva', 'pending', NULL, '2026-09-16 21:17:00'),
(42, 73, 'immunization', '2026-05-09', 'Reminder: Pentavalent dose 3 scheduled for Daniel Villanueva', 'pending', NULL, '2026-09-16 21:17:00'),
(43, 73, 'immunization', '2026-05-09', 'Reminder: Oral Polio Vaccine dose 3 scheduled for Daniel Villanueva', 'pending', NULL, '2026-09-16 21:17:00');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`) VALUES
(1, 'admin'),
(3, 'health_worker');

-- --------------------------------------------------------

--
-- Table structure for table `timeseries_daily`
--

CREATE TABLE `timeseries_daily` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `series_key` varchar(120) NOT NULL,
  `series_date` date NOT NULL,
  `value` decimal(12,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timeseries_daily`
--

INSERT INTO `timeseries_daily` (`id`, `series_key`, `series_date`, `value`, `created_at`) VALUES
(1, 'visits_total', '2025-03-18', 1.00, '2026-03-18 10:21:10'),
(2, 'visits_total', '2025-03-30', 1.00, '2026-03-18 10:21:10'),
(3, 'visits_total', '2025-04-01', 1.00, '2026-03-18 10:21:10'),
(4, 'visits_total', '2025-04-02', 1.00, '2026-03-18 10:21:10'),
(5, 'visits_total', '2025-04-15', 1.00, '2026-03-18 10:21:10'),
(6, 'visits_total', '2025-04-20', 1.00, '2026-03-18 10:21:10'),
(7, 'visits_total', '2025-04-27', 1.00, '2026-03-18 10:21:10'),
(8, 'visits_total', '2025-04-29', 1.00, '2026-03-18 10:21:10'),
(9, 'visits_total', '2025-04-30', 1.00, '2026-03-18 10:21:10'),
(10, 'visits_total', '2025-05-13', 1.00, '2026-03-18 10:21:10'),
(11, 'visits_total', '2025-05-25', 1.00, '2026-03-18 10:21:10'),
(12, 'visits_total', '2025-05-27', 1.00, '2026-03-18 10:21:10'),
(13, 'visits_total', '2025-05-28', 1.00, '2026-03-18 10:21:10'),
(14, 'visits_total', '2025-06-10', 1.00, '2026-03-18 10:21:10'),
(15, 'visits_total', '2025-06-18', 1.00, '2026-03-18 10:21:10'),
(16, 'visits_total', '2025-06-22', 1.00, '2026-03-18 10:21:10'),
(17, 'visits_total', '2025-06-24', 1.00, '2026-03-18 10:21:10'),
(18, 'visits_total', '2025-06-25', 1.00, '2026-03-18 10:21:10'),
(19, 'visits_total', '2025-07-12', 1.00, '2026-03-18 10:21:10'),
(20, 'visits_total', '2025-07-20', 1.00, '2026-03-18 10:21:10'),
(21, 'visits_total', '2025-07-22', 1.00, '2026-03-18 10:21:10'),
(22, 'visits_total', '2025-07-23', 1.00, '2026-03-18 10:21:10'),
(23, 'visits_total', '2025-08-09', 1.00, '2026-03-18 10:21:10'),
(24, 'visits_total', '2025-08-17', 1.00, '2026-03-18 10:21:10'),
(25, 'visits_total', '2025-08-19', 1.00, '2026-03-18 10:21:10'),
(26, 'visits_total', '2025-08-20', 1.00, '2026-03-18 10:21:10'),
(27, 'visits_total', '2025-08-27', 1.00, '2026-03-18 10:21:10'),
(28, 'visits_total', '2025-09-06', 1.00, '2026-03-18 10:21:10'),
(29, 'visits_total', '2025-09-14', 1.00, '2026-03-18 10:21:10'),
(30, 'visits_total', '2025-09-16', 2.00, '2026-03-18 10:21:10'),
(31, 'visits_total', '2025-09-17', 1.00, '2026-03-18 10:21:10'),
(32, 'visits_total', '2025-10-04', 1.00, '2026-03-18 10:21:10'),
(33, 'visits_total', '2025-10-10', 1.00, '2026-03-18 10:21:10'),
(34, 'visits_total', '2025-10-14', 1.00, '2026-03-18 10:21:10'),
(35, 'visits_total', '2025-10-15', 1.00, '2026-03-18 10:21:10'),
(36, 'visits_total', '2025-10-17', 1.00, '2026-03-18 10:21:10'),
(37, 'visits_total', '2025-11-01', 1.00, '2026-03-18 10:21:10'),
(38, 'visits_total', '2025-11-03', 1.00, '2026-03-18 10:21:10'),
(39, 'visits_total', '2025-11-09', 1.00, '2026-03-18 10:21:10'),
(40, 'visits_total', '2025-11-11', 1.00, '2026-03-18 10:21:10'),
(41, 'visits_total', '2025-11-16', 1.00, '2026-03-18 10:21:10'),
(42, 'visits_total', '2025-11-17', 1.00, '2026-03-18 10:21:10'),
(43, 'visits_total', '2025-11-19', 1.00, '2026-03-18 10:21:10'),
(44, 'visits_total', '2025-11-23', 1.00, '2026-03-18 10:21:10'),
(45, 'visits_total', '2025-11-29', 1.00, '2026-03-18 10:21:10'),
(46, 'visits_total', '2025-12-07', 1.00, '2026-03-18 10:21:10'),
(47, 'visits_total', '2025-12-08', 1.00, '2026-03-18 10:21:10'),
(48, 'visits_total', '2025-12-12', 1.00, '2026-03-18 10:21:10'),
(49, 'visits_total', '2025-12-18', 10.00, '2026-03-18 10:21:10'),
(50, 'visits_total', '2025-12-19', 10.00, '2026-03-18 10:21:10'),
(51, 'visits_total', '2025-12-20', 24.00, '2026-03-18 10:21:10'),
(52, 'visits_total', '2025-12-21', 12.00, '2026-03-18 10:21:10'),
(53, 'visits_total', '2025-12-22', 14.00, '2026-03-18 10:21:10'),
(54, 'visits_total', '2025-12-23', 25.00, '2026-03-18 10:21:10'),
(55, 'visits_total', '2025-12-24', 6.00, '2026-03-18 10:21:10'),
(56, 'visits_total', '2025-12-25', 21.00, '2026-03-18 10:21:10'),
(57, 'visits_total', '2025-12-26', 19.00, '2026-03-18 10:21:10'),
(58, 'visits_total', '2025-12-27', 9.00, '2026-03-18 10:21:10'),
(59, 'visits_total', '2025-12-28', 7.00, '2026-03-18 10:21:10'),
(60, 'visits_total', '2025-12-29', 25.00, '2026-03-18 10:21:11'),
(61, 'visits_total', '2025-12-30', 10.00, '2026-03-18 10:21:11'),
(62, 'visits_total', '2025-12-31', 5.00, '2026-03-18 10:21:11'),
(63, 'visits_total', '2026-01-01', 16.00, '2026-03-18 10:21:11'),
(64, 'visits_total', '2026-01-02', 24.00, '2026-03-18 10:21:11'),
(65, 'visits_total', '2026-01-03', 22.00, '2026-03-18 10:21:11'),
(66, 'visits_total', '2026-01-04', 17.00, '2026-03-18 10:21:11'),
(67, 'visits_total', '2026-01-05', 11.00, '2026-03-18 10:21:11'),
(68, 'visits_total', '2026-01-06', 20.00, '2026-03-18 10:21:11'),
(69, 'visits_total', '2026-01-07', 17.00, '2026-03-18 10:21:11'),
(70, 'visits_total', '2026-01-08', 20.00, '2026-03-18 10:21:11'),
(71, 'visits_total', '2026-01-09', 26.00, '2026-03-18 10:21:11'),
(72, 'visits_total', '2026-01-10', 23.00, '2026-03-18 10:21:11'),
(73, 'visits_total', '2026-01-11', 25.00, '2026-03-18 10:21:11'),
(74, 'visits_total', '2026-01-12', 7.00, '2026-03-18 10:21:11'),
(75, 'visits_total', '2026-01-13', 15.00, '2026-03-18 10:21:11'),
(76, 'visits_total', '2026-01-14', 12.00, '2026-03-18 10:21:11'),
(77, 'visits_total', '2026-01-15', 23.00, '2026-03-18 10:21:11'),
(78, 'visits_total', '2026-01-16', 6.00, '2026-03-18 10:21:11'),
(79, 'visits_total', '2026-01-17', 25.00, '2026-03-18 10:21:11'),
(80, 'visits_total', '2026-01-18', 15.00, '2026-03-18 10:21:11'),
(81, 'visits_total', '2026-01-19', 25.00, '2026-03-18 10:21:11'),
(82, 'visits_total', '2026-01-20', 11.00, '2026-03-18 10:21:11'),
(83, 'visits_total', '2026-01-21', 23.00, '2026-03-18 10:21:11'),
(84, 'visits_total', '2026-01-22', 5.00, '2026-03-18 10:21:11'),
(85, 'visits_total', '2026-01-23', 10.00, '2026-03-18 10:21:11'),
(86, 'visits_total', '2026-01-24', 20.00, '2026-03-18 10:21:11'),
(87, 'visits_total', '2026-01-25', 7.00, '2026-03-18 10:21:11'),
(88, 'visits_total', '2026-01-26', 25.00, '2026-03-18 10:21:11'),
(89, 'visits_total', '2026-01-27', 16.00, '2026-03-18 10:21:11'),
(90, 'visits_total', '2026-01-28', 14.00, '2026-03-18 10:21:11'),
(91, 'visits_total', '2026-01-29', 18.00, '2026-03-18 10:21:11'),
(92, 'visits_total', '2026-01-30', 9.00, '2026-03-18 10:21:11'),
(93, 'visits_total', '2026-01-31', 11.00, '2026-03-18 10:21:11'),
(94, 'visits_total', '2026-02-01', 15.00, '2026-03-18 10:21:11'),
(95, 'visits_total', '2026-02-02', 8.00, '2026-03-18 10:21:11'),
(96, 'visits_total', '2026-02-03', 15.00, '2026-03-18 10:21:11'),
(97, 'visits_total', '2026-02-04', 17.00, '2026-03-18 10:21:11'),
(98, 'visits_total', '2026-02-05', 19.00, '2026-03-18 10:21:11'),
(99, 'visits_total', '2026-02-06', 8.00, '2026-03-18 10:21:11'),
(100, 'visits_total', '2026-02-07', 7.00, '2026-03-18 10:21:11'),
(101, 'visits_total', '2026-02-08', 9.00, '2026-03-18 10:21:11'),
(102, 'visits_total', '2026-02-09', 24.00, '2026-03-18 10:21:11'),
(103, 'visits_total', '2026-02-10', 12.00, '2026-03-18 10:21:11'),
(104, 'visits_total', '2026-02-11', 20.00, '2026-03-18 10:21:11'),
(105, 'visits_total', '2026-02-12', 21.00, '2026-03-18 10:21:11'),
(106, 'visits_total', '2026-02-13', 11.00, '2026-03-18 10:21:11'),
(107, 'visits_total', '2026-02-14', 7.00, '2026-03-18 10:21:11'),
(108, 'visits_total', '2026-02-15', 11.00, '2026-03-18 10:21:11'),
(109, 'visits_total', '2026-02-16', 21.00, '2026-03-18 10:21:11'),
(110, 'visits_total', '2026-02-17', 12.00, '2026-03-18 10:21:11'),
(111, 'visits_total', '2026-02-18', 10.00, '2026-03-18 10:21:11'),
(112, 'visits_total', '2026-02-19', 20.00, '2026-03-18 10:21:11'),
(113, 'visits_total', '2026-02-20', 7.00, '2026-03-18 10:21:11'),
(114, 'visits_total', '2026-02-21', 7.00, '2026-03-18 10:21:11'),
(115, 'visits_total', '2026-02-22', 22.00, '2026-03-18 10:21:11'),
(116, 'visits_total', '2026-02-23', 18.00, '2026-03-18 10:21:11'),
(117, 'visits_total', '2026-02-24', 18.00, '2026-03-18 10:21:11'),
(118, 'visits_total', '2026-02-25', 10.00, '2026-03-18 10:21:11'),
(119, 'visits_total', '2026-02-26', 10.00, '2026-03-18 10:21:11'),
(120, 'visits_total', '2026-02-27', 24.00, '2026-03-18 10:21:11'),
(121, 'visits_total', '2026-02-28', 17.00, '2026-03-18 10:21:11'),
(122, 'visits_total', '2026-03-01', 17.00, '2026-03-18 10:21:11'),
(123, 'visits_total', '2026-03-02', 18.00, '2026-03-18 10:21:11'),
(124, 'visits_total', '2026-03-03', 20.00, '2026-03-18 10:21:11'),
(125, 'visits_total', '2026-03-04', 6.00, '2026-03-18 10:21:11'),
(126, 'visits_total', '2026-03-05', 9.00, '2026-03-18 10:21:11'),
(127, 'visits_total', '2026-03-06', 20.00, '2026-03-18 10:21:11'),
(128, 'visits_total', '2026-03-07', 14.00, '2026-03-18 10:21:11'),
(129, 'visits_total', '2026-03-08', 10.00, '2026-03-18 10:21:11'),
(130, 'visits_total', '2026-03-09', 18.00, '2026-03-18 10:21:11'),
(131, 'visits_total', '2026-03-10', 5.00, '2026-03-18 10:21:11'),
(132, 'visits_total', '2026-03-11', 22.00, '2026-03-18 10:21:11'),
(133, 'visits_total', '2026-03-12', 9.00, '2026-03-18 10:21:11'),
(134, 'visits_total', '2026-03-13', 25.00, '2026-03-18 10:21:11'),
(135, 'visits_total', '2026-03-14', 7.00, '2026-03-18 10:21:11'),
(136, 'visits_total', '2026-03-15', 16.00, '2026-03-18 10:21:11'),
(137, 'visits_total', '2026-03-16', 16.00, '2026-03-18 10:21:11'),
(138, 'visits_total', '2026-03-17', 5.00, '2026-03-18 10:21:11'),
(139, 'medicine_total', '2025-03-23', 152.00, '2026-03-18 10:21:11'),
(140, 'medicine_total', '2025-03-24', 108.00, '2026-03-18 10:21:11'),
(141, 'medicine_total', '2025-03-25', 116.00, '2026-03-18 10:21:11'),
(142, 'medicine_total', '2025-03-30', 105.00, '2026-03-18 10:21:11'),
(143, 'medicine_total', '2025-04-02', 112.00, '2026-03-18 10:21:11'),
(144, 'medicine_total', '2025-04-04', 173.00, '2026-03-18 10:21:11'),
(145, 'medicine_total', '2025-04-05', 75.00, '2026-03-18 10:21:11'),
(146, 'medicine_total', '2025-04-06', 5.00, '2026-03-18 10:21:11'),
(147, 'medicine_total', '2025-04-07', 125.00, '2026-03-18 10:21:11'),
(148, 'medicine_total', '2025-04-11', 32.00, '2026-03-18 10:21:11'),
(149, 'medicine_total', '2025-04-12', 44.00, '2026-03-18 10:21:11'),
(150, 'medicine_total', '2025-04-13', 114.00, '2026-03-18 10:21:11'),
(151, 'medicine_total', '2025-04-14', 108.00, '2026-03-18 10:21:11'),
(152, 'medicine_total', '2025-04-15', 7.00, '2026-03-18 10:21:11'),
(153, 'medicine_total', '2025-04-18', 43.00, '2026-03-18 10:21:11'),
(154, 'medicine_total', '2025-04-21', 340.00, '2026-03-18 10:21:11'),
(155, 'medicine_total', '2025-04-22', 88.00, '2026-03-18 10:21:11'),
(156, 'medicine_total', '2025-04-24', 190.00, '2026-03-18 10:21:11'),
(157, 'medicine_total', '2025-04-25', 110.00, '2026-03-18 10:21:11'),
(158, 'medicine_total', '2025-04-26', 377.00, '2026-03-18 10:21:11'),
(159, 'medicine_total', '2025-04-27', 80.00, '2026-03-18 10:21:11'),
(160, 'medicine_total', '2025-04-29', 210.00, '2026-03-18 10:21:11'),
(161, 'medicine_total', '2025-05-01', 85.00, '2026-03-18 10:21:11'),
(162, 'medicine_total', '2025-05-02', 116.00, '2026-03-18 10:21:11'),
(163, 'medicine_total', '2025-05-03', 14.00, '2026-03-18 10:21:11'),
(164, 'medicine_total', '2025-05-04', 17.00, '2026-03-18 10:21:11'),
(165, 'medicine_total', '2025-05-05', 49.00, '2026-03-18 10:21:11'),
(166, 'medicine_total', '2025-05-06', 18.00, '2026-03-18 10:21:11'),
(167, 'medicine_total', '2025-05-07', 309.00, '2026-03-18 10:21:11'),
(168, 'medicine_total', '2025-05-09', 50.00, '2026-03-18 10:21:11'),
(169, 'medicine_total', '2025-05-10', 110.00, '2026-03-18 10:21:11'),
(170, 'medicine_total', '2025-05-11', 103.00, '2026-03-18 10:21:11'),
(171, 'medicine_total', '2025-05-12', 257.00, '2026-03-18 10:21:11'),
(172, 'medicine_total', '2025-05-13', 128.00, '2026-03-18 10:21:11'),
(173, 'medicine_total', '2025-05-14', 146.00, '2026-03-18 10:21:11'),
(174, 'medicine_total', '2025-05-16', 132.00, '2026-03-18 10:21:11'),
(175, 'medicine_total', '2025-05-18', 22.00, '2026-03-18 10:21:11'),
(176, 'medicine_total', '2025-05-21', 26.00, '2026-03-18 10:21:11'),
(177, 'medicine_total', '2025-05-22', 58.00, '2026-03-18 10:21:11'),
(178, 'medicine_total', '2025-05-23', 192.00, '2026-03-18 10:21:11'),
(179, 'medicine_total', '2025-05-24', 27.00, '2026-03-18 10:21:11'),
(180, 'medicine_total', '2025-05-25', 137.00, '2026-03-18 10:21:11'),
(181, 'medicine_total', '2025-05-26', 386.00, '2026-03-18 10:21:11'),
(182, 'medicine_total', '2025-05-27', 57.00, '2026-03-18 10:21:11'),
(183, 'medicine_total', '2025-05-28', 175.00, '2026-03-18 10:21:11'),
(184, 'medicine_total', '2025-05-29', 22.00, '2026-03-18 10:21:11'),
(185, 'medicine_total', '2025-05-31', 39.00, '2026-03-18 10:21:11'),
(186, 'medicine_total', '2025-06-01', 107.00, '2026-03-18 10:21:11'),
(187, 'medicine_total', '2025-06-02', 13.00, '2026-03-18 10:21:11'),
(188, 'medicine_total', '2025-06-03', 114.00, '2026-03-18 10:21:11'),
(189, 'medicine_total', '2025-06-04', 33.00, '2026-03-18 10:21:11'),
(190, 'medicine_total', '2025-06-05', 184.00, '2026-03-18 10:21:11'),
(191, 'medicine_total', '2025-06-06', 41.00, '2026-03-18 10:21:11'),
(192, 'medicine_total', '2025-06-08', 61.00, '2026-03-18 10:21:11'),
(193, 'medicine_total', '2025-06-10', 440.00, '2026-03-18 10:21:11'),
(194, 'medicine_total', '2025-06-11', 129.00, '2026-03-18 10:21:11'),
(195, 'medicine_total', '2025-06-12', 89.00, '2026-03-18 10:21:11'),
(196, 'medicine_total', '2025-06-13', 116.00, '2026-03-18 10:21:11'),
(197, 'medicine_total', '2025-06-14', 14.00, '2026-03-18 10:21:11'),
(198, 'medicine_total', '2025-06-16', 15.00, '2026-03-18 10:21:11'),
(199, 'medicine_total', '2025-06-17', 221.00, '2026-03-18 10:21:11'),
(200, 'medicine_total', '2025-06-18', 92.00, '2026-03-18 10:21:11'),
(201, 'medicine_total', '2025-06-19', 24.00, '2026-03-18 10:21:11'),
(202, 'medicine_total', '2025-06-20', 105.00, '2026-03-18 10:21:11'),
(203, 'medicine_total', '2025-06-21', 46.00, '2026-03-18 10:21:11'),
(204, 'medicine_total', '2025-06-22', 70.00, '2026-03-18 10:21:11'),
(205, 'medicine_total', '2025-06-23', 393.00, '2026-03-18 10:21:11'),
(206, 'medicine_total', '2025-06-24', 183.00, '2026-03-18 10:21:11'),
(207, 'medicine_total', '2025-06-25', 118.00, '2026-03-18 10:21:11'),
(208, 'medicine_total', '2025-06-26', 235.00, '2026-03-18 10:21:11'),
(209, 'medicine_total', '2025-06-27', 22.00, '2026-03-18 10:21:11'),
(210, 'medicine_total', '2025-06-28', 121.00, '2026-03-18 10:21:11'),
(211, 'medicine_total', '2025-06-30', 253.00, '2026-03-18 10:21:11'),
(212, 'medicine_total', '2025-07-01', 53.00, '2026-03-18 10:21:11'),
(213, 'medicine_total', '2025-07-02', 35.00, '2026-03-18 10:21:11'),
(214, 'medicine_total', '2025-07-03', 12.00, '2026-03-18 10:21:11'),
(215, 'medicine_total', '2025-07-04', 83.00, '2026-03-18 10:21:11'),
(216, 'medicine_total', '2025-07-05', 169.00, '2026-03-18 10:21:11'),
(217, 'medicine_total', '2025-07-06', 302.00, '2026-03-18 10:21:11'),
(218, 'medicine_total', '2025-07-07', 109.00, '2026-03-18 10:21:11'),
(219, 'medicine_total', '2025-07-08', 208.00, '2026-03-18 10:21:11'),
(220, 'medicine_total', '2025-07-09', 126.00, '2026-03-18 10:21:11'),
(221, 'medicine_total', '2025-07-10', 155.00, '2026-03-18 10:21:11'),
(222, 'medicine_total', '2025-07-11', 36.00, '2026-03-18 10:21:11'),
(223, 'medicine_total', '2025-07-12', 34.00, '2026-03-18 10:21:11'),
(224, 'medicine_total', '2025-07-15', 139.00, '2026-03-18 10:21:11'),
(225, 'medicine_total', '2025-07-16', 46.00, '2026-03-18 10:21:11'),
(226, 'medicine_total', '2025-07-17', 100.00, '2026-03-18 10:21:11'),
(227, 'medicine_total', '2025-07-18', 226.00, '2026-03-18 10:21:11'),
(228, 'medicine_total', '2025-07-19', 82.00, '2026-03-18 10:21:11'),
(229, 'medicine_total', '2025-07-20', 48.00, '2026-03-18 10:21:11'),
(230, 'medicine_total', '2025-07-21', 130.00, '2026-03-18 10:21:11'),
(231, 'medicine_total', '2025-07-22', 106.00, '2026-03-18 10:21:11'),
(232, 'medicine_total', '2025-07-23', 18.00, '2026-03-18 10:21:11'),
(233, 'medicine_total', '2025-07-24', 188.00, '2026-03-18 10:21:11'),
(234, 'medicine_total', '2025-07-25', 258.00, '2026-03-18 10:21:11'),
(235, 'medicine_total', '2025-07-26', 291.00, '2026-03-18 10:21:11'),
(236, 'medicine_total', '2025-07-27', 140.00, '2026-03-18 10:21:11'),
(237, 'medicine_total', '2025-07-28', 34.00, '2026-03-18 10:21:11'),
(238, 'medicine_total', '2025-07-29', 18.00, '2026-03-18 10:21:11'),
(239, 'medicine_total', '2025-07-30', 91.00, '2026-03-18 10:21:11'),
(240, 'medicine_total', '2025-07-31', 34.00, '2026-03-18 10:21:11'),
(241, 'medicine_total', '2025-08-01', 171.00, '2026-03-18 10:21:11'),
(242, 'medicine_total', '2025-08-02', 385.00, '2026-03-18 10:21:11'),
(243, 'medicine_total', '2025-08-03', 246.00, '2026-03-18 10:21:11'),
(244, 'medicine_total', '2025-08-04', 105.00, '2026-03-18 10:21:11'),
(245, 'medicine_total', '2025-08-05', 12.00, '2026-03-18 10:21:11'),
(246, 'medicine_total', '2025-08-06', 523.00, '2026-03-18 10:21:11'),
(247, 'medicine_total', '2025-08-07', 123.00, '2026-03-18 10:21:11'),
(248, 'medicine_total', '2025-08-08', 145.00, '2026-03-18 10:21:11'),
(249, 'medicine_total', '2025-08-09', 61.00, '2026-03-18 10:21:11'),
(250, 'medicine_total', '2025-08-11', 138.00, '2026-03-18 10:21:11'),
(251, 'medicine_total', '2025-08-12', 158.00, '2026-03-18 10:21:11'),
(252, 'medicine_total', '2025-08-13', 79.00, '2026-03-18 10:21:11'),
(253, 'medicine_total', '2025-08-14', 106.00, '2026-03-18 10:21:11'),
(254, 'medicine_total', '2025-08-15', 310.00, '2026-03-18 10:21:11'),
(255, 'medicine_total', '2025-08-16', 42.00, '2026-03-18 10:21:11'),
(256, 'medicine_total', '2025-08-17', 188.00, '2026-03-18 10:21:11'),
(257, 'medicine_total', '2025-08-18', 147.00, '2026-03-18 10:21:11'),
(258, 'medicine_total', '2025-08-19', 104.00, '2026-03-18 10:21:11'),
(259, 'medicine_total', '2025-08-20', 338.00, '2026-03-18 10:21:11'),
(260, 'medicine_total', '2025-08-21', 186.00, '2026-03-18 10:21:11'),
(261, 'medicine_total', '2025-08-22', 18.00, '2026-03-18 10:21:11'),
(262, 'medicine_total', '2025-08-23', 59.00, '2026-03-18 10:21:11'),
(263, 'medicine_total', '2025-08-24', 282.00, '2026-03-18 10:21:11'),
(264, 'medicine_total', '2025-08-25', 271.00, '2026-03-18 10:21:11'),
(265, 'medicine_total', '2025-08-26', 290.00, '2026-03-18 10:21:11'),
(266, 'medicine_total', '2025-08-27', 184.00, '2026-03-18 10:21:11'),
(267, 'medicine_total', '2025-08-28', 45.00, '2026-03-18 10:21:11'),
(268, 'medicine_total', '2025-08-29', 119.00, '2026-03-18 10:21:11'),
(269, 'medicine_total', '2025-08-30', 136.00, '2026-03-18 10:21:11'),
(270, 'medicine_total', '2025-08-31', 385.00, '2026-03-18 10:21:11'),
(271, 'medicine_total', '2025-09-01', 126.00, '2026-03-18 10:21:11'),
(272, 'medicine_total', '2025-09-02', 51.00, '2026-03-18 10:21:11'),
(273, 'medicine_total', '2025-09-03', 367.00, '2026-03-18 10:21:11'),
(274, 'medicine_total', '2025-09-04', 266.00, '2026-03-18 10:21:11'),
(275, 'medicine_total', '2025-09-05', 74.00, '2026-03-18 10:21:11'),
(276, 'medicine_total', '2025-09-06', 207.00, '2026-03-18 10:21:11'),
(277, 'medicine_total', '2025-09-07', 103.00, '2026-03-18 10:21:11'),
(278, 'medicine_total', '2025-09-08', 376.00, '2026-03-18 10:21:11'),
(279, 'medicine_total', '2025-09-09', 444.00, '2026-03-18 10:21:11'),
(280, 'medicine_total', '2025-09-10', 144.00, '2026-03-18 10:21:11'),
(281, 'medicine_total', '2025-09-11', 221.00, '2026-03-18 10:21:11'),
(282, 'medicine_total', '2025-09-12', 121.00, '2026-03-18 10:21:11'),
(283, 'medicine_total', '2025-09-13', 13.00, '2026-03-18 10:21:11'),
(284, 'medicine_total', '2025-09-14', 108.00, '2026-03-18 10:21:11'),
(285, 'medicine_total', '2025-09-15', 349.00, '2026-03-18 10:21:11'),
(286, 'medicine_total', '2025-09-16', 160.00, '2026-03-18 10:21:11'),
(287, 'medicine_total', '2025-09-17', 139.00, '2026-03-18 10:21:11'),
(288, 'medicine_total', '2025-09-18', 199.00, '2026-03-18 10:21:11'),
(289, 'medicine_total', '2025-09-19', 311.00, '2026-03-18 10:21:11'),
(290, 'medicine_total', '2025-09-20', 298.00, '2026-03-18 10:21:11'),
(291, 'medicine_total', '2025-09-21', 310.00, '2026-03-18 10:21:11'),
(292, 'medicine_total', '2025-09-22', 257.00, '2026-03-18 10:21:11'),
(293, 'medicine_total', '2025-09-23', 101.00, '2026-03-18 10:21:11'),
(294, 'medicine_total', '2025-09-24', 114.00, '2026-03-18 10:21:11'),
(295, 'medicine_total', '2025-09-25', 512.00, '2026-03-18 10:21:11'),
(296, 'medicine_total', '2025-09-26', 218.00, '2026-03-18 10:21:11'),
(297, 'medicine_total', '2025-09-27', 291.00, '2026-03-18 10:21:11'),
(298, 'medicine_total', '2025-09-28', 364.00, '2026-03-18 10:21:11'),
(299, 'medicine_total', '2025-09-29', 115.00, '2026-03-18 10:21:11'),
(300, 'medicine_total', '2025-09-30', 174.00, '2026-03-18 10:21:11'),
(301, 'medicine_total', '2025-10-01', 214.00, '2026-03-18 10:21:11'),
(302, 'medicine_total', '2025-10-02', 115.00, '2026-03-18 10:21:11'),
(303, 'medicine_total', '2025-10-03', 275.00, '2026-03-18 10:21:11'),
(304, 'medicine_total', '2025-10-04', 373.00, '2026-03-18 10:21:11'),
(305, 'medicine_total', '2025-10-05', 352.00, '2026-03-18 10:21:11'),
(306, 'medicine_total', '2025-10-06', 101.00, '2026-03-18 10:21:11'),
(307, 'medicine_total', '2025-10-07', 297.00, '2026-03-18 10:21:11'),
(308, 'medicine_total', '2025-10-08', 224.00, '2026-03-18 10:21:11'),
(309, 'medicine_total', '2025-10-09', 117.00, '2026-03-18 10:21:11'),
(310, 'medicine_total', '2025-10-10', 47.00, '2026-03-18 10:21:11'),
(311, 'medicine_total', '2025-10-11', 252.00, '2026-03-18 10:21:11'),
(312, 'medicine_total', '2025-10-12', 298.00, '2026-03-18 10:21:11'),
(313, 'medicine_total', '2025-10-13', 87.00, '2026-03-18 10:21:11'),
(314, 'medicine_total', '2025-10-14', 53.00, '2026-03-18 10:21:11'),
(315, 'medicine_total', '2025-10-15', 377.00, '2026-03-18 10:21:11'),
(316, 'medicine_total', '2025-10-16', 134.00, '2026-03-18 10:21:11'),
(317, 'medicine_total', '2025-10-17', 182.00, '2026-03-18 10:21:11'),
(318, 'medicine_total', '2025-10-18', 130.00, '2026-03-18 10:21:11'),
(319, 'medicine_total', '2025-10-19', 184.00, '2026-03-18 10:21:11'),
(320, 'medicine_total', '2025-10-20', 137.00, '2026-03-18 10:21:11'),
(321, 'medicine_total', '2025-10-21', 523.00, '2026-03-18 10:21:11'),
(322, 'medicine_total', '2025-10-22', 197.00, '2026-03-18 10:21:11'),
(323, 'medicine_total', '2025-10-23', 318.00, '2026-03-18 10:21:11'),
(324, 'medicine_total', '2025-10-24', 95.00, '2026-03-18 10:21:11'),
(325, 'medicine_total', '2025-10-25', 277.00, '2026-03-18 10:21:11'),
(326, 'medicine_total', '2025-10-26', 316.00, '2026-03-18 10:21:11'),
(327, 'medicine_total', '2025-10-27', 305.00, '2026-03-18 10:21:11'),
(328, 'medicine_total', '2025-10-28', 198.00, '2026-03-18 10:21:11'),
(329, 'medicine_total', '2025-10-29', 391.00, '2026-03-18 10:21:11'),
(330, 'medicine_total', '2025-10-30', 199.00, '2026-03-18 10:21:11'),
(331, 'medicine_total', '2025-10-31', 355.00, '2026-03-18 10:21:11'),
(332, 'medicine_total', '2025-11-01', 216.00, '2026-03-18 10:21:11'),
(333, 'medicine_total', '2025-11-02', 622.00, '2026-03-18 10:21:11'),
(334, 'medicine_total', '2025-11-03', 317.00, '2026-03-18 10:21:11'),
(335, 'medicine_total', '2025-11-04', 227.00, '2026-03-18 10:21:11'),
(336, 'medicine_total', '2025-11-05', 204.00, '2026-03-18 10:21:11'),
(337, 'medicine_total', '2025-11-06', 233.00, '2026-03-18 10:21:11'),
(338, 'medicine_total', '2025-11-07', 44.00, '2026-03-18 10:21:11'),
(339, 'medicine_total', '2025-11-08', 426.00, '2026-03-18 10:21:11'),
(340, 'medicine_total', '2025-11-09', 207.00, '2026-03-18 10:21:11'),
(341, 'medicine_total', '2025-11-10', 86.00, '2026-03-18 10:21:11'),
(342, 'medicine_total', '2025-11-11', 111.00, '2026-03-18 10:21:11'),
(343, 'medicine_total', '2025-11-12', 241.00, '2026-03-18 10:21:11'),
(344, 'medicine_total', '2025-11-13', 340.00, '2026-03-18 10:21:11'),
(345, 'medicine_total', '2025-11-14', 121.00, '2026-03-18 10:21:11'),
(346, 'medicine_total', '2025-11-15', 198.00, '2026-03-18 10:21:11'),
(347, 'medicine_total', '2025-11-16', 88.00, '2026-03-18 10:21:11'),
(348, 'medicine_total', '2025-11-17', 191.00, '2026-03-18 10:21:11'),
(349, 'medicine_total', '2025-11-18', 221.00, '2026-03-18 10:21:11'),
(350, 'medicine_total', '2025-11-19', 409.00, '2026-03-18 10:21:11'),
(351, 'medicine_total', '2025-11-20', 268.00, '2026-03-18 10:21:11'),
(352, 'medicine_total', '2025-11-21', 112.00, '2026-03-18 10:21:11'),
(353, 'medicine_total', '2025-11-22', 157.00, '2026-03-18 10:21:11'),
(354, 'medicine_total', '2025-11-24', 248.00, '2026-03-18 10:21:11'),
(355, 'medicine_total', '2025-11-25', 380.00, '2026-03-18 10:21:11'),
(356, 'medicine_total', '2025-11-26', 65.00, '2026-03-18 10:21:11'),
(357, 'medicine_total', '2025-11-27', 502.00, '2026-03-18 10:21:11'),
(358, 'medicine_total', '2025-11-29', 296.00, '2026-03-18 10:21:11'),
(359, 'medicine_total', '2025-11-30', 396.00, '2026-03-18 10:21:11'),
(360, 'medicine_total', '2025-12-01', 153.00, '2026-03-18 10:21:11'),
(361, 'medicine_total', '2025-12-02', 268.00, '2026-03-18 10:21:11'),
(362, 'medicine_total', '2025-12-03', 53.00, '2026-03-18 10:21:11'),
(363, 'medicine_total', '2025-12-04', 352.00, '2026-03-18 10:21:11'),
(364, 'medicine_total', '2025-12-05', 290.00, '2026-03-18 10:21:11'),
(365, 'medicine_total', '2025-12-06', 88.00, '2026-03-18 10:21:11'),
(366, 'medicine_total', '2025-12-07', 78.00, '2026-03-18 10:21:11'),
(367, 'medicine_total', '2025-12-08', 230.00, '2026-03-18 10:21:11'),
(368, 'medicine_total', '2025-12-09', 179.00, '2026-03-18 10:21:11'),
(369, 'medicine_total', '2025-12-10', 266.00, '2026-03-18 10:21:11'),
(370, 'medicine_total', '2025-12-11', 31.00, '2026-03-18 10:21:11'),
(371, 'medicine_total', '2025-12-12', 5.00, '2026-03-18 10:21:11'),
(372, 'medicine_total', '2025-12-13', 176.00, '2026-03-18 10:21:11'),
(373, 'medicine_total', '2025-12-14', 163.00, '2026-03-18 10:21:11'),
(374, 'medicine_total', '2025-12-15', 345.00, '2026-03-18 10:21:11'),
(375, 'medicine_total', '2025-12-16', 277.00, '2026-03-18 10:21:11'),
(376, 'medicine_total', '2025-12-17', 289.00, '2026-03-18 10:21:11'),
(377, 'medicine_total', '2025-12-18', 37.00, '2026-03-18 10:21:11'),
(378, 'medicine_total', '2025-12-19', 110.00, '2026-03-18 10:21:11'),
(379, 'medicine_total', '2025-12-20', 147.00, '2026-03-18 10:21:11'),
(380, 'medicine_total', '2025-12-21', 374.00, '2026-03-18 10:21:11'),
(381, 'medicine_total', '2025-12-22', 225.00, '2026-03-18 10:21:11'),
(382, 'medicine_total', '2025-12-23', 165.00, '2026-03-18 10:21:11'),
(383, 'medicine_total', '2025-12-24', 212.00, '2026-03-18 10:21:11'),
(384, 'medicine_total', '2025-12-25', 363.00, '2026-03-18 10:21:11'),
(385, 'medicine_total', '2025-12-26', 175.00, '2026-03-18 10:21:11'),
(386, 'medicine_total', '2025-12-27', 288.00, '2026-03-18 10:21:11'),
(387, 'medicine_total', '2025-12-28', 409.00, '2026-03-18 10:21:11'),
(388, 'medicine_total', '2025-12-29', 180.00, '2026-03-18 10:21:11'),
(389, 'medicine_total', '2025-12-30', 311.00, '2026-03-18 10:21:11'),
(390, 'medicine_total', '2025-12-31', 258.00, '2026-03-18 10:21:11'),
(391, 'medicine_total', '2026-01-01', 263.00, '2026-03-18 10:21:11'),
(392, 'medicine_total', '2026-01-02', 305.00, '2026-03-18 10:21:11'),
(393, 'medicine_total', '2026-01-03', 306.00, '2026-03-18 10:21:11'),
(394, 'medicine_total', '2026-01-04', 247.00, '2026-03-18 10:21:11'),
(395, 'medicine_total', '2026-01-05', 111.00, '2026-03-18 10:21:11'),
(396, 'medicine_total', '2026-01-06', 182.00, '2026-03-18 10:21:11'),
(397, 'medicine_total', '2026-01-07', 105.00, '2026-03-18 10:21:11'),
(398, 'medicine_total', '2026-01-08', 368.00, '2026-03-18 10:21:11'),
(399, 'medicine_total', '2026-01-09', 193.00, '2026-03-18 10:21:11'),
(400, 'medicine_total', '2026-01-10', 258.00, '2026-03-18 10:21:11'),
(401, 'medicine_total', '2026-01-11', 146.00, '2026-03-18 10:21:11'),
(402, 'medicine_total', '2026-01-12', 84.00, '2026-03-18 10:21:11'),
(403, 'medicine_total', '2026-01-13', 59.00, '2026-03-18 10:21:11'),
(404, 'medicine_total', '2026-01-14', 191.00, '2026-03-18 10:21:11'),
(405, 'medicine_total', '2026-01-15', 223.00, '2026-03-18 10:21:11'),
(406, 'medicine_total', '2026-01-16', 233.00, '2026-03-18 10:21:11'),
(407, 'medicine_total', '2026-01-17', 38.00, '2026-03-18 10:21:11'),
(408, 'medicine_total', '2026-01-18', 65.00, '2026-03-18 10:21:11'),
(409, 'medicine_total', '2026-01-19', 92.00, '2026-03-18 10:21:11'),
(410, 'medicine_total', '2026-01-20', 174.00, '2026-03-18 10:21:11'),
(411, 'medicine_total', '2026-01-21', 136.00, '2026-03-18 10:21:11'),
(412, 'medicine_total', '2026-01-22', 348.00, '2026-03-18 10:21:11'),
(413, 'medicine_total', '2026-01-23', 488.00, '2026-03-18 10:21:11'),
(414, 'medicine_total', '2026-01-24', 118.00, '2026-03-18 10:21:11'),
(415, 'medicine_total', '2026-01-25', 211.00, '2026-03-18 10:21:11'),
(416, 'medicine_total', '2026-01-26', 184.00, '2026-03-18 10:21:11'),
(417, 'medicine_total', '2026-01-27', 84.00, '2026-03-18 10:21:11'),
(418, 'medicine_total', '2026-01-28', 333.00, '2026-03-18 10:21:11'),
(419, 'medicine_total', '2026-01-29', 81.00, '2026-03-18 10:21:11'),
(420, 'medicine_total', '2026-01-30', 706.00, '2026-03-18 10:21:11'),
(421, 'medicine_total', '2026-01-31', 136.00, '2026-03-18 10:21:11'),
(422, 'medicine_total', '2026-02-01', 291.00, '2026-03-18 10:21:11'),
(423, 'medicine_total', '2026-02-02', 80.00, '2026-03-18 10:21:11'),
(424, 'medicine_total', '2026-02-03', 283.00, '2026-03-18 10:21:11'),
(425, 'medicine_total', '2026-02-04', 112.00, '2026-03-18 10:21:11'),
(426, 'medicine_total', '2026-02-05', 149.00, '2026-03-18 10:21:11'),
(427, 'medicine_total', '2026-02-06', 362.00, '2026-03-18 10:21:11'),
(428, 'medicine_total', '2026-02-07', 25.00, '2026-03-18 10:21:11'),
(429, 'medicine_total', '2026-02-08', 384.00, '2026-03-18 10:21:11'),
(430, 'medicine_total', '2026-02-09', 510.00, '2026-03-18 10:21:11'),
(431, 'medicine_total', '2026-02-10', 181.00, '2026-03-18 10:21:11'),
(432, 'medicine_total', '2026-02-11', 43.00, '2026-03-18 10:21:11'),
(433, 'medicine_total', '2026-02-12', 149.00, '2026-03-18 10:21:11'),
(434, 'medicine_total', '2026-02-13', 19.00, '2026-03-18 10:21:11'),
(435, 'medicine_total', '2026-02-14', 466.00, '2026-03-18 10:21:11'),
(436, 'medicine_total', '2026-02-15', 208.00, '2026-03-18 10:21:11'),
(437, 'medicine_total', '2026-02-16', 89.00, '2026-03-18 10:21:11'),
(438, 'medicine_total', '2026-02-17', 261.00, '2026-03-18 10:21:11'),
(439, 'medicine_total', '2026-02-18', 135.00, '2026-03-18 10:21:11'),
(440, 'medicine_total', '2026-02-19', 76.00, '2026-03-18 10:21:11'),
(441, 'medicine_total', '2026-02-20', 346.00, '2026-03-18 10:21:11'),
(442, 'medicine_total', '2026-02-21', 417.00, '2026-03-18 10:21:11'),
(443, 'medicine_total', '2026-02-22', 442.00, '2026-03-18 10:21:11'),
(444, 'medicine_total', '2026-02-23', 165.00, '2026-03-18 10:21:11'),
(445, 'medicine_total', '2026-02-24', 125.00, '2026-03-18 10:21:11'),
(446, 'medicine_total', '2026-02-25', 153.00, '2026-03-18 10:21:11'),
(447, 'medicine_total', '2026-02-26', 195.00, '2026-03-18 10:21:11'),
(448, 'medicine_total', '2026-02-27', 131.00, '2026-03-18 10:21:11'),
(449, 'medicine_total', '2026-02-28', 123.00, '2026-03-18 10:21:11'),
(450, 'medicine_total', '2026-03-01', 359.00, '2026-03-18 10:21:11'),
(451, 'medicine_total', '2026-03-02', 93.00, '2026-03-18 10:21:11'),
(452, 'medicine_total', '2026-03-03', 81.00, '2026-03-18 10:21:11'),
(453, 'medicine_total', '2026-03-04', 96.00, '2026-03-18 10:21:11'),
(454, 'medicine_total', '2026-03-05', 68.00, '2026-03-18 10:21:11'),
(455, 'medicine_total', '2026-03-06', 322.00, '2026-03-18 10:21:11'),
(456, 'medicine_total', '2026-03-07', 41.00, '2026-03-18 10:21:11'),
(457, 'medicine_total', '2026-03-08', 267.00, '2026-03-18 10:21:11'),
(458, 'medicine_total', '2026-03-09', 288.00, '2026-03-18 10:21:11'),
(459, 'medicine_total', '2026-03-10', 393.00, '2026-03-18 10:21:11'),
(460, 'medicine_total', '2026-03-11', 91.00, '2026-03-18 10:21:11'),
(461, 'medicine_total', '2026-03-12', 99.00, '2026-03-18 10:21:11'),
(462, 'medicine_total', '2026-03-13', 182.00, '2026-03-18 10:21:11'),
(463, 'medicine_total', '2026-03-14', 201.00, '2026-03-18 10:21:11'),
(464, 'medicine_total', '2026-03-15', 281.00, '2026-03-18 10:21:11'),
(465, 'medicine_total', '2026-03-16', 341.00, '2026-03-18 10:21:11'),
(466, 'medicine_total', '2026-03-17', 151.00, '2026-03-18 10:21:11'),
(467, 'medicine_total', '2026-03-18', 121.00, '2026-03-18 10:21:11');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(120) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `status`, `created_at`, `updated_at`) VALUES
(2, 1, 'admin', '$2y$10$0mvIR7AT0u9y4q.FMLpg8.tJ77mPD7zrZCdAuTjl78r2g4UYteO22', 'System Admin', NULL, NULL, 'active', '2026-03-18 10:08:37', NULL),
(3, 3, 'bhw', '$2y$10$5jqcR2FrgaMsuOPxeWkofeIZdrzsKFSufD5InCtLJGQ2Hi.oQR/ua', 'Barangay Health Worker', NULL, NULL, 'active', '2026-03-18 10:08:37', '2026-09-09 19:22:27');

-- --------------------------------------------------------

--
-- Table structure for table `vaccines`
--

CREATE TABLE `vaccines` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(40) NOT NULL,
  `recommended_min_age_months` int(10) UNSIGNED DEFAULT NULL,
  `recommended_max_age_months` int(10) UNSIGNED DEFAULT NULL,
  `doses_required` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `vaccines`
--

INSERT INTO `vaccines` (`id`, `name`, `code`, `recommended_min_age_months`, `recommended_max_age_months`, `doses_required`, `created_at`) VALUES
(1, 'BCG', 'BCG', 0, 1, 1, '2026-03-18 10:08:37'),
(2, 'Hepatitis B', 'HEPB', 0, 1, 1, '2026-03-18 10:08:37'),
(3, 'Pentavalent', 'PENTA', 2, 6, 3, '2026-03-18 10:08:37'),
(4, 'Oral Polio Vaccine', 'OPV', 2, 6, 3, '2026-03-18 10:08:37'),
(5, 'Measles, Mumps, Rubella', 'MMR', 9, 24, 2, '2026-03-18 10:08:37'),
(6, 'Inactivated Polio Vaccine', 'IPV', 3, 9, 2, '2026-09-21 18:43:31'),
(7, 'Pneumococcal Conjugate Vaccine', 'PCV', 2, 6, 3, '2026-09-21 18:43:31'),
(8, 'Measles-Rubella', 'MR', 9, 12, 1, '2026-09-21 18:43:31'),
(9, 'Tetanus Diphtheria', 'TD', 12, 180, 1, '2026-09-21 18:43:31'),
(10, 'Human Papillomavirus', 'HPV', 108, 168, 2, '2026-09-21 18:43:31');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `patient_id` bigint(20) UNSIGNED NOT NULL,
  `visit_datetime` datetime NOT NULL,
  `visit_type` enum('general','immunization','maternal','other') NOT NULL DEFAULT 'general',
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `patient_id`, `visit_datetime`, `visit_type`, `reason`, `notes`, `recorded_by`, `created_at`) VALUES
(1, 12, '2025-12-18 14:38:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(2, 42, '2025-12-18 14:27:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(3, 30, '2025-12-18 10:26:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(4, 46, '2025-12-18 14:45:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(5, 11, '2025-12-18 11:11:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(6, 2, '2025-12-18 09:00:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(7, 30, '2025-12-18 14:01:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(8, 35, '2025-12-18 08:08:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(9, 18, '2025-12-18 14:30:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(10, 20, '2025-12-18 14:02:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(11, 21, '2025-12-19 14:37:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(12, 27, '2025-12-19 16:44:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(13, 18, '2025-12-19 15:09:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(14, 40, '2025-12-19 10:57:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(15, 14, '2025-12-19 12:06:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(16, 8, '2025-12-19 15:55:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(17, 51, '2025-12-19 08:05:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(18, 39, '2025-12-19 15:45:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(19, 40, '2025-12-19 11:23:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(20, 15, '2025-12-19 09:41:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(21, 9, '2025-12-20 14:24:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(22, 47, '2025-12-20 16:16:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(23, 2, '2025-12-20 16:03:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(24, 2, '2025-12-20 11:21:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(25, 4, '2025-12-20 14:54:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(26, 45, '2025-12-20 09:41:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(27, 45, '2025-12-20 11:47:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(28, 51, '2025-12-20 13:23:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(29, 36, '2025-12-20 08:23:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(30, 15, '2025-12-20 11:34:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(31, 19, '2025-12-20 09:38:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(32, 33, '2025-12-20 13:24:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(33, 42, '2025-12-20 13:12:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(34, 10, '2025-12-20 08:05:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(35, 44, '2025-12-20 15:28:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(36, 23, '2025-12-20 16:06:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(37, 32, '2025-12-20 09:55:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(38, 33, '2025-12-20 16:18:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(39, 42, '2025-12-20 15:26:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(40, 48, '2025-12-20 16:17:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(41, 2, '2025-12-20 10:47:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(42, 43, '2025-12-20 13:45:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(43, 28, '2025-12-20 14:58:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(44, 36, '2025-12-20 12:17:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(45, 31, '2025-12-21 11:09:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(46, 33, '2025-12-21 14:24:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(47, 39, '2025-12-21 10:44:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(48, 36, '2025-12-21 16:14:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(49, 24, '2025-12-21 08:26:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(50, 6, '2025-12-21 15:20:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(51, 33, '2025-12-21 11:57:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(52, 38, '2025-12-21 10:12:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(53, 22, '2025-12-21 10:27:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(54, 36, '2025-12-21 09:44:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(55, 6, '2025-12-21 13:46:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(56, 10, '2025-12-21 09:23:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(57, 46, '2025-12-22 08:41:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(58, 51, '2025-12-22 14:27:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(59, 21, '2025-12-22 16:22:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(60, 30, '2025-12-22 08:01:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(61, 7, '2025-12-22 15:56:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(62, 10, '2025-12-22 09:45:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(63, 9, '2025-12-22 15:54:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(64, 27, '2025-12-22 14:18:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(65, 23, '2025-12-22 15:32:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(66, 41, '2025-12-22 10:00:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(67, 9, '2025-12-22 11:54:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(68, 18, '2025-12-22 14:13:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(69, 21, '2025-12-22 08:43:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(70, 23, '2025-12-22 15:50:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(71, 8, '2025-12-23 11:32:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(72, 23, '2025-12-23 15:15:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(73, 15, '2025-12-23 11:49:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(74, 19, '2025-12-23 14:53:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(75, 24, '2025-12-23 11:36:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(76, 28, '2025-12-23 11:57:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(77, 12, '2025-12-23 11:54:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(78, 3, '2025-12-23 16:45:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(79, 20, '2025-12-23 09:31:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(80, 32, '2025-12-23 13:04:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(81, 45, '2025-12-23 10:12:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(82, 48, '2025-12-23 14:51:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(83, 19, '2025-12-23 16:55:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(84, 16, '2025-12-23 15:54:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(85, 19, '2025-12-23 09:26:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(86, 15, '2025-12-23 09:05:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(87, 48, '2025-12-23 13:12:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(88, 45, '2025-12-23 09:07:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(89, 42, '2025-12-23 16:34:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(90, 21, '2025-12-23 08:13:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(91, 2, '2025-12-23 16:43:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(92, 35, '2025-12-23 12:28:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(93, 49, '2025-12-23 15:31:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(94, 33, '2025-12-23 12:58:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(95, 2, '2025-12-23 10:42:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(96, 3, '2025-12-24 09:36:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(97, 10, '2025-12-24 08:35:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(98, 48, '2025-12-24 13:46:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(99, 4, '2025-12-24 12:25:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(100, 7, '2025-12-24 13:07:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(101, 4, '2025-12-25 16:59:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(102, 49, '2025-12-25 15:16:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(103, 14, '2025-12-25 15:30:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(104, 44, '2025-12-25 08:00:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(105, 22, '2025-12-25 10:55:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(106, 19, '2025-12-25 11:21:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(107, 15, '2025-12-25 16:23:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(108, 20, '2025-12-25 08:21:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(109, 47, '2025-12-25 16:19:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(110, 29, '2025-12-25 11:33:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(111, 46, '2025-12-25 11:32:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(112, 29, '2025-12-25 16:04:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(113, 30, '2025-12-25 13:33:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(114, 29, '2025-12-25 13:06:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(115, 42, '2025-12-25 09:22:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(116, 6, '2025-12-25 14:36:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(117, 21, '2025-12-25 13:01:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(118, 15, '2025-12-25 16:41:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(119, 32, '2025-12-25 10:26:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(120, 49, '2025-12-25 14:58:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(121, 24, '2025-12-25 08:53:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(122, 17, '2025-12-26 15:41:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(123, 25, '2025-12-26 11:38:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(124, 34, '2025-12-26 14:32:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(125, 11, '2025-12-26 15:37:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(126, 42, '2025-12-26 11:22:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(127, 40, '2025-12-26 12:07:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(128, 28, '2025-12-26 15:49:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(129, 14, '2025-12-26 12:14:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(130, 12, '2025-12-26 10:59:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(131, 47, '2025-12-26 15:37:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(132, 37, '2025-12-26 11:06:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(133, 46, '2025-12-26 16:16:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(134, 12, '2025-12-26 15:00:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(135, 18, '2025-12-26 12:07:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(136, 7, '2025-12-26 08:41:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(137, 20, '2025-12-26 10:16:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(138, 40, '2025-12-26 09:55:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(139, 27, '2025-12-26 09:50:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(140, 49, '2025-12-26 10:36:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(141, 22, '2025-12-27 13:47:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(142, 37, '2025-12-27 16:21:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(143, 44, '2025-12-27 14:49:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(144, 43, '2025-12-27 14:58:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(145, 39, '2025-12-27 16:00:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(146, 21, '2025-12-27 14:01:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(147, 27, '2025-12-27 10:43:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(148, 19, '2025-12-27 08:26:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(149, 9, '2025-12-28 13:51:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(150, 2, '2025-12-28 09:29:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(151, 26, '2025-12-28 16:52:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(152, 30, '2025-12-28 12:14:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(153, 22, '2025-12-28 11:35:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(154, 31, '2025-12-28 11:18:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(155, 40, '2025-12-28 12:37:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(156, 35, '2025-12-29 11:39:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(157, 1, '2025-12-29 08:45:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(158, 46, '2025-12-29 15:34:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(159, 31, '2025-12-29 11:23:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(160, 44, '2025-12-29 12:37:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(161, 49, '2025-12-29 14:12:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(162, 6, '2025-12-29 10:50:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(163, 3, '2025-12-29 08:09:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(164, 41, '2025-12-29 10:56:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(165, 33, '2025-12-29 09:06:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(166, 3, '2025-12-29 08:17:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(167, 16, '2025-12-29 13:25:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(168, 26, '2025-12-29 08:23:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(169, 47, '2025-12-29 16:32:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(170, 22, '2025-12-29 14:59:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(171, 25, '2025-12-29 10:41:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(172, 24, '2025-12-29 11:56:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(173, 19, '2025-12-29 14:23:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(174, 13, '2025-12-29 10:26:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(175, 36, '2025-12-29 09:09:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(176, 10, '2025-12-29 10:35:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(177, 45, '2025-12-29 14:56:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(178, 43, '2025-12-29 11:07:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(179, 43, '2025-12-29 16:58:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(180, 45, '2025-12-29 13:54:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(181, 22, '2025-12-30 10:10:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(182, 17, '2025-12-30 12:02:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(183, 4, '2025-12-30 10:31:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(184, 39, '2025-12-30 09:45:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(185, 34, '2025-12-30 08:16:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(186, 37, '2025-12-30 11:55:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(187, 46, '2025-12-30 12:49:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(188, 11, '2025-12-30 08:15:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(189, 42, '2025-12-30 08:37:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(190, 1, '2025-12-30 08:41:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(191, 47, '2025-12-31 16:01:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(192, 44, '2025-12-31 10:00:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(193, 26, '2025-12-31 11:55:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(194, 31, '2025-12-31 15:31:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(195, 41, '2025-12-31 09:01:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(196, 18, '2026-01-01 11:05:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(197, 32, '2026-01-01 11:23:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(198, 26, '2026-01-01 08:02:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(199, 1, '2026-01-01 16:38:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(200, 20, '2026-01-01 12:54:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(201, 29, '2026-01-01 13:05:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(202, 19, '2026-01-01 10:53:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(203, 18, '2026-01-01 10:09:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(204, 34, '2026-01-01 14:18:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(205, 49, '2026-01-01 11:43:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(206, 39, '2026-01-01 11:07:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(207, 16, '2026-01-01 15:13:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(208, 15, '2026-01-01 10:56:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(209, 44, '2026-01-01 15:32:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(210, 12, '2026-01-01 13:45:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(211, 35, '2026-01-01 13:14:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(212, 7, '2026-01-02 11:09:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(213, 47, '2026-01-02 16:34:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(214, 17, '2026-01-02 09:24:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(215, 16, '2026-01-02 14:45:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(216, 22, '2026-01-02 10:47:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(217, 18, '2026-01-02 10:11:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(218, 22, '2026-01-02 09:17:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(219, 8, '2026-01-02 08:16:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(220, 27, '2026-01-02 09:51:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(221, 25, '2026-01-02 14:04:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(222, 22, '2026-01-02 12:44:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(223, 24, '2026-01-02 09:07:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(224, 8, '2026-01-02 11:06:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(225, 29, '2026-01-02 14:22:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(226, 1, '2026-01-02 13:12:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(227, 27, '2026-01-02 09:43:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(228, 19, '2026-01-02 15:29:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(229, 6, '2026-01-02 11:37:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(230, 44, '2026-01-02 12:14:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(231, 51, '2026-01-02 09:47:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(232, 6, '2026-01-02 08:57:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(233, 47, '2026-01-02 12:11:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(234, 29, '2026-01-02 16:44:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(235, 23, '2026-01-02 14:03:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(236, 19, '2026-01-03 14:31:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(237, 29, '2026-01-03 14:17:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(238, 7, '2026-01-03 15:05:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(239, 29, '2026-01-03 12:55:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(240, 45, '2026-01-03 14:56:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(241, 15, '2026-01-03 14:00:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(242, 5, '2026-01-03 10:35:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(243, 30, '2026-01-03 14:08:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(244, 29, '2026-01-03 16:29:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(245, 27, '2026-01-03 09:39:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(246, 35, '2026-01-03 10:52:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(247, 43, '2026-01-03 08:02:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(248, 26, '2026-01-03 08:10:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(249, 44, '2026-01-03 09:17:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(250, 10, '2026-01-03 11:27:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(251, 8, '2026-01-03 10:43:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(252, 45, '2026-01-03 08:23:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(253, 31, '2026-01-03 14:10:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(254, 14, '2026-01-03 09:32:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(255, 21, '2026-01-03 08:19:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(256, 25, '2026-01-03 15:38:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(257, 32, '2026-01-03 09:01:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(258, 42, '2026-01-04 11:13:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(259, 46, '2026-01-04 14:58:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(260, 1, '2026-01-04 12:12:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(261, 9, '2026-01-04 11:11:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(262, 24, '2026-01-04 08:05:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(263, 39, '2026-01-04 08:36:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(264, 5, '2026-01-04 08:20:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(265, 38, '2026-01-04 13:19:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(266, 6, '2026-01-04 15:57:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(267, 10, '2026-01-04 15:49:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(268, 25, '2026-01-04 11:20:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(269, 42, '2026-01-04 09:22:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(270, 10, '2026-01-04 14:39:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(271, 12, '2026-01-04 11:30:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(272, 4, '2026-01-04 10:38:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(273, 21, '2026-01-04 11:53:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(274, 33, '2026-01-05 14:17:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(275, 21, '2026-01-05 08:28:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(276, 8, '2026-01-05 12:20:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(277, 31, '2026-01-05 12:41:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(278, 42, '2026-01-05 14:55:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(279, 39, '2026-01-05 11:34:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(280, 49, '2026-01-05 15:52:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(281, 23, '2026-01-05 11:25:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(282, 46, '2026-01-05 09:46:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(283, 24, '2026-01-05 14:57:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(284, 27, '2026-01-06 16:19:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(285, 23, '2026-01-06 16:58:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(286, 24, '2026-01-06 14:59:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(287, 3, '2026-01-06 09:47:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(288, 6, '2026-01-06 14:55:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(289, 15, '2026-01-06 16:06:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(290, 17, '2026-01-06 08:38:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(291, 31, '2026-01-06 14:27:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(292, 51, '2026-01-06 09:34:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(293, 13, '2026-01-06 12:09:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(294, 9, '2026-01-06 11:27:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(295, 4, '2026-01-06 08:52:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(296, 38, '2026-01-06 09:41:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(297, 13, '2026-01-06 16:40:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(298, 22, '2026-01-06 08:43:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(299, 6, '2026-01-06 16:55:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(300, 46, '2026-01-06 11:11:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(301, 15, '2026-01-06 13:44:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(302, 29, '2026-01-06 09:00:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(303, 10, '2026-01-06 16:04:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(304, 9, '2026-01-07 14:30:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(305, 19, '2026-01-07 09:16:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(306, 20, '2026-01-07 14:42:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(307, 5, '2026-01-07 10:22:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(308, 47, '2026-01-07 08:57:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(309, 21, '2026-01-07 11:32:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(310, 33, '2026-01-07 09:04:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(311, 1, '2026-01-07 12:51:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(312, 8, '2026-01-07 12:23:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(313, 13, '2026-01-07 09:59:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(314, 8, '2026-01-07 11:11:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(315, 20, '2026-01-07 08:14:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(316, 42, '2026-01-07 12:22:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(317, 16, '2026-01-07 16:15:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(318, 12, '2026-01-07 10:03:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(319, 37, '2026-01-07 10:41:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(320, 40, '2026-01-07 08:12:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(321, 15, '2026-01-08 14:09:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(322, 18, '2026-01-08 12:30:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(323, 24, '2026-01-08 13:55:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(324, 21, '2026-01-08 16:46:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(325, 32, '2026-01-08 14:12:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(326, 1, '2026-01-08 10:31:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(327, 21, '2026-01-08 12:23:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(328, 34, '2026-01-08 14:32:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(329, 18, '2026-01-08 08:06:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(330, 44, '2026-01-08 12:18:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(331, 38, '2026-01-08 09:07:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(332, 7, '2026-01-08 09:28:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(333, 2, '2026-01-08 11:20:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(334, 48, '2026-01-08 14:16:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(335, 1, '2026-01-08 10:25:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(336, 42, '2026-01-08 08:58:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(337, 31, '2026-01-08 16:11:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(338, 45, '2026-01-08 09:18:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(339, 51, '2026-01-08 16:11:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(340, 1, '2026-01-08 11:40:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(341, 49, '2026-01-09 16:12:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(342, 10, '2026-01-09 16:13:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(343, 39, '2026-01-09 11:05:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(344, 11, '2026-01-09 12:30:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(345, 43, '2026-01-09 11:41:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(346, 49, '2026-01-09 12:30:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(347, 35, '2026-01-09 10:26:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(348, 42, '2026-01-09 15:07:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(349, 32, '2026-01-09 16:47:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(350, 34, '2026-01-09 15:34:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(351, 29, '2026-01-09 09:31:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(352, 7, '2026-01-09 14:27:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(353, 3, '2026-01-09 14:19:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(354, 46, '2026-01-09 08:01:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(355, 18, '2026-01-09 09:26:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(356, 22, '2026-01-09 09:48:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(357, 9, '2026-01-09 08:35:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(358, 15, '2026-01-09 08:28:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(359, 17, '2026-01-09 09:38:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(360, 47, '2026-01-09 09:06:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(361, 22, '2026-01-09 11:37:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(362, 20, '2026-01-09 14:24:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(363, 38, '2026-01-09 10:37:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(364, 30, '2026-01-09 13:18:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(365, 18, '2026-01-09 09:39:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(366, 43, '2026-01-10 12:40:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(367, 21, '2026-01-10 11:10:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(368, 46, '2026-01-10 14:58:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(369, 20, '2026-01-10 16:40:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(370, 23, '2026-01-10 11:01:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(371, 48, '2026-01-10 12:16:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(372, 5, '2026-01-10 12:13:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(373, 21, '2026-01-10 09:25:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(374, 17, '2026-01-10 11:39:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(375, 35, '2026-01-10 14:16:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(376, 42, '2026-01-10 13:57:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(377, 47, '2026-01-10 08:43:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(378, 22, '2026-01-10 10:24:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(379, 39, '2026-01-10 14:08:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(380, 34, '2026-01-10 09:30:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(381, 10, '2026-01-10 14:03:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(382, 30, '2026-01-10 12:58:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(383, 41, '2026-01-10 11:52:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(384, 24, '2026-01-10 08:03:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(385, 22, '2026-01-10 14:37:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(386, 36, '2026-01-10 10:35:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(387, 16, '2026-01-10 13:53:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(388, 30, '2026-01-10 08:09:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(389, 46, '2026-01-11 10:27:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(390, 2, '2026-01-11 15:36:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(391, 45, '2026-01-11 14:07:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(392, 31, '2026-01-11 14:39:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(393, 8, '2026-01-11 16:07:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(394, 30, '2026-01-11 15:27:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(395, 20, '2026-01-11 15:34:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(396, 42, '2026-01-11 13:56:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(397, 6, '2026-01-11 08:13:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(398, 33, '2026-01-11 15:11:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(399, 20, '2026-01-11 11:39:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(400, 36, '2026-01-11 13:33:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(401, 8, '2026-01-11 16:43:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(402, 13, '2026-01-11 08:15:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(403, 29, '2026-01-11 12:36:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(404, 23, '2026-01-11 09:25:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(405, 41, '2026-01-11 12:38:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(406, 51, '2026-01-11 14:48:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(407, 6, '2026-01-11 14:52:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(408, 27, '2026-01-11 12:03:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(409, 23, '2026-01-11 13:38:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(410, 47, '2026-01-11 10:42:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(411, 23, '2026-01-11 16:52:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(412, 27, '2026-01-11 12:12:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(413, 35, '2026-01-11 11:57:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(414, 33, '2026-01-12 13:21:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(415, 23, '2026-01-12 12:07:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(416, 9, '2026-01-12 16:36:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(417, 47, '2026-01-12 09:05:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(418, 8, '2026-01-12 11:10:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03');
INSERT INTO `visits` (`id`, `patient_id`, `visit_datetime`, `visit_type`, `reason`, `notes`, `recorded_by`, `created_at`) VALUES
(419, 37, '2026-01-12 10:16:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(420, 18, '2026-01-12 09:36:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(421, 3, '2026-01-13 08:47:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(422, 1, '2026-01-13 14:17:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(423, 33, '2026-01-13 08:13:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(424, 32, '2026-01-13 12:56:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(425, 21, '2026-01-13 12:31:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(426, 37, '2026-01-13 09:02:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(427, 34, '2026-01-13 15:37:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(428, 6, '2026-01-13 13:08:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(429, 30, '2026-01-13 11:00:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(430, 34, '2026-01-13 11:58:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(431, 22, '2026-01-13 10:24:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(432, 4, '2026-01-13 15:11:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(433, 42, '2026-01-13 11:08:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(434, 48, '2026-01-13 10:40:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(435, 25, '2026-01-14 12:24:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(436, 45, '2026-01-14 14:10:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(437, 2, '2026-01-14 12:53:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(438, 1, '2026-01-14 09:47:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(439, 21, '2026-01-14 15:02:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(440, 27, '2026-01-14 14:28:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(441, 45, '2026-01-14 11:08:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(442, 12, '2026-01-14 10:14:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(443, 45, '2026-01-14 15:06:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(444, 25, '2026-01-14 13:55:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(445, 28, '2026-01-14 10:22:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(446, 33, '2026-01-14 14:01:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(447, 49, '2026-01-15 09:38:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(448, 31, '2026-01-15 13:56:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(449, 29, '2026-01-15 10:59:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(450, 27, '2026-01-15 08:55:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(451, 4, '2026-01-15 13:47:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(452, 25, '2026-01-15 12:34:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(453, 47, '2026-01-15 12:15:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(454, 37, '2026-01-15 14:10:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(455, 31, '2026-01-15 12:28:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(456, 36, '2026-01-15 16:00:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(457, 38, '2026-01-15 15:34:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(458, 48, '2026-01-15 11:45:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(459, 6, '2026-01-15 09:10:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(460, 24, '2026-01-15 13:31:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(461, 11, '2026-01-15 08:32:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(462, 31, '2026-01-15 13:26:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(463, 36, '2026-01-15 13:30:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(464, 2, '2026-01-15 08:38:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(465, 37, '2026-01-15 09:07:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(466, 23, '2026-01-15 10:16:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(467, 47, '2026-01-15 13:02:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(468, 36, '2026-01-15 11:12:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(469, 44, '2026-01-15 16:45:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(470, 16, '2026-01-16 16:32:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(471, 10, '2026-01-16 15:28:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(472, 25, '2026-01-16 14:31:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(473, 25, '2026-01-16 12:23:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(474, 21, '2026-01-16 08:46:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(475, 6, '2026-01-17 09:55:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(476, 11, '2026-01-17 10:11:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(477, 49, '2026-01-17 15:40:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(478, 37, '2026-01-17 08:23:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(479, 19, '2026-01-17 12:57:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(480, 28, '2026-01-17 14:33:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(481, 48, '2026-01-17 14:39:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(482, 16, '2026-01-17 08:39:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(483, 35, '2026-01-17 11:18:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(484, 16, '2026-01-17 11:54:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(485, 15, '2026-01-17 15:36:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(486, 2, '2026-01-17 10:18:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(487, 7, '2026-01-17 15:35:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(488, 42, '2026-01-17 09:22:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(489, 18, '2026-01-17 11:40:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(490, 48, '2026-01-17 15:30:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(491, 28, '2026-01-17 09:28:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(492, 3, '2026-01-17 15:45:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(493, 8, '2026-01-17 09:00:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(494, 51, '2026-01-17 11:27:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(495, 12, '2026-01-17 10:44:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(496, 38, '2026-01-17 09:17:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(497, 11, '2026-01-17 16:06:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(498, 15, '2026-01-17 13:15:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(499, 26, '2026-01-17 15:53:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(500, 16, '2026-01-18 11:27:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(501, 36, '2026-01-18 10:49:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(502, 17, '2026-01-18 14:37:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(503, 11, '2026-01-18 16:32:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(504, 23, '2026-01-18 09:43:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(505, 23, '2026-01-18 10:48:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(506, 29, '2026-01-18 14:50:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(507, 4, '2026-01-18 10:41:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(508, 31, '2026-01-18 12:12:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(509, 26, '2026-01-18 16:03:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(510, 44, '2026-01-18 15:38:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(511, 3, '2026-01-18 15:54:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(512, 33, '2026-01-18 12:04:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(513, 9, '2026-01-18 13:49:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(514, 7, '2026-01-18 09:24:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(515, 16, '2026-01-19 15:31:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(516, 7, '2026-01-19 12:04:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(517, 6, '2026-01-19 12:16:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(518, 33, '2026-01-19 12:12:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(519, 42, '2026-01-19 11:31:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(520, 4, '2026-01-19 11:57:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(521, 25, '2026-01-19 08:28:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(522, 30, '2026-01-19 09:49:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(523, 45, '2026-01-19 11:01:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(524, 43, '2026-01-19 15:26:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(525, 29, '2026-01-19 15:52:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(526, 15, '2026-01-19 12:05:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(527, 21, '2026-01-19 09:11:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(528, 46, '2026-01-19 14:33:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(529, 33, '2026-01-19 13:39:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(530, 5, '2026-01-19 10:02:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(531, 30, '2026-01-19 16:31:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(532, 10, '2026-01-19 13:06:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(533, 47, '2026-01-19 12:19:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(534, 18, '2026-01-19 11:11:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(535, 47, '2026-01-19 12:22:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(536, 35, '2026-01-19 14:27:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(537, 48, '2026-01-19 10:43:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(538, 10, '2026-01-19 11:09:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(539, 24, '2026-01-19 08:55:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(540, 17, '2026-01-20 15:00:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(541, 45, '2026-01-20 08:15:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(542, 45, '2026-01-20 10:41:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(543, 11, '2026-01-20 16:33:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(544, 2, '2026-01-20 15:06:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(545, 41, '2026-01-20 15:00:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(546, 7, '2026-01-20 09:25:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(547, 8, '2026-01-20 12:38:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(548, 31, '2026-01-20 08:30:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(549, 2, '2026-01-20 14:38:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(550, 43, '2026-01-21 11:02:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(551, 40, '2026-01-21 08:14:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(552, 24, '2026-01-21 14:17:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(553, 3, '2026-01-21 16:44:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(554, 17, '2026-01-21 14:14:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(555, 46, '2026-01-21 10:13:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(556, 32, '2026-01-21 15:39:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(557, 37, '2026-01-21 13:33:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(558, 2, '2026-01-21 11:48:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(559, 3, '2026-01-21 11:15:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(560, 24, '2026-01-21 13:58:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(561, 5, '2026-01-21 11:54:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(562, 40, '2026-01-21 15:24:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(563, 7, '2026-01-21 09:39:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(564, 47, '2026-01-21 09:04:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(565, 3, '2026-01-21 09:22:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(566, 27, '2026-01-21 10:59:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(567, 2, '2026-01-21 09:59:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(568, 22, '2026-01-21 09:25:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(569, 16, '2026-01-21 15:53:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(570, 38, '2026-01-21 12:40:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(571, 38, '2026-01-21 10:24:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(572, 48, '2026-01-21 15:46:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(573, 10, '2026-01-22 14:43:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(574, 35, '2026-01-22 14:59:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(575, 38, '2026-01-22 09:55:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(576, 14, '2026-01-22 10:13:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(577, 3, '2026-01-22 16:51:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(578, 26, '2026-01-23 08:33:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(579, 32, '2026-01-23 13:39:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(580, 2, '2026-01-23 08:26:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(581, 25, '2026-01-23 12:37:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(582, 11, '2026-01-23 14:54:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(583, 28, '2026-01-23 14:05:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(584, 40, '2026-01-23 13:59:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(585, 16, '2026-01-23 14:05:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(586, 14, '2026-01-23 08:21:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(587, 19, '2026-01-23 13:41:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(588, 43, '2026-01-24 16:35:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(589, 28, '2026-01-24 13:20:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(590, 48, '2026-01-24 09:02:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(591, 23, '2026-01-24 14:25:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(592, 13, '2026-01-24 10:05:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(593, 20, '2026-01-24 15:50:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(594, 35, '2026-01-24 16:19:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(595, 40, '2026-01-24 14:42:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(596, 18, '2026-01-24 13:32:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(597, 21, '2026-01-24 16:10:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(598, 13, '2026-01-24 10:53:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(599, 4, '2026-01-24 12:02:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(600, 13, '2026-01-24 11:13:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(601, 14, '2026-01-24 14:32:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(602, 11, '2026-01-24 15:48:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(603, 10, '2026-01-24 12:59:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(604, 40, '2026-01-24 12:41:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(605, 38, '2026-01-24 12:26:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(606, 40, '2026-01-24 14:13:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(607, 40, '2026-01-25 16:33:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(608, 33, '2026-01-25 10:04:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(609, 39, '2026-01-25 09:00:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(610, 46, '2026-01-25 13:46:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(611, 48, '2026-01-25 16:31:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(612, 27, '2026-01-25 16:51:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(613, 5, '2026-01-25 15:31:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(614, 10, '2026-01-26 08:37:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(615, 46, '2026-01-26 10:55:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(616, 12, '2026-01-26 12:04:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(617, 3, '2026-01-26 10:41:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(618, 10, '2026-01-26 09:51:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(619, 6, '2026-01-26 11:54:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(620, 38, '2026-01-26 16:13:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(621, 35, '2026-01-26 11:43:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(622, 47, '2026-01-26 14:49:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(623, 14, '2026-01-26 14:41:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(624, 51, '2026-01-26 09:18:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(625, 29, '2026-01-26 12:40:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(626, 21, '2026-01-26 16:44:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(627, 39, '2026-01-26 15:00:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(628, 26, '2026-01-26 11:56:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(629, 16, '2026-01-26 11:39:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(630, 18, '2026-01-26 08:46:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(631, 13, '2026-01-26 11:28:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(632, 21, '2026-01-26 14:39:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(633, 21, '2026-01-26 11:12:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(634, 42, '2026-01-26 11:58:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(635, 5, '2026-01-26 11:33:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(636, 29, '2026-01-26 14:00:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(637, 5, '2026-01-26 16:45:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(638, 1, '2026-01-26 16:26:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(639, 4, '2026-01-27 14:09:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(640, 30, '2026-01-27 11:00:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(641, 49, '2026-01-27 14:41:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(642, 42, '2026-01-27 16:05:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(643, 31, '2026-01-27 10:49:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(644, 11, '2026-01-27 08:19:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(645, 43, '2026-01-27 11:42:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(646, 26, '2026-01-27 11:29:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(647, 9, '2026-01-27 15:04:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(648, 14, '2026-01-27 12:08:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(649, 25, '2026-01-27 09:03:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(650, 31, '2026-01-27 16:47:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(651, 39, '2026-01-27 09:06:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(652, 29, '2026-01-27 12:05:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(653, 41, '2026-01-27 08:10:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(654, 16, '2026-01-27 12:22:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(655, 28, '2026-01-28 14:50:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(656, 24, '2026-01-28 15:55:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(657, 6, '2026-01-28 14:11:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(658, 38, '2026-01-28 10:24:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(659, 15, '2026-01-28 14:55:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(660, 1, '2026-01-28 11:17:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(661, 41, '2026-01-28 13:41:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(662, 11, '2026-01-28 14:49:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(663, 42, '2026-01-28 16:51:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(664, 42, '2026-01-28 08:57:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(665, 28, '2026-01-28 13:20:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(666, 48, '2026-01-28 13:39:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(667, 5, '2026-01-28 15:39:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(668, 39, '2026-01-28 13:12:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(669, 36, '2026-01-29 13:40:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(670, 30, '2026-01-29 14:42:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(671, 46, '2026-01-29 16:58:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(672, 16, '2026-01-29 10:38:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(673, 13, '2026-01-29 10:13:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(674, 23, '2026-01-29 15:54:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(675, 4, '2026-01-29 10:04:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(676, 15, '2026-01-29 08:50:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(677, 13, '2026-01-29 12:53:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(678, 18, '2026-01-29 14:51:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(679, 4, '2026-01-29 15:59:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(680, 46, '2026-01-29 11:11:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(681, 41, '2026-01-29 09:18:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(682, 11, '2026-01-29 09:06:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(683, 49, '2026-01-29 09:59:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(684, 31, '2026-01-29 16:03:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(685, 18, '2026-01-29 15:12:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(686, 9, '2026-01-29 12:24:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(687, 37, '2026-01-30 12:54:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(688, 31, '2026-01-30 12:20:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(689, 42, '2026-01-30 15:57:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(690, 41, '2026-01-30 14:54:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(691, 9, '2026-01-30 10:40:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(692, 33, '2026-01-30 14:40:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(693, 9, '2026-01-30 12:53:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(694, 5, '2026-01-30 08:01:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(695, 45, '2026-01-30 09:08:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(696, 12, '2026-01-31 14:31:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(697, 25, '2026-01-31 10:23:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(698, 6, '2026-01-31 16:04:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(699, 48, '2026-01-31 08:41:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(700, 30, '2026-01-31 14:31:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(701, 30, '2026-01-31 16:32:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(702, 21, '2026-01-31 12:43:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(703, 19, '2026-01-31 08:05:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(704, 42, '2026-01-31 08:08:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(705, 51, '2026-01-31 15:00:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(706, 9, '2026-01-31 09:01:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(707, 8, '2026-02-01 16:01:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(708, 26, '2026-02-01 13:48:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(709, 33, '2026-02-01 14:33:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(710, 43, '2026-02-01 11:41:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(711, 32, '2026-02-01 16:14:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(712, 47, '2026-02-01 08:53:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(713, 4, '2026-02-01 11:15:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(714, 30, '2026-02-01 11:58:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(715, 47, '2026-02-01 09:10:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(716, 42, '2026-02-01 14:27:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(717, 39, '2026-02-01 11:49:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(718, 44, '2026-02-01 09:19:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(719, 36, '2026-02-01 11:34:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(720, 16, '2026-02-01 11:29:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(721, 3, '2026-02-02 12:17:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(722, 4, '2026-02-02 16:02:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(723, 38, '2026-02-02 10:00:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(724, 2, '2026-02-02 11:05:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(725, 9, '2026-02-02 14:00:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(726, 12, '2026-02-02 08:46:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(727, 29, '2026-02-02 14:57:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(728, 49, '2026-02-02 13:26:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(729, 13, '2026-02-03 11:44:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(730, 44, '2026-02-03 11:54:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(731, 51, '2026-02-03 15:41:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(732, 38, '2026-02-03 14:55:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(733, 13, '2026-02-03 08:37:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(734, 35, '2026-02-03 14:20:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(735, 44, '2026-02-03 13:48:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(736, 21, '2026-02-03 10:19:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(737, 38, '2026-02-03 16:04:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(738, 48, '2026-02-03 15:29:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(739, 9, '2026-02-03 12:00:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(740, 39, '2026-02-03 11:00:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(741, 29, '2026-02-03 13:05:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(742, 48, '2026-02-03 13:51:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(743, 35, '2026-02-03 16:08:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(744, 1, '2026-02-04 16:36:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(745, 45, '2026-02-04 08:13:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(746, 30, '2026-02-04 16:28:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(747, 34, '2026-02-04 13:02:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(748, 35, '2026-02-04 15:07:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(749, 23, '2026-02-04 10:50:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(750, 23, '2026-02-04 11:39:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(751, 30, '2026-02-04 10:33:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(752, 41, '2026-02-04 15:05:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(753, 34, '2026-02-04 12:07:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(754, 43, '2026-02-04 10:52:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(755, 48, '2026-02-04 10:15:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(756, 10, '2026-02-04 16:05:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(757, 4, '2026-02-04 13:54:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(758, 16, '2026-02-04 09:16:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(759, 33, '2026-02-04 09:30:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(760, 37, '2026-02-04 15:51:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(761, 5, '2026-02-05 12:05:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(762, 32, '2026-02-05 09:44:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(763, 32, '2026-02-05 14:32:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(764, 5, '2026-02-05 14:06:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(765, 49, '2026-02-05 11:33:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(766, 7, '2026-02-05 12:43:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(767, 26, '2026-02-05 10:02:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(768, 36, '2026-02-05 11:13:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(769, 17, '2026-02-05 14:08:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(770, 48, '2026-02-05 16:55:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(771, 22, '2026-02-05 15:07:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(772, 16, '2026-02-05 10:14:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(773, 22, '2026-02-05 16:25:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(774, 16, '2026-02-05 12:07:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(775, 31, '2026-02-05 11:49:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(776, 21, '2026-02-05 15:54:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(777, 41, '2026-02-05 15:49:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(778, 16, '2026-02-05 09:40:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(779, 12, '2026-02-05 16:59:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(780, 26, '2026-02-06 10:38:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(781, 33, '2026-02-06 09:47:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(782, 43, '2026-02-06 13:17:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(783, 29, '2026-02-06 15:10:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(784, 22, '2026-02-06 09:00:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(785, 18, '2026-02-06 08:08:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(786, 51, '2026-02-06 11:13:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(787, 13, '2026-02-07 14:50:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(788, 14, '2026-02-07 10:39:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(789, 37, '2026-02-07 11:03:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(790, 30, '2026-02-07 16:43:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(791, 12, '2026-02-07 12:35:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(792, 33, '2026-02-07 14:41:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(793, 3, '2026-02-07 11:02:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(794, 2, '2026-02-08 12:55:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(795, 2, '2026-02-08 11:08:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(796, 35, '2026-02-08 12:30:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(797, 40, '2026-02-08 16:28:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(798, 41, '2026-02-08 16:14:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(799, 51, '2026-02-08 10:08:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(800, 11, '2026-02-08 16:36:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(801, 7, '2026-02-08 08:55:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(802, 19, '2026-02-08 14:19:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(803, 9, '2026-02-09 15:47:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(804, 43, '2026-02-09 16:37:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(805, 39, '2026-02-09 16:54:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(806, 14, '2026-02-09 15:03:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(807, 15, '2026-02-09 09:18:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(808, 5, '2026-02-09 11:19:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(809, 34, '2026-02-09 08:19:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(810, 28, '2026-02-09 08:44:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(811, 38, '2026-02-09 10:33:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(812, 4, '2026-02-09 12:08:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(813, 9, '2026-02-09 11:42:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(814, 31, '2026-02-09 12:25:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(815, 12, '2026-02-09 13:41:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(816, 23, '2026-02-09 09:53:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(817, 21, '2026-02-09 16:56:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(818, 16, '2026-02-09 15:30:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(819, 25, '2026-02-09 11:46:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(820, 35, '2026-02-09 09:22:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(821, 12, '2026-02-09 12:24:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(822, 36, '2026-02-09 15:03:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(823, 17, '2026-02-09 15:27:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(824, 16, '2026-02-09 12:47:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(825, 14, '2026-02-09 08:51:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(826, 25, '2026-02-09 10:31:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(827, 28, '2026-02-10 11:46:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(828, 8, '2026-02-10 11:18:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(829, 28, '2026-02-10 15:41:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(830, 39, '2026-02-10 13:23:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(831, 48, '2026-02-10 11:18:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(832, 5, '2026-02-10 10:48:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(833, 5, '2026-02-10 11:53:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(834, 3, '2026-02-10 09:01:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(835, 39, '2026-02-10 15:01:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03');
INSERT INTO `visits` (`id`, `patient_id`, `visit_datetime`, `visit_type`, `reason`, `notes`, `recorded_by`, `created_at`) VALUES
(836, 23, '2026-02-10 12:05:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(837, 11, '2026-02-10 11:03:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(838, 3, '2026-02-11 13:17:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(839, 49, '2026-02-11 09:10:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(840, 17, '2026-02-11 10:17:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(841, 1, '2026-02-11 11:31:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(842, 21, '2026-02-11 08:40:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(843, 37, '2026-02-11 12:34:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(844, 29, '2026-02-11 11:49:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(845, 28, '2026-02-11 08:55:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(846, 5, '2026-02-11 09:23:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(847, 19, '2026-02-11 13:47:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(848, 31, '2026-02-11 12:21:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(849, 21, '2026-02-11 16:36:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(850, 34, '2026-02-11 12:32:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(851, 40, '2026-02-11 11:01:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(852, 8, '2026-02-11 13:15:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(853, 44, '2026-02-11 13:07:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(854, 26, '2026-02-11 15:20:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(855, 42, '2026-02-11 11:04:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(856, 29, '2026-02-11 14:59:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(857, 21, '2026-02-11 11:40:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(858, 6, '2026-02-12 15:16:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(859, 2, '2026-02-12 12:26:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(860, 35, '2026-02-12 16:09:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(861, 49, '2026-02-12 10:59:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(862, 36, '2026-02-12 13:06:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(863, 15, '2026-02-12 14:49:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(864, 33, '2026-02-12 09:44:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(865, 31, '2026-02-12 11:36:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(866, 6, '2026-02-12 09:51:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(867, 1, '2026-02-12 09:57:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(868, 21, '2026-02-12 11:26:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(869, 16, '2026-02-12 10:09:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(870, 33, '2026-02-12 15:38:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(871, 47, '2026-02-12 16:37:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(872, 51, '2026-02-12 13:18:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(873, 7, '2026-02-12 11:02:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(874, 2, '2026-02-12 09:14:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(875, 33, '2026-02-12 15:00:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(876, 22, '2026-02-12 14:55:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(877, 28, '2026-02-12 16:13:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(878, 46, '2026-02-12 11:36:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(879, 47, '2026-02-13 16:06:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(880, 8, '2026-02-13 10:40:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(881, 9, '2026-02-13 12:55:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(882, 20, '2026-02-13 15:58:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(883, 44, '2026-02-13 15:39:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(884, 24, '2026-02-13 13:00:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(885, 47, '2026-02-13 14:50:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(886, 16, '2026-02-13 12:45:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(887, 37, '2026-02-13 08:00:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(888, 31, '2026-02-13 14:14:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(889, 44, '2026-02-13 15:33:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(890, 32, '2026-02-14 16:20:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(891, 22, '2026-02-14 08:46:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(892, 11, '2026-02-14 13:05:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(893, 37, '2026-02-14 14:05:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(894, 25, '2026-02-14 09:28:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(895, 6, '2026-02-14 14:46:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(896, 46, '2026-02-14 14:20:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(897, 18, '2026-02-15 08:35:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(898, 10, '2026-02-15 09:49:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(899, 32, '2026-02-15 12:02:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(900, 43, '2026-02-15 13:21:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(901, 43, '2026-02-15 08:02:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(902, 11, '2026-02-15 10:49:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(903, 17, '2026-02-15 12:35:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(904, 34, '2026-02-15 15:15:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(905, 37, '2026-02-15 10:05:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(906, 14, '2026-02-15 08:51:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(907, 2, '2026-02-15 08:00:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(908, 20, '2026-02-16 12:21:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(909, 18, '2026-02-16 11:40:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(910, 5, '2026-02-16 11:20:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(911, 24, '2026-02-16 15:59:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(912, 36, '2026-02-16 15:26:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(913, 33, '2026-02-16 09:05:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(914, 32, '2026-02-16 11:08:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(915, 27, '2026-02-16 13:55:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(916, 40, '2026-02-16 08:42:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(917, 9, '2026-02-16 14:35:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(918, 21, '2026-02-16 11:08:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(919, 46, '2026-02-16 11:32:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(920, 48, '2026-02-16 14:01:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(921, 36, '2026-02-16 09:59:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(922, 7, '2026-02-16 14:45:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(923, 46, '2026-02-16 12:19:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(924, 15, '2026-02-16 09:42:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(925, 49, '2026-02-16 10:05:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(926, 40, '2026-02-16 08:12:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(927, 5, '2026-02-16 15:22:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(928, 4, '2026-02-16 16:24:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(929, 29, '2026-02-17 10:48:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(930, 44, '2026-02-17 11:37:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(931, 47, '2026-02-17 14:26:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(932, 34, '2026-02-17 10:11:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(933, 23, '2026-02-17 12:55:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(934, 31, '2026-02-17 08:52:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(935, 24, '2026-02-17 13:13:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(936, 38, '2026-02-17 09:04:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(937, 29, '2026-02-17 16:37:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(938, 24, '2026-02-17 12:14:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(939, 36, '2026-02-17 08:58:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(940, 11, '2026-02-17 09:18:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(941, 46, '2026-02-18 12:46:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(942, 38, '2026-02-18 08:58:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(943, 34, '2026-02-18 16:37:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(944, 27, '2026-02-18 16:42:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(945, 15, '2026-02-18 14:45:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(946, 48, '2026-02-18 11:43:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(947, 40, '2026-02-18 14:12:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(948, 12, '2026-02-18 12:29:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(949, 5, '2026-02-18 08:41:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(950, 20, '2026-02-18 12:30:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(951, 44, '2026-02-19 11:24:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(952, 46, '2026-02-19 12:10:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(953, 44, '2026-02-19 13:29:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(954, 42, '2026-02-19 08:42:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(955, 43, '2026-02-19 14:48:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(956, 13, '2026-02-19 08:35:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(957, 11, '2026-02-19 11:26:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(958, 32, '2026-02-19 15:53:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(959, 19, '2026-02-19 11:20:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(960, 14, '2026-02-19 12:07:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(961, 32, '2026-02-19 12:43:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(962, 21, '2026-02-19 09:10:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(963, 20, '2026-02-19 13:13:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(964, 44, '2026-02-19 13:53:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(965, 17, '2026-02-19 15:52:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(966, 32, '2026-02-19 08:36:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(967, 51, '2026-02-19 08:32:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(968, 26, '2026-02-19 15:39:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(969, 2, '2026-02-19 15:31:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(970, 38, '2026-02-19 13:05:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(971, 7, '2026-02-20 10:32:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(972, 14, '2026-02-20 08:47:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(973, 7, '2026-02-20 08:56:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(974, 21, '2026-02-20 14:07:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(975, 13, '2026-02-20 10:41:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(976, 11, '2026-02-20 12:38:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(977, 40, '2026-02-20 11:26:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(978, 45, '2026-02-21 10:53:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(979, 46, '2026-02-21 16:45:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(980, 8, '2026-02-21 12:55:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(981, 46, '2026-02-21 10:50:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(982, 3, '2026-02-21 10:18:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(983, 28, '2026-02-21 09:31:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(984, 9, '2026-02-21 09:16:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(985, 6, '2026-02-22 11:54:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(986, 8, '2026-02-22 09:14:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(987, 51, '2026-02-22 15:18:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(988, 36, '2026-02-22 15:05:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(989, 7, '2026-02-22 14:16:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(990, 11, '2026-02-22 12:00:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(991, 4, '2026-02-22 12:24:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(992, 25, '2026-02-22 13:15:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(993, 11, '2026-02-22 14:58:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(994, 35, '2026-02-22 10:57:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(995, 6, '2026-02-22 15:31:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(996, 14, '2026-02-22 12:04:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(997, 14, '2026-02-22 16:25:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(998, 40, '2026-02-22 13:31:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(999, 13, '2026-02-22 10:00:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1000, 45, '2026-02-22 10:35:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1001, 25, '2026-02-22 11:24:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1002, 12, '2026-02-22 12:56:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1003, 12, '2026-02-22 11:58:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1004, 42, '2026-02-22 10:19:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1005, 39, '2026-02-22 15:51:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1006, 47, '2026-02-22 10:28:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1007, 15, '2026-02-23 16:55:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1008, 48, '2026-02-23 08:21:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1009, 48, '2026-02-23 16:36:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1010, 21, '2026-02-23 08:51:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1011, 14, '2026-02-23 13:40:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1012, 46, '2026-02-23 10:28:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1013, 16, '2026-02-23 16:01:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1014, 26, '2026-02-23 12:11:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1015, 13, '2026-02-23 15:30:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1016, 13, '2026-02-23 09:19:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1017, 20, '2026-02-23 10:13:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1018, 35, '2026-02-23 14:42:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1019, 12, '2026-02-23 11:42:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1020, 44, '2026-02-23 08:53:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1021, 7, '2026-02-23 13:57:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1022, 28, '2026-02-23 09:30:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1023, 33, '2026-02-23 08:58:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1024, 41, '2026-02-23 13:38:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1025, 34, '2026-02-24 09:50:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1026, 11, '2026-02-24 10:10:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1027, 20, '2026-02-24 15:14:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1028, 36, '2026-02-24 13:48:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1029, 3, '2026-02-24 10:52:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1030, 36, '2026-02-24 15:16:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1031, 1, '2026-02-24 15:45:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1032, 40, '2026-02-24 16:46:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1033, 9, '2026-02-24 16:41:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1034, 8, '2026-02-24 11:33:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1035, 48, '2026-02-24 08:38:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1036, 32, '2026-02-24 15:36:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1037, 29, '2026-02-24 14:09:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1038, 37, '2026-02-24 11:20:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1039, 30, '2026-02-24 09:41:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1040, 41, '2026-02-24 15:13:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1041, 39, '2026-02-24 16:05:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1042, 38, '2026-02-24 13:02:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1043, 36, '2026-02-25 16:44:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1044, 39, '2026-02-25 12:53:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1045, 42, '2026-02-25 14:06:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1046, 51, '2026-02-25 10:55:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1047, 19, '2026-02-25 16:00:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1048, 22, '2026-02-25 08:22:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1049, 25, '2026-02-25 15:48:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1050, 36, '2026-02-25 10:08:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1051, 42, '2026-02-25 12:59:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1052, 30, '2026-02-25 14:49:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1053, 46, '2026-02-26 15:06:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1054, 7, '2026-02-26 11:25:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1055, 23, '2026-02-26 16:59:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1056, 33, '2026-02-26 10:46:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1057, 38, '2026-02-26 11:11:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1058, 3, '2026-02-26 11:39:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1059, 49, '2026-02-26 15:39:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1060, 14, '2026-02-26 14:51:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1061, 36, '2026-02-26 09:07:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1062, 44, '2026-02-26 16:18:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1063, 2, '2026-02-27 12:34:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1064, 8, '2026-02-27 15:34:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1065, 34, '2026-02-27 11:49:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1066, 43, '2026-02-27 15:00:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1067, 41, '2026-02-27 10:28:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1068, 30, '2026-02-27 13:49:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1069, 8, '2026-02-27 09:44:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1070, 2, '2026-02-27 16:18:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1071, 29, '2026-02-27 08:46:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1072, 1, '2026-02-27 12:01:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1073, 13, '2026-02-27 12:22:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1074, 14, '2026-02-27 09:54:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1075, 38, '2026-02-27 09:32:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1076, 20, '2026-02-27 09:29:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1077, 37, '2026-02-27 13:59:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1078, 51, '2026-02-27 10:16:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1079, 7, '2026-02-27 12:06:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1080, 23, '2026-02-27 14:46:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1081, 39, '2026-02-27 10:35:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1082, 25, '2026-02-27 15:41:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1083, 13, '2026-02-27 13:03:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1084, 15, '2026-02-27 08:32:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1085, 38, '2026-02-27 16:44:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1086, 42, '2026-02-27 09:40:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1087, 33, '2026-02-28 16:26:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1088, 20, '2026-02-28 15:50:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1089, 10, '2026-02-28 11:46:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1090, 45, '2026-02-28 08:30:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1091, 44, '2026-02-28 15:49:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1092, 16, '2026-02-28 08:25:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1093, 38, '2026-02-28 09:27:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1094, 5, '2026-02-28 11:26:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1095, 30, '2026-02-28 13:19:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1096, 24, '2026-02-28 13:06:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1097, 32, '2026-02-28 12:40:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1098, 44, '2026-02-28 11:47:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1099, 3, '2026-02-28 09:16:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1100, 37, '2026-02-28 11:11:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1101, 12, '2026-02-28 12:45:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1102, 41, '2026-02-28 14:11:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1103, 5, '2026-02-28 14:44:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1104, 45, '2026-03-01 13:57:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1105, 21, '2026-03-01 08:33:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1106, 34, '2026-03-01 16:50:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1107, 33, '2026-03-01 11:19:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1108, 16, '2026-03-01 16:09:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1109, 35, '2026-03-01 13:35:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1110, 22, '2026-03-01 12:30:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1111, 6, '2026-03-01 14:16:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1112, 51, '2026-03-01 14:17:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1113, 13, '2026-03-01 14:36:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1114, 29, '2026-03-01 11:30:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1115, 11, '2026-03-01 10:55:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1116, 34, '2026-03-01 15:40:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1117, 49, '2026-03-01 08:49:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1118, 5, '2026-03-01 08:45:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1119, 8, '2026-03-01 13:34:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1120, 21, '2026-03-01 14:41:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1121, 39, '2026-03-02 09:12:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1122, 23, '2026-03-02 13:48:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1123, 20, '2026-03-02 15:22:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1124, 29, '2026-03-02 16:09:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1125, 44, '2026-03-02 09:44:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1126, 11, '2026-03-02 16:50:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1127, 25, '2026-03-02 13:34:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1128, 36, '2026-03-02 09:09:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1129, 30, '2026-03-02 12:52:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1130, 36, '2026-03-02 14:46:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1131, 22, '2026-03-02 14:49:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1132, 31, '2026-03-02 11:52:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1133, 1, '2026-03-02 13:22:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1134, 42, '2026-03-02 12:36:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1135, 38, '2026-03-02 08:27:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1136, 9, '2026-03-02 12:18:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1137, 26, '2026-03-02 14:00:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1138, 18, '2026-03-02 12:33:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1139, 5, '2026-03-03 11:27:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1140, 2, '2026-03-03 11:18:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1141, 5, '2026-03-03 16:28:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1142, 22, '2026-03-03 08:26:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1143, 42, '2026-03-03 10:37:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1144, 31, '2026-03-03 15:40:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1145, 28, '2026-03-03 12:19:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1146, 27, '2026-03-03 09:48:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1147, 36, '2026-03-03 10:35:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1148, 4, '2026-03-03 08:28:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1149, 24, '2026-03-03 14:33:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1150, 28, '2026-03-03 14:22:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1151, 36, '2026-03-03 08:26:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1152, 9, '2026-03-03 11:27:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1153, 16, '2026-03-03 13:47:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1154, 14, '2026-03-03 09:55:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1155, 11, '2026-03-03 15:06:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1156, 24, '2026-03-03 09:49:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1157, 46, '2026-03-03 16:23:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1158, 18, '2026-03-03 13:34:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1159, 37, '2026-03-04 16:15:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1160, 35, '2026-03-04 08:51:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1161, 44, '2026-03-04 14:55:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1162, 29, '2026-03-04 12:24:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1163, 27, '2026-03-04 13:14:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1164, 7, '2026-03-04 14:34:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1165, 37, '2026-03-05 08:48:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1166, 17, '2026-03-05 16:39:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1167, 31, '2026-03-05 10:37:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1168, 21, '2026-03-05 14:45:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1169, 40, '2026-03-05 16:05:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1170, 25, '2026-03-05 10:45:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1171, 20, '2026-03-05 13:38:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1172, 42, '2026-03-05 15:59:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1173, 48, '2026-03-05 11:45:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1174, 23, '2026-03-06 08:57:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1175, 29, '2026-03-06 11:49:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1176, 34, '2026-03-06 09:44:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1177, 38, '2026-03-06 15:56:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1178, 31, '2026-03-06 08:24:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1179, 36, '2026-03-06 08:46:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1180, 45, '2026-03-06 16:22:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1181, 9, '2026-03-06 11:03:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1182, 16, '2026-03-06 12:24:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1183, 32, '2026-03-06 13:28:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1184, 21, '2026-03-06 10:31:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1185, 12, '2026-03-06 15:59:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1186, 40, '2026-03-06 13:55:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1187, 38, '2026-03-06 16:13:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1188, 48, '2026-03-06 16:10:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1189, 47, '2026-03-06 16:27:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1190, 9, '2026-03-06 12:46:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1191, 45, '2026-03-06 16:07:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1192, 18, '2026-03-06 16:01:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1193, 40, '2026-03-07 16:05:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1194, 1, '2026-03-07 15:46:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1195, 18, '2026-03-07 12:11:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1196, 22, '2026-03-07 16:00:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1197, 34, '2026-03-07 10:56:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1198, 32, '2026-03-07 14:58:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1199, 10, '2026-03-07 14:02:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1200, 7, '2026-03-07 13:49:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1201, 9, '2026-03-07 10:14:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1202, 12, '2026-03-07 16:05:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1203, 45, '2026-03-07 09:31:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1204, 39, '2026-03-07 10:54:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1205, 23, '2026-03-07 16:02:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1206, 34, '2026-03-07 09:35:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1207, 12, '2026-03-08 13:22:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1208, 37, '2026-03-08 14:41:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1209, 43, '2026-03-08 11:07:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1210, 40, '2026-03-08 09:02:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1211, 6, '2026-03-08 14:58:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1212, 40, '2026-03-08 09:36:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1213, 3, '2026-03-08 08:00:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1214, 51, '2026-03-08 08:56:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1215, 18, '2026-03-08 16:53:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1216, 45, '2026-03-08 11:06:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1217, 19, '2026-03-09 14:44:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1218, 49, '2026-03-09 10:27:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1219, 40, '2026-03-09 09:54:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1220, 29, '2026-03-09 11:54:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1221, 37, '2026-03-09 10:37:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1222, 18, '2026-03-09 08:35:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1223, 51, '2026-03-09 13:17:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1224, 45, '2026-03-09 16:23:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1225, 43, '2026-03-09 11:38:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1226, 46, '2026-03-09 13:45:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1227, 3, '2026-03-09 08:36:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1228, 20, '2026-03-09 15:21:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1229, 8, '2026-03-09 14:06:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1230, 31, '2026-03-09 10:13:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1231, 47, '2026-03-09 14:49:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1232, 35, '2026-03-09 12:10:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1233, 7, '2026-03-09 12:00:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1234, 51, '2026-03-10 09:59:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1235, 22, '2026-03-10 09:34:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1236, 12, '2026-03-10 15:20:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1237, 38, '2026-03-10 09:51:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1238, 14, '2026-03-10 12:13:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1239, 41, '2026-03-11 14:23:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1240, 24, '2026-03-11 14:42:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1241, 11, '2026-03-11 14:01:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1242, 31, '2026-03-11 08:40:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1243, 48, '2026-03-11 10:37:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1244, 11, '2026-03-11 16:14:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1245, 27, '2026-03-11 12:30:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1246, 32, '2026-03-11 09:11:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1247, 23, '2026-03-11 11:26:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1248, 48, '2026-03-11 08:54:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1249, 39, '2026-03-11 12:34:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1250, 41, '2026-03-11 16:31:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03');
INSERT INTO `visits` (`id`, `patient_id`, `visit_datetime`, `visit_type`, `reason`, `notes`, `recorded_by`, `created_at`) VALUES
(1251, 32, '2026-03-11 15:07:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1252, 25, '2026-03-11 14:54:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1253, 33, '2026-03-11 15:06:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1254, 43, '2026-03-11 12:44:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1255, 17, '2026-03-11 12:13:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1256, 34, '2026-03-11 11:33:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1257, 10, '2026-03-11 10:21:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1258, 30, '2026-03-11 09:18:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1259, 22, '2026-03-11 15:45:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1260, 38, '2026-03-11 11:16:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1261, 6, '2026-03-12 13:56:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1262, 19, '2026-03-12 12:09:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1263, 14, '2026-03-12 09:32:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1264, 13, '2026-03-12 08:03:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1265, 4, '2026-03-12 08:16:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1266, 35, '2026-03-12 11:33:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1267, 4, '2026-03-12 09:31:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1268, 42, '2026-03-12 12:37:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1269, 3, '2026-03-12 09:59:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1270, 42, '2026-03-13 12:24:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1271, 32, '2026-03-13 10:54:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1272, 26, '2026-03-13 09:10:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1273, 18, '2026-03-13 14:20:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1274, 3, '2026-03-13 11:03:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1275, 1, '2026-03-13 14:42:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1276, 30, '2026-03-13 12:16:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1277, 15, '2026-03-13 12:22:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1278, 15, '2026-03-13 09:13:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1279, 41, '2026-03-13 11:52:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1280, 7, '2026-03-13 15:21:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1281, 26, '2026-03-13 09:53:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1282, 35, '2026-03-13 14:08:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1283, 25, '2026-03-13 15:32:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1284, 27, '2026-03-13 14:08:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1285, 48, '2026-03-13 16:10:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1286, 12, '2026-03-13 11:39:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1287, 4, '2026-03-13 09:20:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1288, 30, '2026-03-13 11:31:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1289, 7, '2026-03-13 09:56:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1290, 9, '2026-03-13 15:58:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1291, 20, '2026-03-13 13:15:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1292, 45, '2026-03-13 16:40:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1293, 3, '2026-03-13 12:33:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1294, 21, '2026-03-13 08:11:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1295, 18, '2026-03-14 11:51:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1296, 20, '2026-03-14 09:03:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1297, 51, '2026-03-14 13:38:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1298, 29, '2026-03-14 11:16:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1299, 3, '2026-03-14 11:21:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1300, 14, '2026-03-14 10:35:00', 'general', 'Health certificate', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1301, 34, '2026-03-14 16:11:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1302, 17, '2026-03-15 12:20:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1303, 34, '2026-03-15 08:52:00', 'general', 'Diabetes checkup', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1304, 6, '2026-03-15 10:10:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1305, 20, '2026-03-15 13:40:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1306, 20, '2026-03-15 15:06:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1307, 32, '2026-03-15 08:40:00', 'general', 'Fever and cough', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1308, 26, '2026-03-15 09:26:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1309, 12, '2026-03-15 15:52:00', 'general', 'Medication refill', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1310, 17, '2026-03-15 12:46:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:03'),
(1311, 3, '2026-03-15 13:43:00', 'general', 'Health certificate', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1312, 1, '2026-03-15 16:40:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:03'),
(1313, 1, '2026-03-15 10:00:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1314, 5, '2026-03-15 08:29:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1315, 8, '2026-03-15 13:22:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1316, 30, '2026-03-15 14:53:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1317, 35, '2026-03-15 16:47:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1318, 37, '2026-03-16 10:51:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1319, 42, '2026-03-16 11:54:00', 'general', 'Health checkup', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1320, 1, '2026-03-16 10:24:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1321, 45, '2026-03-16 16:58:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1322, 29, '2026-03-16 09:42:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1323, 16, '2026-03-16 11:23:00', 'general', 'General consultation', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1324, 2, '2026-03-16 16:51:00', 'general', 'Fever and cough', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1325, 8, '2026-03-16 15:37:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1326, 47, '2026-03-16 11:35:00', 'general', 'Wound care', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1327, 8, '2026-03-16 13:55:00', 'general', 'Health checkup', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1328, 16, '2026-03-16 11:16:00', 'general', 'Wound care', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1329, 36, '2026-03-16 10:21:00', 'general', 'Medication refill', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1330, 5, '2026-03-16 08:48:00', 'general', 'Hypertension monitoring', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1331, 36, '2026-03-16 12:51:00', 'general', 'General consultation', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1332, 41, '2026-03-16 14:54:00', 'general', 'Follow-up visit', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1333, 26, '2026-03-16 13:56:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1334, 51, '2026-03-17 13:10:00', 'general', 'Blood pressure check', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1335, 40, '2026-03-17 14:04:00', 'general', 'Hypertension monitoring', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1336, 38, '2026-03-17 14:54:00', 'general', 'Follow-up visit', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1337, 36, '2026-03-17 15:33:00', 'general', 'Diabetes checkup', 'General consultation visit', 3, '2026-03-18 10:20:04'),
(1338, 7, '2026-03-17 10:35:00', 'general', 'Blood pressure check', 'General consultation visit', 2, '2026-03-18 10:20:04'),
(1339, 73, '2025-11-17 00:00:00', 'immunization', 'BCG dose 1', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1340, 73, '2025-11-16 00:00:00', 'immunization', 'Hepatitis B dose 1', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1341, 73, '2026-01-16 00:00:00', 'immunization', 'Pentavalent dose 1', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1342, 73, '2026-01-13 00:00:00', 'immunization', 'Oral Polio Vaccine dose 1', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1343, 97, '2025-08-27 00:00:00', 'immunization', 'Measles, Mumps, Rubella dose 1', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1344, 97, '2025-10-17 00:00:00', 'immunization', 'Measles, Mumps, Rubella dose 2', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1345, 35, '2025-10-10 00:00:00', 'immunization', 'Measles, Mumps, Rubella dose 2', 'Immunization visit', 3, '2026-03-18 10:20:04'),
(1346, 40, '2025-04-20 00:00:00', 'immunization', 'Measles, Mumps, Rubella dose 1', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1347, 40, '2025-06-18 00:00:00', 'immunization', 'Measles, Mumps, Rubella dose 2', 'Immunization visit', 2, '2026-03-18 10:20:04'),
(1348, 67, '2024-11-26 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1349, 67, '2024-12-24 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1350, 67, '2025-01-21 00:00:00', 'maternal', 'Prenatal checkup - 12 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1351, 67, '2025-02-18 00:00:00', 'maternal', 'Prenatal checkup - 16 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1352, 67, '2025-03-18 00:00:00', 'maternal', 'Prenatal checkup - 20 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1353, 67, '2025-04-15 00:00:00', 'maternal', 'Prenatal checkup - 24 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1354, 67, '2025-05-13 00:00:00', 'maternal', 'Prenatal checkup - 28 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1355, 67, '2025-06-10 00:00:00', 'maternal', 'Prenatal checkup - 32 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1356, 67, '2025-09-16 00:00:00', 'maternal', 'Postnatal checkup - 42 days postpartum', 'Postnatal visit', 3, '2026-03-18 10:20:05'),
(1357, 67, '2025-11-03 00:00:00', 'maternal', 'Postnatal checkup - 90 days postpartum', 'Postnatal visit', 2, '2026-03-18 10:20:05'),
(1358, 46, '2025-12-12 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1359, 46, '2026-01-09 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1360, 46, '2026-02-06 00:00:00', 'maternal', 'Prenatal checkup - 12 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1361, 46, '2026-03-06 00:00:00', 'maternal', 'Prenatal checkup - 16 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1362, 57, '2025-07-12 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1363, 57, '2025-08-09 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1364, 57, '2025-09-06 00:00:00', 'maternal', 'Prenatal checkup - 12 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1365, 57, '2025-10-04 00:00:00', 'maternal', 'Prenatal checkup - 16 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1366, 57, '2025-11-01 00:00:00', 'maternal', 'Prenatal checkup - 20 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1367, 57, '2025-11-29 00:00:00', 'maternal', 'Prenatal checkup - 24 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1368, 57, '2025-12-27 00:00:00', 'maternal', 'Prenatal checkup - 28 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1369, 57, '2026-01-24 00:00:00', 'maternal', 'Prenatal checkup - 32 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1370, 7, '2025-02-02 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1371, 7, '2025-03-02 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1372, 7, '2025-03-30 00:00:00', 'maternal', 'Prenatal checkup - 12 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1373, 7, '2025-04-27 00:00:00', 'maternal', 'Prenatal checkup - 16 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1374, 7, '2025-05-25 00:00:00', 'maternal', 'Prenatal checkup - 20 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1375, 7, '2025-06-22 00:00:00', 'maternal', 'Prenatal checkup - 24 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1376, 7, '2025-07-20 00:00:00', 'maternal', 'Prenatal checkup - 28 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1377, 7, '2025-08-17 00:00:00', 'maternal', 'Prenatal checkup - 32 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1378, 7, '2025-09-14 00:00:00', 'maternal', 'Prenatal checkup - 36 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1379, 7, '2025-11-23 00:00:00', 'maternal', 'Postnatal checkup - 42 days postpartum', 'Postnatal visit', 2, '2026-03-18 10:20:05'),
(1380, 15, '2025-12-08 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1381, 15, '2026-01-05 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1382, 83, '2025-04-01 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1383, 83, '2025-04-29 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1384, 83, '2025-05-27 00:00:00', 'maternal', 'Prenatal checkup - 12 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1385, 83, '2025-06-24 00:00:00', 'maternal', 'Prenatal checkup - 16 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1386, 83, '2025-07-22 00:00:00', 'maternal', 'Prenatal checkup - 20 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1387, 83, '2025-08-19 00:00:00', 'maternal', 'Prenatal checkup - 24 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1388, 83, '2025-09-16 00:00:00', 'maternal', 'Prenatal checkup - 28 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1389, 83, '2025-10-14 00:00:00', 'maternal', 'Prenatal checkup - 32 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1390, 83, '2025-11-11 00:00:00', 'maternal', 'Prenatal checkup - 36 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1391, 83, '2026-01-20 00:00:00', 'maternal', 'Postnatal checkup - 42 days postpartum', 'Postnatal visit', 2, '2026-03-18 10:20:05'),
(1392, 83, '2026-03-09 00:00:00', 'maternal', 'Postnatal checkup - 90 days postpartum', 'Postnatal visit', 3, '2026-03-18 10:20:05'),
(1393, 95, '2025-03-05 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1394, 95, '2025-04-02 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1395, 95, '2025-04-30 00:00:00', 'maternal', 'Prenatal checkup - 12 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1396, 95, '2025-05-28 00:00:00', 'maternal', 'Prenatal checkup - 16 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1397, 95, '2025-06-25 00:00:00', 'maternal', 'Prenatal checkup - 20 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1398, 95, '2025-07-23 00:00:00', 'maternal', 'Prenatal checkup - 24 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1399, 95, '2025-08-20 00:00:00', 'maternal', 'Prenatal checkup - 28 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1400, 95, '2025-09-17 00:00:00', 'maternal', 'Prenatal checkup - 32 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1401, 95, '2025-10-15 00:00:00', 'maternal', 'Prenatal checkup - 36 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1402, 95, '2025-11-19 00:00:00', 'maternal', 'Postnatal checkup - 7 days postpartum', 'Postnatal visit', 2, '2026-03-18 10:20:05'),
(1403, 95, '2025-12-24 00:00:00', 'maternal', 'Postnatal checkup - 42 days postpartum', 'Postnatal visit', 2, '2026-03-18 10:20:05'),
(1404, 95, '2026-02-10 00:00:00', 'maternal', 'Postnatal checkup - 90 days postpartum', 'Postnatal visit', 2, '2026-03-18 10:20:05'),
(1405, 18, '2025-11-09 00:00:00', 'maternal', 'Prenatal checkup - 4 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1406, 18, '2025-12-07 00:00:00', 'maternal', 'Prenatal checkup - 8 weeks', 'Prenatal visit', 2, '2026-03-18 10:20:05'),
(1407, 18, '2026-01-04 00:00:00', 'maternal', 'Prenatal checkup - 12 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05'),
(1408, 18, '2026-02-01 00:00:00', 'maternal', 'Prenatal checkup - 16 weeks', 'Prenatal visit', 3, '2026-03-18 10:20:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_act_created` (`created_at`),
  ADD KEY `idx_act_module` (`module`),
  ADD KEY `idx_act_action` (`action`),
  ADD KEY `idx_act_user` (`user_id`),
  ADD KEY `idx_act_entity` (`entity_type`,`entity_id`);

--
-- Indexes for table `analytics_daily`
--
ALTER TABLE `analytics_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_metric_date` (`metric_name`,`metric_date`),
  ADD KEY `idx_analytics_date` (`metric_date`),
  ADD KEY `idx_analytics_metric` (`metric_name`);

--
-- Indexes for table `arima_parameters`
--
ALTER TABLE `arima_parameters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ap_run` (`run_id`);

--
-- Indexes for table `forecast_results`
--
ALTER TABLE `forecast_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_run_date` (`run_id`,`forecast_date`);

--
-- Indexes for table `forecast_runs`
--
ALTER TABLE `forecast_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fr_series_key` (`series_key`);

--
-- Indexes for table `fp_records`
--
ALTER TABLE `fp_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `client_code` (`client_code`),
  ADD KEY `idx_fp_records_status` (`status`),
  ADD KEY `idx_fp_records_method` (`method_accepted`),
  ADD KEY `idx_fp_records_patient` (`patient_id`);

--
-- Indexes for table `fp_visits`
--
ALTER TABLE `fp_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fp_visits_record` (`fp_record_id`),
  ADD KEY `fk_fp_visits_user` (`recorded_by`),
  ADD KEY `idx_fp_visits_date` (`visit_date`),
  ADD KEY `idx_fp_visits_next_app` (`next_appointment_date`);

--
-- Indexes for table `health_services`
--
ALTER TABLE `health_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `service_code` (`service_code`),
  ADD KEY `idx_hs_category` (`category`),
  ADD KEY `idx_hs_active` (`is_active`);

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `household_code` (`household_code`);

--
-- Indexes for table `immunization_records`
--
ALTER TABLE `immunization_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vaccine_id` (`vaccine_id`),
  ADD KEY `administered_by` (`administered_by`),
  ADD KEY `idx_immunization_date` (`administered_on`),
  ADD KEY `idx_immunization_patient` (`patient_id`);

--
-- Indexes for table `immunization_schedule`
--
ALTER TABLE `immunization_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vaccine_id` (`vaccine_id`),
  ADD KEY `idx_schedule_date` (`scheduled_date`),
  ADD KEY `idx_schedule_patient` (`patient_id`),
  ADD KEY `idx_schedule_status` (`status`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_medicines_name` (`name`);

--
-- Indexes for table `medicine_batches`
--
ALTER TABLE `medicine_batches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_batch` (`medicine_id`,`batch_no`),
  ADD KEY `idx_batches_expiry` (`expiry_date`),
  ADD KEY `idx_batches_medicine` (`medicine_id`);

--
-- Indexes for table `medicine_transactions`
--
ALTER TABLE `medicine_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_transactions_datetime` (`transaction_datetime`),
  ADD KEY `idx_transactions_medicine` (`medicine_id`),
  ADD KEY `idx_transactions_type` (`transaction_type`);

--
-- Indexes for table `ncd_records`
--
ALTER TABLE `ncd_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ncd_code` (`ncd_code`),
  ADD KEY `idx_ncd_records_status` (`status`),
  ADD KEY `idx_ncd_records_diagnosis` (`diagnosis_type`),
  ADD KEY `idx_ncd_records_patient` (`patient_id`),
  ADD KEY `idx_ncd_records_risk` (`philpen_risk_level`);

--
-- Indexes for table `ncd_visits`
--
ALTER TABLE `ncd_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ncd_visits_user` (`recorded_by`),
  ADD KEY `idx_ncd_visits_date` (`visit_date`),
  ADD KEY `idx_ncd_visits_next_app` (`next_appointment_date`),
  ADD KEY `idx_ncd_visits_record` (`ncd_record_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`),
  ADD KEY `idx_patients_name` (`last_name`,`first_name`),
  ADD KEY `idx_patients_birth_date` (`birth_date`),
  ADD KEY `idx_patients_status` (`status`);

--
-- Indexes for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_allergies_patient` (`patient_id`);

--
-- Indexes for table `patient_appointments`
--
ALTER TABLE `patient_appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `appointment_code` (`appointment_code`),
  ADD KEY `fk_apt_user` (`created_by`),
  ADD KEY `idx_apt_date` (`appointment_date`),
  ADD KEY `idx_apt_status` (`status`),
  ADD KEY `idx_apt_patient` (`patient_id`),
  ADD KEY `idx_apt_service` (`service_id`),
  ADD KEY `idx_apt_date_status` (`appointment_date`,`status`);

--
-- Indexes for table `patient_conditions`
--
ALTER TABLE `patient_conditions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conditions_patient` (`patient_id`),
  ADD KEY `idx_conditions_status` (`status`);

--
-- Indexes for table `postnatal_visits`
--
ALTER TABLE `postnatal_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_postnatal_date` (`visit_datetime`),
  ADD KEY `idx_postnatal_pregnancy` (`pregnancy_id`);

--
-- Indexes for table `pregnancies`
--
ALTER TABLE `pregnancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pregnancies_patient` (`patient_id`),
  ADD KEY `idx_pregnancies_status` (`status`);

--
-- Indexes for table `prenatal_visits`
--
ALTER TABLE `prenatal_visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_prenatal_date` (`visit_datetime`),
  ADD KEY `idx_prenatal_pregnancy` (`pregnancy_id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reminders_due` (`due_date`),
  ADD KEY `idx_reminders_status` (`status`),
  ADD KEY `idx_reminders_patient` (`patient_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `timeseries_daily`
--
ALTER TABLE `timeseries_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_series_day` (`series_key`,`series_date`),
  ADD KEY `idx_series_date` (`series_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `vaccines`
--
ALTER TABLE `vaccines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_visits_datetime` (`visit_datetime`),
  ADD KEY `idx_visits_patient_date` (`patient_id`,`visit_datetime`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1179;

--
-- AUTO_INCREMENT for table `analytics_daily`
--
ALTER TABLE `analytics_daily`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `arima_parameters`
--
ALTER TABLE `arima_parameters`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `forecast_results`
--
ALTER TABLE `forecast_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3562;

--
-- AUTO_INCREMENT for table `forecast_runs`
--
ALTER TABLE `forecast_runs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=191;

--
-- AUTO_INCREMENT for table `fp_records`
--
ALTER TABLE `fp_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `fp_visits`
--
ALTER TABLE `fp_visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `health_services`
--
ALTER TABLE `health_services`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `immunization_records`
--
ALTER TABLE `immunization_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `immunization_schedule`
--
ALTER TABLE `immunization_schedule`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `medicine_batches`
--
ALTER TABLE `medicine_batches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=184;

--
-- AUTO_INCREMENT for table `medicine_transactions`
--
ALTER TABLE `medicine_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1149;

--
-- AUTO_INCREMENT for table `ncd_records`
--
ALTER TABLE `ncd_records`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `ncd_visits`
--
ALTER TABLE `ncd_visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `patient_appointments`
--
ALTER TABLE `patient_appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `patient_conditions`
--
ALTER TABLE `patient_conditions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `postnatal_visits`
--
ALTER TABLE `postnatal_visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `pregnancies`
--
ALTER TABLE `pregnancies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `prenatal_visits`
--
ALTER TABLE `prenatal_visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timeseries_daily`
--
ALTER TABLE `timeseries_daily`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=468;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `vaccines`
--
ALTER TABLE `vaccines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1409;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_act_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `arima_parameters`
--
ALTER TABLE `arima_parameters`
  ADD CONSTRAINT `fk_ap_run` FOREIGN KEY (`run_id`) REFERENCES `forecast_runs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `forecast_results`
--
ALTER TABLE `forecast_results`
  ADD CONSTRAINT `fk_fres_run` FOREIGN KEY (`run_id`) REFERENCES `forecast_runs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fp_records`
--
ALTER TABLE `fp_records`
  ADD CONSTRAINT `fk_fp_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fp_visits`
--
ALTER TABLE `fp_visits`
  ADD CONSTRAINT `fk_fp_visits_record` FOREIGN KEY (`fp_record_id`) REFERENCES `fp_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fp_visits_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `immunization_records`
--
ALTER TABLE `immunization_records`
  ADD CONSTRAINT `immunization_records_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `immunization_records_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`),
  ADD CONSTRAINT `immunization_records_ibfk_3` FOREIGN KEY (`administered_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `immunization_schedule`
--
ALTER TABLE `immunization_schedule`
  ADD CONSTRAINT `immunization_schedule_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `immunization_schedule_ibfk_2` FOREIGN KEY (`vaccine_id`) REFERENCES `vaccines` (`id`);

--
-- Constraints for table `medicine_batches`
--
ALTER TABLE `medicine_batches`
  ADD CONSTRAINT `medicine_batches_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicine_transactions`
--
ALTER TABLE `medicine_transactions`
  ADD CONSTRAINT `medicine_transactions_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicines` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `medicine_transactions_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `medicine_batches` (`id`),
  ADD CONSTRAINT `medicine_transactions_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `ncd_records`
--
ALTER TABLE `ncd_records`
  ADD CONSTRAINT `fk_ncd_records_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ncd_visits`
--
ALTER TABLE `ncd_visits`
  ADD CONSTRAINT `fk_ncd_visits_record` FOREIGN KEY (`ncd_record_id`) REFERENCES `ncd_records` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ncd_visits_user` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`);

--
-- Constraints for table `patient_allergies`
--
ALTER TABLE `patient_allergies`
  ADD CONSTRAINT `patient_allergies_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_appointments`
--
ALTER TABLE `patient_appointments`
  ADD CONSTRAINT `fk_apt_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_apt_service` FOREIGN KEY (`service_id`) REFERENCES `health_services` (`id`),
  ADD CONSTRAINT `fk_apt_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `patient_conditions`
--
ALTER TABLE `patient_conditions`
  ADD CONSTRAINT `patient_conditions_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `postnatal_visits`
--
ALTER TABLE `postnatal_visits`
  ADD CONSTRAINT `postnatal_visits_ibfk_1` FOREIGN KEY (`pregnancy_id`) REFERENCES `pregnancies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `postnatal_visits_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `pregnancies`
--
ALTER TABLE `pregnancies`
  ADD CONSTRAINT `pregnancies_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `prenatal_visits`
--
ALTER TABLE `prenatal_visits`
  ADD CONSTRAINT `prenatal_visits_ibfk_1` FOREIGN KEY (`pregnancy_id`) REFERENCES `pregnancies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `prenatal_visits_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `reminders`
--
ALTER TABLE `reminders`
  ADD CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
