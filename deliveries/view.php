<?php
/**
 * Delivery Details and Status Management
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

$db = Database::getInstance()->getConnection();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect_to('deliveries/index.php');
}

// Fetch Delivery
$sql = "
    SELECT 
        d.*,
        c.name as customer_name, c.customer_code, c.phone, c.email,
        l.name as source_location_name,
        a.username as creator_name
    FROM deliveries d
    JOIN customers c ON d.customer_id = c.id
    LEFT JOIN locations l ON d.source_location_id = l.id
    LEFT JOIN admins a ON d.created_by = a.id
    WHERE d.id = ?
";
$stmt = $db->prepare($sql);
$stmt->execute([$id]);
$delivery = $stmt->fetch();

if (!$delivery) {
    redirect_to('deliveries/index.php');
}

// Fetch Items
$items_stmt = $db->prepare("
    SELECT di.*, p.product_name, p.sku, p.quantity as global_stock 
    FROM delivery_items di
    JOIN products p ON di.product_id = p.id
    WHERE di.delivery_id = ?
");
$items_stmt->execute([$id]);
$items = $items_stmt->fetchAll();

// Fetch Logs
$logs_stmt = $db->prepare("
    SELECT dl.*, a.username 
    FROM delivery_status_logs dl
    JOIN admins a ON dl.changed_by = a.id
    WHERE dl.delivery_id = ?
    ORDER BY dl.created_at DESC
");
$logs_stmt->execute([$id]);
$logs = $logs_stmt->fetchAll();

// Dynamic Badge
$b_class = 'badge-secondary';
switch($delivery['delivery_status']) {
    case 'pending': $b_class = 'badge-secondary'; break;
    case 'packed': $b_class = 'badge-info'; break;
    case 'dispatched':
    case 'in_transit': $b_class = 'badge-primary'; break;
    case 'delivered': $b_class = 'badge-success'; break;
    case 'cancelled':
    case 'returned': $b_class = 'badge-danger'; break;
}

$page_title = "Delivery " . $delivery['delivery_code'];
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card mb-4">
                    <div class="card-header justify-between">
                            <h3 class="card-title">Order #<?php echo htmlspecialchars($delivery['delivery_code']); ?></h3>
                            <div class="no-print btn-group">
                                <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨️ Print</button>
                                <?php if(in_array($delivery['delivery_status'], ['pending', 'packed', 'dispatched', 'in_transit'])): ?>
                                    <button onclick="document.getElementById('statusModal').style.display='block'" class="btn btn-secondary btn-sm">Update Status</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                            <div>
                                <h5>Delivery Info</h5>
                                <p><strong>Status:</strong> <span class="badge <?php echo $b_class; ?>"><?php echo ucfirst($delivery['delivery_status']); ?></span></p>
                                <p><strong>Date Created:</strong> <?php echo date('M j, Y H:i', strtotime($delivery['created_at'])); ?></p>
                                <p><strong>Source:</strong> <?php echo htmlspecialchars($delivery['source_location_name'] ?? 'Global Stock'); ?></p>
                                <p><strong>Created By:</strong> <?php echo htmlspecialchars($delivery['creator_name']); ?></p>
                            </div>
                            <div>
                                <h5>Customer details</h5>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($delivery['customer_name']); ?> (<?php echo htmlspecialchars($delivery['customer_code']); ?>)</p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($delivery['phone'] ?? '-'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($delivery['email'] ?? '-'); ?></p>
                            </div>
                            <div>
                                <h5>Destination</h5>
                                <p><strong>City:</strong> <?php echo htmlspecialchars($delivery['destination_city'] ?? '-'); ?></p>
                                <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($delivery['destination_address'] ?? '-')); ?></p>
                                <p><strong>Expected:</strong> <?php echo $delivery['expected_date'] ? date('M j, Y', strtotime($delivery['expected_date'])) : '-'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header"><h3 class="card-title">Items</h3></div>
                    <div class="card-body p-0">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $i): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($i['sku']); ?></code></td>
                                    <td><?php echo htmlspecialchars($i['product_name']); ?></td>
                                    <td><strong><?php echo number_format($i['quantity']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card no-print">
                    <div class="card-header"><h3 class="card-title">Status History</h3></div>
                    <div class="card-body">
                        <ul class="status-timeline">
                            <?php foreach($logs as $l): ?>
                            <li>
                                <strong><?php echo ucfirst($l['new_status']); ?></strong> 
                                - <small class="text-muted"><?php echo date('M j, Y H:i', strtotime($l['created_at'])); ?> by <?php echo htmlspecialchars($l['username']); ?></small>
                                <?php if($l['notes']): ?>
                                    <p class="mb-0 mt-1"><small><?php echo htmlspecialchars($l['notes']); ?></small></p>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">
        <div style="background:#fff; max-width:400px; margin:100px auto; padding:20px; border-radius:8px;">
            <h4>Update Delivery Status</h4>
            <form action="update_status.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                <input type="hidden" name="delivery_id" value="<?php echo $id; ?>">
                
                <div class="form-group mt-3">
                    <label>New Status</label>
                    <select name="status" class="form-select" required>
                        <?php
                        $opts = ['pending', 'packed', 'dispatched', 'in_transit', 'delivered', 'returned', 'cancelled'];
                        foreach($opts as $o) {
                            if($o === $delivery['delivery_status']) continue;
                            echo "<option value=\"$o\">".ucfirst($o)."</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Return Location (If returning)</label>
                    <select name="return_location_id" class="form-select">
                        <option value="">-- Main Stock / Undefined --</option>
                        <?php 
                        $locs = $db->query("SELECT id, name FROM locations WHERE status='active'")->fetchAll();
                        foreach($locs as $l) echo "<option value='{$l['id']}'>".htmlspecialchars($l['name'])."</option>";
                        ?>
                    </select>
                    <small class="text-muted">Only required if status = Returned</small>
                </div>
                
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                
                <div class="mt-4" style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('statusModal').style.display='none'">Close</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        
            </main>
<?php require_once '../includes/footer.php'; ?>
