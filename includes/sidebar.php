<?php
if (!defined('SMS_INCLUDED')) exit;

$current_page = $_SERVER['PHP_SELF'];
?>
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-logo">
                <a href="<?php echo BASE_URL; ?>">
                    <svg class="nav-icon" style="color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <h1>SMS</h1>
                </a>
            </div>
            <ul class="sidebar-nav">
                <li>
                    <a href="<?php echo BASE_URL; ?>" class="<?php echo strpos($current_page, 'index.php') !== false && strpos($current_page, '/') === strrpos($current_page, '/') ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('products/list.php'); ?>" class="<?php echo strpos($current_page, 'products/list') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                        <span class="nav-text">Products</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('products/create.php'); ?>" class="<?php echo strpos($current_page, 'products/create') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span class="nav-text">Add Product</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('locations/index.php'); ?>" class="<?php echo strpos($current_page, 'locations/') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="nav-text">Locations</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('deliveries/index.php'); ?>" class="<?php echo strpos($current_page, 'deliveries/') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                        <span class="nav-text">Deliveries</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('customers/index.php'); ?>" class="<?php echo strpos($current_page, 'customers/') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="nav-text">Customers</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('qr/scan.php'); ?>" class="<?php echo strpos($current_page, 'qr/') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm14 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        <span class="nav-text">QR Scanner</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('logs/stock_logs.php'); ?>" class="<?php echo strpos($current_page, 'logs/') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="nav-text">Stock Logs</span>
                    </a>
                </li>
                <li>
                    <a href="<?php echo url('exports/'); ?>" class="<?php echo strpos($current_page, 'exports') !== false ? 'active' : ''; ?>">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span class="nav-text">Export Data</span>
                    </a>
                </li>
            </ul>
        </nav>
        
        <!-- Main Content Wrapper -->
        <div class="main-content">
