<?php
/**
 * Edit Location
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

$db = Database::getInstance()->getConnection();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect_to('locations/index.php');
}

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
        $status = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
        
        if ($parent_id === $id) {
            $error = "A location cannot be its own parent.";
        } elseif (empty($code) || empty($name) || empty($type)) {
            $error = "Code, Name, and Type are required.";
        } else {
            try {
                $stmt = $db->prepare("UPDATE locations SET location_code = ?, name = ?, type = ?, parent_id = ?, address = ?, city = ?, notes = ?, status = ? WHERE id = ?");
                $stmt->execute([$code, $name, $type, $parent_id, $address, $city, $notes, $status, $id]);
                $success = "Location updated successfully.";
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

// Fetch current
$stmt = $db->prepare("SELECT * FROM locations WHERE id = ?");
$stmt->execute([$id]);
$location = $stmt->fetch();

if (!$location) {
    redirect_to('locations/index.php');
}

// Fetch possible parents (exclude self and children to avoid circular loops - simple check here just excludes self)
$parents_stmt = $db->prepare("SELECT id, name, location_code FROM locations WHERE id != ? AND status = 'active' ORDER BY name ASC");
$parents_stmt->execute([$id]);
$parents = $parents_stmt->fetchAll();

$page_title = "Edit Location";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div class="card-header">
                        <h3 class="card-title">Edit Location: <?php echo htmlspecialchars($location['name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="index.php">Back to list</a></div><?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Location Code *</label>
                                <input type="text" name="location_code" class="form-control" required value="<?php echo htmlspecialchars($location['location_code']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($location['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Type *</label>
                                <select name="type" class="form-select" required>
                                    <?php 
                                    $types = ['warehouse', 'zone', 'aisle', 'rack', 'shelf', 'bin', 'store'];
                                    foreach($types as $t): 
                                    ?>
                                        <option value="<?php echo $t; ?>" <?php echo $location['type'] === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Parent Location</label>
                                <select name="parent_id" class="form-select">
                                    <option value="">-- None (Top Level) --</option>
                                    <?php foreach ($parents as $p): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo $location['parent_id'] == $p['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['name'] . ' (' . $p['location_code'] . ')'); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="active" <?php echo $location['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $location['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($location['city']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($location['address']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Location</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>
