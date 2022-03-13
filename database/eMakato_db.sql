-- -------------------------------------------------------------
-- TablePlus 4.6.0(406)
--
-- https://tableplus.com/
--
-- Database: eMakato_db
-- Generation Time: 2022-03-13 14:16:50.5530
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


DROP TABLE IF EXISTS `account_types`;
CREATE TABLE `account_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(15) NOT NULL,
  `description` varchar(55) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `account_type_id` int NOT NULL,
  `name` varchar(15) NOT NULL,
  `description` varchar(55) DEFAULT NULL,
  `mask` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `acquisition`;
CREATE TABLE `acquisition` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(200) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` int NOT NULL,
  `name` varchar(45) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `customer_accounts`;
CREATE TABLE `customer_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `account_type_id` int NOT NULL,
  `account_id` int NOT NULL,
  `account_number` varchar(55) NOT NULL,
  `account_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `partner_id` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `first_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `last_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `middle_name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `id_type` int DEFAULT NULL,
  `id_number` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender_id` int DEFAULT NULL,
  `marital_status_id` int DEFAULT NULL,
  `region_id` int DEFAULT NULL,
  `sector_id` int DEFAULT NULL,
  `occupation_id` int DEFAULT NULL,
  `acquisition_id` int DEFAULT NULL,
  `dependant` int DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'inactive',
  `created_by` int NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `added_by` (`created_by`),
  KEY `id_type` (`id_type`,`region_id`,`acquisition_id`),
  KEY `region_id` (`region_id`),
  KEY `acquisition_id` (`acquisition_id`),
  KEY `occupation` (`occupation_id`),
  KEY `sector_id` (`sector_id`),
  KEY `gender_id` (`gender_id`),
  KEY `martial_status_id` (`marital_status_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `debit_orders`;
CREATE TABLE `debit_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_account_id` int NOT NULL,
  `code` varchar(11) DEFAULT NULL,
  `loan_purpose_id` int DEFAULT NULL,
  `total_amount` float NOT NULL,
  `paid_amount` float NOT NULL DEFAULT '0',
  `outstanding_amount` float NOT NULL DEFAULT '0',
  `debit_type` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `min_installment_amount` float DEFAULT '0',
  `max_installment_amount` float DEFAULT '0',
  `installment_number` int NOT NULL,
  `periodicity` enum('ANNUALLY','MONTHLY','WEEKLY','DAILY') NOT NULL DEFAULT 'MONTHLY',
  `deduction_end_date` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `evidence_of_aggrement` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` int DEFAULT NULL,
  `approver_at` datetime DEFAULT NULL,
  `approve_code` varchar(15) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `gender`;
CREATE TABLE `gender` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(10) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `id_types`;
CREATE TABLE `id_types` (
  `id` int NOT NULL,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `loan_purposes`;
CREATE TABLE `loan_purposes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `marital_status`;
CREATE TABLE `marital_status` (
  `id` int NOT NULL,
  `name` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `occupations`;
CREATE TABLE `occupations` (
  `id` int NOT NULL,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `partner_accounts`;
CREATE TABLE `partner_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `account_type_id` int NOT NULL,
  `partner_uuid` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `account_id` int NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(25) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text CHARACTER SET utf8 COLLATE utf8_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `partner_approvals`;
CREATE TABLE `partner_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int DEFAULT NULL,
  `key` varchar(255) DEFAULT NULL,
  `label` varchar(255) DEFAULT NULL,
  `active` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `partner_categories`;
CREATE TABLE `partner_categories` (
  `id` int NOT NULL,
  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(80) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `partner_documents`;
CREATE TABLE `partner_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `partner_uuid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `partner_staff`;
CREATE TABLE `partner_staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `partner_uuid` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `NIDA_number` varchar(30) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(25) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `partner_users`;
CREATE TABLE `partner_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `partner_id` int DEFAULT NULL,
  `partner_uuid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `authorize` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `partners`;
CREATE TABLE `partners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `partner_category_id` int NOT NULL,
  `business_name` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `year_of_incorporation` year NOT NULL,
  `tin_number` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `number_of_staff` int NOT NULL,
  `region_id` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `website` varchar(100) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `postal_address` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `physical_address` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `complete` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `next_step` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `otp_token` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `region_id` (`region_id`,`partner_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payment_histories`;
CREATE TABLE `payment_histories` (
  `id` int NOT NULL,
  `debit_order_id` int NOT NULL,
  `expected_amount` float NOT NULL,
  `paid_amount` float NOT NULL,
  `payment_reference` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `payment_logs`;
CREATE TABLE `payment_logs` (
  `id` int NOT NULL,
  `msisdn` varchar(15) NOT NULL,
  `amount` int NOT NULL,
  `end_date` date DEFAULT NULL,
  `reference` varchar(45) NOT NULL,
  `spid` int NOT NULL DEFAULT '259911',
  `request_id` varchar(45) DEFAULT NULL,
  `service_id` int NOT NULL DEFAULT '1',
  `callback` varchar(100) DEFAULT NULL,
  `ipg_reference` varchar(45) DEFAULT NULL,
  `status` enum('INITIATED','SUCCESS','FAIL','ERROR') NOT NULL DEFAULT 'INITIATED',
  `remarks` varchar(65) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int NOT NULL,
  `amount` float NOT NULL,
  `reference` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `msisdn` varchar(20) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `transid` varchar(50) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `insight_reference` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `remarks` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `perm_classes`;
CREATE TABLE `perm_classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordering` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `perm_methods`;
CREATE TABLE `perm_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `perm_methods_class_id_foreign` (`class_id`),
  CONSTRAINT `perm_methods_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `perm_classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `perm_roles`;
CREATE TABLE `perm_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `classes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `methods` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `perm_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `perm_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `perm_users`;
CREATE TABLE `perm_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `classes` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `methods` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `perm_users_user_id_foreign` (`user_id`),
  CONSTRAINT `perm_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `regions`;
CREATE TABLE `regions` (
  `id` int NOT NULL,
  `name` varchar(45) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `role_user`;
CREATE TABLE `role_user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `role_user_user_id_foreign` (`user_id`),
  KEY `role_user_role_id_foreign` (`role_id`),
  CONSTRAINT `role_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sectors`;
CREATE TABLE `sectors` (
  `id` int NOT NULL,
  `name` varchar(40) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `description` varchar(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NIDA_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verify_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `account_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'BANK', 'bank', '2021-11-02 11:02:01', '2021-11-02 11:02:01'),
(2, 'MNO', 'mno', '2021-11-02 11:02:01', '2021-11-02 11:02:01');

INSERT INTO `accounts` (`id`, `account_type_id`, `name`, `description`, `mask`, `created_at`, `updated_at`) VALUES
(1, 1, 'NMB Bank', 'NMB Bank', '99999999999', '2022-02-24 15:36:38', '2022-03-03 12:09:43'),
(2, 2, 'Vodacom', 'vodacom', NULL, '2022-02-24 15:36:38', '2022-02-24 15:51:15');

INSERT INTO `acquisition` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'WEB', 'Acquired from web portal', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(2, 'USSD', 'Acquired from USSD', '2021-10-13 00:00:00', '2021-10-06 00:00:00'),
(3, 'MOBILE APP', 'Acquired from MOBILE APP', '2021-10-13 00:00:00', '2021-10-06 00:00:00');

INSERT INTO `countries` (`id`, `code`, `name`, `created_at`, `updated_at`) VALUES
(1, 255, 'TANZANIA', '2021-11-06 12:12:54', '2021-11-06 12:12:54');

INSERT INTO `customer_accounts` (`id`, `customer_id`, `account_type_id`, `account_id`, `account_number`, `account_name`, `phone`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '011877465544', NULL, '0717705746', 21, '2022-03-03 11:46:29', '2022-03-03 11:46:29'),
(2, 2, 1, 1, '012345678', NULL, '0717705746', 23, '2022-03-03 15:14:48', '2022-03-03 15:14:48'),
(3, 3, 1, 1, '0117645544338', 'Renfrid Ngolongolo', '0717705746', 24, '2022-03-12 10:02:13', '2022-03-12 10:02:13');

INSERT INTO `customers` (`id`, `uuid`, `partner_id`, `phone`, `first_name`, `last_name`, `middle_name`, `dob`, `id_type`, `id_number`, `gender_id`, `marital_status_id`, `region_id`, `sector_id`, `occupation_id`, `acquisition_id`, `dependant`, `status`, `created_by`, `created_at`, `updated_at`, `updated_by`) VALUES
(1, 'df728d58-39b3-4e01-946e-87db6ab5a61d', '1', NULL, 'Renfrid', 'Ngolongolo', 'W', '1947-06-06', 1, '88366465-53553-55252-55', 1, 1, NULL, 1, 1, NULL, NULL, 'Active', 21, '2022-03-03 11:46:29', '2022-03-03 11:46:29', NULL),
(2, '826f9072-4e7b-4d96-9a03-1c2c202ede81', '1', NULL, 'Renfrid', 'William', 'W', '1949-04-06', 1, '18877366-35553-66535-55', 1, 1, NULL, 1, 1, NULL, NULL, 'Active', 23, '2022-03-03 15:14:48', '2022-03-03 15:14:48', NULL),
(3, 'afb0016f-ba0d-4362-815c-94ca3844121e', '2', NULL, 'Renfrid', 'ngolongolo', 'William', '1987-11-08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'active', 24, '2022-03-12 09:20:03', '2022-03-13 09:48:37', 24);

INSERT INTO `debit_orders` (`id`, `customer_account_id`, `code`, `loan_purpose_id`, `total_amount`, `paid_amount`, `outstanding_amount`, `debit_type`, `min_installment_amount`, `max_installment_amount`, `installment_number`, `periodicity`, `deduction_end_date`, `start_date`, `end_date`, `evidence_of_aggrement`, `approved`, `approved_by`, `approver_at`, `approve_code`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 2, 1000000, 0, 0, NULL, 100000, NULL, 10, 'MONTHLY', NULL, '2022-03-10', NULL, 'Sample Template.xlsx', 0, NULL, NULL, NULL, '2022-03-03 11:46:29', '2022-03-03 11:46:29'),
(2, 2, NULL, 2, 1000000, 0, 0, NULL, 100000, NULL, 10, 'MONTHLY', NULL, '2022-03-10', NULL, NULL, 0, NULL, NULL, NULL, '2022-03-03 15:14:48', '2022-03-03 15:14:48'),
(3, 3, NULL, NULL, 2000000, 0, 0, 'FIXED', 0, 120000, 20, 'MONTHLY', 'specific_date', '2022-03-19', '2022-04-10', 'phems.pdf', 0, NULL, NULL, NULL, '2022-03-12 12:02:29', '2022-03-12 12:02:29');

INSERT INTO `gender` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Male', '2021-10-14 00:00:00', '2021-10-26 01:12:57'),
(2, 'Female', '2021-10-14 00:00:00', '2021-10-26 01:12:57');

INSERT INTO `id_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'NIDA', 'National Identity Card Number', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(2, 'DRIVING LICENCE', 'Driving licence card', '2021-10-14 00:00:00', '2021-10-14 00:00:00');

INSERT INTO `loan_purposes` (`id`, `name`) VALUES
(1, 'Emergencies'),
(2, 'Debt consolidation'),
(3, 'Home improvements or repairs'),
(4, 'Child-related costs'),
(5, 'Major life milestones');

INSERT INTO `marital_status` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Married', '2021-10-14 16:33:06', '2021-10-26 01:12:57'),
(2, 'Single', '2021-10-14 16:33:06', '2021-10-26 01:12:57'),
(3, 'Divorce', '2021-10-14 16:33:20', '2021-10-26 01:12:57');

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_000000_create_users_table', 1),
(2, '2014_10_12_100000_create_password_resets_table', 1),
(3, '2019_08_19_000000_create_failed_jobs_table', 1),
(4, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(5, '2022_02_12_073539_create_roles_table', 2),
(6, '2022_02_12_073554_create_role_user_table', 2),
(7, '2022_02_12_075306_create_perm_classes_table', 3),
(8, '2022_02_12_075318_create_perm_methods_table', 4),
(9, '2022_02_12_075333_create_perm_roles_table', 4),
(10, '2022_02_12_075341_create_perm_users_table', 4);

INSERT INTO `occupations` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Accountant', 'Accountant', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(2, 'Architect', 'Architect', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(3, 'Engineer', 'Engineer', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(4, ' Medical Practitioner', ' Medical Practitioner', '2021-10-14 00:00:00', '2021-10-14 00:00:00');

INSERT INTO `partner_accounts` (`id`, `partner_id`, `account_type_id`, `partner_uuid`, `account_id`, `account_name`, `account_number`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', 1, 'Laina Finance', '0233445567', 0, NULL, '2022-03-03 14:22:41', '2022-03-03 14:22:41'),
(2, 2, 1, 'c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', 1, 'Laina Finance', '01124786663553', 0, NULL, '2022-03-07 10:26:14', '2022-03-07 10:26:14');

INSERT INTO `partner_approvals` (`id`, `partner_id`, `key`, `label`, `active`) VALUES
(1, 2, 'Company_Information', 'Company Information', 1),
(2, 2, 'Company_Documents', 'Company Documents', 1),
(3, 2, 'Company_Collection_Accounts', 'Company Collection Accounts', 1),
(4, 2, 'Company_Authorized_Representative', 'Company Authorized Representative', 1);

INSERT INTO `partner_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'MICROFINANCE', 'Shop owned by Vodacom', '2021-10-25 23:31:18', '2021-11-04 16:46:59'),
(2, 'SHOPS', 'Other Shop which are not owned by MNO', '2021-10-25 23:36:03', '2021-11-04 16:47:09'),
(3, 'INSURER', 'Insurance Companies which issues IPF', '2021-10-25 23:37:50', '2021-11-04 16:47:29');

INSERT INTO `partner_documents` (`id`, `partner_id`, `partner_uuid`, `key`, `name`, `attachment`) VALUES
(1, 1, '5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', 'business_licence', 'Business Licence', 'Production-template.xlsx'),
(2, 1, '5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', 'certificate_of_incorporation', 'Certificate of incorporation', 'Sample Template.xlsx'),
(3, 1, '5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', 'TIN_certificate', 'TIN Certificate', 'Draft Table_Revised.xlsx'),
(4, 2, 'c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', 'business_licence', 'Business Licence', '505-5053288_clouds-media-group-clouds-entertainment.png'),
(5, 2, 'c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', 'certificate_of_incorporation', 'Certificate of incorporation', 'Draft Table_Revised.xlsx'),
(6, 2, 'c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', 'TIN_certificate', 'TIN Certificate', 'Screenshot 2022-03-06 at 13.00.43.png');

INSERT INTO `partner_staff` (`id`, `partner_id`, `partner_uuid`, `name`, `NIDA_number`, `email`, `phone`, `title`, `created_at`, `updated_at`) VALUES
(1, 1, '5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', 'Renfrid', '12345567-88746-64455-89', 'renfrid@gmail.com', '0717705746', 'CFO', '2022-03-03 17:28:33', '2022-03-03 17:28:33'),
(2, 2, 'c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', 'Renfrid', '38736636-53535-53535-53', 'renfridfrancis@gmail.com', '255717705746', 'CEO', '2022-03-07 13:56:31', '2022-03-07 13:56:31');

INSERT INTO `partner_users` (`id`, `user_id`, `partner_id`, `partner_uuid`, `authorize`, `created_at`, `updated_at`) VALUES
(1, 23, 1, '5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', 1, '2022-03-03 14:32:26', '2022-03-03 14:32:26'),
(2, 24, 2, 'c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', 1, '2022-03-07 11:18:26', '2022-03-07 11:18:26');

INSERT INTO `partners` (`id`, `uuid`, `partner_category_id`, `business_name`, `year_of_incorporation`, `tin_number`, `number_of_staff`, `region_id`, `phone`, `email`, `website`, `postal_address`, `physical_address`, `complete`, `status`, `next_step`, `otp_token`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, '5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', 1, 'Laina Finance', '2013', '003-435-289', 12, '2', '0717705746', 'info@lainafinance.com', NULL, NULL, 'Mikocheni', 1, 3, 'company-info-overview/5c5bd81a-2ee3-49b0-8d33-11ef3f96b8cf', NULL, NULL, NULL, '2022-03-03 14:14:41', '2022-03-03 14:46:12'),
(2, 'c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', 1, 'Smart Wallet', '2020', '998-377-363', 10, '2', '255717705746', 'info@smartwallet.co.tz', NULL, 'P.0 Box 1443', 'Mikocheni, Dar es Salaam', 1, 3, 'company-info-overview/c4056861-0c4c-4e4b-a522-2cc1de3a2fa0', '548180', NULL, NULL, '2022-03-07 09:33:20', '2022-03-13 11:13:58');

INSERT INTO `payment_histories` (`id`, `debit_order_id`, `expected_amount`, `paid_amount`, `payment_reference`, `created_at`, `updated_at`) VALUES
(9, 25, 7168, 15000, 'laf89-093240', '2021-10-21 17:49:35', '2021-10-26 01:12:57'),
(10, 25, 6542, 6400, 'laf89-093240y', '2021-10-21 17:51:19', '2021-10-26 01:12:57');

INSERT INTO `payments` (`id`, `amount`, `reference`, `msisdn`, `status`, `transid`, `insight_reference`, `remarks`, `created_at`, `updated_at`) VALUES
(18, 27000, 'LAF-29209', '255744423289', 'SUCCESS', 'LAF-29209', 'LAF-96380', NULL, '2021-11-11 09:19:35', '2021-11-11 09:19:35'),
(21, 190001, 'LAF-45228', '255653190260', 'SUCCESS', 'LAF-45228', 'LAF-63157', NULL, '2021-11-11 10:36:55', '2021-11-11 10:36:55');

INSERT INTO `regions` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'ARUSHA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(2, 'DAR ES SALAAM', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(3, 'DODOMA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(4, 'GEITA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(5, 'IRINGA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(6, 'KAGERA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(7, 'KATAVI', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(8, 'KIGOMA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(9, 'KILIMANJARO', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(10, 'LINDI', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(11, 'MANYARA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(12, 'MARA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(13, 'MBEYA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(14, 'MOROGORO', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(15, 'MTWARA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(16, 'MWANZA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(17, 'NJOMBE', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(18, 'PWANI', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(19, 'RUKWA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(20, 'RUVUMA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(21, 'SHINYANGA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(22, 'SIMIYU', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(23, 'SINGIDA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(24, 'SONGWE', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(25, 'TABORA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(26, 'TANGA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(27, 'KASKAZINI PEMBA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(28, 'KASKAZINI UNGUJA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(29, 'KUSINI PEMBA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(30, 'KUSINI UNGUJA', '2021-05-04 04:19:07', '2021-10-26 01:12:57'),
(31, 'MJINIMAGHARIBI', '2021-05-04 04:19:07', '2021-10-26 01:12:57');

INSERT INTO `role_user` (`id`, `user_id`, `role_id`) VALUES
(1, 1, 1),
(16, 21, 2),
(17, 22, 2),
(18, 23, 2),
(19, 24, 2);

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'company_approver', 'Company Approver');

INSERT INTO `sectors` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'GOVERNMENT', 'Government employees', '2021-10-14 16:28:21', '2021-10-26 01:12:57'),
(2, 'PRIVATE', 'SME', '2021-10-14 16:28:21', '2021-11-04 14:12:05');

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `NIDA_number`, `email_verified_at`, `password`, `remember_token`, `verify_token`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Admin Solocodes', 'admin@lainafinance.co.tz', NULL, NULL, NULL, '$2y$10$0b/tjmMN.FnQhhmwRh2WkuW5FzSMrIPqOXbd1CPw/rcLQcV7z/mL2', NULL, NULL, 1, NULL, NULL),
(21, 'Tony', 'julius@lainafinance.com', '0717705746', '38736262-53552-35325-53', NULL, '$2y$10$xYSjDvPy1VrBqiqINaHAoOh21yvQgiPN.rckzpYa8BOVbQ8DNvCaS', NULL, 'CiMalabq8KsSNOQuodAkej4KlRQ0etfaMy9J2rYsQO3yJ6w9gmRiDEHopcTdiC5C', 1, '2022-03-03 11:32:16', '2022-03-03 11:39:08'),
(22, 're', 're@gmail.com', '099882772', '73437648-23476-32647-82', NULL, '$2y$10$VRccDCp1OZOylLTvvDjgpe6fzwI/CKExKU3WZCCkJhAwbKasW4VOO', NULL, 'xJZCpme6DL6uRroBCBw3dEwS52HVRR1noQjyoxF5y6qf1BY6GLZSTbrgJeynfWQ8', 0, '2022-03-03 13:49:06', '2022-03-03 13:49:06'),
(23, 'Renfrid', 'renfridfrancis@gmail.com', '0717705746', '66455599-44444-44444-12', NULL, '$2y$10$qVJEpok88l4bsXuMRenDcOhzfZeif48YNapH/bcz.rap7iaATTzdK', NULL, 'blqarJfItSfgeEJWPwrbtL4a5UnVOJQDMvFpwvFLPWWULno9IdPDbpl6LV6v1UYj', 1, '2022-03-03 14:32:26', '2022-03-03 14:46:12'),
(24, 'Smart Wallet', 'info@smartwallet.co.tz', NULL, NULL, NULL, '$2y$10$QeMk0yw99miNENHUNcTeiOJ6axasqAO9qWfLlJzFFzr4UU4sCd/Ta', NULL, 'a0p4YQWP51DZMQn4teF9SrMAba7UwwIk6rDElimAK82QL5F8udCNVmxxZ0EMC86G', 1, '2022-03-07 11:18:26', '2022-03-12 07:12:41');



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;