<?php
/**
 * Create Delivery Order
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
$delivery_id = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $code = trim($_POST['delivery_code']);
        $customer_id = intval($_POST['customer_id']);
        $source_loc_id = !empty($_POST['source_location_id']) ? intval($_POST['source_location_id']) : null;
        $expected_date = !empty($_POST['expected_date']) ? $_POST['expected_date'] : null;
        $address = trim($_POST['destination_address']);
        $city = trim($_POST['destination_city']);
        $notes = trim($_POST['notes']);
        
        $products = $_POST['products'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        
        if (empty($code) || empty($customer_id) || empty($products)) {
            $error = "Code, Customer, and at least one Product are required.";
        } else {
            try {
                $db->beginTransaction();
                
                // Validate stock locally against source location (or generally)
                foreach ($products as $index => $pid) {
                    $pid = intval($pid);
                    $qty = intval($quantities[$index] ?? 0);
                    if ($qty <= 0) {
                        throw new Exception("Invalid quantity for product in list.");
                    }
                    
                    if ($source_loc_id) {
                        $stmt = $db->prepare("SELECT quantity FROM product_locations WHERE product_id = ? AND location_id = ?");
                        $stmt->execute([$pid, $source_loc_id]);
                        $loc_qty = $stmt->fetchColumn();
                        if ($loc_qty === false || $loc_qty < $qty) {
                            $stmt = $db->prepare("SELECT product_name FROM products WHERE id = ?");
                            $stmt->execute([$pid]);
                            $name = $stmt->fetchColumn();
                            throw new Exception("Insufficient stock for '$name' at the selected source location.");
                        }
                    } else {
                        // Validate Global Stock
                        $stmt = $db->prepare("SELECT quantity, product_name FROM products WHERE id = ?");
                        $stmt->execute([$pid]);
                        $pdata = $stmt->fetch();
                        if (!$pdata || $pdata['quantity'] < $qty) {
                            throw new Exception("Insufficient global stock for '".$pdata['product_name']."'.");
                        }
                    }
                }
                
                // Insert Delivery (Option A / B logic: We deduct on DISPATCH, so we just CREATE pending here)
                $stmt = $db->prepare("INSERT INTO deliveries (delivery_code, customer_id, source_location_id, expected_date, destination_address, destination_city, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $customer_id, $source_loc_id, $expected_date, $address, $city, $notes, $admin['id']]);
                
                $delivery_id = $db->lastInsertId();
                
                // Insert items
                $stmt_items = $db->prepare("INSERT INTO delivery_items (delivery_id, product_id, quantity) VALUES (?, ?, ?)");
                foreach ($products as $index => $pid) {
                    $pid = intval($pid);
                    $qty = intval($quantities[$index]);
                    $stmt_items->execute([$delivery_id, $pid, $qty]);
                }
                
                // Log Creation
                $log_stmt = $db->prepare("INSERT INTO delivery_status_logs (delivery_id, new_status, changed_by, notes) VALUES (?, 'pending', ?, 'Delivery created via admin')");
                $log_stmt->execute([$delivery_id, $admin['id']]);
                
                $db->commit();
                $success = "Delivery order created successfully.";
            } catch (PDOException $e) {
                $db->rollBack();
                if ($e->getCode() == 23000) {
                    $error = "Delivery Code must be unique.";
                } else {
                    $error = "Database Error: " . $e->getMessage();
                }
            } catch (Exception $e) {
                $db->rollBack();
                $error = $e->getMessage();
            }
        }
    }
}

// Data for Dropdowns
$customers = $db->query("SELECT id, name, customer_code FROM customers ORDER BY name")->fetchAll();
$locations = $db->query("SELECT id, name, location_code FROM locations WHERE status = 'active' ORDER BY name")->fetchAll();

// Product JSON list for Dynamic UI
$prod_stmt = $db->query("SELECT p.id, p.product_name, p.sku, p.quantity as global_qty FROM products p ORDER BY p.product_name");
$available_products = $prod_stmt->fetchAll();

$page_title = "Create Delivery";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card" style="max-width: 900px; margin: 0 auto;">
                    <div class="card-header">
                        <h3 class="card-title">Create Delivery Order</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="view.php?id=<?php echo $delivery_id; ?>">View Delivery</a></div><?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">Delivery Code *</label>
                                    <input type="text" name="delivery_code" class="form-control" required placeholder="DLV-<?php echo time(); ?>" value="DLV-<?php echo time(); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Customer *</label>
                                    <select name="customer_id" class="form-select" required>
                                        <option value="">-- Select Customer --</option>
                                        <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' [' . $c['customer_code'] . ']'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Source Location</label>
                                    <select name="source_location_id" class="form-select">
                                        <option value="">-- Dynamic (No specific source yet) --</option>
                                        <?php foreach ($locations as $l): ?>
                                        <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['name'] . ' [' . $l['location_code'] . ']'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted">If selected, items will be strictly reserved and dispatched from this location.</small>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Expected Date</label>
                                    <input type="date" name="expected_date" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Destination City</label>
                                    <input type="text" name="destination_city" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Destination Address</label>
                                    <textarea name="destination_address" class="form-control" rows="2"></textarea>
                                </div>
                            </div>
                            
                            <hr style="margin: 20px 0;">
                            <h4>Delivery Items</h4>
                            <table class="table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th style="width: 150px;">Quantity</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <!-- Dynamic rows go here -->
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addRow()">+ Add Product</button>
                            
                            <div class="form-group mt-4">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary">Create Delivery</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>
