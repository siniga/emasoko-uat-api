-- -------------------------------------------------------------
-- TablePlus 4.8.0(432)
--
-- https://tableplus.com/
--
-- Database: emakato_db
-- Generation Time: 2024-06-07 13:17:03.6340
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
  `mask` varchar(20) DEFAULT NULL,
  `active` int NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `acquisition`;
CREATE TABLE `acquisition` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(200) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `countries`;
CREATE TABLE `countries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `code` varchar(5) NOT NULL,
  `name` varchar(45) NOT NULL,
  `initial` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `customer_accounts`;
CREATE TABLE `customer_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `customer_id` int NOT NULL,
  `account_type_id` int NOT NULL,
  `account_id` int NOT NULL,
  `account_number` varchar(55) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `account_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `status` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'partial',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_by` int NOT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `customer_partners`;
CREATE TABLE `customer_partners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `partner_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int NOT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `updated_by` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `phone` varchar(15) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `fullname` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `id_type` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT 'NIN',
  `id_number` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `status` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'partial',
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `created_by` int NOT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `added_by` (`created_by`),
  KEY `id_type` (`id_type`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `debit_orders`;
CREATE TABLE `debit_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `partner_id` int DEFAULT NULL,
  `customer_account_id` int NOT NULL,
  `total_amount` float NOT NULL,
  `paid_amount` float NOT NULL DEFAULT '0',
  `outstanding_amount` float NOT NULL DEFAULT '0',
  `debit_type` varchar(20) DEFAULT NULL,
  `installment_amount` float DEFAULT NULL,
  `min_installment_amount` float DEFAULT '0',
  `max_installment_amount` float DEFAULT '0',
  `installment_number` int NOT NULL,
  `periodicity` enum('ANNUALLY','MONTHLY','WEEKLY','DAILY') NOT NULL DEFAULT 'MONTHLY',
  `deduction_end_date` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `evidence_of_aggrement` varchar(255) DEFAULT NULL,
  `status` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'saved',
  `otp_code` varchar(11) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `approved` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'PENDING',
  `approved_by` int DEFAULT NULL,
  `approver_at` datetime DEFAULT NULL,
  `approve_code` varchar(15) DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `document_categories`;
CREATE TABLE `document_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `gender`;
CREATE TABLE `gender` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `id_types`;
CREATE TABLE `id_types` (
  `id` int NOT NULL,
  `name` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `loan_purposes`;
CREATE TABLE `loan_purposes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `marital_status`;
CREATE TABLE `marital_status` (
  `id` int NOT NULL,
  `name` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `occupations`;
CREATE TABLE `occupations` (
  `id` int NOT NULL,
  `name` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `partner_accounts`;
CREATE TABLE `partner_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `account_type_id` int NOT NULL,
  `partner_uuid` text NOT NULL,
  `account_id` int NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(25) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `remarks` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `partner_approvals`;
CREATE TABLE `partner_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int DEFAULT NULL,
  `sequence` int DEFAULT '1',
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `partner_categories`;
CREATE TABLE `partner_categories` (
  `id` int NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `partner_documents`;
CREATE TABLE `partner_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `partner_uuid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `partner_staff`;
CREATE TABLE `partner_staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `partner_uuid` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `NIDA_number` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `phone` varchar(25) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `title` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`partner_id`)
) ENGINE=InnoDB AUTO_INCREMENT=92 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `partner_users`;
CREATE TABLE `partner_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `partner_id` int DEFAULT NULL,
  `partner_uuid` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `authorize` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `partners`;
CREATE TABLE `partners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `partner_category_id` int NOT NULL,
  `business_name` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `year_of_incorporation` year NOT NULL,
  `tin_number` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `number_of_staff` int NOT NULL,
  `region_id` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `email` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `website` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `postal_address` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `physical_address` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `complete` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '0',
  `next_step` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `otp_token` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `region_id` (`region_id`,`partner_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `payment_histories`;
CREATE TABLE `payment_histories` (
  `id` int NOT NULL,
  `debit_order_id` int NOT NULL,
  `expected_amount` float NOT NULL,
  `paid_amount` float NOT NULL,
  `payment_reference` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
  `reference` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `msisdn` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `status` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `transid` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `insight_reference` varchar(64) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `remarks` varchar(128) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `perm_classes`;
CREATE TABLE `perm_classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ordering` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `perm_methods`;
CREATE TABLE `perm_methods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `perm_methods_class_id_foreign` (`class_id`),
  CONSTRAINT `perm_methods_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `perm_classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `perm_roles`;
CREATE TABLE `perm_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint unsigned NOT NULL,
  `classes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `methods` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `perm_roles_role_id_foreign` (`role_id`),
  CONSTRAINT `perm_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `perm_users`;
CREATE TABLE `perm_users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `classes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `methods` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `perm_users_user_id_foreign` (`user_id`),
  CONSTRAINT `perm_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
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
  `name` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `sectors`;
CREATE TABLE `sectors` (
  `id` int NOT NULL,
  `name` varchar(40) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `description` varchar(60) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `sms_logs`;
CREATE TABLE `sms_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `message_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `gateway_id` varchar(100) DEFAULT NULL,
  `gateway_response` json DEFAULT NULL,
  `gateway_code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `gateway_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `sender` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `phone` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `sms_count` int NOT NULL,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT 'PENDING',
  `schedule` int DEFAULT '0',
  `schedule_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `NIDA_number` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verify_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `account_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'BANK', 'bank', '2021-11-02 11:02:01', '2021-11-02 11:02:01'),
(2, 'MNO', 'mno', '2021-11-02 11:02:01', '2021-11-02 11:02:01');

INSERT INTO `accounts` (`id`, `account_type_id`, `name`, `description`, `mask`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, 'NMB Bank', 'NMB Bank', '99999999999', 1, '2022-02-24 15:36:38', '2022-10-31 09:26:30'),
(2, 2, 'Vodacom', 'vodacom', NULL, 1, '2022-02-24 15:36:38', '2022-10-31 09:26:26'),
(3, 1, 'CRDB Bank', 'CRDB Bank', NULL, 1, '2022-03-29 15:58:16', '2023-09-05 04:16:15'),
(5, 2, 'Tigo', 'Tigo Pesa', NULL, 1, '2023-09-05 04:16:39', '2023-09-05 04:16:39');

INSERT INTO `acquisition` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'WEB', 'Acquired from web portal', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(2, 'USSD', 'Acquired from USSD', '2021-10-13 00:00:00', '2021-10-06 00:00:00'),
(3, 'MOBILE APP', 'Acquired from MOBILE APP', '2021-10-13 00:00:00', '2021-10-06 00:00:00');

INSERT INTO `countries` (`id`, `code`, `name`, `initial`, `created_at`, `updated_at`) VALUES
(1, '+255', 'TANZANIA', 'TZ', '2021-11-06 12:12:54', '2022-04-01 17:33:41');

INSERT INTO `customer_accounts` (`id`, `uuid`, `customer_id`, `account_type_id`, `account_id`, `account_number`, `account_name`, `phone`, `status`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'cd4c1df3-5082-3bab-9082-2b66f9c91a67', 12, 2, 1, '4485287390514536', 'Feeney Ltd', '920-308-5497', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(2, 'ea62e269-8ef6-3916-93ac-ab9bba4a4a17', 16, 1, 2, '4539910026995235', 'Stanton, Bashirian and Tillman', '+1-206-594-8507', 'partial', NULL, 1, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(3, '021e71e7-a88b-3f58-a70b-868fd2441593', 11, 2, 2, '6011878765695062', 'Witting, Grimes and Renner', '(435) 350-6669', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(4, 'd30b94b6-c0db-3aaa-8eaf-36021e222408', 20, 1, 1, '4916775518650', 'O\'Reilly PLC', '+18312562907', 'partial', NULL, 29, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(5, '2a0a28ff-4395-3754-9fb4-936756eec47e', 7, 2, 2, '2462485886176437', 'Conroy-Beatty', '508-317-7095', 'partial', NULL, 29, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(6, 'edbafc4c-03ee-3680-b091-9022e959cf32', 22, 2, 1, '376239116211079', 'Bailey-Goldner', '419.826.3428', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(7, '3a42015f-86e8-3824-8081-dfe12aa341b3', 15, 2, 3, '5225689035074275', 'Ratke, Schultz and Witting', '+1.726.803.5115', 'partial', NULL, 1, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(8, '9fa3d653-230a-320d-a2c3-c53bcaab1f3e', 10, 1, 1, '3528391594691081', 'Julias Tonny', '0717000000', 'partial', NULL, 1, NULL, '2023-05-05 10:08:52', '2023-05-08 12:34:39'),
(9, '37ac18f2-86af-34e0-91a6-11fdf9b68e91', 14, 1, 2, '5530161460524588', 'Wintheiser, Halvorson and Friesen', '+1 (917) 822-4640', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(10, '9f615e3f-fb68-32f7-8865-61ff4ae19391', 8, 1, 1, '4929300916731015', 'Goodwin-Maggio', '480-500-3125', 'partial', NULL, 1, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(11, 'bd5208d0-2186-3c08-95a4-d79c827aea36', 18, 1, 2, '2537673099157150', 'Schneider LLC', '+15866970527', 'partial', NULL, 29, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(12, 'eca92cec-eefa-362b-8ece-1821f254e00d', 19, 2, 2, '375397349111622', 'Kovacek-Towne', '1-936-714-1318', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(13, '175ca392-25ed-3c32-b333-a9ba11e94758', 13, 2, 3, '6011529337820528', 'Erdman, Gislason and Hilpert', '+1-657-434-7465', 'partial', NULL, 29, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(14, '87dec92b-a8c3-357f-99e7-4866f5d43263', 23, 2, 3, '4539141028941494', 'Johns-Willms', '828.378.8824', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(15, 'f7ccc951-a84f-3639-a7e9-9a9919200afc', 2, 2, 3, '4539876429368280', 'Denesik Ltd', '312-399-4174', 'partial', NULL, 29, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(16, 'e94cd495-9e7d-340a-87ab-ac5176cb0fa0', 3, 1, 3, '4556672900715513', 'Herman PLC', '1-515-662-3595', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(17, '4662396e-7daf-314e-bbf3-617434814531', 6, 2, 2, '5328272617154934', 'Buckridge-O\'Keefe', '+1-606-783-9786', 'partial', NULL, 1, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(18, 'ff293b7e-3e3f-3181-a06b-c90ea337b286', 1, 2, 1, '6011386844212040', 'Bins, McDermott and Altenwerth', '505.589.6347', 'partial', NULL, 29, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(19, '3e40a91c-24b3-3228-af12-c78a64160163', 9, 1, 1, '3528236125895794', 'Klein Group', '+1-276-921-4679', 'partial', NULL, 30, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(20, '73b7ab0e-bb91-3309-a3a6-e3feb2fc5316', 17, 2, 3, '5532235227796073', 'Kiehn-Cruickshank', '928.802.7591', 'partial', NULL, 29, NULL, '2023-05-05 10:08:52', '2023-05-05 10:08:52'),
(21, '554afdf1-e933-3f45-be04-0c35f5bf7240', 20, 2, 3, '4539712104611662', 'Runolfsson Group', '(716) 459-6755', 'partial', NULL, 29, NULL, '2023-05-05 11:28:38', '2023-05-05 11:28:38'),
(22, '2bd4c7ea-c758-321e-afde-7f52e123c7d0', 9, 2, 2, '4916879275516088', 'Bergnaum Ltd', '1-832-510-2398', 'partial', NULL, 30, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(23, '1cddbbb5-c79e-3679-889a-a7b6cf8d0fe7', 3, 1, 1, '01988366644', 'Renfrid William', '255717000000', 'partial', NULL, 30, 1, '2023-05-05 11:28:39', '2023-09-04 09:21:16'),
(24, 'fbf1445e-f6ff-335d-91a3-a6f9520ea6df', 22, 2, 1, '4532666566769771', 'Oberbrunner-Tremblay', '(765) 380-6835', 'partial', NULL, 30, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(25, '0b879049-caa4-329d-acdc-bb6e9da781ab', 2, 2, 2, '4916160018770757', 'Schaefer-Stark', '+1-830-758-1803', 'partial', NULL, 29, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(26, 'e37f3836-55ab-376e-8e20-f4fb5c20a632', 11, 1, 3, '2221910071403579', 'Fritsch, Mante and Hoeger', '+1-361-762-4097', 'partial', NULL, 29, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(27, '20c08911-0982-3fb4-90f1-3b617dbcbf8b', 10, 2, 1, '374997682403550', 'Collier LLC', '859-674-4774', 'partial', NULL, 29, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(28, '6547d411-fc2c-3813-a0c5-4e376ba82b89', 8, 1, 2, '2652706918329722', 'O\'Conner-Pacocha', '531-762-7233', 'partial', NULL, 29, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(29, 'db336685-4c49-3978-9d7e-4bec6e54abad', 12, 1, 1, '340090344197660', 'Cormier and Sons', '+1-878-399-8507', 'partial', NULL, 29, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(30, 'a0ca72c9-edf7-3856-bb4c-144696c6a590', 18, 2, 2, '4716178927190521', 'Beahan, Kreiger and Stracke', '231-287-4042', 'partial', NULL, 30, NULL, '2023-05-05 11:28:39', '2023-05-05 11:28:39'),
(31, '4f5432ae-b254-4fc9-9699-5d89d8e307b8', 2, 1, 1, '01124789100', 'Renfrid Ngolongolo', '255753437856', 'partial', NULL, 1, NULL, '2023-09-04 10:05:45', '2023-09-11 09:40:01'),
(32, 'eafe613f-6acd-4719-9b12-77553fdf6f90', 2, 1, 3, '01124789100', 'Renfrid Ngolongolo', '255753437856', 'partial', NULL, 30, NULL, '2023-09-05 04:17:07', '2023-09-05 04:42:32'),
(33, '18996fe6-4ebb-4bdb-ab70-9845d5544582', 1, 1, 1, '01124789108', 'Willabelle William', '255717705746', 'partial', NULL, 30, NULL, '2024-01-08 15:41:35', '2024-01-09 11:56:35'),
(34, '58f94a3c-b915-48f6-b708-11dfd007431f', 1, 2, 5, NULL, NULL, '255717705746', 'partial', NULL, 30, NULL, '2024-01-09 06:28:53', '2024-01-09 06:28:53'),
(35, '43adbc7a-0992-4913-aba5-7556a1db2409', 24, 1, 1, '2003004004', 'Tonny Missokia', '255684512512', 'partial', NULL, 30, NULL, '2024-01-09 12:05:02', '2024-01-10 10:31:30'),
(36, 'b5ec4d9e-75af-40c2-a53e-b9fe1495dea7', 2, 2, 2, NULL, NULL, '255753437856', 'partial', NULL, 30, NULL, '2024-01-20 12:56:57', '2024-01-20 12:56:57'),
(37, 'cfec9c2e-242d-43c6-8d12-1b16aa75472e', 25, 1, 1, '122342343443543', 'Enna Kiondo', '255765453536', 'partial', NULL, 30, NULL, '2024-02-06 07:17:27', '2024-02-06 07:17:27'),
(38, 'e38b4819-55f3-4044-926d-565969f3d0eb', 26, 1, 1, '5003004004', 'Husna Tajiri', '2557656546464', 'partial', NULL, 30, NULL, '2024-02-28 09:11:24', '2024-02-28 09:11:24'),
(39, '0d02025e-d2df-44ab-ba6b-dc833ab4e06b', 27, 1, 1, '22345678945', 'MIRAN WASEDA', '255658811577', 'partial', NULL, 30, NULL, '2024-03-21 12:03:26', '2024-03-21 12:31:35'),
(40, '08363a4f-6608-4a22-98ff-4c56f7749313', 28, 1, 1, '22345678896', 'MIKAYLA MAULID', '255744811766', 'partial', NULL, 30, NULL, '2024-03-27 06:32:13', '2024-03-27 06:32:13'),
(41, 'e78753e9-9de5-47e4-a2a9-626922a5d367', 28, 2, 2, NULL, NULL, '255744811766', 'partial', NULL, 30, NULL, '2024-03-27 06:51:46', '2024-03-27 06:51:46');

INSERT INTO `customer_partners` (`id`, `customer_id`, `partner_id`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, 1, 2, '2023-05-04 06:46:11', 29, '2023-05-04 06:46:11', NULL),
(2, 2, 2, '2023-05-04 14:28:51', 1, '2023-09-04 10:05:45', NULL),
(3, 2, 5, '2023-09-05 04:18:55', 1, '2023-09-11 09:41:13', NULL),
(4, 2, 50, '2023-09-05 04:48:00', 30, '2023-09-05 04:48:00', NULL),
(5, 1, 50, '2024-01-08 15:48:45', 30, '2024-01-08 15:48:45', NULL),
(6, 24, 50, '2024-01-09 12:07:16', 30, '2024-01-09 12:07:16', NULL),
(7, 25, 50, '2024-02-06 07:20:28', 30, '2024-02-06 07:20:28', NULL),
(8, 26, 50, '2024-02-28 09:13:09', 30, '2024-02-28 09:13:09', NULL),
(9, 27, 50, '2024-03-21 12:19:24', 30, '2024-03-21 12:19:24', NULL),
(10, 28, 50, '2024-03-27 06:39:50', 30, '2024-03-27 06:39:50', NULL);

INSERT INTO `customers` (`id`, `phone`, `fullname`, `dob`, `id_type`, `id_number`, `email`, `address`, `status`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, '255717705746', 'WILLABELLE WILLIAM', NULL, 'NIN', NULL, NULL, NULL, 'partial', NULL, 30, NULL, '2023-02-27 09:49:46', '2024-01-09 11:56:35'),
(2, '255753437856', '', NULL, 'NIN', '77997757-75775-77577-57', NULL, NULL, 'partial', NULL, 30, 29, '2023-05-04 01:00:59', '2024-01-20 12:56:57'),
(3, '255717000000', 'Renfrid William', NULL, 'NIN', NULL, NULL, NULL, 'partial', NULL, 29, 1, '2023-05-04 01:47:00', '2023-09-04 09:21:16'),
(4, '+1-256-364-7993', 'Noble Ondricka', '1994-06-07', 'NIN', '4716464632857', 'wanda57@hauck.com', '5688 Wintheiser Locks Suite 058\nHuelsside, NJ 16360', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(5, '1-801-205-6883', 'Edythe Parisian', '1973-02-19', 'NIN', '4955779975471463', 'dlynch@gmail.com', '3744 D\'angelo Isle Suite 872\nWest Roy, NJ 10785-9540', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(6, '1-248-820-9263', 'Toby Bechtelar', '1992-09-15', 'NIN', '348530029344378', 'darby.ratke@hotmail.com', '6596 Desiree Vista Suite 136\nNew Savannahborough, WY 15167-1551', 'partial', NULL, 29, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(7, '843-964-1286', 'Rose West', '1985-12-02', 'NIN', '5541282852754224', 'leuschke.samantha@gislason.info', '7565 Tomasa Place Suite 521\nPaigeside, NY 58168-8614', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(8, '(828) 373-8268', 'Tianna Bartell', '1971-09-11', 'NIN', '4539718966162371', 'grady.rhianna@gmail.com', '44286 Damien Walk Suite 829\nKaydenport, LA 41208-5760', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(9, '(628) 272-6417', 'Lloyd McGlynn PhD', '1999-01-07', 'NIN', '4539410611577977', 'nfarrell@fritsch.org', '692 Hudson Stream\nEast Rex, AL 34839-4741', 'partial', NULL, 30, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(10, '248-390-4829', 'Domenick Predovic', '1985-08-25', 'NIN', '3589640820562011', 'phegmann@yahoo.com', '5840 Kaya Lock\nLabadieborough, AK 28798', 'partial', NULL, 29, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(11, '534-408-5292', 'Dana Heaney II', '1999-10-07', 'NIN', '2343985747261141', 'fritz42@yahoo.com', '8780 Jerel Summit\nChristinehaven, AZ 55849-6427', 'partial', NULL, 30, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(12, '+1-980-450-5880', 'Armando Will', '1974-06-17', 'NIN', '4024007143883873', 'nat01@gmail.com', '947 Greenfelder River Apt. 456\nEast Dina, MT 77280-9877', 'partial', NULL, 29, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(13, '+1.740.527.5791', 'Allan Rodriguez MD', '1996-02-07', 'NIN', '4485618592541036', 'horn@gleason.com', '718 Walker Lodge\nRaleighbury, CA 47469', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(14, '+1-463-231-7232', 'Lucile Prohaska', '1971-09-11', 'NIN', '4716184749073036', 'tessie62@pollich.com', '529 Leffler River\nDestinifurt, WV 95726', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(15, '1-952-370-5783', 'Mrs. Sophie Predovic', '1976-05-30', 'NIN', '4556084569326972', 'wolf.clara@kub.com', '148 Spinka Orchard\nRutherfordville, NV 88656', 'partial', NULL, 30, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(16, '917-559-9569', 'Lincoln Schoen II', '1996-06-24', 'NIN', '346500071221628', 'abelardo.welch@yahoo.com', '73998 Norberto Light Apt. 423\nLake Domenicshire, KY 42385-9683', 'partial', NULL, 29, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(17, '(726) 687-1631', 'Mackenzie Reichert', '1976-04-08', 'NIN', '4539534778069880', 'brooks75@hotmail.com', '592 Predovic Knoll Suite 321\nSchuppeside, DE 71874-1412', 'partial', NULL, 30, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(18, '+1.848.481.7228', 'Titus Quitzon', '1980-04-24', 'NIN', '4929748349901748', 'ckub@gmail.com', '2898 Bernadine Crossing\nSouth Deshaunmouth, MN 11055-1099', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(19, '360-949-4850', 'Mrs. Idell Sanford V', '1989-04-11', 'NIN', '4916712353401', 'ferne.damore@turcotte.info', '4927 Mohr Way Suite 477\nPort Itzelbury, WY 10087-0281', 'partial', NULL, 29, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(20, '458-283-4160', 'Maribel Farrell', '1994-04-02', 'NIN', '4556475890760717', 'reed55@ohara.com', '27277 Melody Island Suite 911\nHarberside, WV 58510', 'partial', NULL, 29, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(21, '1-870-305-8779', 'Maddison Runte', '1996-04-07', 'NIN', '5415367511318918', 'noemi.schroeder@stanton.org', '522 Elmira Inlet Apt. 120\nGinoland, GA 87338', 'partial', NULL, 30, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(22, '+1.425.586.4850', 'Pearl Hirthe', '1975-04-11', 'NIN', '4485044661612147', 'cortney.feil@gmail.com', '424 Feeney Unions\nArmstrongbury, FL 83451-0882', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(23, '925.786.0870', 'Brionna Spencer', '1977-11-07', 'NIN', '3528815510648221', 'bashirian.eleanore@yahoo.com', '807 Shanahan Lake\nSouth Aishaview, SC 45409', 'partial', NULL, 1, NULL, '2023-05-05 10:01:09', '2023-05-05 10:01:09'),
(24, '255684512512', 'TONNY MISSOKIA', NULL, 'NIN', '12122123-23123-23123-22', NULL, NULL, 'partial', NULL, 30, NULL, '2024-01-09 12:05:02', '2024-01-10 10:31:30'),
(25, '255765453536', 'ENNA KIONDO', NULL, 'NIN', '12345678-98765-43245-67', NULL, NULL, 'partial', NULL, 30, NULL, '2024-02-06 07:17:27', '2024-02-06 07:17:27'),
(26, '2557656546464', 'HUSNA TAJIRI', NULL, 'NIN', '12345556-66666-66666-66', NULL, NULL, 'partial', NULL, 30, NULL, '2024-02-28 09:11:24', '2024-02-28 09:11:24'),
(27, '255658811577', 'MIRAN WASEDA', NULL, 'NIN', '19780412-11125-87931-23', 'miranwaseda9@gmail.com', 'DAR ES SALAAM', 'partial', NULL, 30, NULL, '2024-03-21 12:03:26', '2024-03-21 12:10:26'),
(28, '255744811766', '', NULL, 'NIN', '19860903-11107-89123-45', 'MikaylaM@82023', 'DAR ES SALAAM', 'partial', NULL, 30, NULL, '2024-03-27 06:32:13', '2024-03-27 06:51:46');

INSERT INTO `debit_orders` (`id`, `uuid`, `partner_id`, `customer_account_id`, `total_amount`, `paid_amount`, `outstanding_amount`, `debit_type`, `installment_amount`, `min_installment_amount`, `max_installment_amount`, `installment_number`, `periodicity`, `deduction_end_date`, `start_date`, `end_date`, `evidence_of_aggrement`, `status`, `otp_code`, `approved`, `approved_by`, `approver_at`, `approve_code`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
(1, '36a3b8e6-3e98-4579-8262-bd05d6525ad90', 50, 1, 1000000, 0, 0, 'FIXED', 100000, 0, 0, 10, 'MONTHLY', 'totalLoanCovered', '2023-03-01', '2025-03-01', '20230227081845-Companies.pdf', 'submitted', NULL, 'REJECTED', NULL, NULL, NULL, NULL, NULL, '2022-06-22 10:02:32', '2023-09-05 04:33:51'),
(2, '36a3b8e6-3e98-4579-8262-bd05d6525addkl', 50, 2, 10000000, 0, 0, 'FIXED', 100000, 0, 0, 12, 'MONTHLY', 'totalLoanCovered', '2023-05-31', '2025-05-31', '20230227081845-Companies.pdf', 'submitted', NULL, 'REJECTED', NULL, NULL, NULL, NULL, NULL, '2022-06-22 10:02:32', '2023-09-05 04:33:51'),
(4, '36a3b8e6-3e98-4579-8262-bd05d6525adkd', 50, 4, 10000000, 0, 0, 'FIXED', 100000, 0, 0, 10, 'MONTHLY', 'totalLoanCovered', '2023-05-31', '2025-05-31', NULL, 'submitted', NULL, 'REJECTED', NULL, NULL, NULL, NULL, NULL, '2022-06-22 10:02:32', '2023-09-05 04:33:51'),
(5, '36a3b8e6-3e98-4579-8262-bd05d6525aerre', 50, 25, 4169020, 0, 0, 'FIXED', 28273, 43974, 74494, 23, 'WEEKLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2022-06-22 10:02:32', '2023-09-05 04:33:51'),
(6, '36a3b8e6-3e98-4579-8262-bd05d6525zzz', 50, 11, 8505850, 0, 0, 'DYNAMIC', 82997, 92697, 75177, 14, 'WEEKLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2022-08-22 10:02:32', '2023-09-05 04:33:51'),
(7, '36a3b8e6-3e98-4579-8262-bd05d6525ddd', 50, 22, 7337940, 0, 0, 'DYNAMIC', 87944, 60877, 19280, 13, 'MONTHLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2022-09-22 10:02:32', '2023-09-05 04:33:51'),
(8, '36a3b8e6-3e98-4579-8262-bd05d6525adjd', 50, 5, 893144, 0, 0, 'FIXED', 28215, 54554, 67110, 23, 'MONTHLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2022-05-10 11:53:17', '2023-09-05 04:33:51'),
(9, '36a3b8e6-3e98-4579-8262-bd05d6525adk', 50, 4, 8977670, 0, 0, 'FIXED', 46253, 38683, 10647, 15, 'MONTHLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2022-05-10 00:00:00', '2023-09-05 04:33:51'),
(10, '36a3b8e6-3e98-4579-8262-bd05d6525atte', 50, 19, 6505590, 0, 0, 'DYNAMIC', 57029, 31971, 48048, 22, 'MONTHLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-01-05 11:53:17', '2023-09-05 04:33:51'),
(11, '36a3b8e6-3e98-4579-8262-bd05d6525ayu', 50, 3, 2293280, 0, 0, 'FIXED', 16767, 96551, 37879, 24, 'MONTHLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2023-01-05 11:53:17', '2023-09-05 04:33:51'),
(12, '36a3b8e6-3e98-4579-8262-bd05d6525dk', 50, 14, 9799510, 0, 0, 'FIXED', 24626, 69003, 49726, 12, 'WEEKLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-01-05 11:53:17', '2023-09-05 04:33:51'),
(13, '36a3b8e6-3e98-4579-8262-bd05d6525uie', 50, 13, 5996100, 0, 0, 'FIXED', 35196, 29731, 22275, 24, 'WEEKLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-01-05 11:53:17', '2023-09-05 04:33:51'),
(14, '36a3b8e6-3e98-4579-8262-bd05d6525ai8', 50, 30, 2424730, 0, 0, 'FIXED', 23266, 22432, 15003, 13, 'WEEKLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-02-05 11:53:17', '2023-09-05 04:33:51'),
(15, '36a3b8e6-3e98-4579-8262-bd05d6525a56', 50, 29, 3794170, 0, 0, 'FIXED', 59942, 57149, 17448, 13, 'MONTHLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-02-05 11:53:17', '2023-09-05 04:33:51'),
(16, '36a3b8e6-3e98-4579-8262-bd05d6525adj', 50, 28, 6212440, 0, 0, 'FIXED', 58022, 16891, 45564, 23, 'WEEKLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-03-05 11:53:17', '2023-09-05 04:33:51'),
(17, '36a3b8e6-3e98-4579-8262-bd05d6525adh', 50, 10, 7157240, 0, 0, 'FIXED', 55697, 22022, 79967, 21, 'MONTHLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-03-05 11:53:17', '2023-09-05 04:33:51'),
(18, '36a3b8e6-3e98-4579-8262-bd05d6525a787', 50, 17, 3978000, 0, 0, 'DYNAMIC', 57965, 32184, 50369, 22, 'MONTHLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2023-03-05 11:53:17', '2023-09-05 04:33:51'),
(19, '36a3b8e6-3e98-4579-8262-bd05d6525arew', 50, 26, 2593000, 0, 0, 'DYNAMIC', 43214, 65182, 70507, 12, 'WEEKLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-04-05 11:53:17', '2023-09-05 04:33:51'),
(20, '36a3b8e6-3e98-4579-8262-bd05d6525dhfd', 50, 1, 7328060, 0, 0, 'DYNAMIC', 41952, 20119, 66798, 23, 'MONTHLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-04-05 11:53:17', '2023-09-05 04:33:51'),
(21, '36a3b8e6-3e98-4579-8262-bd05d6525fhhhs', 50, 20, 9716990, 0, 0, 'FIXED', 65376, 19116, 73780, 11, 'WEEKLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2023-04-05 11:53:17', '2023-09-05 04:33:51'),
(22, '36a3b8e6-3e98-4579-8262-bd05d6525ardd', 50, 24, 6143830, 0, 0, 'FIXED', 59711, 46659, 85375, 10, 'MONTHLY', 'totalLoanCovered', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-05-05 11:53:17', '2023-09-05 04:33:51'),
(23, '36a3b8e6-3e98-4579-8262-bd05d6525a884', 50, 8, 8847560, 0, 0, 'DYNAMIC', 64540, 58433, 34054, 18, 'MONTHLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-05-05 11:53:17', '2023-09-05 04:33:51'),
(24, '36a3b8e6-3e98-4579-8262-bd05d6525a90', 50, 23, 1262340, 0, 0, 'FIXED', 74872, 46071, 21511, 10, 'MONTHLY', 'specificDate', '2023-05-05', '2025-05-05', NULL, 'submitted', NULL, 'APPROVED', NULL, NULL, NULL, NULL, NULL, '2023-05-05 11:53:17', '2023-09-05 04:33:51'),
(28, '4fab7bff-c05e-42b0-963c-17af66219e04', 50, 32, 10000000, 0, 0, 'FIXED', 100000, 0, 0, 15, 'MONTHLY', 'totalLoanCovered', '2023-09-08', '2025-09-08', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2023-09-05 04:48:00', '2023-09-05 04:48:03'),
(29, '76ff00f1-e6fc-40d0-a2bd-dde882a05f7b', 5, 31, 10000000, 0, 0, 'FIXED', 100000, 0, 0, 12, 'MONTHLY', 'totalLoanCovered', '2023-09-14', '2025-09-14', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2023-09-11 09:41:13', '2023-09-11 09:41:21'),
(30, '27f551e2-3230-4fd5-b0d3-58b2008b8371', 50, 33, 1000000, 0, 0, 'FIXED', 100000, 0, 0, 10, 'MONTHLY', 'totalLoanCovered', '2024-02-01', '2026-02-01', 'emakato logo001.pdf', 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-01-08 15:48:45', '2024-01-08 15:49:27'),
(31, '4ed527ac-ead1-40a1-b9c2-1ca6d5378b8d', 50, 34, 10000000, 0, 0, 'FIXED', 100000, 0, 0, 40, 'MONTHLY', 'totalLoanCovered', '2024-01-10', '2026-01-10', 'emakato logo001.pdf', 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-01-09 06:29:35', '2024-01-09 06:29:41'),
(32, 'caf67760-d33c-4e54-803b-6481db381415', 50, 35, 2000000, 0, 0, 'DYNAMIC', 100000, 100000, 50000, 20, 'MONTHLY', 'totalLoanCovered', '2024-01-25', '2026-01-25', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-01-09 12:07:16', '2024-01-10 10:34:20'),
(33, '09cf5298-86a5-40dc-8999-cfaa470b5734', 50, 36, 1000000, 0, 0, 'DYNAMIC', 100000, 50000, 10000, 2, 'MONTHLY', 'totalLoanCovered', '2024-01-31', '2026-01-31', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-01-20 12:59:41', '2024-01-20 12:59:52'),
(34, '26e287c0-ba96-4e7c-96c9-ebb1786a8335', 50, 37, 750000, 0, 0, 'DYNAMIC', 0, 300000, 100000, 5, 'MONTHLY', 'totalLoanCovered', '2024-02-21', '2026-02-21', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-02-06 07:20:28', '2024-02-06 07:20:42'),
(35, '417c2800-8c76-43e1-9775-19f9c1206a1d', 50, 38, 1000000, 0, 0, 'DYNAMIC', 50000, 100000, 10000, 100, 'MONTHLY', 'totalLoanCovered', '2024-03-14', '2026-03-14', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-02-28 09:13:09', '2024-02-28 09:13:18'),
(36, 'ccafdd21-2d43-484f-8a28-a55d60940465', 50, 39, 600000, 0, 0, 'FIXED', 100000, 0, 0, 6, 'MONTHLY', 'totalLoanCovered', '2024-04-21', '2026-04-21', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-03-21 12:19:24', '2024-03-21 12:31:59'),
(37, '7c4b293d-f4f4-4578-abba-96a8aa35eb23', 50, 40, 500000, 0, 0, 'DYNAMIC', 100000, 100000, 75000, 7, 'MONTHLY', 'totalLoanCovered', '2024-04-27', '2026-04-27', NULL, 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-03-27 06:39:50', '2024-03-27 06:40:17'),
(38, 'ff69636b-d7de-480e-bdbe-72e1fb6761c3', 50, 41, 400000, 0, 0, 'FIXED', 50000, 0, 0, 8, 'MONTHLY', 'totalLoanCovered', '2024-04-27', '2026-04-27', 'Leave Form.pdf', 'submitted', NULL, 'PENDING', NULL, NULL, NULL, NULL, NULL, '2024-03-27 06:54:02', '2024-03-27 06:55:31');

INSERT INTO `document_categories` (`id`, `name`, `key`, `required`) VALUES
(1, 'Business Licence', 'Business_Licence', 1),
(2, 'Certificate of incorporation', 'Certificate_of_incorporation', 1),
(3, 'TIN Certificate', 'TIN_Certificate', 1),
(4, 'BRELA report for shareholders', 'BRELA_report_for_shareholders', 0),
(5, 'Audited financial statement', 'Audited_financial_statement', 0),
(6, 'Introduction letter introducing director as a custodian of the account.', 'Introduction_letter', 0),
(7, 'Annual return', 'Annual_return', 0);

INSERT INTO `gender` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Male', '2021-10-14 00:00:00', '2021-10-26 01:12:57'),
(2, 'Female', '2021-10-14 00:00:00', '2021-10-26 01:12:57');

INSERT INTO `id_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'NIDA', 'National Identity Card Number', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(2, 'DRIVING LICENCE', 'Driving licence card', '2021-10-14 00:00:00', '2021-10-14 00:00:00');

INSERT INTO `jobs` (`id`, `queue`, `payload`, `attempts`, `reserved_at`, `available_at`, `created_at`) VALUES
(7, 'default', '{\"uuid\":\"ca54cf51-c85b-4d69-b231-32b66316267a\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"C1Z2V5P9H8T\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668465910, 1668465910),
(8, 'default', '{\"uuid\":\"fcd7df01-a106-4dff-8b96-e4b85b145bb7\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"AF7WYMMFYUT\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668465919, 1668465919),
(9, 'default', '{\"uuid\":\"79fca2e6-caeb-43c1-9813-4d653744625e\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"CRXQ87PM9EH\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668465924, 1668465924),
(10, 'default', '{\"uuid\":\"9aeb9b81-2ea2-4ceb-b8dd-47cb17cbb7d2\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"7QADTT96N34\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668465999, 1668465999),
(11, 'default', '{\"uuid\":\"443ac643-9310-473d-bda1-b1031d182664\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"UJXRNYAXDY1\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668466021, 1668466021),
(12, 'default', '{\"uuid\":\"5ad0088e-7056-4bbb-95c3-50df67398aa8\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"P4RS7LWTNR1\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668466059, 1668466059),
(13, 'default', '{\"uuid\":\"978d19a7-d0df-4b88-8de8-79b9a0575e57\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"UCD783H2CMP\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668468177, 1668468177),
(14, 'default', '{\"uuid\":\"f7c7208f-717a-4f40-847e-b03bbe52cc04\",\"displayName\":\"App\\\\Jobs\\\\SendEmailJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendEmailJob\",\"command\":\"O:21:\\\"App\\\\Jobs\\\\SendEmailJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:5:\\\"email\\\";s:25:\\\"renfrid@smartwallet.co.tz\\\";s:4:\\\"name\\\";s:11:\\\"SmartWallet\\\";s:7:\\\"subject\\\";s:27:\\\"Company Assessment Approval\\\";s:4:\\\"body\\\";s:177:\\\"Company registration has been approved by eMakato.<br\\/>You can start using your eMakato account now. Login to your account by <a href=http:\\/\\/127.0.0.1:8000\\/login>click here.<\\/a>\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1677488576, 1677488576),
(15, 'default', '{\"uuid\":\"422cfa23-d2cf-4c08-9bf9-8c18e403f64f\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"CEZUP37CDZZ\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1677492173, 1677492173),
(16, 'default', '{\"uuid\":\"0f5156cf-68fb-450e-85c3-67663f8cfdc5\",\"displayName\":\"App\\\\Jobs\\\\SendEmailJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendEmailJob\",\"command\":\"O:21:\\\"App\\\\Jobs\\\\SendEmailJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:5:{s:10:\\\"from_email\\\";s:28:\\\"renfrid.ngolongolo@gmail.com\\\";s:8:\\\"to_email\\\";s:28:\\\"renfrid.william@solocodes.io\\\";s:4:\\\"name\\\";s:15:\\\"Renfrid William\\\";s:7:\\\"subject\\\";s:23:\\\"Request for Information\\\";s:4:\\\"body\\\";s:117:\\\"<p>Phone : \'0753437856\'<\\/p><p>Inquiries : \'Company Registration\'<\\/p><p>Message : \'I need to register new company\'<\\/p>\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1681895492, 1681895492),
(17, 'default', '{\"uuid\":\"e130841c-8030-4aa7-ac43-2ef84d065f0c\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"7R6X81TL9UR\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1693887543, 1693887543),
(18, 'default', '{\"uuid\":\"70581d66-bae2-4697-90b8-e3df5da4a3af\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"DAX8ZKX4K2M\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1693888606, 1693888606),
(19, 'default', '{\"uuid\":\"98871e17-9131-4397-aaa8-f714bf7e014f\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"N3WGGN5GHDQ\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1693889283, 1693889283),
(20, 'default', '{\"uuid\":\"4b88c484-b857-4c41-8ded-dcfc9c39aa4a\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"5NUDZ4C37D1\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1694425281, 1694425281),
(21, 'default', '{\"uuid\":\"c6df364e-c090-4474-99f3-73c14833d9f5\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"3W8FQU2MSPU\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1704728967, 1704728967),
(22, 'default', '{\"uuid\":\"b2046695-4c4b-4e06-bd28-ff72b6cf89a4\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"41A11YA7D59\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1704781781, 1704781781),
(23, 'default', '{\"uuid\":\"401fb754-952c-4357-ad13-2733c6a7edf6\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"NLWNUC95VSL\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1704802048, 1704802048),
(24, 'default', '{\"uuid\":\"3eb4fe8a-88a3-462a-8640-71a5064db38f\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"FB3FH7TQC58\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1705755592, 1705755592),
(25, 'default', '{\"uuid\":\"dffd5671-e3f7-470c-8809-efd4f30d1874\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"8SL54DDWYXX\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1707204042, 1707204042),
(26, 'default', '{\"uuid\":\"34aef487-2746-4c36-8e71-eb017f7f4177\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"F3ZPUPH45DN\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1709111598, 1709111598),
(27, 'default', '{\"uuid\":\"227c9337-b946-4fbd-acf0-242105aba464\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"1RTRVCXSFZL\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1711024076, 1711024076),
(28, 'default', '{\"uuid\":\"a09d9014-bd55-41dc-8528-39768645a66a\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"HDCBMTFXPXG\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1711521617, 1711521617),
(29, 'default', '{\"uuid\":\"690eba99-2f94-4efd-a7ee-6e8beecc22a2\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:2:{s:10:\\\"message_id\\\";s:11:\\\"V4CYSHWH3KA\\\";s:8:\\\"schedule\\\";N;}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1711522531, 1711522531);

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
(5, 5, 1, '193c8978-a412-4915-85a4-b71881fdf83e', 1, 'SmartWallet2', '01188466649', 0, NULL, '2023-05-02 13:51:09', '2023-05-02 13:51:09'),
(7, 13, 1, 'e81051b4-4baa-3750-9c86-21182f08ea29', 3, 'Howe, O\'Hara and Kreiger', '4716606243803', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(8, 26, 1, 'b9fdaa1b-fb1e-31a6-a305-d8ac6c66a772', 2, 'Hahn, Abshire and Stracke', '4929048190666536', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(9, 7, 2, '53ce1822-8d16-391b-aef0-fda21d86f8e2', 3, 'Ritchie PLC', '4716753317664994', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(10, 20, 1, '6ec8849e-db48-345f-99d5-f54da36ef0b8', 2, 'Oberbrunner-O\'Reilly', '4485121309767465', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(11, 27, 2, 'a2cfc1a5-c0d1-37a8-b4ef-367fd74101c5', 3, 'Daugherty, Keebler and Koepp', '4485762932134', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(12, 8, 2, 'd25b94e2-bca8-3cdb-ba61-3e10ce738b64', 2, 'Cummings Ltd', '4539761955909022', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(13, 16, 1, '8d873039-797e-3e1f-b83a-e521f80ff753', 1, 'Schultz Ltd', '3589039248918907', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(14, 21, 1, 'ae9fb3bd-5781-33da-afe6-c1578491f68f', 3, 'Spencer, Stoltenberg and Lind', '4716587241220691', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(15, 10, 1, '81584560-5c18-3f92-90ab-e9537d302f5e', 1, 'Fisher-Pagac', '4024007139757', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(16, 23, 2, '45a10c96-ce45-3e99-930b-bfe6b5f07198', 3, 'Herman-Schroeder', '4026278840070', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(17, 36, 1, '94f2f233-f80c-3fb9-bec8-141c8b2e1e0b', 3, 'Schoen-Sipes', '4916421398887789', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(18, 24, 1, 'aedd7245-09c1-3405-9b04-be2ca30beeda', 3, 'Schuster PLC', '4916633190439763', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(19, 35, 2, '7bed8cb8-7fdf-38a2-ae10-e33ff42a61c5', 2, 'Kunze and Sons', '5205665393814555', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(21, 30, 1, '6f8a068e-e36a-36cd-805e-9f5aac3ed185', 3, 'Bins-Legros', '4539519271521255', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(22, 39, 2, '33869572-8f4d-3331-ad4a-9d71eb565791', 1, 'Ryan Ltd', '347048309281419', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(23, 12, 2, 'a91d4744-1cce-304f-b741-80aadb9c02b0', 3, 'Schneider PLC', '3589304185697785', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(24, 5, 1, '8b6b57ba-3e18-399a-bc1e-439396b79d38', 3, 'Mills, Kuphal and Bahringer', '4024007128926044', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(25, 15, 1, '738f9b89-8676-39ca-812a-ac39a16bae3f', 1, 'Robel-Ferry', '4532555622363572', 0, NULL, '2023-05-05 09:31:06', '2023-05-05 09:31:06'),
(26, 18, 2, 'ae9fb3bd-5781-33da-afe6-c1578491f68f', 3, 'Kris, Padberg and Collins', '6011093250425278', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(27, 38, 2, '6ec8849e-db48-345f-99d5-f54da36ef0b8', 3, 'O\'Kon, Lockman and Ferry', '2720999404373423', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(28, 26, 1, 'decc6efe-5883-3a5f-8b66-e74a48444ebc', 3, 'Abshire Group', '4716302581052591', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(29, 5, 2, '81584560-5c18-3f92-90ab-e9537d302f5e', 1, 'Kuhic PLC', '6011947173634201', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(30, 21, 1, '8d873039-797e-3e1f-b83a-e521f80ff753', 3, 'Ferry-Lubowitz', '4716232334969017', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(31, 19, 1, '738f9b89-8676-39ca-812a-ac39a16bae3f', 3, 'Weimann, Lindgren and Paucek', '5323583603541446', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(32, 13, 2, '3c2172a5-2215-3b44-9e03-c4369b0f81f7', 1, 'Effertz, Roberts and Veum', '4532889716211938', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(34, 15, 2, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 2, 'Harris, Jerde and Williamson', '5272188535503794', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(35, 24, 2, 'c67be886-79b8-3c2b-8ba0-9b569eb331c0', 1, 'O\'Keefe-Wolff', '5204326806783421', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(36, 29, 2, '45a10c96-ce45-3e99-930b-bfe6b5f07198', 2, 'Stroman-Fritsch', '4716277884341745', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(37, 43, 1, 'd25b94e2-bca8-3cdb-ba61-3e10ce738b64', 3, 'Feeney-Koch', '3589515368355665', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(38, 33, 1, '10dbfd81-da81-3e78-9e5c-daee61d70383', 1, 'Mohr, Ritchie and Johns', '4485877286234684', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(39, 23, 1, '0f3ba348-5f1c-315e-a62a-cdb55de3282b', 3, 'O\'Reilly LLC', '4929458395783', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(40, 42, 1, '5ecf2bf2-5f4e-3aaf-9953-d0727a32f972', 3, 'Lakin, Bailey and Williamson', '4024007152274857', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(41, 41, 2, 'aedd7245-09c1-3405-9b04-be2ca30beeda', 1, 'Pfannerstill, Beahan and Muller', '4539639199366', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(43, 40, 2, 'b9fdaa1b-fb1e-31a6-a305-d8ac6c66a772', 1, 'Goldner Ltd', '2587989291955699', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(44, 16, 2, '709710d3-3e60-37dc-9ff1-14ce44b1aeb8', 2, 'Cormier, Kulas and Fadel', '6011418737441194', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(45, 28, 2, '7ac7ba4b-9308-340c-b5ac-f581cc7084a0', 3, 'Altenwerth-Weimann', '3589556312929569', 0, NULL, '2023-05-05 09:39:09', '2023-05-05 09:39:09'),
(46, 46, 1, 'f6fe89c5-068e-41ea-8e57-281e83299544', 1, 'Company A', '01188466654', 0, NULL, '2023-05-05 14:17:25', '2023-05-05 14:17:25'),
(49, 49, 1, '55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', 1, 'Mars Digital', '00023445', 0, NULL, '2023-05-11 13:30:28', '2023-05-11 13:30:28'),
(50, 50, 1, 'e8e16bd6-e989-40cc-9979-4f178f2e7acb', 1, 'Renfrid Ngolongolo', '0816466464', 0, NULL, '2023-08-31 12:33:22', '2023-08-31 12:33:22');

INSERT INTO `partner_approvals` (`id`, `partner_id`, `sequence`, `key`, `label`, `status`, `remarks`) VALUES
(13, 5, NULL, 'Company_Information', 'Company Information', 'approved', NULL),
(14, 5, NULL, 'Company_Documents', 'Company Documents', 'approved', NULL),
(15, 5, NULL, 'Company_Collection_Accounts', 'Company Collection Accounts', 'approved', NULL),
(16, 5, NULL, 'Company_Authorized_Representative', 'Company Authorized Representative', 'approved', NULL),
(17, 46, NULL, 'Company_Information', 'Company Information', 'pending', NULL),
(18, 46, NULL, 'Company_Documents', 'Company Documents', 'pending', NULL),
(19, 46, NULL, 'Company_Collection_Accounts', 'Company Collection Accounts', 'pending', NULL),
(20, 46, NULL, 'Company_Authorized_Representative', 'Company Authorized Representative', 'pending', NULL),
(29, 49, 1, 'Company_Information', 'KYC Information', 'approved', 'Lorem ipsum'),
(30, 49, 2, 'Company_Documents', 'Documents', 'rejected', 'Lorem ipsum 4'),
(31, 49, 3, 'Company_Collection_Accounts', 'Collection Accounts', 'approved', 'safi sana'),
(32, 49, 4, 'Company_Authorized_Representative', 'Authorized Representative', 'rejected', 'Lets go'),
(33, 50, 1, 'Company_Information', 'KYC Information', 'approved', NULL),
(34, 50, 2, 'Company_Documents', 'Documents', 'approved', NULL),
(35, 50, 3, 'Company_Collection_Accounts', 'Collection Accounts', 'approved', NULL),
(36, 50, 4, 'Company_Authorized_Representative', 'Authorized Representative', 'approved', NULL);

INSERT INTO `partner_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Financial Institution', 'Shop owned by Vodacom', '2021-10-25 23:31:18', '2024-01-09 11:55:46'),
(2, 'Trading Business', 'Other Shop which are not owned by MNO', '2021-10-25 23:36:03', '2024-01-09 11:54:53'),
(3, 'Insurance Company', 'Insurance Companies which issues IPF', '2021-10-25 23:37:50', '2024-01-09 11:55:46'),
(4, 'Real Estate Company', 'Real Estate business', '2021-10-25 23:37:50', '2024-01-09 11:55:46'),
(5, 'Subscription Company', 'Subscription companies', '2024-01-09 11:55:47', '2024-01-09 11:55:47');

INSERT INTO `partner_documents` (`id`, `partner_id`, `partner_uuid`, `key`, `name`, `attachment`) VALUES
(13, 5, '193c8978-a412-4915-85a4-b71881fdf83e', 'Business_Licence', 'Business Licence', '20230227081845-Companies.pdf'),
(14, 5, '193c8978-a412-4915-85a4-b71881fdf83e', 'Certificate_of_incorporation', 'Certificate of incorporation', '20230227081845-Companies.pdf'),
(15, 5, '193c8978-a412-4915-85a4-b71881fdf83e', 'TIN_Certificate', 'TIN Certificate', '20230227081845-Companies.pdf'),
(16, 46, 'f6fe89c5-068e-41ea-8e57-281e83299544', 'Business_Licence', 'Business Licence', '20230227081845-Companies.pdf'),
(17, 46, 'f6fe89c5-068e-41ea-8e57-281e83299544', 'Certificate_of_incorporation', 'Certificate of incorporation', '20230227081845-Companies.pdf'),
(18, 46, 'f6fe89c5-068e-41ea-8e57-281e83299544', 'TIN_Certificate', 'TIN Certificate', '20230227081845-Companies.pdf'),
(25, 49, '55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', 'Business_Licence', 'Business Licence', '20230227081845-Companies.pdf'),
(26, 49, '55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', 'Certificate_of_incorporation', 'Certificate of incorporation', '20230227081845-Companies.pdf'),
(27, 49, '55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', 'TIN_Certificate', 'TIN Certificate', '20230227081845-Companies.pdf'),
(28, 50, 'e8e16bd6-e989-40cc-9979-4f178f2e7acb', 'Business_Licence', 'Business Licence', 'Ngao_Ya_Buku.pdf'),
(29, 50, 'e8e16bd6-e989-40cc-9979-4f178f2e7acb', 'Certificate_of_incorporation', 'Certificate of incorporation', 'Ngao_Ya_Buku.pdf'),
(30, 50, 'e8e16bd6-e989-40cc-9979-4f178f2e7acb', 'TIN_Certificate', 'TIN Certificate', 'Ngao_Ya_Buku.pdf');

INSERT INTO `partner_staff` (`id`, `partner_id`, `partner_uuid`, `name`, `NIDA_number`, `email`, `phone`, `title`, `created_at`, `updated_at`) VALUES
(5, 5, '193c8978-a412-4915-85a4-b71881fdf83e', 'Nickas Ngolongolo', '87746655-52772-65536-63', 'renfrid.ngolongolo@gmail.com', '0717705746', 'CEO', '2023-05-02 16:54:14', '2023-05-02 16:54:14'),
(6, 27, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 'Marielle Hessel', '2625268295570281', 'prath@hotmail.com', '831.462.8386', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(7, 25, '33869572-8f4d-3331-ad4a-9d71eb565791', 'Brennon Frami', '2449485826407163', 'general.bahringer@reynolds.com', '+1.959.908.8724', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(8, 13, '8d873039-797e-3e1f-b83a-e521f80ff753', 'Sofia Pfannerstill', '2444451064080968', 'gregg.goyette@weissnat.info', '908.928.1014', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(9, 12, 'a2cfc1a5-c0d1-37a8-b4ef-367fd74101c5', 'Penelope Hessel', '4929557275370368', 'cassin.gretchen@gmail.com', '1-470-505-5995', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(10, 35, '9901fe70-6d22-331f-b142-e97e69f94a43', 'Dr. Ernesto Goodwin', '2221519943958494', 'bode.adolph@auer.com', '763.351.9335', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(11, 26, '709710d3-3e60-37dc-9ff1-14ce44b1aeb8', 'Yesenia Rempel', '4716640438591489', 'kirlin.rocky@larson.com', '+1-585-894-6250', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(12, 20, '17322b39-8b35-46f2-9120-06b52dd4b84a', 'Elise Kessler', '5453183296369767', 'eldridge.rowe@emmerich.info', '1-681-868-2047', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(13, 42, '6f8a068e-e36a-36cd-805e-9f5aac3ed185', 'Roselyn Gislason', '5392293539732822', 'crona.cecelia@trantow.org', '571-695-9398', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(14, 11, 'b9fdaa1b-fb1e-31a6-a305-d8ac6c66a772', 'Prof. Garrick Upton II', '5442714970138674', 'gutmann.lenny@becker.com', '+19415910291', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(15, 40, '8c8cc3b1-5842-39c8-befd-947a15cfcf20', 'Haley Stoltenberg', '4716491506139029', 'herman.tressa@crooks.com', '(562) 346-9301', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(16, 28, 'b5f5a335-fafc-3673-9edd-ed0fbfd0e1f3', 'Daniella Gorczany', '5584483207465098', 'preston51@deckow.com', '954-782-5359', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(17, 16, 'a91d4744-1cce-304f-b741-80aadb9c02b0', 'Elizabeth Abbott', '2370025169993917', 'penelope.bashirian@yahoo.com', '(928) 385-1944', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(18, 14, 'e81051b4-4baa-3750-9c86-21182f08ea29', 'Ronaldo Mraz', '5570279382685021', 'dessie87@hotmail.com', '+14078600291', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(19, 44, '193c8978-a412-4915-85a4-b71881fdf83e', 'Linnie O\'Connell', '2221290828123428', 'willie.rath@gmail.com', '(270) 466-3303', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(20, 6, '69dca6e7-055d-3b5e-a663-e17b18050998', 'Anabelle Wintheiser', '6011363255141141', 'marvin.hayley@hotmail.com', '(669) 382-7379', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(21, 5, 'aedd7245-09c1-3405-9b04-be2ca30beeda', 'Doris Buckridge MD', '2425914014931435', 'abel.hegmann@hegmann.com', '+1.206.502.4292', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(22, 30, '8b6b57ba-3e18-399a-bc1e-439396b79d38', 'Friedrich Stiedemann', '4556469788009634', 'mbrown@hotmail.com', '+1 (458) 452-4630', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(23, 7, '6ec8849e-db48-345f-99d5-f54da36ef0b8', 'Annabelle O\'Hara', '4532428548190', 'dschuster@yahoo.com', '+1-979-776-9477', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(24, 43, 'adb781a4-4d02-3ee6-aeb5-0ea13d7b7ba0', 'Hilma Schiller', '4658791022312509', 'rasheed04@hotmail.com', '+1-539-490-1520', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(25, 9, '94f2f233-f80c-3fb9-bec8-141c8b2e1e0b', 'Tamia Marquardt', '376267125933169', 'cole.torrey@ziemann.biz', '1-248-325-2605', 'CEO', '2023-05-05 12:38:02', '2023-05-05 12:38:02'),
(27, 37, 'ae9fb3bd-5781-33da-afe6-c1578491f68f', 'Garland Halvorson', '6011313004883493', 'loyal19@schultz.org', '+15409792102', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(28, 18, '8dcd45f7-58b4-3887-9ae4-80e5cfaf6039', 'Prof. Zack Lesch', '5214730718959821', 'dare.kiel@ankunding.com', '(678) 642-6251', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(29, 38, '10dbfd81-da81-3e78-9e5c-daee61d70383', 'Dr. Orland Medhurst', '4539772086139231', 'jason.buckridge@greenfelder.com', '(231) 788-1014', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(30, 36, 'd25b94e2-bca8-3cdb-ba61-3e10ce738b64', 'Kaitlyn Dicki', '2563450360939724', 'senger.alec@hotmail.com', '1-678-201-9258', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(31, 17, '267648fc-ab5e-3343-a9f6-8e36e639d304', 'Nils Graham', '4305519403731808', 'earnest99@hand.biz', '+1-325-293-9391', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(32, 22, '3c2172a5-2215-3b44-9e03-c4369b0f81f7', 'Meggie Schneider', '371018646279920', 'douglas.hazle@weber.com', '301-865-9049', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(34, 19, 'c67be886-79b8-3c2b-8ba0-9b569eb331c0', 'Dr. Blair Runolfsdottir PhD', '5149403348387876', 'yolanda.reichert@hotmail.com', '+17656341601', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(35, 32, 'e664522a-1afe-3dde-8b10-61f4da603397', 'Ida Gleason', '2221972005919633', 'nora.hagenes@yahoo.com', '+13238153292', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(36, 15, '738f9b89-8676-39ca-812a-ac39a16bae3f', 'Kale Rath', '6011690740721784', 'vance.ullrich@yundt.com', '+14057720134', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(37, 33, '63ba4a8e-82e2-3c7b-b151-cc5d40fcc998', 'Ms. Virgie Rogahn', '5334755236640913', 'cassandra19@yahoo.com', '1-657-906-4599', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(38, 34, '7ac7ba4b-9308-340c-b5ac-f581cc7084a0', 'Chadd Weber', '4916989580283785', 'egreenholt@treutel.com', '832-936-4771', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(39, 21, '74a74923-6ac0-3d6d-8d22-65bbaf01f777', 'Mrs. Calista Kihn', '5482218770586778', 'smosciski@gmail.com', '769-251-1548', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(40, 24, 'decc6efe-5883-3a5f-8b66-e74a48444ebc', 'Ms. Lilyan Keeling Sr.', '4532923620551', 'breanna45@hotmail.com', '1-323-537-8759', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(41, 8, '53ce1822-8d16-391b-aef0-fda21d86f8e2', 'Mr. Jonatan Hoeger II', '2391549790600430', 'leannon.jerrold@harber.com', '+1.385.994.3445', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(42, 41, '0f3ba348-5f1c-315e-a62a-cdb55de3282b', 'Bradford Bode', '4716267258895202', 'opagac@schaden.com', '1-484-448-4032', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(44, 29, '96652454-e8a1-30d0-a368-81151622f564', 'Layne Welch', '5414020975929107', 'hartmann.jed@lueilwitz.com', '321-505-3227', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(45, 10, '81584560-5c18-3f92-90ab-e9537d302f5e', 'Alexis Schmitt', '343062404197648', 'econnelly@schiller.org', '1-430-429-2484', 'CEO', '2023-05-05 12:38:20', '2023-05-05 12:38:20'),
(46, 16, '17322b39-8b35-46f2-9120-06b52dd4b84a', 'Mr. Collin Hegmann MD', '4916772325963550', 'qstracke@hotmail.com', '(517) 252-6431', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(48, 34, '81584560-5c18-3f92-90ab-e9537d302f5e', 'Darron Conn', '4556856439182812', 'cwalsh@gmail.com', '312.434.5261', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(49, 36, 'decc6efe-5883-3a5f-8b66-e74a48444ebc', 'Ms. Rae Gorczany MD', '4716404559383014', 'anita.wyman@grant.com', '+1-361-916-0502', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(50, 44, '0f3ba348-5f1c-315e-a62a-cdb55de3282b', 'Mercedes Simonis', '6011749860453097', 'georgiana13@beahan.com', '+14846621389', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(51, 27, '94f2f233-f80c-3fb9-bec8-141c8b2e1e0b', 'Mckenzie Quigley', '5235950260276797', 'ygreenholt@gmail.com', '(510) 437-8178', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(52, 8, '6f8a068e-e36a-36cd-805e-9f5aac3ed185', 'Prof. Kayden Gutmann', '3589170757050632', 'czboncak@gmail.com', '(820) 740-8160', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(53, 6, 'aedd7245-09c1-3405-9b04-be2ca30beeda', 'Shaylee Flatley', '2537185695799426', 'mertz.minnie@gmail.com', '+12088341532', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(54, 41, 'e81051b4-4baa-3750-9c86-21182f08ea29', 'Liliana Walsh', '4916611641021', 'vivien73@gmail.com', '209.350.0442', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(55, 26, '267648fc-ab5e-3343-a9f6-8e36e639d304', 'Robert Zieme', '4821441377877159', 'bernita92@hudson.com', '+15614343275', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(57, 21, 'adb781a4-4d02-3ee6-aeb5-0ea13d7b7ba0', 'Ms. Alta Koepp DVM', '4716138494022085', 'lucie.hermiston@gmail.com', '1-707-538-6232', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(59, 38, '3c2172a5-2215-3b44-9e03-c4369b0f81f7', 'Annabel Larkin', '341424513056648', 'jdaniel@crooks.com', '859-513-0452', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(60, 43, '96652454-e8a1-30d0-a368-81151622f564', 'Betty Pfeffer Sr.', '2697051343613516', 'uhagenes@cormier.com', '1-307-400-7874', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(61, 31, '4551e838-c611-48e4-a6ed-f7e9a5ea793d', 'Pearlie Bahringer DVM', '2691366828311949', 'augusta71@hotmail.com', '541-813-2256', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(62, 23, '45a10c96-ce45-3e99-930b-bfe6b5f07198', 'Myrtie Christiansen', '340882648161453', 'wiegand.demond@gorczany.com', '+1.863.344.0004', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(63, 9, '193c8978-a412-4915-85a4-b71881fdf83e', 'Sanford Romaguera', '6011414647794459', 'schumm.francisca@torphy.com', '1-979-688-8360', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(64, 14, 'd25b94e2-bca8-3cdb-ba61-3e10ce738b64', 'Jovan Gutmann', '5360886580481500', 'hunter.collier@gmail.com', '1-774-364-4842', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(65, 40, 'c1b77715-41ec-32bf-bcaa-a19a179650db', 'Jaylen Schmeler', '5338711472222086', 'zbashirian@wiza.com', '+1.860.385.8092', 'CEO', '2023-05-05 12:38:51', '2023-05-05 12:38:51'),
(66, 29, '8dcd45f7-58b4-3887-9ae4-80e5cfaf6039', 'Frieda Parisian MD', '4539490205255982', 'garnet.jast@hackett.com', '+15202605819', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(68, 18, '69dca6e7-055d-3b5e-a663-e17b18050998', 'Oren Smith', '4916260086932', 'holly42@stehr.info', '+1-223-818-1108', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(69, 12, 'c67be886-79b8-3c2b-8ba0-9b569eb331c0', 'Jason Torp', '6011989365621042', 'paula86@heaney.com', '+1 (817) 924-4981', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(70, 19, '8b6b57ba-3e18-399a-bc1e-439396b79d38', 'Prof. Kacey Schultz', '4556071866263735', 'randy.beier@gmail.com', '+1.650.510.3057', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(71, 32, '10dbfd81-da81-3e78-9e5c-daee61d70383', 'Werner Wisozk', '2221303553822526', 'raquel.stoltenberg@gmail.com', '+1 (563) 721-6586', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(72, 39, '32d20437-a104-315e-bddc-3d7d9a921a85', 'Madelynn Farrell', '4916174241511292', 'azboncak@hotmail.com', '314-373-3563', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(73, 5, 'e664522a-1afe-3dde-8b10-61f4da603397', 'Tia Nitzsche PhD', '2384109939596699', 'aniyah10@yahoo.com', '682.543.6780', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(74, 10, '53ce1822-8d16-391b-aef0-fda21d86f8e2', 'Edgar Hackett I', '4024007130677700', 'rodriguez.vallie@oreilly.info', '267.972.3649', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(75, 25, 'a91d4744-1cce-304f-b741-80aadb9c02b0', 'Meggie Pagac', '5349986884655746', 'janie.cummerata@yahoo.com', '(762) 867-2562', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(76, 35, 'a97122a5-2abb-3bba-a4b1-3092121cb2a5', 'Royce Brown V', '2221988798136011', 'pfeil@gmail.com', '551-635-4597', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(77, 30, '8d873039-797e-3e1f-b83a-e521f80ff753', 'Milton Greenholt', '5184418949695655', 'greenholt.aniyah@breitenberg.com', '(270) 867-7131', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(78, 20, 'a2cfc1a5-c0d1-37a8-b4ef-367fd74101c5', 'Maynard Ryan', '6011463884332994', 'zboncak.catherine@gmail.com', '+17068280032', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(79, 22, 'b9fdaa1b-fb1e-31a6-a305-d8ac6c66a772', 'Raphaelle Willms', '4716508576706', 'miller.maybell@gmail.com', '(820) 429-6545', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(80, 17, '63ba4a8e-82e2-3c7b-b151-cc5d40fcc998', 'Erika Rowe', '5451369302446994', 'lysanne45@yahoo.com', '+1-803-573-6693', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(81, 13, '7bed8cb8-7fdf-38a2-ae10-e33ff42a61c5', 'Zachariah Mann', '2535350580389053', 'zschinner@gmail.com', '(952) 601-9219', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(82, 24, '74a74923-6ac0-3d6d-8d22-65bbaf01f777', 'Josh Hudson', '3528838548065101', 'emmett27@smith.com', '820.502.8711', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(83, 11, '709710d3-3e60-37dc-9ff1-14ce44b1aeb8', 'Reta Corwin II', '5413707035321348', 'jolie.pollich@mckenzie.com', '(984) 612-2204', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(84, 37, '8c8cc3b1-5842-39c8-befd-947a15cfcf20', 'Kaden Bartell', '4716047820734315', 'zbrakus@koepp.biz', '(936) 544-4103', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(85, 15, '6ec8849e-db48-345f-99d5-f54da36ef0b8', 'Greg Gibson', '3528634412793724', 'dandre.little@yahoo.com', '+1-272-825-3559', 'CEO', '2023-05-05 12:38:54', '2023-05-05 12:38:54'),
(86, 46, 'f6fe89c5-068e-41ea-8e57-281e83299544', 'Company A', '98747636-63626-25424-42', 'company@gmail.com', '0717705746', 'CEO', '2023-05-05 17:18:02', '2023-05-05 17:18:02'),
(90, 49, '55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', 'Renfrid Ngolongolo', '18773663-66366-36636-66', 'renfridfrancis@gmail.com', '0753898989', 'IT Manager', '2023-05-12 08:34:04', '2023-05-12 08:34:04'),
(91, 50, 'e8e16bd6-e989-40cc-9979-4f178f2e7acb', 'Renfrid', '77487466-46646-64646-64', 'renfridfrancis@gmail.com', '0749989898', 'CEO', '2023-08-31 15:34:07', '2023-08-31 15:34:07');

INSERT INTO `partner_users` (`id`, `user_id`, `partner_id`, `partner_uuid`, `authorize`, `created_at`, `updated_at`) VALUES
(5, 30, 50, '193c8978-a412-4915-85a4-b71881fdf83e', 1, '2023-05-02 13:58:36', '2023-09-05 04:32:54'),
(6, 31, 46, 'f6fe89c5-068e-41ea-8e57-281e83299544', 1, '2023-05-05 14:19:54', '2023-05-05 14:19:54'),
(9, 34, 49, '55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', 1, '2023-05-12 06:58:16', '2023-05-12 06:58:16'),
(10, 35, 5, 'e8e16bd6-e989-40cc-9979-4f178f2e7acb', 1, '2023-08-31 12:34:20', '2023-09-05 04:32:54');

INSERT INTO `partners` (`id`, `uuid`, `partner_category_id`, `business_name`, `year_of_incorporation`, `tin_number`, `number_of_staff`, `region_id`, `phone`, `email`, `website`, `postal_address`, `physical_address`, `complete`, `status`, `next_step`, `otp_token`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(5, '193c8978-a412-4915-85a4-b71881fdf83e', 1, 'Bima Mkononi', '2020', '876-544-444', 10, '2', '+255717705746', 'ngolongolo@bimamkononi.co.tz', NULL, 'P.o box 123', 'Mikocheni', 1, 3, 'companies/documents/193c8978-a412-4915-85a4-b71881fdf83e', NULL, NULL, NULL, '2023-05-02 13:43:06', '2023-09-05 04:18:25'),
(6, 'a97122a5-2abb-3bba-a4b1-3092121cb2a5', 1, 'Daugherty-Rutherford', '2015', 'a05578a4-c5a6-3340-87d8-01f79ce2e0eb', 11, '29', '1-785-713-0670', 'edythe34@kilback.com', 'http://damore.org/numquam-ullam-nihil-quisquam-et-accusamus-consequuntur-rerum-inventore.html', '138 Margret Ramp\nFunkberg, KY 44100', '29738 Rippin Heights Suite 740', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(7, '33869572-8f4d-3331-ad4a-9d71eb565791', 3, 'Dibbert PLC', '2020', '15d0ad98-f873-38cb-966f-09841e404fbc', 20, '23', '1-636-838-2322', 'dach.annie@berge.com', 'http://wiza.com/cumque-aspernatur-tempore-totam-amet-ex-ipsa-commodi-repellendus', '13546 Lyla Pines Suite 708\nSouth Dawson, CA 38954-8699', '311 Kulas Keys', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(8, '738f9b89-8676-39ca-812a-ac39a16bae3f', 2, 'Lueilwitz-Beier', '2015', '6799b53c-938e-3156-af14-b901cb63a2c3', 20, '27', '1-239-971-8657', 'keeling.cara@hane.org', 'https://www.gibson.com/neque-tenetur-dolor-in-voluptate-sit-sapiente-est-nostrum', '8671 Cleve Islands\nBoehmburgh, UT 82950', '5809 Kutch Plaza', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(9, '8b6b57ba-3e18-399a-bc1e-439396b79d38', 2, 'Hodkiewicz Inc', '2014', 'c03c54a5-2ce6-3b52-9721-35f824563519', 11, '13', '678.534.7872', 'celia.ernser@gmail.com', 'http://www.wilkinson.com/voluptatem-eum-qui-odio-debitis-mollitia-totam-rerum', '6622 Piper Ranch\nIlahaven, WI 35191-8715', '88002 Mohr Dale Apt. 077', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(10, 'adb781a4-4d02-3ee6-aeb5-0ea13d7b7ba0', 2, 'Lang-Lubowitz', '2000', 'e689d139-1ee2-326c-8b29-3dee34a2e8b2', 16, '31', '651-278-6322', 'ksmith@dicki.com', 'http://legros.com/quo-perferendis-illum-animi-in-maiores', '499 Dickens Overpass\nLake Randal, NY 74128', '355 Phyllis River', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(11, '74a74923-6ac0-3d6d-8d22-65bbaf01f777', 2, 'Windler-Hirthe', '2016', '9ebd5c9c-5a0a-3029-b146-716b8293834d', 14, '31', '458.736.4474', 'kraig.grant@reichel.com', 'http://www.rohan.com/porro-laborum-nobis-earum-error-est-fugiat', '7153 Stefanie Lodge\nJaredborough, SD 81453-8698', '40324 Casimer Meadows', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(12, 'e664522a-1afe-3dde-8b10-61f4da603397', 2, 'White, Spinka and Homenick', '2012', '3cb5ee53-ee95-3d8b-8123-bdfd50f6cb4f', 12, '26', '864.215.9577', 'hhuels@eichmann.info', 'http://www.koch.biz/quas-quisquam-nemo-tenetur-fuga-praesentium-nemo', '77642 Dickens Motorway\nLake Alice, IN 83733-5797', '1465 Abshire Mountain', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(13, '9901fe70-6d22-331f-b142-e97e69f94a43', 1, 'Borer-Schamberger', '2020', '10d097b5-e678-3c4d-9cc4-2c32565a9862', 12, '27', '848.845.2958', 'terry.watson@stroman.com', 'http://beier.com/dolorem-repudiandae-consequatur-sit-saepe-autem-corrupti', '47152 Considine Path Suite 874\nThomasfurt, GA 13218-1751', '6235 Jerrold Locks', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(14, '6f8a068e-e36a-36cd-805e-9f5aac3ed185', 2, 'Ullrich, Mills and Champlin', '2014', 'e371807c-2959-3914-b692-761b9af90531', 12, '21', '1-502-414-4274', 'christiana.mraz@yahoo.com', 'http://klein.biz/doloremque-tempore-id-quod-accusantium-qui', '1967 Sporer Rest Apt. 168\nPort Rebabury, WI 04538-1956', '9395 Sid Burgs', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(15, 'a91d4744-1cce-304f-b741-80aadb9c02b0', 1, 'Labadie, Corkery and Gibson', '2017', '78562cd0-f667-377a-aea6-00146151aee8', 10, '9', '+1 (564) 238-3875', 'berneice.ryan@boyle.com', 'https://lind.biz/placeat-adipisci-dolor-et-modi-aut-rerum-vero-earum.html', '160 Roberts Loaf\nJavierborough, OK 92790-3929', '334 Afton Branch Apt. 533', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(16, '0f3ba348-5f1c-315e-a62a-cdb55de3282b', 1, 'Bahringer Group', '2014', '2c436191-3a8a-321a-92f6-eab7cb6b439e', 16, '21', '(949) 805-7501', 'sam.johnston@gmail.com', 'http://jerde.com/dolorem-ex-natus-a-quis-nisi', '93141 Presley Mountains\nFrankiemouth, OK 17959', '106 Christian Lakes', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(17, 'e81051b4-4baa-3750-9c86-21182f08ea29', 2, 'Rath, Rolfson and Bergnaum', '2008', 'b155699e-dd6f-377d-b0b3-ea197732c268', 17, '26', '1-585-805-7548', 'hayden40@breitenberg.org', 'http://maggio.biz/', '24282 King Crossroad Suite 858\nNorth Cordellland, AK 36867-4217', '92617 Upton Estates', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(18, 'c1b77715-41ec-32bf-bcaa-a19a179650db', 1, 'Kilback Ltd', '2002', 'e4849b3c-e4ab-3d3b-bd43-a013de68c0ae', 12, '28', '820-532-6434', 'elizabeth.boyer@gmail.com', 'http://www.jacobi.biz/', '929 Ervin Courts\nKassulkehaven, FL 43982', '86249 Becker Shore', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(19, '5ecf2bf2-5f4e-3aaf-9953-d0727a32f972', 2, 'Stiedemann-Koepp', '2000', '35f601cf-3227-37c4-80e7-193148fa5ee1', 16, '3', '+1-463-823-3681', 'urunte@yahoo.com', 'https://www.steuber.com/et-nostrum-ut-vel-odio-amet-voluptas-aperiam', '18604 Deanna Cove Apt. 830\nSouth Jerrodside, AZ 71506-7847', '2497 Blick Route', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(20, '94f2f233-f80c-3fb9-bec8-141c8b2e1e0b', 1, 'Satterfield and Sons', '2012', 'c042f577-cd86-348a-ad33-a6e4079fee87', 16, '12', '(941) 665-4409', 'senger.tyree@rutherford.org', 'http://shields.info/et-et-dolor-temporibus-rerum', '620 Bruen Isle\nCorwinshire, MT 19571-9410', '5830 Weimann Radial Suite 815', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(21, 'decc6efe-5883-3a5f-8b66-e74a48444ebc', 2, 'Jacobs-Satterfield', '2007', '846-903-597', 18, '22', '+255717705746', 'ruthe99@yahoo.com', 'http://www.dooley.org/quibusdam-ea-voluptas-vel-aliquam-accusamus-quos', '456 Martine Hills Suite 112South Nashside, UT 33652-1860', '22025 Bradtke Corner Apt. 894', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 09:40:08'),
(22, 'b5f5a335-fafc-3673-9edd-ed0fbfd0e1f3', 3, 'Quitzon-Emmerich', '2006', '359-055-513', 10, '1', '+255717705746', 'alaina.volkman@haley.biz', 'http://www.howe.com/dolores-aut-quis-quia-tempora.html', '8274 Hand Motorway Apt. 865Port Libbietown, HI 44715-0793', '8123 Nelda Plains', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(23, '63ba4a8e-82e2-3c7b-b151-cc5d40fcc998', 3, 'Schultz-Beer', '2016', '9e79a688-65d3-33f1-94b5-8d050e84934a', 16, '14', '(484) 982-5006', 'heidenreich.joseph@gmail.com', 'http://wilkinson.com/repudiandae-ut-nihil-corporis-itaque-dolor.html', '375 Bergstrom Mission Suite 351\nLamontbury, LA 30402-2313', '172 Heidenreich Mills', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(24, '267648fc-ab5e-3343-a9f6-8e36e639d304', 1, 'West Group', '2004', 'f6b9a715-ef3a-3245-abdf-9fa88b2a7441', 11, '3', '1-380-279-5797', 'zhodkiewicz@schuppe.com', 'https://www.rath.biz/nam-ab-numquam-hic', '1583 Brian Bridge\nNew Naomiside, SD 17802-6614', '983 Medhurst Centers Suite 789', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(25, 'aedd7245-09c1-3405-9b04-be2ca30beeda', 3, 'Rowe Inc', '2002', 'a18c33f7-02bb-38fd-8db6-859c0c643e0f', 13, '8', '607.225.4254', 'joan84@kerluke.biz', 'http://schroeder.com/', '780 Dare Oval\nJakubowskimouth, AK 16374', '94223 Aida Manors Apt. 569', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:26:22', '2023-05-05 12:08:42'),
(26, '10dbfd81-da81-3e78-9e5c-daee61d70383', 1, 'Ryan, Schinner and Carter', '2012', 'c3953898-7c10-3618-ba52-eac9dab0ec8f', 17, '30', '(786) 665-0374', 'gwyman@goodwin.info', 'http://leuschke.org/id-ratione-laudantium-magni-in-maiores-ipsam.html', '8114 Marvin Circle\nSouth Berniece, PA 44709', '5654 Koby Walks Suite 142', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(27, '7bed8cb8-7fdf-38a2-ae10-e33ff42a61c5', 1, 'Pagac-Armstrong', '2009', '28226aa1-00bf-39aa-8d8c-11b7e8e751c1', 11, '29', '701.456.5640', 'linda47@oreilly.com', 'http://www.fahey.net/', '5098 Ayden Views\nSmithamview, WV 82075-9161', '947 Hills Ville', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(28, '6ec8849e-db48-345f-99d5-f54da36ef0b8', 3, 'Langosh-Marks', '2008', 'f622be28-73e5-336a-a079-90c975659ad6', 16, '17', '1-806-647-1794', 'gleichner.frida@yahoo.com', 'http://murazik.com/voluptatum-quo-culpa-explicabo-consectetur-voluptatem.html', '325 Haley Spurs Suite 167\nMacejkovicfurt, AR 74780', '15259 Dach Glen Apt. 596', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(29, '81584560-5c18-3f92-90ab-e9537d302f5e', 1, 'Mueller-Davis', '2000', '39076510-cc0a-3a50-99b0-f6ff7f2442dc', 12, '13', '501-700-9883', 'bgoyette@hotmail.com', 'http://www.leffler.com/velit-dolore-quia-natus-expedita-enim-deleniti.html', '36881 Jaskolski View Apt. 850\nJermeyberg, AK 79613', '7759 Eugenia Canyon Apt. 237', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(30, '8dcd45f7-58b4-3887-9ae4-80e5cfaf6039', 1, 'Torp LLC', '2012', '2e1e60f0-8aa8-377c-a318-518d5a05f968', 11, '26', '919.520.4858', 'hzulauf@hotmail.com', 'http://www.lebsack.info/', '991 D\'Amore Manor Suite 026\nWest George, MS 19987', '562 Maeve Branch Apt. 171', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(31, '709710d3-3e60-37dc-9ff1-14ce44b1aeb8', 3, 'McGlynn, Kerluke and Hartmann', '2006', 'b98958f6-aa83-389e-accb-2eef9f4a8893', 10, '19', '1-540-784-3434', 'audrey66@hotmail.com', 'http://www.green.com/', '55426 Smitham Rest\nHubertberg, NM 55771-1653', '3579 Zulauf Fork Suite 536', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(32, '8d873039-797e-3e1f-b83a-e521f80ff753', 3, 'Green, Wiegand and Nienow', '2001', '04e6b266-1dbe-3d63-8c8a-03398d6913a3', 11, '7', '1-872-848-3649', 'lisa25@yahoo.com', 'http://lehner.com/et-cum-aut-sint-eaque', '88249 Emmerich Run Apt. 225\nSabinastad, GA 15796-9492', '78375 Alexandra Passage', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(33, '69dca6e7-055d-3b5e-a663-e17b18050998', 2, 'Hahn PLC', '2023', '19285abf-a04f-3078-8608-726637c3e84a', 11, '17', '+19184947980', 'jeremie.connelly@bechtelar.com', 'http://www.carter.org/eveniet-nostrum-enim-quae-quo-quasi-natus-illo-quibusdam', '534 Schaefer Mountains\nNew Alenahaven, LA 41815-0398', '6596 Coby Courts', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(34, '3c2172a5-2215-3b44-9e03-c4369b0f81f7', 3, 'Howe, Kohler and Kreiger', '2000', '404b9fb6-3e4e-3ebf-b67e-29c3508762b9', 15, '25', '640-806-6370', 'hkling@gmail.com', 'https://www.legros.com/impedit-consequuntur-dolorem-et-qui-et-excepturi-laboriosam', '6445 Carroll Pass Suite 497\nJanetmouth, MT 30025', '4592 Runolfsson Forges Apt. 336', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(35, 'ae9fb3bd-5781-33da-afe6-c1578491f68f', 1, 'Lindgren PLC', '2000', '4fb73939-03d5-3de0-8158-52f8d1955260', 15, '2', '1-605-814-0399', 'kiarra89@gmail.com', 'https://zulauf.net/iure-iusto-dignissimos-deleniti.html', '610 Johnson Pine\nPort Stephonfurt, LA 15534-6228', '8960 Block Centers', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(36, '7ac7ba4b-9308-340c-b5ac-f581cc7084a0', 3, 'O\'Keefe and Sons', '2003', '0004df40-b3c4-30d9-bf3e-dbfe73c1e5e6', 14, '3', '+1 (717) 417-5787', 'mohr.cassandra@nicolas.com', 'http://www.turner.net/autem-cupiditate-ut-itaque-est-ipsum-voluptatem-in', '724 Bednar Mill Suite 203\nNorth Travon, RI 79374', '70659 Eudora Light', 0, 0, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-06 10:17:14'),
(37, 'b9fdaa1b-fb1e-31a6-a305-d8ac6c66a772', 2, 'Ferry, Walsh and Schoen', '2000', 'd60ef54f-4850-3ebf-923a-283ecf1ff2dd', 17, '13', '+1.531.216.2110', 'shaun.graham@cole.com', 'http://www.toy.net/autem-vel-natus-eum-modi-et.html', '8720 Sydnee Station\nDamienmouth, AZ 30889', '490 Homenick Via', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(38, 'a2cfc1a5-c0d1-37a8-b4ef-367fd74101c5', 3, 'Feeney Group', '2008', '1797bb2a-d9c8-354c-b8db-f8d48b3675ba', 11, '16', '1-919-425-9259', 'davon.toy@yahoo.com', 'http://heathcote.net/', '76642 Walter Island Apt. 586\nDonnashire, OK 24078', '5032 Roberts Station', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(39, 'd25b94e2-bca8-3cdb-ba61-3e10ce738b64', 3, 'Waelchi Group', '2017', '61e4a5af-18a8-3248-a440-5c0a082da5bb', 15, '13', '+1 (757) 283-2820', 'idietrich@mccullough.org', 'http://www.oberbrunner.info/hic-laboriosam-voluptate-numquam-magni-sed', '86370 Jalen Plain\nGulgowskichester, CO 19483-9235', '583 Pacocha Passage', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(40, '8c8cc3b1-5842-39c8-befd-947a15cfcf20', 3, 'Harris Group', '2012', '11cedd13-fb2b-3968-be72-056c2e20d947', 16, '15', '+1 (508) 407-0013', 'margarett.stamm@hotmail.com', 'http://www.wiza.com/', '670 Quitzon Tunnel Suite 563\nKilbackview, DC 76452-0094', '313 Kuvalis Center Suite 940', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(41, 'c67be886-79b8-3c2b-8ba0-9b569eb331c0', 2, 'Muller LLC', '2003', 'a53ab22e-ba44-33bc-afb6-568cde7d4b2d', 14, '16', '1-743-538-6992', 'esmeralda.beatty@yahoo.com', 'http://www.kirlin.net/saepe-temporibus-earum-consequatur-esse-dolore', '243 Legros Unions Suite 048\nHillberg, VA 70538', '736 Bartell Streets Apt. 369', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(42, '96652454-e8a1-30d0-a368-81151622f564', 1, 'Kub PLC', '2018', '905275fa-3bf8-3dc7-aaac-e9e682ef8288', 19, '27', '657.346.4723', 'schmitt.mariane@feest.com', 'http://connelly.com/', '28146 Robel Streets Apt. 385\nNorth Ellie, UT 46547-5606', '175 Bayer Club Suite 034', 1, 3, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-05 12:08:42'),
(43, '32d20437-a104-315e-bddc-3d7d9a921a85', 2, 'Schulist and Sons', '2008', 'ee0381af-149f-364e-9e84-a42db210686c', 19, '7', '1-540-576-1790', 'diego31@oconnell.biz', 'http://www.schinner.com/distinctio-sed-eveniet-amet-et-doloremque-dolore-nisi.html', '1402 Johns Circle\nMathiasborough, NV 11538-1143', '87960 Jaskolski Manors Apt. 816', 0, 0, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-06 10:17:14'),
(44, '45a10c96-ce45-3e99-930b-bfe6b5f07198', 1, 'Padberg, Lynch and Luettgen', '2017', '4339dcfb-226c-31fb-87c2-27f00e68f014', 20, '30', '845-859-0060', 'nmarvin@gmail.com', 'http://brown.com/', '81585 Armstrong Viaduct\nNew Casimerside, WV 72265', '23917 Bethel Lodge Suite 413', 0, 0, NULL, NULL, 1, NULL, '2023-05-05 06:33:18', '2023-05-06 10:17:14'),
(46, 'f6fe89c5-068e-41ea-8e57-281e83299544', 1, 'Company A', '2020', '774-664-552', 10, '2', '+255717705746', 'companyA@gmail.com', NULL, 'P.o box 123', 'Mikocheni', 1, 1, 'company-info-overview/f6fe89c5-068e-41ea-8e57-281e83299544', NULL, NULL, NULL, '2023-05-05 14:12:54', '2023-05-06 07:56:53'),
(47, '53ce1822-8d16-391b-aef0-fda21d86f8e2', 1, 'Emard Inc', '2020', '116-531-231', 16, '18', '+255717705743', 'vinnie.hickle@kub.org', 'http://schaden.biz/veritatis-itaque-unde-odit-tenetur-est-incidunt', '8338 Amina IslandsPort Lethaview, ND 94877-6093', '22035 Camryn Course', 0, 0, 'company-documents/53ce1822-8d16-391b-aef0-fda21d86f8e2', NULL, NULL, NULL, '2023-05-06 08:04:22', '2023-05-06 08:04:22'),
(49, '55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', 1, 'Mars Digital', '2023', '999-030-030', 100, '3', '+255717705647', 'renfrid@gmail.com', NULL, NULL, 'Mikocheni', 1, 2, 'companies/documents/55c49a52-a644-4a9a-9a6d-d5ebb7fe9d3d', NULL, NULL, NULL, '2023-05-10 14:09:55', '2023-05-12 12:41:21'),
(50, 'e8e16bd6-e989-40cc-9979-4f178f2e7acb', 3, 'Bimapap Company Limited', '2017', '890-927-737', 10, '2', '+255717705876', 'info@bimapap.co.tz', NULL, NULL, 'Mikocheni, Dar es Salaam', 1, 2, 'companies/documents/e8e16bd6-e989-40cc-9979-4f178f2e7acb', NULL, NULL, NULL, '2023-08-31 12:32:12', '2023-09-11 09:39:08'),
(51, '660f06b5-bdf6-47db-81a4-9d9ed00cb96e', 3, 'NHC', '2005', '988-833-333', 10, '2', '+255717000000', 'nhc@gmail.com', NULL, NULL, 'Mikocheni, Dar es Salaam', 0, 0, 'companies/accounts/660f06b5-bdf6-47db-81a4-9d9ed00cb96e', NULL, NULL, NULL, '2024-01-08 15:30:36', '2024-01-08 15:33:13');

INSERT INTO `payment_histories` (`id`, `debit_order_id`, `expected_amount`, `paid_amount`, `payment_reference`, `created_at`, `updated_at`) VALUES
(9, 25, 7168, 15000, 'laf89-093240', '2021-10-21 17:49:35', '2021-10-26 01:12:57'),
(10, 25, 6542, 6400, 'laf89-093240y', '2021-10-21 17:51:19', '2021-10-26 01:12:57');

INSERT INTO `payments` (`id`, `amount`, `reference`, `msisdn`, `status`, `transid`, `insight_reference`, `remarks`, `created_at`, `updated_at`) VALUES
(18, 27000, 'LAF-29209', '255744423289', 'SUCCESS', 'LAF-29209', 'LAF-96380', NULL, '2021-11-11 09:19:35', '2021-11-11 09:19:35'),
(21, 190001, 'LAF-45228', '255653190260', 'SUCCESS', 'LAF-45228', 'LAF-63157', NULL, '2021-11-11 10:36:55', '2021-11-11 10:36:55');

INSERT INTO `regions` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'ARUSHA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(2, 'DAR ES SALAAM', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(3, 'DODOMA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(4, 'GEITA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(5, 'IRINGA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(6, 'KAGERA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(7, 'KATAVI', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(8, 'KIGOMA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(9, 'KILIMANJARO', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(10, 'LINDI', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(11, 'MANYARA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(12, 'MARA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(13, 'MBEYA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(14, 'MOROGORO', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(15, 'MTWARA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(16, 'MWANZA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(17, 'NJOMBE', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(18, 'PWANI', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(19, 'RUKWA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(20, 'RUVUMA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(21, 'SHINYANGA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(22, 'SIMIYU', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(23, 'SINGIDA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(24, 'SONGWE', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(25, 'TABORA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(26, 'TANGA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(27, 'KASKAZINI PEMBA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(28, 'KASKAZINI UNGUJA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(29, 'KUSINI PEMBA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(30, 'KUSINI UNGUJA', '2021-05-04 07:19:07', '2021-10-26 04:12:57'),
(31, 'MJINIMAGHARIBI', '2021-05-04 07:19:07', '2021-10-26 04:12:57');

INSERT INTO `role_user` (`id`, `user_id`, `role_id`) VALUES
(30, 1, 1),
(35, 30, 2);

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'company_approver', 'Company Approver'),
(4, 'company_user', 'Company User');

INSERT INTO `sectors` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'GOVERNMENT', 'Government employees', '2021-10-14 16:28:21', '2021-10-26 01:12:57'),
(2, 'PRIVATE', 'SME', '2021-10-14 16:28:21', '2021-11-04 14:12:05');

INSERT INTO `sms_logs` (`id`, `message`, `message_id`, `gateway_id`, `gateway_response`, `gateway_code`, `gateway_message`, `sender`, `phone`, `sms_count`, `status`, `schedule`, `schedule_at`, `created_at`, `created_by`, `updated_at`) VALUES
(22, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'Y29DH1J3E3J', NULL, '{\"code\": 120, \"message\": \"Invalid api_key and/or secret_id\"}', '120', 'Invalid api_key and/or secret_id', 'TAARIFA', '255746898988', 1, 'REJECTED', 0, NULL, '2022-11-14 14:09:14', 29, '2022-11-14 14:09:18'),
(23, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'C1Z2V5P9H8T', NULL, NULL, NULL, NULL, 'TAARIFA', '255717705746', 1, 'PENDING', 0, NULL, '2022-11-14 22:45:10', 29, '2022-11-14 22:45:10'),
(24, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'AF7WYMMFYUT', NULL, NULL, NULL, NULL, 'TAARIFA', '255717705746', 1, 'PENDING', 0, NULL, '2022-11-14 22:45:19', 29, '2022-11-14 22:45:19'),
(25, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'CRXQ87PM9EH', NULL, NULL, NULL, NULL, 'TAARIFA', '255717705746', 1, 'PENDING', 0, NULL, '2022-11-14 22:45:24', 29, '2022-11-14 22:45:24'),
(26, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', '7QADTT96N34', NULL, NULL, NULL, NULL, 'TAARIFA', '255717705746', 1, 'PENDING', 0, NULL, '2022-11-14 22:46:39', 29, '2022-11-14 22:46:39'),
(27, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'UJXRNYAXDY1', NULL, NULL, NULL, NULL, 'TAARIFA', '255717705746', 1, 'PENDING', 0, NULL, '2022-11-14 22:47:01', 29, '2022-11-14 22:47:01'),
(28, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'P4RS7LWTNR1', NULL, NULL, NULL, NULL, 'TAARIFA', '255717705746', 1, 'PENDING', 0, NULL, '2022-11-14 22:47:39', 29, '2022-11-14 22:47:39'),
(29, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'UCD783H2CMP', NULL, NULL, NULL, NULL, 'TAARIFA', '255753437856', 1, 'PENDING', 0, NULL, '2022-11-14 23:22:57', 29, '2022-11-14 23:22:57'),
(30, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'CEZUP37CDZZ', NULL, NULL, NULL, NULL, 'TAARIFA', '255717705746', 1, 'PENDING', 0, NULL, '2023-02-27 10:02:53', 29, '2023-02-27 10:02:53'),
(31, 'Habari Renfrid Ngolongolo, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', '7R6X81TL9UR', NULL, NULL, NULL, NULL, 'LAINA', '255753437856', 1, 'PENDING', 0, NULL, '2023-09-05 04:19:03', 1, '2023-09-05 04:19:03'),
(32, 'Habari Renfrid Ngolongolo, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', 'DAX8ZKX4K2M', NULL, NULL, NULL, NULL, 'LAINA', '255753437856', 1, 'PENDING', 0, NULL, '2023-09-05 04:36:46', 30, '2023-09-05 04:36:46'),
(33, 'Habari Renfrid Ngolongolo, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', 'N3WGGN5GHDQ', NULL, NULL, NULL, NULL, 'LAINA', '255753437856', 1, 'PENDING', 0, NULL, '2023-09-05 04:48:03', 30, '2023-09-05 04:48:03'),
(34, 'Habari Renfrid Ngolongolo, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', '5NUDZ4C37D1', NULL, NULL, NULL, NULL, 'LAINA', '255753437856', 1, 'PENDING', 0, NULL, '2023-09-11 09:41:21', 1, '2023-09-11 09:41:21'),
(35, 'Habari Renfrid Ngolongolo, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', '3W8FQU2MSPU', NULL, NULL, NULL, NULL, 'LAINA', '255717705746', 1, 'PENDING', 0, NULL, '2024-01-08 15:49:27', 30, '2024-01-08 15:49:27'),
(36, 'Habari , eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', '41A11YA7D59', NULL, NULL, NULL, NULL, 'LAINA', '255717705746', 1, 'PENDING', 0, NULL, '2024-01-09 06:29:41', 30, '2024-01-09 06:29:41'),
(37, 'Habari Tonny Missokia, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', 'NLWNUC95VSL', NULL, NULL, NULL, NULL, 'LAINA', '255684512512', 1, 'PENDING', 0, NULL, '2024-01-09 12:07:28', 30, '2024-01-09 12:07:28'),
(38, 'Habari , eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', 'FB3FH7TQC58', NULL, NULL, NULL, NULL, 'LAINA', '255753437856', 1, 'PENDING', 0, NULL, '2024-01-20 12:59:52', 30, '2024-01-20 12:59:52'),
(39, 'Habari Enna Kiondo, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', '8SL54DDWYXX', NULL, NULL, NULL, NULL, 'LAINA', '255765453536', 1, 'PENDING', 0, NULL, '2024-02-06 07:20:42', 30, '2024-02-06 07:20:42'),
(40, 'Habari Husna Tajiri, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', 'F3ZPUPH45DN', NULL, NULL, NULL, NULL, 'LAINA', '2557656546464', 1, 'PENDING', 0, NULL, '2024-02-28 09:13:18', 30, '2024-02-28 09:13:18'),
(41, 'Habari MIRAN WASEDA, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', '1RTRVCXSFZL', NULL, NULL, NULL, NULL, 'LAINA', '255658811577', 1, 'PENDING', 0, NULL, '2024-03-21 12:27:56', 30, '2024-03-21 12:27:56'),
(42, 'Habari MIKAYLA MAULID, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', 'HDCBMTFXPXG', NULL, NULL, NULL, NULL, 'LAINA', '255744811766', 1, 'PENDING', 0, NULL, '2024-03-27 06:40:17', 30, '2024-03-27 06:40:17'),
(43, 'Habari , eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.', 'V4CYSHWH3KA', NULL, NULL, NULL, NULL, 'LAINA', '255744811766', 1, 'PENDING', 0, NULL, '2024-03-27 06:55:31', 30, '2024-03-27 06:55:31');

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `NIDA_number`, `email_verified_at`, `password`, `remember_token`, `verify_token`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Admin Solocodes', 'admin@solocodes.co.tz', '0753437856', NULL, NULL, '$2y$10$0b/tjmMN.FnQhhmwRh2WkuW5FzSMrIPqOXbd1CPw/rcLQcV7z/mL2', NULL, NULL, 1, NULL, '2022-10-31 08:33:02'),
(30, 'Laina Finance', 'info@lainafinance.com', '0753437856', NULL, NULL, '$2y$10$88rGvsFyhYIwZSbP9hq7Eun/NztciDTMHPIyFwa7.Gjz.GR2Y6Jgu', NULL, 'd4RctYeGaoXcFgOnmMyHaNqGs6SKJybrKT96bWm38AvBh9nKDe8Xw10ur7NLiLkl', 1, '2023-02-27 08:33:03', '2024-01-08 15:40:25');



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;