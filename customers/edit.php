<?php
/**
 * Edit Customer
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
    redirect_to('customers/index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token validation failed.";
    } else {
        $code = trim($_POST['customer_code']);
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $notes = trim($_POST['notes']);
        
        if (empty($code) || empty($name)) {
            $error = "Code and Name are required.";
        } else {
            try {
                $stmt = $db->prepare("UPDATE customers SET customer_code = ?, name = ?, phone = ?, email = ?, address = ?, city = ?, notes = ? WHERE id = ?");
                $stmt->execute([$code, $name, $phone, $email, $address, $city, $notes, $id]);
                $success = "Customer updated successfully.";
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Customer Code must be unique.";
                } else {
                    $error = "Database Error: " . $e->getMessage();
                }
            }
        }
    }
}

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirect_to('customers/index.php');
}

$page_title = "Edit Customer";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div class="card-header">
                        <h3 class="card-title">Edit Customer: <?php echo htmlspecialchars($customer['name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="index.php">Back to list</a></div><?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Customer Code *</label>
                                <input type="text" name="customer_code" class="form-control" required value="<?php echo htmlspecialchars($customer['customer_code']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($customer['name']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Update Customer</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>
