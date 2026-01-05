<?php
/**
 * Export Data Center
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $export_type = $_POST['export_type'] ?? '';
        $format = $_POST['format'] ?? 'csv';
        
        switch ($export_type) {
            case 'products':
                exportProducts($format);
                break;
            case 'logs':
                exportLogs($format);
                break;
            case 'low_stock':
                exportLowStock($format);
                break;
            default:
                $error_message = 'Invalid export type selected.';
        }
    }
}

function exportProducts($format = 'csv') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT 
                p.id, p.product_name, p.sku, c.name as category,
                p.quantity, p.min_stock_level, p.status, p.location, p.supplier,
                p.purchase_date, p.purchase_price, p.selling_price, 
                p.qr_code_value, p.notes, p.created_at, p.last_updated
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            ORDER BY p.product_name ASC
        ");
        $products = $stmt->fetchAll();
        
        if ($format === 'csv') {
            outputCSV('products_' . date('Y-m-d_H-i-s'), [
                'ID', 'Product Name', 'SKU', 'Category', 'Quantity', 'Min Stock Level', 
                'Status', 'Location', 'Supplier', 'Purchase Date', 'Purchase Price', 
                'Selling Price', 'QR Code', 'Notes', 'Created', 'Last Updated'
            ], $products, [
                'id', 'product_name', 'sku', 'category', 'quantity', 'min_stock_level',
                'status', 'location', 'supplier', 'purchase_date', 'purchase_price',
                'selling_price', 'qr_code_value', 'notes', 'created_at', 'last_updated'
            ]);
        } else {
            outputHTML('Products Report', $products, 'products');
        }
    } catch (Exception $e) {
        error_log("Export products error: " . $e->getMessage());
        die("Export failed. Please try again.");
    }
}

function exportLogs($format = 'csv') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT 
                sl.id, p.product_name, p.sku, sl.action_type,
                sl.old_quantity, sl.new_quantity, sl.old_status, sl.new_status,
                sl.old_location, sl.new_location, sl.notes, a.username as admin,
                sl.ip_address, sl.created_at
            FROM stock_logs sl
            JOIN products p ON sl.product_id = p.id
            JOIN admins a ON sl.admin_id = a.id
            ORDER BY sl.created_at DESC
        ");
        $logs = $stmt->fetchAll();
        
        if ($format === 'csv') {
            outputCSV('stock_logs_' . date('Y-m-d_H-i-s'), [
                'ID', 'Product Name', 'SKU', 'Action', 'Old Quantity', 'New Quantity',
                'Old Status', 'New Status', 'Old Location', 'New Location', 
                'Notes', 'Admin', 'IP Address', 'Date/Time'
            ], $logs, [
                'id', 'product_name', 'sku', 'action_type', 'old_quantity', 'new_quantity',
                'old_status', 'new_status', 'old_location', 'new_location',
                'notes', 'admin', 'ip_address', 'created_at'
            ]);
        } else {
            outputHTML('Stock Logs Report', $logs, 'logs');
        }
    } catch (Exception $e) {
        error_log("Export logs error: " . $e->getMessage());
        die("Export failed. Please try again.");
    }
}

function exportLowStock($format = 'csv') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("
            SELECT 
                p.id, p.product_name, p.sku, c.name as category,
                p.quantity, p.min_stock_level, p.status, p.location, p.supplier,
                p.last_updated
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.status IN ('low_stock', 'out_of_stock')
            ORDER BY p.quantity ASC, p.last_updated DESC
        ");
        $products = $stmt->fetchAll();
        
        if ($format === 'csv') {
            outputCSV('low_stock_alert_' . date('Y-m-d_H-i-s'), [
                'ID', 'Product Name', 'SKU', 'Category', 'Current Quantity', 
                'Min Stock Level', 'Status', 'Location', 'Supplier', 'Last Updated'
            ], $products, [
                'id', 'product_name', 'sku', 'category', 'quantity', 'min_stock_level',
                'status', 'location', 'supplier', 'last_updated'
            ]);
        } else {
            outputHTML('Low Stock Alert Report', $products, 'low_stock');
        }
    } catch (Exception $e) {
        error_log("Export low stock error: " . $e->getMessage());
        die("Export failed. Please try again.");
    }
}

function outputCSV($filename, $headers, $data, $fields) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Output headers
    fputcsv($output, $headers);
    
    // Output data
    foreach ($data as $row) {
        $csv_row = [];
        foreach ($fields as $field) {
            $csv_row[] = $row[$field] ?? '';
        }
        fputcsv($output, $csv_row);
    }
    
    fclose($output);
    exit();
}

function outputHTML($title, $data, $type) {
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . ' - Stock Management System</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #2c3e50; margin-bottom: 5px; }
            .header p { color: #6c757d; margin: 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f8f9fa; font-weight: bold; }
            .status-in { color: #27ae60; }
            .status-low { color: #f39c12; }
            .status-out { color: #e74c3c; }
            .status-damaged { color: #95a5a6; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>
            <p>Generated on: ' . date('M j, Y H:i:s') . '</p>
            <p>Stock Management System</p>
        </div>
        
        <div class="no-print">
            <button onclick="window.print()">🖨️ Print Report</button>
            <a href="<?php echo url('exports/'); ?>" style="margin-left: 10px;">← Back to Export Center</a>
        </div>';
    
    if (empty($data)) {
        echo '<p style="text-align: center; color: #6c757d; margin-top: 50px;">No data to display.</p>';
    } else {
        echo '<table>';
        
        if ($type === 'products' || $type === 'low_stock') {
            echo '<thead>
                <tr>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Last Updated</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($data as $row) {
                $status_class = 'status-' . str_replace('_', '', $row['status']);
                echo '<tr>
                    <td>' . htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($row['sku'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($row['category'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . number_format($row['quantity']) . '</td>
                    <td class="' . $status_class . '">' . ucfirst(str_replace('_', ' ', $row['status'])) . '</td>
                    <td>' . htmlspecialchars($row['location'] ?: '-', ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . date('M j, Y H:i', strtotime($row['last_updated'])) . '</td>
                </tr>';
            }
        } elseif ($type === 'logs') {
            echo '<thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Product</th>
                    <th>Action</th>
                    <th>Changes</th>
                    <th>Admin</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($data as $row) {
                $changes = [];
                if ($row['old_quantity'] !== null && $row['new_quantity'] !== null && $row['old_quantity'] != $row['new_quantity']) {
                    $changes[] = 'Qty: ' . $row['old_quantity'] . ' → ' . $row['new_quantity'];
                }
                if ($row['old_status'] && $row['new_status'] && $row['old_status'] != $row['new_status']) {
                    $changes[] = 'Status: ' . $row['old_status'] . ' → ' . $row['new_status'];
                }
                
                echo '<tr>
                    <td>' . date('M j, Y H:i:s', strtotime($row['created_at'])) . '</td>
                    <td>' . htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') . '<br><small>' . htmlspecialchars($row['sku'], ENT_QUOTES, 'UTF-8') . '</small></td>
                    <td>' . ucfirst(str_replace('_', ' ', $row['action_type'])) . '</td>
                    <td>' . (empty($changes) ? '-' : implode('<br>', $changes)) . '</td>
                    <td>' . htmlspecialchars($row['admin'], ENT_QUOTES, 'UTF-8') . '</td>
                    <td>' . htmlspecialchars($row['notes'] ?: '-', ENT_QUOTES, 'UTF-8') . '</td>
                </tr>';
            }
        }
        
        echo '</tbody></table>';
    }
    
    echo '<div style="margin-top: 30px; text-align: center; color: #6c757d; font-size: 12px;">
        <p>Stock Management System - ' . date('Y') . '</p>
    </div>
    </body>
    </html>';
    exit();
}

$page_title = "Export Data";
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
                <li><a href="<?php echo url('products/create.php'); ?>"><span class="nav-icon">➕</span><span class="nav-text">Add Product</span></a></li>
                <li><a href="<?php echo url('qr/scan.php'); ?>"><span class="nav-icon">📱</span><span class="nav-text">QR Scanner</span></a></li>
                <li><a href="<?php echo url('logs/stock_logs.php'); ?>"><span class="nav-icon">📋</span><span class="nav-text">Stock Logs</span></a></li>
                <li><a href="<?php echo url('exports/'); ?>" class="active"><span class="nav-icon">📤</span><span class="nav-text">Export Data</span></a></li>
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
                        <span class="title-icon">📤</span>
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
                <?php if (isset($error_message)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Products Export -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">📦 Products Export</h3>
                            </div>
                            <div class="card-body">
                                <p>Export complete product database including stock levels, categories, and details.</p>
                                
                                <form method="POST" action="">
                                    <?php echo CSRF::getTokenField(); ?>
                                    <input type="hidden" name="export_type" value="products">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Export Format:</label>
                                        <div>
                                            <label><input type="radio" name="format" value="csv" checked> CSV File</label><br>
                                            <label><input type="radio" name="format" value="html"> HTML Report</label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">📤 Export Products</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Stock Logs Export -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">📋 Stock Logs Export</h3>
                            </div>
                            <div class="card-body">
                                <p>Export complete stock activity history including all changes and admin actions.</p>
                                
                                <form method="POST" action="">
                                    <?php echo CSRF::getTokenField(); ?>
                                    <input type="hidden" name="export_type" value="logs">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Export Format:</label>
                                        <div>
                                            <label><input type="radio" name="format" value="csv" checked> CSV File</label><br>
                                            <label><input type="radio" name="format" value="html"> HTML Report</label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-warning">📤 Export Logs</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Low Stock Alert Export -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">⚠️ Low Stock Alert</h3>
                            </div>
                            <div class="card-body">
                                <p>Export products with low stock or out of stock status for immediate attention.</p>
                                
                                <form method="POST" action="">
                                    <?php echo CSRF::getTokenField(); ?>
                                    <input type="hidden" name="export_type" value="low_stock">
                                    
                                    <div class="form-group">
                                        <label class="form-label">Export Format:</label>
                                        <div>
                                            <label><input type="radio" name="format" value="csv" checked> CSV File</label><br>
                                            <label><input type="radio" name="format" value="html"> HTML Report</label>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-danger">📤 Export Low Stock</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Export Instructions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📋 Export Instructions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>CSV Format</h5>
                                <ul>
                                    <li>Comma-separated values file</li>
                                    <li>Can be opened in Excel, Google Sheets</li>
                                    <li>Best for data analysis and processing</li>
                                    <li>UTF-8 encoded for international characters</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5>HTML Report</h5>
                                <ul>
                                    <li>Formatted web page report</li>
                                    <li>Ready for printing or sharing</li>
                                    <li>Includes company branding</li>
                                    <li>Print-friendly layout</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <strong>Note:</strong> All exports include current data at the time of generation. 
                            Large databases may take a few moments to process.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="../assets/js/mobile-menu-universal.js"></script>
</body>
</html>