<?php
/**
 * View Location Details & Products
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

$db = Database::getInstance()->getConnection();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect_to('locations/index.php');
}

// Fetch Location
$stmt = $db->prepare("SELECT l.*, p.name as parent_name FROM locations l LEFT JOIN locations p ON l.parent_id = p.id WHERE l.id = ?");
$stmt->execute([$id]);
$location = $stmt->fetch();

if (!$location) {
    redirect_to('locations/index.php');
}

// Fetch Products in this Location
$prod_stmt = $db->prepare("
    SELECT p.id, p.product_name, p.sku, pl.quantity, c.name as category_name
    FROM product_locations pl
    JOIN products p ON pl.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE pl.location_id = ? AND pl.quantity > 0
    ORDER BY p.product_name ASC
");
$prod_stmt->execute([$id]);
$products = $prod_stmt->fetchAll();

$page_title = "Location Details";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card mb-4">
                    <div class="card-header justify-between">
                            <h3 class="card-title">Location: <?php echo htmlspecialchars($location['name']); ?></h3>
                            <a href="edit.php?id=<?php echo $location['id']; ?>" class="btn btn-primary btn-sm">Edit Setting</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p><strong>Code:</strong> <code><?php echo htmlspecialchars($location['location_code']); ?></code></p>
                                <p><strong>Type:</strong> <?php echo ucfirst($location['type']); ?></p>
                                <p><strong>Parent:</strong> <?php echo $location['parent_name'] ? htmlspecialchars($location['parent_name']) : 'None'; ?></p>
                            </div>
                            <div>
                                <p><strong>Status:</strong> <span class="badge <?php echo $location['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>"><?php echo ucfirst($location['status']); ?></span></p>
                                <p><strong>City:</strong> <?php echo htmlspecialchars($location['city'] ?? '-'); ?></p>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($location['address'] ?? '-'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Products in this Location</h3>
                    </div>
                    <div class="card-body">
                        <?php if(empty($products)): ?>
                            <div class="alert alert-info">No products currently stored in this location.</div>
                        <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>Quantity Available</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($products as $p): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($p['sku']); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($p['product_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($p['category_name']); ?></td>
                                        <td><strong><?php echo number_format($p['quantity']); ?></strong></td>
                                        <td>
                                            <a href="<?php echo url('products/edit.php?id=' . $p['id']); ?>" class="btn btn-secondary btn-sm">Manage</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>
