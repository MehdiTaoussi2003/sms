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
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = ['total_products' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'recent_updates' => 0];
    $low_stock_products = [];
    $recent_changes = [];
}

$page_title = "Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Stock Management System</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
</head>
<body>
    <div class="admin-layout">
        <!-- Modern Responsive Sidebar -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <h1>SMS</h1>
                <p>Stock Management</p>
            </div>
            <ul class="sidebar-nav">
                <li><a href="<?php echo BASE_URL; ?>" class="active"><span class="nav-icon">📊</span><span class="nav-text">Dashboard</span></a></li>
                <li><a href="<?php echo url('products/list.php'); ?>"><span class="nav-icon">📦</span><span class="nav-text">Products</span></a></li>
                <li><a href="<?php echo url('products/create.php'); ?>"><span class="nav-icon">➕</span><span class="nav-text">Add Product</span></a></li>
                <li><a href="<?php echo url('qr/scan.php'); ?>"><span class="nav-icon">📱</span><span class="nav-text">QR Scanner</span></a></li>
                <li><a href="<?php echo url('logs/stock_logs.php'); ?>"><span class="nav-icon">📋</span><span class="nav-text">Stock Logs</span></a></li>
                <li><a href="<?php echo url('exports/'); ?>"><span class="nav-icon">📤</span><span class="nav-text">Export Data</span></a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Modern Responsive Top Bar -->
            <header class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                        <span>☰</span>
                    </button>
                    <h1 class="page-title">
                        <span class="title-icon">📊</span>
                        <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                </div>
                <div class="admin-info">
                    <div class="admin-welcome">
                        <div class="admin-name">Welcome, <?php echo htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="admin-role"><?php echo htmlspecialchars($admin['role'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                    <a href="<?php echo url('logout.php'); ?>" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </header>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const mobileToggle = document.getElementById('mobile-menu-toggle');
                    const sidebar = document.getElementById('sidebar');
                    const sidebarOverlay = document.getElementById('sidebar-overlay');
                    
                    mobileToggle?.addEventListener('click', function(e) {
                        e.preventDefault();
                        sidebar.classList.toggle('mobile-open');
                        sidebarOverlay.classList.toggle('active');
                        document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
                    });
                    
                    sidebarOverlay?.addEventListener('click', function() {
                        sidebar.classList.remove('mobile-open');
                        sidebarOverlay.classList.remove('active');
                        document.body.style.overflow = '';
                    });
                    
                    document.querySelectorAll('.sidebar-nav a').forEach(link => {
                        link.addEventListener('click', function() {
                            if (window.innerWidth <= 1024) {
                                sidebar.classList.remove('mobile-open');
                                sidebarOverlay.classList.remove('active');
                                document.body.style.overflow = '';
                            }
                        });
                    });
                });
            </script>
            
            <!-- Content -->
            <main class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number total"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number low"><?php echo number_format($stats['low_stock']); ?></div>
                        <div class="stat-label">Low Stock</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number out"><?php echo number_format($stats['out_of_stock']); ?></div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number recent"><?php echo number_format($stats['recent_updates']); ?></div>
                        <div class="stat-label">Updated Today</div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <!-- Low Stock Alert -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">⚠️ Stock Alerts</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($low_stock_products)): ?>
                                    <div class="alert alert-success">
                                        All products are adequately stocked!
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>SKU</th>
                                                    <th>Quantity</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($low_stock_products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></td>
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
                                    <div class="text-center mt-3">
                                        <a href="<?php echo url('products/list.php?filter=low_stock'); ?>" class="btn btn-warning btn-sm">View All Low Stock</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <!-- Recent Activity -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">🕒 Recent Activity</h3>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_changes)): ?>
                                    <div class="alert alert-info">
                                        No recent stock changes.
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
                                                        <strong><?php echo htmlspecialchars($change['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($change['sku'], ENT_QUOTES, 'UTF-8'); ?></small>
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
                                                        <small><?php echo date('M j, H:i', strtotime($change['created_at'])); ?></small>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="<?php echo url('logs/stock_logs.php'); ?>" class="btn btn-primary btn-sm">View All Logs</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">🚀 Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="btn-group">
                            <a href="<?php echo url('products/create.php'); ?>" class="btn btn-primary">Add New Product</a>
                            <a href="<?php echo url('qr/scan.php'); ?>" class="btn btn-success">Scan QR Code</a>
                            <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary">Browse Products</a>
                            <a href="<?php echo url('exports/'); ?>" class="btn btn-warning">Export Data</a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="<?php echo url('assets/js/mobile-menu-universal.js'); ?>"></script>
    <script>
        SMS.MobileMenu.init();
    </script>
</body>
</html>