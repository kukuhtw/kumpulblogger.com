-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: afragola-db.id.domainesia.com:3306
-- Generation Time: Jul 12, 2026 at 12:56 PM
-- Server version: 8.0.46-0ubuntu0.24.04.3
-- PHP Version: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `myadnetwork_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `advertisers_ads`
--

CREATE TABLE `advertisers_ads` (
  `id` int NOT NULL,
  `local_ads_id` int NOT NULL,
  `providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `advertisers_id` int NOT NULL,
  `title_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `description_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `landingpage_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `image_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `regdate` datetime NOT NULL,
  `budget_per_click_textads` int NOT NULL DEFAULT '100',
  `budget_allocation` int DEFAULT '0',
  `current_spending` int DEFAULT '0',
  `current_spending_from_partner` int DEFAULT '0',
  `last_updated_spending` datetime DEFAULT NULL,
  `ispublished` tinyint(1) NOT NULL DEFAULT '0',
  `published_date` datetime NOT NULL,
  `is_paid` tinyint(1) DEFAULT '0',
  `paid_date` datetime DEFAULT NULL,
  `paid_desc` text COLLATE utf8mb4_general_ci,
  `total_click` int NOT NULL DEFAULT '0',
  `current_click` int NOT NULL DEFAULT '0',
  `current_click_partner` int DEFAULT '0',
  `is_expired` tinyint(1) NOT NULL DEFAULT '0',
  `expired_date` datetime NOT NULL,
  `is_paused` tinyint(1) DEFAULT '0',
  `paused_date` datetime DEFAULT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `advertisers_ads`
--


-- --------------------------------------------------------

--
-- Table structure for table `advertisers_ads_partners`
--

CREATE TABLE `advertisers_ads_partners` (
  `id` int NOT NULL,
  `local_ads_id` int NOT NULL,
  `providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `advertisers_id` int NOT NULL,
  `title_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `description_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `landingpage_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `image_url` text COLLATE utf8mb4_general_ci NOT NULL,
  `regdate` datetime NOT NULL,
  `budget_per_click_textads` int DEFAULT NULL,
  `budget_allocation` int DEFAULT NULL,
  `current_spending` int DEFAULT NULL,
  `last_updated_spending` datetime DEFAULT NULL,
  `ispublished` tinyint(1) NOT NULL DEFAULT '0',
  `published_date` datetime NOT NULL,
  `total_click` int NOT NULL DEFAULT '0',
  `current_click` int NOT NULL DEFAULT '0',
  `is_expired` tinyint(1) NOT NULL DEFAULT '0',
  `expired_date` datetime NOT NULL,
  `is_paused` tinyint(1) NOT NULL DEFAULT '0',
  `paused_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ad_clicks`
--

CREATE TABLE `ad_clicks` (
  `id` bigint NOT NULL,
  `local_ads_id` int NOT NULL,
  `rate_text_ads` decimal(15,2) DEFAULT NULL,
  `budget_per_click_textads` int DEFAULT NULL,
  `ad_id` int NOT NULL,
  `pub_id` int NOT NULL,
  `pub_provider` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_cookies` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `browser_agent` text COLLATE utf8mb4_general_ci,
  `referrer` text COLLATE utf8mb4_general_ci,
  `site_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title_ads` text COLLATE utf8mb4_general_ci,
  `landingpage_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `click_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `time_epoch_click` bigint DEFAULT NULL,
  `isaudit` tinyint(1) NOT NULL DEFAULT '0',
  `audit_date` datetime DEFAULT NULL,
  `is_reject` tinyint(1) NOT NULL DEFAULT '0',
  `reason_rejection` text COLLATE utf8mb4_general_ci NOT NULL,
  `ads_providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ads_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `pubs_providers_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `revenue_publishers` decimal(15,2) DEFAULT NULL,
  `revenue_adnetwork_local` decimal(15,2) DEFAULT NULL,
  `revenue_adnetwork_partner` decimal(15,2) DEFAULT NULL,
  `hash_click` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `hash_audit` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_sync` int NOT NULL DEFAULT '0',
  `syncdate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ad_clicks`
--


-- --------------------------------------------------------

--
-- Table structure for table `ad_clicks_partner`
--

CREATE TABLE `ad_clicks_partner` (
  `id` int NOT NULL,
  `local_click_id` int NOT NULL,
  `local_ads_id` int NOT NULL,
  `rate_text_ads` int DEFAULT NULL,
  `budget_per_click_textads` int DEFAULT NULL,
  `ad_id` int NOT NULL,
  `pub_id` int NOT NULL,
  `pub_provider` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_cookies` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `browser_agent` text COLLATE utf8mb4_general_ci,
  `referrer` text COLLATE utf8mb4_general_ci,
  `site_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title_ads` text COLLATE utf8mb4_general_ci,
  `landingpage_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `click_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `time_epoch_click` bigint NOT NULL,
  `isaudit` tinyint(1) NOT NULL DEFAULT '0',
  `audit_date` datetime DEFAULT NULL,
  `is_reject` tinyint(1) NOT NULL DEFAULT '0',
  `reason_rejection` text COLLATE utf8mb4_general_ci NOT NULL,
  `ads_providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ads_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `pubs_providers_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `revenue_publishers` int DEFAULT NULL,
  `revenue_adnetwork_local` int DEFAULT NULL,
  `revenue_adnetwork_partner` int DEFAULT NULL,
  `hash_click` varchar(128) COLLATE utf8mb4_general_ci NOT NULL,
  `hash_audit` varchar(128) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
PARTITION BY RANGE (year(`click_time`))
(
PARTITION p_before_2020 VALUES LESS THAN (2020) ENGINE=InnoDB,
PARTITION p_2020 VALUES LESS THAN (2021) ENGINE=InnoDB,
PARTITION p_2021 VALUES LESS THAN (2022) ENGINE=InnoDB,
PARTITION p_2022 VALUES LESS THAN (2023) ENGINE=InnoDB,
PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE=InnoDB
);

-- --------------------------------------------------------

--
-- Table structure for table `articles`
--

CREATE TABLE `articles` (
  `id` int NOT NULL,
  `ispublished` tinyint(1) NOT NULL DEFAULT '0',
  `publishers_local_id` int NOT NULL,
  `pub_id` int DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `html_content` text COLLATE utf8mb4_general_ci NOT NULL,
  `images` text COLLATE utf8mb4_general_ci NOT NULL,
  `prediction_id` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tag` text COLLATE utf8mb4_general_ci NOT NULL,
  `language` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `tone` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `topic` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `input_token` int NOT NULL DEFAULT '0',
  `output_token` int NOT NULL DEFAULT '0',
  `json_response` text COLLATE utf8mb4_general_ci,
  `json_quiz` text COLLATE utf8mb4_general_ci,
  `wav` char(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `articles`
--


-- --------------------------------------------------------

--
-- Table structure for table `document_technical`
--

CREATE TABLE `document_technical` (
  `id` int NOT NULL,
  `filename` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `function_name` text COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_technical`
--


-- --------------------------------------------------------

--
-- Table structure for table `hasil_belanja_influencer`
--

CREATE TABLE `hasil_belanja_influencer` (
  `id` int NOT NULL,
  `order_id` char(40) COLLATE utf8mb4_general_ci NOT NULL,
  `advertiser_id` int NOT NULL,
  `owner_id` int NOT NULL,
  `media_id` int NOT NULL,
  `media_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `media_url` text COLLATE utf8mb4_general_ci NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `total_price` decimal(15,2) NOT NULL,
  `checkout_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hasil_belanja_influencer`
--


-- --------------------------------------------------------

--
-- Table structure for table `idea_article`
--

CREATE TABLE `idea_article` (
  `id` int NOT NULL,
  `topik` text COLLATE utf8mb4_general_ci NOT NULL,
  `deskripsi` text COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `idea_article`
--


-- --------------------------------------------------------

--
-- Table structure for table `influencer_media`
--

CREATE TABLE `influencer_media` (
  `id` int NOT NULL,
  `owner_id` int NOT NULL,
  `owner_provider_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `media_id` int NOT NULL,
  `media_name` char(50) COLLATE utf8mb4_general_ci NOT NULL,
  `media_url` text COLLATE utf8mb4_general_ci NOT NULL,
  `owner_media_desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `rate_owner` decimal(15,2) NOT NULL,
  `rate_markup_provider` decimal(15,2) NOT NULL,
  `rate_partner` decimal(15,2) NOT NULL,
  `regdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `influencer_media`
--


-- --------------------------------------------------------

--
-- Table structure for table `list_browser_banned`
--

CREATE TABLE `list_browser_banned` (
  `id` int NOT NULL,
  `browser_agent` text COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `date_banned` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `list_browser_banned`
--


-- --------------------------------------------------------

--
-- Table structure for table `list_ip_banned`
--

CREATE TABLE `list_ip_banned` (
  `id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `date_banned` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `list_ip_banned`
--


-- --------------------------------------------------------

--
-- Table structure for table `llm_settings`
--

CREATE TABLE `llm_settings` (
  `id` int NOT NULL,
  `llm_model` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `openai_key` text COLLATE utf8mb4_general_ci,
  `replicate_key` text COLLATE utf8mb4_general_ci,
  `max_tokens` int DEFAULT '2048',
  `temperature` decimal(3,2) DEFAULT '0.70',
  `regdate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `llm_settings`
--


-- --------------------------------------------------------

--
-- Table structure for table `log_payment_order_influencer`
--

CREATE TABLE `log_payment_order_influencer` (
  `id` int NOT NULL,
  `advertiser_id` int NOT NULL,
  `order_id` char(40) COLLATE utf8mb4_general_ci NOT NULL,
  `payment_message` text COLLATE utf8mb4_general_ci NOT NULL,
  `payment_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mapping_advertisers_ads_publishers_site`
--

CREATE TABLE `mapping_advertisers_ads_publishers_site` (
  `id` bigint NOT NULL,
  `rate_text_ads` decimal(15,2) NOT NULL,
  `budget_per_click_textads` decimal(15,2) DEFAULT NULL,
  `local_ads_id` int NOT NULL,
  `publishers_site_local_id` int NOT NULL,
  `owner_advertisers_id` int NOT NULL,
  `title_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `description_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `landingpage_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `publishers_local_id` int NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `image_url` text COLLATE utf8mb4_general_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `is_paused` tinyint(1) NOT NULL DEFAULT '0',
  `is_expired` tinyint(1) NOT NULL DEFAULT '0',
  `is_approved_by_publisher` tinyint(1) NOT NULL DEFAULT '0',
  `is_approved_by_advertiser` tinyint(1) NOT NULL DEFAULT '0',
  `published_date` date DEFAULT NULL,
  `paused_date` datetime DEFAULT NULL,
  `expired_date` datetime DEFAULT NULL,
  `approval_date_publisher` datetime DEFAULT NULL,
  `approval_date_advertiser` datetime DEFAULT NULL,
  `pubs_providers_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ads_providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ads_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reasons_rejected_by_advertiser` text COLLATE utf8mb4_general_ci,
  `reasons_rejected_by_publisher` text COLLATE utf8mb4_general_ci,
  `revenue_publishers` int DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mapping_advertisers_ads_publishers_site`
--


-- --------------------------------------------------------

--
-- Table structure for table `mapping_advertisers_ads_publishers_site_from_partners`
--

CREATE TABLE `mapping_advertisers_ads_publishers_site_from_partners` (
  `id` bigint NOT NULL,
  `local_mapping_id` bigint NOT NULL,
  `rate_text_ads` int NOT NULL,
  `budget_per_click_textads` int DEFAULT NULL,
  `local_ads_id` int NOT NULL,
  `publishers_site_local_id` int NOT NULL,
  `owner_advertisers_id` int NOT NULL,
  `title_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `description_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `landingpage_ads` text COLLATE utf8mb4_general_ci NOT NULL,
  `publishers_local_id` int NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `image_url` text COLLATE utf8mb4_general_ci,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `is_paused` tinyint(1) NOT NULL DEFAULT '0',
  `is_expired` tinyint(1) NOT NULL DEFAULT '0',
  `is_approved_by_publisher` tinyint(1) NOT NULL DEFAULT '0',
  `is_approved_by_advertiser` tinyint(1) NOT NULL DEFAULT '0',
  `published_date` date DEFAULT NULL,
  `paused_date` datetime DEFAULT NULL,
  `expired_date` datetime DEFAULT NULL,
  `approval_date_publisher` datetime DEFAULT NULL,
  `approval_date_advertiser` datetime DEFAULT NULL,
  `pubs_providers_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ads_providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ads_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `reasons_rejected_by_advertiser` text COLLATE utf8mb4_general_ci,
  `reasons_rejected_by_publisher` text COLLATE utf8mb4_general_ci,
  `revenue_publishers` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int NOT NULL,
  `media` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--


-- --------------------------------------------------------

--
-- Table structure for table `msadmin`
--

CREATE TABLE `msadmin` (
  `id` smallint NOT NULL,
  `loginemail` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `passwords` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `whatsapp` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_login_attempt` datetime DEFAULT NULL,
  `pass_phrase` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `number_last_login_attempt` int NOT NULL DEFAULT '0',
  `forgot_password_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `realname` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `msadmin`
--


-- --------------------------------------------------------

--
-- Table structure for table `msusers`
--

CREATE TABLE `msusers` (
  `id` int NOT NULL,
  `loginemail` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `passwords` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `whatsapp` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_login_attempt` datetime DEFAULT NULL,
  `pass_phrase` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `number_last_login_attempt` int NOT NULL DEFAULT '0',
  `forgot_password_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `realname` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `regdate` datetime NOT NULL,
  `current_revenue` decimal(15,2) NOT NULL DEFAULT '0.00',
  `local_revenue_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `local_revenue_unpaid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `current_revenue_from_partner` decimal(15,2) NOT NULL DEFAULT '0.00',
  `partner_revenue_paid` decimal(15,2) DEFAULT '0.00',
  `partner_revenue_unpaid` decimal(15,2) DEFAULT '0.00',
  `total_current_revenue` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_updated_revenue` datetime DEFAULT NULL,
  `current_spending` decimal(15,2) NOT NULL DEFAULT '0.00',
  `current_spending_from_partner` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_current_spending` decimal(15,2) NOT NULL DEFAULT '0.00',
  `last_updated_spending` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `msusers`
--


-- --------------------------------------------------------

--
-- Table structure for table `payment_local_pubs`
--

CREATE TABLE `payment_local_pubs` (
  `id` int NOT NULL,
  `email_pubs` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text COLLATE utf8mb4_general_ci,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_local_pubs`
--


-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_providers`
--

CREATE TABLE `payment_partner_providers` (
  `id` int NOT NULL,
  `partner_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email_provider` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text COLLATE utf8mb4_general_ci,
  `payment_date` datetime DEFAULT NULL,
  `payment_by` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_providers_sync`
--

CREATE TABLE `payment_partner_providers_sync` (
  `id` int NOT NULL,
  `local_id` int NOT NULL,
  `partner_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email_provider` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text COLLATE utf8mb4_general_ci,
  `payment_date` datetime DEFAULT NULL,
  `payment_by` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_pubs`
--

CREATE TABLE `payment_partner_pubs` (
  `id` int NOT NULL,
  `publisher_local_id` int NOT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email_pubs` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text COLLATE utf8mb4_general_ci,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_by` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_partner_pubs_sync`
--

CREATE TABLE `payment_partner_pubs_sync` (
  `id` int NOT NULL,
  `local_id` int NOT NULL,
  `publisher_local_id` int NOT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email_pubs` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nominal` decimal(15,2) DEFAULT NULL,
  `payment_description` text COLLATE utf8mb4_general_ci,
  `payment_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `payment_by` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

CREATE TABLE `providers` (
  `id` smallint NOT NULL,
  `providers_code` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `hash_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `secret_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `api_endpoint` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `my_revenue` decimal(15,2) NOT NULL DEFAULT '0.00',
  `my_revenue_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `my_revenue_unpaid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `regdate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers`
--


-- --------------------------------------------------------

--
-- Table structure for table `providers_contact_person`
--

CREATE TABLE `providers_contact_person` (
  `id` smallint NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `whatsapp` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_bank` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `providers_contact_person`
--


-- --------------------------------------------------------

--
-- Table structure for table `providers_contact_person_sync`
--

CREATE TABLE `providers_contact_person_sync` (
  `id` smallint NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `whatsapp` varchar(25) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_bank` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers_partners`
--

CREATE TABLE `providers_partners` (
  `id` int NOT NULL,
  `signature` text COLLATE utf8mb4_general_ci NOT NULL,
  `providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `target_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `api_endpoint` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `requestdate` datetime NOT NULL,
  `time_epoch_requestdate` bigint NOT NULL,
  `is_followup` tinyint(1) DEFAULT '0',
  `public_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `secret_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `isapproved` tinyint(1) NOT NULL DEFAULT '0',
  `approved_date` datetime NOT NULL,
  `time_epoch_approveddate` bigint NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_hold` tinyint(1) NOT NULL DEFAULT '0',
  `hold_date` datetime NOT NULL,
  `ipaddress` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `source_url` text COLLATE utf8mb4_general_ci NOT NULL,
  `browser_agent` text COLLATE utf8mb4_general_ci NOT NULL,
  `partner_revenue` decimal(15,2) DEFAULT NULL,
  `partner_revenue_paid` decimal(15,2) DEFAULT NULL,
  `partner_revenue_unpaid` decimal(15,2) DEFAULT NULL,
  `last_updated_revenue` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers_request`
--

CREATE TABLE `providers_request` (
  `id` int NOT NULL,
  `request_from` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `signature` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `target_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `api_endpoint` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `request_date` datetime NOT NULL,
  `is_followup` tinyint(1) NOT NULL DEFAULT '0',
  `time_epoch_requestdate` bigint NOT NULL,
  `ipaddress` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_url` text COLLATE utf8mb4_general_ci,
  `browser_agent` text COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publishers_site`
--

CREATE TABLE `publishers_site` (
  `id` int NOT NULL,
  `internal_blog` tinyint(1) NOT NULL DEFAULT '0',
  `providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `publishers_local_id` int NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `alternate_code` text COLLATE utf8mb4_general_ci,
  `public_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `secret_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `rate_text_ads` decimal(15,2) NOT NULL DEFAULT '50.00',
  `advertiser_allowed` text COLLATE utf8mb4_general_ci,
  `advertiser_rejected` text COLLATE utf8mb4_general_ci,
  `regdate` datetime NOT NULL,
  `current_site_revenue` decimal(15,2) DEFAULT NULL,
  `current_site_revenue_from_partner` decimal(15,2) DEFAULT NULL,
  `isbanned` tinyint(1) NOT NULL DEFAULT '0',
  `banned_date` datetime DEFAULT NULL,
  `banned_reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `ulasan` text COLLATE utf8mb4_general_ci,
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publishers_site`
--


-- --------------------------------------------------------

--
-- Table structure for table `publishers_site_partners`
--

CREATE TABLE `publishers_site_partners` (
  `id` int NOT NULL,
  `local_id` int NOT NULL,
  `providers_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `publishers_local_id` int NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `site_desc` text COLLATE utf8mb4_general_ci NOT NULL,
  `rate_text_ads` decimal(15,2) UNSIGNED DEFAULT '10.00',
  `advertiser_allowed` text COLLATE utf8mb4_general_ci,
  `advertiser_rejected` text COLLATE utf8mb4_general_ci,
  `regdate` datetime NOT NULL,
  `isbanned` tinyint(1) NOT NULL DEFAULT '0',
  `banned_date` datetime DEFAULT NULL,
  `banned_reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `last_updated` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publisher_partner`
--

CREATE TABLE `publisher_partner` (
  `id` int NOT NULL,
  `loginemail` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `whatsapp` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(55) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `publishers_local_id` int NOT NULL,
  `revenue_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `revenue_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `revenue_unpaid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `publisher_quota`
--

CREATE TABLE `publisher_quota` (
  `id` int NOT NULL,
  `publisher_id` int NOT NULL,
  `pub_id` int NOT NULL,
  `daily_free_quota` int NOT NULL DEFAULT '1',
  `free_quota_articles` int NOT NULL DEFAULT '5',
  `paid_quota` int NOT NULL DEFAULT '0',
  `quota_valid_until` date DEFAULT NULL,
  `username` char(18) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `last_updated` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `publisher_quota`
--


-- --------------------------------------------------------

--
-- Table structure for table `rekap_harian`
--

CREATE TABLE `rekap_harian` (
  `id` bigint NOT NULL,
  `tanggal_klik` date DEFAULT NULL,
  `local_ads_id` int DEFAULT NULL,
  `ads_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sumber_data` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sumber_data_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `revenue_publishers` int DEFAULT NULL,
  `revenue_adnetwork_local` int DEFAULT NULL,
  `revenue_adnetwork_partner` int DEFAULT NULL,
  `total_spending` int DEFAULT NULL,
  `jumlah_klik` int DEFAULT NULL,
  `title_ads` text COLLATE utf8mb4_general_ci,
  `landingpage_ads` text COLLATE utf8mb4_general_ci,
  `budget_allocation` int DEFAULT NULL,
  `rekap_date` datetime DEFAULT NULL,
  `last_updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rekap_harian`
--


-- --------------------------------------------------------

--
-- Table structure for table `rekap_harian_provider_partner`
--

CREATE TABLE `rekap_harian_provider_partner` (
  `id` bigint NOT NULL,
  `rekap_date` date NOT NULL,
  `ads_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `total_clicks` int NOT NULL DEFAULT '0',
  `total_revenue_partner` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_harian_publishers`
--

CREATE TABLE `rekap_harian_publishers` (
  `rekap_id` bigint NOT NULL,
  `rekap_date` date NOT NULL,
  `pub_id` int NOT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ads_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `total_revenue_publishers` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rekap_harian_publishers`
--


-- --------------------------------------------------------

--
-- Table structure for table `rekap_publisher_revenue_harian_partner`
--

CREATE TABLE `rekap_publisher_revenue_harian_partner` (
  `id` int NOT NULL,
  `pub_id` int NOT NULL,
  `site_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `date_click` date NOT NULL,
  `total_revenue_publishers` int NOT NULL DEFAULT '0',
  `total_clicks` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_pubs_revenue`
--

CREATE TABLE `rekap_pubs_revenue` (
  `pub_id` int NOT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `partner_revenue` int DEFAULT '0',
  `local_revenue` int DEFAULT '0',
  `total_revenue` int DEFAULT '0',
  `total_click` int DEFAULT NULL,
  `calculation_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rekap_total_publisher_partner`
--

CREATE TABLE `rekap_total_publisher_partner` (
  `id` int NOT NULL,
  `pub_id` int NOT NULL,
  `owner_id` int DEFAULT '0',
  `site_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `site_domain` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pubs_providers_domain_url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `total_revenue_publishers` int NOT NULL DEFAULT '0',
  `total_clicks` int NOT NULL DEFAULT '0',
  `rekap_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `setting_rule_clicks`
--

CREATE TABLE `setting_rule_clicks` (
  `id` int NOT NULL,
  `rule_name` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `threshold` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `setting_rule_clicks`
--


-- --------------------------------------------------------

--
-- Table structure for table `video_watch_logs`
--

CREATE TABLE `video_watch_logs` (
  `id` bigint NOT NULL,
  `pubid` int NOT NULL,
  `startTime` varchar(35) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `videoId` varchar(16) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `duration` int NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `useragent` text COLLATE utf8mb4_general_ci,
  `referrer` text COLLATE utf8mb4_general_ci,
  `url` text COLLATE utf8mb4_general_ci,
  `token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `viewed_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `video_watch_logs`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `advertisers_ads`
--
ALTER TABLE `advertisers_ads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`(100)),
  ADD KEY `idx_advertisers_id` (`advertisers_id`),
  ADD KEY `idx_ispublished_isexpired_ispaused` (`ispublished`,`is_expired`,`is_paused`),
  ADD KEY `idx_title_ads` (`title_ads`(100));

--
-- Indexes for table `advertisers_ads_partners`
--
ALTER TABLE `advertisers_ads_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`),
  ADD KEY `idx_advertisers_id` (`advertisers_id`),
  ADD KEY `idx_status_flags` (`ispublished`,`is_expired`,`is_paused`),
  ADD KEY `idx_budget_spending` (`budget_per_click_textads`,`budget_allocation`,`current_spending`),
  ADD KEY `idx_dates` (`regdate`,`published_date`,`expired_date`,`paused_date`);

--
-- Indexes for table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_ads_providers_domain_url` (`ads_providers_domain_url`(100)),
  ADD KEY `idx_pub_id` (`pub_id`),
  ADD KEY `idx_ip_user_agent` (`ip_address`,`user_cookies`(100),`browser_agent`(100)),
  ADD KEY `idx_time_epoch_click` (`time_epoch_click`),
  ADD KEY `idx_isaudit_is_reject` (`isaudit`,`is_reject`),
  ADD KEY `idx_hash_click` (`hash_click`),
  ADD KEY `idx_hash_audit` (`hash_audit`),
  ADD KEY `idx_is_sync` (`is_sync`),
  ADD KEY `idx_click_time` (`click_time`),
  ADD KEY `idx_ad_id` (`ad_id`),
  ADD KEY `idx_pub_provider` (`pub_provider`),
  ADD KEY `idx_site_domain` (`site_domain`),
  ADD KEY `idx_ads_providers_name` (`ads_providers_name`),
  ADD KEY `idx_pubs_providers_name` (`pubs_providers_name`),
  ADD KEY `idx_revenue` (`revenue_publishers`,`revenue_adnetwork_local`,`revenue_adnetwork_partner`);

--
-- Indexes for table `ad_clicks_partner`
--
ALTER TABLE `ad_clicks_partner`
  ADD PRIMARY KEY (`id`,`click_time`),
  ADD KEY `idx_local_click_id` (`local_click_id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_pub_id` (`pub_id`),
  ADD KEY `idx_click_time` (`click_time`),
  ADD KEY `idx_hash_click` (`hash_click`);

--
-- Indexes for table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_technical`
--
ALTER TABLE `document_technical`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hasil_belanja_influencer`
--
ALTER TABLE `hasil_belanja_influencer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `idea_article`
--
ALTER TABLE `idea_article`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `influencer_media`
--
ALTER TABLE `influencer_media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `list_browser_banned`
--
ALTER TABLE `list_browser_banned`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_browser_agent` (`browser_agent`(100)),
  ADD KEY `idx_date_banned` (`date_banned`);

--
-- Indexes for table `list_ip_banned`
--
ALTER TABLE `list_ip_banned`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`);

--
-- Indexes for table `llm_settings`
--
ALTER TABLE `llm_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_payment_order_influencer`
--
ALTER TABLE `log_payment_order_influencer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mapping_advertisers_ads_publishers_site`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_publishers_site_local_id` (`publishers_site_local_id`),
  ADD KEY `idx_ads_providers_domain_url` (`ads_providers_domain_url`),
  ADD KEY `idx_pubs_providers_domain_url` (`pubs_providers_domain_url`),
  ADD KEY `idx_rate_budget` (`rate_text_ads`,`budget_per_click_textads`,`local_ads_id`),
  ADD KEY `idx_published_date` (`published_date`),
  ADD KEY `idx_paused_date` (`paused_date`),
  ADD KEY `idx_expired_date` (`expired_date`);

--
-- Indexes for table `mapping_advertisers_ads_publishers_site_from_partners`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site_from_partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_mapping` (`local_mapping_id`,`local_ads_id`,`publishers_site_local_id`,`ads_providers_domain_url`,`pubs_providers_domain_url`),
  ADD KEY `idx_published_date` (`published_date`),
  ADD KEY `idx_is_published` (`is_published`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `msadmin`
--
ALTER TABLE `msadmin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `msusers`
--
ALTER TABLE `msusers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `loginemail` (`loginemail`),
  ADD KEY `last_login` (`last_login`),
  ADD KEY `whatsapp` (`whatsapp`),
  ADD KEY `last_login_attempt` (`last_login_attempt`),
  ADD KEY `current_revenue` (`current_revenue`),
  ADD KEY `current_revenue_from_partner` (`current_revenue_from_partner`),
  ADD KEY `total_current_revenue` (`total_current_revenue`),
  ADD KEY `current_spending` (`current_spending`),
  ADD KEY `total_current_spending` (`total_current_spending`);

--
-- Indexes for table `payment_local_pubs`
--
ALTER TABLE `payment_local_pubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_pubs` (`email_pubs`),
  ADD KEY `payment_date` (`payment_date`);

--
-- Indexes for table `payment_partner_providers`
--
ALTER TABLE `payment_partner_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_providers_domain_url` (`partner_providers_domain_url`),
  ADD KEY `idx_email_provider` (`email_provider`);

--
-- Indexes for table `payment_partner_providers_sync`
--
ALTER TABLE `payment_partner_providers_sync`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_providers_domain_url` (`partner_providers_domain_url`),
  ADD KEY `idx_email_provider` (`email_provider`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_email_provider_domain_url` (`email_provider`,`partner_providers_domain_url`),
  ADD KEY `idx_local_id` (`local_id`);

--
-- Indexes for table `payment_partner_pubs`
--
ALTER TABLE `payment_partner_pubs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publisher_local_id` (`publisher_local_id`),
  ADD KEY `pubs_providers_domain_url` (`pubs_providers_domain_url`),
  ADD KEY `email_pubs` (`email_pubs`);

--
-- Indexes for table `payment_partner_pubs_sync`
--
ALTER TABLE `payment_partner_pubs_sync`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_publisher_local_id` (`publisher_local_id`),
  ADD KEY `idx_pubs_providers_domain_url` (`pubs_providers_domain_url`),
  ADD KEY `idx_email_pubs` (`email_pubs`),
  ADD KEY `idx_composite` (`publisher_local_id`,`pubs_providers_domain_url`,`email_pubs`);

--
-- Indexes for table `providers`
--
ALTER TABLE `providers`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `providers_contact_person`
--
ALTER TABLE `providers_contact_person`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `providers_contact_person_sync`
--
ALTER TABLE `providers_contact_person_sync`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `providers_partners`
--
ALTER TABLE `providers_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_providers_name` (`providers_name`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`),
  ADD KEY `idx_target_providers_domain_url` (`target_providers_domain_url`),
  ADD KEY `idx_is_hold` (`is_hold`),
  ADD KEY `idx_secret_key` (`secret_key`),
  ADD KEY `idx_public_key` (`public_key`);

--
-- Indexes for table `providers_request`
--
ALTER TABLE `providers_request`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `publishers_site`
--
ALTER TABLE `publishers_site`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_providers_domain_url` (`providers_domain_url`),
  ADD KEY `idx_publishers_local_id` (`publishers_local_id`),
  ADD KEY `idx_site_name_domain` (`site_name`,`site_domain`),
  ADD KEY `idx_rate_revenue` (`rate_text_ads`,`current_site_revenue`),
  ADD KEY `idx_isbanned` (`isbanned`,`banned_date`),
  ADD KEY `idx_last_updated` (`last_updated`);

--
-- Indexes for table `publishers_site_partners`
--
ALTER TABLE `publishers_site_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `local_id` (`local_id`),
  ADD KEY `providers_name` (`providers_name`),
  ADD KEY `providers_domain_url` (`providers_domain_url`),
  ADD KEY `site_domain` (`site_domain`),
  ADD KEY `isbanned` (`isbanned`),
  ADD KEY `regdate` (`regdate`);

--
-- Indexes for table `publisher_partner`
--
ALTER TABLE `publisher_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_key` (`loginemail`,`pubs_providers_domain_url`,`publishers_local_id`);

--
-- Indexes for table `publisher_quota`
--
ALTER TABLE `publisher_quota`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rekap_harian`
--
ALTER TABLE `rekap_harian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tanggal_klik` (`tanggal_klik`),
  ADD KEY `idx_ads_providers_domain_url` (`ads_providers_domain_url`),
  ADD KEY `idx_local_ads_id` (`local_ads_id`),
  ADD KEY `idx_sumber_data` (`sumber_data`),
  ADD KEY `idx_total_spending` (`total_spending`),
  ADD KEY `idx_jumlah_klik` (`jumlah_klik`),
  ADD KEY `idx_composite` (`tanggal_klik`,`local_ads_id`,`ads_providers_domain_url`);

--
-- Indexes for table `rekap_harian_provider_partner`
--
ALTER TABLE `rekap_harian_provider_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`rekap_date`,`ads_providers_domain_url`);

--
-- Indexes for table `rekap_harian_publishers`
--
ALTER TABLE `rekap_harian_publishers`
  ADD PRIMARY KEY (`rekap_id`);

--
-- Indexes for table `rekap_publisher_revenue_harian_partner`
--
ALTER TABLE `rekap_publisher_revenue_harian_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`pub_id`,`pubs_providers_domain_url`,`date_click`);

--
-- Indexes for table `rekap_pubs_revenue`
--
ALTER TABLE `rekap_pubs_revenue`
  ADD PRIMARY KEY (`pub_id`,`pubs_providers_domain_url`);

--
-- Indexes for table `rekap_total_publisher_partner`
--
ALTER TABLE `rekap_total_publisher_partner`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rekap` (`pub_id`,`pubs_providers_domain_url`);

--
-- Indexes for table `setting_rule_clicks`
--
ALTER TABLE `setting_rule_clicks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_rule_name` (`rule_name`);

--
-- Indexes for table `video_watch_logs`
--
ALTER TABLE `video_watch_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pubid` (`pubid`),
  ADD KEY `viewed_at` (`viewed_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advertisers_ads`
--
ALTER TABLE `advertisers_ads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `advertisers_ads_partners`
--
ALTER TABLE `advertisers_ads_partners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ad_clicks`
--
ALTER TABLE `ad_clicks`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10206;

--
-- AUTO_INCREMENT for table `ad_clicks_partner`
--
ALTER TABLE `ad_clicks_partner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=650;

--
-- AUTO_INCREMENT for table `document_technical`
--
ALTER TABLE `document_technical`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `hasil_belanja_influencer`
--
ALTER TABLE `hasil_belanja_influencer`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `idea_article`
--
ALTER TABLE `idea_article`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3658;

--
-- AUTO_INCREMENT for table `influencer_media`
--
ALTER TABLE `influencer_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `list_browser_banned`
--
ALTER TABLE `list_browser_banned`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `list_ip_banned`
--
ALTER TABLE `list_ip_banned`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `llm_settings`
--
ALTER TABLE `llm_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `log_payment_order_influencer`
--
ALTER TABLE `log_payment_order_influencer`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mapping_advertisers_ads_publishers_site`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=971;

--
-- AUTO_INCREMENT for table `mapping_advertisers_ads_publishers_site_from_partners`
--
ALTER TABLE `mapping_advertisers_ads_publishers_site_from_partners`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `msadmin`
--
ALTER TABLE `msadmin`
  MODIFY `id` smallint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `msusers`
--
ALTER TABLE `msusers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5759;

--
-- AUTO_INCREMENT for table `payment_local_pubs`
--
ALTER TABLE `payment_local_pubs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `payment_partner_providers`
--
ALTER TABLE `payment_partner_providers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_partner_providers_sync`
--
ALTER TABLE `payment_partner_providers_sync`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_partner_pubs`
--
ALTER TABLE `payment_partner_pubs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_partner_pubs_sync`
--
ALTER TABLE `payment_partner_pubs_sync`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `providers`
--
ALTER TABLE `providers`
  MODIFY `id` smallint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `providers_contact_person`
--
ALTER TABLE `providers_contact_person`
  MODIFY `id` smallint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `providers_contact_person_sync`
--
ALTER TABLE `providers_contact_person_sync`
  MODIFY `id` smallint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `providers_partners`
--
ALTER TABLE `providers_partners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `providers_request`
--
ALTER TABLE `providers_request`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publishers_site`
--
ALTER TABLE `publishers_site`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `publishers_site_partners`
--
ALTER TABLE `publishers_site_partners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publisher_partner`
--
ALTER TABLE `publisher_partner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `publisher_quota`
--
ALTER TABLE `publisher_quota`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `rekap_harian`
--
ALTER TABLE `rekap_harian`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=780;

--
-- AUTO_INCREMENT for table `rekap_harian_provider_partner`
--
ALTER TABLE `rekap_harian_provider_partner`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_harian_publishers`
--
ALTER TABLE `rekap_harian_publishers`
  MODIFY `rekap_id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=648;

--
-- AUTO_INCREMENT for table `rekap_publisher_revenue_harian_partner`
--
ALTER TABLE `rekap_publisher_revenue_harian_partner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rekap_total_publisher_partner`
--
ALTER TABLE `rekap_total_publisher_partner`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `setting_rule_clicks`
--
ALTER TABLE `setting_rule_clicks`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `video_watch_logs`
--
ALTER TABLE `video_watch_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45748;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
