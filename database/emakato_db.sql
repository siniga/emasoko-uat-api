-- -------------------------------------------------------------
-- TablePlus 4.8.0(432)
--
-- https://tableplus.com/
--
-- Database: emakato_db
-- Generation Time: 2022-11-21 09:47:23.2140
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb3;

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
  `uuid` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

DROP TABLE IF EXISTS `debit_orders`;
CREATE TABLE `debit_orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `partner_id` int DEFAULT NULL,
  `customer_account_id` int NOT NULL,
  `code` varchar(11) DEFAULT NULL,
  `loan_purpose_id` int DEFAULT NULL,
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
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `approved_by` int DEFAULT NULL,
  `approver_at` datetime DEFAULT NULL,
  `approve_code` varchar(15) DEFAULT NULL,
  `remarks` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3;

DROP TABLE IF EXISTS `partner_approvals`;
CREATE TABLE `partner_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int DEFAULT NULL,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `account_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'BANK', 'bank', '2021-11-02 11:02:01', '2021-11-02 11:02:01'),
(2, 'MNO', 'mno', '2021-11-02 11:02:01', '2021-11-02 11:02:01');

INSERT INTO `accounts` (`id`, `account_type_id`, `name`, `description`, `mask`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, 'NMB Bank', 'NMB Bank', '99999999999', 1, '2022-02-24 15:36:38', '2022-10-31 09:26:30'),
(2, 2, 'Vodacom', 'vodacom', NULL, 1, '2022-02-24 15:36:38', '2022-10-31 09:26:26'),
(3, 1, 'Stanbic Bank', 'Stanbic Bank', NULL, 1, '2022-03-29 15:58:16', '2022-10-31 09:26:21'),
(4, 1, 'Test', 'test channel', NULL, 1, '2022-10-31 09:20:05', '2022-10-31 09:20:05');

INSERT INTO `acquisition` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'WEB', 'Acquired from web portal', '2021-10-14 00:00:00', '2021-10-14 00:00:00'),
(2, 'USSD', 'Acquired from USSD', '2021-10-13 00:00:00', '2021-10-06 00:00:00'),
(3, 'MOBILE APP', 'Acquired from MOBILE APP', '2021-10-13 00:00:00', '2021-10-06 00:00:00');

INSERT INTO `countries` (`id`, `code`, `name`, `initial`, `created_at`, `updated_at`) VALUES
(1, '+255', 'TANZANIA', 'TZ', '2021-11-06 12:12:54', '2022-04-01 17:33:41');

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
(13, 'default', '{\"uuid\":\"978d19a7-d0df-4b88-8de8-79b9a0575e57\",\"displayName\":\"App\\\\Jobs\\\\SendSMSJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"maxTries\":null,\"maxExceptions\":null,\"failOnTimeout\":false,\"backoff\":null,\"timeout\":null,\"retryUntil\":null,\"data\":{\"commandName\":\"App\\\\Jobs\\\\SendSMSJob\",\"command\":\"O:19:\\\"App\\\\Jobs\\\\SendSMSJob\\\":11:{s:7:\\\"\\u0000*\\u0000data\\\";a:4:{s:8:\\\"schedule\\\";N;s:10:\\\"message_id\\\";s:11:\\\"UCD783H2CMP\\\";s:6:\\\"sender\\\";s:7:\\\"TAARIFA\\\";s:7:\\\"message\\\";s:71:\\\"eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.\\\";}s:3:\\\"job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:15:\\\"chainConnection\\\";N;s:10:\\\"chainQueue\\\";N;s:19:\\\"chainCatchCallbacks\\\";N;s:5:\\\"delay\\\";N;s:11:\\\"afterCommit\\\";N;s:10:\\\"middleware\\\";a:0:{}s:7:\\\"chained\\\";a:0:{}}\"}}', 0, NULL, 1668468177, 1668468177);

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
(2, 2, 1, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 1, 'Multics Systems', '01188466644', 0, NULL, '2022-10-27 08:17:50', '2022-10-27 08:17:50');

INSERT INTO `partner_approvals` (`id`, `partner_id`, `key`, `label`, `status`, `remarks`) VALUES
(5, 2, 'Company_Information', 'Company Information', 'approved', NULL),
(6, 2, 'Company_Documents', 'Company Documents', 'approved', NULL),
(7, 2, 'Company_Collection_Accounts', 'Company Collection Accounts', 'approved', NULL),
(8, 2, 'Company_Authorized_Representative', 'Company Authorized Representative', 'approved', NULL);

INSERT INTO `partner_categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'MICROFINANCE', 'Shop owned by Vodacom', '2021-10-25 23:31:18', '2021-11-04 16:46:59'),
(2, 'SHOPS', 'Other Shop which are not owned by MNO', '2021-10-25 23:36:03', '2021-11-04 16:47:09'),
(3, 'INSURER', 'Insurance Companies which issues IPF', '2021-10-25 23:37:50', '2021-11-04 16:47:29');

INSERT INTO `partner_documents` (`id`, `partner_id`, `partner_uuid`, `key`, `name`, `attachment`) VALUES
(4, 2, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 'Business_Licence', 'Business Licence', 'invoice_INV-C-2022-992801.pdf'),
(5, 2, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 'Certificate_of_incorporation', 'Certificate of incorporation', 'invoice_INV-C-2022-992801.pdf'),
(6, 2, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 'TIN_Certificate', 'TIN Certificate', 'invoice_INV-C-2022-992801.pdf');

INSERT INTO `partner_staff` (`id`, `partner_id`, `partner_uuid`, `name`, `NIDA_number`, `email`, `phone`, `title`, `created_at`, `updated_at`) VALUES
(2, 2, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 'Nickas Ngolongolo', '88376356-53553-55353-55', 'ngolongolo@multics.co.tz', '255717705746', 'CEO', '2022-10-27 11:18:26', '2022-10-27 11:18:26');

INSERT INTO `partner_users` (`id`, `user_id`, `partner_id`, `partner_uuid`, `authorize`, `created_at`, `updated_at`) VALUES
(2, 29, 2, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 1, '2022-10-27 08:18:36', '2022-10-27 08:18:36');

INSERT INTO `partners` (`id`, `uuid`, `partner_category_id`, `business_name`, `year_of_incorporation`, `tin_number`, `number_of_staff`, `region_id`, `phone`, `email`, `website`, `postal_address`, `physical_address`, `complete`, `status`, `next_step`, `otp_token`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(2, 'a244e5c2-da3a-493e-a7d9-7aa07a252d50', 1, 'Multics Systems', '2020', '983-477-388', 100, '2', '+255717705746', 'ngolongolo@multics.co.tz', NULL, NULL, 'Mikocheni, Dar es Salaam', 1, 3, 'company-info-overview/a244e5c2-da3a-493e-a7d9-7aa07a252d50', NULL, NULL, NULL, '2022-10-27 08:16:46', '2022-10-27 11:31:35');

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
(31, 29, 2);

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'admin', 'Administrator'),
(2, 'company_approver', 'Company Approver'),
(4, 'test', 'Test'),
(5, 'Group_2', 'Describe my group');

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
(29, 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?', 'UCD783H2CMP', NULL, NULL, NULL, NULL, 'TAARIFA', '255753437856', 1, 'PENDING', 0, NULL, '2022-11-14 23:22:57', 29, '2022-11-14 23:22:57');

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `NIDA_number`, `email_verified_at`, `password`, `remember_token`, `verify_token`, `active`, `created_at`, `updated_at`) VALUES
(1, 'Admin Solocodes', 'admin@solocodes.co.tz', '0753437856', NULL, NULL, '$2y$10$0b/tjmMN.FnQhhmwRh2WkuW5FzSMrIPqOXbd1CPw/rcLQcV7z/mL2', NULL, NULL, 1, NULL, '2022-10-31 08:33:02'),
(29, 'Multics Systems', 'ngolongolo@multics.co.tz', '0767000300', NULL, NULL, '$2y$10$A1/XIri2ZmsJgsdNoW1kdezuWCD63sNzwr8vNBhTMSIFkUWqUWu4a', NULL, 'yyCXuzzbt9lyvX7OP6ndcvDFUuWN0YeKmeFdETLPEqkLz4ppwauqlLlGG3QqWGbk', 1, '2022-10-26 13:48:16', '2022-10-31 08:23:39');



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;