<?php
/**
 * Migration: Role-Based Admin RBAC & Account Recovery Columns
 */
require_once __DIR__ . '/../config/config.php';

$db = get_db();
$cols = $db->query("DESCRIBE admins")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('reset_token', $cols)) {
    $db->exec("ALTER TABLE admins ADD COLUMN reset_token VARCHAR(100) NULL AFTER lockout_until");
    echo "Added reset_token column.\n";
} else {
    echo "reset_token already exists.\n";
}

if (!in_array('reset_expires_at', $cols)) {
    $db->exec("ALTER TABLE admins ADD COLUMN reset_expires_at DATETIME NULL AFTER reset_token");
    echo "Added reset_expires_at column.\n";
} else {
    echo "reset_expires_at already exists.\n";
}

if (!in_array('is_active', $cols)) {
    $db->exec("ALTER TABLE admins ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
    echo "Added is_active column.\n";
} else {
    echo "is_active already exists.\n";
}

// Ensure the default admin is superadmin/owner
$db->exec("UPDATE admins SET role = 'superadmin', is_active = 1 WHERE username = 'admin'");
echo "Admin role verified.\n";

// Check if a staff account exists, if not create a sample staff account
$stmt = $db->query("SELECT COUNT(*) FROM admins WHERE role = 'staff'");
if ($stmt->fetchColumn() == 0) {
    $staff_pass = password_hash('reception123', PASSWORD_BCRYPT);
    $ins = $db->prepare("
        INSERT INTO admins (username, password_hash, email, full_name, role, is_active)
        VALUES ('receptionist', :pass, 'reception@ironcladgaming.in', 'Arena Front Desk Staff', 'staff', 1)
    ");
    $ins->execute([':pass' => $staff_pass]);
    echo "Sample receptionist account created (username: receptionist, password: reception123).\n";
}

echo "Admin RBAC migration finished successfully!\n";

