<?php
if (!defined('SMS_INCLUDED')) exit;

// Expecting $page_title to be set before including topbar, else default
$display_title = isset($page_title) ? $page_title : 'Dashboard';

// Use active session info for the topbar if exists, fallback if not
$admin_name = isset($admin['username']) ? $admin['username'] : (isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Admin User');
$admin_role = isset($admin['role']) ? $admin['role'] : (isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : 'Administrator');
$initials = strtoupper(substr($admin_name, 0, 1));
?>
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Toggle Menu">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="page-title"><?php echo htmlspecialchars($display_title); ?></h2>
                </div>
                
                <div class="top-bar-right">
                    <div class="admin-info">
                        <div class="admin-welcome">
                            <span class="admin-name"><?php echo htmlspecialchars($admin_name); ?></span>
                            <span class="admin-role"><?php echo htmlspecialchars($admin_role); ?></span>
                        </div>
                        <div class="admin-avatar"><?php echo $initials; ?></div>
                        
                        <!-- Simple Logout Button using Ghost Style -->
                        <a href="<?php echo url('logout.php'); ?>" class="btn btn-ghost btn-sm" title="Logout" style="margin-left: 8px; padding: 4px;">
                            <svg fill="none" stroke="var(--color-danger)" viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </a>
                    </div>
                </div>
            </header>
