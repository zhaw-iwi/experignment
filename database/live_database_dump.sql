-- phpMyAdmin SQL Dump
-- version 4.9.6
-- https://www.phpmyadmin.net/
--
-- Host: e93ud.myd.infomaniak.com
-- Generation Time: Jul 05, 2026 at 05:18 PM
-- Server version: 10.6.18-MariaDB-deb11-log
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e93ud_aydemmel`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_fields`
--

CREATE TABLE `access_fields` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `experiment_id` bigint(20) UNSIGNED NOT NULL,
  `condition_id` bigint(20) UNSIGNED DEFAULT NULL,
  `field_key` varchar(64) NOT NULL,
  `label` varchar(255) NOT NULL,
  `value_type` enum('text','url','pid','appointment') NOT NULL DEFAULT 'text',
  `value_source` enum('shared','pool','staff_entry') NOT NULL DEFAULT 'shared',
  `shared_value` text DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access_fields`
--

INSERT INTO `access_fields` (`id`, `experiment_id`, `condition_id`, `field_key`, `label`, `value_type`, `value_source`, `shared_value`, `is_visible`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 'pid', 'PID', 'pid', 'staff_entry', NULL, 1, 0, '2026-05-12 07:28:54', '2026-05-12 07:49:45'),
(2, 1, NULL, 'survey_url', 'Umfrage-Link', 'url', 'staff_entry', NULL, 1, 1, '2026-05-12 07:48:47', '2026-05-12 07:48:47'),
(3, 1, 1, 'chat_url', 'Chat-Link', 'url', 'staff_entry', NULL, 1, 2, '2026-05-12 07:49:22', '2026-05-12 07:49:22'),
(4, 2, NULL, 'experiment_url', 'Experiment-Link', 'url', 'shared', 'https://pecziflo-ba-poc.duckdns.org/', 1, 0, '2026-05-12 13:41:24', '2026-05-12 13:41:24'),
(5, 3, NULL, 'pid', 'PID', 'pid', 'pool', NULL, 1, 0, '2026-05-12 17:57:05', '2026-05-12 17:57:05'),
(6, 3, NULL, 'experiment_url', 'Experiment-Link', 'url', 'shared', 'https://promise-ba-raoul-0d516f2c57d0.herokuapp.com/study', 1, 1, '2026-05-12 17:58:17', '2026-05-12 17:58:17'),
(7, 4, NULL, 'pid', 'PID', 'pid', 'pool', NULL, 1, 0, '2026-05-12 18:55:56', '2026-05-12 18:55:56'),
(8, 4, 3, 'chat_url', 'Chat-Link', 'url', 'pool', NULL, 1, 1, '2026-05-12 18:56:57', '2026-05-12 18:56:57'),
(10, 5, NULL, 'pid', 'PID', 'pid', 'pool', NULL, 1, 0, '2026-05-12 20:13:43', '2026-05-12 20:13:43'),
(11, 5, NULL, 'experiment_url', 'Experiment-Link', 'url', 'shared', 'https://docs.google.com/forms/d/e/1FAIpQLSfAAyrsSAUzuA2eW_jNiZrAJ8lvIybGXh_jxqwgyIi0uB6evQ/viewform', 1, 1, '2026-05-12 20:14:18', '2026-05-12 20:14:18'),
(12, 5, NULL, 'instruction', 'Instruktion', 'text', 'shared', 'Lesen Sie das in der Mail mitgesendete HelveVista_Evaluation.pdf und halten Sie die beiden anderen Dokumente (VA_Zimmermann_Laura.pdf, IK_Zimmermann_Laura.pdf) bereit.', 1, 2, '2026-05-12 20:35:40', '2026-05-12 20:35:40'),
(13, 4, 4, 'avatar_url', 'Avatar-Link', 'url', 'pool', NULL, 1, 2, '2026-05-12 22:05:08', '2026-05-12 22:05:08'),
(14, 4, NULL, 'survey_url', 'Umfrage-Link', 'url', 'pool', NULL, 1, 3, '2026-05-12 22:06:23', '2026-05-12 22:06:23'),
(15, 4, NULL, 'instruction', 'Instruktion', 'text', 'shared', 'Lesen Sie die in der E-Mail enthaltene Instuktion.', 1, 4, '2026-05-12 22:54:58', '2026-05-12 22:54:58'),
(16, 6, NULL, 'declaration', 'Deklaration', 'text', 'shared', 'Wir interessieren uns für die Nutzung eines Tools und für deine Meinung zum Tool. Wir brauchen nicht zu wissen, wer du bist. Wenn wir von dir Kontaktangaben haben, werden wir trotzdem nicht wissen, welche Gespräche und welche Meinungen zu diesen Angaben gehören. Alles, was das Tool aufnimmt, wird von uns anonym erfasst und bearbeitet. Alle Daten werden ausschliesslich von uns verwendet und niemals weitergegeben. Das Tool benutzt GPT bei OpenAI, um die Gespräche zu transkribieren und extrahieren. Wir sind von der Hochschule der Angewandten Wissenschaften (ZHAW) und der Universität Zürich (UZH). Alles, was du mit uns tust, behandeln wir anonym und behalten es für uns. Alexandre de Spindler verantwortet diese Untersuchung. Bei Fragen, Unklarheiten oder Anmerkungen kannst du ihm eine E-Mail oder auf Teams schreiben.', 1, 0, '2026-05-13 06:59:39', '2026-05-26 14:02:34'),
(17, 8, 5, 'model_instanciation_a', 'Model Instanciation A', 'text', 'shared', '{     \"condition\": \"A\",     \"entity type A\": \"Company\",     \"entity type B\": \"Employee\",     \"entity type C\": \"Manager\",     \"attribute a1\": \"companyName\",     \"attribute a2\": \"headquartersCity\",     \"attribute b\": \"emailAddress\",     \"attribute c\": \"managementLevel\",     \"relationship ab\": \"employs\",     \"relationship bc\": \"reportsTo\",     \"inheritance C from B\": \"Manager extends Employee\"   }', 1, 0, '2026-05-18 17:13:36', '2026-05-18 17:13:56'),
(18, 8, 6, 'model_instanciation_b', 'Model Instanciation B', 'text', 'shared', '{     \"condition\": \"B\",     \"entity type A\": \"University\",     \"entity type B\": \"Course\",     \"entity type C\": \"LabCourse\",     \"attribute a1\": \"universityName\",     \"attribute a2\": \"campusCity\",     \"attribute b\": \"courseTitle\",     \"attribute c\": \"labRoom\",     \"relationship ab\": \"offers\",     \"relationship bc\": \"hasPrerequisite\",     \"inheritance C from B\": \"LabCourse extends Course\"   }', 1, 1, '2026-05-18 17:14:36', '2026-05-18 17:14:49'),
(19, 8, 7, 'model_instanciation_c', 'Model Instanciation C', 'text', 'shared', '{     \"condition\": \"C\",     \"entity type A\": \"LibraryBranch\",     \"entity type B\": \"LibraryItem\",     \"entity type C\": \"Book\",     \"attribute a1\": \"branchName\",     \"attribute a2\": \"streetAddress\",     \"attribute b\": \"catalogCode\",     \"attribute c\": \"isbn\",     \"relationship ab\": \"stores\",     \"relationship bc\": \"belongsToSeries\",     \"inheritance C from B\": \"Book extends LibraryItem\"   }', 1, 2, '2026-05-18 17:15:24', '2026-05-18 17:15:24'),
(20, 8, 8, 'model_instanciation_d', 'Model Instanciation D', 'text', 'shared', '{     \"condition\": \"D\",     \"entity type A\": \"Restaurant\",     \"entity type B\": \"MenuItem\",     \"entity type C\": \"ChefSpecial\",     \"attribute a1\": \"restaurantName\",     \"attribute a2\": \"cuisineType\",     \"attribute b\": \"itemName\",     \"attribute c\": \"featuredWeek\",     \"relationship ab\": \"serves\",     \"relationship bc\": \"pairsWith\",     \"inheritance C from B\": \"ChefSpecial extends MenuItem\"   }', 1, 3, '2026-05-18 17:16:01', '2026-05-18 17:16:01'),
(21, 8, 9, 'model_instanciation_e', 'Model Instanciation E', 'text', 'shared', '{     \"condition\": \"E\",     \"entity type A\": \"Museum\",     \"entity type B\": \"Exhibit\",     \"entity type C\": \"InteractiveExhibit\",     \"attribute a1\": \"museumName\",     \"attribute a2\": \"openingYear\",     \"attribute b\": \"exhibitTitle\",     \"attribute c\": \"interactionType\",     \"relationship ab\": \"displays\",     \"relationship bc\": \"isGuidedBy\",     \"inheritance C from B\": \"InteractiveExhibit extends Exhibit\"   }', 1, 4, '2026-05-18 17:16:44', '2026-05-18 17:16:44'),
(22, 8, 10, 'model_instanciation_f', 'Model Instanciation F', 'text', 'shared', '{     \"condition\": \"F\",     \"entity type A\": \"FitnessCenter\",     \"entity type B\": \"TrainingSession\",     \"entity type C\": \"PersonalTrainingSession\",     \"attribute a1\": \"centerName\",     \"attribute a2\": \"membershipTier\",     \"attribute b\": \"sessionDate\",     \"attribute c\": \"trainerName\",     \"relationship ab\": \"schedules\",     \"relationship bc\": \"followsProgram\",     \"inheritance C from B\": \"PersonalTrainingSession extends TrainingSession\"   }', 1, 5, '2026-05-18 17:17:28', '2026-05-18 17:17:28'),
(23, 8, 11, 'model_instanciation_g', 'Model Instanciation G', 'text', 'shared', '{     \"condition\": \"G\",     \"entity type A\": \"Airport\",     \"entity type B\": \"Flight\",     \"entity type C\": \"InternationalFlight\",     \"attribute a1\": \"airportCode\",     \"attribute a2\": \"city\",     \"attribute b\": \"flightNumber\",     \"attribute c\": \"passportControlGate\",     \"relationship ab\": \"departs\",     \"relationship bc\": \"connectsTo\",     \"inheritance C from B\": \"InternationalFlight extends Flight\"   }', 1, 6, '2026-05-18 17:18:06', '2026-05-18 17:18:06'),
(24, 8, 12, 'model_instanciation_h', 'Model Instanciation H', 'text', 'shared', '{     \"condition\": \"H\",     \"entity type A\": \"FilmStudio\",     \"entity type B\": \"Production\",     \"entity type C\": \"FeatureFilm\",     \"attribute a1\": \"studioName\",     \"attribute a2\": \"foundingYear\",     \"attribute b\": \"productionTitle\",     \"attribute c\": \"runtimeMinutes\",     \"relationship ab\": \"produces\",     \"relationship bc\": \"usesSourceMaterial\",     \"inheritance C from B\": \"FeatureFilm extends Production\"   }', 1, 7, '2026-05-18 17:19:21', '2026-05-18 17:20:43'),
(25, 8, 13, 'model_instanciation_i', 'Model Instanciation I', 'text', 'shared', '{     \"condition\": \"I\",     \"entity type A\": \"Farm\",     \"entity type B\": \"CropBatch\",     \"entity type C\": \"OrganicCropBatch\",     \"attribute a1\": \"farmName\",     \"attribute a2\": \"region\",     \"attribute b\": \"harvestDate\",     \"attribute c\": \"certificationCode\",     \"relationship ab\": \"cultivates\",     \"relationship bc\": \"derivedFrom\",     \"inheritance C from B\": \"OrganicCropBatch extends CropBatch\"   }', 1, 8, '2026-05-18 17:20:01', '2026-05-18 17:20:01'),
(26, 7, NULL, 'pid', 'PID', 'pid', 'pool', NULL, 1, 0, '2026-05-20 08:09:08', '2026-05-20 08:31:25'),
(27, 7, NULL, 'experiment_url', 'Experiment Link', 'url', 'shared', 'https://attune-radio.vercel.app/', 1, 1, '2026-05-20 08:10:02', '2026-05-20 08:11:40'),
(28, 7, NULL, 'access_code', 'Access Code', 'text', 'shared', 'zhaw-winterthur-bsc2026-28', 1, 2, '2026-05-20 08:11:15', '2026-05-20 08:11:15'),
(29, 6, NULL, 'survey_url', 'Umfrage Link', 'url', 'shared', 'https://www.uzh.ch/zi/cl/surveys/index.php/189166?lang=de-easy', 1, 1, '2026-05-26 13:55:20', '2026-05-26 13:55:20'),
(30, 6, NULL, 'participant_id', 'Participant ID', 'pid', 'staff_entry', NULL, 1, 2, '2026-05-26 13:55:20', '2026-05-26 13:55:20'),
(31, 6, NULL, 'team_id', 'Team ID', 'text', 'staff_entry', NULL, 1, 3, '2026-05-26 13:55:20', '2026-05-26 13:55:20'),
(32, 6, NULL, 'role', 'Rolle', 'text', 'staff_entry', NULL, 1, 4, '2026-05-26 13:55:20', '2026-05-26 13:55:20');

-- --------------------------------------------------------

--
-- Table structure for table `access_pool_rows`
--

CREATE TABLE `access_pool_rows` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `experiment_id` bigint(20) UNSIGNED NOT NULL,
  `condition_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_assigned` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_participation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access_pool_rows`
--

INSERT INTO `access_pool_rows` (`id`, `experiment_id`, `condition_id`, `is_assigned`, `assigned_participation_id`, `assigned_at`, `created_at`) VALUES
(42, 3, NULL, 1, 152, '2026-05-13 13:18:53', '2026-05-12 18:31:55'),
(43, 3, NULL, 1, 133, '2026-05-13 10:18:44', '2026-05-12 18:31:55'),
(44, 3, NULL, 1, 192, '2026-05-13 19:04:46', '2026-05-12 18:31:55'),
(45, 3, NULL, 1, 86, '2026-05-12 20:42:14', '2026-05-12 18:31:55'),
(46, 3, NULL, 0, NULL, NULL, '2026-05-12 18:31:55'),
(47, 3, NULL, 1, 227, '2026-05-14 07:07:18', '2026-05-12 18:31:55'),
(48, 3, NULL, 1, 90, '2026-05-12 20:55:30', '2026-05-12 18:31:55'),
(49, 3, NULL, 1, 155, '2026-05-13 13:27:21', '2026-05-12 18:31:55'),
(50, 3, NULL, 1, 162, '2026-05-13 13:56:26', '2026-05-12 18:31:55'),
(51, 3, NULL, 1, 158, '2026-05-13 13:37:31', '2026-05-12 18:31:55'),
(52, 3, NULL, 1, 143, '2026-05-13 12:26:51', '2026-05-12 18:31:55'),
(53, 3, NULL, 1, 138, '2026-05-13 11:56:22', '2026-05-12 18:31:55'),
(54, 3, NULL, 1, 117, '2026-05-13 09:05:14', '2026-05-12 18:31:55'),
(55, 3, NULL, 1, 104, '2026-05-13 07:00:09', '2026-05-12 18:31:55'),
(56, 3, NULL, 1, 177, '2026-05-13 14:38:38', '2026-05-12 18:31:55'),
(57, 3, NULL, 0, NULL, NULL, '2026-05-12 18:31:55'),
(58, 3, NULL, 1, 183, '2026-05-13 15:55:52', '2026-05-12 18:31:55'),
(59, 3, NULL, 1, 101, '2026-05-13 06:27:12', '2026-05-12 18:31:55'),
(60, 3, NULL, 1, 120, '2026-05-13 09:24:30', '2026-05-12 18:31:55'),
(61, 3, NULL, 1, 125, '2026-05-13 09:45:12', '2026-05-12 18:31:55'),
(62, 3, NULL, 1, 145, '2026-05-13 12:30:21', '2026-05-12 18:31:55'),
(63, 3, NULL, 1, 179, '2026-05-13 15:22:39', '2026-05-12 18:31:55'),
(64, 3, NULL, 1, 174, '2026-05-13 14:29:03', '2026-05-12 18:31:55'),
(65, 3, NULL, 1, 209, '2026-05-13 19:54:45', '2026-05-12 18:31:55'),
(66, 3, NULL, 1, 88, '2026-05-12 20:44:18', '2026-05-12 18:31:55'),
(67, 3, NULL, 1, 91, '2026-05-12 21:05:25', '2026-05-12 18:31:55'),
(68, 3, NULL, 1, 136, '2026-05-13 11:05:22', '2026-05-12 18:31:55'),
(69, 3, NULL, 1, 87, '2026-05-12 20:43:07', '2026-05-12 18:31:55'),
(70, 3, NULL, 1, 207, '2026-05-13 19:34:33', '2026-05-12 18:31:55'),
(71, 3, NULL, 1, 171, '2026-05-13 14:19:10', '2026-05-12 18:31:55'),
(72, 3, NULL, 0, NULL, NULL, '2026-05-12 18:31:55'),
(73, 3, NULL, 0, NULL, NULL, '2026-05-12 18:31:55'),
(74, 3, NULL, 1, 89, '2026-05-12 20:48:05', '2026-05-12 18:31:55'),
(75, 3, NULL, 1, 140, '2026-05-13 12:03:46', '2026-05-12 18:31:55'),
(76, 3, NULL, 1, 146, '2026-05-13 12:37:56', '2026-05-12 18:31:55'),
(77, 3, NULL, 1, 118, '2026-05-13 09:24:02', '2026-05-12 18:31:55'),
(78, 3, NULL, 1, 84, '2026-05-12 19:52:40', '2026-05-12 18:31:55'),
(79, 3, NULL, 0, NULL, NULL, '2026-05-12 18:31:55'),
(80, 3, NULL, 1, 99, '2026-05-13 04:59:56', '2026-05-12 18:31:55'),
(81, 3, NULL, 1, 137, '2026-05-13 11:07:47', '2026-05-12 18:31:55'),
(82, 3, NULL, 0, NULL, NULL, '2026-05-12 18:31:55'),
(83, 5, NULL, 1, 122, '2026-05-13 09:25:03', '2026-05-12 20:31:22'),
(84, 5, NULL, 1, 248, '2026-05-18 06:37:19', '2026-05-12 20:31:22'),
(85, 5, NULL, 1, 123, '2026-05-13 09:27:48', '2026-05-12 20:31:22'),
(86, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(87, 5, NULL, 1, 115, '2026-05-13 08:41:41', '2026-05-12 20:31:22'),
(88, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(89, 5, NULL, 1, 127, '2026-05-13 09:46:04', '2026-05-12 20:31:22'),
(90, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(91, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(92, 5, NULL, 1, 224, '2026-05-14 06:44:26', '2026-05-12 20:31:22'),
(93, 5, NULL, 1, 139, '2026-05-13 11:57:10', '2026-05-12 20:31:22'),
(94, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(95, 5, NULL, 1, 226, '2026-05-14 07:07:09', '2026-05-12 20:31:22'),
(96, 5, NULL, 1, 204, '2026-05-13 19:21:24', '2026-05-12 20:31:22'),
(97, 5, NULL, 1, 173, '2026-05-13 14:27:28', '2026-05-12 20:31:22'),
(98, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(99, 5, NULL, 1, 249, '2026-05-18 06:49:23', '2026-05-12 20:31:22'),
(100, 5, NULL, 1, 110, '2026-05-13 07:59:32', '2026-05-12 20:31:22'),
(101, 5, NULL, 1, 92, '2026-05-12 21:16:56', '2026-05-12 20:31:22'),
(102, 5, NULL, 1, 129, '2026-05-13 09:57:45', '2026-05-12 20:31:22'),
(103, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(104, 5, NULL, 0, NULL, NULL, '2026-05-12 20:31:22'),
(105, 5, NULL, 1, 116, '2026-05-13 08:43:00', '2026-05-12 20:31:22'),
(306, 4, 3, 1, 153, '2026-05-13 13:19:02', '2026-05-12 22:51:41'),
(307, 4, 3, 1, 109, '2026-05-13 07:58:22', '2026-05-12 22:51:41'),
(308, 4, 3, 1, 191, '2026-05-13 19:04:26', '2026-05-12 22:51:41'),
(309, 4, 3, 1, 176, '2026-05-13 14:30:58', '2026-05-12 22:51:41'),
(310, 4, 3, 1, 114, '2026-05-13 08:40:30', '2026-05-12 22:51:41'),
(311, 4, 3, 1, 121, '2026-05-13 09:24:58', '2026-05-12 22:51:41'),
(312, 4, 3, 1, 119, '2026-05-13 09:24:10', '2026-05-12 22:51:41'),
(313, 4, 3, 1, 175, '2026-05-13 14:29:48', '2026-05-12 22:51:41'),
(314, 4, 3, 1, 221, '2026-05-13 21:57:53', '2026-05-12 22:51:41'),
(315, 4, 3, 1, 105, '2026-05-13 07:05:35', '2026-05-12 22:51:41'),
(316, 4, 3, 1, 165, '2026-05-13 14:02:26', '2026-05-12 22:51:41'),
(317, 4, 3, 1, 172, '2026-05-13 14:21:39', '2026-05-12 22:51:41'),
(318, 4, 3, 1, 113, '2026-05-13 08:27:59', '2026-05-12 22:51:41'),
(319, 4, 3, 1, 112, '2026-05-13 08:20:16', '2026-05-12 22:51:41'),
(320, 4, 3, 1, 157, '2026-05-13 13:33:45', '2026-05-12 22:51:41'),
(321, 4, 3, 1, 206, '2026-05-13 19:30:36', '2026-05-12 22:51:41'),
(322, 4, 3, 1, 100, '2026-05-13 06:06:48', '2026-05-12 22:51:41'),
(323, 4, 3, 1, 148, '2026-05-13 13:09:19', '2026-05-12 22:51:41'),
(324, 4, 3, 1, 141, '2026-05-13 12:20:23', '2026-05-12 22:51:41'),
(325, 4, 3, 1, 181, '2026-05-13 15:52:17', '2026-05-12 22:51:41'),
(326, 4, 4, 0, NULL, NULL, '2026-05-12 22:52:02'),
(327, 4, 4, 1, 184, '2026-05-13 16:30:23', '2026-05-12 22:52:02'),
(328, 4, 4, 0, NULL, NULL, '2026-05-12 22:52:02'),
(329, 4, 4, 1, 132, '2026-05-13 10:10:29', '2026-05-12 22:52:02'),
(330, 4, 4, 1, 106, '2026-05-13 07:08:39', '2026-05-12 22:52:02'),
(331, 4, 4, 1, 124, '2026-05-13 09:35:18', '2026-05-12 22:52:02'),
(332, 4, 4, 1, 151, '2026-05-13 13:18:47', '2026-05-12 22:52:02'),
(333, 4, 4, 1, 144, '2026-05-13 12:29:18', '2026-05-12 22:52:02'),
(334, 4, 4, 1, 182, '2026-05-13 15:52:45', '2026-05-12 22:52:02'),
(335, 4, 4, 1, 102, '2026-05-13 06:42:57', '2026-05-12 22:52:02'),
(336, 4, 4, 1, 185, '2026-05-13 18:06:31', '2026-05-12 22:52:02'),
(337, 4, 4, 1, 164, '2026-05-13 14:00:13', '2026-05-12 22:52:02'),
(338, 4, 4, 1, 154, '2026-05-13 13:20:04', '2026-05-12 22:52:02'),
(339, 4, 4, 1, 107, '2026-05-13 07:08:52', '2026-05-12 22:52:02'),
(340, 4, 4, 1, 166, '2026-05-13 14:16:11', '2026-05-12 22:52:02'),
(341, 4, 4, 1, 135, '2026-05-13 10:20:45', '2026-05-12 22:52:02'),
(342, 4, 4, 0, NULL, NULL, '2026-05-12 22:52:02'),
(343, 4, 4, 1, 214, '2026-05-13 20:28:17', '2026-05-12 22:52:02'),
(344, 4, 4, 1, 108, '2026-05-13 07:46:06', '2026-05-12 22:52:02'),
(345, 4, 4, 1, 186, '2026-05-13 18:21:23', '2026-05-12 22:52:02'),
(346, 7, NULL, 1, 394, '2026-05-23 08:22:01', '2026-05-20 09:38:56'),
(347, 7, NULL, 1, 377, '2026-05-20 17:38:00', '2026-05-20 09:38:56'),
(348, 7, NULL, 1, 387, '2026-05-21 18:19:56', '2026-05-20 09:38:56'),
(349, 7, NULL, 1, 364, '2026-05-20 10:29:28', '2026-05-20 09:38:56'),
(350, 7, NULL, 1, 369, '2026-05-20 11:54:34', '2026-05-20 09:38:56'),
(351, 7, NULL, 1, 370, '2026-05-20 11:59:40', '2026-05-20 09:38:56'),
(352, 7, NULL, 1, 373, '2026-05-20 15:04:07', '2026-05-20 09:38:56'),
(353, 7, NULL, 1, 384, '2026-05-21 15:47:08', '2026-05-20 09:38:56'),
(354, 7, NULL, 1, 400, '2026-05-24 15:54:42', '2026-05-20 09:38:56'),
(355, 7, NULL, 1, 361, '2026-05-20 09:50:35', '2026-05-20 09:38:56'),
(356, 7, NULL, 1, 398, '2026-05-24 09:57:54', '2026-05-20 09:38:56'),
(357, 7, NULL, 1, 360, '2026-05-20 09:48:23', '2026-05-20 09:38:56'),
(358, 7, NULL, 1, 385, '2026-05-21 16:09:13', '2026-05-20 09:38:56'),
(359, 7, NULL, 1, 374, '2026-05-20 16:16:19', '2026-05-20 09:38:56'),
(360, 7, NULL, 1, 366, '2026-05-20 10:39:27', '2026-05-20 09:38:56'),
(361, 7, NULL, 1, 397, '2026-05-24 07:23:51', '2026-05-20 09:38:56'),
(362, 7, NULL, 1, 381, '2026-05-21 10:11:06', '2026-05-20 09:38:56'),
(363, 7, NULL, 1, 396, '2026-05-23 21:20:09', '2026-05-20 09:38:56'),
(364, 7, NULL, 1, 393, '2026-05-22 20:37:29', '2026-05-20 09:38:56'),
(365, 7, NULL, 1, 399, '2026-05-24 11:20:19', '2026-05-20 09:38:56'),
(366, 7, NULL, 1, 395, '2026-05-23 11:07:46', '2026-05-20 09:38:56'),
(367, 7, NULL, 1, 367, '2026-05-20 11:22:08', '2026-05-20 09:38:56'),
(368, 7, NULL, 1, 392, '2026-05-22 16:20:44', '2026-05-20 09:38:56'),
(369, 7, NULL, 1, 365, '2026-05-20 10:29:35', '2026-05-20 09:38:56'),
(370, 7, NULL, 1, 383, '2026-05-21 12:57:01', '2026-05-20 09:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `access_pool_values`
--

CREATE TABLE `access_pool_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pool_row_id` bigint(20) UNSIGNED NOT NULL,
  `field_id` bigint(20) UNSIGNED NOT NULL,
  `field_value` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `access_pool_values`
--

INSERT INTO `access_pool_values` (`id`, `pool_row_id`, `field_id`, `field_value`) VALUES
(42, 42, 5, '8280'),
(43, 43, 5, '4346'),
(44, 44, 5, '6230'),
(45, 45, 5, '6564'),
(46, 46, 5, '7464'),
(47, 47, 5, '5310'),
(48, 48, 5, '5856'),
(49, 49, 5, '5626'),
(50, 50, 5, '4491'),
(51, 51, 5, '5226'),
(52, 52, 5, '4353'),
(53, 53, 5, '5854'),
(54, 54, 5, '5458'),
(55, 55, 5, '6512'),
(56, 56, 5, '4178'),
(57, 57, 5, '6981'),
(58, 58, 5, '6065'),
(59, 59, 5, '7700'),
(60, 60, 5, '3681'),
(61, 61, 5, '7345'),
(62, 62, 5, '5647'),
(63, 63, 5, '7385'),
(64, 64, 5, '6134'),
(65, 65, 5, '7711'),
(66, 66, 5, '3987'),
(67, 67, 5, '4997'),
(68, 68, 5, '8205'),
(69, 69, 5, '5780'),
(70, 70, 5, '4632'),
(71, 71, 5, '5591'),
(72, 72, 5, '3906'),
(73, 73, 5, '5508'),
(74, 74, 5, '5245'),
(75, 75, 5, '4415'),
(76, 76, 5, '5961'),
(77, 77, 5, '4057'),
(78, 78, 5, '6074'),
(79, 79, 5, '5561'),
(80, 80, 5, '3629'),
(81, 81, 5, '3599'),
(82, 82, 5, '3297'),
(83, 83, 10, '4965'),
(84, 84, 10, '6928'),
(85, 85, 10, '5592'),
(86, 86, 10, '6763'),
(87, 87, 10, '5125'),
(88, 88, 10, '3190'),
(89, 89, 10, '5931'),
(90, 90, 10, '3762'),
(91, 91, 10, '4462'),
(92, 92, 10, '3558'),
(93, 93, 10, '3542'),
(94, 94, 10, '3988'),
(95, 95, 10, '8318'),
(96, 96, 10, '8045'),
(97, 97, 10, '6247'),
(98, 98, 10, '5596'),
(99, 99, 10, '4114'),
(100, 100, 10, '6334'),
(101, 101, 10, '3815'),
(102, 102, 10, '3957'),
(103, 103, 10, '7054'),
(104, 104, 10, '7065'),
(105, 105, 10, '5257'),
(626, 306, 7, '5603'),
(627, 306, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(628, 306, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?0b2ffbe3-440e-45dc-990c-083c4febca23'),
(629, 307, 7, '3901'),
(630, 307, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(631, 307, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?18f48b24-efb4-4084-94b1-65b88e13cc52'),
(632, 308, 7, '6828'),
(633, 308, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(634, 308, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?1d0a9a26-c077-4221-9ffa-3ce1eb565ca5'),
(635, 309, 7, '5858'),
(636, 309, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(637, 309, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?217f05a8-27e8-4415-862b-1bb80d6e78ae'),
(638, 310, 7, '3322'),
(639, 310, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(640, 310, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?297a55ed-beef-47a0-be6b-44a2560173d9'),
(641, 311, 7, '3796'),
(642, 311, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(643, 311, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?2dc24ea0-2f3f-4f2f-af3c-eaa1921713e7'),
(644, 312, 7, '5669'),
(645, 312, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(646, 312, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?2e48dcb3-a251-4a33-8b7d-d0491e8f4ca1'),
(647, 313, 7, '4440'),
(648, 313, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(649, 313, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?31dfb71f-12b0-4734-b9ab-b4ddebf5c6a8'),
(650, 314, 7, '3357'),
(651, 314, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(652, 314, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?34715a54-dea2-4b22-a8c4-852960ab6c60'),
(653, 315, 7, '4932'),
(654, 315, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(655, 315, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?39781ca8-516a-4eff-aa33-d50e102ab04c'),
(656, 316, 7, '7735'),
(657, 316, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(658, 316, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?3d5a9349-4091-493d-84d7-761792fc0414'),
(659, 317, 7, '5097'),
(660, 317, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(661, 317, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?3e9dee63-b1a8-422d-b223-c343ceb965fd'),
(662, 318, 7, '3453'),
(663, 318, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(664, 318, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?439461f6-5801-452f-ae0c-3bc7e8c4cfce'),
(665, 319, 7, '3534'),
(666, 319, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(667, 319, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?476bb655-dc93-4ccd-98df-a0a2e025dbbd'),
(668, 320, 7, '5471'),
(669, 320, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(670, 320, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?4e78d1d6-b534-4b5e-abaf-0b2a7b00039f'),
(671, 321, 7, '4861'),
(672, 321, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(673, 321, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?51b5adb6-1aa9-4263-bd3e-cb310e321f27'),
(674, 322, 7, '4059'),
(675, 322, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(676, 322, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?5854cadd-cf9f-4216-9652-992a6744a34d'),
(677, 323, 7, '7475'),
(678, 323, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(679, 323, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?66d668f7-a075-4ca5-bdff-c62bab4d7141'),
(680, 324, 7, '3371'),
(681, 324, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(682, 324, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?7b3e1891-49b6-4852-a237-c52c470c90d9'),
(683, 325, 7, '7825'),
(684, 325, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(685, 325, 8, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/text/index.html?7e4b56ca-fbc3-4c4b-9c8c-a3a62f987e2d'),
(686, 326, 7, '4417'),
(687, 326, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(688, 326, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?84ec3c25-2cf3-4d99-b9c8-4aef913a06e2'),
(689, 327, 7, '4951'),
(690, 327, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(691, 327, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?85074661-9e4f-46e4-97dc-af1bbe1de985'),
(692, 328, 7, '7993'),
(693, 328, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(694, 328, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?928bd767-b37f-419d-b94b-57dcf4a87e9b'),
(695, 329, 7, '5461'),
(696, 329, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(697, 329, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?931be2cb-5669-4013-a979-57fdff10ed50'),
(698, 330, 7, '4734'),
(699, 330, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(700, 330, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?97c3a867-17d2-4a2f-a6a4-71f1d29f4f2c'),
(701, 331, 7, '8441'),
(702, 331, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(703, 331, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?9d8256c0-26e4-458e-9ef1-8a4f5deb6e7f'),
(704, 332, 7, '4147'),
(705, 332, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(706, 332, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?a562b2d0-bff7-4a66-9aae-b6013dbc94f2'),
(707, 333, 7, '3774'),
(708, 333, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(709, 333, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?a69122fe-3fe8-4783-a1e1-26ad7029feb8'),
(710, 334, 7, '6830'),
(711, 334, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(712, 334, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?b3516006-b54b-4a49-bbc7-e8587a9f2603'),
(713, 335, 7, '5524'),
(714, 335, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(715, 335, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?b64e92ac-d8fa-4c5d-9fc6-ca1ff86f1360'),
(716, 336, 7, '6673'),
(717, 336, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(718, 336, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?d8850632-9141-4451-813f-c1886ad5744c'),
(719, 337, 7, '4100'),
(720, 337, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(721, 337, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?dbb9555d-5966-4add-a97c-99c564cc82cf'),
(722, 338, 7, '4203'),
(723, 338, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(724, 338, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?dbdae811-0b23-4d6c-9ef3-0eec97239812'),
(725, 339, 7, '3180'),
(726, 339, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(727, 339, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?dbe92548-768d-456d-95a1-8f64eb637ecb'),
(728, 340, 7, '7306'),
(729, 340, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(730, 340, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?dd8ac534-e086-46fb-a5e9-02b3a9df9107'),
(731, 341, 7, '4458'),
(732, 341, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(733, 341, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?e79835fe-9433-419d-9523-7aa0b6d55a2b'),
(734, 342, 7, '6683'),
(735, 342, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(736, 342, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?ea874469-64b4-430a-b6a3-051dc7f65fb3'),
(737, 343, 7, '3306'),
(738, 343, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(739, 343, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?f3fbfe46-bb26-4892-9e45-048b17d70d5b'),
(740, 344, 7, '5028'),
(741, 344, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(742, 344, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?fdde4404-c495-4aba-a426-799550b53476'),
(743, 345, 7, '3214'),
(744, 345, 14, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_2f9eRWK5x6fgDau'),
(745, 345, 13, 'https://bachelor-avatar-bot-baskaber-ae7d276dc901.herokuapp.com/index.html?fef5675b-95ed-4f49-af91-146f882a4a1e'),
(746, 346, 26, '4946'),
(747, 347, 26, '3794'),
(748, 348, 26, '7821'),
(749, 349, 26, '7764'),
(750, 350, 26, '7678'),
(751, 351, 26, '5489'),
(752, 352, 26, '8175'),
(753, 353, 26, '6275'),
(754, 354, 26, '4967'),
(755, 355, 26, '7231'),
(756, 356, 26, '5901'),
(757, 357, 26, '6437'),
(758, 358, 26, '7373'),
(759, 359, 26, '6117'),
(760, 360, 26, '7461'),
(761, 361, 26, '7264'),
(762, 362, 26, '7890'),
(763, 363, 26, '3306'),
(764, 364, 26, '4450'),
(765, 365, 26, '5013'),
(766, 366, 26, '6432'),
(767, 367, 26, '4614'),
(768, 368, 26, '4761'),
(769, 369, 26, '3602'),
(770, 370, 26, '4491');

-- --------------------------------------------------------

--
-- Table structure for table `allowed_students`
--

CREATE TABLE `allowed_students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `allowed_students`
--

INSERT INTO `allowed_students` (`id`, `student_email`, `created_at`) VALUES
(1, 'ahilaayu@students.zhaw.ch', '2026-05-12 05:28:04'),
(2, 'ahmedkas@students.zhaw.ch', '2026-05-12 05:28:04'),
(3, 'alijasan@students.zhaw.ch', '2026-05-12 05:28:04'),
(4, 'altaymet@students.zhaw.ch', '2026-05-12 05:28:04'),
(5, 'altinker@students.zhaw.ch', '2026-05-12 05:28:04'),
(6, 'arenilui@students.zhaw.ch', '2026-05-12 05:28:04'),
(7, 'bachmad3@students.zhaw.ch', '2026-05-12 05:28:04'),
(8, 'baersim1@students.zhaw.ch', '2026-05-12 05:28:04'),
(9, 'bakisnus@students.zhaw.ch', '2026-05-12 05:28:04'),
(10, 'beparsad@students.zhaw.ch', '2026-05-12 05:28:04'),
(11, 'bereri01@students.zhaw.ch', '2026-05-12 05:28:04'),
(12, 'blassroc@students.zhaw.ch', '2026-05-12 05:28:04'),
(13, 'brogeluc@students.zhaw.ch', '2026-05-12 05:28:04'),
(14, 'bruneand@students.zhaw.ch', '2026-05-12 05:28:04'),
(15, 'cabdiabd@students.zhaw.ch', '2026-05-12 05:28:04'),
(16, 'dalipval@students.zhaw.ch', '2026-05-12 05:28:04'),
(17, 'debelno1@students.zhaw.ch', '2026-05-12 05:28:04'),
(18, 'dietisve@students.zhaw.ch', '2026-05-12 05:28:04'),
(19, 'difrodar@students.zhaw.ch', '2026-05-12 05:28:04'),
(20, 'dumandil@students.zhaw.ch', '2026-05-12 05:28:04'),
(21, 'duriqdon@students.zhaw.ch', '2026-05-12 05:28:04'),
(22, 'elcali01@students.zhaw.ch', '2026-05-12 05:28:04'),
(23, 'elshaklo@students.zhaw.ch', '2026-05-12 05:28:04'),
(24, 'engeljar@students.zhaw.ch', '2026-05-12 05:28:04'),
(25, 'erikcley@students.zhaw.ch', '2026-05-12 05:28:04'),
(26, 'eymansaa@students.zhaw.ch', '2026-05-12 05:28:04'),
(27, 'farizeni@students.zhaw.ch', '2026-05-12 05:28:04'),
(28, 'fawaziss@students.zhaw.ch', '2026-05-12 05:28:04'),
(29, 'feerdav1@students.zhaw.ch', '2026-05-12 05:28:04'),
(30, 'ferrepa1@students.zhaw.ch', '2026-05-12 05:28:04'),
(31, 'fischjad@students.zhaw.ch', '2026-05-12 05:28:04'),
(32, 'freilen1@students.zhaw.ch', '2026-05-12 05:28:04'),
(33, 'garciign@students.zhaw.ch', '2026-05-12 05:28:04'),
(34, 'grueteri@students.zhaw.ch', '2026-05-12 05:28:04'),
(35, 'haerrbas@students.zhaw.ch', '2026-05-12 05:28:04'),
(36, 'haslejoe@students.zhaw.ch', '2026-05-12 05:28:04'),
(37, 'helgrem1@students.zhaw.ch', '2026-05-12 05:28:04'),
(38, 'hengaric@students.zhaw.ch', '2026-05-12 05:28:04'),
(39, 'iljazmar@students.zhaw.ch', '2026-05-12 05:28:04'),
(40, 'imeridon@students.zhaw.ch', '2026-05-12 05:28:04'),
(41, 'kadoland@students.zhaw.ch', '2026-05-12 05:28:04'),
(42, 'kenkasha@students.zhaw.ch', '2026-05-12 05:28:04'),
(43, 'kermoala@students.zhaw.ch', '2026-05-12 05:28:04'),
(44, 'kerndan1@students.zhaw.ch', '2026-05-12 05:28:04'),
(45, 'kienemic@students.zhaw.ch', '2026-05-12 05:28:04'),
(46, 'knezemak@students.zhaw.ch', '2026-05-12 05:28:04'),
(47, 'kuhnlev2@students.zhaw.ch', '2026-05-12 05:28:04'),
(48, 'laadi001@students.zhaw.ch', '2026-05-12 05:28:04'),
(49, 'lingor01@students.zhaw.ch', '2026-05-12 05:28:04'),
(50, 'martim07@students.zhaw.ch', '2026-05-12 05:28:04'),
(51, 'matovale@students.zhaw.ch', '2026-05-12 05:28:04'),
(52, 'moergnoe@students.zhaw.ch', '2026-05-12 05:28:04'),
(53, 'mohamma1@students.zhaw.ch', '2026-05-12 05:28:04'),
(54, 'muralar1@students.zhaw.ch', '2026-05-12 05:28:04'),
(55, 'muslidri@students.zhaw.ch', '2026-05-12 05:28:04'),
(56, 'novaiand@students.zhaw.ch', '2026-05-12 05:28:04'),
(57, 'okaemr01@students.zhaw.ch', '2026-05-12 05:28:04'),
(58, 'ordonheb@students.zhaw.ch', '2026-05-12 05:28:04'),
(59, 'pachuval@students.zhaw.ch', '2026-05-12 05:28:04'),
(60, 'pejakkri@students.zhaw.ch', '2026-05-12 05:28:04'),
(61, 'perkokri@students.zhaw.ch', '2026-05-12 05:28:04'),
(62, 'piccalor@students.zhaw.ch', '2026-05-12 05:28:04'),
(63, 'powroluk@students.zhaw.ch', '2026-05-12 05:28:04'),
(64, 'pupovalb@students.zhaw.ch', '2026-05-12 05:28:04'),
(65, 'radovmar@students.zhaw.ch', '2026-05-12 05:28:04'),
(66, 'rahmaann@students.zhaw.ch', '2026-05-12 05:28:04'),
(67, 'rajarvee@students.zhaw.ch', '2026-05-12 05:28:04'),
(68, 'ramaale1@students.zhaw.ch', '2026-05-12 05:28:04'),
(69, 'reihwlia@students.zhaw.ch', '2026-05-12 05:28:04'),
(70, 'rexheeri@students.zhaw.ch', '2026-05-12 05:28:04'),
(71, 'rittiele@students.zhaw.ch', '2026-05-12 05:28:04'),
(72, 'rueegsi7@students.zhaw.ch', '2026-05-12 05:28:04'),
(73, 'russoren@students.zhaw.ch', '2026-05-12 05:28:04'),
(74, 'rutzeeri@students.zhaw.ch', '2026-05-12 05:28:04'),
(75, 'salcelor@students.zhaw.ch', '2026-05-12 05:28:04'),
(76, 'schjan06@students.zhaw.ch', '2026-05-12 05:28:04'),
(77, 'schmusan@students.zhaw.ch', '2026-05-12 05:28:04'),
(78, 'schulpa5@students.zhaw.ch', '2026-05-12 05:28:04'),
(79, 'senththe@students.zhaw.ch', '2026-05-12 05:28:04'),
(80, 'sharis01@students.zhaw.ch', '2026-05-12 05:28:04'),
(81, 'simiciva@students.zhaw.ch', '2026-05-12 05:28:04'),
(82, 'simicjas@students.zhaw.ch', '2026-05-12 05:28:04'),
(83, 'singhsub@students.zhaw.ch', '2026-05-12 05:28:04'),
(84, 'sommecin@students.zhaw.ch', '2026-05-12 05:28:04'),
(85, 'soutorub@students.zhaw.ch', '2026-05-12 05:28:04'),
(86, 'stanisaw@students.zhaw.ch', '2026-05-12 05:28:04'),
(87, 'steinjo6@students.zhaw.ch', '2026-05-12 05:28:04'),
(88, 'stermmur@students.zhaw.ch', '2026-05-12 05:28:04'),
(89, 'stettbea@students.zhaw.ch', '2026-05-12 05:28:04'),
(90, 'storztyl@students.zhaw.ch', '2026-05-12 05:28:04'),
(91, 'stroelau@students.zhaw.ch', '2026-05-12 05:28:04'),
(92, 'stumpmar@students.zhaw.ch', '2026-05-12 05:28:04'),
(93, 'sueruvol@students.zhaw.ch', '2026-05-12 05:28:04'),
(94, 'thurnkev@students.zhaw.ch', '2026-05-12 05:28:04'),
(95, 'ullmaart@students.zhaw.ch', '2026-05-12 05:28:04'),
(96, 'vadaclin@students.zhaw.ch', '2026-05-12 05:28:04'),
(97, 'vignamaa@students.zhaw.ch', '2026-05-12 05:28:04'),
(98, 'vogmar04@students.zhaw.ch', '2026-05-12 05:28:04'),
(99, 'vukcema1@students.zhaw.ch', '2026-05-12 05:28:04'),
(100, 'wallrtim@students.zhaw.ch', '2026-05-12 05:28:04'),
(101, 'walteemm@students.zhaw.ch', '2026-05-12 05:28:04'),
(102, 'werzben1@students.zhaw.ch', '2026-05-12 05:28:04'),
(103, 'willnkan@students.zhaw.ch', '2026-05-12 05:28:04'),
(104, 'zenulske@students.zhaw.ch', '2026-05-12 05:28:04'),
(105, 'zimmech5@students.zhaw.ch', '2026-05-12 05:28:04'),
(106, 'desa@students.zhaw.ch', '2026-05-12 18:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `participation_id` bigint(20) UNSIGNED NOT NULL,
  `appointment_text` varchar(255) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `participation_id`, `appointment_text`, `updated_at`) VALUES
(2, 237, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30'),
(3, 380, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(4, 195, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(5, 215, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(6, 379, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(7, 245, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(8, 236, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(9, 355, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30'),
(10, 352, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(11, 190, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(12, 197, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(13, 213, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30'),
(14, 199, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(15, 203, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30'),
(16, 356, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(17, 200, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(18, 201, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(19, 178, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(20, 378, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(21, 391, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(22, 147, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(23, 210, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(24, 402, '08:00 - 09:15 Uhr', '2026-05-26 20:26:52'),
(25, 246, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(26, 254, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(27, 234, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30'),
(28, 253, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(29, 233, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(30, 243, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30'),
(31, 229, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(32, 230, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(33, 188, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(34, 169, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(35, 202, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(36, 235, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(37, 376, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(38, 193, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(39, 357, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(40, 232, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(41, 161, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(42, 238, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(43, 239, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(44, 134, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(45, 252, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(46, 241, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(47, 371, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(48, 368, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(49, 386, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(50, 212, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(51, 247, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(52, 196, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(53, 211, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30'),
(54, 156, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(55, 189, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(56, 362, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(57, 160, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(58, 223, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(59, 111, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(60, 128, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(61, 142, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(62, 244, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(63, 208, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(64, 375, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(65, 220, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(66, 390, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(67, 358, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(68, 170, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(69, 231, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(70, 159, '14:05 - 15:25 Uhr', '2026-05-26 13:19:30'),
(71, 163, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(72, 131, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(73, 130, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(74, 198, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(75, 168, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(76, 222, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(77, 363, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(78, 388, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(79, 218, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(80, 217, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(81, 187, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(82, 150, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(83, 250, '13:00 - 14:15 Uhr', '2026-05-26 13:19:30'),
(84, 149, '10:15 - 11:35 Uhr', '2026-05-26 13:19:30'),
(85, 205, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(86, 194, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(87, 225, '09:05 - 10:25 Uhr', '2026-05-26 13:19:30'),
(88, 167, '08:00 - 09:15 Uhr', '2026-05-26 13:19:30'),
(89, 372, '15:15 - 16:35 Uhr', '2026-05-26 13:19:30');

-- --------------------------------------------------------

--
-- Table structure for table `eligibility_field_values`
--

CREATE TABLE `eligibility_field_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `eligibility_id` bigint(20) UNSIGNED NOT NULL,
  `field_id` bigint(20) UNSIGNED NOT NULL,
  `field_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `eligibility_field_values`
--

INSERT INTO `eligibility_field_values` (`id`, `eligibility_id`, `field_id`, `field_value`, `updated_at`) VALUES
(1, 21, 1, '96', '2026-05-12 11:31:16'),
(2, 21, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_yNAfwZG4m6XG6Gi&_g_=g', '2026-05-12 11:31:16'),
(3, 21, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=dac07b16-026d-492f-a007-6486dcf3d516', '2026-05-12 11:31:16'),
(4, 16, 1, '140', '2026-05-12 11:31:16'),
(5, 16, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_e2BpWjcQgdPT410&_g_=g', '2026-05-12 11:31:16'),
(6, 16, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=092c7e0a-3aa6-4be9-a232-7c38a695ddd9', '2026-05-12 11:31:16'),
(7, 23, 1, '89', '2026-05-12 11:31:16'),
(8, 23, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_9exeGf3bRfaZTRb&_g_=g', '2026-05-12 11:31:16'),
(9, 23, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=9bf5a516-73ca-4294-a0dd-260d1f201ace', '2026-05-12 11:31:16'),
(10, 10, 1, '88', '2026-05-12 11:31:16'),
(11, 10, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_LzE2z5mbmdspHQD&_g_=g', '2026-05-12 11:31:16'),
(12, 10, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=a10cb165-cb39-415d-94ac-7d83040919a9', '2026-05-12 11:31:16'),
(13, 12, 1, '131', '2026-05-12 11:31:16'),
(14, 12, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_uTCmtr3JtZDgujX&_g_=g', '2026-05-12 11:31:16'),
(15, 12, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=bb3c8970-f165-4e07-9882-667db1ea8a5e', '2026-05-12 11:31:16'),
(16, 5, 1, '90', '2026-05-12 11:31:16'),
(17, 5, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_axT9Fd8TpdrEVu8&_g_=g', '2026-05-12 11:31:16'),
(18, 5, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=83dfe60b-2659-4876-ad21-e0af378f1b5f', '2026-05-12 11:31:16'),
(19, 13, 1, '87', '2026-05-12 11:31:16'),
(20, 13, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_vXznhKZuQfkdt3M&_g_=g', '2026-05-12 11:31:16'),
(21, 13, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=176a7905-9c3b-40f2-9608-83082d3c1f32', '2026-05-12 11:31:16'),
(22, 11, 1, '98', '2026-05-12 11:31:16'),
(23, 11, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_cxR1ZqsWXqAxkg6&_g_=g', '2026-05-12 11:31:16'),
(24, 11, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=4ec48220-f537-4cef-b0cc-4481f8267bf1', '2026-05-12 11:31:16'),
(25, 19, 1, '92', '2026-05-12 11:31:16'),
(26, 19, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_iqD0UJzVSG5i1nM&_g_=g', '2026-05-12 11:31:16'),
(27, 19, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=d0caa13d-1e85-4b59-81b5-41a8e0430419', '2026-05-12 11:31:16'),
(28, 6, 1, '110', '2026-05-12 11:31:16'),
(29, 6, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_OdVwG0IAnEPfi7o&_g_=g', '2026-05-12 11:31:16'),
(30, 6, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=1fd93d56-2be1-4eae-a392-4abd2d71c6ca', '2026-05-12 11:31:16'),
(31, 9, 1, '107', '2026-05-12 11:31:16'),
(32, 9, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_SUG1F27qxLUADI1&_g_=g', '2026-05-12 11:31:16'),
(33, 9, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=08569374-7f74-4aa6-916b-2ae90d682989', '2026-05-12 11:31:16'),
(34, 17, 1, '115', '2026-05-12 11:31:16'),
(35, 17, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_riKucJc0pQVj4gz&_g_=g', '2026-05-12 11:31:16'),
(36, 17, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=966e73fb-ac78-4dd7-9da0-c008fcdb681c', '2026-05-12 11:31:16'),
(37, 22, 1, '111', '2026-05-12 11:31:16'),
(38, 22, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_gHVwKTAPy0xh0OC&_g_=g', '2026-05-12 11:31:16'),
(39, 22, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=1d3670d8-45f2-4f99-b60a-7d6325a0ebca', '2026-05-12 11:31:16'),
(40, 18, 1, '113', '2026-05-12 11:31:16'),
(41, 18, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_lRRzGCOBfA8eDYk&_g_=g', '2026-05-12 11:31:16'),
(42, 18, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=1320c35e-ac32-42ab-805a-40be9458b781', '2026-05-12 11:31:16'),
(43, 20, 1, '91', '2026-05-12 11:31:16'),
(44, 20, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_0qzQNXgcNTx1VBc&_g_=g', '2026-05-12 11:31:16'),
(45, 20, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=be36eb7f-d78c-4c39-869f-53fd7e42dcde', '2026-05-12 11:31:16'),
(46, 8, 1, '126', '2026-05-12 11:31:16'),
(47, 8, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_pVDoIbmV4vn2ZgC&_g_=g', '2026-05-12 11:31:16'),
(48, 8, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=51153fc1-29bd-4044-8e98-479f4e4c0961', '2026-05-12 11:31:16'),
(49, 15, 1, '136', '2026-05-12 11:31:16'),
(50, 15, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_dtnvn7TMUfPLoRg&_g_=g', '2026-05-12 11:31:16'),
(51, 15, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=05f0e922-6bba-4847-9d36-4e8926081e72', '2026-05-12 11:31:16'),
(52, 7, 1, '108', '2026-05-12 11:31:16'),
(53, 7, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_wzDVhpaKG0VWVHH&_g_=g', '2026-05-12 11:31:16'),
(54, 7, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=d0655ce5-94e9-454f-a689-59dc0f0d03a6', '2026-05-12 11:31:16'),
(55, 14, 1, '134', '2026-05-12 11:31:16'),
(56, 14, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_xp4GQe8lalyE6gw&_g_=g', '2026-05-12 11:31:16'),
(57, 14, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=cc76ccac-a884-41d6-a825-9b0e5ce3519d', '2026-05-12 11:31:16');

-- --------------------------------------------------------

--
-- Table structure for table `experiments`
--

CREATE TABLE `experiments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `public_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_open` tinyint(1) NOT NULL DEFAULT 0,
  `eligibility_mode` enum('all_allowed','selected') NOT NULL DEFAULT 'selected',
  `condition_mode` enum('none','student_choice','assigned') NOT NULL DEFAULT 'none',
  `requires_time_slot` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `experiments`
--

INSERT INTO `experiments` (`id`, `public_name`, `description`, `is_open`, `eligibility_mode`, `condition_mode`, `requires_time_slot`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Experiment 1a', 'Melisa', 0, 'selected', 'assigned', 0, 0, '2026-05-12 07:28:12', '2026-05-20 07:10:56'),
(2, 'Experiment 1b', 'Flo', 0, 'selected', 'assigned', 0, 1, '2026-05-12 13:38:34', '2026-05-20 07:12:16'),
(3, 'Experiment 2a', 'Raoul', 0, 'selected', 'assigned', 0, 3, '2026-05-12 14:44:55', '2026-05-20 07:16:07'),
(4, 'Experiment 2b', 'Berkant', 0, 'selected', 'assigned', 0, 4, '2026-05-12 18:41:52', '2026-05-20 07:21:41'),
(5, 'Experiment 1c', 'HelveVista', 0, 'selected', 'none', 0, 2, '2026-05-12 19:57:53', '2026-05-20 06:58:17'),
(6, 'Experiment 3', NULL, 0, 'all_allowed', 'none', 1, 6, '2026-05-12 23:04:22', '2026-05-28 05:21:41'),
(7, 'Experiment 2c', 'Deadline: Sonntag, 24. Mai, Mitternacht.', 0, 'selected', 'assigned', 0, 5, '2026-05-12 23:08:52', '2026-05-26 08:24:11'),
(8, 'Hackathon', 'Angaben zur Vervollständigung der Projektarbeiten', 0, 'all_allowed', 'assigned', 0, 9, '2026-05-18 12:35:37', '2026-05-19 09:16:22'),
(9, 'Experiment 3b', NULL, 0, 'selected', 'none', 0, 12, '2026-05-28 05:34:30', '2026-05-28 05:36:28');

-- --------------------------------------------------------

--
-- Table structure for table `experiment_conditions`
--

CREATE TABLE `experiment_conditions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `experiment_id` bigint(20) UNSIGNED NOT NULL,
  `public_name` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `experiment_conditions`
--

INSERT INTO `experiment_conditions` (`id`, `experiment_id`, `public_name`, `sort_order`, `created_at`) VALUES
(1, 1, 'Text', 0, '2026-05-12 07:28:22'),
(2, 1, 'Tablet', 0, '2026-05-12 07:28:25'),
(3, 4, 'Text', 0, '2026-05-12 18:54:20'),
(4, 4, 'Heygen', 1, '2026-05-12 18:54:35'),
(5, 8, 'A', 0, '2026-05-18 12:49:02'),
(6, 8, 'B', 1, '2026-05-18 12:49:05'),
(7, 8, 'C', 2, '2026-05-18 12:49:08'),
(8, 8, 'D', 3, '2026-05-18 12:49:12'),
(9, 8, 'E', 4, '2026-05-18 12:49:24'),
(10, 8, 'F', 5, '2026-05-18 12:49:27'),
(11, 8, 'G', 6, '2026-05-18 12:49:30'),
(12, 8, 'H', 7, '2026-05-18 12:51:35'),
(13, 8, 'I', 8, '2026-05-18 12:51:44');

-- --------------------------------------------------------

--
-- Table structure for table `experiment_eligibilities`
--

CREATE TABLE `experiment_eligibilities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `experiment_id` bigint(20) UNSIGNED NOT NULL,
  `student_email` varchar(255) NOT NULL,
  `condition_id` bigint(20) UNSIGNED DEFAULT NULL,
  `source` enum('manual','random','system') NOT NULL DEFAULT 'manual',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `experiment_eligibilities`
--

INSERT INTO `experiment_eligibilities` (`id`, `experiment_id`, `student_email`, `condition_id`, `source`, `created_at`) VALUES
(5, 1, 'difrodar@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(6, 1, 'kuhnlev2@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(7, 1, 'stanisaw@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(8, 1, 'salcelor@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(9, 1, 'muslidri@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(10, 1, 'bakisnus@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(11, 1, 'kenkasha@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(12, 1, 'bruneand@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(13, 1, 'fawaziss@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(14, 1, 'wallrtim@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(15, 1, 'sommecin@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(16, 1, 'arenilui@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(17, 1, 'novaiand@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(18, 1, 'ramaale1@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(19, 1, 'kerndan1@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(20, 1, 'russoren@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(21, 1, 'altaymet@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(22, 1, 'rahmaann@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(23, 1, 'baersim1@students.zhaw.ch', 1, 'random', '2026-05-12 07:52:16'),
(24, 2, 'storztyl@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(25, 2, 'alijasan@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(26, 2, 'willnkan@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(27, 2, 'stermmur@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(28, 2, 'vogmar04@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(29, 2, 'haerrbas@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(30, 2, 'schmusan@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(31, 2, 'haslejoe@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(32, 2, 'ferrepa1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(33, 2, 'dalipval@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(34, 2, 'farizeni@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(35, 2, 'werzben1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(36, 2, 'muralar1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(37, 2, 'blassroc@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(38, 2, 'zenulske@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(39, 2, 'laadi001@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(40, 2, 'grueteri@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(41, 2, 'kadoland@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(42, 2, 'pachuval@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(43, 2, 'erikcley@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(44, 2, 'dumandil@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(45, 2, 'senththe@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(46, 2, 'bereri01@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(47, 2, 'moergnoe@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(48, 2, 'schjan06@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(49, 2, 'stettbea@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(50, 2, 'reihwlia@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(51, 2, 'pupovalb@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(52, 2, 'pejakkri@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(53, 2, 'iljazmar@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(54, 2, 'cabdiabd@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(55, 2, 'powroluk@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(56, 2, 'imeridon@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(57, 2, 'eymansaa@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(58, 2, 'dietisve@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(59, 2, 'thurnkev@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(60, 2, 'garciign@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(61, 2, 'beparsad@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(62, 2, 'elcali01@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(63, 2, 'piccalor@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(64, 2, 'debelno1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(65, 2, 'vadaclin@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(66, 2, 'engeljar@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(67, 2, 'feerdav1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(68, 2, 'brogeluc@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(69, 2, 'okaemr01@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(70, 2, 'rajarvee@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(71, 2, 'bachmad3@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(72, 2, 'schulpa5@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(73, 2, 'helgrem1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(74, 2, 'martim07@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(75, 2, 'sharis01@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(76, 2, 'stroelau@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(77, 2, 'vignamaa@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(78, 2, 'altinker@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:03'),
(79, 2, 'ahilaayu@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(80, 2, 'freilen1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(81, 2, 'elshaklo@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(82, 2, 'stumpmar@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(83, 2, 'fischjad@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(84, 2, 'perkokri@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(85, 2, 'singhsub@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(86, 2, 'steinjo6@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:03:04'),
(87, 3, 'kermoala@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:06'),
(88, 3, 'schulpa5@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:06'),
(89, 3, 'vogmar04@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(90, 3, 'vukcema1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(91, 3, 'stermmur@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(92, 3, 'iljazmar@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(93, 3, 'brogeluc@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(94, 3, 'stanisaw@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(95, 3, 'sueruvol@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(96, 3, 'bakisnus@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(97, 3, 'difrodar@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(98, 3, 'soutorub@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(99, 3, 'reihwlia@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(100, 3, 'laadi001@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(101, 3, 'bereri01@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(102, 3, 'dalipval@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(103, 3, 'okaemr01@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(104, 3, 'stroelau@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(105, 3, 'zimmech5@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(106, 3, 'engeljar@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(107, 3, 'sommecin@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(108, 3, 'arenilui@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(109, 3, 'elshaklo@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(110, 3, 'rexheeri@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(111, 3, 'dumandil@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(112, 3, 'rutzeeri@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(113, 3, 'hengaric@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(114, 3, 'pachuval@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(115, 3, 'ahilaayu@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(116, 3, 'zenulske@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(117, 3, 'pejakkri@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(118, 3, 'rueegsi7@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(119, 3, 'kerndan1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(120, 3, 'werzben1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(121, 3, 'garciign@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(122, 3, 'freilen1@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(123, 3, 'rittiele@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(124, 3, 'knezemak@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(125, 3, 'simiciva@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(126, 3, 'martim07@students.zhaw.ch', NULL, 'manual', '2026-05-12 14:53:07'),
(127, 3, 'desa@students.zhaw.ch', NULL, 'manual', '2026-05-12 18:30:21'),
(128, 4, 'ahmedkas@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(129, 4, 'alijasan@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(130, 4, 'altaymet@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(131, 4, 'altinker@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(132, 4, 'bachmad3@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(133, 4, 'baersim1@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(134, 4, 'beparsad@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(135, 4, 'blassroc@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(136, 4, 'bruneand@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(137, 4, 'cabdiabd@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(138, 4, 'debelno1@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(139, 4, 'dietisve@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(140, 4, 'duriqdon@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(141, 4, 'elcali01@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(142, 4, 'erikcley@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(143, 4, 'eymansaa@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(144, 4, 'farizeni@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(145, 4, 'fawaziss@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(146, 4, 'feerdav1@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(147, 4, 'ferrepa1@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(148, 4, 'fischjad@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(149, 4, 'grueteri@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(150, 4, 'haerrbas@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(151, 4, 'haslejoe@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(152, 4, 'helgrem1@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(153, 4, 'imeridon@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(154, 4, 'kadoland@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(155, 4, 'kenkasha@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(156, 4, 'kienemic@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(157, 4, 'kuhnlev2@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(158, 4, 'lingor01@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(159, 4, 'matovale@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(160, 4, 'moergnoe@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(161, 4, 'mohamma1@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(162, 4, 'muralar1@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(163, 4, 'muslidri@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(164, 4, 'novaiand@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(165, 4, 'ordonheb@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(166, 4, 'perkokri@students.zhaw.ch', 4, 'random', '2026-05-12 19:06:56'),
(167, 4, 'piccalor@students.zhaw.ch', 3, 'random', '2026-05-12 19:06:56'),
(168, 5, 'ahmedkas@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(169, 5, 'duriqdon@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(170, 5, 'hengaric@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(171, 5, 'kermoala@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(172, 5, 'kienemic@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(173, 5, 'knezemak@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(174, 5, 'lingor01@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(175, 5, 'matovale@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(176, 5, 'mohamma1@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(177, 5, 'ordonheb@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(178, 5, 'radovmar@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(179, 5, 'rexheeri@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(180, 5, 'rittiele@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(181, 5, 'rueegsi7@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(182, 5, 'rutzeeri@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(183, 5, 'simiciva@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(184, 5, 'simicjas@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(185, 5, 'soutorub@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(186, 5, 'ullmaart@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(187, 5, 'vukcema1@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(188, 5, 'walteemm@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(189, 5, 'zimmech5@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(190, 5, 'sueruvol@students.zhaw.ch', NULL, 'manual', '2026-05-12 20:30:46'),
(191, 8, 'ahilaayu@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:14'),
(192, 8, 'ahmedkas@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:14'),
(193, 8, 'alijasan@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:14'),
(194, 8, 'altaymet@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:14'),
(195, 8, 'altinker@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:14'),
(196, 8, 'arenilui@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:14'),
(197, 8, 'bachmad3@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:14'),
(198, 8, 'baersim1@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:14'),
(199, 8, 'bakisnus@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:14'),
(200, 8, 'beparsad@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:14'),
(201, 8, 'bereri01@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:14'),
(202, 8, 'blassroc@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:14'),
(203, 8, 'brogeluc@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:14'),
(204, 8, 'bruneand@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:14'),
(205, 8, 'cabdiabd@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:14'),
(206, 8, 'dalipval@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:14'),
(207, 8, 'debelno1@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:14'),
(208, 8, 'desa@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:14'),
(209, 8, 'dietisve@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:14'),
(210, 8, 'difrodar@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:15'),
(211, 8, 'dumandil@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(212, 8, 'duriqdon@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:15'),
(213, 8, 'elcali01@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(214, 8, 'elshaklo@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(215, 8, 'engeljar@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(216, 8, 'erikcley@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(217, 8, 'eymansaa@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(218, 8, 'farizeni@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(219, 8, 'fawaziss@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(220, 8, 'feerdav1@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(221, 8, 'ferrepa1@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:15'),
(222, 8, 'fischjad@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(223, 8, 'freilen1@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(224, 8, 'garciign@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(225, 8, 'grueteri@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(226, 8, 'haerrbas@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(227, 8, 'haslejoe@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(228, 8, 'helgrem1@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(229, 8, 'hengaric@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(230, 8, 'iljazmar@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(231, 8, 'imeridon@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:15'),
(232, 8, 'kadoland@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(233, 8, 'kenkasha@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(234, 8, 'kermoala@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(235, 8, 'kerndan1@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(236, 8, 'kienemic@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(237, 8, 'knezemak@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(238, 8, 'kuhnlev2@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(239, 8, 'laadi001@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(240, 8, 'lingor01@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(241, 8, 'martim07@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(242, 8, 'matovale@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(243, 8, 'moergnoe@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(244, 8, 'mohamma1@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(245, 8, 'muralar1@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(246, 8, 'muslidri@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(247, 8, 'novaiand@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(248, 8, 'okaemr01@students.zhaw.ch', 13, 'random', '2026-05-18 17:21:15'),
(249, 8, 'ordonheb@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(250, 8, 'pachuval@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(251, 8, 'pejakkri@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(252, 8, 'perkokri@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(253, 8, 'piccalor@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(254, 8, 'powroluk@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(255, 8, 'pupovalb@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(256, 8, 'radovmar@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(257, 8, 'rahmaann@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(258, 8, 'rajarvee@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(259, 8, 'ramaale1@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(260, 8, 'reihwlia@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(261, 8, 'rexheeri@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(262, 8, 'rittiele@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(263, 8, 'rueegsi7@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(264, 8, 'russoren@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(265, 8, 'rutzeeri@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(266, 8, 'salcelor@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(267, 8, 'schjan06@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(268, 8, 'schmusan@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(269, 8, 'schulpa5@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(270, 8, 'senththe@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(271, 8, 'sharis01@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(272, 8, 'simiciva@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(273, 8, 'simicjas@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(274, 8, 'singhsub@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(275, 8, 'sommecin@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(276, 8, 'soutorub@students.zhaw.ch', 12, 'random', '2026-05-18 17:21:15'),
(277, 8, 'stanisaw@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(278, 8, 'steinjo6@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(279, 8, 'stermmur@students.zhaw.ch', 7, 'random', '2026-05-18 17:21:15'),
(280, 8, 'stettbea@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(281, 8, 'storztyl@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(282, 8, 'stroelau@students.zhaw.ch', 11, 'random', '2026-05-18 17:21:15'),
(283, 8, 'stumpmar@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(284, 8, 'sueruvol@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(285, 8, 'thurnkev@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(286, 8, 'ullmaart@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(287, 8, 'vadaclin@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(288, 8, 'vignamaa@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(289, 8, 'vogmar04@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(290, 8, 'vukcema1@students.zhaw.ch', 8, 'random', '2026-05-18 17:21:15'),
(291, 8, 'wallrtim@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(292, 8, 'walteemm@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(293, 8, 'werzben1@students.zhaw.ch', 10, 'random', '2026-05-18 17:21:15'),
(294, 8, 'willnkan@students.zhaw.ch', 9, 'random', '2026-05-18 17:21:15'),
(295, 8, 'zenulske@students.zhaw.ch', 5, 'random', '2026-05-18 17:21:15'),
(296, 8, 'zimmech5@students.zhaw.ch', 6, 'random', '2026-05-18 17:21:15'),
(297, 7, 'powroluk@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(298, 7, 'pupovalb@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(299, 7, 'radovmar@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(300, 7, 'rahmaann@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(301, 7, 'rajarvee@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(302, 7, 'ramaale1@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(303, 7, 'russoren@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(304, 7, 'salcelor@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(305, 7, 'schjan06@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(306, 7, 'schmusan@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(307, 7, 'senththe@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(308, 7, 'sharis01@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(309, 7, 'simicjas@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(310, 7, 'singhsub@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(311, 7, 'steinjo6@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(312, 7, 'stettbea@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(313, 7, 'storztyl@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(314, 7, 'stumpmar@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(315, 7, 'thurnkev@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(316, 7, 'ullmaart@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(317, 7, 'vadaclin@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(318, 7, 'vignamaa@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(319, 7, 'wallrtim@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(320, 7, 'walteemm@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(321, 7, 'willnkan@students.zhaw.ch', NULL, 'manual', '2026-05-20 08:28:23'),
(322, 7, 'desa@students.zhaw.ch', NULL, 'manual', '2026-05-20 09:39:28'),
(323, 9, 'wallrtim@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(324, 9, 'ordonheb@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(325, 9, 'schjan06@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(326, 9, 'stumpmar@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(327, 9, 'vukcema1@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(329, 9, 'stermmur@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(330, 9, 'werzben1@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(331, 9, 'dumandil@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(332, 9, 'kienemic@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(333, 9, 'erikcley@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(334, 9, 'dietisve@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(335, 9, 'knezemak@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(336, 9, 'mohamma1@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(337, 9, 'rittiele@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18'),
(338, 9, 'rueegsi7@students.zhaw.ch', NULL, 'manual', '2026-05-28 05:36:18');

-- --------------------------------------------------------

--
-- Table structure for table `participations`
--

CREATE TABLE `participations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `experiment_id` bigint(20) UNSIGNED NOT NULL,
  `condition_id` bigint(20) UNSIGNED DEFAULT NULL,
  `student_email` varchar(255) NOT NULL,
  `access_pool_row_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `participations`
--

INSERT INTO `participations` (`id`, `experiment_id`, `condition_id`, `student_email`, `access_pool_row_id`, `assigned_at`, `confirmed_at`, `created_at`) VALUES
(1, 1, 1, 'altaymet@students.zhaw.ch', NULL, '2026-05-12 11:48:42', '2026-05-12 13:32:48', '2026-05-12 11:48:42'),
(2, 1, 1, 'arenilui@students.zhaw.ch', NULL, '2026-05-12 11:49:55', '2026-05-12 13:33:08', '2026-05-12 11:49:55'),
(3, 1, 1, 'bakisnus@students.zhaw.ch', NULL, '2026-05-12 11:50:19', '2026-05-12 13:32:49', '2026-05-12 11:50:19'),
(4, 1, 1, 'kuhnlev2@students.zhaw.ch', NULL, '2026-05-12 11:51:12', '2026-05-12 13:33:06', '2026-05-12 11:51:12'),
(5, 1, 1, 'muslidri@students.zhaw.ch', NULL, '2026-05-12 11:51:25', '2026-05-12 13:32:51', '2026-05-12 11:51:25'),
(6, 1, 1, 'novaiand@students.zhaw.ch', NULL, '2026-05-12 11:51:34', '2026-05-12 13:33:05', '2026-05-12 11:51:34'),
(7, 1, 1, 'baersim1@students.zhaw.ch', NULL, '2026-05-12 13:20:54', '2026-05-12 13:32:52', '2026-05-12 13:20:54'),
(8, 1, 1, 'bruneand@students.zhaw.ch', NULL, '2026-05-12 13:28:02', '2026-05-12 13:33:03', '2026-05-12 13:28:02'),
(9, 1, 1, 'difrodar@students.zhaw.ch', NULL, '2026-05-12 13:28:41', '2026-05-12 13:32:53', '2026-05-12 13:28:41'),
(10, 1, 1, 'fawaziss@students.zhaw.ch', NULL, '2026-05-12 13:29:04', '2026-05-12 13:33:02', '2026-05-12 13:29:04'),
(11, 1, 1, 'kenkasha@students.zhaw.ch', NULL, '2026-05-12 13:29:19', '2026-05-12 13:32:55', '2026-05-12 13:29:19'),
(12, 1, 1, 'kerndan1@students.zhaw.ch', NULL, '2026-05-12 13:29:26', '2026-05-12 13:33:00', '2026-05-12 13:29:26'),
(13, 1, 1, 'rahmaann@students.zhaw.ch', NULL, '2026-05-12 13:29:44', '2026-05-12 13:32:56', '2026-05-12 13:29:44'),
(14, 1, 1, 'ramaale1@students.zhaw.ch', NULL, '2026-05-12 13:29:59', '2026-05-12 13:32:57', '2026-05-12 13:29:59'),
(15, 1, 1, 'russoren@students.zhaw.ch', NULL, '2026-05-12 13:30:15', '2026-05-12 13:32:58', '2026-05-12 13:30:15'),
(16, 1, 1, 'salcelor@students.zhaw.ch', NULL, '2026-05-12 13:30:28', '2026-05-12 13:32:39', '2026-05-12 13:30:28'),
(17, 1, 1, 'sommecin@students.zhaw.ch', NULL, '2026-05-12 13:30:41', '2026-05-12 13:32:38', '2026-05-12 13:30:41'),
(18, 1, 1, 'stanisaw@students.zhaw.ch', NULL, '2026-05-12 13:30:57', '2026-05-12 13:32:36', '2026-05-12 13:30:57'),
(19, 1, 1, 'wallrtim@students.zhaw.ch', NULL, '2026-05-12 13:31:11', '2026-05-12 13:32:34', '2026-05-12 13:31:11'),
(20, 2, NULL, 'erikcley@students.zhaw.ch', NULL, '2026-05-12 14:03:57', '2026-05-16 11:30:02', '2026-05-12 14:03:57'),
(21, 2, NULL, 'eymansaa@students.zhaw.ch', NULL, '2026-05-12 14:04:12', '2026-05-16 11:30:22', '2026-05-12 14:04:12'),
(22, 2, NULL, 'farizeni@students.zhaw.ch', NULL, '2026-05-12 14:04:26', '2026-05-16 11:30:38', '2026-05-12 14:04:26'),
(23, 2, NULL, 'feerdav1@students.zhaw.ch', NULL, '2026-05-12 14:04:32', '2026-05-16 11:30:56', '2026-05-12 14:04:32'),
(24, 2, NULL, 'ferrepa1@students.zhaw.ch', NULL, '2026-05-12 14:04:40', '2026-05-16 11:31:10', '2026-05-12 14:04:40'),
(25, 2, NULL, 'fischjad@students.zhaw.ch', NULL, '2026-05-12 14:04:49', '2026-05-16 11:31:42', '2026-05-12 14:04:49'),
(26, 2, NULL, 'freilen1@students.zhaw.ch', NULL, '2026-05-12 14:04:57', '2026-05-16 11:31:47', '2026-05-12 14:04:57'),
(27, 2, NULL, 'garciign@students.zhaw.ch', NULL, '2026-05-12 14:05:09', '2026-05-16 11:37:00', '2026-05-12 14:05:09'),
(28, 2, NULL, 'grueteri@students.zhaw.ch', NULL, '2026-05-12 14:05:16', '2026-05-16 11:37:05', '2026-05-12 14:05:16'),
(29, 2, NULL, 'haerrbas@students.zhaw.ch', NULL, '2026-05-12 14:05:24', '2026-05-16 11:37:17', '2026-05-12 14:05:24'),
(30, 2, NULL, 'haslejoe@students.zhaw.ch', NULL, '2026-05-12 14:05:38', '2026-05-16 11:37:28', '2026-05-12 14:05:38'),
(31, 2, NULL, 'helgrem1@students.zhaw.ch', NULL, '2026-05-12 14:05:45', '2026-05-16 11:37:39', '2026-05-12 14:05:45'),
(32, 2, NULL, 'iljazmar@students.zhaw.ch', NULL, '2026-05-12 14:05:52', '2026-05-16 11:37:59', '2026-05-12 14:05:52'),
(33, 2, NULL, 'imeridon@students.zhaw.ch', NULL, '2026-05-12 14:06:01', '2026-05-16 11:38:15', '2026-05-12 14:06:01'),
(34, 2, NULL, 'kadoland@students.zhaw.ch', NULL, '2026-05-12 14:06:08', '2026-05-16 11:38:28', '2026-05-12 14:06:08'),
(35, 2, NULL, 'laadi001@students.zhaw.ch', NULL, '2026-05-12 14:06:17', '2026-05-16 11:38:36', '2026-05-12 14:06:17'),
(36, 2, NULL, 'martim07@students.zhaw.ch', NULL, '2026-05-12 14:06:24', '2026-05-16 11:38:22', '2026-05-12 14:06:24'),
(37, 2, NULL, 'moergnoe@students.zhaw.ch', NULL, '2026-05-12 14:06:33', '2026-05-16 11:38:09', '2026-05-12 14:06:33'),
(38, 2, NULL, 'muralar1@students.zhaw.ch', NULL, '2026-05-12 14:06:41', '2026-05-16 11:37:47', '2026-05-12 14:06:41'),
(39, 2, NULL, 'okaemr01@students.zhaw.ch', NULL, '2026-05-12 14:06:49', '2026-05-16 11:37:32', '2026-05-12 14:06:49'),
(40, 2, NULL, 'pachuval@students.zhaw.ch', NULL, '2026-05-12 14:06:58', '2026-05-16 11:37:26', '2026-05-12 14:06:58'),
(41, 2, NULL, 'pejakkri@students.zhaw.ch', NULL, '2026-05-12 14:07:18', '2026-05-16 11:37:08', '2026-05-12 14:07:18'),
(42, 2, NULL, 'perkokri@students.zhaw.ch', NULL, '2026-05-12 14:07:25', '2026-05-16 11:37:02', '2026-05-12 14:07:25'),
(43, 2, NULL, 'piccalor@students.zhaw.ch', NULL, '2026-05-12 14:07:34', '2026-05-16 11:36:55', '2026-05-12 14:07:34'),
(44, 2, NULL, 'powroluk@students.zhaw.ch', NULL, '2026-05-12 14:07:42', '2026-05-16 11:36:48', '2026-05-12 14:07:42'),
(45, 2, NULL, 'pupovalb@students.zhaw.ch', NULL, '2026-05-12 14:07:49', '2026-05-16 11:35:16', '2026-05-12 14:07:49'),
(46, 2, NULL, 'rajarvee@students.zhaw.ch', NULL, '2026-05-12 14:07:58', '2026-05-16 11:35:09', '2026-05-12 14:07:58'),
(47, 2, NULL, 'reihwlia@students.zhaw.ch', NULL, '2026-05-12 14:08:07', '2026-05-16 11:34:49', '2026-05-12 14:08:07'),
(48, 2, NULL, 'schjan06@students.zhaw.ch', NULL, '2026-05-12 14:08:17', '2026-05-16 11:34:36', '2026-05-12 14:08:17'),
(49, 2, NULL, 'schmusan@students.zhaw.ch', NULL, '2026-05-12 14:08:25', '2026-05-16 11:34:30', '2026-05-12 14:08:25'),
(50, 2, NULL, 'schulpa5@students.zhaw.ch', NULL, '2026-05-12 14:08:34', '2026-05-16 11:34:25', '2026-05-12 14:08:34'),
(51, 2, NULL, 'senththe@students.zhaw.ch', NULL, '2026-05-12 14:08:43', '2026-05-16 11:29:45', '2026-05-12 14:08:43'),
(52, 2, NULL, 'sharis01@students.zhaw.ch', NULL, '2026-05-12 14:08:53', '2026-05-16 11:29:40', '2026-05-12 14:08:53'),
(53, 2, NULL, 'singhsub@students.zhaw.ch', NULL, '2026-05-12 14:09:01', '2026-05-16 11:28:32', '2026-05-12 14:09:01'),
(54, 2, NULL, 'steinjo6@students.zhaw.ch', NULL, '2026-05-12 14:09:10', '2026-05-16 11:28:21', '2026-05-12 14:09:10'),
(55, 2, NULL, 'stermmur@students.zhaw.ch', NULL, '2026-05-12 14:09:21', '2026-05-16 11:28:11', '2026-05-12 14:09:21'),
(56, 2, NULL, 'stettbea@students.zhaw.ch', NULL, '2026-05-12 14:09:31', '2026-05-16 11:28:01', '2026-05-12 14:09:31'),
(57, 2, NULL, 'storztyl@students.zhaw.ch', NULL, '2026-05-12 14:09:40', '2026-05-16 11:27:46', '2026-05-12 14:09:40'),
(58, 2, NULL, 'stroelau@students.zhaw.ch', NULL, '2026-05-12 14:09:49', '2026-05-16 11:27:42', '2026-05-12 14:09:49'),
(59, 2, NULL, 'stumpmar@students.zhaw.ch', NULL, '2026-05-12 14:09:58', '2026-05-16 11:27:38', '2026-05-12 14:09:58'),
(60, 2, NULL, 'thurnkev@students.zhaw.ch', NULL, '2026-05-12 14:11:15', '2026-05-16 11:27:25', '2026-05-12 14:11:15'),
(61, 2, NULL, 'vadaclin@students.zhaw.ch', NULL, '2026-05-12 14:11:29', '2026-05-16 11:27:16', '2026-05-12 14:11:29'),
(62, 2, NULL, 'vignamaa@students.zhaw.ch', NULL, '2026-05-12 14:11:44', '2026-05-16 11:26:47', '2026-05-12 14:11:44'),
(63, 2, NULL, 'werzben1@students.zhaw.ch', NULL, '2026-05-12 14:12:02', '2026-05-16 11:26:40', '2026-05-12 14:12:02'),
(64, 2, NULL, 'willnkan@students.zhaw.ch', NULL, '2026-05-12 14:12:11', '2026-05-16 11:26:28', '2026-05-12 14:12:11'),
(65, 2, NULL, 'zenulske@students.zhaw.ch', NULL, '2026-05-12 14:12:20', '2026-05-16 11:26:23', '2026-05-12 14:12:20'),
(66, 2, NULL, 'ahilaayu@students.zhaw.ch', NULL, '2026-05-12 14:12:31', '2026-05-16 11:08:02', '2026-05-12 14:12:31'),
(67, 2, NULL, 'alijasan@students.zhaw.ch', NULL, '2026-05-12 14:12:46', '2026-05-16 11:07:09', '2026-05-12 14:12:46'),
(68, 2, NULL, 'altinker@students.zhaw.ch', NULL, '2026-05-12 14:12:54', '2026-05-16 11:07:01', '2026-05-12 14:12:54'),
(69, 2, NULL, 'bachmad3@students.zhaw.ch', NULL, '2026-05-12 14:13:08', '2026-05-16 11:06:40', '2026-05-12 14:13:08'),
(70, 2, NULL, 'beparsad@students.zhaw.ch', NULL, '2026-05-12 14:13:19', '2026-05-16 11:06:29', '2026-05-12 14:13:19'),
(71, 2, NULL, 'engeljar@students.zhaw.ch', NULL, '2026-05-12 14:13:37', '2026-05-16 11:06:15', '2026-05-12 14:13:37'),
(72, 2, NULL, 'elshaklo@students.zhaw.ch', NULL, '2026-05-12 14:15:08', '2026-05-16 11:06:07', '2026-05-12 14:15:08'),
(73, 2, NULL, 'elcali01@students.zhaw.ch', NULL, '2026-05-12 14:15:15', '2026-05-16 11:05:55', '2026-05-12 14:15:15'),
(74, 2, NULL, 'dumandil@students.zhaw.ch', NULL, '2026-05-12 14:15:24', '2026-05-16 11:05:49', '2026-05-12 14:15:24'),
(75, 2, NULL, 'dietisve@students.zhaw.ch', NULL, '2026-05-12 14:15:34', '2026-05-16 11:05:43', '2026-05-12 14:15:34'),
(76, 2, NULL, 'debelno1@students.zhaw.ch', NULL, '2026-05-12 14:15:42', '2026-05-16 11:05:39', '2026-05-12 14:15:42'),
(77, 2, NULL, 'dalipval@students.zhaw.ch', NULL, '2026-05-12 14:15:49', '2026-05-16 11:05:34', '2026-05-12 14:15:49'),
(78, 2, NULL, 'cabdiabd@students.zhaw.ch', NULL, '2026-05-12 14:16:00', '2026-05-16 11:05:30', '2026-05-12 14:16:00'),
(79, 2, NULL, 'brogeluc@students.zhaw.ch', NULL, '2026-05-12 14:16:07', '2026-05-16 11:05:26', '2026-05-12 14:16:07'),
(80, 2, NULL, 'blassroc@students.zhaw.ch', NULL, '2026-05-12 14:16:15', '2026-05-16 11:05:21', '2026-05-12 14:16:15'),
(81, 2, NULL, 'bereri01@students.zhaw.ch', NULL, '2026-05-12 14:16:22', '2026-05-16 11:05:18', '2026-05-12 14:16:22'),
(82, 2, NULL, 'vogmar04@students.zhaw.ch', NULL, '2026-05-12 14:26:53', '2026-05-20 07:12:46', '2026-05-12 14:26:53'),
(84, 3, NULL, 'stroelau@students.zhaw.ch', 78, '2026-05-12 19:52:40', '2026-05-20 07:47:04', '2026-05-12 19:52:40'),
(86, 3, NULL, 'okaemr01@students.zhaw.ch', 45, '2026-05-12 20:42:14', '2026-05-20 07:41:28', '2026-05-12 20:42:14'),
(87, 3, NULL, 'stermmur@students.zhaw.ch', 69, '2026-05-12 20:43:07', '2026-05-20 07:40:53', '2026-05-12 20:43:07'),
(88, 3, NULL, 'werzben1@students.zhaw.ch', 66, '2026-05-12 20:44:18', '2026-05-20 07:52:30', '2026-05-12 20:44:18'),
(89, 3, NULL, 'engeljar@students.zhaw.ch', 74, '2026-05-12 20:48:05', '2026-05-20 07:41:43', '2026-05-12 20:48:05'),
(90, 3, NULL, 'sommecin@students.zhaw.ch', 48, '2026-05-12 20:55:30', '2026-05-20 07:45:29', '2026-05-12 20:55:30'),
(91, 3, NULL, 'soutorub@students.zhaw.ch', 67, '2026-05-12 21:05:25', '2026-05-20 07:42:13', '2026-05-12 21:05:25'),
(92, 5, NULL, 'soutorub@students.zhaw.ch', 101, '2026-05-12 21:16:56', '2026-05-20 06:59:47', '2026-05-12 21:16:56'),
(99, 3, NULL, 'kerndan1@students.zhaw.ch', 80, '2026-05-13 04:59:56', '2026-05-20 07:42:27', '2026-05-13 04:59:56'),
(100, 4, 3, 'kuhnlev2@students.zhaw.ch', 322, '2026-05-13 06:06:48', '2026-05-20 07:23:38', '2026-05-13 06:06:48'),
(101, 3, NULL, 'vogmar04@students.zhaw.ch', 59, '2026-05-13 06:27:12', '2026-05-20 07:44:29', '2026-05-13 06:27:12'),
(102, 4, 4, 'fischjad@students.zhaw.ch', 335, '2026-05-13 06:42:57', '2026-05-20 07:23:51', '2026-05-13 06:42:57'),
(104, 3, NULL, 'garciign@students.zhaw.ch', 55, '2026-05-13 07:00:09', '2026-05-20 07:46:39', '2026-05-13 07:00:09'),
(105, 4, 3, 'grueteri@students.zhaw.ch', 315, '2026-05-13 07:05:35', '2026-05-20 07:24:11', '2026-05-13 07:05:35'),
(106, 4, 4, 'blassroc@students.zhaw.ch', 330, '2026-05-13 07:08:39', '2026-05-20 07:24:48', '2026-05-13 07:08:39'),
(107, 4, 4, 'beparsad@students.zhaw.ch', 339, '2026-05-13 07:08:52', '2026-05-20 07:24:02', '2026-05-13 07:08:52'),
(108, 4, 4, 'muralar1@students.zhaw.ch', 344, '2026-05-13 07:46:06', '2026-05-20 07:24:20', '2026-05-13 07:46:06'),
(109, 4, 3, 'feerdav1@students.zhaw.ch', 307, '2026-05-13 07:58:22', '2026-05-20 07:23:56', '2026-05-13 07:58:22'),
(110, 5, NULL, 'radovmar@students.zhaw.ch', 100, '2026-05-13 07:59:32', '2026-05-20 07:01:40', '2026-05-13 07:59:32'),
(111, 6, NULL, 'pupovalb@students.zhaw.ch', NULL, '2026-05-13 08:15:44', '2026-05-28 05:07:47', '2026-05-13 08:15:44'),
(112, 4, 3, 'fawaziss@students.zhaw.ch', 319, '2026-05-13 08:20:16', '2026-05-20 07:24:07', '2026-05-13 08:20:16'),
(113, 4, 3, 'moergnoe@students.zhaw.ch', 318, '2026-05-13 08:27:59', '2026-05-20 07:23:43', '2026-05-13 08:27:59'),
(114, 4, 3, 'kadoland@students.zhaw.ch', 310, '2026-05-13 08:40:30', '2026-05-20 07:24:24', '2026-05-13 08:40:30'),
(115, 5, NULL, 'simicjas@students.zhaw.ch', 87, '2026-05-13 08:41:41', '2026-05-20 06:59:56', '2026-05-13 08:41:41'),
(116, 5, NULL, 'simiciva@students.zhaw.ch', 105, '2026-05-13 08:43:00', '2026-05-20 07:00:09', '2026-05-13 08:43:00'),
(117, 3, NULL, 'pejakkri@students.zhaw.ch', 54, '2026-05-13 09:05:14', '2026-05-20 07:48:44', '2026-05-13 09:05:14'),
(118, 3, NULL, 'iljazmar@students.zhaw.ch', 77, '2026-05-13 09:24:02', '2026-05-20 07:44:54', '2026-05-13 09:24:02'),
(119, 4, 3, 'novaiand@students.zhaw.ch', 312, '2026-05-13 09:24:10', '2026-05-20 07:24:41', '2026-05-13 09:24:10'),
(120, 3, NULL, 'simiciva@students.zhaw.ch', 60, '2026-05-13 09:24:30', '2026-05-20 07:44:40', '2026-05-13 09:24:30'),
(121, 4, 3, 'kienemic@students.zhaw.ch', 311, '2026-05-13 09:24:58', '2026-05-20 07:24:16', '2026-05-13 09:24:58'),
(122, 5, NULL, 'ullmaart@students.zhaw.ch', 83, '2026-05-13 09:25:03', '2026-05-20 07:00:24', '2026-05-13 09:25:03'),
(123, 5, NULL, 'kermoala@students.zhaw.ch', 85, '2026-05-13 09:27:48', '2026-05-20 07:01:25', '2026-05-13 09:27:48'),
(124, 4, 4, 'erikcley@students.zhaw.ch', 331, '2026-05-13 09:35:18', '2026-05-20 07:27:07', '2026-05-13 09:35:18'),
(125, 3, NULL, 'rexheeri@students.zhaw.ch', 61, '2026-05-13 09:45:12', '2026-05-20 07:45:08', '2026-05-13 09:45:12'),
(126, 6, NULL, 'erikcley@students.zhaw.ch', NULL, '2026-05-13 09:46:01', NULL, '2026-05-13 09:46:01'),
(127, 5, NULL, 'rexheeri@students.zhaw.ch', 89, '2026-05-13 09:46:04', '2026-05-20 07:01:50', '2026-05-13 09:46:04'),
(128, 6, NULL, 'radovmar@students.zhaw.ch', NULL, '2026-05-13 09:54:25', '2026-05-28 05:07:47', '2026-05-13 09:54:25'),
(129, 5, NULL, 'kienemic@students.zhaw.ch', 102, '2026-05-13 09:57:45', '2026-05-20 07:02:13', '2026-05-13 09:57:45'),
(130, 6, NULL, 'simicjas@students.zhaw.ch', NULL, '2026-05-13 09:58:05', '2026-05-28 05:07:47', '2026-05-13 09:58:05'),
(131, 6, NULL, 'simiciva@students.zhaw.ch', NULL, '2026-05-13 09:58:18', '2026-05-28 05:07:47', '2026-05-13 09:58:18'),
(132, 4, 4, 'baersim1@students.zhaw.ch', 329, '2026-05-13 10:10:29', '2026-05-20 07:25:22', '2026-05-13 10:10:29'),
(133, 3, NULL, 'arenilui@students.zhaw.ch', 43, '2026-05-13 10:18:44', '2026-05-20 07:45:47', '2026-05-13 10:18:44'),
(134, 6, NULL, 'kienemic@students.zhaw.ch', NULL, '2026-05-13 10:19:20', NULL, '2026-05-13 10:19:20'),
(135, 4, 4, 'bruneand@students.zhaw.ch', 341, '2026-05-13 10:20:45', '2026-05-20 07:25:15', '2026-05-13 10:20:45'),
(136, 3, NULL, 'bakisnus@students.zhaw.ch', 68, '2026-05-13 11:05:22', '2026-05-20 07:45:59', '2026-05-13 11:05:22'),
(137, 3, NULL, 'ahilaayu@students.zhaw.ch', 81, '2026-05-13 11:07:47', '2026-05-20 07:46:10', '2026-05-13 11:07:47'),
(138, 3, NULL, 'kermoala@students.zhaw.ch', 53, '2026-05-13 11:56:22', '2026-05-20 07:46:28', '2026-05-13 11:56:22'),
(139, 5, NULL, 'duriqdon@students.zhaw.ch', 93, '2026-05-13 11:57:10', '2026-05-20 07:07:52', '2026-05-13 11:57:10'),
(140, 3, NULL, 'schulpa5@students.zhaw.ch', 75, '2026-05-13 12:03:46', '2026-05-20 07:46:51', '2026-05-13 12:03:46'),
(141, 4, 3, 'haslejoe@students.zhaw.ch', 324, '2026-05-13 12:20:23', '2026-05-20 07:25:08', '2026-05-13 12:20:23'),
(142, 6, NULL, 'rahmaann@students.zhaw.ch', NULL, '2026-05-13 12:22:24', '2026-05-28 05:07:47', '2026-05-13 12:22:24'),
(143, 3, NULL, 'brogeluc@students.zhaw.ch', 52, '2026-05-13 12:26:51', '2026-05-20 07:47:25', '2026-05-13 12:26:51'),
(144, 4, 4, 'duriqdon@students.zhaw.ch', 333, '2026-05-13 12:29:18', '2026-05-20 07:25:29', '2026-05-13 12:29:18'),
(145, 3, NULL, 'elshaklo@students.zhaw.ch', 62, '2026-05-13 12:30:21', '2026-05-20 07:47:47', '2026-05-13 12:30:21'),
(146, 3, NULL, 'pachuval@students.zhaw.ch', 76, '2026-05-13 12:37:56', '2026-05-20 07:47:14', '2026-05-13 12:37:56'),
(147, 6, NULL, 'elshaklo@students.zhaw.ch', NULL, '2026-05-13 12:58:03', '2026-05-28 05:07:47', '2026-05-13 12:58:03'),
(148, 4, 3, 'alijasan@students.zhaw.ch', 323, '2026-05-13 13:09:19', '2026-05-20 07:25:02', '2026-05-13 13:09:19'),
(149, 6, NULL, 'vadaclin@students.zhaw.ch', NULL, '2026-05-13 13:10:26', '2026-05-28 05:07:47', '2026-05-13 13:10:26'),
(150, 6, NULL, 'thurnkev@students.zhaw.ch', NULL, '2026-05-13 13:16:10', '2026-05-28 05:07:47', '2026-05-13 13:16:10'),
(151, 4, 4, 'kenkasha@students.zhaw.ch', 332, '2026-05-13 13:18:47', '2026-05-20 07:25:45', '2026-05-13 13:18:47'),
(152, 3, NULL, 'dumandil@students.zhaw.ch', 42, '2026-05-13 13:18:53', '2026-05-20 07:48:00', '2026-05-13 13:18:53'),
(153, 4, 3, 'ahmedkas@students.zhaw.ch', 306, '2026-05-13 13:19:02', '2026-05-20 07:26:14', '2026-05-13 13:19:02'),
(154, 4, 4, 'ordonheb@students.zhaw.ch', 338, '2026-05-13 13:20:04', '2026-05-20 07:25:35', '2026-05-13 13:20:04'),
(155, 3, NULL, 'rutzeeri@students.zhaw.ch', 49, '2026-05-13 13:27:21', '2026-05-20 07:48:20', '2026-05-13 13:27:21'),
(156, 6, NULL, 'pachuval@students.zhaw.ch', NULL, '2026-05-13 13:30:34', '2026-05-28 05:07:47', '2026-05-13 13:30:34'),
(157, 4, 3, 'piccalor@students.zhaw.ch', 320, '2026-05-13 13:33:45', '2026-05-20 07:24:56', '2026-05-13 13:33:45'),
(158, 3, NULL, 'freilen1@students.zhaw.ch', 51, '2026-05-13 13:37:31', '2026-05-20 07:48:31', '2026-05-13 13:37:31'),
(159, 6, NULL, 'senththe@students.zhaw.ch', NULL, '2026-05-13 13:43:57', '2026-05-28 05:07:47', '2026-05-13 13:43:57'),
(160, 6, NULL, 'piccalor@students.zhaw.ch', NULL, '2026-05-13 13:47:28', '2026-05-28 05:07:47', '2026-05-13 13:47:28'),
(161, 6, NULL, 'kenkasha@students.zhaw.ch', NULL, '2026-05-13 13:50:00', '2026-05-28 05:07:47', '2026-05-13 13:50:00'),
(162, 3, NULL, 'bereri01@students.zhaw.ch', 50, '2026-05-13 13:56:26', '2026-05-20 07:48:55', '2026-05-13 13:56:26'),
(163, 6, NULL, 'sharis01@students.zhaw.ch', NULL, '2026-05-13 13:59:27', '2026-05-28 05:07:47', '2026-05-13 13:59:27'),
(164, 4, 4, 'helgrem1@students.zhaw.ch', 337, '2026-05-13 14:00:13', '2026-05-20 07:25:53', '2026-05-13 14:00:13'),
(165, 4, 3, 'eymansaa@students.zhaw.ch', 316, '2026-05-13 14:02:26', '2026-05-20 07:26:10', '2026-05-13 14:02:26'),
(166, 4, 4, 'haerrbas@students.zhaw.ch', 340, '2026-05-13 14:16:11', '2026-05-20 07:25:49', '2026-05-13 14:16:11'),
(167, 6, NULL, 'willnkan@students.zhaw.ch', NULL, '2026-05-13 14:18:02', '2026-05-28 05:07:47', '2026-05-13 14:18:02'),
(168, 6, NULL, 'sommecin@students.zhaw.ch', NULL, '2026-05-13 14:18:33', '2026-05-28 05:07:47', '2026-05-13 14:18:33'),
(169, 6, NULL, 'haerrbas@students.zhaw.ch', NULL, '2026-05-13 14:18:45', '2026-05-28 05:07:47', '2026-05-13 14:18:45'),
(170, 6, NULL, 'schmusan@students.zhaw.ch', NULL, '2026-05-13 14:19:00', '2026-05-28 05:07:47', '2026-05-13 14:19:00'),
(171, 3, NULL, 'martim07@students.zhaw.ch', 71, '2026-05-13 14:19:10', '2026-05-20 07:49:07', '2026-05-13 14:19:10'),
(172, 4, 3, 'debelno1@students.zhaw.ch', 317, '2026-05-13 14:21:39', '2026-05-20 07:27:20', '2026-05-13 14:21:39'),
(173, 5, NULL, 'hengaric@students.zhaw.ch', 97, '2026-05-13 14:27:28', '2026-05-20 07:09:15', '2026-05-13 14:27:28'),
(174, 3, NULL, 'difrodar@students.zhaw.ch', 64, '2026-05-13 14:29:03', '2026-05-20 07:49:18', '2026-05-13 14:29:03'),
(175, 4, 3, 'farizeni@students.zhaw.ch', 313, '2026-05-13 14:29:48', '2026-05-20 07:27:04', '2026-05-13 14:29:48'),
(176, 4, 3, 'ferrepa1@students.zhaw.ch', 309, '2026-05-13 14:30:58', '2026-05-20 07:27:22', '2026-05-13 14:30:58'),
(177, 3, NULL, 'hengaric@students.zhaw.ch', 56, '2026-05-13 14:38:38', '2026-05-20 07:49:32', '2026-05-13 14:38:38'),
(178, 6, NULL, 'difrodar@students.zhaw.ch', NULL, '2026-05-13 14:42:19', '2026-05-28 05:07:47', '2026-05-13 14:42:19'),
(179, 3, NULL, 'stanisaw@students.zhaw.ch', 63, '2026-05-13 15:22:39', '2026-05-20 07:49:46', '2026-05-13 15:22:39'),
(180, 6, NULL, 'dumandil@students.zhaw.ch', NULL, '2026-05-13 15:43:04', NULL, '2026-05-13 15:43:04'),
(181, 4, 3, 'imeridon@students.zhaw.ch', 325, '2026-05-13 15:52:17', '2026-05-20 07:27:12', '2026-05-13 15:52:17'),
(182, 4, 4, 'cabdiabd@students.zhaw.ch', 334, '2026-05-13 15:52:45', '2026-05-20 07:26:43', '2026-05-13 15:52:45'),
(183, 3, NULL, 'laadi001@students.zhaw.ch', 58, '2026-05-13 15:55:51', '2026-05-20 07:50:05', '2026-05-13 15:55:51'),
(184, 4, 4, 'muslidri@students.zhaw.ch', 327, '2026-05-13 16:30:23', '2026-05-20 07:26:17', '2026-05-13 16:30:23'),
(185, 4, 4, 'perkokri@students.zhaw.ch', 336, '2026-05-13 18:06:31', '2026-05-20 07:26:58', '2026-05-13 18:06:31'),
(186, 4, 4, 'elcali01@students.zhaw.ch', 345, '2026-05-13 18:21:23', '2026-05-20 07:26:55', '2026-05-13 18:21:23'),
(187, 6, NULL, 'stroelau@students.zhaw.ch', NULL, '2026-05-13 18:58:31', '2026-05-28 05:07:47', '2026-05-13 18:58:31'),
(188, 6, NULL, 'grueteri@students.zhaw.ch', NULL, '2026-05-13 19:02:06', '2026-05-28 05:07:47', '2026-05-13 19:02:06'),
(189, 6, NULL, 'pejakkri@students.zhaw.ch', NULL, '2026-05-13 19:03:17', '2026-05-28 05:07:47', '2026-05-13 19:03:17'),
(190, 6, NULL, 'beparsad@students.zhaw.ch', NULL, '2026-05-13 19:03:41', '2026-05-28 05:07:47', '2026-05-13 19:03:41'),
(191, 4, 3, 'altinker@students.zhaw.ch', 308, '2026-05-13 19:04:26', '2026-05-20 07:26:53', '2026-05-13 19:04:26'),
(192, 3, NULL, 'dalipval@students.zhaw.ch', 44, '2026-05-13 19:04:46', '2026-05-20 07:50:13', '2026-05-13 19:04:46'),
(193, 6, NULL, 'iljazmar@students.zhaw.ch', NULL, '2026-05-13 19:05:01', '2026-05-28 05:07:47', '2026-05-13 19:05:01'),
(194, 6, NULL, 'vogmar04@students.zhaw.ch', NULL, '2026-05-13 19:06:29', '2026-05-28 05:07:47', '2026-05-13 19:06:29'),
(195, 6, NULL, 'alijasan@students.zhaw.ch', NULL, '2026-05-13 19:06:46', '2026-05-28 05:07:47', '2026-05-13 19:06:46'),
(196, 6, NULL, 'novaiand@students.zhaw.ch', NULL, '2026-05-13 19:08:29', '2026-05-28 05:07:47', '2026-05-13 19:08:29'),
(197, 6, NULL, 'bereri01@students.zhaw.ch', NULL, '2026-05-13 19:11:49', '2026-05-28 05:07:47', '2026-05-13 19:11:49'),
(198, 6, NULL, 'singhsub@students.zhaw.ch', NULL, '2026-05-13 19:12:01', '2026-05-28 05:07:47', '2026-05-13 19:12:01'),
(199, 6, NULL, 'brogeluc@students.zhaw.ch', NULL, '2026-05-13 19:13:55', '2026-05-28 05:07:47', '2026-05-13 19:13:55'),
(200, 6, NULL, 'dalipval@students.zhaw.ch', NULL, '2026-05-13 19:17:00', '2026-05-28 05:07:47', '2026-05-13 19:17:00'),
(201, 6, NULL, 'debelno1@students.zhaw.ch', NULL, '2026-05-13 19:19:30', '2026-05-28 05:07:47', '2026-05-13 19:19:30'),
(202, 6, NULL, 'haslejoe@students.zhaw.ch', NULL, '2026-05-13 19:19:43', '2026-05-28 05:07:47', '2026-05-13 19:19:43'),
(203, 6, NULL, 'bruneand@students.zhaw.ch', NULL, '2026-05-13 19:20:01', '2026-05-28 05:07:47', '2026-05-13 19:20:01'),
(204, 5, NULL, 'lingor01@students.zhaw.ch', 96, '2026-05-13 19:21:24', '2026-05-20 07:09:30', '2026-05-13 19:21:24'),
(205, 6, NULL, 'vignamaa@students.zhaw.ch', NULL, '2026-05-13 19:27:47', '2026-05-28 05:07:47', '2026-05-13 19:27:47'),
(206, 4, 3, 'lingor01@students.zhaw.ch', 321, '2026-05-13 19:30:36', '2026-05-20 07:27:02', '2026-05-13 19:30:36'),
(207, 3, NULL, 'reihwlia@students.zhaw.ch', 70, '2026-05-13 19:34:33', '2026-05-20 07:51:52', '2026-05-13 19:34:33'),
(208, 6, NULL, 'reihwlia@students.zhaw.ch', NULL, '2026-05-13 19:52:15', '2026-05-28 05:07:47', '2026-05-13 19:52:15'),
(209, 3, NULL, 'zenulske@students.zhaw.ch', 65, '2026-05-13 19:54:45', '2026-05-20 07:52:00', '2026-05-13 19:54:45'),
(210, 6, NULL, 'engeljar@students.zhaw.ch', NULL, '2026-05-13 19:58:14', '2026-05-28 05:07:47', '2026-05-13 19:58:14'),
(211, 6, NULL, 'okaemr01@students.zhaw.ch', NULL, '2026-05-13 20:00:49', '2026-05-28 05:07:47', '2026-05-13 20:00:49'),
(212, 6, NULL, 'muralar1@students.zhaw.ch', NULL, '2026-05-13 20:02:26', '2026-05-28 05:07:47', '2026-05-13 20:02:26'),
(213, 6, NULL, 'blassroc@students.zhaw.ch', NULL, '2026-05-13 20:13:14', '2026-05-28 05:07:47', '2026-05-13 20:13:14'),
(214, 4, 4, 'altaymet@students.zhaw.ch', 343, '2026-05-13 20:28:17', '2026-05-20 07:26:50', '2026-05-13 20:28:17'),
(215, 6, NULL, 'altaymet@students.zhaw.ch', NULL, '2026-05-13 20:48:15', '2026-05-28 05:07:47', '2026-05-13 20:48:15'),
(217, 6, NULL, 'storztyl@students.zhaw.ch', NULL, '2026-05-13 20:59:33', '2026-05-28 05:07:47', '2026-05-13 20:59:33'),
(218, 6, NULL, 'stettbea@students.zhaw.ch', NULL, '2026-05-13 21:07:51', '2026-05-28 05:07:47', '2026-05-13 21:07:51'),
(219, 6, NULL, 'werzben1@students.zhaw.ch', NULL, '2026-05-13 21:10:11', NULL, '2026-05-13 21:10:11'),
(220, 6, NULL, 'russoren@students.zhaw.ch', NULL, '2026-05-13 21:45:27', '2026-05-28 05:07:47', '2026-05-13 21:45:27'),
(221, 4, 3, 'bachmad3@students.zhaw.ch', 314, '2026-05-13 21:57:53', '2026-05-20 07:26:47', '2026-05-13 21:57:53'),
(222, 6, NULL, 'soutorub@students.zhaw.ch', NULL, '2026-05-13 22:05:32', '2026-05-28 05:07:47', '2026-05-13 22:05:32'),
(223, 6, NULL, 'powroluk@students.zhaw.ch', NULL, '2026-05-13 22:17:57', '2026-05-28 05:07:47', '2026-05-13 22:17:57'),
(224, 5, NULL, 'walteemm@students.zhaw.ch', 92, '2026-05-14 06:44:26', '2026-05-20 07:09:36', '2026-05-14 06:44:26'),
(225, 6, NULL, 'walteemm@students.zhaw.ch', NULL, '2026-05-14 06:45:15', '2026-05-28 05:07:47', '2026-05-14 06:45:15'),
(226, 5, NULL, 'vukcema1@students.zhaw.ch', 95, '2026-05-14 07:07:09', '2026-05-20 07:09:47', '2026-05-14 07:07:09'),
(227, 3, NULL, 'vukcema1@students.zhaw.ch', 47, '2026-05-14 07:07:18', '2026-05-20 09:31:36', '2026-05-14 07:07:18'),
(228, 6, NULL, 'stermmur@students.zhaw.ch', NULL, '2026-05-14 07:21:17', NULL, '2026-05-14 07:21:17'),
(229, 6, NULL, 'freilen1@students.zhaw.ch', NULL, '2026-05-14 07:31:20', '2026-05-28 05:07:47', '2026-05-14 07:31:20'),
(230, 6, NULL, 'garciign@students.zhaw.ch', NULL, '2026-05-14 07:59:54', '2026-05-28 05:07:47', '2026-05-14 07:59:54'),
(231, 6, NULL, 'schulpa5@students.zhaw.ch', NULL, '2026-05-14 08:00:31', '2026-05-28 05:07:47', '2026-05-14 08:00:31'),
(232, 6, NULL, 'kadoland@students.zhaw.ch', NULL, '2026-05-14 08:00:32', '2026-05-28 05:07:47', '2026-05-14 08:00:32'),
(233, 6, NULL, 'ferrepa1@students.zhaw.ch', NULL, '2026-05-14 08:27:49', '2026-05-28 05:07:47', '2026-05-14 08:27:49'),
(234, 6, NULL, 'fawaziss@students.zhaw.ch', NULL, '2026-05-14 08:33:46', '2026-05-28 05:07:47', '2026-05-14 08:33:46'),
(235, 6, NULL, 'helgrem1@students.zhaw.ch', NULL, '2026-05-14 09:35:02', '2026-05-28 05:07:47', '2026-05-14 09:35:02'),
(236, 6, NULL, 'bachmad3@students.zhaw.ch', NULL, '2026-05-14 13:28:16', '2026-05-28 05:07:47', '2026-05-14 13:28:16'),
(237, 6, NULL, 'ahilaayu@students.zhaw.ch', NULL, '2026-05-14 17:02:06', '2026-05-28 05:07:47', '2026-05-14 17:02:06'),
(238, 6, NULL, 'kermoala@students.zhaw.ch', NULL, '2026-05-14 17:22:46', '2026-05-28 11:26:54', '2026-05-14 17:22:46'),
(239, 6, NULL, 'kerndan1@students.zhaw.ch', NULL, '2026-05-15 13:01:46', '2026-05-28 05:07:47', '2026-05-15 13:01:46'),
(240, 6, NULL, 'vukcema1@students.zhaw.ch', NULL, '2026-05-15 14:16:03', NULL, '2026-05-15 14:16:03'),
(241, 6, NULL, 'laadi001@students.zhaw.ch', NULL, '2026-05-16 10:01:47', '2026-05-28 05:07:47', '2026-05-16 10:01:47'),
(242, 6, NULL, 'stumpmar@students.zhaw.ch', NULL, '2026-05-16 11:13:52', NULL, '2026-05-16 11:13:52'),
(243, 6, NULL, 'fischjad@students.zhaw.ch', NULL, '2026-05-16 15:38:32', '2026-05-28 05:07:47', '2026-05-16 15:38:32'),
(244, 6, NULL, 'rajarvee@students.zhaw.ch', NULL, '2026-05-16 20:20:56', '2026-05-28 05:07:47', '2026-05-16 20:20:56'),
(245, 6, NULL, 'arenilui@students.zhaw.ch', NULL, '2026-05-17 13:56:20', '2026-05-28 05:07:47', '2026-05-17 13:56:20'),
(246, 6, NULL, 'eymansaa@students.zhaw.ch', NULL, '2026-05-17 14:36:45', '2026-05-28 05:07:47', '2026-05-17 14:36:45'),
(247, 6, NULL, 'muslidri@students.zhaw.ch', NULL, '2026-05-17 15:07:23', '2026-05-28 05:07:47', '2026-05-17 15:07:23'),
(248, 5, NULL, 'rutzeeri@students.zhaw.ch', 84, '2026-05-18 06:37:19', '2026-05-20 07:09:59', '2026-05-18 06:37:19'),
(249, 5, NULL, 'ahmedkas@students.zhaw.ch', 99, '2026-05-18 06:49:23', '2026-05-20 07:10:12', '2026-05-18 06:49:23'),
(250, 6, NULL, 'ullmaart@students.zhaw.ch', NULL, '2026-05-18 07:24:19', '2026-05-28 05:07:47', '2026-05-18 07:24:19'),
(252, 6, NULL, 'kuhnlev2@students.zhaw.ch', NULL, '2026-05-18 15:19:46', '2026-05-28 05:07:47', '2026-05-18 15:19:46'),
(253, 6, NULL, 'feerdav1@students.zhaw.ch', NULL, '2026-05-18 17:11:30', '2026-05-28 05:07:47', '2026-05-18 17:11:30'),
(254, 6, NULL, 'farizeni@students.zhaw.ch', NULL, '2026-05-18 17:15:11', '2026-05-28 05:07:47', '2026-05-18 17:15:11'),
(256, 8, 12, 'salcelor@students.zhaw.ch', NULL, '2026-05-18 17:41:19', '2026-05-20 14:26:04', '2026-05-18 17:41:19'),
(257, 8, 13, 'bakisnus@students.zhaw.ch', NULL, '2026-05-18 17:41:29', '2026-05-20 14:26:04', '2026-05-18 17:41:29'),
(258, 8, 10, 'muslidri@students.zhaw.ch', NULL, '2026-05-18 17:42:44', '2026-05-18 18:56:45', '2026-05-18 17:42:44'),
(259, 8, 8, 'vukcema1@students.zhaw.ch', NULL, '2026-05-18 17:42:44', '2026-05-20 14:56:12', '2026-05-18 17:42:44'),
(260, 8, 13, 'okaemr01@students.zhaw.ch', NULL, '2026-05-18 17:42:48', '2026-05-18 18:56:16', '2026-05-18 17:42:48'),
(261, 8, 5, 'novaiand@students.zhaw.ch', NULL, '2026-05-18 17:42:50', '2026-05-18 19:04:23', '2026-05-18 17:42:50'),
(262, 8, 13, 'ferrepa1@students.zhaw.ch', NULL, '2026-05-18 17:42:50', '2026-05-20 14:26:04', '2026-05-18 17:42:50'),
(263, 8, 11, 'erikcley@students.zhaw.ch', NULL, '2026-05-18 17:42:51', '2026-05-18 19:01:10', '2026-05-18 17:42:51'),
(264, 8, 12, 'soutorub@students.zhaw.ch', NULL, '2026-05-18 17:42:51', '2026-05-18 18:51:54', '2026-05-18 17:42:51'),
(265, 8, 9, 'vadaclin@students.zhaw.ch', NULL, '2026-05-18 17:42:52', '2026-05-18 19:01:18', '2026-05-18 17:42:52'),
(266, 8, 8, 'kienemic@students.zhaw.ch', NULL, '2026-05-18 17:42:55', '2026-05-20 14:26:04', '2026-05-18 17:42:55'),
(267, 8, 11, 'haslejoe@students.zhaw.ch', NULL, '2026-05-18 17:42:58', '2026-05-18 18:53:39', '2026-05-18 17:42:58'),
(268, 8, 6, 'kermoala@students.zhaw.ch', NULL, '2026-05-18 17:43:00', '2026-05-18 19:03:08', '2026-05-18 17:43:00'),
(269, 8, 9, 'baersim1@students.zhaw.ch', NULL, '2026-05-18 17:43:03', '2026-05-18 18:59:00', '2026-05-18 17:43:03'),
(270, 8, 12, 'garciign@students.zhaw.ch', NULL, '2026-05-18 17:43:03', '2026-05-20 14:26:04', '2026-05-18 17:43:03'),
(271, 8, 8, 'stettbea@students.zhaw.ch', NULL, '2026-05-18 17:43:09', '2026-05-20 14:26:04', '2026-05-18 17:43:09'),
(272, 8, 7, 'schulpa5@students.zhaw.ch', NULL, '2026-05-18 17:43:12', '2026-05-20 14:26:04', '2026-05-18 17:43:12'),
(273, 8, 9, 'farizeni@students.zhaw.ch', NULL, '2026-05-18 17:43:16', '2026-05-20 14:26:04', '2026-05-18 17:43:16'),
(275, 8, 6, 'radovmar@students.zhaw.ch', NULL, '2026-05-18 17:43:18', '2026-05-20 14:26:04', '2026-05-18 17:43:18'),
(276, 8, 8, 'beparsad@students.zhaw.ch', NULL, '2026-05-18 17:43:18', '2026-05-20 14:26:04', '2026-05-18 17:43:18'),
(277, 8, 8, 'helgrem1@students.zhaw.ch', NULL, '2026-05-18 17:43:19', '2026-05-20 14:26:04', '2026-05-18 17:43:19'),
(278, 8, 5, 'ullmaart@students.zhaw.ch', NULL, '2026-05-18 17:43:20', '2026-05-20 14:26:04', '2026-05-18 17:43:20'),
(279, 8, 11, 'stroelau@students.zhaw.ch', NULL, '2026-05-18 17:43:20', '2026-05-18 18:55:58', '2026-05-18 17:43:20'),
(280, 8, 9, 'storztyl@students.zhaw.ch', NULL, '2026-05-18 17:43:20', '2026-05-18 19:02:55', '2026-05-18 17:43:20'),
(281, 8, 12, 'ordonheb@students.zhaw.ch', NULL, '2026-05-18 17:43:20', '2026-05-18 18:52:46', '2026-05-18 17:43:20'),
(282, 8, 6, 'dalipval@students.zhaw.ch', NULL, '2026-05-18 17:43:21', '2026-05-20 14:26:04', '2026-05-18 17:43:21'),
(283, 8, 11, 'simiciva@students.zhaw.ch', NULL, '2026-05-18 17:43:21', '2026-05-20 14:26:04', '2026-05-18 17:43:21'),
(284, 8, 13, 'bachmad3@students.zhaw.ch', NULL, '2026-05-18 17:43:21', '2026-05-20 14:27:13', '2026-05-18 17:43:21'),
(285, 8, 11, 'grueteri@students.zhaw.ch', NULL, '2026-05-18 17:43:22', '2026-05-18 18:54:44', '2026-05-18 17:43:22'),
(286, 8, 5, 'vogmar04@students.zhaw.ch', NULL, '2026-05-18 17:43:22', '2026-05-18 19:02:32', '2026-05-18 17:43:22'),
(287, 8, 6, 'stanisaw@students.zhaw.ch', NULL, '2026-05-18 17:43:23', '2026-05-20 14:26:04', '2026-05-18 17:43:23'),
(288, 8, 8, 'blassroc@students.zhaw.ch', NULL, '2026-05-18 17:43:24', '2026-05-18 18:54:54', '2026-05-18 17:43:24'),
(289, 8, 10, 'kuhnlev2@students.zhaw.ch', NULL, '2026-05-18 17:43:24', '2026-05-18 18:57:23', '2026-05-18 17:43:24'),
(290, 8, 6, 'walteemm@students.zhaw.ch', NULL, '2026-05-18 17:43:25', '2026-05-20 14:26:04', '2026-05-18 17:43:25'),
(291, 8, 11, 'senththe@students.zhaw.ch', NULL, '2026-05-18 17:43:26', '2026-05-20 14:26:54', '2026-05-18 17:43:26'),
(292, 8, 10, 'engeljar@students.zhaw.ch', NULL, '2026-05-18 17:43:26', '2026-05-18 19:04:55', '2026-05-18 17:43:26'),
(293, 8, 12, 'muralar1@students.zhaw.ch', NULL, '2026-05-18 17:43:30', '2026-05-20 14:26:04', '2026-05-18 17:43:30'),
(294, 8, 12, 'kenkasha@students.zhaw.ch', NULL, '2026-05-18 17:43:32', '2026-05-20 14:26:04', '2026-05-18 17:43:32'),
(295, 8, 7, 'stermmur@students.zhaw.ch', NULL, '2026-05-18 17:43:37', '2026-05-18 18:57:04', '2026-05-18 17:43:37'),
(296, 8, 7, 'piccalor@students.zhaw.ch', NULL, '2026-05-18 17:43:37', '2026-05-18 19:00:59', '2026-05-18 17:43:37'),
(297, 8, 8, 'fawaziss@students.zhaw.ch', NULL, '2026-05-18 17:43:38', '2026-05-18 18:57:40', '2026-05-18 17:43:38'),
(298, 8, 10, 'brogeluc@students.zhaw.ch', NULL, '2026-05-18 17:43:39', '2026-05-20 14:26:04', '2026-05-18 17:43:39'),
(299, 8, 5, 'thurnkev@students.zhaw.ch', NULL, '2026-05-18 17:43:46', '2026-05-20 14:26:04', '2026-05-18 17:43:46'),
(300, 8, 6, 'schjan06@students.zhaw.ch', NULL, '2026-05-18 17:43:46', '2026-05-20 14:26:04', '2026-05-18 17:43:46'),
(301, 8, 7, 'hengaric@students.zhaw.ch', NULL, '2026-05-18 17:43:47', '2026-05-20 14:27:57', '2026-05-18 17:43:47'),
(302, 8, 10, 'martim07@students.zhaw.ch', NULL, '2026-05-18 17:43:47', '2026-05-18 18:54:33', '2026-05-18 17:43:47'),
(303, 8, 7, 'kerndan1@students.zhaw.ch', NULL, '2026-05-18 17:43:48', '2026-05-18 18:59:14', '2026-05-18 17:43:48'),
(304, 8, 7, 'simicjas@students.zhaw.ch', NULL, '2026-05-18 17:43:49', '2026-05-18 19:04:39', '2026-05-18 17:43:49'),
(305, 8, 5, 'arenilui@students.zhaw.ch', NULL, '2026-05-18 17:43:49', '2026-05-18 18:59:33', '2026-05-18 17:43:49'),
(306, 8, 7, 'kadoland@students.zhaw.ch', NULL, '2026-05-18 17:43:53', '2026-05-20 14:26:04', '2026-05-18 17:43:53'),
(307, 8, 7, 'pejakkri@students.zhaw.ch', NULL, '2026-05-18 17:43:55', '2026-05-18 19:04:07', '2026-05-18 17:43:55'),
(308, 8, 11, 'laadi001@students.zhaw.ch', NULL, '2026-05-18 17:43:55', '2026-05-20 14:53:05', '2026-05-18 17:43:55'),
(309, 8, 10, 'werzben1@students.zhaw.ch', NULL, '2026-05-18 17:44:03', '2026-05-20 14:26:04', '2026-05-18 17:44:03'),
(310, 8, 13, 'altaymet@students.zhaw.ch', NULL, '2026-05-18 17:44:04', '2026-05-18 18:58:42', '2026-05-18 17:44:04'),
(311, 8, 5, 'ahmedkas@students.zhaw.ch', NULL, '2026-05-18 17:44:05', '2026-05-20 14:46:28', '2026-05-18 17:44:05'),
(312, 8, 6, 'zimmech5@students.zhaw.ch', NULL, '2026-05-18 17:44:05', '2026-05-20 14:26:04', '2026-05-18 17:44:05'),
(313, 8, 12, 'iljazmar@students.zhaw.ch', NULL, '2026-05-18 17:44:07', '2026-05-18 19:03:54', '2026-05-18 17:44:07'),
(314, 8, 9, 'rajarvee@students.zhaw.ch', NULL, '2026-05-18 17:44:11', '2026-05-18 19:02:14', '2026-05-18 17:44:11'),
(315, 8, 5, 'zenulske@students.zhaw.ch', NULL, '2026-05-18 17:44:12', '2026-05-20 14:26:04', '2026-05-18 17:44:12'),
(316, 8, 11, 'russoren@students.zhaw.ch', NULL, '2026-05-18 17:44:14', '2026-05-20 14:26:04', '2026-05-18 17:44:14'),
(317, 8, 11, 'haerrbas@students.zhaw.ch', NULL, '2026-05-18 17:44:15', '2026-05-20 14:26:04', '2026-05-18 17:44:15'),
(318, 8, 9, 'powroluk@students.zhaw.ch', NULL, '2026-05-18 17:44:15', '2026-05-20 14:26:04', '2026-05-18 17:44:15'),
(319, 8, 8, 'dumandil@students.zhaw.ch', NULL, '2026-05-18 17:44:15', '2026-05-18 19:02:05', '2026-05-18 17:44:15'),
(320, 8, 13, 'difrodar@students.zhaw.ch', NULL, '2026-05-18 17:44:16', '2026-05-20 14:26:04', '2026-05-18 17:44:16'),
(321, 8, 5, 'pupovalb@students.zhaw.ch', NULL, '2026-05-18 17:44:16', '2026-05-20 14:26:04', '2026-05-18 17:44:16'),
(322, 8, 7, 'feerdav1@students.zhaw.ch', NULL, '2026-05-18 17:44:17', '2026-05-18 19:03:43', '2026-05-18 17:44:17'),
(323, 8, 8, 'ramaale1@students.zhaw.ch', NULL, '2026-05-18 17:44:17', '2026-05-18 19:02:42', '2026-05-18 17:44:17'),
(324, 8, 10, 'bruneand@students.zhaw.ch', NULL, '2026-05-18 17:44:17', '2026-05-18 19:00:50', '2026-05-18 17:44:17'),
(325, 8, 7, 'reihwlia@students.zhaw.ch', NULL, '2026-05-18 17:44:18', '2026-05-20 14:26:04', '2026-05-18 17:44:18'),
(326, 8, 7, 'rutzeeri@students.zhaw.ch', NULL, '2026-05-18 17:44:18', '2026-05-18 18:53:30', '2026-05-18 17:44:18'),
(327, 8, 13, 'imeridon@students.zhaw.ch', NULL, '2026-05-18 17:44:18', '2026-05-20 14:26:04', '2026-05-18 17:44:18'),
(328, 8, 12, 'lingor01@students.zhaw.ch', NULL, '2026-05-18 17:44:19', '2026-05-18 19:00:33', '2026-05-18 17:44:19'),
(329, 8, 10, 'stumpmar@students.zhaw.ch', NULL, '2026-05-18 17:44:19', '2026-05-20 14:26:04', '2026-05-18 17:44:19'),
(330, 8, 9, 'rexheeri@students.zhaw.ch', NULL, '2026-05-18 17:44:22', '2026-05-20 14:26:04', '2026-05-18 17:44:22'),
(331, 8, 10, 'pachuval@students.zhaw.ch', NULL, '2026-05-18 17:44:24', '2026-05-20 14:26:04', '2026-05-18 17:44:24'),
(332, 8, 11, 'schmusan@students.zhaw.ch', NULL, '2026-05-18 17:44:25', '2026-05-20 14:26:04', '2026-05-18 17:44:25'),
(333, 8, 6, 'eymansaa@students.zhaw.ch', NULL, '2026-05-18 17:44:29', '2026-05-20 14:26:04', '2026-05-18 17:44:29'),
(334, 8, 10, 'altinker@students.zhaw.ch', NULL, '2026-05-18 17:44:30', '2026-05-20 14:26:04', '2026-05-18 17:44:30'),
(335, 8, 6, 'moergnoe@students.zhaw.ch', NULL, '2026-05-18 17:44:32', '2026-05-20 14:26:04', '2026-05-18 17:44:32'),
(336, 8, 13, 'ahilaayu@students.zhaw.ch', NULL, '2026-05-18 17:44:33', '2026-05-18 18:57:13', '2026-05-18 17:44:33'),
(337, 8, 6, 'perkokri@students.zhaw.ch', NULL, '2026-05-18 17:44:40', '2026-05-18 18:59:45', '2026-05-18 17:44:40'),
(338, 8, 9, 'willnkan@students.zhaw.ch', NULL, '2026-05-18 17:44:41', '2026-05-20 14:26:04', '2026-05-18 17:44:41'),
(339, 8, 5, 'singhsub@students.zhaw.ch', NULL, '2026-05-18 17:44:44', '2026-05-20 14:26:04', '2026-05-18 17:44:44'),
(340, 8, 7, 'elcali01@students.zhaw.ch', NULL, '2026-05-18 17:44:48', '2026-05-18 19:00:12', '2026-05-18 17:44:48'),
(341, 8, 9, 'dietisve@students.zhaw.ch', NULL, '2026-05-18 17:44:48', '2026-05-18 19:00:24', '2026-05-18 17:44:48'),
(342, 8, 13, 'alijasan@students.zhaw.ch', NULL, '2026-05-18 17:44:57', '2026-05-18 18:58:52', '2026-05-18 17:44:57'),
(343, 8, 8, 'bereri01@students.zhaw.ch', NULL, '2026-05-18 17:45:09', '2026-05-20 14:26:04', '2026-05-18 17:45:09'),
(344, 8, 10, 'freilen1@students.zhaw.ch', NULL, '2026-05-18 17:45:13', '2026-05-18 18:58:23', '2026-05-18 17:45:13'),
(345, 8, 10, 'debelno1@students.zhaw.ch', NULL, '2026-05-18 17:45:18', '2026-05-20 14:26:04', '2026-05-18 17:45:18'),
(346, 8, 6, 'fischjad@students.zhaw.ch', NULL, '2026-05-18 17:45:20', '2026-05-20 14:26:04', '2026-05-18 17:45:20'),
(347, 8, 9, 'rueegsi7@students.zhaw.ch', NULL, '2026-05-18 17:45:48', '2026-05-18 19:05:08', '2026-05-18 17:45:48'),
(348, 8, 12, 'sharis01@students.zhaw.ch', NULL, '2026-05-18 17:45:54', '2026-05-20 14:26:04', '2026-05-18 17:45:54'),
(349, 8, 13, 'duriqdon@students.zhaw.ch', NULL, '2026-05-18 17:46:12', '2026-05-18 18:58:15', '2026-05-18 17:46:12'),
(350, 8, 8, 'steinjo6@students.zhaw.ch', NULL, '2026-05-18 17:49:36', '2026-05-20 14:26:04', '2026-05-18 17:49:36'),
(351, 8, 12, 'cabdiabd@students.zhaw.ch', NULL, '2026-05-18 17:49:56', '2026-05-20 14:26:04', '2026-05-18 17:49:56'),
(352, 6, NULL, 'bakisnus@students.zhaw.ch', NULL, '2026-05-18 20:29:42', '2026-05-28 05:07:47', '2026-05-18 20:29:42'),
(353, 8, 13, 'desa@students.zhaw.ch', NULL, '2026-05-19 09:15:16', '2026-05-20 13:00:01', '2026-05-19 09:15:16'),
(354, 6, NULL, 'desa@students.zhaw.ch', NULL, '2026-05-19 09:17:38', NULL, '2026-05-19 09:17:38'),
(355, 6, NULL, 'baersim1@students.zhaw.ch', NULL, '2026-05-19 21:57:53', '2026-05-28 05:07:47', '2026-05-19 21:57:53'),
(356, 6, NULL, 'cabdiabd@students.zhaw.ch', NULL, '2026-05-19 22:59:19', '2026-05-28 05:07:47', '2026-05-19 22:59:19'),
(357, 6, NULL, 'imeridon@students.zhaw.ch', NULL, '2026-05-19 23:02:41', '2026-05-28 05:07:47', '2026-05-19 23:02:41'),
(358, 6, NULL, 'salcelor@students.zhaw.ch', NULL, '2026-05-20 08:08:00', '2026-05-28 05:07:47', '2026-05-20 08:08:00'),
(360, 7, NULL, 'simicjas@students.zhaw.ch', 357, '2026-05-20 09:48:23', '2026-05-26 08:27:24', '2026-05-20 09:48:23'),
(361, 7, NULL, 'salcelor@students.zhaw.ch', 355, '2026-05-20 09:50:35', '2026-05-26 08:27:24', '2026-05-20 09:50:35'),
(362, 6, NULL, 'perkokri@students.zhaw.ch', NULL, '2026-05-20 10:02:07', '2026-05-28 05:07:47', '2026-05-20 10:02:07'),
(363, 6, NULL, 'stanisaw@students.zhaw.ch', NULL, '2026-05-20 10:27:58', '2026-05-28 05:07:47', '2026-05-20 10:27:58'),
(364, 7, NULL, 'senththe@students.zhaw.ch', 349, '2026-05-20 10:29:28', '2026-05-26 08:27:24', '2026-05-20 10:29:28'),
(365, 7, NULL, 'vadaclin@students.zhaw.ch', 369, '2026-05-20 10:29:35', '2026-05-26 08:27:24', '2026-05-20 10:29:35'),
(366, 7, NULL, 'rahmaann@students.zhaw.ch', 360, '2026-05-20 10:39:27', '2026-05-26 08:27:24', '2026-05-20 10:39:27'),
(367, 7, NULL, 'stettbea@students.zhaw.ch', 367, '2026-05-20 11:22:08', '2026-05-26 08:27:24', '2026-05-20 11:22:08'),
(368, 6, NULL, 'martim07@students.zhaw.ch', NULL, '2026-05-20 11:52:34', '2026-05-28 05:07:47', '2026-05-20 11:52:34'),
(369, 7, NULL, 'singhsub@students.zhaw.ch', 350, '2026-05-20 11:54:34', '2026-05-26 08:27:24', '2026-05-20 11:54:34'),
(370, 7, NULL, 'powroluk@students.zhaw.ch', 351, '2026-05-20 11:59:40', '2026-05-26 08:27:24', '2026-05-20 11:59:40'),
(371, 6, NULL, 'lingor01@students.zhaw.ch', NULL, '2026-05-20 12:48:12', '2026-05-28 05:07:47', '2026-05-20 12:48:12'),
(372, 6, NULL, 'zimmech5@students.zhaw.ch', NULL, '2026-05-20 14:15:11', '2026-05-28 05:07:47', '2026-05-20 14:15:11'),
(373, 7, NULL, 'ullmaart@students.zhaw.ch', 352, '2026-05-20 15:04:07', '2026-05-26 08:27:24', '2026-05-20 15:04:07'),
(374, 7, NULL, 'walteemm@students.zhaw.ch', 359, '2026-05-20 16:16:19', '2026-05-26 08:27:24', '2026-05-20 16:16:19'),
(375, 6, NULL, 'rexheeri@students.zhaw.ch', NULL, '2026-05-20 16:57:54', '2026-05-28 05:07:47', '2026-05-20 16:57:54'),
(376, 6, NULL, 'hengaric@students.zhaw.ch', NULL, '2026-05-20 17:37:40', '2026-05-28 05:07:47', '2026-05-20 17:37:40'),
(377, 7, NULL, 'russoren@students.zhaw.ch', 347, '2026-05-20 17:38:00', '2026-05-26 08:27:24', '2026-05-20 17:38:00'),
(378, 6, NULL, 'duriqdon@students.zhaw.ch', NULL, '2026-05-20 21:06:20', '2026-05-28 05:07:47', '2026-05-20 21:06:20'),
(379, 6, NULL, 'altinker@students.zhaw.ch', NULL, '2026-05-20 21:30:55', '2026-05-28 05:07:47', '2026-05-20 21:30:55'),
(380, 6, NULL, 'ahmedkas@students.zhaw.ch', NULL, '2026-05-21 06:32:12', '2026-05-28 05:07:47', '2026-05-21 06:32:12'),
(381, 7, NULL, 'willnkan@students.zhaw.ch', 362, '2026-05-21 10:11:06', '2026-05-26 08:27:24', '2026-05-21 10:11:06'),
(382, 6, NULL, 'schjan06@students.zhaw.ch', NULL, '2026-05-21 12:49:10', NULL, '2026-05-21 12:49:10'),
(383, 7, NULL, 'schjan06@students.zhaw.ch', 370, '2026-05-21 12:57:01', '2026-05-26 08:27:24', '2026-05-21 12:57:01'),
(384, 7, NULL, 'stumpmar@students.zhaw.ch', 353, '2026-05-21 15:47:08', '2026-05-26 08:27:24', '2026-05-21 15:47:08'),
(385, 7, NULL, 'rajarvee@students.zhaw.ch', 358, '2026-05-21 16:09:13', '2026-05-26 08:27:24', '2026-05-21 16:09:13'),
(386, 6, NULL, 'moergnoe@students.zhaw.ch', NULL, '2026-05-21 17:13:25', '2026-05-28 05:07:47', '2026-05-21 17:13:25'),
(387, 7, NULL, 'thurnkev@students.zhaw.ch', 348, '2026-05-21 18:19:56', '2026-05-26 08:27:24', '2026-05-21 18:19:56'),
(388, 6, NULL, 'steinjo6@students.zhaw.ch', NULL, '2026-05-21 18:37:02', '2026-05-28 05:07:47', '2026-05-21 18:37:02'),
(389, 6, NULL, 'ordonheb@students.zhaw.ch', NULL, '2026-05-21 22:17:55', NULL, '2026-05-21 22:17:55'),
(390, 6, NULL, 'rutzeeri@students.zhaw.ch', NULL, '2026-05-22 04:03:47', '2026-05-28 05:07:47', '2026-05-22 04:03:47'),
(391, 6, NULL, 'elcali01@students.zhaw.ch', NULL, '2026-05-22 07:45:45', '2026-05-28 05:07:47', '2026-05-22 07:45:45'),
(392, 7, NULL, 'wallrtim@students.zhaw.ch', 368, '2026-05-22 16:20:44', '2026-05-26 08:27:24', '2026-05-22 16:20:44'),
(393, 7, NULL, 'storztyl@students.zhaw.ch', 364, '2026-05-22 20:37:29', '2026-05-26 08:27:24', '2026-05-22 20:37:29'),
(394, 7, NULL, 'vignamaa@students.zhaw.ch', 346, '2026-05-23 08:22:01', '2026-05-26 08:27:24', '2026-05-23 08:22:01'),
(395, 7, NULL, 'ramaale1@students.zhaw.ch', 366, '2026-05-23 11:07:46', '2026-05-26 08:27:24', '2026-05-23 11:07:46'),
(396, 7, NULL, 'pupovalb@students.zhaw.ch', 363, '2026-05-23 21:20:09', '2026-05-26 08:27:24', '2026-05-23 21:20:09'),
(397, 7, NULL, 'sharis01@students.zhaw.ch', 361, '2026-05-24 07:23:51', '2026-05-26 08:27:24', '2026-05-24 07:23:51'),
(398, 7, NULL, 'radovmar@students.zhaw.ch', 356, '2026-05-24 09:57:54', '2026-05-26 08:27:24', '2026-05-24 09:57:54'),
(399, 7, NULL, 'steinjo6@students.zhaw.ch', 365, '2026-05-24 11:20:19', '2026-05-26 08:27:24', '2026-05-24 11:20:19'),
(400, 7, NULL, 'schmusan@students.zhaw.ch', 354, '2026-05-24 15:54:42', '2026-05-26 08:27:24', '2026-05-24 15:54:42'),
(401, 6, NULL, 'wallrtim@students.zhaw.ch', NULL, '2026-05-25 10:29:40', NULL, '2026-05-25 10:29:40'),
(402, 6, NULL, 'ramaale1@students.zhaw.ch', NULL, '2026-05-26 10:55:49', '2026-05-28 05:07:47', '2026-05-26 10:55:49');

-- --------------------------------------------------------

--
-- Table structure for table `participation_field_values`
--

CREATE TABLE `participation_field_values` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `participation_id` bigint(20) UNSIGNED NOT NULL,
  `field_id` bigint(20) UNSIGNED NOT NULL,
  `field_value` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `participation_field_values`
--

INSERT INTO `participation_field_values` (`id`, `participation_id`, `field_id`, `field_value`, `updated_at`) VALUES
(1, 1, 1, '96', '2026-05-12 11:48:42'),
(2, 1, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_yNAfwZG4m6XG6Gi&_g_=g', '2026-05-12 11:48:42'),
(3, 1, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=dac07b16-026d-492f-a007-6486dcf3d516', '2026-05-12 11:48:42'),
(4, 2, 1, '140', '2026-05-12 11:49:55'),
(5, 2, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_e2BpWjcQgdPT410&_g_=g', '2026-05-12 11:49:55'),
(6, 2, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=092c7e0a-3aa6-4be9-a232-7c38a695ddd9', '2026-05-12 11:49:55'),
(7, 3, 1, '88', '2026-05-12 11:50:19'),
(8, 3, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_LzE2z5mbmdspHQD&_g_=g', '2026-05-12 11:50:19'),
(9, 3, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=a10cb165-cb39-415d-94ac-7d83040919a9', '2026-05-12 11:50:19'),
(10, 4, 1, '110', '2026-05-12 11:51:12'),
(11, 4, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_OdVwG0IAnEPfi7o&_g_=g', '2026-05-12 11:51:12'),
(12, 4, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=1fd93d56-2be1-4eae-a392-4abd2d71c6ca', '2026-05-12 11:51:12'),
(13, 5, 1, '107', '2026-05-12 11:51:25'),
(14, 5, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_SUG1F27qxLUADI1&_g_=g', '2026-05-12 11:51:25'),
(15, 5, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=08569374-7f74-4aa6-916b-2ae90d682989', '2026-05-12 11:51:25'),
(16, 6, 1, '115', '2026-05-12 11:51:34'),
(17, 6, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_riKucJc0pQVj4gz&_g_=g', '2026-05-12 11:51:34'),
(18, 6, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=966e73fb-ac78-4dd7-9da0-c008fcdb681c', '2026-05-12 11:51:34'),
(19, 7, 1, '89', '2026-05-12 13:20:54'),
(20, 7, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_9exeGf3bRfaZTRb&_g_=g', '2026-05-12 13:20:54'),
(21, 7, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=9bf5a516-73ca-4294-a0dd-260d1f201ace', '2026-05-12 13:20:54'),
(22, 8, 1, '131', '2026-05-12 13:28:02'),
(23, 8, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_uTCmtr3JtZDgujX&_g_=g', '2026-05-12 13:28:02'),
(24, 8, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=bb3c8970-f165-4e07-9882-667db1ea8a5e', '2026-05-12 13:28:02'),
(25, 9, 1, '90', '2026-05-12 13:28:41'),
(26, 9, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_axT9Fd8TpdrEVu8&_g_=g', '2026-05-12 13:28:41'),
(27, 9, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=83dfe60b-2659-4876-ad21-e0af378f1b5f', '2026-05-12 13:28:41'),
(28, 10, 1, '87', '2026-05-12 13:29:04'),
(29, 10, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_vXznhKZuQfkdt3M&_g_=g', '2026-05-12 13:29:04'),
(30, 10, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=176a7905-9c3b-40f2-9608-83082d3c1f32', '2026-05-12 13:29:04'),
(31, 11, 1, '98', '2026-05-12 13:29:19'),
(32, 11, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_cxR1ZqsWXqAxkg6&_g_=g', '2026-05-12 13:29:19'),
(33, 11, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=4ec48220-f537-4cef-b0cc-4481f8267bf1', '2026-05-12 13:29:19'),
(34, 12, 1, '92', '2026-05-12 13:29:26'),
(35, 12, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_iqD0UJzVSG5i1nM&_g_=g', '2026-05-12 13:29:26'),
(36, 12, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=d0caa13d-1e85-4b59-81b5-41a8e0430419', '2026-05-12 13:29:26'),
(37, 13, 1, '111', '2026-05-12 13:29:44'),
(38, 13, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_gHVwKTAPy0xh0OC&_g_=g', '2026-05-12 13:29:44'),
(39, 13, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=1d3670d8-45f2-4f99-b60a-7d6325a0ebca', '2026-05-12 13:29:44'),
(40, 14, 1, '113', '2026-05-12 13:29:59'),
(41, 14, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_lRRzGCOBfA8eDYk&_g_=g', '2026-05-12 13:29:59'),
(42, 14, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=1320c35e-ac32-42ab-805a-40be9458b781', '2026-05-12 13:29:59'),
(43, 15, 1, '91', '2026-05-12 13:30:15'),
(44, 15, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_0qzQNXgcNTx1VBc&_g_=g', '2026-05-12 13:30:15'),
(45, 15, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=be36eb7f-d78c-4c39-869f-53fd7e42dcde', '2026-05-12 13:30:15'),
(46, 16, 1, '126', '2026-05-12 13:30:28'),
(47, 16, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_pVDoIbmV4vn2ZgC&_g_=g', '2026-05-12 13:30:28'),
(48, 16, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=51153fc1-29bd-4044-8e98-479f4e4c0961', '2026-05-12 13:30:28'),
(49, 17, 1, '136', '2026-05-12 13:30:41'),
(50, 17, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_dtnvn7TMUfPLoRg&_g_=g', '2026-05-12 13:30:41'),
(51, 17, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=05f0e922-6bba-4847-9d36-4e8926081e72', '2026-05-12 13:30:41'),
(52, 18, 1, '108', '2026-05-12 13:30:57'),
(53, 18, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_wzDVhpaKG0VWVHH&_g_=g', '2026-05-12 13:30:57'),
(54, 18, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=d0655ce5-94e9-454f-a689-59dc0f0d03a6', '2026-05-12 13:30:57'),
(55, 19, 1, '134', '2026-05-12 13:31:11'),
(56, 19, 2, 'https://immzhaw.eu.qualtrics.com/jfe/form/SV_86ArQChg4w4SGi2?Q_CHL=gl&Q_DL=EMD_55jomdRZXECRRqu_86ArQChg4w4SGi2_CGC_xp4GQe8lalyE6gw&_g_=g', '2026-05-12 13:31:11'),
(57, 19, 3, 'https://aydemmel-306ec007f2e4.herokuapp.com/?agentId=cc76ccac-a884-41d6-a825-9b0e5ce3519d', '2026-05-12 13:31:11'),
(58, 208, 30, '3159', '2026-05-26 13:55:21'),
(59, 211, 30, '3204', '2026-05-26 13:55:21'),
(60, 250, 30, '3302', '2026-05-26 13:55:21'),
(61, 111, 30, '3336', '2026-05-26 13:55:21'),
(62, 375, 30, '3371', '2026-05-26 13:55:21'),
(63, 371, 30, '3526', '2026-05-26 13:55:21'),
(64, 147, 30, '3666', '2026-05-26 13:55:21'),
(65, 130, 30, '3774', '2026-05-26 13:55:21'),
(66, 200, 30, '3828', '2026-05-26 13:55:21'),
(67, 245, 30, '4000', '2026-05-26 13:55:21'),
(68, 190, 30, '4329', '2026-05-26 13:55:21'),
(69, 202, 30, '4378', '2026-05-26 13:55:21'),
(70, 235, 30, '4487', '2026-05-26 13:55:21'),
(71, 167, 30, '4489', '2026-05-26 13:55:21'),
(72, 218, 30, '4679', '2026-05-26 13:55:21'),
(73, 231, 30, '4692', '2026-05-26 13:55:21'),
(74, 355, 30, '4746', '2026-05-26 13:55:21'),
(75, 201, 30, '4854', '2026-05-26 13:55:21'),
(76, 239, 30, '4866', '2026-05-26 13:55:21'),
(77, 215, 30, '4934', '2026-05-26 13:55:21'),
(78, 376, 30, '5069', '2026-05-26 13:55:21'),
(79, 131, 30, '5155', '2026-05-26 13:55:21'),
(80, 254, 30, '5239', '2026-05-26 13:55:21'),
(81, 368, 30, '5327', '2026-05-26 13:55:21'),
(82, 159, 30, '5380', '2026-05-26 13:55:21'),
(83, 169, 30, '5444', '2026-05-26 13:55:21'),
(84, 178, 30, '5480', '2026-05-26 13:55:21'),
(85, 142, 30, '5499', '2026-05-26 13:55:21'),
(86, 386, 30, '5939', '2026-05-26 13:55:21'),
(87, 222, 30, '6004', '2026-05-26 13:55:21'),
(88, 189, 30, '6163', '2026-05-26 13:55:21'),
(89, 357, 30, '6193', '2026-05-26 13:55:21'),
(90, 150, 30, '6433', '2026-05-26 13:55:21'),
(91, 198, 30, '6448', '2026-05-26 13:55:21'),
(92, 232, 30, '6477', '2026-05-26 13:55:21'),
(93, 194, 30, '6609', '2026-05-26 13:55:21'),
(94, 210, 30, '6624', '2026-05-26 13:55:21'),
(95, 156, 30, '6721', '2026-05-26 13:55:21'),
(96, 197, 30, '6728', '2026-05-26 13:55:21'),
(97, 372, 30, '6852', '2026-05-26 13:55:21'),
(98, 379, 30, '6921', '2026-05-26 13:55:21'),
(99, 128, 30, '7027', '2026-05-26 13:55:21'),
(100, 380, 30, '7053', '2026-05-26 13:55:21'),
(101, 390, 30, '7113', '2026-05-26 13:55:21'),
(102, 233, 30, '7114', '2026-05-26 13:55:21'),
(103, 160, 30, '7189', '2026-05-26 13:55:21'),
(104, 229, 30, '7254', '2026-05-26 13:55:21'),
(105, 362, 30, '7265', '2026-05-26 13:55:21'),
(106, 241, 30, '7418', '2026-05-26 13:55:21'),
(107, 253, 30, '7487', '2026-05-26 13:55:21'),
(108, 187, 30, '7502', '2026-05-26 13:55:21'),
(109, 149, 30, '7577', '2026-05-26 13:55:21'),
(110, 225, 30, '7727', '2026-05-26 13:55:21'),
(111, 388, 30, '7786', '2026-05-26 13:55:21'),
(112, 230, 30, '7900', '2026-05-26 13:55:21'),
(113, 358, 30, '7988', '2026-05-26 13:55:21'),
(114, 188, 30, '8019', '2026-05-26 13:55:21'),
(115, 170, 30, '8093', '2026-05-26 13:55:21'),
(116, 220, 30, '8146', '2026-05-26 13:55:21'),
(117, 193, 30, '8165', '2026-05-26 13:55:21'),
(118, 243, 30, '8170', '2026-05-26 13:55:21'),
(119, 402, 30, '8228', '2026-05-26 20:23:02'),
(120, 195, 30, '8255', '2026-05-26 13:55:21'),
(121, 168, 30, '8334', '2026-05-26 13:55:21'),
(122, 161, 30, '8478', '2026-05-26 13:55:21'),
(123, 217, 30, '8536', '2026-05-26 13:55:21'),
(124, 223, 30, '8556', '2026-05-26 13:55:21'),
(125, 196, 30, '8579', '2026-05-26 13:55:21'),
(126, 352, 30, '8688', '2026-05-26 13:55:21'),
(127, 356, 30, '8705', '2026-05-26 13:55:21'),
(128, 378, 30, '8796', '2026-05-26 13:55:21'),
(129, 203, 30, '8802', '2026-05-26 13:55:21'),
(130, 237, 30, '8811', '2026-05-26 13:55:21'),
(131, 238, 30, '8854', '2026-05-26 13:55:21'),
(132, 199, 30, '8877', '2026-05-26 13:55:21'),
(133, 391, 30, '8897', '2026-05-26 13:55:21'),
(134, 252, 30, '8920', '2026-05-26 13:55:21'),
(135, 236, 30, '8989', '2026-05-26 13:55:21'),
(136, 246, 30, '9031', '2026-05-26 13:55:21'),
(137, 205, 30, '9079', '2026-05-26 13:55:21'),
(138, 363, 30, '9107', '2026-05-26 13:55:21'),
(139, 247, 30, '9201', '2026-05-26 13:55:21'),
(140, 134, 30, '9259', '2026-05-26 13:55:21'),
(141, 163, 30, '9295', '2026-05-26 13:55:21'),
(142, 244, 30, '9315', '2026-05-26 13:55:21'),
(143, 234, 30, '9349', '2026-05-26 13:55:21'),
(144, 212, 30, '9373', '2026-05-26 13:55:21'),
(145, 213, 30, '9541', '2026-05-26 13:55:21'),
(185, 237, 31, '21', '2026-05-26 13:55:21'),
(186, 380, 31, '16', '2026-05-26 13:55:21'),
(187, 195, 31, '3', '2026-05-26 13:55:21'),
(188, 215, 31, '19', '2026-05-26 13:55:21'),
(189, 379, 31, '20', '2026-05-26 13:55:21'),
(190, 245, 31, '15', '2026-05-26 13:55:21'),
(191, 236, 31, '18', '2026-05-26 13:55:21'),
(192, 355, 31, '21', '2026-05-26 13:55:21'),
(193, 352, 31, '14', '2026-05-26 13:55:21'),
(194, 190, 31, '11', '2026-05-26 13:55:21'),
(195, 197, 31, '4', '2026-05-26 13:55:21'),
(196, 213, 31, '22', '2026-05-26 13:55:21'),
(197, 199, 31, '6', '2026-05-26 13:55:21'),
(198, 203, 31, '21', '2026-05-26 13:55:21'),
(199, 356, 31, '16', '2026-05-26 13:55:21'),
(200, 200, 31, '15', '2026-05-26 13:55:21'),
(201, 201, 31, '10', '2026-05-26 13:55:21'),
(202, 178, 31, '5', '2026-05-26 13:55:21'),
(203, 378, 31, '18', '2026-05-26 13:55:21'),
(204, 391, 31, '17', '2026-05-26 13:55:21'),
(205, 147, 31, '15', '2026-05-26 13:55:21'),
(206, 210, 31, '12', '2026-05-26 13:55:21'),
(207, 402, 31, '2', '2026-05-26 20:23:02'),
(208, 246, 31, '17', '2026-05-26 13:55:21'),
(209, 254, 31, '16', '2026-05-26 13:55:21'),
(210, 234, 31, '22', '2026-05-26 13:55:21'),
(211, 253, 31, '14', '2026-05-26 13:55:21'),
(212, 233, 31, '13', '2026-05-26 13:55:21'),
(213, 243, 31, '21', '2026-05-26 13:55:21'),
(214, 229, 31, '10', '2026-05-26 13:55:21'),
(215, 230, 31, '9', '2026-05-26 13:55:21'),
(216, 188, 31, '19', '2026-05-26 13:55:21'),
(217, 169, 31, '6', '2026-05-26 13:55:21'),
(218, 202, 31, '4', '2026-05-26 13:55:21'),
(219, 235, 31, '3', '2026-05-26 13:55:21'),
(220, 376, 31, '17', '2026-05-26 13:55:21'),
(221, 193, 31, '8', '2026-05-26 13:55:21'),
(222, 357, 31, '16', '2026-05-26 13:55:21'),
(223, 232, 31, '12', '2026-05-26 13:55:21'),
(224, 161, 31, '8', '2026-05-26 13:55:21'),
(225, 238, 31, '1', '2026-05-26 13:55:21'),
(226, 239, 31, '7', '2026-05-26 13:55:21'),
(227, 134, 31, '3', '2026-05-26 13:55:21'),
(228, 252, 31, '18', '2026-05-26 13:55:21'),
(229, 241, 31, '12', '2026-05-26 13:55:21'),
(230, 371, 31, '20', '2026-05-26 13:55:21'),
(231, 368, 31, '18', '2026-05-26 13:55:21'),
(232, 386, 31, '9', '2026-05-26 13:55:21'),
(233, 212, 31, '10', '2026-05-26 13:55:21'),
(234, 247, 31, '19', '2026-05-26 13:55:21'),
(235, 196, 31, '4', '2026-05-26 13:55:21'),
(236, 211, 31, '22', '2026-05-26 13:55:21'),
(237, 156, 31, '9', '2026-05-26 13:55:21'),
(238, 189, 31, '12', '2026-05-26 13:55:21'),
(239, 362, 31, '17', '2026-05-26 13:55:21'),
(240, 160, 31, '11', '2026-05-26 13:55:21'),
(241, 223, 31, '5', '2026-05-26 13:55:21'),
(242, 111, 31, '7', '2026-05-26 13:55:21'),
(243, 128, 31, '1', '2026-05-26 13:55:21'),
(244, 142, 31, '1', '2026-05-26 13:55:21'),
(245, 244, 31, '14', '2026-05-26 13:55:21'),
(246, 208, 31, '2', '2026-05-26 13:55:21'),
(247, 375, 31, '20', '2026-05-26 13:55:21'),
(248, 220, 31, '10', '2026-05-26 13:55:21'),
(249, 390, 31, '14', '2026-05-26 13:55:21'),
(250, 358, 31, '20', '2026-05-26 13:55:21'),
(251, 170, 31, '5', '2026-05-26 13:55:21'),
(252, 231, 31, '8', '2026-05-26 13:55:21'),
(253, 159, 31, '19', '2026-05-26 13:55:21'),
(254, 163, 31, '11', '2026-05-26 13:55:21'),
(255, 131, 31, '9', '2026-05-26 13:55:21'),
(256, 130, 31, '6', '2026-05-26 13:55:21'),
(257, 198, 31, '7', '2026-05-26 13:55:21'),
(258, 168, 31, '13', '2026-05-26 13:55:21'),
(259, 222, 31, '8', '2026-05-26 13:55:21'),
(260, 363, 31, '15', '2026-05-26 13:55:21'),
(261, 388, 31, '13', '2026-05-26 13:55:21'),
(262, 218, 31, '7', '2026-05-26 13:55:21'),
(263, 217, 31, '1', '2026-05-26 13:55:21'),
(264, 187, 31, '4', '2026-05-26 13:55:21'),
(265, 150, 31, '6', '2026-05-26 13:55:21'),
(266, 250, 31, '13', '2026-05-26 13:55:21'),
(267, 149, 31, '11', '2026-05-26 13:55:21'),
(268, 205, 31, '2', '2026-05-26 13:55:21'),
(269, 194, 31, '3', '2026-05-26 13:55:21'),
(270, 225, 31, '5', '2026-05-26 13:55:21'),
(271, 167, 31, '2', '2026-05-26 13:55:21'),
(272, 372, 31, '22', '2026-05-26 13:55:21'),
(312, 237, 32, 'B', '2026-05-26 13:55:21'),
(313, 380, 32, 'D', '2026-05-26 13:55:21'),
(314, 195, 32, 'B', '2026-05-26 13:55:21'),
(315, 215, 32, 'C', '2026-05-26 13:55:21'),
(316, 379, 32, 'B', '2026-05-26 13:55:21'),
(317, 245, 32, 'D', '2026-05-26 13:55:21'),
(318, 236, 32, 'D', '2026-05-26 13:55:21'),
(319, 355, 32, 'D', '2026-05-26 13:55:21'),
(320, 352, 32, 'B', '2026-05-26 13:55:21'),
(321, 190, 32, 'D', '2026-05-26 13:55:21'),
(322, 197, 32, 'B', '2026-05-26 13:55:21'),
(323, 213, 32, 'B', '2026-05-26 13:55:21'),
(324, 199, 32, 'D', '2026-05-26 13:55:21'),
(325, 203, 32, 'C', '2026-05-26 13:55:21'),
(326, 356, 32, 'C', '2026-05-26 13:55:21'),
(327, 200, 32, 'C', '2026-05-26 13:55:21'),
(328, 201, 32, 'D', '2026-05-26 13:55:21'),
(329, 178, 32, 'D', '2026-05-26 13:55:21'),
(330, 378, 32, 'C', '2026-05-26 13:55:21'),
(331, 391, 32, 'A', '2026-05-26 13:55:21'),
(332, 147, 32, 'A', '2026-05-26 13:55:21'),
(333, 210, 32, 'D', '2026-05-26 13:55:21'),
(334, 402, 32, 'A', '2026-05-26 20:23:02'),
(335, 246, 32, 'D', '2026-05-26 13:55:21'),
(336, 254, 32, 'B', '2026-05-26 13:55:21'),
(337, 234, 32, 'A', '2026-05-26 13:55:21'),
(338, 253, 32, 'A', '2026-05-26 13:55:21'),
(339, 233, 32, 'D', '2026-05-26 13:55:21'),
(340, 243, 32, 'A', '2026-05-26 13:55:21'),
(341, 229, 32, 'B', '2026-05-26 13:55:21'),
(342, 230, 32, 'D', '2026-05-26 13:55:21'),
(343, 188, 32, 'B', '2026-05-26 13:55:21'),
(344, 169, 32, 'C', '2026-05-26 13:55:21'),
(345, 202, 32, 'A', '2026-05-26 13:55:21'),
(346, 235, 32, 'C', '2026-05-26 13:55:21'),
(347, 376, 32, 'C', '2026-05-26 13:55:21'),
(348, 193, 32, 'D', '2026-05-26 13:55:21'),
(349, 357, 32, 'A', '2026-05-26 13:55:21'),
(350, 232, 32, 'A', '2026-05-26 13:55:21'),
(351, 161, 32, 'C', '2026-05-26 13:55:21'),
(352, 238, 32, 'B', '2026-05-26 13:55:21'),
(353, 239, 32, 'A', '2026-05-26 13:55:21'),
(354, 134, 32, 'D', '2026-05-26 13:55:21'),
(355, 252, 32, 'B', '2026-05-26 13:55:21'),
(356, 241, 32, 'B', '2026-05-26 13:55:21'),
(357, 371, 32, 'C', '2026-05-26 13:55:21'),
(358, 368, 32, 'A', '2026-05-26 13:55:21'),
(359, 386, 32, 'C', '2026-05-26 13:55:21'),
(360, 212, 32, 'A', '2026-05-26 13:55:21'),
(361, 247, 32, 'A', '2026-05-26 13:55:21'),
(362, 196, 32, 'C', '2026-05-26 13:55:21'),
(363, 211, 32, 'D', '2026-05-26 13:55:21'),
(364, 156, 32, 'A', '2026-05-26 13:55:21'),
(365, 189, 32, 'C', '2026-05-26 13:55:21'),
(366, 362, 32, 'B', '2026-05-26 13:55:21'),
(367, 160, 32, 'B', '2026-05-26 13:55:21'),
(368, 223, 32, 'A', '2026-05-26 13:55:21'),
(369, 111, 32, 'D', '2026-05-26 13:55:21'),
(370, 128, 32, 'A', '2026-05-26 13:55:21'),
(371, 142, 32, 'C', '2026-05-26 13:55:21'),
(372, 244, 32, 'D', '2026-05-26 13:55:21'),
(373, 208, 32, 'C', '2026-05-26 13:55:21'),
(374, 375, 32, 'D', '2026-05-26 13:55:21'),
(375, 220, 32, 'C', '2026-05-26 13:55:21'),
(376, 390, 32, 'C', '2026-05-26 13:55:21'),
(377, 358, 32, 'A', '2026-05-26 13:55:21'),
(378, 170, 32, 'C', '2026-05-26 13:55:21'),
(379, 231, 32, 'B', '2026-05-26 13:55:21'),
(380, 159, 32, 'D', '2026-05-26 13:55:21'),
(381, 163, 32, 'C', '2026-05-26 13:55:21'),
(382, 131, 32, 'B', '2026-05-26 13:55:21'),
(383, 130, 32, 'A', '2026-05-26 13:55:21'),
(384, 198, 32, 'B', '2026-05-26 13:55:21'),
(385, 168, 32, 'B', '2026-05-26 13:55:21'),
(386, 222, 32, 'A', '2026-05-26 13:55:21'),
(387, 363, 32, 'B', '2026-05-26 13:55:21'),
(388, 388, 32, 'C', '2026-05-26 13:55:21'),
(389, 218, 32, 'C', '2026-05-26 13:55:21'),
(390, 217, 32, 'D', '2026-05-26 13:55:21'),
(391, 187, 32, 'D', '2026-05-26 13:55:21'),
(392, 150, 32, 'B', '2026-05-26 13:55:21'),
(393, 250, 32, 'A', '2026-05-26 13:55:21'),
(394, 149, 32, 'A', '2026-05-26 13:55:21'),
(395, 205, 32, 'D', '2026-05-26 13:55:21'),
(396, 194, 32, 'A', '2026-05-26 13:55:21'),
(397, 225, 32, 'B', '2026-05-26 13:55:21'),
(398, 167, 32, 'B', '2026-05-26 13:55:21'),
(399, 372, 32, 'C', '2026-05-26 13:55:21');

-- --------------------------------------------------------

--
-- Table structure for table `randomization_runs`
--

CREATE TABLE `randomization_runs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `experiment_id` bigint(20) UNSIGNED NOT NULL,
  `seed` varchar(128) NOT NULL,
  `total_students` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `randomization_run_allocations`
--

CREATE TABLE `randomization_run_allocations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `run_id` bigint(20) UNSIGNED NOT NULL,
  `condition_id` bigint(20) UNSIGNED NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `assigned_count` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_versions`
--

CREATE TABLE `schema_versions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `version_number` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schema_versions`
--

INSERT INTO `schema_versions` (`id`, `version_number`, `description`, `applied_at`) VALUES
(1, 2, 'Greenfield multi-experiment schema', '2026-05-12 05:27:55');

-- --------------------------------------------------------

--
-- Table structure for table `slot_choices`
--

CREATE TABLE `slot_choices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `participation_id` bigint(20) UNSIGNED NOT NULL,
  `time_slot_id` bigint(20) UNSIGNED NOT NULL,
  `chosen_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `slot_choices`
--

INSERT INTO `slot_choices` (`id`, `participation_id`, `time_slot_id`, `chosen_at`) VALUES
(3, 111, 1, '2026-05-13 08:16:06'),
(4, 128, 1, '2026-05-13 09:57:56'),
(5, 130, 1, '2026-05-13 09:58:16'),
(6, 131, 1, '2026-05-13 09:58:35'),
(7, 134, 1, '2026-05-13 10:20:08'),
(8, 147, 2, '2026-05-13 12:59:21'),
(9, 149, 1, '2026-05-13 13:11:52'),
(10, 150, 1, '2026-05-13 13:31:45'),
(11, 156, 1, '2026-05-13 13:32:02'),
(12, 160, 1, '2026-05-13 13:48:49'),
(13, 161, 1, '2026-05-13 13:50:45'),
(14, 126, 1, '2026-05-13 13:54:53'),
(15, 170, 1, '2026-05-13 14:19:12'),
(16, 167, 1, '2026-05-13 14:19:13'),
(17, 169, 1, '2026-05-13 14:19:17'),
(18, 180, 3, '2026-05-13 15:44:14'),
(19, 178, 1, '2026-05-13 18:58:33'),
(20, 187, 1, '2026-05-13 18:59:48'),
(21, 189, 1, '2026-05-13 19:03:47'),
(22, 168, 2, '2026-05-13 19:05:14'),
(23, 188, 2, '2026-05-13 19:05:20'),
(24, 195, 1, '2026-05-13 19:07:19'),
(25, 190, 1, '2026-05-13 19:07:28'),
(26, 194, 1, '2026-05-13 19:07:33'),
(27, 198, 1, '2026-05-13 19:12:12'),
(28, 142, 1, '2026-05-13 19:13:14'),
(29, 197, 1, '2026-05-13 19:14:52'),
(30, 163, 1, '2026-05-13 19:15:29'),
(31, 199, 1, '2026-05-13 19:15:46'),
(32, 200, 2, '2026-05-13 19:17:32'),
(33, 201, 1, '2026-05-13 19:19:40'),
(34, 203, 2, '2026-05-13 19:20:03'),
(35, 202, 1, '2026-05-13 19:20:25'),
(36, 205, 1, '2026-05-13 19:34:58'),
(37, 208, 1, '2026-05-13 19:53:24'),
(38, 211, 2, '2026-05-13 20:01:38'),
(39, 212, 1, '2026-05-13 20:02:48'),
(40, 213, 2, '2026-05-13 20:13:58'),
(41, 193, 1, '2026-05-13 20:25:38'),
(42, 196, 1, '2026-05-13 20:26:27'),
(43, 215, 2, '2026-05-13 20:49:56'),
(44, 210, 1, '2026-05-13 20:54:46'),
(46, 217, 1, '2026-05-13 21:00:00'),
(47, 218, 1, '2026-05-13 21:08:21'),
(48, 219, 3, '2026-05-13 21:28:53'),
(49, 220, 1, '2026-05-13 21:45:47'),
(50, 222, 1, '2026-05-13 22:07:18'),
(51, 223, 1, '2026-05-13 22:18:10'),
(52, 225, 1, '2026-05-14 06:52:21'),
(54, 228, 3, '2026-05-14 07:25:48'),
(55, 229, 1, '2026-05-14 07:32:18'),
(56, 231, 1, '2026-05-14 08:01:01'),
(57, 230, 1, '2026-05-14 08:01:03'),
(58, 232, 1, '2026-05-14 08:01:21'),
(59, 233, 2, '2026-05-14 08:28:32'),
(60, 234, 2, '2026-05-14 08:33:53'),
(61, 235, 1, '2026-05-14 09:35:44'),
(62, 236, 2, '2026-05-14 13:36:00'),
(63, 237, 2, '2026-05-14 17:02:55'),
(64, 238, 1, '2026-05-14 17:23:22'),
(65, 239, 1, '2026-05-15 13:02:29'),
(66, 240, 3, '2026-05-15 15:03:10'),
(67, 241, 1, '2026-05-16 10:02:17'),
(68, 242, 3, '2026-05-16 11:15:10'),
(69, 243, 2, '2026-05-16 15:45:20'),
(70, 244, 2, '2026-05-16 20:22:15'),
(71, 245, 2, '2026-05-17 13:56:38'),
(72, 246, 2, '2026-05-17 14:37:27'),
(73, 247, 2, '2026-05-17 15:07:56'),
(74, 250, 2, '2026-05-18 07:24:49'),
(75, 252, 2, '2026-05-18 15:19:54'),
(76, 253, 2, '2026-05-18 17:11:48'),
(77, 254, 2, '2026-05-18 17:15:17'),
(78, 352, 2, '2026-05-18 20:29:54'),
(79, 356, 2, '2026-05-19 23:00:22'),
(80, 357, 2, '2026-05-19 23:03:27'),
(81, 358, 2, '2026-05-20 08:08:34'),
(82, 355, 2, '2026-05-20 10:21:21'),
(83, 363, 2, '2026-05-20 10:28:11'),
(84, 159, 2, '2026-05-20 10:29:49'),
(85, 368, 2, '2026-05-20 11:53:32'),
(86, 362, 2, '2026-05-20 12:32:55'),
(87, 371, 2, '2026-05-20 12:48:48'),
(88, 372, 2, '2026-05-20 14:15:32'),
(89, 375, 2, '2026-05-20 17:03:08'),
(90, 376, 2, '2026-05-20 17:38:19'),
(91, 378, 2, '2026-05-20 21:07:29'),
(92, 379, 2, '2026-05-20 21:33:10'),
(93, 380, 2, '2026-05-21 06:32:40'),
(94, 382, 3, '2026-05-21 12:49:28'),
(95, 386, 1, '2026-05-21 17:19:18'),
(96, 388, 2, '2026-05-21 18:37:45'),
(97, 389, 2, '2026-05-21 22:18:04'),
(98, 390, 2, '2026-05-22 04:03:57'),
(99, 391, 2, '2026-05-22 07:50:47'),
(100, 401, 2, '2026-05-25 10:30:03'),
(101, 402, 2, '2026-05-26 10:56:00');

-- --------------------------------------------------------

--
-- Table structure for table `time_slots`
--

CREATE TABLE `time_slots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `experiment_id` bigint(20) UNSIGNED NOT NULL,
  `label` varchar(255) NOT NULL,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `capacity` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_slots`
--

INSERT INTO `time_slots` (`id`, `experiment_id`, `label`, `starts_at`, `ends_at`, `capacity`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 6, 'Morgen', '2026-05-27 08:00:00', '2026-05-27 12:00:00', 48, 1, 0, '2026-05-12 23:13:38', '2026-05-12 23:14:35'),
(2, 6, 'Nachmittag', '2026-05-27 13:00:00', '2026-05-27 17:00:00', 48, 1, 1, '2026-05-12 23:14:27', '2026-05-12 23:14:27'),
(3, 6, 'Der 27. geht mir nicht', '0001-01-01 00:00:00', '0001-01-01 00:00:00', 105, 1, 2, '2026-05-13 06:55:53', '2026-05-13 06:56:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_fields`
--
ALTER TABLE `access_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_access_fields_key` (`experiment_id`,`condition_id`,`field_key`),
  ADD KEY `idx_access_fields_experiment` (`experiment_id`,`condition_id`,`sort_order`,`id`),
  ADD KEY `fk_access_fields_condition` (`condition_id`);

--
-- Indexes for table `access_pool_rows`
--
ALTER TABLE `access_pool_rows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_access_pool_rows_participation` (`assigned_participation_id`),
  ADD KEY `idx_access_pool_rows_available` (`experiment_id`,`condition_id`,`is_assigned`),
  ADD KEY `fk_access_pool_rows_condition` (`condition_id`);

--
-- Indexes for table `access_pool_values`
--
ALTER TABLE `access_pool_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_access_pool_values_row_field` (`pool_row_id`,`field_id`),
  ADD KEY `idx_access_pool_values_field` (`field_id`);

--
-- Indexes for table `allowed_students`
--
ALTER TABLE `allowed_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_allowed_students_student_email` (`student_email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_appointments_participation` (`participation_id`);

--
-- Indexes for table `eligibility_field_values`
--
ALTER TABLE `eligibility_field_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_eligibility_field_values` (`eligibility_id`,`field_id`),
  ADD KEY `idx_eligibility_field_values_field` (`field_id`);

--
-- Indexes for table `experiments`
--
ALTER TABLE `experiments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_experiments_sort_order` (`sort_order`,`id`),
  ADD KEY `idx_experiments_is_open` (`is_open`);

--
-- Indexes for table `experiment_conditions`
--
ALTER TABLE `experiment_conditions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_experiment_conditions_name` (`experiment_id`,`public_name`),
  ADD KEY `idx_experiment_conditions_experiment` (`experiment_id`,`sort_order`,`id`);

--
-- Indexes for table `experiment_eligibilities`
--
ALTER TABLE `experiment_eligibilities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_experiment_eligibilities_student` (`experiment_id`,`student_email`),
  ADD KEY `idx_experiment_eligibilities_student_email` (`student_email`),
  ADD KEY `idx_experiment_eligibilities_condition` (`condition_id`);

--
-- Indexes for table `participations`
--
ALTER TABLE `participations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_participations_experiment_student` (`experiment_id`,`student_email`),
  ADD UNIQUE KEY `uq_participations_access_pool_row` (`access_pool_row_id`),
  ADD KEY `idx_participations_student_email` (`student_email`),
  ADD KEY `idx_participations_condition` (`condition_id`);

--
-- Indexes for table `participation_field_values`
--
ALTER TABLE `participation_field_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_participation_field_values` (`participation_id`,`field_id`),
  ADD KEY `idx_participation_field_values_field` (`field_id`);

--
-- Indexes for table `randomization_runs`
--
ALTER TABLE `randomization_runs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_randomization_runs_experiment` (`experiment_id`,`created_at`);

--
-- Indexes for table `randomization_run_allocations`
--
ALTER TABLE `randomization_run_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_randomization_run_allocations_run` (`run_id`),
  ADD KEY `fk_randomization_run_allocations_condition` (`condition_id`);

--
-- Indexes for table `schema_versions`
--
ALTER TABLE `schema_versions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_schema_versions_version_number` (`version_number`);

--
-- Indexes for table `slot_choices`
--
ALTER TABLE `slot_choices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slot_choices_participation` (`participation_id`),
  ADD KEY `idx_slot_choices_slot` (`time_slot_id`);

--
-- Indexes for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_time_slots_experiment` (`experiment_id`,`is_active`,`sort_order`,`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_fields`
--
ALTER TABLE `access_fields`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `access_pool_rows`
--
ALTER TABLE `access_pool_rows`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=371;

--
-- AUTO_INCREMENT for table `access_pool_values`
--
ALTER TABLE `access_pool_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=771;

--
-- AUTO_INCREMENT for table `allowed_students`
--
ALTER TABLE `allowed_students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=107;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `eligibility_field_values`
--
ALTER TABLE `eligibility_field_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `experiments`
--
ALTER TABLE `experiments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `experiment_conditions`
--
ALTER TABLE `experiment_conditions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `experiment_eligibilities`
--
ALTER TABLE `experiment_eligibilities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=339;

--
-- AUTO_INCREMENT for table `participations`
--
ALTER TABLE `participations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=403;

--
-- AUTO_INCREMENT for table `participation_field_values`
--
ALTER TABLE `participation_field_values`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=400;

--
-- AUTO_INCREMENT for table `randomization_runs`
--
ALTER TABLE `randomization_runs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `randomization_run_allocations`
--
ALTER TABLE `randomization_run_allocations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schema_versions`
--
ALTER TABLE `schema_versions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `slot_choices`
--
ALTER TABLE `slot_choices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

--
-- AUTO_INCREMENT for table `time_slots`
--
ALTER TABLE `time_slots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_fields`
--
ALTER TABLE `access_fields`
  ADD CONSTRAINT `fk_access_fields_condition` FOREIGN KEY (`condition_id`) REFERENCES `experiment_conditions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_access_fields_experiment` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `access_pool_rows`
--
ALTER TABLE `access_pool_rows`
  ADD CONSTRAINT `fk_access_pool_rows_condition` FOREIGN KEY (`condition_id`) REFERENCES `experiment_conditions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_access_pool_rows_experiment` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `access_pool_values`
--
ALTER TABLE `access_pool_values`
  ADD CONSTRAINT `fk_access_pool_values_field` FOREIGN KEY (`field_id`) REFERENCES `access_fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_access_pool_values_row` FOREIGN KEY (`pool_row_id`) REFERENCES `access_pool_rows` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `fk_appointments_participation` FOREIGN KEY (`participation_id`) REFERENCES `participations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `eligibility_field_values`
--
ALTER TABLE `eligibility_field_values`
  ADD CONSTRAINT `fk_eligibility_field_values_eligibility` FOREIGN KEY (`eligibility_id`) REFERENCES `experiment_eligibilities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_eligibility_field_values_field` FOREIGN KEY (`field_id`) REFERENCES `access_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `experiment_conditions`
--
ALTER TABLE `experiment_conditions`
  ADD CONSTRAINT `fk_experiment_conditions_experiment` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `experiment_eligibilities`
--
ALTER TABLE `experiment_eligibilities`
  ADD CONSTRAINT `fk_experiment_eligibilities_allowed_student` FOREIGN KEY (`student_email`) REFERENCES `allowed_students` (`student_email`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_experiment_eligibilities_condition` FOREIGN KEY (`condition_id`) REFERENCES `experiment_conditions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_experiment_eligibilities_experiment` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `participations`
--
ALTER TABLE `participations`
  ADD CONSTRAINT `fk_participations_access_pool_row` FOREIGN KEY (`access_pool_row_id`) REFERENCES `access_pool_rows` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_participations_allowed_student` FOREIGN KEY (`student_email`) REFERENCES `allowed_students` (`student_email`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_participations_condition` FOREIGN KEY (`condition_id`) REFERENCES `experiment_conditions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_participations_experiment` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `participation_field_values`
--
ALTER TABLE `participation_field_values`
  ADD CONSTRAINT `fk_participation_field_values_field` FOREIGN KEY (`field_id`) REFERENCES `access_fields` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_participation_field_values_participation` FOREIGN KEY (`participation_id`) REFERENCES `participations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `randomization_runs`
--
ALTER TABLE `randomization_runs`
  ADD CONSTRAINT `fk_randomization_runs_experiment` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `randomization_run_allocations`
--
ALTER TABLE `randomization_run_allocations`
  ADD CONSTRAINT `fk_randomization_run_allocations_condition` FOREIGN KEY (`condition_id`) REFERENCES `experiment_conditions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_randomization_run_allocations_run` FOREIGN KEY (`run_id`) REFERENCES `randomization_runs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `slot_choices`
--
ALTER TABLE `slot_choices`
  ADD CONSTRAINT `fk_slot_choices_participation` FOREIGN KEY (`participation_id`) REFERENCES `participations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_slot_choices_slot` FOREIGN KEY (`time_slot_id`) REFERENCES `time_slots` (`id`);

--
-- Constraints for table `time_slots`
--
ALTER TABLE `time_slots`
  ADD CONSTRAINT `fk_time_slots_experiment` FOREIGN KEY (`experiment_id`) REFERENCES `experiments` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
