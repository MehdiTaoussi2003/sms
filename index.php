<?php
/**
 * Admin Dashboard
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'auth/auth_check.php';
require_once 'auth/csrf.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

try {
    $db = Database::getInstance()->getConnection();
    
    // Get dashboard statistics
    $stats = [];
    
    // Total products
    $stmt = $db->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $stmt->fetch()['count'];
    
    // Low stock products
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'low_stock'");
    $stats['low_stock'] = $stmt->fetch()['count'];
    
    // Out of stock products
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'out_of_stock'");
    $stats['out_of_stock'] = $stmt->fetch()['count'];
    
    // Recently updated products (last 24 hours)
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE last_updated >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stats['recent_updates'] = $stmt->fetch()['count'];
    
    // Get low stock products
    $stmt = $db->query("
        SELECT p.id, p.product_name, p.sku, c.name as category_name, p.quantity, p.min_stock_level, p.status, p.last_updated
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.status IN ('low_stock', 'out_of_stock')
        ORDER BY p.quantity ASC, p.last_updated DESC 
        LIMIT 10
    ");
    $low_stock_products = $stmt->fetchAll();
    
    // Get recent stock changes
    $stmt = $db->query("
        SELECT sl.id, p.product_name, p.sku, a.username, sl.action_type, sl.old_quantity, sl.new_quantity, sl.created_at
        FROM stock_logs sl
        JOIN products p ON sl.product_id = p.id
        JOIN admins a ON sl.admin_id = a.id
        ORDER BY sl.created_at DESC
        LIMIT 10
    ");
    $recent_changes = $stmt->fetchAll();
    
    // Get pending deliveries
    $stmt = $db->query("SELECT COUNT(*) as count FROM deliveries WHERE delivery_status = 'pending'");
    $stats['pending_deliveries'] = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = ['total_products' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'recent_updates' => 0, 'pending_deliveries' => 0];
    $low_stock_products = [];
    $recent_changes = [];
}

$page_title = "Dashboard";
?>
<?php require_once 'includes/header.php'; ?>
<?php require_once 'includes/sidebar.php'; ?>
<?php require_once 'includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2"><div class="stat-label m-0">Total Products</div><svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg></div>
                        <div class="stat-number total"><?php echo number_format($stats['total_products']); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2"><div class="stat-label m-0">Low Stock</div><svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg></div>
                        <div class="stat-number low"><?php echo number_format($stats['low_stock']); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2"><div class="stat-label m-0">Out of Stock</div><svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg></div>
                        <div class="stat-number out"><?php echo number_format($stats['out_of_stock']); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2"><div class="stat-label m-0">Updated Today</div><svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg></div>
                        <div class="stat-number recent"><?php echo number_format($stats['recent_updates']); ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="flex items-center justify-between mb-2"><div class="stat-label m-0">Pending Deliveries</div><svg class="stat-icon" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg></div>
                        <div class="stat-number" style="color: var(--color-primary);"><?php echo number_format($stats['pending_deliveries']); ?></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <!-- Low Stock Alert -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Stock Alerts</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($low_stock_products)): ?>
                                <div style="padding: var(--space-6);">
                                    <div class="alert alert-success mt-2 mb-2">
                                        All products are adequately stocked!
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                    <span class="text-muted" style="font-size: var(--text-xs);"><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                </td>
                                                <td><?php echo number_format($product['quantity']); ?></td>
                                                <td>
                                                    <?php 
                                                    $badge_class = $product['status'] === 'out_of_stock' ? 'badge-danger' : 'badge-warning';
                                                    $status_text = ucfirst(str_replace('_', ' ', $product['status']));
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($status_text, ENT_QUOTES, 'UTF-8'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3 mb-3" style="padding: var(--space-4);">
                                    <a href="<?php echo url('products/list.php?filter=low_stock'); ?>" class="btn btn-secondary btn-sm">View All Low Stock</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Recent Activity</h3>
                        </div>
                        <div class="card-body" style="padding: 0;">
                            <?php if (empty($recent_changes)): ?>
                                <div style="padding: var(--space-6);">
                                    <div class="alert alert-info mt-2 mb-2">
                                        No recent stock changes.
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Action</th>
                                                <th>Quantity</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_changes as $change): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($change['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <div class="text-muted" style="font-size: var(--text-xs);"><?php echo htmlspecialchars($change['sku'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $action_labels = [
                                                        'create' => 'Created',
                                                        'update' => 'Updated', 
                                                        'stock_in' => 'Stock In',
                                                        'stock_out' => 'Stock Out',
                                                        'adjustment' => 'Adjusted',
                                                        'scan_update' => 'Scanned'
                                                    ];
                                                    echo htmlspecialchars($action_labels[$change['action_type']] ?? ucfirst($change['action_type']), ENT_QUOTES, 'UTF-8');
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if ($change['old_quantity'] !== null && $change['new_quantity'] !== null): ?>
                                                        <?php echo number_format($change['old_quantity']); ?> → <?php echo number_format($change['new_quantity']); ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="text-muted" style="font-size: var(--text-xs);"><?php echo date('M j, H:i', strtotime($change['created_at'])); ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3 mb-3" style="padding: var(--space-4);">
                                    <a href="<?php echo url('logs/stock_logs.php'); ?>" class="btn btn-secondary btn-sm">View All Logs</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="btn-group" style="flex-wrap: wrap;">
                            <a href="<?php echo url('products/create.php'); ?>" class="btn btn-primary">Add New Product</a>
                            <a href="<?php echo url('locations/index.php'); ?>" class="btn btn-secondary">Manage Locations</a>
                            <a href="<?php echo url('deliveries/index.php'); ?>" class="btn btn-secondary">Manage Deliveries</a>
                            <a href="<?php echo url('qr/scan.php'); ?>" class="btn btn-secondary">Scan QR Code</a>
                            <a href="<?php echo url('products/list.php'); ?>" class="btn btn-ghost">Browse Products</a>
                        </div>
                    </div>
                </div>
            </main>

<?php require_once 'includes/footer.php'; ?>