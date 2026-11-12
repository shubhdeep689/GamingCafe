<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Admin Profile & Password Change
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!verify_csrf()) {
        $errors[] = 'Security token invalid.';
    } else {
        $curr_pass = (string)($_POST['current_password'] ?? '');
        $new_pass  = (string)($_POST['new_password'] ?? '');
        $conf_pass = (string)($_POST['confirm_password'] ?? '');
        $email     = trim($_POST['email'] ?? '');

        // Fetch current admin password hash
        $stmt = $db->prepare("SELECT password_hash FROM admins WHERE id = :id");
        $stmt->execute([':id' => $admin_user['id']]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($curr_pass, $hash)) {
            $errors[] = 'Current password does not match our records.';
        } elseif (strlen($new_pass) < 8) {
            $errors[] = 'New password must be at least 8 characters long.';
        } elseif ($new_pass !== $conf_pass) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE admins SET password_hash = :h, email = :e WHERE id = :id");
            $upd->execute([':h' => $new_hash, ':e' => $email, ':id' => $admin_user['id']]);

            log_admin_activity($admin_user['id'], 'password_changed', 'Admin successfully changed their login credentials');
            set_flash('success', 'Password and security settings updated successfully.');
            redirect(BASE_URL . 'admin/profile.php');
        }
    }
}

$admin_title = 'Admin Profile & Security';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="iron-card p-4 p-md-5">
      <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div>
          <h3 class="h4 text-gold brand-font mb-1">Admin Profile & Password</h3>
          <span class="text-muted-iron small">Manage your login credentials and security tokens.</span>
        </div>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
          <ul class="mb-0 small ps-3">
            <?php foreach ($errors as $err): ?>
              <li><?= e($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>admin/profile.php">
        <?= csrf_field() ?>
        <input type="hidden" name="change_password" value="1">

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" value="<?= e($admin_user['username']) ?>" disabled>
        </div>

        <div class="mb-3">
          <label class="form-label">Admin Email <span class="text-danger">*</span></label>
          <input type="email" name="email" class="form-control" value="<?= e($admin_user['email']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Current Password <span class="text-danger">*</span></label>
          <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
        </div>

        <div class="mb-3">
          <label class="form-label">New Password (Min 8 characters) <span class="text-danger">*</span></label>
          <input type="password" name="new_password" class="form-control" required placeholder="Enter new strong password">
        </div>

        <div class="mb-4">
          <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
          <input type="password" name="confirm_password" class="form-control" required placeholder="Re-type new password">
        </div>

        <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3">
          <i class="bi bi-shield-lock-fill me-1"></i> Update Security Credentials
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

