<?php
/**
 * QR Code Generator and Printer
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once 'qr_generator.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();
$product = null;
$error_message = '';

// Get product ID
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    $error_message = 'Invalid product ID.';
} else {
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
            $error_message = 'Product not found.';
        } else {
            // Generate or update QR code file
            // Generate QR code using reliable API services
            $qr_value = $product['qr_code_value'];
            $qr_size = 300;
            
            // Try multiple QR generation services for reliability
            $qr_services = [
                'qr-server' => "https://api.qrserver.com/v1/create-qr-code/?size={$qr_size}x{$qr_size}&data=" . urlencode($qr_value) . "&format=PNG&margin=10",
                'quickchart' => "https://quickchart.io/qr?text=" . urlencode($qr_value) . "&size={$qr_size}&format=png&margin=2",
                'qrcode-api' => "https://qr-code-api.com/api/v1/qr?text=" . urlencode($qr_value) . "&size={$qr_size}&format=png"
            ];
            
            $qr_generated = false;
            
            foreach ($qr_services as $service_name => $qr_url) {
                try {
                    // Set context for HTTP request with timeout
                    $context = stream_context_create([
                        'http' => [
                            'timeout' => 10,
                            'user_agent' => 'Stock Management System'
                        ]
                    ]);
                    
                    $qr_image_data = @file_get_contents($qr_url, false, $context);
                    
                    if ($qr_image_data !== false && strlen($qr_image_data) > 100) {
                        // Save the QR image
                        $filename = 'qr_product_' . $product['id'] . '_' . date('Ymd_His') . '.png';
                        $filepath = '../assets/qrcodes/' . $filename;
                        
                        if (file_put_contents($filepath, $qr_image_data)) {
                            // Update product with QR code path
                            $update_stmt = $db->prepare("UPDATE products SET qr_code_path = ? WHERE id = ?");
                            $update_stmt->execute(['assets/qrcodes/' . $filename, $product_id]);
                            $product['qr_code_path'] = 'assets/qrcodes/' . $filename;
                            $product['qr_image_url'] = $qr_url;
                            $product['qr_service'] = $service_name;
                            $qr_generated = true;
                            break; // Success, exit loop
                        }
                    }
                } catch (Exception $e) {
                    error_log("QR service {$service_name} failed: " . $e->getMessage());
                    continue; // Try next service
                }
            }
            
            if (!$qr_generated) {
                // Final fallback: use QR Server API directly (most reliable)
                $product['qr_image_url'] = "https://api.qrserver.com/v1/create-qr-code/?size={$qr_size}x{$qr_size}&data=" . urlencode($qr_value);
                $product['qr_service'] = 'qr-server-direct';
            }
        }
    } catch (Exception $e) {
        error_log("QR generation error: " . $e->getMessage());
        $error_message = 'Failed to load product data.';
    }
}

$page_title = "QR Code Generator";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Stock Management System</title>
    <link rel="stylesheet" href="<?php echo url('assets/css/style.css'); ?>">
    <style>
        .qr-container {
            text-align: center;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .qr-code {
            max-width: 100%;
            height: auto;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .product-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
        }
        
        .print-section {
            margin-top: 20px;
        }
        
        @media print {
            .no-print { display: none !important; }
            .qr-container { 
                box-shadow: none;
                border: 1px solid #ddd;
                page-break-inside: avoid;
            }
            body { background: white !important; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="sidebar no-print">
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
            <header class="top-bar no-print">
                <h1 class="page-title"><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="admin-info">
                    <span>Welcome, <?php echo htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <a href="<?php echo url('logout.php'); ?>" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </header>
            
            <!-- Content -->
            <main class="content">
                <?php if ($error_message): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-error">
                                <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary">Back to Products</a>
                        </div>
                    </div>
                <?php elseif ($product): ?>
                    <div class="no-print mb-3">
                        <div class="btn-group">
                            <button onclick="window.print()" class="btn btn-primary">🖨️ Print QR Code</button>
                            <a href="<?php echo url('products/edit.php?id=' . $product_id); ?>" class="btn btn-secondary">Edit Product</a>
                            <a href="<?php echo url('products/list.php'); ?>" class="btn btn-secondary">Back to Products</a>
                        </div>
                    </div>
                    
                    <div class="qr-container">
                        <h2>Product QR Code</h2>
                        
                        <div class="qr-code-display">
                            <?php if (isset($product['qr_image_url'])): ?>
                                <!-- Direct Google Charts QR Code -->
                                <img src="<?php echo htmlspecialchars($product['qr_image_url'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="QR Code for <?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="qr-code"
                                     style="border: 2px solid #ddd; border-radius: 8px; background: white; padding: 10px;">
                            <?php elseif (!empty($product['qr_code_path']) && file_exists('../' . $product['qr_code_path'])): ?>
                                <!-- Saved QR Code File -->
                                <img src="../<?php echo htmlspecialchars($product['qr_code_path'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="QR Code for <?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                     class="qr-code"
                                     style="border: 2px solid #ddd; border-radius: 8px; background: white; padding: 10px;">
                            <?php else: ?>
                                <!-- Multiple Fallback QR Generation Options -->
                                <div style="text-align: center;">
                                    <!-- Primary Fallback: QR Server -->
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($product['qr_code_value']); ?>" 
                                         alt="QR Code for <?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         class="qr-code"
                                         style="border: 2px solid #ddd; border-radius: 8px; background: white; padding: 10px;"
                                         onerror="this.onerror=null; this.src='https://quickchart.io/qr?text=<?php echo urlencode($product['qr_code_value']); ?>&size=300';">
                                    
                                    <br><small style="color: #666; margin-top: 10px; display: block;">
                                        Fallback QR Generation - If image doesn't load, check internet connection
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- QR Code Debug Info -->
                        <div style="margin-top: 15px; padding: 15px; background: #e7f3ff; border-radius: 6px; font-size: 12px;">
                            <strong>🔍 QR Debug Info:</strong><br>
                            <strong>Value:</strong> <code style="background: #fff; padding: 2px 4px; border-radius: 3px;"><?php echo htmlspecialchars($product['qr_code_value'], ENT_QUOTES, 'UTF-8'); ?></code><br>
                            <strong>Length:</strong> <?php echo strlen($product['qr_code_value']); ?> characters<br>
                            <?php if (isset($product['qr_service'])): ?>
                                <strong>Service:</strong> <?php echo htmlspecialchars($product['qr_service'], ENT_QUOTES, 'UTF-8'); ?><br>
                            <?php endif; ?>
                            <?php if (isset($product['qr_image_url'])): ?>
                                <strong>Live URL:</strong> <small><?php echo htmlspecialchars($product['qr_image_url'], ENT_QUOTES, 'UTF-8'); ?></small><br>
                            <?php endif; ?>
                            <?php if (!empty($product['qr_code_path'])): ?>
                                <strong>Saved File:</strong> <?php echo htmlspecialchars($product['qr_code_path'], ENT_QUOTES, 'UTF-8'); ?><br>
                            <?php endif; ?>
                            
                            <!-- Test Links -->
                            <div style="margin-top: 10px;">
                                <strong>🧪 Test QR Services:</strong><br>
                                <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo urlencode($product['qr_code_value']); ?>" target="_blank" style="color: #007bff;">QR Server</a> | 
                                <a href="https://quickchart.io/qr?text=<?php echo urlencode($product['qr_code_value']); ?>&size=300" target="_blank" style="color: #007bff;">QuickChart</a> |
                                <a href="javascript:navigator.clipboard.writeText('<?php echo addslashes($product['qr_code_value']); ?>')" style="color: #007bff;">📋 Copy Value</a>
                            </div>
                        </div>
                        
                        <div class="product-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <h3><?php echo htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><strong>SKU:</strong> <code><?php echo htmlspecialchars($product['sku'], ENT_QUOTES, 'UTF-8'); ?></code></p>
                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><strong>Current Stock:</strong> <?php echo number_format($product['quantity']); ?> units</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> 
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
                                    </p>
                                    <?php if ($product['location']): ?>
                                    <p><strong>Location:</strong> <?php echo htmlspecialchars($product['location'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                    <?php if ($product['supplier']): ?>
                                    <p><strong>Supplier:</strong> <?php echo htmlspecialchars($product['supplier'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endif; ?>
                                    <p><strong>QR Value:</strong> <code style="word-break: break-all; font-size: 10px;"><?php echo htmlspecialchars($product['qr_code_value'], ENT_QUOTES, 'UTF-8'); ?></code></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="print-section">
                            <h4>Stock Management System (SMS)</h4>
                            <p class="text-muted">Generated on: <?php echo date('M j, Y H:i:s'); ?></p>
                            <p class="text-muted">Scan this QR code with the SMS mobile scanner to quickly access product information and update stock levels.</p>
                        </div>
                    </div>
                    
                    <div class="no-print">
                        <div class="card mt-4">
                            <div class="card-header">
                                <h3 class="card-title">Instructions</h3>
                            </div>
                            <div class="card-body">
                                <h5>How to use this QR code:</h5>
                                <ol>
                                    <li><strong>Print:</strong> Click the "Print QR Code" button to print this page</li>
                                    <li><strong>Attach:</strong> Cut and attach the QR code to the physical product or storage location</li>
                                    <li><strong>Scan:</strong> Use the QR Scanner in the SMS system to quickly access this product</li>
                                    <li><strong>Update:</strong> Real-time stock updates can be made by scanning the code</li>
                                </ol>
                                
                                <div class="alert alert-info">
                                    <strong>QR Code Value:</strong> <?php echo htmlspecialchars($product['qr_code_value'], ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script>
        // Print functionality
        function printQR() {
            window.print();
        }
        
        // Auto-print if requested
        if (new URLSearchParams(window.location.search).get('print') === '1') {
            window.onload = function() {
                setTimeout(printQR, 1000);
            };
        }
    </script>
</body>
</html>