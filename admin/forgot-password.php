<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Owner & Staff Password Recovery Request Portal
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$page_title = 'Reset Admin Password | ' . get_brand_short();
$msg_success = '';
$msg_error = '';
$generated_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $msg_error = 'Security CSRF check failed.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $res = admin_generate_password_reset($email);

        if ($res['success']) {
            $msg_success = $res['message'];
            if (!empty($res['raw_token'])) {
                // In local/staging environment, surface the direct reset link
                $generated_link = BASE_URL . 'admin/reset-password.php?token=' . urlencode($res['raw_token']);
            }
        } else {
            $msg_error = $res['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password | <?= e(get_brand_short()) ?> Sanctuary</title>
  
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
      
      <h3 class="h4 text-gold brand-font mb-1">PASSWORD RECOVERY</h3>
      <p class="text-muted-iron small mb-4">Enter your registered admin email address to receive a secure password reset link.</p>

      <?php if (!empty($msg_success)): ?>
        <div class="alert alert-success py-2 px-3 small text-start mb-4">
          <i class="bi bi-check-circle-fill me-1"></i> <?= e($msg_success) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($msg_error)): ?>
        <div class="alert alert-danger py-2 px-3 small text-start mb-4">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($msg_error) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($generated_link)): ?>
        <div class="alert alert-warning py-3 px-3 text-start small mb-4" style="background: rgba(214,168,75,0.12); border: 1.5px solid var(--gold); color: #fff;">
          <div class="fw-bold text-gold mb-1"><i class="bi bi-shield-check me-1"></i> Single-Use Reset Link Generated:</div>
          <div class="text-muted-iron mb-2" style="font-size: 0.72rem;">Click below to set your new password (valid for 1 hour):</div>
          <a href="<?= $generated_link ?>" class="btn btn-warning btn-sm fw-bold text-dark w-100 text-truncate">
            <i class="bi bi-arrow-right-circle-fill me-1"></i> Open Password Reset Form
          </a>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>admin/forgot-password.php" class="text-start">
        <?= csrf_field() ?>

        <div class="mb-4">
          <label for="adminEmail" class="form-label text-light small fw-bold">Registered Admin Email</label>
          <div class="input-group">
            <span class="input-group-text bg-dark border-secondary border-opacity-25 text-muted-iron">
              <i class="bi bi-envelope-fill"></i>
            </span>
            <input type="email" name="email" id="adminEmail" class="form-control bg-dark text-light border-secondary" placeholder="e.g. owner@ironcladgaming.in" required autofocus>
          </div>
        </div>

        <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-2 fs-6">
          <i class="bi bi-send-fill me-1"></i> Dispatch Reset Link
        </button>
      </form>

      <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
        <a href="<?= BASE_URL ?>admin/login.php" class="text-gold small text-decoration-none">
          <i class="bi bi-arrow-left me-1"></i> Back to Login
        </a>
        <a href="<?= BASE_URL ?>admin/recover.php" class="text-muted-iron small text-decoration-none">
          <i class="bi bi-tools me-1"></i> Developer Recovery
        </a>
      </div>
    </div>

  </div>

</body>
</html>

