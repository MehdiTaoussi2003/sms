<?php
/**
 * Delete Product
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

$error_message = '';
$product = null;

// Get product ID
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    redirect_to('products/list.php');
}

// Get product data
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        redirect_to('products/list.php');
    }
} catch (Exception $e) {
    error_log("Product fetch error: " . $e->getMessage());
    redirect_to('products/list.php');
}

// Handle delete confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        try {
            $db->beginTransaction();
            
            // Log the deletion
            $log_stmt = $db->prepare("
                INSERT INTO stock_logs (
                    product_id, admin_id, action_type, old_quantity, new_quantity, 
                    old_status, new_status, notes, ip_address, user_agent
                ) VALUES (?, ?, 'delete', ?, NULL, ?, NULL, 'Product deleted', ?, ?)
            ");
            
            $log_stmt->execute([
                $product_id,
                $admin['id'],
                $product['quantity'],
                $product['status'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            
            // Delete QR code file if exists
            if ($product['qr_code_path'] && file_exists('../' . $product['qr_code_path'])) {
                unlink('../' . $product['qr_code_path']);
            }
            
            // Delete product (this will cascade delete stock_logs due to foreign key)
            $delete_stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $delete_stmt->execute([$product_id]);
            
            $db->commit();
            
            // Redirect with success message
            $_SESSION['success_message'] = 'Product "' . $product['product_name'] . '" deleted successfully.';
            redirect_to('products/list.php');
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Product deletion error: " . $e->getMessage());
            $error_message = 'Failed to delete product. Please try again.';
        }
    }
}

$page_title = "Delete Product";
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
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-logo">
                <h1>SMS</h1>
            </div>
            <ul class="sidebar-nav">
                <li><a href="<?php echo BASE_URL; ?>">📊 Dashboard</a></li>
                <li><a href="<?php echo url('products/list.php'); ?>" class="active">📦 Products</a></li>
                <li><a href="<?php echo url('products/create.php'); ?>">➕ Add Product</a></li>
                <li><a href="<?php echo url('qr/scan.php'); ?>">📱 QR Scanner</a></li>
                <li><a href="<?php echo url('logs/stock_logs.php'); ?>">📋 Stock Logs</a></li>
                <li><a href="<?php echo url('exports/'); ?>">📤 Export Data</a></li>
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <h1 class="page-title"><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="admin-info">
                    <span>Welcome, <?php echo htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="<?php echo url('logout.php'); ?>" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </header>
            
            <!-- Content -->
            <main class="content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">⚠️ Confirm Product Deletion</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> This action cannot be undone. All product data and associated stock logs will be permanently deleted.
                        </div>
                        
                        <div class="product-details">
                            <h4>Product Details:</h4>
                            <div class="product-field">
                                <span class="field-label">Name:</span>
                                <span class="field-value"><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="product-field">
                                <span class="field-label">SKU:</span>
                                <span class="field-value"><code><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></code></span>
                            </div>
                            <div class="product-field">
                                <span class="field-label">Category:</span>
                                <span class="field-value"><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="product-field">
                                <span class="field-label">Current Quantity:</span>
                                <span class="field-value"><?php echo number_format($product['quantity']); ?></span>
                            </div>
                            <div class="product-field">
                                <span class="field-label">Status:</span>
                                <span class="field-value">
                                    <?php 
                                    $badge_classes = [
                                        'in_stock' => 'badge-success',
                                        'low_stock' => 'badge-warning', 
                                        'out_of_stock' => 'badge-danger',
                                        'damaged' => 'badge-secondary'
                                    ];
                                    $badge_class = $badge_classes[$product['status']] ?? 'badge-secondary';
                                    $status_text = ucfirst(str_replace('_', ' ', $product['status']));
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo htmlspecialchars($status_text, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </span>
                            </div>
                            <?php if ($product['location']): ?>
                            <div class="product-field">
                                <span class="field-label">Location:</span>
                                <span class="field-value"><?php echo htmlspecialchars($product['location'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($product['supplier']): ?>
                            <div class="product-field">
                                <span class="field-label">Supplier:</span>
                                <span class="field-value"><?php echo htmlspecialchars($product['supplier'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <form method="POST" action="" style="margin-top: 20px;">
                            <?php echo CSRF::getTokenField(); ?>
                            
                            <div class="form-group">
                                <div class="btn-group">
                                    <button type="submit" class="btn btn-danger">🗑️ Yes, Delete Product</button>
                                    <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary">Cancel</a>
                                    <a href="<?php echo url('products/edit.php?id=' . $product_id); ?>" class="btn btn-primary">Edit Instead</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>