-- COSMIC SURGICALS Inventory V2 upgrade
-- Tested for MySQL 5.7+ / MariaDB 10.2+ (XAMPP)
-- IMPORTANT: Back up cosmic_billing before running this script in phpMyAdmin.

USE cosmic_billing;

-- The procedure makes the upgrade safe for both the office backup (which has
-- no stock_count yet) and repositories where migration 001 was already run.
DROP PROCEDURE IF EXISTS upgrade_inventory_v2;
DELIMITER $$
CREATE PROCEDURE upgrade_inventory_v2()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'stock_count') THEN
        ALTER TABLE products ADD COLUMN stock_count DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER unit;
    ELSE
        ALTER TABLE products MODIFY COLUMN stock_count DECIMAL(12,2) NOT NULL DEFAULT 0;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'barcode') THEN
        ALTER TABLE products ADD COLUMN barcode VARCHAR(100) NULL AFTER hsn_code;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_path') THEN
        ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL AFTER barcode;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND COLUMN_NAME = 'low_stock_threshold') THEN
        ALTER TABLE products ADD COLUMN low_stock_threshold DECIMAL(12,2) NOT NULL DEFAULT 5 AFTER stock_count;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products' AND INDEX_NAME = 'uq_products_barcode') THEN
        ALTER TABLE products ADD UNIQUE KEY uq_products_barcode (barcode);
    END IF;
END$$
DELIMITER ;

CALL upgrade_inventory_v2();
DROP PROCEDURE upgrade_inventory_v2;

CREATE TABLE IF NOT EXISTS stock_movements (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT NOT NULL,
    movement_type VARCHAR(30) NOT NULL,
    quantity_change DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_before DECIMAL(12,2) NOT NULL DEFAULT 0,
    stock_after DECIMAL(12,2) NOT NULL DEFAULT 0,
    reference_type VARCHAR(30) NULL,
    reference_id INT NULL,
    notes VARCHAR(500) NULL,
    details TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_stock_product_date (product_id, created_at),
    KEY idx_stock_reference (reference_type, reference_id),
    KEY idx_stock_created_by (created_by),
    CONSTRAINT fk_stock_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_stock_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create an opening audit entry for every existing product.
INSERT INTO stock_movements
    (product_id, movement_type, quantity_change, stock_before, stock_after, notes, created_by)
SELECT p.id, 'opening', p.stock_count, 0, p.stock_count,
       'Opening balance recorded during Inventory V2 upgrade', NULL
FROM products p
WHERE NOT EXISTS (
    SELECT 1 FROM stock_movements sm WHERE sm.product_id = p.id
);
