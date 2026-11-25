-- MySQL dump 10.13  Distrib 8.0.30, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: sagara_baja
-- ------------------------------------------------------
-- Server version	8.0.30

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `wp_sb_audit_logs`
--

DROP TABLE IF EXISTS `wp_sb_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `object_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `object_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `old_values` longtext COLLATE utf8mb4_unicode_520_ci,
  `new_values` longtext COLLATE utf8mb4_unicode_520_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_object` (`object_type`,`object_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_audit_logs`
--

LOCK TABLES `wp_sb_audit_logs` WRITE;
/*!40000 ALTER TABLE `wp_sb_audit_logs` DISABLE KEYS */;
INSERT INTO `wp_sb_audit_logs` VALUES (1,NULL,NULL,'activate_plugin','core','0.1.0-planning',NULL,'{\"executed_migrations\":[\"Migration_003_Sales\"]}',NULL,NULL,'2026-11-25 03:10:23'),(2,NULL,NULL,'activate_plugin','core','0.1.0-planning',NULL,'{\"executed_migrations\":[]}',NULL,NULL,'2026-11-25 03:10:40'),(3,NULL,NULL,'create_product','product','SB-BESI-08',NULL,'{\"sku\":\"SB-BESI-08\",\"barcode\":\"899100108001\",\"name\":\"Besi Beton 8mm Polos SNI (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":42000,\"sell_price_retail\":48000,\"sell_price_wholesale\":46000,\"sell_price_project\":44500,\"min_stock\":100,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(4,NULL,NULL,'stock_in','stock','PROD-1-WH-1','{\"qty\":0}','{\"qty\":500,\"delta\":500,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-08\"}',NULL,NULL,'2026-11-25 03:11:46'),(5,NULL,NULL,'create_product','product','SB-BESI-10',NULL,'{\"sku\":\"SB-BESI-10\",\"barcode\":\"899100110001\",\"name\":\"Besi Beton 10mm Ulir SNI (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":65000,\"sell_price_retail\":74000,\"sell_price_wholesale\":71000,\"sell_price_project\":69000,\"min_stock\":80,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(6,NULL,NULL,'stock_in','stock','PROD-2-WH-1','{\"qty\":0}','{\"qty\":400,\"delta\":400,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-10\"}',NULL,NULL,'2026-11-25 03:11:46'),(7,NULL,NULL,'create_product','product','SB-CNL-C75',NULL,'{\"sku\":\"SB-CNL-C75\",\"barcode\":\"899100207501\",\"name\":\"Kanal C75 Baja Ringan 0.75mm (Dummy)\",\"category_id\":2,\"base_unit_id\":1,\"cost_price\":72000,\"sell_price_retail\":82000,\"sell_price_wholesale\":79000,\"sell_price_project\":76000,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(8,NULL,NULL,'stock_in','stock','PROD-3-WH-1','{\"qty\":0}','{\"qty\":350,\"delta\":350,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-CNL-C75\"}',NULL,NULL,'2026-11-25 03:11:46'),(9,NULL,NULL,'create_product','product','SB-RNG-045',NULL,'{\"sku\":\"SB-RNG-045\",\"barcode\":\"899100204501\",\"name\":\"Reng Baja Ringan 0.45mm (Dummy)\",\"category_id\":2,\"base_unit_id\":1,\"cost_price\":34000,\"sell_price_retail\":40000,\"sell_price_wholesale\":38000,\"sell_price_project\":36500,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(10,NULL,NULL,'stock_in','stock','PROD-4-WH-1','{\"qty\":0}','{\"qty\":300,\"delta\":300,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-RNG-045\"}',NULL,NULL,'2026-11-25 03:11:46'),(11,NULL,NULL,'create_product','product','SB-SMN-TR50',NULL,'{\"sku\":\"SB-SMN-TR50\",\"barcode\":\"899100305001\",\"name\":\"Semen Tiga Roda 50kg (Dummy)\",\"category_id\":3,\"base_unit_id\":3,\"cost_price\":62000,\"sell_price_retail\":68000,\"sell_price_wholesale\":66000,\"sell_price_project\":64500,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(12,NULL,NULL,'stock_in','stock','PROD-5-WH-1','{\"qty\":0}','{\"qty\":200,\"delta\":200,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SMN-TR50\"}',NULL,NULL,'2026-11-25 03:11:46'),(13,NULL,NULL,'create_product','product','SB-PSR-PSG',NULL,'{\"sku\":\"SB-PSR-PSG\",\"barcode\":null,\"name\":\"Pasir Pasang Bangunan Super (Dummy)\",\"category_id\":3,\"base_unit_id\":7,\"cost_price\":240000,\"sell_price_retail\":290000,\"sell_price_wholesale\":275000,\"sell_price_project\":260000,\"min_stock\":10,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(14,NULL,NULL,'stock_in','stock','PROD-6-WH-1','{\"qty\":0}','{\"qty\":50,\"delta\":50,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PSR-PSG\"}',NULL,NULL,'2026-11-25 03:11:46'),(15,NULL,NULL,'create_product','product','SB-SPD-035',NULL,'{\"sku\":\"SB-SPD-035\",\"barcode\":\"899100403501\",\"name\":\"Atap Spandek Pasir 0.35mm (Dummy)\",\"category_id\":2,\"base_unit_id\":6,\"cost_price\":45000,\"sell_price_retail\":54000,\"sell_price_wholesale\":51000,\"sell_price_project\":48000,\"min_stock\":30,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(16,NULL,NULL,'stock_in','stock','PROD-7-WH-1','{\"qty\":0}','{\"qty\":150,\"delta\":150,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SPD-035\"}',NULL,NULL,'2026-11-25 03:11:46'),(17,NULL,NULL,'create_product','product','SB-PLW-09MM',NULL,'{\"sku\":\"SB-PLW-09MM\",\"barcode\":\"899100509001\",\"name\":\"Triplek Meranti Cor 9mm 122x244 (Dummy)\",\"category_id\":4,\"base_unit_id\":6,\"cost_price\":85000,\"sell_price_retail\":98000,\"sell_price_wholesale\":94000,\"sell_price_project\":90000,\"min_stock\":20,\"is_active\":1,\"created_at\":\"2026-11-25 03:11:46\",\"updated_at\":\"2026-11-25 03:11:46\"}',NULL,NULL,'2026-11-25 03:11:46'),(18,NULL,NULL,'stock_in','stock','PROD-8-WH-1','{\"qty\":0}','{\"qty\":120,\"delta\":120,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLW-09MM\"}',NULL,NULL,'2026-11-25 03:11:46'),(19,NULL,NULL,'create_product','product','SB-BESI-06',NULL,'{\"sku\":\"SB-BESI-06\",\"barcode\":\"899100106001\",\"name\":\"Besi Beton 6mm Polos SNI (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":28000,\"sell_price_retail\":33000,\"sell_price_wholesale\":31000,\"sell_price_project\":29500,\"min_stock\":100,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(20,NULL,NULL,'stock_in','stock','PROD-9-WH-1','{\"qty\":0}','{\"qty\":600,\"delta\":600,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-06-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(21,NULL,NULL,'stock_in','stock','PROD-9-WH-2','{\"qty\":0}','{\"qty\":100,\"delta\":100,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-06-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(22,NULL,NULL,'stock_in','stock','PROD-1-WH-2','{\"qty\":0}','{\"qty\":100,\"delta\":100,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-08-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(23,NULL,NULL,'stock_in','stock','PROD-2-WH-2','{\"qty\":0}','{\"qty\":80,\"delta\":80,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-10-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(24,NULL,NULL,'create_product','product','SB-BESI-12',NULL,'{\"sku\":\"SB-BESI-12\",\"barcode\":\"899100112001\",\"name\":\"Besi Beton 12mm Ulir SNI (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":96000,\"sell_price_retail\":108000,\"sell_price_wholesale\":104000,\"sell_price_project\":101000,\"min_stock\":60,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(25,NULL,NULL,'stock_in','stock','PROD-10-WH-1','{\"qty\":0}','{\"qty\":300,\"delta\":300,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-12-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(26,NULL,NULL,'stock_in','stock','PROD-10-WH-2','{\"qty\":0}','{\"qty\":50,\"delta\":50,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-12-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(27,NULL,NULL,'create_product','product','SB-BESI-16',NULL,'{\"sku\":\"SB-BESI-16\",\"barcode\":\"899100116001\",\"name\":\"Besi Beton 16mm Ulir SNI (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":170000,\"sell_price_retail\":192000,\"sell_price_wholesale\":185000,\"sell_price_project\":179000,\"min_stock\":40,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(28,NULL,NULL,'stock_in','stock','PROD-11-WH-1','{\"qty\":0}','{\"qty\":200,\"delta\":200,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-16-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(29,NULL,NULL,'stock_in','stock','PROD-11-WH-2','{\"qty\":0}','{\"qty\":30,\"delta\":30,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BESI-16-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(30,NULL,NULL,'create_product','product','SB-HLW-2040',NULL,'{\"sku\":\"SB-HLW-2040\",\"barcode\":\"899100120401\",\"name\":\"Besi Hollow Galvanis 20x40 1.2mm 6m (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":58000,\"sell_price_retail\":68000,\"sell_price_wholesale\":64000,\"sell_price_project\":61000,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(31,NULL,NULL,'stock_in','stock','PROD-12-WH-1','{\"qty\":0}','{\"qty\":350,\"delta\":350,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-HLW-2040-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(32,NULL,NULL,'stock_in','stock','PROD-12-WH-2','{\"qty\":0}','{\"qty\":60,\"delta\":60,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-HLW-2040-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(33,NULL,NULL,'create_product','product','SB-HLW-4040',NULL,'{\"sku\":\"SB-HLW-4040\",\"barcode\":\"899100140401\",\"name\":\"Besi Hollow Galvanis 40x40 1.4mm 6m (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":98000,\"sell_price_retail\":115000,\"sell_price_wholesale\":109000,\"sell_price_project\":104000,\"min_stock\":40,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(34,NULL,NULL,'stock_in','stock','PROD-13-WH-1','{\"qty\":0}','{\"qty\":250,\"delta\":250,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-HLW-4040-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(35,NULL,NULL,'stock_in','stock','PROD-13-WH-2','{\"qty\":0}','{\"qty\":50,\"delta\":50,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-HLW-4040-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(36,NULL,NULL,'create_product','product','SB-PIP-150',NULL,'{\"sku\":\"SB-PIP-150\",\"barcode\":\"899100115001\",\"name\":\"Pipa Besi Galvanis 1.5 Inch Medium (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":125000,\"sell_price_retail\":145000,\"sell_price_wholesale\":138000,\"sell_price_project\":132000,\"min_stock\":30,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(37,NULL,NULL,'stock_in','stock','PROD-14-WH-1','{\"qty\":0}','{\"qty\":180,\"delta\":180,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PIP-150-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(38,NULL,NULL,'stock_in','stock','PROD-14-WH-2','{\"qty\":0}','{\"qty\":30,\"delta\":30,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PIP-150-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(39,NULL,NULL,'create_product','product','SB-PLT-3030',NULL,'{\"sku\":\"SB-PLT-3030\",\"barcode\":\"899100130301\",\"name\":\"Plat Strip 3x30mm 6m (Dummy)\",\"category_id\":1,\"base_unit_id\":1,\"cost_price\":31000,\"sell_price_retail\":38000,\"sell_price_wholesale\":35000,\"sell_price_project\":33000,\"min_stock\":40,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(40,NULL,NULL,'stock_in','stock','PROD-15-WH-1','{\"qty\":0}','{\"qty\":250,\"delta\":250,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLT-3030-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(41,NULL,NULL,'stock_in','stock','PROD-15-WH-2','{\"qty\":0}','{\"qty\":40,\"delta\":40,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLT-3030-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(42,NULL,NULL,'create_product','product','SB-WRM-M06',NULL,'{\"sku\":\"SB-WRM-M06\",\"barcode\":\"899100100601\",\"name\":\"Wiremesh M6 Ulir SNI 2.1x5.4m (Dummy)\",\"category_id\":1,\"base_unit_id\":6,\"cost_price\":295000,\"sell_price_retail\":340000,\"sell_price_wholesale\":325000,\"sell_price_project\":310000,\"min_stock\":20,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(43,NULL,NULL,'stock_in','stock','PROD-16-WH-1','{\"qty\":0}','{\"qty\":120,\"delta\":120,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-WRM-M06-GD1\"}',NULL,NULL,'2026-11-25 04:30:21'),(44,NULL,NULL,'stock_in','stock','PROD-16-WH-2','{\"qty\":0}','{\"qty\":20,\"delta\":20,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-WRM-M06-GD2\"}',NULL,NULL,'2026-11-25 04:30:21'),(45,NULL,NULL,'create_product','product','SB-WRM-M08',NULL,'{\"sku\":\"SB-WRM-M08\",\"barcode\":\"899100100801\",\"name\":\"Wiremesh M8 Ulir SNI 2.1x5.4m (Dummy)\",\"category_id\":1,\"base_unit_id\":6,\"cost_price\":510000,\"sell_price_retail\":580000,\"sell_price_wholesale\":555000,\"sell_price_project\":535000,\"min_stock\":15,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:21\",\"updated_at\":\"2026-11-25 04:30:21\"}',NULL,NULL,'2026-11-25 04:30:21'),(46,NULL,NULL,'stock_in','stock','PROD-17-WH-1','{\"qty\":0}','{\"qty\":80,\"delta\":80,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-WRM-M08-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(47,NULL,NULL,'stock_in','stock','PROD-17-WH-2','{\"qty\":0}','{\"qty\":15,\"delta\":15,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-WRM-M08-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(48,NULL,NULL,'stock_in','stock','PROD-3-WH-2','{\"qty\":0}','{\"qty\":70,\"delta\":70,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-CNL-C75-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(49,NULL,NULL,'create_product','product','SB-CNL-C65',NULL,'{\"sku\":\"SB-CNL-C65\",\"barcode\":\"899100206501\",\"name\":\"Kanal C75 Baja Ringan 0.65mm (Dummy)\",\"category_id\":2,\"base_unit_id\":1,\"cost_price\":62000,\"sell_price_retail\":71000,\"sell_price_wholesale\":68000,\"sell_price_project\":65000,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(50,NULL,NULL,'stock_in','stock','PROD-18-WH-1','{\"qty\":0}','{\"qty\":300,\"delta\":300,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-CNL-C65-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(51,NULL,NULL,'stock_in','stock','PROD-18-WH-2','{\"qty\":0}','{\"qty\":60,\"delta\":60,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-CNL-C65-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(52,NULL,NULL,'stock_in','stock','PROD-4-WH-2','{\"qty\":0}','{\"qty\":60,\"delta\":60,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-RNG-045-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(53,NULL,NULL,'create_product','product','SB-RNG-040',NULL,'{\"sku\":\"SB-RNG-040\",\"barcode\":\"899100204001\",\"name\":\"Reng Baja Ringan 0.40mm (Dummy)\",\"category_id\":2,\"base_unit_id\":1,\"cost_price\":29000,\"sell_price_retail\":35000,\"sell_price_wholesale\":33000,\"sell_price_project\":31000,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(54,NULL,NULL,'stock_in','stock','PROD-19-WH-1','{\"qty\":0}','{\"qty\":300,\"delta\":300,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-RNG-040-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(55,NULL,NULL,'stock_in','stock','PROD-19-WH-2','{\"qty\":0}','{\"qty\":60,\"delta\":60,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-RNG-040-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(56,NULL,NULL,'stock_in','stock','PROD-7-WH-2','{\"qty\":0}','{\"qty\":30,\"delta\":30,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SPD-035-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(57,NULL,NULL,'create_product','product','SB-SPD-030',NULL,'{\"sku\":\"SB-SPD-030\",\"barcode\":\"899100403001\",\"name\":\"Atap Spandek Polos 0.30mm 6m (Dummy)\",\"category_id\":2,\"base_unit_id\":6,\"cost_price\":36000,\"sell_price_retail\":43000,\"sell_price_wholesale\":40500,\"sell_price_project\":38500,\"min_stock\":30,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(58,NULL,NULL,'stock_in','stock','PROD-20-WH-1','{\"qty\":0}','{\"qty\":200,\"delta\":200,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SPD-030-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(59,NULL,NULL,'stock_in','stock','PROD-20-WH-2','{\"qty\":0}','{\"qty\":40,\"delta\":40,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SPD-030-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(60,NULL,NULL,'create_product','product','SB-GTM-2X4',NULL,'{\"sku\":\"SB-GTM-2X4\",\"barcode\":\"899100402401\",\"name\":\"Genteng Metal Pasir 2x4 Daun (Dummy)\",\"category_id\":2,\"base_unit_id\":6,\"cost_price\":32000,\"sell_price_retail\":38500,\"sell_price_wholesale\":36000,\"sell_price_project\":34000,\"min_stock\":40,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(61,NULL,NULL,'stock_in','stock','PROD-21-WH-1','{\"qty\":0}','{\"qty\":250,\"delta\":250,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-GTM-2X4-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(62,NULL,NULL,'stock_in','stock','PROD-21-WH-2','{\"qty\":0}','{\"qty\":50,\"delta\":50,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-GTM-2X4-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(63,NULL,NULL,'stock_in','stock','PROD-5-WH-2','{\"qty\":0}','{\"qty\":40,\"delta\":40,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SMN-TR50-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(64,NULL,NULL,'create_product','product','SB-SMN-GS40',NULL,'{\"sku\":\"SB-SMN-GS40\",\"barcode\":\"899100304001\",\"name\":\"Semen Gresik 40kg (Dummy)\",\"category_id\":3,\"base_unit_id\":3,\"cost_price\":49000,\"sell_price_retail\":54500,\"sell_price_wholesale\":52500,\"sell_price_project\":51000,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(65,NULL,NULL,'stock_in','stock','PROD-22-WH-1','{\"qty\":0}','{\"qty\":250,\"delta\":250,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SMN-GS40-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(66,NULL,NULL,'stock_in','stock','PROD-22-WH-2','{\"qty\":0}','{\"qty\":50,\"delta\":50,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SMN-GS40-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(67,NULL,NULL,'create_product','product','SB-MTR-M100',NULL,'{\"sku\":\"SB-MTR-M100\",\"barcode\":\"899100310001\",\"name\":\"Semen Mortar Perekat Hebel 40kg (Dummy)\",\"category_id\":3,\"base_unit_id\":3,\"cost_price\":63000,\"sell_price_retail\":72000,\"sell_price_wholesale\":69000,\"sell_price_project\":66500,\"min_stock\":40,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(68,NULL,NULL,'stock_in','stock','PROD-23-WH-1','{\"qty\":0}','{\"qty\":180,\"delta\":180,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-MTR-M100-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(69,NULL,NULL,'stock_in','stock','PROD-23-WH-2','{\"qty\":0}','{\"qty\":35,\"delta\":35,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-MTR-M100-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(70,NULL,NULL,'create_product','product','SB-HBL-10CM',NULL,'{\"sku\":\"SB-HBL-10CM\",\"barcode\":\"899100320001\",\"name\":\"Bata Ringan Hebel 10x20x60cm (Dummy)\",\"category_id\":3,\"base_unit_id\":7,\"cost_price\":550000,\"sell_price_retail\":620000,\"sell_price_wholesale\":595000,\"sell_price_project\":580000,\"min_stock\":10,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(71,NULL,NULL,'stock_in','stock','PROD-24-WH-1','{\"qty\":0}','{\"qty\":40,\"delta\":40,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-HBL-10CM-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(72,NULL,NULL,'stock_in','stock','PROD-24-WH-2','{\"qty\":0}','{\"qty\":8,\"delta\":8,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-HBL-10CM-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(73,NULL,NULL,'stock_in','stock','PROD-6-WH-2','{\"qty\":0}','{\"qty\":10,\"delta\":10,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PSR-PSG-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(74,NULL,NULL,'create_product','product','SB-BTU-SPL',NULL,'{\"sku\":\"SB-BTU-SPL\",\"barcode\":null,\"name\":\"Batu Split \\/ Kerikil Cor (Dummy)\",\"category_id\":3,\"base_unit_id\":7,\"cost_price\":260000,\"sell_price_retail\":310000,\"sell_price_wholesale\":290000,\"sell_price_project\":280000,\"min_stock\":10,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(75,NULL,NULL,'stock_in','stock','PROD-25-WH-1','{\"qty\":0}','{\"qty\":45,\"delta\":45,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BTU-SPL-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(76,NULL,NULL,'stock_in','stock','PROD-25-WH-2','{\"qty\":0}','{\"qty\":10,\"delta\":10,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-BTU-SPL-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(77,NULL,NULL,'stock_in','stock','PROD-8-WH-2','{\"qty\":0}','{\"qty\":25,\"delta\":25,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLW-09MM-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(78,NULL,NULL,'create_product','product','SB-PLW-12MM',NULL,'{\"sku\":\"SB-PLW-12MM\",\"barcode\":\"899100512001\",\"name\":\"Triplek Meranti 12mm 122x244 (Dummy)\",\"category_id\":4,\"base_unit_id\":6,\"cost_price\":125000,\"sell_price_retail\":145000,\"sell_price_wholesale\":139000,\"sell_price_project\":133000,\"min_stock\":20,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(79,NULL,NULL,'stock_in','stock','PROD-26-WH-1','{\"qty\":0}','{\"qty\":100,\"delta\":100,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLW-12MM-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(80,NULL,NULL,'stock_in','stock','PROD-26-WH-2','{\"qty\":0}','{\"qty\":20,\"delta\":20,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLW-12MM-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(81,NULL,NULL,'create_product','product','SB-PLW-18MM',NULL,'{\"sku\":\"SB-PLW-18MM\",\"barcode\":\"899100518001\",\"name\":\"Triplek Meranti 18mm 122x244 (Dummy)\",\"category_id\":4,\"base_unit_id\":6,\"cost_price\":185000,\"sell_price_retail\":210000,\"sell_price_wholesale\":202000,\"sell_price_project\":195000,\"min_stock\":15,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(82,NULL,NULL,'stock_in','stock','PROD-27-WH-1','{\"qty\":0}','{\"qty\":80,\"delta\":80,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLW-18MM-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(83,NULL,NULL,'stock_in','stock','PROD-27-WH-2','{\"qty\":0}','{\"qty\":15,\"delta\":15,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PLW-18MM-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(84,NULL,NULL,'create_product','product','SB-GRC-04MM',NULL,'{\"sku\":\"SB-GRC-04MM\",\"barcode\":\"899100504001\",\"name\":\"Papan GRC \\/ Kalsiboard 4mm 122x244 (Dummy)\",\"category_id\":4,\"base_unit_id\":6,\"cost_price\":52000,\"sell_price_retail\":62000,\"sell_price_wholesale\":59000,\"sell_price_project\":56000,\"min_stock\":30,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(85,NULL,NULL,'stock_in','stock','PROD-28-WH-1','{\"qty\":0}','{\"qty\":150,\"delta\":150,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-GRC-04MM-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(86,NULL,NULL,'stock_in','stock','PROD-28-WH-2','{\"qty\":0}','{\"qty\":30,\"delta\":30,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-GRC-04MM-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(87,NULL,NULL,'create_product','product','SB-SKR-ROOF',NULL,'{\"sku\":\"SB-SKR-ROOF\",\"barcode\":\"899100600101\",\"name\":\"Baut Roofing Baja Ringan 10x19 (500 pcs\\/dus) (Dummy)\",\"category_id\":5,\"base_unit_id\":8,\"cost_price\":68000,\"sell_price_retail\":85000,\"sell_price_wholesale\":79000,\"sell_price_project\":74000,\"min_stock\":20,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(88,NULL,NULL,'stock_in','stock','PROD-29-WH-1','{\"qty\":0}','{\"qty\":100,\"delta\":100,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SKR-ROOF-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(89,NULL,NULL,'stock_in','stock','PROD-29-WH-2','{\"qty\":0}','{\"qty\":20,\"delta\":20,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-SKR-ROOF-GD2\"}',NULL,NULL,'2026-11-25 04:30:22'),(90,NULL,NULL,'create_product','product','SB-PKU-KYU',NULL,'{\"sku\":\"SB-PKU-KYU\",\"barcode\":\"899100600201\",\"name\":\"Paku Kayu Campur 5-10cm SNI (Dummy)\",\"category_id\":5,\"base_unit_id\":5,\"cost_price\":14500,\"sell_price_retail\":18000,\"sell_price_wholesale\":16500,\"sell_price_project\":15500,\"min_stock\":50,\"is_active\":1,\"created_at\":\"2026-11-25 04:30:22\",\"updated_at\":\"2026-11-25 04:30:22\"}',NULL,NULL,'2026-11-25 04:30:22'),(91,NULL,NULL,'stock_in','stock','PROD-30-WH-1','{\"qty\":0}','{\"qty\":200,\"delta\":200,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PKU-KYU-GD1\"}',NULL,NULL,'2026-11-25 04:30:22'),(92,NULL,NULL,'stock_in','stock','PROD-30-WH-2','{\"qty\":0}','{\"qty\":40,\"delta\":40,\"ref_type\":\"initial\",\"ref_id\":\"INIT-SB-PKU-KYU-GD2\"}',NULL,NULL,'2026-11-25 04:30:22');
/*!40000 ALTER TABLE `wp_sb_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_cashier_shifts`
--

DROP TABLE IF EXISTS `wp_sb_cashier_shifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_cashier_shifts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cashier_user_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `opened_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` datetime DEFAULT NULL,
  `opening_cash` bigint NOT NULL DEFAULT '0',
  `closing_cash` bigint DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `status` enum('open','closed') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shifts_cashier` (`cashier_user_id`),
  KEY `idx_shifts_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_cashier_shifts`
--

LOCK TABLES `wp_sb_cashier_shifts` WRITE;
/*!40000 ALTER TABLE `wp_sb_cashier_shifts` DISABLE KEYS */;
INSERT INTO `wp_sb_cashier_shifts` VALUES (1,1,1,'2026-11-25 04:13:21','2026-11-25 04:13:44',500000,500000,'Reset test shift','closed','2026-11-25 11:13:21'),(2,1,1,'2026-11-25 04:24:59','2026-11-25 04:33:17',10000000,1000000,'demo','closed','2026-11-25 11:24:59'),(3,1,2,'2026-11-25 04:30:31','2026-11-25 04:30:31',200000,200000,'Close test','closed','2026-11-25 11:30:31'),(4,1,1,'2026-11-25 04:33:31','2026-11-25 04:33:52',10000000,10000000,NULL,'closed','2026-11-25 11:33:31'),(5,1,2,'2026-11-25 04:34:04','2026-11-25 04:34:57',10000000,10000000,NULL,'closed','2026-11-25 11:34:04'),(6,1,2,'2026-11-25 04:37:44','2026-11-25 04:37:44',200000,200000,'Close Test','closed','2026-11-25 11:37:44'),(7,1,2,'2026-11-25 04:38:16',NULL,200000,NULL,'Demo Shift Toko','open','2026-11-25 11:38:16');
/*!40000 ALTER TABLE `wp_sb_cashier_shifts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_categories`
--

DROP TABLE IF EXISTS `wp_sb_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_categories`
--

LOCK TABLES `wp_sb_categories` WRITE;
/*!40000 ALTER TABLE `wp_sb_categories` DISABLE KEYS */;
INSERT INTO `wp_sb_categories` VALUES (1,'Besi & Baja','besi-baja',NULL,'2026-11-25 03:11:46'),(2,'Baja Ringan & Atap','baja-ringan-atap',NULL,'2026-11-25 03:11:46'),(3,'Semen & Pasir','semen-pasir',NULL,'2026-11-25 03:11:46'),(4,'Kayu & Plywood','kayu-plywood',NULL,'2026-11-25 03:11:46'),(5,'Baut & Perlengkapan','baut-perlengkapan',NULL,'2026-11-25 04:30:21');
/*!40000 ALTER TABLE `wp_sb_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_customers`
--

DROP TABLE IF EXISTS `wp_sb_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_520_ci,
  `credit_limit` bigint NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_code` (`code`),
  KEY `idx_customers_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_customers`
--

LOCK TABLES `wp_sb_customers` WRITE;
/*!40000 ALTER TABLE `wp_sb_customers` DISABLE KEYS */;
INSERT INTO `wp_sb_customers` VALUES (1,'CUST-001','CV Bangun Mandiri Perkasa','081298765432',NULL,50000000,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22'),(2,'CUST-002','Mandor Bambang Prakoso','085712345678',NULL,15000000,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22'),(3,'CUST-003','Toko Material Berkah Jaya','087855443322',NULL,30000000,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22'),(4,'CUST-004','PT Cipta Graha Sejahtera','02188997766',NULL,100000000,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22');
/*!40000 ALTER TABLE `wp_sb_customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_document_sequences`
--

DROP TABLE IF EXISTS `wp_sb_document_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_document_sequences` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `doc_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `prefix` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `current_number` int unsigned NOT NULL DEFAULT '0',
  `pad_length` tinyint unsigned NOT NULL DEFAULT '5',
  `reset_period` varchar(10) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'yearly',
  `last_period` varchar(10) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_doc_type` (`doc_type`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_document_sequences`
--

LOCK TABLES `wp_sb_document_sequences` WRITE;
/*!40000 ALTER TABLE `wp_sb_document_sequences` DISABLE KEYS */;
INSERT INTO `wp_sb_document_sequences` VALUES (1,'invoice_resmi','INV-RES/',0,5,'yearly',NULL,'2026-11-25 03:07:42'),(2,'nota_non_resmi','NOT-NR/',0,5,'yearly',NULL,'2026-11-25 03:07:42'),(3,'purchase_order','PO-SB/',0,5,'yearly',NULL,'2026-11-25 03:07:42'),(4,'surat_jalan','SJ-SB/',0,5,'yearly',NULL,'2026-11-25 03:07:42'),(5,'stock_opname','SO-SB/',0,4,'yearly',NULL,'2026-11-25 03:07:42'),(6,'kasbon','KB-SB/',0,4,'yearly',NULL,'2026-11-25 03:07:42'),(8,'stock_transfer','TRF/',0,5,'yearly','2026','0000-00-00 00:00:00');
/*!40000 ALTER TABLE `wp_sb_document_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_payments`
--

DROP TABLE IF EXISTS `wp_sb_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `payment_date` datetime NOT NULL,
  `method` enum('tunai','transfer','qris','kasbon') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'tunai',
  `amount` bigint NOT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_by` bigint unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payments_sale` (`sale_id`),
  KEY `idx_payments_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_payments`
--

LOCK TABLES `wp_sb_payments` WRITE;
/*!40000 ALTER TABLE `wp_sb_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_sb_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_products`
--

DROP TABLE IF EXISTS `wp_sb_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `barcode` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `base_unit_id` bigint unsigned NOT NULL,
  `cost_price` bigint NOT NULL DEFAULT '0' COMMENT 'Harga pokok dalam integer rupiah',
  `sell_price_retail` bigint NOT NULL DEFAULT '0' COMMENT 'Harga eceran rupiah',
  `sell_price_wholesale` bigint NOT NULL DEFAULT '0' COMMENT 'Harga grosir rupiah',
  `sell_price_project` bigint NOT NULL DEFAULT '0' COMMENT 'Harga proyek/kontraktor rupiah',
  `min_stock` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  KEY `name` (`name`(50))
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_products`
--

LOCK TABLES `wp_sb_products` WRITE;
/*!40000 ALTER TABLE `wp_sb_products` DISABLE KEYS */;
INSERT INTO `wp_sb_products` VALUES (1,'SB-BESI-08','899100108001','Besi Beton 8mm Polos SNI (Dummy)',1,1,42000,48000,46000,44500,100.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(2,'SB-BESI-10','899100110001','Besi Beton 10mm Ulir SNI (Dummy)',1,1,65000,74000,71000,69000,80.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(3,'SB-CNL-C75','899100207501','Kanal C75 Baja Ringan 0.75mm (Dummy)',2,1,72000,82000,79000,76000,50.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(4,'SB-RNG-045','899100204501','Reng Baja Ringan 0.45mm (Dummy)',2,1,34000,40000,38000,36500,50.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(5,'SB-SMN-TR50','899100305001','Semen Tiga Roda 50kg (Dummy)',3,3,62000,68000,66000,64500,50.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(6,'SB-PSR-PSG',NULL,'Pasir Pasang Bangunan Super (Dummy)',3,7,240000,290000,275000,260000,10.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(7,'SB-SPD-035','899100403501','Atap Spandek Pasir 0.35mm (Dummy)',2,6,45000,54000,51000,48000,30.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(8,'SB-PLW-09MM','899100509001','Triplek Meranti Cor 9mm 122x244 (Dummy)',4,6,85000,98000,94000,90000,20.0000,1,'2026-11-25 03:11:46','2026-11-25 03:11:46'),(9,'SB-BESI-06','899100106001','Besi Beton 6mm Polos SNI (Dummy)',1,1,28000,33000,31000,29500,100.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(10,'SB-BESI-12','899100112001','Besi Beton 12mm Ulir SNI (Dummy)',1,1,96000,108000,104000,101000,60.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(11,'SB-BESI-16','899100116001','Besi Beton 16mm Ulir SNI (Dummy)',1,1,170000,192000,185000,179000,40.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(12,'SB-HLW-2040','899100120401','Besi Hollow Galvanis 20x40 1.2mm 6m (Dummy)',1,1,58000,68000,64000,61000,50.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(13,'SB-HLW-4040','899100140401','Besi Hollow Galvanis 40x40 1.4mm 6m (Dummy)',1,1,98000,115000,109000,104000,40.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(14,'SB-PIP-150','899100115001','Pipa Besi Galvanis 1.5 Inch Medium (Dummy)',1,1,125000,145000,138000,132000,30.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(15,'SB-PLT-3030','899100130301','Plat Strip 3x30mm 6m (Dummy)',1,1,31000,38000,35000,33000,40.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(16,'SB-WRM-M06','899100100601','Wiremesh M6 Ulir SNI 2.1x5.4m (Dummy)',1,6,295000,340000,325000,310000,20.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(17,'SB-WRM-M08','899100100801','Wiremesh M8 Ulir SNI 2.1x5.4m (Dummy)',1,6,510000,580000,555000,535000,15.0000,1,'2026-11-25 04:30:21','2026-11-25 04:30:21'),(18,'SB-CNL-C65','899100206501','Kanal C75 Baja Ringan 0.65mm (Dummy)',2,1,62000,71000,68000,65000,50.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(19,'SB-RNG-040','899100204001','Reng Baja Ringan 0.40mm (Dummy)',2,1,29000,35000,33000,31000,50.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(20,'SB-SPD-030','899100403001','Atap Spandek Polos 0.30mm 6m (Dummy)',2,6,36000,43000,40500,38500,30.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(21,'SB-GTM-2X4','899100402401','Genteng Metal Pasir 2x4 Daun (Dummy)',2,6,32000,38500,36000,34000,40.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(22,'SB-SMN-GS40','899100304001','Semen Gresik 40kg (Dummy)',3,3,49000,54500,52500,51000,50.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(23,'SB-MTR-M100','899100310001','Semen Mortar Perekat Hebel 40kg (Dummy)',3,3,63000,72000,69000,66500,40.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(24,'SB-HBL-10CM','899100320001','Bata Ringan Hebel 10x20x60cm (Dummy)',3,7,550000,620000,595000,580000,10.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(25,'SB-BTU-SPL',NULL,'Batu Split / Kerikil Cor (Dummy)',3,7,260000,310000,290000,280000,10.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(26,'SB-PLW-12MM','899100512001','Triplek Meranti 12mm 122x244 (Dummy)',4,6,125000,145000,139000,133000,20.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(27,'SB-PLW-18MM','899100518001','Triplek Meranti 18mm 122x244 (Dummy)',4,6,185000,210000,202000,195000,15.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(28,'SB-GRC-04MM','899100504001','Papan GRC / Kalsiboard 4mm 122x244 (Dummy)',4,6,52000,62000,59000,56000,30.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(29,'SB-SKR-ROOF','899100600101','Baut Roofing Baja Ringan 10x19 (500 pcs/dus) (Dummy)',5,8,68000,85000,79000,74000,20.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22'),(30,'SB-PKU-KYU','899100600201','Paku Kayu Campur 5-10cm SNI (Dummy)',5,5,14500,18000,16500,15500,50.0000,1,'2026-11-25 04:30:22','2026-11-25 04:30:22');
/*!40000 ALTER TABLE `wp_sb_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_sale_items`
--

DROP TABLE IF EXISTS `wp_sb_sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_sale_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `unit_id` bigint unsigned NOT NULL,
  `qty` decimal(15,4) NOT NULL,
  `unit_price` bigint NOT NULL,
  `discount_amount` bigint NOT NULL DEFAULT '0',
  `line_total` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sale_items_sale` (`sale_id`),
  KEY `idx_sale_items_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_sale_items`
--

LOCK TABLES `wp_sb_sale_items` WRITE;
/*!40000 ALTER TABLE `wp_sb_sale_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_sb_sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_sales`
--

DROP TABLE IF EXISTS `wp_sb_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_sales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `doc_number` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `invoice_type` enum('resmi','non_resmi') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'non_resmi',
  `sale_date` date NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `shift_id` bigint unsigned DEFAULT NULL,
  `cashier_user_id` bigint unsigned NOT NULL,
  `subtotal` bigint NOT NULL DEFAULT '0',
  `discount_amount` bigint NOT NULL DEFAULT '0',
  `tax_amount` bigint NOT NULL DEFAULT '0',
  `grand_total` bigint NOT NULL DEFAULT '0',
  `paid_amount` bigint NOT NULL DEFAULT '0',
  `status` enum('draft','terbit','lunas','batal') COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'draft',
  `void_reason` text COLLATE utf8mb4_unicode_520_ci,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_doc_number` (`doc_number`),
  KEY `idx_sales_date` (`sale_date`),
  KEY `idx_sales_customer` (`customer_id`),
  KEY `idx_sales_status` (`status`),
  KEY `idx_sales_shift` (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_sales`
--

LOCK TABLES `wp_sb_sales` WRITE;
/*!40000 ALTER TABLE `wp_sb_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_sb_sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_schema_versions`
--

DROP TABLE IF EXISTS `wp_sb_schema_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_schema_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `applied_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_version` (`version`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_schema_versions`
--

LOCK TABLES `wp_sb_schema_versions` WRITE;
/*!40000 ALTER TABLE `wp_sb_schema_versions` DISABLE KEYS */;
INSERT INTO `wp_sb_schema_versions` VALUES (1,'Migration_001_Initial_Core','2026-11-25 03:07:42'),(2,'Migration_002_Inventory','2026-11-25 03:07:43'),(3,'Migration_003_Sales','2026-11-25 03:10:23');
/*!40000 ALTER TABLE `wp_sb_schema_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_stock_balances`
--

DROP TABLE IF EXISTS `wp_sb_stock_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_stock_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_warehouse` (`product_id`,`warehouse_id`),
  KEY `warehouse_id` (`warehouse_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_stock_balances`
--

LOCK TABLES `wp_sb_stock_balances` WRITE;
/*!40000 ALTER TABLE `wp_sb_stock_balances` DISABLE KEYS */;
INSERT INTO `wp_sb_stock_balances` VALUES (1,1,1,500.0000,'2026-11-25 03:11:46'),(2,2,1,400.0000,'2026-11-25 03:11:46'),(3,3,1,350.0000,'2026-11-25 03:11:46'),(4,4,1,300.0000,'2026-11-25 03:11:46'),(5,5,1,200.0000,'2026-11-25 03:11:46'),(6,6,1,50.0000,'2026-11-25 03:11:46'),(7,7,1,150.0000,'2026-11-25 03:11:46'),(8,8,1,120.0000,'2026-11-25 03:11:46'),(9,9,1,600.0000,'2026-11-25 04:30:21'),(10,9,2,100.0000,'2026-11-25 04:30:21'),(11,1,2,100.0000,'2026-11-25 04:30:21'),(12,2,2,80.0000,'2026-11-25 04:30:21'),(13,10,1,300.0000,'2026-11-25 04:30:21'),(14,10,2,50.0000,'2026-11-25 04:30:21'),(15,11,1,200.0000,'2026-11-25 04:30:21'),(16,11,2,30.0000,'2026-11-25 04:30:21'),(17,12,1,350.0000,'2026-11-25 04:30:21'),(18,12,2,60.0000,'2026-11-25 04:30:21'),(19,13,1,250.0000,'2026-11-25 04:30:21'),(20,13,2,50.0000,'2026-11-25 04:30:21'),(21,14,1,180.0000,'2026-11-25 04:30:21'),(22,14,2,30.0000,'2026-11-25 04:30:21'),(23,15,1,250.0000,'2026-11-25 04:30:21'),(24,15,2,40.0000,'2026-11-25 04:30:21'),(25,16,1,120.0000,'2026-11-25 04:30:21'),(26,16,2,20.0000,'2026-11-25 04:30:21'),(27,17,1,80.0000,'2026-11-25 04:30:22'),(28,17,2,15.0000,'2026-11-25 04:30:22'),(29,3,2,70.0000,'2026-11-25 04:30:22'),(30,18,1,300.0000,'2026-11-25 04:30:22'),(31,18,2,60.0000,'2026-11-25 04:30:22'),(32,4,2,60.0000,'2026-11-25 04:30:22'),(33,19,1,300.0000,'2026-11-25 04:30:22'),(34,19,2,60.0000,'2026-11-25 04:30:22'),(35,7,2,30.0000,'2026-11-25 04:30:22'),(36,20,1,200.0000,'2026-11-25 04:30:22'),(37,20,2,40.0000,'2026-11-25 04:30:22'),(38,21,1,250.0000,'2026-11-25 04:30:22'),(39,21,2,50.0000,'2026-11-25 04:30:22'),(40,5,2,40.0000,'2026-11-25 04:30:22'),(41,22,1,250.0000,'2026-11-25 04:30:22'),(42,22,2,50.0000,'2026-11-25 04:30:22'),(43,23,1,180.0000,'2026-11-25 04:30:22'),(44,23,2,35.0000,'2026-11-25 04:30:22'),(45,24,1,40.0000,'2026-11-25 04:30:22'),(46,24,2,8.0000,'2026-11-25 04:30:22'),(47,6,2,10.0000,'2026-11-25 04:30:22'),(48,25,1,45.0000,'2026-11-25 04:30:22'),(49,25,2,10.0000,'2026-11-25 04:30:22'),(50,8,2,25.0000,'2026-11-25 04:30:22'),(51,26,1,100.0000,'2026-11-25 04:30:22'),(52,26,2,20.0000,'2026-11-25 04:30:22'),(53,27,1,80.0000,'2026-11-25 04:30:22'),(54,27,2,15.0000,'2026-11-25 04:30:22'),(55,28,1,150.0000,'2026-11-25 04:30:22'),(56,28,2,30.0000,'2026-11-25 04:30:22'),(57,29,1,100.0000,'2026-11-25 04:30:22'),(58,29,2,20.0000,'2026-11-25 04:30:22'),(59,30,1,200.0000,'2026-11-25 04:30:22'),(60,30,2,40.0000,'2026-11-25 04:30:22');
/*!40000 ALTER TABLE `wp_sb_stock_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_stock_moves`
--

DROP TABLE IF EXISTS `wp_sb_stock_moves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_stock_moves` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `move_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ref_type` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL COMMENT 'initial, purchase_receipt, sale_pos, sale_invoice, adjustment, transfer',
  `ref_id` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `product_id` bigint unsigned NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `qty_change` decimal(12,4) NOT NULL COMMENT 'Positif: masuk, Negatif: keluar',
  `unit_id` bigint unsigned NOT NULL,
  `unit_cost` bigint NOT NULL DEFAULT '0',
  `balance_after` decimal(12,4) NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_date` (`product_id`,`move_date`),
  KEY `warehouse_id` (`warehouse_id`),
  KEY `ref_lookup` (`ref_type`,`ref_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_stock_moves`
--

LOCK TABLES `wp_sb_stock_moves` WRITE;
/*!40000 ALTER TABLE `wp_sb_stock_moves` DISABLE KEYS */;
INSERT INTO `wp_sb_stock_moves` VALUES (1,'2026-11-25 03:11:46','initial','INIT-SB-BESI-08',1,1,500.0000,1,42000,500.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(2,'2026-11-25 03:11:46','initial','INIT-SB-BESI-10',2,1,400.0000,1,65000,400.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(3,'2026-11-25 03:11:46','initial','INIT-SB-CNL-C75',3,1,350.0000,1,72000,350.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(4,'2026-11-25 03:11:46','initial','INIT-SB-RNG-045',4,1,300.0000,1,34000,300.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(5,'2026-11-25 03:11:46','initial','INIT-SB-SMN-TR50',5,1,200.0000,3,62000,200.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(6,'2026-11-25 03:11:46','initial','INIT-SB-PSR-PSG',6,1,50.0000,7,240000,50.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(7,'2026-11-25 03:11:46','initial','INIT-SB-SPD-035',7,1,150.0000,6,45000,150.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(8,'2026-11-25 03:11:46','initial','INIT-SB-PLW-09MM',8,1,120.0000,6,85000,120.0000,'Saldo stok awal dummy data',0,'2026-11-25 03:11:46'),(9,'2026-11-25 04:30:21','initial','INIT-SB-BESI-06-GD1',9,1,600.0000,1,28000,600.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(10,'2026-11-25 04:30:21','initial','INIT-SB-BESI-06-GD2',9,2,100.0000,1,28000,100.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(11,'2026-11-25 04:30:21','initial','INIT-SB-BESI-08-GD2',1,2,100.0000,1,42000,100.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(12,'2026-11-25 04:30:21','initial','INIT-SB-BESI-10-GD2',2,2,80.0000,1,65000,80.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(13,'2026-11-25 04:30:21','initial','INIT-SB-BESI-12-GD1',10,1,300.0000,1,96000,300.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(14,'2026-11-25 04:30:21','initial','INIT-SB-BESI-12-GD2',10,2,50.0000,1,96000,50.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(15,'2026-11-25 04:30:21','initial','INIT-SB-BESI-16-GD1',11,1,200.0000,1,170000,200.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(16,'2026-11-25 04:30:21','initial','INIT-SB-BESI-16-GD2',11,2,30.0000,1,170000,30.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(17,'2026-11-25 04:30:21','initial','INIT-SB-HLW-2040-GD1',12,1,350.0000,1,58000,350.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(18,'2026-11-25 04:30:21','initial','INIT-SB-HLW-2040-GD2',12,2,60.0000,1,58000,60.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(19,'2026-11-25 04:30:21','initial','INIT-SB-HLW-4040-GD1',13,1,250.0000,1,98000,250.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(20,'2026-11-25 04:30:21','initial','INIT-SB-HLW-4040-GD2',13,2,50.0000,1,98000,50.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(21,'2026-11-25 04:30:21','initial','INIT-SB-PIP-150-GD1',14,1,180.0000,1,125000,180.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(22,'2026-11-25 04:30:21','initial','INIT-SB-PIP-150-GD2',14,2,30.0000,1,125000,30.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(23,'2026-11-25 04:30:21','initial','INIT-SB-PLT-3030-GD1',15,1,250.0000,1,31000,250.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(24,'2026-11-25 04:30:21','initial','INIT-SB-PLT-3030-GD2',15,2,40.0000,1,31000,40.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(25,'2026-11-25 04:30:21','initial','INIT-SB-WRM-M06-GD1',16,1,120.0000,6,295000,120.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:21'),(26,'2026-11-25 04:30:21','initial','INIT-SB-WRM-M06-GD2',16,2,20.0000,6,295000,20.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:21'),(27,'2026-11-25 04:30:22','initial','INIT-SB-WRM-M08-GD1',17,1,80.0000,6,510000,80.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(28,'2026-11-25 04:30:22','initial','INIT-SB-WRM-M08-GD2',17,2,15.0000,6,510000,15.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(29,'2026-11-25 04:30:22','initial','INIT-SB-CNL-C75-GD2',3,2,70.0000,1,72000,70.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(30,'2026-11-25 04:30:22','initial','INIT-SB-CNL-C65-GD1',18,1,300.0000,1,62000,300.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(31,'2026-11-25 04:30:22','initial','INIT-SB-CNL-C65-GD2',18,2,60.0000,1,62000,60.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(32,'2026-11-25 04:30:22','initial','INIT-SB-RNG-045-GD2',4,2,60.0000,1,34000,60.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(33,'2026-11-25 04:30:22','initial','INIT-SB-RNG-040-GD1',19,1,300.0000,1,29000,300.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(34,'2026-11-25 04:30:22','initial','INIT-SB-RNG-040-GD2',19,2,60.0000,1,29000,60.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(35,'2026-11-25 04:30:22','initial','INIT-SB-SPD-035-GD2',7,2,30.0000,6,45000,30.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(36,'2026-11-25 04:30:22','initial','INIT-SB-SPD-030-GD1',20,1,200.0000,6,36000,200.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(37,'2026-11-25 04:30:22','initial','INIT-SB-SPD-030-GD2',20,2,40.0000,6,36000,40.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(38,'2026-11-25 04:30:22','initial','INIT-SB-GTM-2X4-GD1',21,1,250.0000,6,32000,250.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(39,'2026-11-25 04:30:22','initial','INIT-SB-GTM-2X4-GD2',21,2,50.0000,6,32000,50.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(40,'2026-11-25 04:30:22','initial','INIT-SB-SMN-TR50-GD2',5,2,40.0000,3,62000,40.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(41,'2026-11-25 04:30:22','initial','INIT-SB-SMN-GS40-GD1',22,1,250.0000,3,49000,250.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(42,'2026-11-25 04:30:22','initial','INIT-SB-SMN-GS40-GD2',22,2,50.0000,3,49000,50.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(43,'2026-11-25 04:30:22','initial','INIT-SB-MTR-M100-GD1',23,1,180.0000,3,63000,180.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(44,'2026-11-25 04:30:22','initial','INIT-SB-MTR-M100-GD2',23,2,35.0000,3,63000,35.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(45,'2026-11-25 04:30:22','initial','INIT-SB-HBL-10CM-GD1',24,1,40.0000,7,550000,40.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(46,'2026-11-25 04:30:22','initial','INIT-SB-HBL-10CM-GD2',24,2,8.0000,7,550000,8.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(47,'2026-11-25 04:30:22','initial','INIT-SB-PSR-PSG-GD2',6,2,10.0000,7,240000,10.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(48,'2026-11-25 04:30:22','initial','INIT-SB-BTU-SPL-GD1',25,1,45.0000,7,260000,45.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(49,'2026-11-25 04:30:22','initial','INIT-SB-BTU-SPL-GD2',25,2,10.0000,7,260000,10.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(50,'2026-11-25 04:30:22','initial','INIT-SB-PLW-09MM-GD2',8,2,25.0000,6,85000,25.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(51,'2026-11-25 04:30:22','initial','INIT-SB-PLW-12MM-GD1',26,1,100.0000,6,125000,100.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(52,'2026-11-25 04:30:22','initial','INIT-SB-PLW-12MM-GD2',26,2,20.0000,6,125000,20.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(53,'2026-11-25 04:30:22','initial','INIT-SB-PLW-18MM-GD1',27,1,80.0000,6,185000,80.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(54,'2026-11-25 04:30:22','initial','INIT-SB-PLW-18MM-GD2',27,2,15.0000,6,185000,15.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(55,'2026-11-25 04:30:22','initial','INIT-SB-GRC-04MM-GD1',28,1,150.0000,6,52000,150.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(56,'2026-11-25 04:30:22','initial','INIT-SB-GRC-04MM-GD2',28,2,30.0000,6,52000,30.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(57,'2026-11-25 04:30:22','initial','INIT-SB-SKR-ROOF-GD1',29,1,100.0000,8,68000,100.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(58,'2026-11-25 04:30:22','initial','INIT-SB-SKR-ROOF-GD2',29,2,20.0000,8,68000,20.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22'),(59,'2026-11-25 04:30:22','initial','INIT-SB-PKU-KYU-GD1',30,1,200.0000,5,14500,200.0000,'Saldo awal dummy data Gudang Utama',0,'2026-11-25 04:30:22'),(60,'2026-11-25 04:30:22','initial','INIT-SB-PKU-KYU-GD2',30,2,40.0000,5,14500,40.0000,'Saldo awal dummy data Gudang Toko',0,'2026-11-25 04:30:22');
/*!40000 ALTER TABLE `wp_sb_stock_moves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_stock_opname_items`
--

DROP TABLE IF EXISTS `wp_sb_stock_opname_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_stock_opname_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opname_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `system_qty` decimal(12,4) NOT NULL,
  `physical_qty` decimal(12,4) NOT NULL,
  `diff_qty` decimal(12,4) NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opname_id` (`opname_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_stock_opname_items`
--

LOCK TABLES `wp_sb_stock_opname_items` WRITE;
/*!40000 ALTER TABLE `wp_sb_stock_opname_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_sb_stock_opname_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_stock_opnames`
--

DROP TABLE IF EXISTS `wp_sb_stock_opnames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_stock_opnames` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `opname_number` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `warehouse_id` bigint unsigned NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'draft' COMMENT 'draft, approved, cancelled',
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `opname_number` (`opname_number`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_stock_opnames`
--

LOCK TABLES `wp_sb_stock_opnames` WRITE;
/*!40000 ALTER TABLE `wp_sb_stock_opnames` DISABLE KEYS */;
/*!40000 ALTER TABLE `wp_sb_stock_opnames` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_suppliers`
--

DROP TABLE IF EXISTS `wp_sb_suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_520_ci,
  `notes` text COLLATE utf8mb4_unicode_520_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_suppliers_code` (`code`),
  KEY `idx_suppliers_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_suppliers`
--

LOCK TABLES `wp_sb_suppliers` WRITE;
/*!40000 ALTER TABLE `wp_sb_suppliers` DISABLE KEYS */;
INSERT INTO `wp_sb_suppliers` VALUES (1,'SUPP-001','PT Krakatau Steel (Persero) Tbk','0213854123',NULL,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22'),(2,'SUPP-002','PT Kencana Maju Bersama','0215432987',NULL,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22'),(3,'SUPP-003','PT Indocement Tunggal Prakarsa','0218765432',NULL,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22'),(4,'SUPP-004','Distributor Kayu Makmur Sentosa','081399887766',NULL,NULL,1,'2026-11-25 11:30:22','2026-11-25 11:30:22');
/*!40000 ALTER TABLE `wp_sb_suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_unit_conversions`
--

DROP TABLE IF EXISTS `wp_sb_unit_conversions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_unit_conversions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned DEFAULT NULL COMMENT 'NULL jika konversi standar global',
  `from_unit_id` bigint unsigned NOT NULL,
  `to_unit_id` bigint unsigned NOT NULL,
  `multiplier` decimal(12,4) NOT NULL COMMENT '1 from_unit = multiplier * to_unit',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_lookup` (`product_id`,`from_unit_id`,`to_unit_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_unit_conversions`
--

LOCK TABLES `wp_sb_unit_conversions` WRITE;
/*!40000 ALTER TABLE `wp_sb_unit_conversions` DISABLE KEYS */;
INSERT INTO `wp_sb_unit_conversions` VALUES (1,NULL,4,3,20.0000,'2026-11-25 03:11:46'),(2,NULL,4,5,1000.0000,'2026-11-25 03:11:46'),(3,1,2,1,25.0000,'2026-11-25 03:11:46'),(4,2,2,1,20.0000,'2026-11-25 03:11:46'),(5,NULL,4,3,20.0000,'2026-11-25 04:30:21'),(6,NULL,4,5,1000.0000,'2026-11-25 04:30:21'),(7,9,2,1,30.0000,'2026-11-25 04:30:21');
/*!40000 ALTER TABLE `wp_sb_unit_conversions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_units`
--

DROP TABLE IF EXISTS `wp_sb_units`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_units` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_units`
--

LOCK TABLES `wp_sb_units` WRITE;
/*!40000 ALTER TABLE `wp_sb_units` DISABLE KEYS */;
INSERT INTO `wp_sb_units` VALUES (1,'btg','Batang','2026-11-25 03:11:45'),(2,'ikat','Ikat','2026-11-25 03:11:45'),(3,'zak','Zak','2026-11-25 03:11:45'),(4,'ton','Ton','2026-11-25 03:11:45'),(5,'kg','Kilogram','2026-11-25 03:11:45'),(6,'lbr','Lembar','2026-11-25 03:11:45'),(7,'m3','Meter Kubik','2026-11-25 03:11:45'),(8,'dus','Dus / Box','2026-11-25 04:30:21'),(9,'pcs','Pcs','2026-11-25 04:30:21');
/*!40000 ALTER TABLE `wp_sb_units` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wp_sb_warehouses`
--

DROP TABLE IF EXISTS `wp_sb_warehouses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wp_sb_warehouses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_520_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_520_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wp_sb_warehouses`
--

LOCK TABLES `wp_sb_warehouses` WRITE;
/*!40000 ALTER TABLE `wp_sb_warehouses` DISABLE KEYS */;
INSERT INTO `wp_sb_warehouses` VALUES (1,'GD-UTAMA','Gudang Utama Setu','Jl. Raya Setu No. 88, Bekasi',1,'2026-11-25 03:11:45'),(2,'GD-TOKO','Gudang Toko & Display','Area Depan Toko Sagara Baja',1,'2026-11-25 03:11:45');
/*!40000 ALTER TABLE `wp_sb_warehouses` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-25 11:42:58
