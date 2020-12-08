-- --------------------------------------------------------
-- Host:                         37.48.247.20
-- Server version:               10.5.8-MariaDB-1:10.5.8+maria~xenial - mariadb.org binary distribution
-- Server OS:                    debian-linux-gnu
-- HeidiSQL Version:             10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table orcalive_civicrm.starfish_civi_county_mapping
CREATE TABLE IF NOT EXISTS `starfish_civi_county_mapping` (
  `id` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'State/Province ID',
  `name` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Name of State/Province',
  `abbreviation` varchar(4) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '2-4 Character Abbreviation of State/Province',
  `country_id` int(10) unsigned NOT NULL COMMENT 'ID of Country that State/Province belong',
  `starfish_county` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table orcalive_civicrm.starfish_civi_county_mapping: ~97 rows (approximately)
/*!40000 ALTER TABLE `starfish_civi_county_mapping` DISABLE KEYS */;
INSERT INTO `starfish_civi_county_mapping` (`id`, `name`, `abbreviation`, `country_id`, `starfish_county`) VALUES
	(2592, 'Aberdeen City', 'ABE', 1226, NULL),
	(2593, 'Aberdeenshire', 'ABD', 1226, NULL),
	(2594, 'Angus', 'ANS', 1226, NULL),
	(2595, 'Co Antrim', 'ANT', 1226, NULL),
	(2597, 'Argyll and Bute', 'AGB', 1226, NULL),
	(2598, 'Co Armagh', 'ARM', 1226, NULL),
	(2606, 'Bedfordshire', 'BDF', 1226, 'Bedfordshire'),
	(2612, 'Gwent', 'BGW', 1226, NULL),
	(2620, 'Bristol, City of', 'BST', 1226, 'City of Bristol'),
	(2622, 'Buckinghamshire', 'BKM', 1226, 'Buckinghamshire'),
	(2626, 'Cambridgeshire', 'CAM', 1226, 'Cambridgeshire'),
	(2634, 'Cheshire', 'CHS', 1226, 'Cheshire'),
	(2635, 'Clackmannanshire', 'CLK', 1226, NULL),
	(2639, 'Cornwall', 'CON', 1226, 'Cornwall'),
	(2643, 'Cumbria', 'CMA', 1226, 'Cumbria'),
	(2647, 'Derbyshire', 'DBY', 1226, 'Derbyshire'),
	(2648, 'Co Londonderry', 'DRY', 1226, NULL),
	(2649, 'Devon', 'DEV', 1226, 'Devon'),
	(2651, 'Dorset', 'DOR', 1226, 'Dorset'),
	(2652, 'Co Down', 'DOW', 1226, NULL),
	(2654, 'Dumfries and Galloway', 'DGY', 1226, NULL),
	(2655, 'Dundee City', 'DND', 1226, NULL),
	(2657, 'County Durham', 'DUR', 1226, 'County Durham'),
	(2659, 'East Ayrshire', 'EAY', 1226, NULL),
	(2660, 'East Dunbartonshire', 'EDU', 1226, NULL),
	(2661, 'East Lothian', 'ELN', 1226, 'East Lothian'),
	(2662, 'East Renfrewshire', 'ERW', 1226, NULL),
	(2663, 'East Riding of Yorkshire', 'ERY', 1226, 'East Yorkshire'),
	(2664, 'East Sussex', 'ESX', 1226, 'East Sussex'),
	(2665, 'Edinburgh, City of', 'EDH', 1226, NULL),
	(2666, 'Na h-Eileanan Siar', 'ELS', 1226, NULL),
	(2668, 'Essex', 'ESS', 1226, 'Essex'),
	(2669, 'Falkirk', 'FAL', 1226, NULL),
	(2670, 'Co Fermanagh', 'FER', 1226, 'Co Fermanagh'),
	(2671, 'Fife', 'FIF', 1226, 'Fife'),
	(2674, 'Glasgow City', 'GLG', 1226, NULL),
	(2675, 'Gloucestershire', 'GLS', 1226, 'Gloucestershire'),
	(2678, 'Gwynedd', 'GWN', 1226, NULL),
	(2682, 'Hampshire', 'HAM', 1226, 'Hampshire'),
	(2687, 'Herefordshire', 'HEF', 1226, 'Herefordshire'),
	(2688, 'Hertfordshire', 'HRT', 1226, 'Hertfordshire'),
	(2689, 'Highland', 'HED', 1226, NULL),
	(2692, 'Inverclyde', 'IVC', 1226, NULL),
	(2694, 'Isle of Wight', 'IOW', 1226, NULL),
	(2699, 'Kent', 'KEN', 1226, 'Kent'),
	(2705, 'Lancashire', 'LAN', 1226, 'Lancashire'),
	(2709, 'Leicestershire', 'LEC', 1226, 'Leicestershire'),
	(2712, 'Lincolnshire', 'LIN', 1226, 'Lincolnshire'),
	(2723, 'Midlothian', 'MLN', 1226, 'Midlothian'),
	(2726, 'Moray', 'MRY', 1226, NULL),
	(2734, 'Norfolk', 'NFK', 1226, 'Norfolk'),
	(2735, 'North Ayrshire', 'NAY', 1226, NULL),
	(2738, 'North Lanarkshire', 'NLK', 1226, NULL),
	(2742, 'North Yorkshire', 'NYK', 1226, 'North Yorkshire'),
	(2743, 'Northamptonshire', 'NTH', 1226, 'Northamptonshire'),
	(2744, 'Northumberland', 'NBL', 1226, 'Northumberland'),
	(2746, 'Nottinghamshire', 'NTT', 1226, 'Nottinghamshire'),
	(2747, 'Oldham', 'OLD', 1226, NULL),
	(2748, 'Omagh', 'OMH', 1226, NULL),
	(2749, 'Orkney Islands', 'ORR', 1226, NULL),
	(2750, 'Oxfordshire', 'OXF', 1226, 'Oxfordshire'),
	(2752, 'Perth and Kinross', 'PKN', 1226, NULL),
	(2757, 'Powys', 'POW', 1226, 'Powys'),
	(2761, 'Renfrewshire', 'RFW', 1226, NULL),
	(2766, 'Rutland', 'RUT', 1226, 'Rutland'),
	(2770, 'Scottish Borders', 'SCB', 1226, NULL),
	(2773, 'Shetland Islands', 'ZET', 1226, NULL),
	(2774, 'Shropshire', 'SHR', 1226, 'Shropshire'),
	(2777, 'Somerset', 'SOM', 1226, 'Somerset'),
	(2778, 'South Ayrshire', 'SAY', 1226, NULL),
	(2779, 'South Gloucestershire', 'SGC', 1226, 'South Gloucestershire'),
	(2780, 'South Lanarkshire', 'SLK', 1226, NULL),
	(2785, 'Staffordshire', 'STS', 1226, 'Staffordshire'),
	(2786, 'Stirling', 'STG', 1226, NULL),
	(2791, 'Suffolk', 'SFK', 1226, 'Suffolk'),
	(2793, 'Surrey', 'SRY', 1226, 'Surrey'),
	(2804, 'Mid Glamorgan', 'VGL', 1226, 'Mid Glamorgan'),
	(2811, 'Warwickshire', 'WAR', 1226, 'Warwickshire'),
	(2813, 'West Dunbartonshire', 'WDU', 1226, NULL),
	(2814, 'West Lothian', 'WLN', 1226, 'West Lothian'),
	(2815, 'West Sussex', 'WSX', 1226, 'West Sussex'),
	(2818, 'Wiltshire', 'WIL', 1226, 'Wiltshire'),
	(2823, 'Worcestershire', 'WOR', 1226, 'Worcestershire'),
	(9986, 'Tyne and Wear', 'TWR', 1226, 'Tyne and Wear'),
	(9988, 'Greater Manchester', 'GTM', 1226, 'Greater Manchester'),
	(9989, 'Co Tyrone', 'TYR', 1226, NULL),
	(9990, 'West Yorkshire', 'WYK', 1226, 'West Yorkshire'),
	(9991, 'South Yorkshire', 'SYK', 1226, 'South Yorkshire'),
	(9992, 'Merseyside', 'MSY', 1226, 'Merseyside'),
	(9993, 'Berkshire', 'BRK', 1226, 'Berkshire'),
	(9994, 'West Midlands', 'WMD', 1226, 'West Midlands'),
	(9998, 'West Glamorgan', 'WGM', 1226, NULL),
	(9999, 'London', 'LON', 1226, 'Greater London'),
	(10013, 'Clwyd', 'CWD', 1226, NULL),
	(10014, 'Dyfed', 'DFD', 1226, NULL),
	(10015, 'South Glamorgan', 'SGM', 1226, 'South Glamorgan'),
	(10364, 'Monmouthshire', 'MON', 1226, NULL),
	(0, 'Somerset', 'SOM', 1226, 'South Somerset'),
	(0, 'Somerset', 'SOM', 1226, 'North Somerset');
/*!40000 ALTER TABLE `starfish_civi_county_mapping` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
