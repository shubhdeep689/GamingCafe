<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Team & Staff Management (Owner / Superadmin Only)
 * Prevents staff lockouts and manages cafe employee access credentials.
 */

$admin_title = 'Team & Staff Management';
require_once __DIR__ . '/includes/admin-header.php';
require_admin_owner();

$db = get_db();
$current_admin = current_admin();

$msg_success = '';
$msg_error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $msg_error = 'Security CSRF check failed.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Create New Staff Member
        if ($action === 'create_user') {
            $username  = strtolower(trim($_POST['username'] ?? ''));
            $full_name = trim($_POST['full_name'] ?? '');
            $email     = strtolower(trim($_POST['email'] ?? ''));
            $role      = in_array($_POST['role'] ?? '', ['manager', 'staff']) ? $_POST['role'] : 'staff';
            $password  = trim($_POST['password'] ?? '');

            if (empty($username) || strlen($username) < 3) {
                $msg_error = 'Username must be at least 3 characters.';
            } elseif (empty($full_name)) {
                $msg_error = 'Please enter staff member full name.';
            } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg_error = 'Please enter a valid email address.';
            } elseif (empty($password) || strlen($password) < 6) {
                $msg_error = 'Password must be at least 6 characters.';
            } else {
                // Check if username or email exists
                $chk = $db->prepare("SELECT id FROM admins WHERE username = :u OR LOWER(email) = :e LIMIT 1");
                $chk->execute([':u' => $username, ':e' => $email]);
                if ($chk->fetch()) {
                    $msg_error = 'A staff account with this username or email already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $db->prepare("
                        INSERT INTO admins (username, password_hash, email, full_name, role, is_active)
                        VALUES (:u, :h, :e, :fn, :r, 1)
                    ");
                    $ins->execute([
                        ':u'  => $username,
                        ':h'  => $hash,
                        ':e'  => $email,
                        ':fn' => $full_name,
                        ':r'  => $role
                    ]);
                    log_admin_activity($current_admin['id'], 'create_staff', "Created new {$role} account: {$username} ({$email})");
                    $msg_success = "Staff account '{$username}' created successfully!";
                }
            }
        }

        // 2. Reset Staff Password
        elseif ($action === 'reset_staff_password') {
            $user_id  = (int)($_POST['target_user_id'] ?? 0);
            $new_pass = trim($_POST['new_password'] ?? '');

            if ($user_id <= 0 || strlen($new_pass) < 6) {
                $msg_error = 'Password must be at least 6 characters.';
            } else {
                // Verify target is not a superadmin if current is not superadmin
                $stmt = $db->prepare("SELECT * FROM admins WHERE id = :id");
                $stmt->execute([':id' => $user_id]);
                $target = $stmt->fetch();

                if (!$target) {
                    $msg_error = 'Target user not found.';
                } elseif ($target['role'] === 'superadmin' && $current_admin['role'] !== 'superadmin') {
                    $msg_error = 'You do not have permission to reset an Owner account password.';
                } else {
                    $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                    $upd = $db->prepare("
                        UPDATE admins 
                        SET password_hash = :h, failed_attempts = 0, lockout_until = NULL 
                        WHERE id = :id
                    ");
                    $upd->execute([':h' => $hash, ':id' => $user_id]);
                    log_admin_activity($current_admin['id'], 'reset_staff_pass', "Reset password for user {$target['username']} (ID {$user_id})");
                    $msg_success = "Password for '{$target['username']}' updated successfully!";
                }
            }
        }

        // 3. Toggle Active / Deactivated
        elseif ($action === 'toggle_active') {
            $user_id = (int)($_POST['target_user_id'] ?? 0);

            if ($user_id === (int)$current_admin['id']) {
                $msg_error = 'You cannot deactivate your own account!';
            } else {
                $stmt = $db->prepare("SELECT * FROM admins WHERE id = :id");
                $stmt->execute([':id' => $user_id]);
                $target = $stmt->fetch();

                if (!$target) {
                    $msg_error = 'User not found.';
                } elseif ($target['role'] === 'superadmin') {
                    $msg_error = 'Owner/Superadmin account cannot be deactivated.';
                } else {
                    $new_status = ($target['is_active'] == 1) ? 0 : 1;
                    $upd = $db->prepare("UPDATE admins SET is_active = :s WHERE id = :id");
                    $upd->execute([':s' => $new_status, ':id' => $user_id]);
                    $st_text = $new_status ? 'activated' : 'deactivated';
                    log_admin_activity($current_admin['id'], 'toggle_staff_status', "Account {$target['username']} {$st_text}");
                    $msg_success = "Staff account '{$target['username']}' has been {$st_text}!";
                }
            }
        }
    }
}

// Fetch all staff accounts
$users = $db->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();
?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Team &amp; Staff Access Management</h3>
    <span class="text-muted-iron small">Assign separate accounts for receptionists and shift managers. Prevents lockouts and protects business settings.</span>
  </div>

  <button type="button" class="btn btn-warning btn-sm fw-bold text-dark px-3 shadow" data-bs-toggle="modal" data-bs-target="#createStaffModal">
    <i class="bi bi-person-plus-fill me-1"></i> Add New Staff Member
  </button>
</div>

<!-- Flash Messages -->
<?php if (!empty($msg_success)): ?>
  <div class="alert alert-success py-2 px-3 small d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><?= e($msg_success) ?></div>
  </div>
<?php endif; ?>

<?php if (!empty($msg_error)): ?>
  <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div><?= e($msg_error) ?></div>
  </div>
<?php endif; ?>

<!-- Role Security Info Card -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="admin-table-card p-3 border-start border-4 border-warning h-100">
      <h6 class="text-gold fw-bold mb-1"><i class="bi bi-shield-lock-fill me-1"></i> Owner (Superadmin)</h6>
      <p class="text-muted-iron small mb-0">Full control. Can manage staff, pricing, settings, payment keys, and execute backups. Cannot be locked out by employees.</p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-table-card p-3 border-start border-4 border-info h-100">
      <h6 class="text-cyan fw-bold mb-1"><i class="bi bi-person-badge me-1"></i> Receptionist (Staff)</h6>
      <p class="text-muted-iron small mb-0">Operational access only. Can create bookings, verify walk-ins, and inspect availability. Cannot touch settings, pricing, or owner accounts.</p>
    </div>
  </div>
  <div class="col-md-4">
    <div class="admin-table-card p-3 border-start border-4 border-success h-100">
      <h6 class="text-green fw-bold mb-1"><i class="bi bi-tools me-1"></i> Developer Support</h6>
      <p class="text-muted-iron small mb-0">Emergency technical recovery tool protected by secret server key in <code>.env</code> file. Available if owner ever loses password.</p>
    </div>
  </div>
</div>

<!-- Staff Accounts Table -->
<div class="admin-table-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle mb-0">
      <thead>
        <tr>
          <th>Employee / User</th>
          <th>Role</th>
          <th>Contact Email</th>
          <th>Account Status</th>
          <th>Last Login</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-dark border border-secondary text-gold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                  <i class="bi <?= $u['role'] === 'superadmin' ? 'bi-shield-fill-check text-gold' : 'bi-person-fill text-muted-iron' ?>"></i>
                </div>
                <div>
                  <strong class="text-light d-block"><?= e($u['full_name']) ?></strong>
                  <span class="text-muted-iron small font-monospace">@<?= e($u['username']) ?></span>
                </div>
              </div>
            </td>
            <td>
              <?php if ($u['role'] === 'superadmin'): ?>
                <span class="badge bg-gold text-dark fw-bold text-uppercase">Owner / Superadmin</span>
              <?php elseif ($u['role'] === 'manager'): ?>
                <span class="badge bg-cyan text-dark fw-bold text-uppercase">Shift Manager</span>
              <?php else: ?>
                <span class="badge bg-secondary text-light text-uppercase">Front Desk Staff</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="text-muted-iron small"><?= e($u['email']) ?></span>
            </td>
            <td>
              <?php if ($u['is_active'] == 1): ?>
                <span class="badge bg-success text-white"><i class="bi bi-check-circle me-1"></i> Active</span>
              <?php else: ?>
                <span class="badge bg-danger text-white"><i class="bi bi-slash-circle me-1"></i> Deactivated</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="text-muted-iron small"><?= !empty($u['last_login']) ? date('d M Y, h:i A', strtotime($u['last_login'])) : 'Never logged in' ?></span>
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <!-- Reset Password Trigger -->
                <button type="button" class="btn btn-outline-warning" title="Reset User Password" onclick="openResetPasswordModal(<?= $u['id'] ?>, '<?= e(addslashes($u['username'])) ?>')">
                  <i class="bi bi-key-fill me-1"></i> Password
                </button>

                <!-- Deactivate / Activate Button (Forbidden for Superadmin) -->
                <?php if ($u['role'] !== 'superadmin' && $u['id'] !== $current_admin['id']): ?>
                  <form method="POST" action="<?= BASE_URL ?>admin/users.php" class="d-inline" onsubmit="return confirm('Are you sure you want to change account status for <?= e($u['username']) ?>?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="target_user_id" value="<?= $u['id'] ?>">
                    <?php if ($u['is_active'] == 1): ?>
                      <button type="submit" class="btn btn-outline-danger" title="Deactivate Employee Account">
                        <i class="bi bi-lock-fill"></i>
                      </button>
                    <?php else: ?>
                      <button type="submit" class="btn btn-outline-success" title="Reactivate Employee Account">
                        <i class="bi bi-unlock-fill"></i>
                      </button>
                    <?php endif; ?>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add New Staff Member -->
<div class="modal fade" id="createStaffModal" tabindex="-1" aria-labelledby="createStaffLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #11151E; border: 1px solid var(--border-color); color: #fff;">
      <div class="modal-header border-secondary border-opacity-25">
        <h5 class="modal-title text-gold brand-font" id="createStaffLabel"><i class="bi bi-person-plus-fill me-2"></i>Add Staff Member</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>admin/users.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_user">
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label text-light small fw-bold">Full Name <span class="text-danger">*</span></label>
            <input type="text" name="full_name" class="form-control bg-dark text-light border-secondary" placeholder="e.g. Gurpreet Singh (Reception)" required>
          </div>
          <div class="mb-3">
            <label class="form-label text-light small fw-bold">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control bg-dark text-light border-secondary" placeholder="e.g. receptionist1" required>
            <div class="form-text text-muted-iron" style="font-size: 0.72rem;">Used by the employee to log into the counter portal.</div>
          </div>
          <div class="mb-3">
            <label class="form-label text-light small fw-bold">Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control bg-dark text-light border-secondary" placeholder="reception@ironcladgaming.in" required>
          </div>
          <div class="mb-3">
            <label class="form-label text-light small fw-bold">Role &amp; Permissions <span class="text-danger">*</span></label>
            <select name="role" class="form-select bg-dark text-light border-secondary">
              <option value="staff" selected>Front Desk Staff (Bookings &amp; Counter Ops Only)</option>
              <option value="manager">Shift Manager (Bookings &amp; Offers Management)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label text-light small fw-bold">Initial Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control bg-dark text-light border-secondary" placeholder="At least 6 characters" required>
          </div>
        </div>
        <div class="modal-footer border-secondary border-opacity-25 py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark">Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal: Reset Staff Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #11151E; border: 1px solid var(--border-color); color: #fff;">
      <div class="modal-header border-secondary border-opacity-25">
        <h5 class="modal-title text-gold brand-font" id="resetModalLabel"><i class="bi bi-key-fill me-2"></i>Reset Employee Password</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="<?= BASE_URL ?>admin/users.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reset_staff_password">
        <input type="hidden" name="target_user_id" id="modalTargetUserId">
        <div class="modal-body p-4">
          <p class="text-muted-iron small mb-3">
            Resetting password for employee: <strong class="text-gold" id="modalTargetUsername"></strong>
          </p>
          <div class="mb-3">
            <label class="form-label text-light small fw-bold">New Secure Password <span class="text-danger">*</span></label>
            <input type="password" name="new_password" class="form-control bg-dark text-light border-secondary" placeholder="At least 6 characters" required autofocus>
          </div>
        </div>
        <div class="modal-footer border-secondary border-opacity-25 py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning btn-sm fw-bold text-dark">Save New Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openResetPasswordModal(userId, username) {
  document.getElementById('modalTargetUserId').value = userId;
  document.getElementById('modalTargetUsername').innerText = username;
  const modal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
  modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

