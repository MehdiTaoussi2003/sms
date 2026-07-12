<?php
// Prevents direct access
if (!defined('SMS_INCLUDED')) {
    exit('Direct access forbidden');
}
if (!isset($page_title)) {
    $page_title = "Admin";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> - Stock Management System</title>
    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?php echo url('assets/css/design-system.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/layout.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/components.css'); ?>">
    <link rel="stylesheet" href="<?php echo url('assets/css/pages.css'); ?>">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar and Backdrop -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
