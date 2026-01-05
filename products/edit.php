<?php
/**
 * Edit Product
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

$error_messages = [];
$success_message = '';
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error_messages[] = 'Invalid security token. Please try again.';
    } else {
        // Get and sanitize form data
        $product_name = trim($_POST['product_name'] ?? '');
        $sku = trim($_POST['sku'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $quantity = max(0, intval($_POST['quantity'] ?? 0));
        $min_stock_level = max(0, intval($_POST['min_stock_level'] ?? 10));
        $status = $_POST['status'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $supplier = trim($_POST['supplier'] ?? '');
        $purchase_date = $_POST['purchase_date'] ?? null;
        $purchase_price = floatval($_POST['purchase_price'] ?? 0);
        $selling_price = floatval($_POST['selling_price'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        
        // Validation
        if (empty($product_name)) {
            $error_messages[] = 'Product name is required.';
        }
        
        if (empty($sku)) {
            $error_messages[] = 'SKU is required.';
        } elseif (!preg_match('/^[A-Za-z0-9_-]+$/', $sku)) {
            $error_messages[] = 'SKU can only contain letters, numbers, hyphens, and underscores.';
        }
        
        if ($category_id <= 0) {
            $error_messages[] = 'Please select a valid category.';
        }
        
        if ($quantity < 0) {
            $error_messages[] = 'Quantity cannot be negative.';
        }
        
        if (!in_array($status, ['in_stock', 'low_stock', 'out_of_stock', 'damaged'])) {
            $error_messages[] = 'Invalid status selected.';
        }
        
        if (!empty($purchase_date) && !DateTime::createFromFormat('Y-m-d', $purchase_date)) {
            $error_messages[] = 'Invalid purchase date format.';
        }
        
        if (empty($error_messages)) {
            try {
                // Check if SKU already exists for other products
                $sku_check = $db->prepare("SELECT id FROM products WHERE sku = ? AND id != ?");
                $sku_check->execute([$sku, $product_id]);
                if ($sku_check->fetch()) {
                    $error_messages[] = 'SKU already exists. Please use a different SKU.';
                } else {
                    // Begin transaction
                    $db->beginTransaction();
                    
                    try {
                        // Store old values for logging
                        $old_quantity = $product['quantity'];
                        $old_status = $product['status'];
                        $old_location = $product['location'];
                        
                        // Update product
                        $stmt = $db->prepare("
                            UPDATE products SET 
                                product_name = ?, sku = ?, category_id = ?, quantity = ?, min_stock_level = ?,
                                status = ?, location = ?, supplier = ?, purchase_date = ?, 
                                purchase_price = ?, selling_price = ?, notes = ?
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([
                            $product_name,
                            $sku,
                            $category_id,
                            $quantity,
                            $min_stock_level,
                            $status,
                            $location ?: null,
                            $supplier ?: null,
                            $purchase_date ?: null,
                            $purchase_price > 0 ? $purchase_price : null,
                            $selling_price > 0 ? $selling_price : null,
                            $notes ?: null,
                            $product_id
                        ]);
                        
                        // Log the update if significant changes occurred
                        $changes = [];
                        if ($old_quantity != $quantity) {
                            $changes[] = "Quantity: $old_quantity → $quantity";
                        }
                        if ($old_status != $status) {
                            $changes[] = "Status: $old_status → $status";
                        }
                        if ($old_location != $location) {
                            $changes[] = "Location: '$old_location' → '$location'";
                        }
                        
                        if (!empty($changes)) {
                            $log_stmt = $db->prepare("
                                INSERT INTO stock_logs (
                                    product_id, admin_id, action_type, old_quantity, new_quantity, 
                                    old_status, new_status, old_location, new_location, notes, 
                                    ip_address, user_agent
                                ) VALUES (?, ?, 'update', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");
                            
                            $log_stmt->execute([
                                $product_id,
                                $admin['id'],
                                $old_quantity,
                                $quantity,
                                $old_status,
                                $status,
                                $old_location,
                                $location,
                                'Product updated: ' . implode(', ', $changes),
                                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                            ]);
                        }
                        
                        $db->commit();
                        
                        $success_message = 'Product updated successfully!';
                        
                        // Refresh product data
                        $stmt = $db->prepare("
                            SELECT p.*, c.name as category_name 
                            FROM products p 
                            JOIN categories c ON p.category_id = c.id 
                            WHERE p.id = ?
                        ");
                        $stmt->execute([$product_id]);
                        $product = $stmt->fetch();
                        
                    } catch (Exception $e) {
                        $db->rollback();
                        throw $e;
                    }
                }
            } catch (Exception $e) {
                error_log("Product update error: " . $e->getMessage());
                $error_messages[] = 'Failed to update product. Please try again.';
            }
        }
    }
}

// Get categories for dropdown
try {
    $categories_stmt = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $categories_stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    error_log("Failed to load categories: " . $e->getMessage());
}

$page_title = "Edit Product";
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
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">Edit Product: <?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="btn-group">
                                <a href="<?php echo url('qr/generate.php?id=' . $product_id); ?>" class="btn btn-secondary btn-sm" target="_blank">View QR Code</a>
                                <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary btn-sm">Back to List</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($error_messages)): ?>
                            <div class="alert alert-error">
                                <ul style="margin: 0; padding-left: 20px;">
                                    <?php foreach ($error_messages as $error): ?>
                                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <?php echo CSRF::getTokenField(); ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="product_name" class="form-label">Product Name *</label>
                                    <input 
                                        type="text" 
                                        id="product_name" 
                                        name="product_name" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['product_name'] ?? $product['product_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                        required 
                                        maxlength="200"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="sku" class="form-label">SKU *</label>
                                    <input 
                                        type="text" 
                                        id="sku" 
                                        name="sku" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['sku'] ?? $product['sku'], ENT_QUOTES, 'UTF-8'); ?>"
                                        required 
                                        maxlength="100"
                                        pattern="[A-Za-z0-9_-]+"
                                        title="SKU can only contain letters, numbers, hyphens, and underscores"
                                    >
                                    <small class="text-muted">Unique identifier (letters, numbers, hyphens, underscores only)</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category_id" class="form-label">Category *</label>
                                    <select id="category_id" name="category_id" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo (($_POST['category_id'] ?? $product['category_id']) == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input 
                                        type="number" 
                                        id="quantity" 
                                        name="quantity" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['quantity'] ?? $product['quantity'], ENT_QUOTES, 'UTF-8'); ?>"
                                        min="0"
                                        step="1"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="min_stock_level" class="form-label">Minimum Stock Level</label>
                                    <input 
                                        type="number" 
                                        id="min_stock_level" 
                                        name="min_stock_level" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['min_stock_level'] ?? $product['min_stock_level'], ENT_QUOTES, 'UTF-8'); ?>"
                                        min="0"
                                        step="1"
                                    >
                                    <small class="text-muted">Alert threshold for low stock warnings</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status" class="form-label">Status *</label>
                                    <select id="status" name="status" class="form-control" required>
                                        <?php 
                                        $current_status = $_POST['status'] ?? $product['status'];
                                        $status_options = [
                                            'in_stock' => 'In Stock',
                                            'low_stock' => 'Low Stock',
                                            'out_of_stock' => 'Out of Stock',
                                            'damaged' => 'Damaged'
                                        ];
                                        ?>
                                        <?php foreach ($status_options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $current_status === $value ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="location" class="form-label">Location</label>
                                    <input 
                                        type="text" 
                                        id="location" 
                                        name="location" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['location'] ?? $product['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        maxlength="100"
                                        placeholder="e.g., Warehouse A, Shelf 3"
                                    >
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="supplier" class="form-label">Supplier</label>
                                    <input 
                                        type="text" 
                                        id="supplier" 
                                        name="supplier" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['supplier'] ?? $product['supplier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        maxlength="200"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input 
                                        type="date" 
                                        id="purchase_date" 
                                        name="purchase_date" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? $product['purchase_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <input 
                                        type="number" 
                                        id="purchase_price" 
                                        name="purchase_price" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['purchase_price'] ?? ($product['purchase_price'] ? number_format($product['purchase_price'], 2, '.', '') : ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="selling_price" class="form-label">Selling Price</label>
                                    <input 
                                        type="number" 
                                        id="selling_price" 
                                        name="selling_price" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['selling_price'] ?? ($product['selling_price'] ? number_format($product['selling_price'], 2, '.', '') : ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        min="0"
                                        step="0.01"
                                        placeholder="0.00"
                                    >
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes" class="form-label">Notes</label>
                                <textarea 
                                    id="notes" 
                                    name="notes" 
                                    class="form-control" 
                                    rows="3"
                                    placeholder="Additional notes or description..."
                                ><?php echo htmlspecialchars($_POST['notes'] ?? $product['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            
                            <!-- Product Info -->
                            <div class="alert alert-info">
                                <strong>Product Information:</strong><br>
                                Created: <?php echo date('M j, Y H:i', strtotime($product['created_at'])); ?><br>
                                Last Updated: <?php echo date('M j, Y H:i', strtotime($product['last_updated'])); ?><br>
                                QR Code: <code><?php echo htmlspecialchars($product['qr_code_value'], ENT_QUOTES, 'UTF-8'); ?></code>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Update Product</button>
                                <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary">Cancel</a>
                                <a href="<?php echo url('products/delete.php?id=' . $product_id); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete Product</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Focus on product name field
        document.getElementById('product_name').focus();
        
        // Auto-update status based on quantity and min stock level
        function updateStatusBasedOnQuantity() {
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const minStock = parseInt(document.getElementById('min_stock_level').value) || 0;
            const statusSelect = document.getElementById('status');
            
            // Don't auto-update if manually set to damaged
            if (statusSelect.value === 'damaged') {
                return;
            }
            
            if (quantity === 0) {
                statusSelect.value = 'out_of_stock';
            } else if (quantity <= minStock) {
                statusSelect.value = 'low_stock';
            } else {
                statusSelect.value = 'in_stock';
            }
        }
        
        document.getElementById('quantity').addEventListener('input', updateStatusBasedOnQuantity);
        document.getElementById('min_stock_level').addEventListener('input', updateStatusBasedOnQuantity);
    </script>
</body>
</html>