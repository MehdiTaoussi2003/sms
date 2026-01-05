<?php
/**
 * Product Lookup API
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

// Set JSON response header
header('Content-Type: application/json');

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();
$response = ['success' => false, 'message' => '', 'product' => null];

try {
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate CSRF token
    if (!CSRF::validateToken($input['csrf_token'] ?? '')) {
        throw new Exception('Invalid security token');
    }
    
    $qr_value = trim($input['qr_value'] ?? '');
    
    if (empty($qr_value)) {
        throw new Exception('QR code value is required');
    }
    
    // Look up product by QR code value
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT 
            p.id, p.product_name, p.sku, p.category_id, c.name as category_name,
            p.quantity, p.min_stock_level, p.status, p.location, p.supplier,
            p.purchase_date, p.purchase_price, p.selling_price, p.qr_code_value,
            p.notes, p.created_at, p.updated_at, p.last_updated
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.qr_code_value = ?
    ");
    
    $stmt->execute([$qr_value]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Product not found with QR code: ' . $qr_value);
    }
    
    // Log the scan
    $log_stmt = $db->prepare("
        INSERT INTO stock_logs (
            product_id, admin_id, action_type, old_quantity, new_quantity,
            old_status, new_status, notes, ip_address, user_agent
        ) VALUES (?, ?, 'scan_lookup', ?, ?, ?, ?, 'QR code scanned', ?, ?)
    ");
    
    $log_stmt->execute([
        $product['id'],
        $admin['id'],
        $product['quantity'],
        $product['quantity'],
        $product['status'],
        $product['status'],
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
    
    $response['success'] = true;
    $response['message'] = 'Product found successfully';
    $response['product'] = $product;
    
} catch (Exception $e) {
    error_log("Product lookup error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>