-- Stock Management System (SMS) Database Schema
-- MySQL Database with UTF-8 support, indexes, and foreign keys

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `stock_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `stock_management`;

-- Table structure for `admins`
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for `categories`
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_category_name` (`name`),
  KEY `idx_category_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for `products`
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(200) NOT NULL,
  `sku` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock_level` int(11) NOT NULL DEFAULT 10,
  `status` enum('in_stock','low_stock','out_of_stock','damaged') NOT NULL DEFAULT 'out_of_stock',
  `location` varchar(100) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT NULL,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `qr_code_value` varchar(255) NOT NULL,
  `qr_code_path` varchar(500) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_sku` (`sku`),
  UNIQUE KEY `idx_qr_code` (`qr_code_value`),
  KEY `idx_product_name` (`product_name`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status` (`status`),
  KEY `idx_quantity` (`quantity`),
  KEY `idx_location` (`location`),
  KEY `idx_supplier` (`supplier`),
  KEY `idx_updated` (`last_updated`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table structure for `stock_logs`
DROP TABLE IF EXISTS `stock_logs`;
CREATE TABLE `stock_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action_type` enum('create','update','delete','stock_in','stock_out','adjustment','scan_update') NOT NULL,
  `old_quantity` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `old_status` enum('in_stock','low_stock','out_of_stock','damaged') DEFAULT NULL,
  `new_status` enum('in_stock','low_stock','out_of_stock','damaged') DEFAULT NULL,
  `old_location` varchar(100) DEFAULT NULL,
  `new_location` varchar(100) DEFAULT NULL,
  `notes` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_product_admin` (`product_id`, `admin_id`),
  CONSTRAINT `fk_stock_logs_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_stock_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO `admins` (`username`, `email`, `password_hash`, `role`, `status`) VALUES
('admin', 'admin@sms.local', '$2y$10$bMluB1y70d3vorsS.UVJpuG4niyH.PPqP/SroDTKoY/SoOrVcWaEe', 'admin', 'active');

-- Insert default categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Electronics', 'Electronic devices and components'),
('Office Supplies', 'Office and administrative supplies'),
('Raw Materials', 'Manufacturing raw materials'),
('Finished Products', 'Ready-to-sell products'),
('Tools & Equipment', 'Tools and manufacturing equipment'),
('Miscellaneous', 'Other uncategorized items');

-- Create indexes for better performance
CREATE INDEX `idx_products_search` ON `products` (`product_name`, `sku`);
CREATE INDEX `idx_stock_logs_date_product` ON `stock_logs` (`created_at`, `product_id`);
CREATE INDEX `idx_products_stock_status` ON `products` (`quantity`, `status`);

-- Create triggers for automatic stock status updates
DELIMITER //

-- Trigger to update stock status when quantity changes
DROP TRIGGER IF EXISTS `update_stock_status` //
CREATE TRIGGER `update_stock_status` 
BEFORE UPDATE ON `products`
FOR EACH ROW
BEGIN
    -- Auto-update stock status based on quantity
    IF NEW.quantity = 0 THEN
        SET NEW.status = 'out_of_stock';
    ELSEIF NEW.quantity <= NEW.min_stock_level THEN
        SET NEW.status = 'low_stock';
    ELSEIF NEW.status != 'damaged' THEN
        SET NEW.status = 'in_stock';
    END IF;
    
    -- Update last_updated timestamp
    SET NEW.last_updated = CURRENT_TIMESTAMP;
END //

-- Trigger to set initial stock status for new products
DROP TRIGGER IF EXISTS `set_initial_stock_status` //
CREATE TRIGGER `set_initial_stock_status`
BEFORE INSERT ON `products`
FOR EACH ROW
BEGIN
    -- Set initial stock status based on quantity
    IF NEW.quantity = 0 THEN
        SET NEW.status = 'out_of_stock';
    ELSEIF NEW.quantity <= NEW.min_stock_level THEN
        SET NEW.status = 'low_stock';
    ELSE
        SET NEW.status = 'in_stock';
    END IF;
    
    -- Set initial timestamps
    SET NEW.last_updated = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- Create views for common queries
CREATE OR REPLACE VIEW `v_low_stock_products` AS
SELECT 
    p.id,
    p.product_name,
    p.sku,
    c.name as category_name,
    p.quantity,
    p.min_stock_level,
    p.status,
    p.location,
    p.last_updated
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE p.status = 'low_stock' OR p.status = 'out_of_stock'
ORDER BY p.quantity ASC, p.last_updated DESC;

CREATE OR REPLACE VIEW `v_recent_stock_changes` AS
SELECT 
    sl.id,
    p.product_name,
    p.sku,
    a.username as admin_username,
    sl.action_type,
    sl.old_quantity,
    sl.new_quantity,
    sl.old_status,
    sl.new_status,
    sl.created_at
FROM stock_logs sl
JOIN products p ON sl.product_id = p.id
JOIN admins a ON sl.admin_id = a.id
ORDER BY sl.created_at DESC
LIMIT 100;

CREATE OR REPLACE VIEW `v_product_summary` AS
SELECT 
    p.id,
    p.product_name,
    p.sku,
    c.name as category_name,
    p.quantity,
    p.min_stock_level,
    p.status,
    p.location,
    p.supplier,
    p.purchase_date,
    p.qr_code_value,
    p.last_updated,
    (SELECT COUNT(*) FROM stock_logs WHERE product_id = p.id) as log_count,
    (SELECT created_at FROM stock_logs WHERE product_id = p.id ORDER BY created_at DESC LIMIT 1) as last_log_date
FROM products p
JOIN categories c ON p.category_id = c.id;

COMMIT;

-- Performance optimization
ANALYZE TABLE admins, categories, products, stock_logs;