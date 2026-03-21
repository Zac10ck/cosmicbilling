<?php
/**
 * Edit Product
 * COSMIC SURGICALS - Invoice Management System
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect('/xamp-cosmic/modules/products/index.php', 'Invalid product', 'error');
}

// Handle delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("UPDATE products SET active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    redirect('/xamp-cosmic/modules/products/index.php', 'Product deleted successfully');
}

// Get product
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/xamp-cosmic/modules/products/index.php', 'Product not found', 'error');
}

$pageTitle = 'Edit Product';
require_once __DIR__ . '/../../includes/header.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission';
    } else {
        $product = [
            'id' => $id,
            'name' => trim($_POST['name'] ?? ''),
            'hsn_code' => trim($_POST['hsn_code'] ?? ''),
            'gst_rate' => $_POST['gst_rate'] ?? '5.00',
            'mrp' => $_POST['mrp'] ?? '',
            'unit' => trim($_POST['unit'] ?? 'Nos'),
            'stock_count' => $_POST['stock_count'] ?? '0'
        ];

        // Validate
        if (empty($product['name'])) {
            $errors[] = 'Product name is required';
        }

        if (empty($errors)) {
            $stmt = $db->prepare("UPDATE products SET name = ?, hsn_code = ?, gst_rate = ?, mrp = ?, unit = ?, stock_count = ? WHERE id = ?");
            $stmt->execute([
                $product['name'],
                $product['hsn_code'] ?: null,
                $product['gst_rate'],
                $product['mrp'] ?: null,
                $product['unit'],
                (int)$product['stock_count'],
                $id
            ]);

            redirect('/xamp-cosmic/modules/products/index.php', 'Product updated successfully');
        }
    }
}
?>

<div class="page-header">
    <h1>Edit Product</h1>
    <a href="/xamp-cosmic/modules/products/index.php" class="btn btn-secondary">Back to List</a>
</div>

<div class="card">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrfField(); ?>

        <div class="form-row">
            <div class="form-group">
                <label for="name">Product Name *</label>
                <input type="text" id="name" name="name" class="form-control" required
                       value="<?php echo e($product['name']); ?>">
            </div>

            <div class="form-group">
                <label for="hsn_code">HSN Code</label>
                <input type="text" id="hsn_code" name="hsn_code" class="form-control"
                       value="<?php echo e($product['hsn_code']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="mrp">MRP (Rs.)</label>
                <input type="number" id="mrp" name="mrp" class="form-control" step="0.01" min="0"
                       value="<?php echo e($product['mrp']); ?>">
            </div>

            <div class="form-group">
                <label for="gst_rate">GST Rate (%)</label>
                <select id="gst_rate" name="gst_rate" class="form-control">
                    <option value="0" <?php echo $product['gst_rate'] == '0' ? 'selected' : ''; ?>>0%</option>
                    <option value="5" <?php echo $product['gst_rate'] == '5' || $product['gst_rate'] == '5.00' ? 'selected' : ''; ?>>5%</option>
                    <option value="12" <?php echo $product['gst_rate'] == '12' ? 'selected' : ''; ?>>12%</option>
                    <option value="18" <?php echo $product['gst_rate'] == '18' ? 'selected' : ''; ?>>18%</option>
                    <option value="28" <?php echo $product['gst_rate'] == '28' ? 'selected' : ''; ?>>28%</option>
                </select>
            </div>

            <div class="form-group">
                <label for="unit">Unit</label>
                <select id="unit" name="unit" class="form-control">
                    <option value="Nos" <?php echo $product['unit'] === 'Nos' ? 'selected' : ''; ?>>Nos</option>
                    <option value="Pair" <?php echo $product['unit'] === 'Pair' ? 'selected' : ''; ?>>Pair</option>
                    <option value="Box" <?php echo $product['unit'] === 'Box' ? 'selected' : ''; ?>>Box</option>
                    <option value="Pack" <?php echo $product['unit'] === 'Pack' ? 'selected' : ''; ?>>Pack</option>
                    <option value="Kg" <?php echo $product['unit'] === 'Kg' ? 'selected' : ''; ?>>Kg</option>
                    <option value="Ltr" <?php echo $product['unit'] === 'Ltr' ? 'selected' : ''; ?>>Ltr</option>
                    <option value="Mtr" <?php echo $product['unit'] === 'Mtr' ? 'selected' : ''; ?>>Mtr</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="stock_count">Stock Count</label>
                <input type="number" id="stock_count" name="stock_count" class="form-control" min="0" step="1"
                       value="<?php echo e($product['stock_count']); ?>">
            </div>
        </div>

        <div class="mt-20">
            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="/xamp-cosmic/modules/products/index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
