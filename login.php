<?php
/**
 * Admin Login Page
 * Stock Management System (SMS)
 */

define('SMS_INCLUDED', true);
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'auth/csrf.php';

session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : BASE_URL;
    unset($_SESSION['redirect_after_login']);
    redirect_to($redirect);
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Input validation
        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                
                // Find admin by username
                $stmt = $db->prepare("
                    SELECT id, username, email, password_hash, role, status, last_login 
                    FROM admins 
                    WHERE username = ? AND status = 'active'
                ");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password_hash'])) {
                    // Update last login
                    $updateStmt = $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                    $updateStmt->execute([$admin['id']]);
                    
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    // Set session variables
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['session_token'] = bin2hex(random_bytes(32));
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_activity'] = time();
                    
                    // Redirect to intended page or dashboard (absolute URL)
                    $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : BASE_URL;
                    unset($_SESSION['redirect_after_login']);
                    redirect_to($redirect);
                } else {
                    $error_message = 'Invalid username or password.';
                    
                    // Log failed login attempt
                    error_log("Failed login attempt for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                }
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error_message = 'A system error occurred. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stock Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1>Stock Management System</h1>
                <p>Please sign in to your account</p>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo CSRF::getTokenField(); ?>
                
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required 
                        autocomplete="username"
                        maxlength="50"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Sign In</button>
            </form>
            
            <div class="mt-4 text-center">
                <small class="text-muted">
                    Default credentials: admin / admin123<br>
                    Please change default password after first login.
                </small>
            </div>
        </div>
    </div>
    
    <script>
        // Focus on username field
        document.getElementById('username').focus();
        
        // Clear form on page unload for security
        window.addEventListener('beforeunload', function() {
            document.getElementById('password').value = '';
        });
    </script>
</body>
</html>