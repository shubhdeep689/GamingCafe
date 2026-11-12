<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Authentication & Security Engine
 */

require_once dirname(__DIR__) . '/config/config.php';

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_user_id']) && !empty($_SESSION['admin_username']);
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        set_flash('danger', 'You must log in to access the Ironclad Management Sanctuary.');
        redirect(BASE_URL . 'admin/login.php');
    }
}

function current_admin(): ?array {
    if (!is_admin_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['admin_user_id'],
        'username'  => $_SESSION['admin_username'],
        'full_name' => $_SESSION['admin_full_name'] ?? 'Admin',
        'role'      => $_SESSION['admin_role'] ?? 'manager',
        'email'     => $_SESSION['admin_email'] ?? ''
    ];
}

function admin_login_attempt(string $username, string $password): array {
    $db = get_db();
    $username = trim($username);

    if (empty($username) || empty($password)) {
        return ['success' => false, 'message' => 'Please provide both username and password.'];
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // Uniform delay against timing attacks
        usleep(random_int(100000, 300000));
        return ['success' => false, 'message' => 'Invalid username or credentials.'];
    }

    // Check account lockout
    if (!empty($admin['lockout_until']) && strtotime($admin['lockout_until']) > time()) {
        $minutes_left = ceil((strtotime($admin['lockout_until']) - time()) / 60);
        return [
            'success' => false,
            'message' => "Security lockout in effect due to excessive failed attempts. Please retry in {$minutes_left} minute(s)."
        ];
    }

    // Verify password hash
    if (!password_verify($password, $admin['password_hash'])) {
        $new_attempts = $admin['failed_attempts'] + 1;
        $lockout = null;
        
        // 5 consecutive failed attempts trigger a 15-minute lockout
        if ($new_attempts >= 5) {
            $lockout = date('Y-m-d H:i:s', time() + (15 * 60));
            $upd = $db->prepare("UPDATE admins SET failed_attempts = :f, lockout_until = :l WHERE id = :id");
            $upd->execute([':f' => $new_attempts, ':l' => $lockout, ':id' => $admin['id']]);
            log_admin_activity($admin['id'], 'admin_lockout', "Account temporarily locked for 15 minutes after 5 failed attempts.");
            return [
                'success' => false,
                'message' => 'Account temporarily locked due to 5 consecutive failed attempts. Please retry after 15 minutes.'
            ];
        } else {
            $upd = $db->prepare("UPDATE admins SET failed_attempts = :f WHERE id = :id");
            $upd->execute([':f' => $new_attempts, ':id' => $admin['id']]);
            return [
                'success' => false,
                'message' => 'Invalid credentials. Attempt ' . $new_attempts . ' of 5 before temporary lockout.'
            ];
        }
    }

    // Login Successful: Reset failed attempts, update last login
    $reset = $db->prepare("UPDATE admins SET failed_attempts = 0, lockout_until = NULL, last_login = NOW() WHERE id = :id");
    $reset->execute([':id' => $admin['id']]);

    // Session fixation mitigation
    session_regenerate_id(true);

    $_SESSION['admin_user_id']   = $admin['id'];
    $_SESSION['admin_username']  = $admin['username'];
    $_SESSION['admin_full_name'] = $admin['full_name'];
    $_SESSION['admin_role']      = $admin['role'];
    $_SESSION['admin_email']     = $admin['email'];

    log_admin_activity($admin['id'], 'admin_login', "Successful login from IP " . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));

    return ['success' => true];
}

function admin_logout(): void {
    if (is_admin_logged_in()) {
        log_admin_activity($_SESSION['admin_user_id'], 'admin_logout', "User logged out.");
    }
    
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

