<?php
/**
 * Product List with Search and Filter
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';
$location_filter = trim($_GET['location'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $db = Database::getInstance()->getConnection();
    
    // Build WHERE clause for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(p.product_name LIKE ? OR p.sku LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($category_filter)) {
        $where_conditions[] = "p.category_id = ?";
        $params[] = $category_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "p.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($location_filter)) {
        $where_conditions[] = "p.location LIKE ?";
        $params[] = "%$location_filter%";
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_products = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_products / $per_page);
    
    // Get products with pagination
    $sql = "
        SELECT 
            p.id, p.product_name, p.sku, c.name as category_name, p.category_id,
            p.quantity, p.min_stock_level, p.status, p.location, p.supplier,
            p.purchase_date, p.last_updated, p.qr_code_value
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        $where_clause
        ORDER BY p.last_updated DESC, p.product_name ASC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Get categories for filter dropdown
    $categories_stmt = $db->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name");
    $categories = $categories_stmt->fetchAll();
    
    // Get unique locations for filter
    $locations_stmt = $db->query("SELECT DISTINCT location FROM products WHERE location IS NOT NULL AND location != '' ORDER BY location");
    $locations = $locations_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Products list error: " . $e->getMessage());
    $products = [];
    $categories = [];
    $locations = [];
    $total_products = 0;
    $total_pages = 1;
}

$page_title = "Products";
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
                <li><a href="<?php echo url('products/list.php'); ?>" class="active"><span class="nav-icon">📦</span><span class="nav-text">Products</span></a></li>
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
                        <span class="title-icon">📦</span>
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
                <!-- Search and Filter Bar -->
                <div class="filter-bar">
                    <form method="GET" action="">
                        <div class="form-group">
                            <label for="search" class="form-label">Search Products</label>
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                class="form-control" 
                                placeholder="Product name or SKU..."
                                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="category" class="form-label">Category</label>
                            <select id="category" name="category" class="form-control">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="in_stock" <?php echo $status_filter === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                <option value="low_stock" <?php echo $status_filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                <option value="out_of_stock" <?php echo $status_filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                <option value="damaged" <?php echo $status_filter === 'damaged' ? 'selected' : ''; ?>>Damaged</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <input 
                                type="text" 
                                id="location" 
                                name="location" 
                                class="form-control" 
                                placeholder="Location..."
                                value="<?php echo htmlspecialchars($location_filter, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">🔍 Search</button>
                            <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary">Clear</a>
                        </div>
                    </form>
                </div>
                
                <!-- Results Summary -->
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <h3 class="card-title">
                                Product List 
                                <span class="badge badge-secondary"><?php echo number_format($total_products); ?> products</span>
                            </h3>
                            <div class="btn-group">
                                <a href="<?php echo url('products/create.php'); ?>" class="btn btn-success btn-sm">➕ Add Product</a>
                                <a href="<?php echo url('exports/?type=products'); ?>" class="btn btn-warning btn-sm">📤 Export</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="alert alert-info text-center">
                                <?php if (!empty($search) || !empty($category_filter) || !empty($status_filter) || !empty($location_filter)): ?>
                                    No products found matching your search criteria.
                                    <br><a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary btn-sm mt-2">View All Products</a>
                                <?php else: ?>
                                    No products found. 
                                    <br><a href="<?php echo url('products/create.php'); ?>" class="btn btn-success btn-sm mt-2">Add Your First Product</a>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Category</th>
                                            <th>Quantity</th>
                                            <th>Status</th>
                                            <th>Location</th>
                                            <th>Last Updated</th>
                                            <th class="no-print">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <?php if ($product['supplier']): ?>
                                                    <br><small class="text-muted">Supplier: <?php echo htmlspecialchars($product['supplier'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></code>
                                            </td>
                                            <td><?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <strong><?php echo number_format($product['quantity']); ?></strong>
                                                <?php if ($product['min_stock_level'] > 0): ?>
                                                    <br><small class="text-muted">Min: <?php echo number_format($product['min_stock_level']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
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
                                            </td>
                                            <td><?php echo htmlspecialchars($product['location'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <small><?php echo date('M j, Y H:i', strtotime($product['last_updated'])); ?></small>
                                            </td>
                                            <td class="no-print">
                                                <div class="btn-group">
                                                    <a href="<?php echo url('products/edit.php?id=' . $product['id']); ?>" class="btn btn-primary btn-sm">Edit</a>
                                                    <a href="<?php echo url('qr/generate.php?id=' . $product['id']); ?>" class="btn btn-secondary btn-sm" target="_blank">QR</a>
                                                    <a href="<?php echo url('products/delete.php?id=' . $product['id']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="pagination-container text-center mt-4">
                                    <div class="pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="btn btn-secondary btn-sm">First</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary btn-sm">Previous</a>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        for ($i = $start_page; $i <= $end_page; $i++): 
                                        ?>
                                            <?php if ($i === $page): ?>
                                                <span class="btn btn-primary btn-sm"><?php echo $i; ?></span>
                                            <?php else: ?>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="btn btn-secondary btn-sm"><?php echo $i; ?></a>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary btn-sm">Next</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="btn btn-secondary btn-sm">Last</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Showing <?php echo number_format(($page - 1) * $per_page + 1); ?> to 
                                            <?php echo number_format(min($page * $per_page, $total_products)); ?> of 
                                            <?php echo number_format($total_products); ?> products
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script>
        // Auto-submit search form on enter
        document.getElementById('search').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
        
        // Quick filter buttons
        function quickFilter(status) {
            const url = new URL(window.location);
            url.searchParams.set('status', status);
            url.searchParams.set('page', '1');
            window.location.href = url.toString();
        }
    </script>
</body>
</html>