<?php
/**
 * Transfer Stock Between Locations
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $product_id = intval($_POST['product_id']);
        $source_id = intval($_POST['source_location']);
        $dest_id = intval($_POST['dest_location']);
        $qty = intval($_POST['quantity']);
        
        if ($qty <= 0) {
            $error = "Transfer quantity must be greater than zero.";
        } elseif ($source_id === $dest_id) {
            $error = "Source and destination locations must be different.";
        } else {
            try {
                $db->beginTransaction();
                
                // 1. Verify source has enough stock
                $stmt = $db->prepare("SELECT quantity FROM product_locations WHERE product_id = ? AND location_id = ? FOR UPDATE");
                $stmt->execute([$product_id, $source_id]);
                $source_stock = $stmt->fetchColumn();
                
                if ($source_stock === false || $source_stock < $qty) {
                    throw new Exception("Insufficient stock in the source location.");
                }
                
                // 2. Deduct from source
                $stmt = $db->prepare("UPDATE product_locations SET quantity = quantity - ? WHERE product_id = ? AND location_id = ?");
                $stmt->execute([$qty, $product_id, $source_id]);
                
                // 3. Add to destination (Insert or Update)
                $stmt = $db->prepare("SELECT id FROM product_locations WHERE product_id = ? AND location_id = ? FOR UPDATE");
                $stmt->execute([$product_id, $dest_id]);
                if ($stmt->fetchColumn()) {
                    $stmt = $db->prepare("UPDATE product_locations SET quantity = quantity + ? WHERE product_id = ? AND location_id = ?");
                    $stmt->execute([$qty, $product_id, $dest_id]);
                } else {
                    $stmt = $db->prepare("INSERT INTO product_locations (product_id, location_id, quantity, is_primary) VALUES (?, ?, ?, 0)");
                    $stmt->execute([$product_id, $dest_id, $qty]);
                }
                
                // 4. Log the transfer properly in stock_logs (re-using action_type 'adjustment' or we could add 'transfer' if schema supported it. Since schema doesn't have 'transfer' in enum, we use 'adjustment' and indicate carefully in notes).
                // Our schema update allows 'adjustment'.
                $notes = "Transferred $qty units from location ID $source_id to location ID $dest_id";
                $stmt = $db->prepare("INSERT INTO stock_logs (product_id, admin_id, action_type, notes, ip_address) VALUES (?, ?, 'adjustment', ?, ?)");
                $stmt->execute([$product_id, $admin['id'], $notes, $_SERVER['REMOTE_ADDR']]);
                
                $db->commit();
                $success = "Successfully transferred $qty units.";
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}

// Get all products that actually have some assigned location stock
$products = $db->query("
    SELECT p.id, p.product_name, p.sku, SUM(pl.quantity) as total_loc_stock 
    FROM products p 
    JOIN product_locations pl ON p.id = pl.product_id 
    GROUP BY p.id HAVING total_loc_stock > 0
    ORDER BY p.product_name
")->fetchAll();

$locations = $db->query("SELECT id, name, location_code FROM locations WHERE status = 'active' ORDER BY name")->fetchAll();

$page_title = "Transfer Stock";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div class="card-header">
                        <h3 class="card-title">Transfer Stock Between Locations</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Product *</label>
                                <select name="product_id" class="form-select" required id="product-select">
                                    <option value="">-- Select Product --</option>
                                    <?php foreach ($products as $p): ?>
                                    <option value="<?php echo $p['id']; ?>">
                                        <?php echo htmlspecialchars($p['product_name'] . ' (' . $p['sku'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Source Location *</label>
                                <select name="source_location" class="form-select" required id="source-select">
                                    <option value="">-- Select Source --</option>
                                    <?php foreach ($locations as $l): ?>
                                    <option value="<?php echo $l['id']; ?>">
                                        <?php echo htmlspecialchars($l['name'] . ' [' . $l['location_code'] . ']'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Ensure the product exists in this location.</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Destination Location *</label>
                                <select name="dest_location" class="form-select" required>
                                    <option value="">-- Select Destination --</option>
                                    <?php foreach ($locations as $l): ?>
                                    <option value="<?php echo $l['id']; ?>">
                                        <?php echo htmlspecialchars($l['name'] . ' [' . $l['location_code'] . ']'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Quantity to Transfer *</label>
                                <input type="number" name="quantity" class="form-control" required min="1" value="1">
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Execute Transfer</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>
