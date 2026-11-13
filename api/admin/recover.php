<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Developer Emergency Account Recovery Engine (Layer 3)
 * Authorized technical support tool to restore Owner account access.
 * Protected by DEVELOPER_RECOVERY_KEY in .env.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

// CLI Support
if (php_sapi_name() === 'cli') {
    echo "========================================================\n";
    echo "  IRONCLAD SANCTUARY — DEVELOPER EMERGENCY RECOVERY     \n";
    echo "========================================================\n\n";

    echo "Enter Developer Master Recovery Key: ";
    $key = trim(fgets(STDIN));
    echo "Enter New Password for Owner Account: ";
    $pass = trim(fgets(STDIN));

    $res = admin_developer_emergency_recovery($key, $pass);
    if ($res['success']) {
        echo "\n[SUCCESS] Owner account ({$res['owner']['username']}) has been successfully restored!\n";
        echo "You can now log in at /admin/login.php with your new password.\n";
    } else {
        echo "\n[ERROR] " . $res['message'] . "\n";
    }
    exit;
}

// Web Interface
$page_title = 'Developer Emergency Recovery | ' . get_brand_short();
$msg_success = '';
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $msg_error = 'Security CSRF check failed.';
    } else {
        $master_key = trim($_POST['master_key'] ?? '');
        $new_pass   = trim($_POST['new_password'] ?? '');
        $confirm    = trim($_POST['confirm_password'] ?? '');

        if ($new_pass !== $confirm) {
            $msg_error = 'Passwords do not match.';
        } else {
            $res = admin_developer_emergency_recovery($master_key, $new_pass);
            if ($res['success']) {
                $msg_success = "Owner account (@{$res['owner']['username']}) has been successfully restored with full privileges! You can now log into the command center.";
            } else {
                $msg_error = $res['message'];
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
  <title>Developer Recovery | <?= e(get_brand_short()) ?> Sanctuary</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css?v=<?= APP_VERSION ?>">
  <link rel="icon" type="image/png" href="<?= get_brand_logo() ?>">
</head>
<body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #090B10; padding: 2rem 1rem;">

  <div class="container position-relative z-2" style="max-width: 500px;">
    
    <div class="iron-card p-4 p-md-5 text-center" style="border: 1px solid rgba(231,76,60,0.4); background: #151922; box-shadow: 0 0 35px rgba(231,76,60,0.2);">
      <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: rgba(231,76,60,0.15); border: 2px solid #e74c3c; color: #e74c3c;">
        <i class="bi bi-shield-lock-fill fs-3"></i>
      </div>
      
      <h3 class="h4 text-danger brand-font mb-1">DEVELOPER EMERGENCY RECOVERY</h3>
      <p class="text-muted-iron small mb-4">
        Authorized technical support gateway to restore business owner credentials in dispute or lockout situations.
      </p>

      <?php if (!empty($msg_success)): ?>
        <div class="alert alert-success py-3 px-3 small text-start mb-4">
          <div class="fw-bold text-success mb-1"><i class="bi bi-check-circle-fill me-1"></i> Account Restored:</div>
          <div><?= e($msg_success) ?></div>
          <div class="mt-3">
            <a href="<?= BASE_URL ?>admin/login.php" class="btn btn-success btn-sm fw-bold w-100">
              <i class="bi bi-box-arrow-in-right me-1"></i> Proceed to Admin Login
            </a>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($msg_error)): ?>
        <div class="alert alert-danger py-2 px-3 small text-start mb-4">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($msg_error) ?>
        </div>
      <?php endif; ?>

      <?php if (empty($msg_success)): ?>
        <form method="POST" action="<?= BASE_URL ?>admin/recover.php" class="text-start">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label for="masterKey" class="form-label text-light small fw-bold">Developer Master Recovery Key</label>
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-danger">
                <i class="bi bi-key-fill"></i>
              </span>
              <input type="password" name="master_key" id="masterKey" class="form-control bg-dark text-light border-secondary font-monospace" placeholder="Key configured in .env" required autofocus>
            </div>
            <div class="form-text text-muted-iron" style="font-size: 0.72rem;">Found in your server's <code>.env</code> file (DEVELOPER_RECOVERY_KEY).</div>
          </div>

          <div class="mb-3">
            <label for="newPass" class="form-label text-light small fw-bold">New Password for Owner</label>
            <input type="password" name="new_password" id="newPass" class="form-control bg-dark text-light border-secondary" placeholder="Choose a new secure password" required>
          </div>

          <div class="mb-4">
            <label for="confirmPass" class="form-label text-light small fw-bold">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirmPass" class="form-control bg-dark text-light border-secondary" placeholder="Re-type new password" required>
          </div>

          <button type="submit" class="btn btn-danger w-100 py-2 fs-6 fw-bold shadow">
            <i class="bi bi-unlock-fill me-1"></i> Execute Emergency Account Reset
          </button>
        </form>
      <?php endif; ?>

      <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
        <a href="<?= BASE_URL ?>admin/login.php" class="text-muted-iron small text-decoration-none">
          <i class="bi bi-arrow-left me-1"></i> Back to Standard Login
        </a>
      </div>
    </div>

  </div>

</body>
</html>

