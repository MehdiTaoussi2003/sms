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
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> - SMS</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg-main: #0B0F19;
                --bg-card: #151B26;
                --border-color: #222B38;
                --text-main: #F3F4F6;
                --text-muted: #9CA3AF;
                --primary: #6366F1;
                --primary-hover: #4F46E5;
                --success: #10B981;
                --danger: #EF4444;
                --warning: #F59E0B;
            }
            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg-main);
                color: var(--text-main);
                margin: 0;
                padding: 2rem;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            .header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid var(--border-color);
                padding-bottom: 1rem;
                margin-bottom: 2rem;
            }
            h1 {
                margin: 0;
                font-size: 1.8rem;
                font-weight: 600;
            }
            .btn-print {
                background-color: var(--primary);
                color: white;
                border: none;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                cursor: pointer;
                font-weight: 500;
                font-size: 0.875rem;
                transition: background-color 0.2s;
                text-decoration: none;
            }
            .btn-print:hover {
                background-color: var(--primary-hover);
            }
            .btn-back {
                background-color: transparent;
                color: var(--text-muted);
                border: 1px solid var(--border-color);
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                cursor: pointer;
                font-weight: 500;
                font-size: 0.875rem;
                transition: all 0.2s;
                text-decoration: none;
                margin-right: 10px;
            }
            .btn-back:hover {
                color: var(--text-main);
                border-color: var(--text-muted);
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
                background-color: var(--bg-card);
                border-radius: 0.5rem;
                overflow: hidden;
            }
            th, td {
                padding: 0.75rem 1rem;
                text-align: left;
                border-bottom: 1px solid var(--border-color);
            }
            th {
                background-color: rgba(99, 102, 241, 0.1);
                font-weight: 600;
                font-size: 0.875rem;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }
            tr:last-child td {
                border-bottom: none;
            }
            .badge {
                display: inline-block;
                padding: 0.25rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 500;
                text-transform: uppercase;
            }
            .badge-in_stock { background-color: rgba(16, 185, 129, 0.2); color: #34D399; }
            .badge-low_stock { background-color: rgba(245, 158, 11, 0.2); color: #FBBF24; }
            .badge-out_of_stock { background-color: rgba(239, 68, 68, 0.2); color: #FCA5A5; }
            .badge-damaged { background-color: rgba(239, 68, 68, 0.2); color: #FCA5A5; }
            
            @media print {
                body {
                    background-color: white;
                    color: black;
                    padding: 0;
                }
                .btn-print, .btn-back {
                    display: none;
                }
                table {
                    background-color: transparent;
                }
                th {
                    background-color: #f3f4f6 !important;
                    color: black !important;
                }
                th, td {
                    border-bottom: 1px solid #e5e7eb;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                <div>
                    <a href="index.php" class="btn-back">← Retour</a>
                    <button onclick="window.print()" class="btn-print">🖨️ Imprimer le rapport</button>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <?php if ($type === 'products' || $type === 'low_stock'): ?>
                            <th>ID</th>
                            <th>Nom du produit</th>
                            <th>SKU</th>
                            <th>Catégorie</th>
                            <th>Qté</th>
                            <th>Seuil Min</th>
                            <th>Statut</th>
                            <th>Emplacement</th>
                            <th>Fournisseur</th>
                        <?php else: ?>
                            <th>Date</th>
                            <th>Produit</th>
                            <th>SKU</th>
                            <th>Action</th>
                            <th>Ancienne Qté</th>
                            <th>Nouvelle Qté</th>
                            <th>Admin</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="<?php echo ($type === 'products' || $type === 'low_stock') ? 9 : 7; ?>" style="text-align: center;">Aucune donnée disponible</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php if ($type === 'products' || $type === 'low_stock'): ?>
                                    <td><?php echo htmlspecialchars($row['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['sku'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['quantity'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['min_stock_level'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo htmlspecialchars($row['status'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $row['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['location'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['supplier'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php else: ?>
                                    <td><?php echo htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['product_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['sku'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(str_replace('_', ' ', $row['action_type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['old_quantity'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['new_quantity'] ?? 0, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($row['admin'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$page_title = "Export Data";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/topbar.php';
?>

<!-- Content -->
<main class="content">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
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
                        
                        <button type="submit" class="btn btn-secondary">📤 Export Logs</button>
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

<?php require_once '../includes/footer.php'; ?>