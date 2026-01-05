<?php
/**
 * Product Update API (for QR Scanner)
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
    
    // Validate CSRF token
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        throw new Exception('Invalid security token');
    }
    
    // Get and validate input
    $product_id = intval($_POST['product_id'] ?? 0);
    $new_quantity = max(0, intval($_POST['quantity'] ?? 0));
    $new_status = $_POST['status'] ?? '';
    $new_location = trim($_POST['location'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }
    
    if (!in_array($new_status, ['in_stock', 'low_stock', 'out_of_stock', 'damaged'])) {
        throw new Exception('Invalid status');
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get current product data
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $current_product = $stmt->fetch();
    
    if (!$current_product) {
        throw new Exception('Product not found');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Update product
        $update_stmt = $db->prepare("
            UPDATE products 
            SET quantity = ?, status = ?, location = ?, notes = COALESCE(NULLIF(?, ''), notes)
            WHERE id = ?
        ");
        
        $update_stmt->execute([
            $new_quantity,
            $new_status,
            $new_location ?: null,
            $notes,
            $product_id
        ]);
        
        // Log the update
        $changes = [];
        if ($current_product['quantity'] != $new_quantity) {
            $changes[] = "Quantity: {$current_product['quantity']} → $new_quantity";
        }
        if ($current_product['status'] != $new_status) {
            $changes[] = "Status: {$current_product['status']} → $new_status";
        }
        if ($current_product['location'] != $new_location) {
            $changes[] = "Location: '{$current_product['location']}' → '$new_location'";
        }
        
        $log_notes = empty($changes) ? 'Product updated via QR scanner' : 
                    'Updated via QR scanner: ' . implode(', ', $changes);
        
        if (!empty($notes)) {
            $log_notes .= ' | Notes: ' . $notes;
        }
        
        $log_stmt = $db->prepare("
            INSERT INTO stock_logs (
                product_id, admin_id, action_type, old_quantity, new_quantity,
                old_status, new_status, old_location, new_location, notes,
                ip_address, user_agent
            ) VALUES (?, ?, 'scan_update', ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $log_stmt->execute([
            $product_id,
            $admin['id'],
            $current_product['quantity'],
            $new_quantity,
            $current_product['status'],
            $new_status,
            $current_product['location'],
            $new_location ?: null,
            $log_notes,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Get updated product data
        $stmt = $db->prepare("
            SELECT 
                p.id, p.product_name, p.sku, c.name as category_name,
                p.quantity, p.min_stock_level, p.status, p.location, p.supplier,
                p.qr_code_value, p.notes, p.last_updated
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$product_id]);
        $updated_product = $stmt->fetch();
        
        $db->commit();
        
        $response['success'] = true;
        $response['message'] = 'Product updated successfully';
        $response['product'] = $updated_product;
        
    } catch (Exception $e) {
        $db->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Product update error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>