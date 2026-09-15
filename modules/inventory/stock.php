<?php
require_once __DIR__ . '/../../includes/auth.php';require_once __DIR__ . '/../../includes/functions.php';require_once __DIR__ . '/../../includes/inventory.php';requireLogin();
$db=getDB();$id=(int)(isset($_GET['id'])?$_GET['id']:0);$stmt=$db->prepare("SELECT * FROM products WHERE id=? AND active=1");$stmt->execute([$id]);$product=$stmt->fetch();if(!$product)redirect('/xamp-cosmic/modules/inventory/index.php','Product not found','error');
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!verifyCSRFToken(isset($_POST['csrf_token'])?$_POST['csrf_token']:''))$errors[]='Your session expired. Please try again.';
 else{
  $action=isset($_POST['action'])?$_POST['action']:'add';$quantity=isset($_POST['quantity'])?(float)$_POST['quantity']:0;$notes=trim(isset($_POST['notes'])?$_POST['notes']:'');
  if($action==='add'&&$quantity<=0)$errors[]='Enter a quantity greater than zero.';
  if($action==='set'&&!isAdmin())$errors[]='Only an administrator can correct stock.';
  if($action==='set'&&$quantity<0)$errors[]='Stock cannot be negative.';
  if($action==='set'&&$notes==='')$errors[]='Please enter a reason for the correction.';
  if(!$errors){try{$db->beginTransaction();$lock=$db->prepare("SELECT stock_count FROM products WHERE id=? AND active=1 FOR UPDATE");$lock->execute([$id]);$before=(float)$lock->fetchColumn();$after=$action==='set'?$quantity:$before+$quantity;$change=$after-$before;$db->prepare("UPDATE products SET stock_count=? WHERE id=?")->execute([$after,$id]);recordStockMovement($db,$id,$action==='set'?'adjustment':'stock_in',$change,$before,$after,$_SESSION['user_id'],$notes?:($action==='set'?'Stock corrected by administrator':'Stock received'));$db->commit();redirect('/xamp-cosmic/modules/inventory/history.php?id='.$id,$action==='set'?'Stock corrected successfully':'Stock added successfully');}catch(Exception $e){if($db->inTransaction())$db->rollBack();$errors[]=$e->getMessage();}}
 }
}
$pageTitle='Update Stock';require_once __DIR__ . '/../../includes/header.php';
?>
<div class="page-header"><div><span class="eyebrow">Stock entry</span><h1><?php echo e($product['name']);?></h1><p class="page-subtitle"><?php echo $product['barcode']?'Barcode '.e($product['barcode']):'Add a delivery to the current balance.';?></p></div><a class="btn btn-secondary" href="/xamp-cosmic/modules/inventory/history.php?id=<?php echo $id;?>">History</a></div>
<?php if($errors):?><div class="alert alert-danger"><?php foreach($errors as $error):?><div><?php echo e($error);?></div><?php endforeach;?></div><?php endif;?>
<div class="stock-entry-layout"><div class="card current-stock-panel"><span>Current stock</span><strong><?php echo formatStock($product['stock_count']);?></strong><small><?php echo e($product['unit']);?></small></div><form method="POST" class="card mobile-form stock-form"><?php echo csrfField();?><input type="hidden" name="action" value="add"><div class="form-group"><label for="quantity">How many arrived?</label><input id="quantity" type="number" name="quantity" class="form-control quantity-input" min="0.01" step="0.01" required autofocus inputmode="decimal" placeholder="0"></div><div class="form-group"><label for="notes">Note (optional)</label><input id="notes" name="notes" class="form-control form-control-lg" maxlength="500" placeholder="Example: Supplier delivery"></div><button class="btn btn-success btn-block btn-xl">Add to stock</button></form></div>
<?php if(isAdmin()):?><details class="card admin-adjustment"><summary>Admin: correct the stock balance</summary><form method="POST" class="mt-20"><?php echo csrfField();?><input type="hidden" name="action" value="set"><div class="form-row"><div class="form-group"><label>Set exact quantity</label><input type="number" name="quantity" class="form-control" min="0" step="0.01" required value="<?php echo e($product['stock_count']);?>"></div><div class="form-group form-grow-2"><label>Reason *</label><input name="notes" class="form-control" required maxlength="500" placeholder="Why is this correction needed?"></div></div><button class="btn btn-danger">Save correction</button></form></details><?php endif;?>
<?php require_once __DIR__ . '/../../includes/footer.php';?>
