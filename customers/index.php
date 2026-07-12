<?php
/**
 * Customers List
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $db = Database::getInstance()->getConnection();
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(name LIKE ? OR customer_code LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $search_param = "%$search%";
        $params = array_fill(0, 4, $search_param);
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM customers $where_clause");
    $count_stmt->execute($params);
    $total_customers = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_customers / $per_page);
    
    $sql = "SELECT * FROM customers $where_clause ORDER BY name ASC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Customers list error: " . $e->getMessage());
    $customers = [];
    $total_customers = 0;
    $total_pages = 1;
}

$page_title = "Customers";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">

            <header class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <h1 class="page-title"><span class="title-icon">👥</span> Customers</h1>
                </div>
            </header>
            
            <main class="content mt-4">
                <div class="filter-bar"><form method="GET" action="" class="w-full"><div class="form-row">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" placeholder="Search customers..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="index.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div></form>
                </div>
                
                <div class="card">
                    <div class="card-header justify-between">
                            <h3 class="card-title">Customers List</h3>
                            <a href="create.php" class="btn btn-primary btn-sm"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Add Customer</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($customers)): ?>
                            <div class="alert alert-info">No customers found. <a href="create.php">Create one</a>.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>City</th>
                                            <th class="no-print">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $c): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($c['customer_code']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($c['name']); ?></strong></td>
                                            <td>
                                                <?php echo htmlspecialchars($c['phone'] ?? '-'); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($c['email'] ?? '-'); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($c['city'] ?? '-'); ?></td>
                                            <td class="no-print">
                                                <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        
            </main>
<?php require_once '../includes/footer.php'; ?>
