<?php
$pageTitle='Add Stock';require_once __DIR__ . '/../../includes/header.php';
$db=getDB();$products=$db->query("SELECT id,name,barcode,stock_count,unit FROM products WHERE active=1 ORDER BY name")->fetchAll();
?>
<div class="page-header"><div><span class="eyebrow">Fast entry</span><h1>Add stock</h1><p class="page-subtitle">Scan a barcode or choose a product.</p></div><a class="btn btn-secondary" href="/xamp-cosmic/modules/inventory/index.php">Back</a></div>
<div class="card quick-stock-card"><div class="form-group"><label>Scan or search product</label><div class="input-action"><input id="quickProductSearch" class="form-control form-control-lg" placeholder="Product name or barcode" autocomplete="off"><button type="button" class="btn btn-scan btn-lg" data-barcode-target="quickProductSearch">Scan</button></div></div><div id="quickProductResults" class="quick-results"></div></div>
<script>window.inventoryProducts=<?php echo json_encode($products,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);?>;</script>
<?php require_once __DIR__ . '/../../includes/footer.php';?>
