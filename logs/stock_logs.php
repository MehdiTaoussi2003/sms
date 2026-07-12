<?php
/**
 * Stock History Logs
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../auth/auth_check.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

// Get filter parameters
$product_filter = trim($_GET['product'] ?? '');
$admin_filter = intval($_GET['admin'] ?? 0);
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

try {
    $db = Database::getInstance()->getConnection();
    
    // Build WHERE clause for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($product_filter)) {
        $where_conditions[] = "(p.product_name LIKE ? OR p.sku LIKE ?)";
        $product_param = "%$product_filter%";
        $params[] = $product_param;
        $params[] = $product_param;
    }
    
    if ($admin_filter > 0) {
        $where_conditions[] = "sl.admin_id = ?";
        $params[] = $admin_filter;
    }
    
    if (!empty($action_filter)) {
        $where_conditions[] = "sl.action_type = ?";
        $params[] = $action_filter;
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(sl.created_at) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(sl.created_at) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM stock_logs sl
        JOIN products p ON sl.product_id = p.id
        JOIN admins a ON sl.admin_id = a.id
        $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_logs = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_logs / $per_page);
    
    // Get logs with pagination
    $sql = "
        SELECT 
            sl.id, sl.action_type, sl.old_quantity, sl.new_quantity, 
            sl.old_status, sl.new_status, sl.old_location, sl.new_location,
            sl.notes, sl.created_at, sl.ip_address,
            p.id as product_id, p.product_name, p.sku,
            a.username as admin_username
        FROM stock_logs sl
        JOIN products p ON sl.product_id = p.id
        JOIN admins a ON sl.admin_id = a.id
        $where_clause
        ORDER BY sl.created_at DESC, sl.id DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    
    // Get admins for filter dropdown
    $admins_stmt = $db->query("SELECT id, username FROM admins ORDER BY username");
    $admins = $admins_stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Stock logs error: " . $e->getMessage());
    $logs = [];
    $admins = [];
    $total_logs = 0;
    $total_pages = 1;
}

$page_title = "Stock Logs";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">

                <!-- Filter Bar -->
                <div class="filter-bar"><form method="GET" action="" class="w-full"><div class="form-row">
                        <div class="form-group">
                            <label for="product" class="form-label">Product</label>
                            <input 
                                type="text" 
                                id="product" 
                                name="product" 
                                class="form-control" 
                                placeholder="Product name or SKU..."
                                value="<?php echo htmlspecialchars($product_filter, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="admin" class="form-label">Admin</label>
                            <select id="admin" name="admin" class="form-select">
                                <option value="">All Admins</option>
                                <?php foreach ($admins as $admin_option): ?>
                                <option value="<?php echo $admin_option['id']; ?>" <?php echo $admin_filter == $admin_option['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin_option['username'], ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="action" class="form-label">Action</label>
                            <select id="action" name="action" class="form-select">
                                <option value="">All Actions</option>
                                <option value="create" <?php echo $action_filter === 'create' ? 'selected' : ''; ?>>Create</option>
                                <option value="update" <?php echo $action_filter === 'update' ? 'selected' : ''; ?>>Update</option>
                                <option value="delete" <?php echo $action_filter === 'delete' ? 'selected' : ''; ?>>Delete</option>
                                <option value="stock_in" <?php echo $action_filter === 'stock_in' ? 'selected' : ''; ?>>Stock In</option>
                                <option value="stock_out" <?php echo $action_filter === 'stock_out' ? 'selected' : ''; ?>>Stock Out</option>
                                <option value="adjustment" <?php echo $action_filter === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                                <option value="scan_update" <?php echo $action_filter === 'scan_update' ? 'selected' : ''; ?>>QR Scan Update</option>
                                <option value="scan_lookup" <?php echo $action_filter === 'scan_lookup' ? 'selected' : ''; ?>>QR Scan Lookup</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_from" class="form-label">From Date</label>
                            <input 
                                type="date" 
                                id="date_from" 
                                name="date_from" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($date_from, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="date_to" class="form-label">To Date</label>
                            <input 
                                type="date" 
                                id="date_to" 
                                name="date_to" 
                                class="form-control" 
                                value="<?php echo htmlspecialchars($date_to, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> Filter</button>
                            <a href="/sms/logs/stock_logs.php" class="btn btn-secondary">Clear</a>
                        </div>
                    </div></form>
                </div>
                
                <!-- Results -->
                <div class="card">
                    <div class="card-header justify-between">
                            <h3 class="card-title">
                                Stock Activity Log 
                                <span class="badge badge-secondary"><?php echo number_format($total_logs); ?> entries</span>
                            </h3>
                            <div class="btn-group">
                                <a href="/sms/exports/?type=logs" class="btn btn-secondary btn-sm">📤 Export</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($logs)): ?>
                            <div class="alert alert-info text-center">
                                <?php if (!empty($product_filter) || $admin_filter > 0 || !empty($action_filter) || !empty($date_from) || !empty($date_to)): ?>
                                    No log entries found matching your filter criteria.
                                    <br><a href="/sms/logs/stock_logs.php" class="btn btn-secondary btn-sm mt-2">View All Logs</a>
                                <?php else: ?>
                                    No stock activity logged yet.
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date/Time</th>
                                            <th>Product</th>
                                            <th>Action</th>
                                            <th>Changes</th>
                                            <th>Admin</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <small><?php echo date('M j, Y', strtotime($log['created_at'])); ?></small><br>
                                                <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($log['product_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['sku'], ENT_QUOTES, 'UTF-8'); ?></small>
                                            </td>
                                            <td>
                                                <?php
                                                $action_labels = [
                                                    'create' => ['Created', 'badge-success'],
                                                    'update' => ['Updated', 'badge-info'],
                                                    'delete' => ['Deleted', 'badge-danger'],
                                                    'stock_in' => ['Stock In', 'badge-success'],
                                                    'stock_out' => ['Stock Out', 'badge-warning'],
                                                    'adjustment' => ['Adjusted', 'badge-secondary'],
                                                    'scan_update' => ['QR Update', 'badge-primary'],
                                                    'scan_lookup' => ['QR Scan', 'badge-secondary']
                                                ];
                                                
                                                $action_info = $action_labels[$log['action_type']] ?? [ucfirst($log['action_type']), 'badge-secondary'];
                                                ?>
                                                <span class="badge <?php echo $action_info[1]; ?>">
                                                    <?php echo htmlspecialchars($action_info[0], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($log['old_quantity'] !== null && $log['new_quantity'] !== null && $log['old_quantity'] != $log['new_quantity']): ?>
                                                    <div><strong>Qty:</strong> <?php echo number_format($log['old_quantity']); ?> → <?php echo number_format($log['new_quantity']); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if ($log['old_status'] && $log['new_status'] && $log['old_status'] != $log['new_status']): ?>
                                                    <div><strong>Status:</strong> <?php echo ucfirst(str_replace('_', ' ', $log['old_status'])); ?> → <?php echo ucfirst(str_replace('_', ' ', $log['new_status'])); ?></div>
                                                <?php endif; ?>
                                                
                                                <?php if ($log['old_location'] !== $log['new_location']): ?>
                                                    <div><strong>Location:</strong> '<?php echo htmlspecialchars($log['old_location'] ?: 'None', ENT_QUOTES, 'UTF-8'); ?>' → '<?php echo htmlspecialchars($log['new_location'] ?: 'None', ENT_QUOTES, 'UTF-8'); ?>'</div>
                                                <?php endif; ?>
                                                
                                                <?php if (!$log['old_quantity'] && !$log['new_quantity'] && !$log['old_status']): ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($log['admin_username'], ENT_QUOTES, 'UTF-8'); ?>
                                                <?php if ($log['ip_address'] && $log['ip_address'] !== 'unknown'): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['notes']): ?>
                                                    <small><?php echo htmlspecialchars($log['notes'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="pagination-container text-center mt-4">
                                    <div class="pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" class="btn btn-secondary btn-sm">First</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary btn-sm">Previous</a>
                                        <?php endif; ?>
                                        
                                        <?php 
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        for ($i = $start_page; $i <= $end_page; $i++): 
                                        ?>
                                            <?php if ($i === $page): ?>
                                                <span class="btn btn-primary btn-sm"><?php echo $i; ?></span>
                                            <?php else: ?>
                                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="btn btn-secondary btn-sm"><?php echo $i; ?></a>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary btn-sm">Next</a>
                                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="btn btn-secondary btn-sm">Last</a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Showing <?php echo number_format(($page - 1) * $per_page + 1); ?> to 
                                            <?php echo number_format(min($page * $per_page, $total_logs)); ?> of 
                                            <?php echo number_format($total_logs); ?> log entries
                                        </small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            
            </main>
<?php require_once '../includes/footer.php'; ?>