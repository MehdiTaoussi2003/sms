<?php
/**
 * Delivery Status Update Logic
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    redirect_to('deliveries/index.php');
}

$db = Database::getInstance()->getConnection();

$id = intval($_POST['delivery_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$return_loc = !empty($_POST['return_location_id']) ? intval($_POST['return_location_id']) : null;

$valid_statuses = ['pending', 'packed', 'dispatched', 'in_transit', 'delivered', 'returned', 'cancelled'];

if ($id <= 0 || !in_array($new_status, $valid_statuses)) {
    redirect_to('deliveries/view.php?id=' . $id . '&err=' . urlencode('Invalid data.'));
}

try {
    $db->beginTransaction();
    
    // Get delivery
    $stmt = $db->prepare("SELECT * FROM deliveries WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $delivery = $stmt->fetch();
    if (!$delivery) throw new Exception("Delivery not found.");
    
    $old_status = $delivery['delivery_status'];
    if ($old_status === $new_status) {
        throw new Exception("Status is already $new_status.");
    }
    
    // Get items
    $items = $db->query("SELECT product_id, quantity FROM delivery_items WHERE delivery_id = $id")->fetchAll();
    
    // RULES FOR STOCK SYNC:
    // DISPATCHING: deduct stock globally, and deduct locally if source_location_id exists
    if ($new_status === 'dispatched' && !in_array($old_status, ['dispatched', 'in_transit', 'delivered'])) {
        foreach ($items as $it) {
            // Deduct global
            $stmt = $db->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
            $stmt->execute([$it['quantity'], $it['product_id'], $it['quantity']]);
            if ($stmt->rowCount() === 0) throw new Exception("Insufficient global stock during dispatch to deduct.");
            
            // Deduct local if source exists
            if ($delivery['source_location_id']) {
                $stmt = $db->prepare("UPDATE product_locations SET quantity = quantity - ? WHERE product_id = ? AND location_id = ? AND quantity >= ?");
                $stmt->execute([$it['quantity'], $it['product_id'], $delivery['source_location_id'], $it['quantity']]);
                if ($stmt->rowCount() === 0) throw new Exception("Insufficient local stock at source location during dispatch.");
            }
            
            // Add stock_logs for dispatch
            $db->prepare("INSERT INTO stock_logs (product_id, admin_id, action_type, notes, ip_address) VALUES (?, ?, 'stock_out', ?, ?)")
               ->execute([$it['product_id'], $admin['id'], "Dispatched with delivery {$delivery['delivery_code']}", $_SERVER['REMOTE_ADDR']]);
        }
    }
    
    // RETURNING: Add stock globally and add locally to return_loc if provided.
    // Assuming we can only return if it was already shipped (dispatched, in_transit, delivered)
    if ($new_status === 'returned' && in_array($old_status, ['dispatched', 'in_transit', 'delivered'])) {
        foreach ($items as $it) {
            // Restore global
            $db->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$it['quantity'], $it['product_id']]);
            
            // Restore local
            if ($return_loc) {
                $check = $db->prepare("SELECT id FROM product_locations WHERE product_id=? AND location_id=?");
                $check->execute([$it['product_id'], $return_loc]);
                if ($check->fetchColumn()) {
                    $db->prepare("UPDATE product_locations SET quantity = quantity + ? WHERE product_id = ? AND location_id = ?")->execute([$it['quantity'], $it['product_id'], $return_loc]);
                } else {
                    $db->prepare("INSERT INTO product_locations (product_id, location_id, quantity) VALUES (?,?,?)")->execute([$it['product_id'], $return_loc, $it['quantity']]);
                }
            }
            
            $db->prepare("INSERT INTO stock_logs (product_id, admin_id, action_type, notes, ip_address) VALUES (?, ?, 'stock_in', ?, ?)")
               ->execute([$it['product_id'], $admin['id'], "Returned from delivery {$delivery['delivery_code']}", $_SERVER['REMOTE_ADDR']]);
        }
    }
    
    // CANCELLING: Only valid before dispatch, so no stock operation needed, just status update
    
    // Update Delivery status
    $dates_sql = "";
    if ($new_status === 'delivered') $dates_sql = ", delivered_date = CURRENT_DATE";
    
    $stmt = $db->prepare("UPDATE deliveries SET delivery_status = ? $dates_sql WHERE id = ?");
    $stmt->execute([$new_status, $id]);
    
    // Log Status Change
    $stmt = $db->prepare("INSERT INTO delivery_status_logs (delivery_id, old_status, new_status, changed_by, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $old_status, $new_status, $admin['id'], $notes]);
    
    $db->commit();
    redirect_to('deliveries/view.php?id=' . $id . '&msg=' . urlencode('Status updated successfully.'));
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    redirect_to('deliveries/view.php?id=' . $id . '&err=' . urlencode($e->getMessage()));
}
