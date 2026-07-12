<?php
/**
 * Locations List
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once '../config/database.php';
require_once '../config/config.php';
require_once '../auth/auth_check.php';
require_once '../auth/csrf.php';

// Require authentication
Auth::requireLogin();

$admin = Auth::getAdminInfo();

// Get search and filter parameters
$search = trim($_GET['search'] ?? '');
$status_filter = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

try {
    $db = Database::getInstance()->getConnection();
    
    // Build WHERE clause for filters
    $where_conditions = [];
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(l.name LIKE ? OR l.location_code LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "l.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count for pagination
    $count_sql = "
        SELECT COUNT(*) as total 
        FROM locations l 
        $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_locations = $count_stmt->fetch()['total'];
    $total_pages = ceil($total_locations / $per_page);
    
    // Get locations with pagination
    $sql = "
        SELECT 
            l.*, 
            p.name as parent_name,
            (SELECT COUNT(*) FROM product_locations pl WHERE pl.location_id = l.id) as products_count
        FROM locations l 
        LEFT JOIN locations p ON l.parent_id = p.id
        $where_clause
        ORDER BY l.name ASC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $locations = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Locations list error: " . $e->getMessage());
    $locations = [];
    $total_locations = 0;
    $total_pages = 1;
}

$page_title = "Locations";
?>
<?php require_once '../includes/header.php'; ?>
<?php require_once '../includes/sidebar.php'; ?>
<?php require_once '../includes/topbar.php'; ?>

            <!-- Content -->
            <main class="content">

            <header class="top-bar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                        <span>☰</span>
                    </button>
                    <h1 class="page-title">
                        <span class="title-icon">🗺️</span>
                        <?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?>
                    </h1>
                </div>
                <div class="admin-info">
                    <div class="admin-welcome">
                        <div class="admin-name">Welcome, <?php echo htmlspecialchars($admin['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="admin-role"><?php echo htmlspecialchars($admin['role'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div class="admin-avatar">
                        <?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
                    </div>
                    <a href="<?php echo url('logout.php'); ?>" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </header>
            
            <main class="content">
                <!-- Filter Bar -->
                <div class="filter-bar"><form method="GET" action="" class="w-full"><div class="form-row">
                        <div class="form-group">
                            <label for="search" class="form-label">Search</label>
                            <input 
                                type="text" 
                                id="search" 
                                name="search" 
                                class="form-control" 
                                placeholder="Name or Code..."
                                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg> Search</button>
                            <a href="<?php echo url('locations/index.php'); ?>" class="btn btn-secondary">Clear</a>
                        </div>
                    </div></form>
                </div>
                
                <!-- Results -->
                <div class="card">
                    <div class="card-header justify-between">
                            <h3 class="card-title">
                                Locations List 
                                <span class="badge badge-secondary"><?php echo number_format($total_locations); ?> locations</span>
                            </h3>
                            <div class="btn-group">
                                <a href="<?php echo url('locations/create.php'); ?>" class="btn btn-primary btn-sm"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg> Add Location</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($locations)): ?>
                            <div class="alert alert-info text-center">
                                No locations found. 
                                <br><a href="<?php echo url('locations/create.php'); ?>" class="btn btn-primary btn-sm mt-2">Add Your First Location</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Location Code</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Parent Location</th>
                                            <th>Products Inside</th>
                                            <th>Status</th>
                                            <th class="no-print">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($locations as $loc): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($loc['location_code'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($loc['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                <?php if($loc['city']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($loc['city'], ENT_QUOTES, 'UTF-8'); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(ucfirst($loc['type']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars($loc['parent_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo number_format($loc['products_count']); ?> item(s)</td>
                                            <td>
                                                <span class="badge <?php echo $loc['status'] === 'active' ? 'badge-success' : 'badge-secondary'; ?>">
                                                    <?php echo ucfirst($loc['status']); ?>
                                                </span>
                                            </td>
                                            <td class="no-print">
                                                <div class="btn-group">
                                                    <a href="<?php echo url('locations/view.php?id=' . $loc['id']); ?>" class="btn btn-secondary btn-sm">View</a>
                                                    <a href="<?php echo url('locations/edit.php?id=' . $loc['id']); ?>" class="btn btn-primary btn-sm">Edit</a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <div class="pagination-container text-center mt-4">
                                   <!-- Standard Pagination Logic Omitted for brevity, but mimicking standard UI -->
                                   <div class="mt-2"><small class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></small></div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        
            </main>
<?php require_once '../includes/footer.php'; ?>
