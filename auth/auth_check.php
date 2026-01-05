<?php
/**
 * Authentication Check
 * Stock Management System (SMS)
 */

// Allow inclusion and load URL helpers
if (!defined('SMS_INCLUDED')) {
    define('SMS_INCLUDED', true);
}
require_once __DIR__ . '/../config/config.php';

class Auth {
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['admin_id']) && 
               isset($_SESSION['admin_username']) && 
               isset($_SESSION['admin_role']) &&
               isset($_SESSION['session_token']);
    }
    
    /**
     * Require authentication
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            // Store current absolute URL for redirect after login
            $_SESSION['redirect_after_login'] = current_url();
            redirect_to('login.php');
        }
        
        // Regenerate session ID periodically for security
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['admin_role'] === $role || $_SESSION['admin_role'] === 'admin';
    }
    
    /**
     * Get current admin info
     */
    public static function getAdminInfo() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'role' => $_SESSION['admin_role']
        ];
    }
    
    /**
     * Login admin
     */
    public static function login($admin_data) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['admin_id'] = $admin_data['id'];
        $_SESSION['admin_username'] = $admin_data['username'];
        $_SESSION['admin_role'] = $admin_data['role'];
        $_SESSION['session_token'] = bin2hex(random_bytes(32));
        $_SESSION['login_time'] = time();
        $_SESSION['last_regeneration'] = time();
    }
    
    /**
     * Logout admin
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear all session data
        $_SESSION = array();
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
}
?>