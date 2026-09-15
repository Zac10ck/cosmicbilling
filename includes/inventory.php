<?php
/**
 * Inventory helpers shared by product, stock and invoice workflows.
 */

require_once __DIR__ . '/../config/database.php';

function recordStockMovement($db, $productId, $type, $quantityChange, $before, $after, $userId, $notes = null, $referenceType = null, $referenceId = null, $details = null) {
    $stmt = $db->prepare("INSERT INTO stock_movements
        (product_id, movement_type, quantity_change, stock_before, stock_after,
         reference_type, reference_id, notes, details, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $productId,
        $type,
        $quantityChange,
        $before,
        $after,
        $referenceType,
        $referenceId,
        $notes ?: null,
        $details ?: null,
        $userId
    ]);
}

function stockMovementLabel($type) {
    $labels = [
        'opening' => 'Opening stock',
        'stock_in' => 'Stock added',
        'sale' => 'Sold on invoice',
        'adjustment' => 'Admin correction',
        'product_created' => 'Product created',
        'product_updated' => 'Product updated',
        'product_deactivated' => 'Product deactivated'
    ];
    return isset($labels[$type]) ? $labels[$type] : ucfirst(str_replace('_', ' ', $type));
}

function stockBadgeClass($stock, $threshold) {
    if ((float)$stock <= 0) return 'danger';
    if ((float)$stock <= (float)$threshold) return 'warning';
    return 'success';
}

function saveProductImage($file, $existingPath = null) {
    if (!$file || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return $existingPath;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('The product image could not be uploaded. Please try again.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new Exception('Product image must be smaller than 5 MB.');
    }

    $imageInfo = @getimagesize($file['tmp_name']);
    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];
    if (!$imageInfo || !isset($allowed[$imageInfo[2]])) {
        throw new Exception('Please choose a JPG, PNG or WebP image.');
    }

    $uploadDir = dirname(__DIR__) . '/uploads/products';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        throw new Exception('Product image folder is not writable.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$imageInfo[2]];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename)) {
        throw new Exception('The product image could not be saved.');
    }

    return '/uploads/products/' . $filename;
}
