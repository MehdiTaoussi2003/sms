<?php
/**
 * Deliveries List
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';

Auth::requireLogin();
$admin = Auth::getAdminInfo();

$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $db = Database::getInstance()->getConnection();
    
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(d.delivery_code LIKE ? OR c.name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "d.delivery_status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM deliveries d 
        JOIN customers c ON d.customer_id = c.id
        $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_deliveries = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_deliveries / $per_page);
    
    // Get deliveries
    $sql = "
        SELECT 
            d.*,
            c.name as customer_name,
            l.name as source_location_name,
            (SELECT SUM(quantity) FROM delivery_items WHERE delivery_id = d.id) as total_items
        FROM deliveries d
        JOIN customers c ON d.customer_id = c.id
        LEFT JOIN locations l ON d.source_location_id = l.id
        $where_clause
        ORDER BY d.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Deliveries list error: " . $e->getMessage());
    $deliveries = [];
    $total_deliveries = 0;
    $total_pages = 1;
}

$page_title = "Deliveries";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">

            <header class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <h1 class="page-title"><span class="title-icon">🚚</span> Deliveries</h1>
                </div>
            </header>
            
            <main class="content mt-4">
                <div class="filter-bar"><form method="GET" action="" class="w-full"><div class="form-row">
                        <div class="form-group">
                            <input type="text" name="search" class="form-control" placeholder="Search code or customer..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <select name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <?php 
                                $statuses = ['pending', 'packed', 'dispatched', 'in_transit', 'delivered', 'returned', 'cancelled'];
                                foreach ($statuses as $s) {
                                    $selected = ($status_filter === $s) ? 'selected' : '';
                                    echo "<option value=\"$s\" $selected>" . ucfirst($s) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="index.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div></form>
                </div>
                
                <div class="card">
                    <div class="card-header justify-between">
                            <h3 class="card-title">Deliveries List</h3>
                            <a href="create.php" class="btn btn-primary btn-sm"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Create Delivery</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($deliveries)): ?>
                            <div class="alert alert-info">No deliveries found.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Delivery Code</th>
                                            <th>Customer</th>
                                            <th>Source Location</th>
                                            <th>Total Items</th>
                                            <th>Status</th>
                                            <th>Created Date</th>
                                            <th class="no-print">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($deliveries as $d): ?>
                                            <?php
                                                // Dynamic Badge
                                                $b_class = 'badge-secondary';
                                                switch($d['delivery_status']) {
                                                    case 'pending': $b_class = 'badge-secondary'; break;
                                                    case 'packed': $b_class = 'badge-info'; break;
                                                    case 'dispatched':
                                                    case 'in_transit': $b_class = 'badge-primary'; break;
                                                    case 'delivered': $b_class = 'badge-success'; break;
                                                    case 'cancelled':
                                                    case 'returned': $b_class = 'badge-danger'; break;
                                                }
                                            ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($d['delivery_code']); ?></code></td>
                                            <td><strong><?php echo htmlspecialchars($d['customer_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($d['source_location_name'] ?? 'Not Assigned'); ?></td>
                                            <td><?php echo number_format($d['total_items'] ?? 0); ?></td>
                                            <td><span class="badge <?php echo $b_class; ?>"><?php echo ucfirst($d['delivery_status']); ?></span></td>
                                            <td><?php echo date('M j, Y', strtotime($d['created_at'])); ?></td>
                                            <td class="no-print">
                                                <a href="view.php?id=<?php echo $d['id']; ?>" class="btn btn-secondary btn-sm">View / Manage</a>
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
