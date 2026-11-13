<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Authentication & Security Engine
 */

require_once dirname(__DIR__) . '/config/config.php';

function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_user_id']) && !empty($_SESSION['admin_username']);
}

function is_admin_owner(): bool {
    if (!is_admin_logged_in()) return false;
    if ((int)($_SESSION['admin_user_id'] ?? 0) === 1 || ($_SESSION['admin_username'] ?? '') === 'admin') return true;
    $role = strtolower($_SESSION['admin_role'] ?? '');
    return in_array($role, ['superadmin', 'owner', 'manager', 'developer']);
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        set_flash('danger', 'You must log in to access the Ironclad Management Sanctuary.');
        redirect(BASE_URL . 'admin/login.php');
    }
}

function require_admin_owner(): void {
    require_admin();
    if (!is_admin_owner()) {
        set_flash('danger', 'Access Denied: Owner/Superadmin privileges required for this management area.');
        redirect(BASE_URL . 'admin/dashboard.php');
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

    // Check if account is active
    if (isset($admin['is_active']) && (int)$admin['is_active'] === 0) {
        return ['success' => false, 'message' => 'This account has been deactivated. Please contact the cafe owner.'];
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
    
    unset(
        $_SESSION['admin_user_id'],
        $_SESSION['admin_username'],
        $_SESSION['admin_full_name'],
        $_SESSION['admin_role'],
        $_SESSION['admin_email']
    );
}

// -----------------------------------------------------------------------------
// ADMIN RECOVERY & PASSWORD RESET FUNCTIONS
// -----------------------------------------------------------------------------

function admin_generate_password_reset(string $email): array {
    $db = get_db();
    $email = strtolower(trim($email));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    $stmt = $db->prepare("SELECT * FROM admins WHERE LOWER(email) = :e AND is_active = 1 LIMIT 1");
    $stmt->execute([':e' => $email]);
    $admin = $stmt->fetch();

    if (!$admin) {
        // Return generic success to prevent email enumeration
        return [
            'success' => true, 
            'message' => 'If this email is registered, a password reset link has been dispatched.',
            'token' => null
        ];
    }

    // Generate random 64-character token
    $raw_token = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $raw_token);
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

    $upd = $db->prepare("UPDATE admins SET reset_token = :t, reset_expires_at = :exp WHERE id = :id");
    $upd->execute([':t' => $token_hash, ':exp' => $expires, ':id' => $admin['id']]);

    log_admin_activity($admin['id'], 'password_reset_request', "Password reset token requested for {$email}");

    return [
        'success'   => true,
        'message'   => 'Password reset token generated successfully.',
        'raw_token' => $raw_token,
        'admin'     => $admin
    ];
}

function admin_reset_password_with_token(string $raw_token, string $new_password): array {
    $db = get_db();
    $raw_token = trim($raw_token);
    $new_password = trim($new_password);

    if (empty($raw_token) || strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    $token_hash = hash('sha256', $raw_token);

    $stmt = $db->prepare("
        SELECT * FROM admins 
        WHERE reset_token = :t 
          AND reset_expires_at > NOW() 
          AND is_active = 1 
        LIMIT 1
    ");
    $stmt->execute([':t' => $token_hash]);
    $admin = $stmt->fetch();

    if (!$admin) {
        return ['success' => false, 'message' => 'This password reset link is invalid or has expired. Please request a new one.'];
    }

    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $upd = $db->prepare("
        UPDATE admins 
        SET password_hash = :h, 
            reset_token = NULL, 
            reset_expires_at = NULL, 
            failed_attempts = 0, 
            lockout_until = NULL, 
            updated_at = NOW() 
        WHERE id = :id
    ");
    $upd->execute([':h' => $new_hash, ':id' => $admin['id']]);

    log_admin_activity($admin['id'], 'password_reset_completed', "Password was successfully reset using verification token.");

    return ['success' => true, 'admin' => $admin];
}

function admin_developer_emergency_recovery(string $master_key, string $new_password): array {
    $db = get_db();
    $master_key = trim($master_key);
    $new_password = trim($new_password);

    $configured_key = env('DEVELOPER_RECOVERY_KEY', '');
    if (empty($configured_key) || strlen($configured_key) < 12) {
        return ['success' => false, 'message' => 'Developer recovery key is not configured or too short in .env file.'];
    }

    if (!hash_equals($configured_key, $master_key)) {
        log_admin_activity(1, 'developer_recovery_failed', "Invalid developer master key attempt from IP " . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        return ['success' => false, 'message' => 'Invalid developer recovery key. Access denied.'];
    }

    if (strlen($new_password) < 6) {
        return ['success' => false, 'message' => 'New password must be at least 6 characters.'];
    }

    // Locate the Owner / Superadmin account
    $stmt = $db->query("SELECT * FROM admins WHERE role = 'superadmin' OR id = 1 ORDER BY id ASC LIMIT 1");
    $owner = $stmt->fetch();

    if (!$owner) {
        return ['success' => false, 'message' => 'No owner account found to recover.'];
    }

    $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $upd = $db->prepare("
        UPDATE admins 
        SET password_hash = :h, 
            reset_token = NULL, 
            reset_expires_at = NULL, 
            failed_attempts = 0, 
            lockout_until = NULL, 
            is_active = 1 
        WHERE id = :id
    ");
    $upd->execute([':h' => $new_hash, ':id' => $owner['id']]);

    log_admin_activity($owner['id'], 'developer_recovery_success', "Emergency Owner account recovery executed by Developer via Master Key.");

    return ['success' => true, 'owner' => $owner];
}

// =============================================================================
// CUSTOMER / GAMER AUTHENTICATION ENGINE
// =============================================================================

function is_customer_logged_in(): bool {
    return !empty($_SESSION['customer_user_id']);
}

function is_user_logged_in(): bool {
    return is_customer_logged_in();
}

function current_customer(): ?array {
    if (!is_customer_logged_in()) {
        return null;
    }
    return [
        'id'        => (int)$_SESSION['customer_user_id'],
        'full_name' => $_SESSION['customer_name'] ?? 'Gamer',
        'phone'     => $_SESSION['customer_phone'] ?? '',
        'email'     => $_SESSION['customer_email'] ?? ''
    ];
}

function current_user(): ?array {
    return current_customer();
}

function require_customer(string $redirect_url = ''): void {
    if (!is_customer_logged_in()) {
        set_flash('warning', 'Please sign in or create an account to continue.');
        $target = $redirect_url ?: ($_SERVER['REQUEST_URI'] ?? BASE_URL . 'my-account.php');
        redirect(BASE_URL . 'login.php?redirect=' . urlencode($target));
    }
}

function customer_login_attempt(string $identifier, string $password): array {
    $db = get_db();
    $identifier = trim($identifier);
    $password = trim($password);

    if (empty($identifier) || empty($password)) {
        return ['success' => false, 'message' => 'Please provide both mobile number/email and your password.'];
    }

    $clean_phone = clean_indian_phone($identifier);
    $email = strtolower($identifier);

    $stmt = $db->prepare("
        SELECT * FROM customers 
        WHERE (phone = :p OR LOWER(email) = :e)
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([':p' => $clean_phone, ':e' => $email]);
    $customer = $stmt->fetch();

    if (!$customer) {
        usleep(random_int(100000, 200000));
        return ['success' => false, 'message' => 'No account found with this phone number or email address.'];
    }

    if ($customer['status'] === 'inactive') {
        return ['success' => false, 'message' => 'Your account is currently disabled. Please contact the arena manager.'];
    }

    if (empty($customer['password_hash'])) {
        return [
            'success' => false, 
            'message' => 'We found your previous bookings! Please click "Create Account" with this mobile number to set a password and access your dashboard.'
        ];
    }

    if (!password_verify($password, $customer['password_hash'])) {
        return ['success' => false, 'message' => 'Incorrect password. Please verify your password and retry.'];
    }

    // Update last login timestamp
    $upd = $db->prepare("UPDATE customers SET last_login = NOW() WHERE id = :id");
    $upd->execute([':id' => $customer['id']]);

    // Set customer session
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['customer_user_id'] = $customer['id'];
    $_SESSION['customer_name']    = $customer['full_name'];
    $_SESSION['customer_phone']   = $customer['phone'];
    $_SESSION['customer_email']   = $customer['email'];

    return ['success' => true, 'customer' => $customer];
}

function customer_register(string $full_name, string $phone, string $email, string $password): array {
    $db = get_db();
    $full_name = trim($full_name);
    $phone = clean_indian_phone($phone);
    $email = strtolower(trim($email));
    $password = trim($password);

    if (empty($full_name) || mb_strlen($full_name) < 2) {
        return ['success' => false, 'message' => 'Please enter your full name (at least 2 characters).'];
    }

    if (empty($phone) || !preg_match('/^[6-9]\d{9}$/', $phone)) {
        return ['success' => false, 'message' => 'Please enter a valid 10-digit Indian mobile number.'];
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please enter a valid email address.'];
    }

    if (empty($password) || strlen($password) < 6) {
        return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
    }

    // Check if phone or email already registered
    $stmt = $db->prepare("SELECT * FROM customers WHERE phone = :p " . (!empty($email) ? "OR LOWER(email) = :e" : "") . " LIMIT 1");
    $params = [':p' => $phone];
    if (!empty($email)) $params[':e'] = $email;
    $stmt->execute($params);
    $existing = $stmt->fetch();

    $hash = password_hash($password, PASSWORD_BCRYPT);

    if ($existing) {
        // If account already has a password set, tell them to log in
        if (!empty($existing['password_hash'])) {
            return [
                'success' => false, 
                'message' => 'An account is already registered with this mobile number or email. Please sign in instead.'
            ];
        }

        // Link existing guest/counter bookings with new account password
        $upd = $db->prepare("
            UPDATE customers 
            SET full_name = :fn, 
                email = COALESCE(NULLIF(:em, ''), email), 
                password_hash = :hash, 
                status = 'active',
                last_login = NOW() 
            WHERE id = :id
        ");
        $upd->execute([
            ':fn'   => $full_name,
            ':em'   => $email,
            ':hash' => $hash,
            ':id'   => $existing['id']
        ]);
        $customer_id = $existing['id'];
    } else {
        // Create brand new customer
        $ins = $db->prepare("
            INSERT INTO customers (full_name, phone, email, password_hash, status, last_login)
            VALUES (:fn, :ph, :em, :hash, 'active', NOW())
        ");
        $ins->execute([
            ':fn'   => $full_name,
            ':ph'   => $phone,
            ':em'   => !empty($email) ? $email : null,
            ':hash' => $hash
        ]);
        $customer_id = (int)$db->lastInsertId();
    }

    // Log the user in immediately
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['customer_user_id'] = $customer_id;
    $_SESSION['customer_name']    = $full_name;
    $_SESSION['customer_phone']   = $phone;
    $_SESSION['customer_email']   = $email;

    return ['success' => true, 'customer_id' => $customer_id];
}

function customer_logout(): void {
    unset(
        $_SESSION['customer_user_id'],
        $_SESSION['customer_name'],
        $_SESSION['customer_phone'],
        $_SESSION['customer_email']
    );
}


