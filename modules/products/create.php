<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/inventory.php';
requireLogin();
$pageTitle = 'Add Product';

$errors = [];
$product = ['name'=>'','hsn_code'=>'','barcode'=>'','gst_rate'=>'5.00','mrp'=>'','unit'=>'Nos','opening_stock'=>'0','low_stock_threshold'=>'5'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['barcode'])) $product['barcode'] = preg_replace('/\s+/', '', trim($_GET['barcode']));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        foreach (array_keys($product) as $key) if (isset($_POST[$key])) $product[$key] = trim($_POST[$key]);
        $product['barcode'] = preg_replace('/\s+/', '', $product['barcode']);
        if ($product['name'] === '') $errors[] = 'Product name is required.';
        if (!is_numeric($product['opening_stock']) || (float)$product['opening_stock'] < 0) $errors[] = 'Opening stock must be zero or more.';
        if (!is_numeric($product['low_stock_threshold']) || (float)$product['low_stock_threshold'] < 0) $errors[] = 'Low-stock alert must be zero or more.';
        if (!$errors) {
            try {
                $imagePath = saveProductImage(isset($_FILES['image']) ? $_FILES['image'] : null);
                $db = getDB(); $db->beginTransaction();
                $stmt = $db->prepare("INSERT INTO products (name,hsn_code,barcode,image_path,gst_rate,mrp,unit,stock_count,low_stock_threshold) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$product['name'],$product['hsn_code']?:null,$product['barcode']?:null,$imagePath,$product['gst_rate'],$product['mrp']!==''?$product['mrp']:null,$product['unit'],$product['opening_stock'],$product['low_stock_threshold']]);
                $productId = $db->lastInsertId();
                recordStockMovement($db,$productId,'product_created',0,$product['opening_stock'],$product['opening_stock'],$_SESSION['user_id'],'Product created');
                if ((float)$product['opening_stock'] > 0) recordStockMovement($db,$productId,'opening',$product['opening_stock'],0,$product['opening_stock'],$_SESSION['user_id'],'Opening stock');
                $db->commit();
                redirect('/xamp-cosmic/modules/inventory/history.php?id='.$productId,'Product added successfully');
            } catch (Exception $e) {
                if (isset($db) && $db->inTransaction()) $db->rollBack();
                $errors[] = strpos($e->getMessage(),'Duplicate') !== false ? 'That barcode is already used by another product.' : $e->getMessage();
            }
        }
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><span class="eyebrow">Product master</span><h1>Add a product</h1><p class="page-subtitle">Only the essentials. You can add more stock at any time.</p></div><a href="/xamp-cosmic/modules/inventory/index.php" class="btn btn-secondary">Back</a></div>
<?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $error): ?><div><?php echo e($error); ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data" class="card mobile-form">
<?php echo csrfField(); ?>
<div class="form-section-title">Product details</div>
<div class="form-row"><div class="form-group form-grow-2"><label for="name">Product name *</label><input type="text" id="name" name="name" class="form-control form-control-lg" required autofocus value="<?php echo e($product['name']); ?>" placeholder="Example: Surgical Gloves Medium"></div><div class="form-group"><label for="barcode">Barcode</label><div class="input-action"><input type="text" id="barcode" name="barcode" class="form-control form-control-lg" inputmode="numeric" value="<?php echo e($product['barcode']); ?>" placeholder="Scan or type"><button type="button" class="btn btn-scan" data-barcode-target="barcode">Scan</button></div></div></div>
<div class="form-row"><div class="form-group"><label for="hsn_code">HSN code</label><input type="text" id="hsn_code" name="hsn_code" class="form-control" value="<?php echo e($product['hsn_code']); ?>"></div><div class="form-group"><label for="mrp">MRP (Rs.)</label><input type="number" id="mrp" name="mrp" class="form-control" step="0.01" min="0" value="<?php echo e($product['mrp']); ?>"></div><div class="form-group"><label for="gst_rate">GST</label><select id="gst_rate" name="gst_rate" class="form-control"><?php foreach ([0,5,12,18,28] as $rate): ?><option value="<?php echo $rate; ?>" <?php echo (float)$product['gst_rate']===(float)$rate?'selected':''; ?>><?php echo $rate; ?>%</option><?php endforeach; ?></select></div><div class="form-group"><label for="unit">Unit</label><select id="unit" name="unit" class="form-control"><?php foreach (['Nos','Pair','Box','Pack','Kg','Ltr','Mtr'] as $unit): ?><option value="<?php echo $unit; ?>" <?php echo $product['unit']===$unit?'selected':''; ?>><?php echo $unit; ?></option><?php endforeach; ?></select></div></div>
<div class="form-section-title">Starting inventory</div>
<div class="form-row"><div class="form-group"><label for="opening_stock">Quantity on hand</label><input type="number" id="opening_stock" name="opening_stock" class="form-control form-control-lg" min="0" step="0.01" value="<?php echo e($product['opening_stock']); ?>"></div><div class="form-group"><label for="low_stock_threshold">Warn me at</label><input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control form-control-lg" min="0" step="0.01" value="<?php echo e($product['low_stock_threshold']); ?>"><small class="field-help">Shown as low stock at this quantity.</small></div><div class="form-group form-grow-2"><label for="image">Product photo</label><input type="file" id="image" name="image" class="form-control file-input" accept="image/jpeg,image/png,image/webp" capture="environment"><small class="field-help">Take a photo or choose one. Maximum 5 MB.</small></div></div>
<div class="sticky-form-actions"><button type="submit" class="btn btn-primary btn-lg">Save product</button><a href="/xamp-cosmic/modules/inventory/index.php" class="btn btn-secondary btn-lg">Cancel</a></div>
</form>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
