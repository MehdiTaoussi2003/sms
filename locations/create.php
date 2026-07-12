<?php
/**
 * Create Location
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $code = trim($_POST['location_code']);
        $name = trim($_POST['name']);
        $type = trim($_POST['type']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $notes = trim($_POST['notes']);
        
        if (empty($code) || empty($name) || empty($type)) {
            $error = "Code, Name, and Type are required.";
        } else {
            try {
                $stmt = $db->prepare("INSERT INTO locations (location_code, name, type, parent_id, address, city, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $name, $type, $parent_id, $address, $city, $notes]);
                $success = "Location created successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Location Code must be unique.";
                } else {
                    $error = "Database Error: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch possible parents
$parents_stmt = $db->query("SELECT id, name, location_code FROM locations WHERE status = 'active' ORDER BY name ASC");
$parents = $parents_stmt->fetchAll();

$page_title = "Add Location";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div class="card-header">
                        <h3 class="card-title">Create New Location</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="index.php">Back to list</a></div><?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Location Code *</label>
                                <input type="text" name="location_code" class="form-control" required placeholder="e.g. WH1-A1">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" required placeholder="e.g. Warehouse A">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Type *</label>
                                <select name="type" class="form-select" required>
                                    <option value="warehouse">Warehouse</option>
                                    <option value="zone">Zone</option>
                                    <option value="aisle">Aisle</option>
                                    <option value="rack">Rack</option>
                                    <option value="shelf">Shelf</option>
                                    <option value="bin">Bin</option>
                                    <option value="store">Store</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Parent Location</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- None (Top Level) --</option>
                                    <?php foreach ($parents as $p): ?>
                                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name'] . ' (' . $p['location_code'] . ')'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Save Location</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>
