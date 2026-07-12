<?php
/**
 * API: Product Stock Information
 * Returns total global stock and stock mapped per physical location for a given product ID.
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';

Auth::requireLogin();
header('Content-Type: application/json');

$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid Product ID provided."]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get product info
    $stmt = $db->prepare("SELECT id, product_name, sku, quantity as global_qty FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(["status" => "error", "message" => "Product not found."]);
        exit;
    }
    
    // Get location breakdown
    $loc_stmt = $db->prepare("
        SELECT pl.quantity, pl.is_primary, l.id as location_id, l.name, l.location_code
        FROM product_locations pl
        JOIN locations l ON pl.location_id = l.id
        WHERE pl.product_id = ?
    ");
    $loc_stmt->execute([$product_id]);
    $locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response = [
        "status" => "success",
        "data" => [
            "product" => $product,
            "locations" => $locations
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
