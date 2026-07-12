<?php
/**
 * API: Location Lookup
 * Returns location list as JSON.
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';

Auth::requireLogin();
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();
$locations = $db->query("SELECT id, location_code, name FROM locations WHERE status = 'active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["status" => "success", "data" => $locations]);
