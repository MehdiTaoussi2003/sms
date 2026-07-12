<?php
/**
 * Create Customer
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
                $stmt = $db->prepare("INSERT INTO customers (customer_code, name, phone, email, address, city, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $name, $phone, $email, $address, $city, $notes]);
                $success = "Customer created successfully.";
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

$page_title = "Add Customer";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">
<div class="card" style="max-width: 600px; margin: 0 auto;">
                    <div class="card-header">
                        <h3 class="card-title">Create New Customer</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?> <a href="index.php">Back to list</a></div><?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo CSRF::generateToken(); ?>">
                            
                            <div class="form-group">
                                <label class="form-label">Customer Code *</label>
                                <input type="text" name="customer_code" class="form-control" required placeholder="CUST-001">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control">
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
                                <button type="submit" class="btn btn-primary">Save Customer</button>
                                <a href="index.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>
