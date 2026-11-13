<?php
require_once __DIR__ . '/../config/config.php';

$db = get_db();
$cols = $db->query("DESCRIBE customers")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('password_hash', $cols)) {
    $db->exec("ALTER TABLE customers ADD COLUMN password_hash VARCHAR(255) NULL AFTER email");
    echo "Added password_hash column.\n";
} else {
    echo "password_hash column already exists.\n";
}

if (!in_array('status', $cols)) {
    $db->exec("ALTER TABLE customers ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER total_spent");
    echo "Added status column.\n";
} else {
    echo "status column already exists.\n";
}

if (!in_array('last_login', $cols)) {
    $db->exec("ALTER TABLE customers ADD COLUMN last_login DATETIME NULL AFTER status");
    echo "Added last_login column.\n";
} else {
    echo "last_login column already exists.\n";
}

echo "Database customer schema updated successfully!\n";
