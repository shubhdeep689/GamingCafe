<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Set New Admin Password via Verified Security Token
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$raw_token = trim($_GET['token'] ?? ($_POST['token'] ?? ''));
$db = get_db();
$msg_error = '';
$msg_success = '';

// Check if token exists and is valid
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
    $msg_error = 'This password reset link is invalid, expired, or has already been used.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $admin) {
    if (!verify_csrf()) {
        $msg_error = 'Security CSRF check failed.';
    } else {
        $new_pass = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm_password'] ?? '');

        if (strlen($new_pass) < 6) {
            $msg_error = 'Password must be at least 6 characters.';
        } elseif ($new_pass !== $confirm) {
            $msg_error = 'Passwords do not match.';
        } else {
            $res = admin_reset_password_with_token($raw_token, $new_pass);
            if ($res['success']) {
                set_flash('success', 'Your password has been successfully updated! You can now log into the command center.');
                redirect(BASE_URL . 'admin/login.php');
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
  <title>Set New Password | <?= e(get_brand_short()) ?> Sanctuary</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600;700&family=Segoe+UI:wght@400;600;700&display=swap" rel="stylesheet">
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="<?= ASSETS_URL ?>css/style.css?v=<?= APP_VERSION ?>">
  <link rel="icon" type="image/png" href="<?= get_brand_logo() ?>">
</head>
<body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #090B10; padding: 2rem 1rem;">

  <div class="container position-relative z-2" style="max-width: 460px;">
    
    <div class="iron-card p-4 p-md-5 text-center" style="border: 1px solid rgba(214,168,75,0.3); background: #151922;">
      <a href="<?= BASE_URL ?>">
        <img src="<?= get_brand_logo() ?>" alt="Logo" class="img-fluid mb-3" style="max-height: 75px; filter: drop-shadow(0 0 15px rgba(214,168,75,0.4));">
      </a>
      
      <h3 class="h4 text-gold brand-font mb-1">CREATE NEW PASSWORD</h3>
      <?php if ($admin): ?>
        <p class="text-muted-iron small mb-4">Resetting credentials for account: <strong class="text-light"><?= e($admin['username']) ?></strong> (<?= e($admin['email']) ?>)</p>
      <?php endif; ?>

      <?php if (!empty($msg_error)): ?>
        <div class="alert alert-danger py-2 px-3 small text-start mb-4">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($msg_error) ?>
        </div>
      <?php endif; ?>

      <?php if ($admin): ?>
        <form method="POST" action="<?= BASE_URL ?>admin/reset-password.php" class="text-start">
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($raw_token) ?>">

          <div class="mb-3">
            <label for="newPass" class="form-label text-light small fw-bold">New Secure Password</label>
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted-iron">
                <i class="bi bi-lock-fill"></i>
              </span>
              <input type="password" name="password" id="newPass" class="form-control bg-dark text-light border-secondary" placeholder="At least 6 characters" required autofocus>
            </div>
          </div>

          <div class="mb-4">
            <label for="confirmPass" class="form-label text-light small fw-bold">Confirm Password</label>
            <div class="input-group">
              <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted-iron">
                <i class="bi bi-shield-check"></i>
              </span>
              <input type="password" name="confirm_password" id="confirmPass" class="form-control bg-dark text-light border-secondary" placeholder="Re-type password" required>
            </div>
          </div>

          <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-2 fs-6">
            <i class="bi bi-check2-circle me-1"></i> Update &amp; Secure Account
          </button>
        </form>
      <?php else: ?>
        <div class="mt-3">
          <a href="<?= BASE_URL ?>admin/forgot-password.php" class="btn btn-warning btn-sm fw-bold text-dark">
            <i class="bi bi-arrow-repeat me-1"></i> Request a New Link
          </a>
        </div>
      <?php endif; ?>

      <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-center">
        <a href="<?= BASE_URL ?>admin/login.php" class="text-muted-iron small text-decoration-none">
          <i class="bi bi-arrow-left me-1"></i> Return to Login
        </a>
      </div>
    </div>

  </div>

</body>
</html>

