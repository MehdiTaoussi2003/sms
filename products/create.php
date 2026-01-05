<?php
/**
 * Create Product
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
        
        if (!empty($purchase_date) && !DateTime::createFromFormat('Y-m-d', $purchase_date)) {
            $error_messages[] = 'Invalid purchase date format.';
        }
        
        if (empty($error_messages)) {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Check if SKU already exists
                $sku_check = $db->prepare("SELECT id FROM products WHERE sku = ?");
                $sku_check->execute([$sku]);
                if ($sku_check->fetch()) {
                    $error_messages[] = 'SKU already exists. Please use a different SKU.';
                } else {
                    // Generate unique QR code value in proper SMS format
                    $timestamp = time();
                    $random_hex = strtoupper(substr(md5($sku . $timestamp), 0, 13));
                    $qr_code_value = 'SMS_' . $random_hex . '_' . strtoupper(substr($sku, 0, 10));
                    
                    // Begin transaction
                    $db->beginTransaction();
                    
                    try {
                        // Insert product
                        $stmt = $db->prepare("
                            INSERT INTO products (
                                product_name, sku, category_id, quantity, min_stock_level, 
                                location, supplier, purchase_date, purchase_price, selling_price, 
                                qr_code_value, notes
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $product_name,
                            $sku,
                            $category_id,
                            $quantity,
                            $min_stock_level,
                            $location ?: null,
                            $supplier ?: null,
                            $purchase_date ?: null,
                            $purchase_price > 0 ? $purchase_price : null,
                            $selling_price > 0 ? $selling_price : null,
                            $qr_code_value,
                            $notes ?: null
                        ]);
                        
                        $product_id = $db->lastInsertId();
                        
                        // Log the creation
                        $log_stmt = $db->prepare("
                            INSERT INTO stock_logs (
                                product_id, admin_id, action_type, old_quantity, new_quantity, 
                                old_status, new_status, notes, ip_address, user_agent
                            ) VALUES (?, ?, 'create', NULL, ?, NULL, 
                                (SELECT status FROM products WHERE id = ?), 
                                'Product created', ?, ?)
                        ");
                        
                        $log_stmt->execute([
                            $product_id,
                            $admin['id'],
                            $quantity,
                            $product_id,
                            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                        ]);
                        
                        $db->commit();
                        
                        $success_message = 'Product created successfully!';
                        
                        // Clear form data on success
                        $_POST = [];
                        
                    } catch (Exception $e) {
                        $db->rollback();
                        throw $e;
                    }
                }
            } catch (Exception $e) {
                error_log("Product creation error: " . $e->getMessage());
                $error_messages[] = 'Failed to create product. Please try again.';
            }
        }
    }
}

// Get categories for dropdown
try {
    $db = Database::getInstance()->getConnection();
    $categories_stmt = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $categories_stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
    error_log("Failed to load categories: " . $e->getMessage());
}

$page_title = "Add Product";
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
                <li><a href="<?php echo BASE_URL; ?>"><span class="nav-icon">📊</span><span class="nav-text">Dashboard</span></a></li>
                <li><a href="<?php echo url('products/list.php'); ?>"><span class="nav-icon">📦</span><span class="nav-text">Products</span></a></li>
                <li><a href="<?php echo url('products/create.php'); ?>" class="active"><span class="nav-icon">➕</span><span class="nav-text">Add Product</span></a></li>
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
                        <span class="title-icon">➕</span>
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
            
            <!-- Content -->
            <main class="content">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Create New Product</h3>
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
                                <div class="mt-2">
                                    <a href="<?php echo url('products/create.php'); ?>" class="btn btn-success btn-sm">Add Another Product</a>
                                    <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary btn-sm">View All Products</a>
                                </div>
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
                                        value="<?php echo htmlspecialchars($_POST['product_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                                        value="<?php echo htmlspecialchars($_POST['sku'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                                        <option value="<?php echo $category['id']; ?>" <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="quantity" class="form-label">Initial Quantity</label>
                                    <input 
                                        type="number" 
                                        id="quantity" 
                                        name="quantity" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['quantity'] ?? '0', ENT_QUOTES, 'UTF-8'); ?>"
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
                                        value="<?php echo htmlspecialchars($_POST['min_stock_level'] ?? '10', ENT_QUOTES, 'UTF-8'); ?>"
                                        min="0"
                                        step="1"
                                    >
                                    <small class="text-muted">Alert threshold for low stock warnings</small>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="location" class="form-label">Location</label>
                                    <input 
                                        type="text" 
                                        id="location" 
                                        name="location" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        maxlength="100"
                                        placeholder="e.g., Warehouse A, Shelf 3"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="supplier" class="form-label">Supplier</label>
                                    <input 
                                        type="text" 
                                        id="supplier" 
                                        name="supplier" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['supplier'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                        maxlength="200"
                                    >
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input 
                                        type="date" 
                                        id="purchase_date" 
                                        name="purchase_date" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['purchase_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <input 
                                        type="number" 
                                        id="purchase_price" 
                                        name="purchase_price" 
                                        class="form-control" 
                                        value="<?php echo htmlspecialchars($_POST['purchase_price'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                                        value="<?php echo htmlspecialchars($_POST['selling_price'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
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
                                ><?php echo htmlspecialchars($_POST['notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Create Product</button>
                                <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Auto-generate SKU from product name
        document.getElementById('product_name').addEventListener('input', function() {
            const productName = this.value;
            const skuField = document.getElementById('sku');
            
            if (productName && !skuField.value) {
                // Generate SKU from product name
                let sku = productName
                    .toUpperCase()
                    .replace(/[^A-Z0-9\s]/g, '')
                    .replace(/\s+/g, '_')
                    .substring(0, 20);
                
                // Add timestamp for uniqueness
                const timestamp = Date.now().toString().slice(-6);
                sku = sku + '_' + timestamp;
                
                skuField.value = sku;
            }
        });
        
        // Focus on product name field
        document.getElementById('product_name').focus();
    </script>
</body>
</html>