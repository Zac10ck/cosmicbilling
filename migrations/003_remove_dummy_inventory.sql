-- COSMIC SURGICALS - Remove the eight default demo products
-- Safe cleanup: does not touch invoices, customers, users, or real products.
-- A demo product is skipped if it has stock, a barcode/image, or is linked
-- to an invoice. Back up cosmic_billing before running this in phpMyAdmin.

USE cosmic_billing;
START TRANSACTION;

CREATE TEMPORARY TABLE dummy_products_to_remove (
    id INT PRIMARY KEY
);

INSERT INTO dummy_products_to_remove (id)
SELECT p.id
FROM products p
WHERE p.stock_count = 0
  AND p.barcode IS NULL
  AND p.image_path IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM invoice_items ii WHERE ii.product_id = p.id
  )
  AND (
      (p.name = 'Surgical Gloves (Pair)' AND p.hsn_code = '40151110' AND p.mrp = 45.00) OR
      (p.name = 'Cotton Roll 500g' AND p.hsn_code = '30051010' AND p.mrp = 180.00) OR
      (p.name = 'Bandage Crepe 6"' AND p.hsn_code = '30059010' AND p.mrp = 85.00) OR
      (p.name = 'Syringe 5ml Disposable' AND p.hsn_code = '90183100' AND p.mrp = 8.00) OR
      (p.name = 'Surgical Mask (Box of 50)' AND p.hsn_code = '63079090' AND p.mrp = 250.00) OR
      (p.name = 'Betadine Solution 100ml' AND p.hsn_code = '30049099' AND p.mrp = 125.00) OR
      (p.name = 'ORS Sachets' AND p.hsn_code = '30049099' AND p.mrp = 22.00) OR
      (p.name = 'Digital Thermometer' AND p.hsn_code = '90251990' AND p.mrp = 150.00)
  );

-- Audit rows must be removed first because they intentionally reference products.
DELETE sm
FROM stock_movements sm
INNER JOIN dummy_products_to_remove d ON d.id = sm.product_id;

DELETE p
FROM products p
INNER JOIN dummy_products_to_remove d ON d.id = p.id;

SET @dummy_products_removed = ROW_COUNT();
COMMIT;

SELECT @dummy_products_removed AS dummy_products_removed;
DROP TEMPORARY TABLE dummy_products_to_remove;
