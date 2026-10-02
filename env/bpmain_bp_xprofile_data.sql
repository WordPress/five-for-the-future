SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wordpress`
--

-- --------------------------------------------------------

--
-- Table structure for table `bpmain_bp_xprofile_data`
--

CREATE TABLE `bpmain_bp_xprofile_data` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `field_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `value` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bpmain_bp_xprofile_data`
--

INSERT INTO `bpmain_bp_xprofile_data` (`id`, `field_id`, `user_id`, `value`, `last_updated`) VALUES
(1, 24, 2, 'Yes', '2024-07-01 01:38:31'),
(2, 29, 2, '40', '2024-07-01 01:38:31'),
(3, 30, 2, 'a:1:{i:0;s:9:\"Meta Team\";}', '2024-07-01 01:38:31'),
(4, 24, 3, 'Yes', '2024-07-01 01:38:31'),
(5, 29, 3, '40', '2024-07-01 01:38:31'),
(6, 30, 3, 'a:1:{i:0;s:9:\"Meta Team\";}', '2024-07-01 01:38:31'),
(7, 24, 4, 'Yes', '2024-07-01 01:38:31'),
(8, 29, 4, '40', '2024-07-01 01:38:31'),
(9, 30, 4, 'a:1:{i:0;s:9:\"Meta Team\";}', '2024-07-01 01:38:31'),
(10, 24, 5, 'Yes', '2024-07-01 01:38:31'),
(11, 29, 5, '40', '2024-07-01 01:38:31'),
(12, 30, 5, 'a:1:{i:0;s:9:\"Meta Team\";}', '2024-07-01 01:38:31'),
(13, 24, 6, 'Yes', '2024-07-01 01:38:31'),
(14, 29, 6, '40', '2024-07-01 01:38:31'),
(15, 30, 6, 'a:1:{i:0;s:9:\"Meta Team\";}', '2024-07-01 01:38:31'),
(16, 24, 7, 'Yes', '2024-07-01 01:38:31'),
(17, 29, 7, '40', '2024-07-01 01:38:31'),
(18, 30, 7, 'a:1:{i:0;s:9:\"Meta Team\";}', '2024-07-01 01:38:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bpmain_bp_xprofile_data`
--
ALTER TABLE `bpmain_bp_xprofile_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `field_id` (`field_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bpmain_bp_xprofile_data`
--
ALTER TABLE `bpmain_bp_xprofile_data`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
