<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * UPI QR Code & Payment Instructions Manager
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_upi_settings'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $upi_id = trim($_POST['upi_id'] ?? '');
        $merchant = trim($_POST['upi_merchant_name'] ?? '');
        $instructions = trim($_POST['payment_instructions'] ?? '');

        // Update Text Settings
        update_setting('upi_id', $upi_id, 'payment');
        update_setting('upi_merchant_name', $merchant, 'payment');
        update_setting('payment_instructions', $instructions, 'payment');

        // Handle QR Code Image Upload if provided
        if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
            $upload = handle_file_upload($_FILES['qr_image'], 'qr/', 5242880, ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
            if ($upload['success']) {
                update_setting('upi_qr_image', $upload['path'], 'payment');
                log_admin_activity($admin_user['id'], 'update_upi_qr', "Uploaded new UPI QR code: {$upload['path']}");
            } else {
                set_flash('danger', 'QR Image Upload Error: ' . $upload['error']);
            }
        }

        log_admin_activity($admin_user['id'], 'update_upi_config', 'Updated UPI payment settings and merchant info');
        set_flash('success', 'UPI QR Code and payment configurations updated successfully.');
        redirect(BASE_URL . 'admin/upi-qr.php');
    }
}

$current_qr = get_setting('upi_qr_image', 'images/sample_qr.svg');
$current_upi_id = get_setting('upi_id', 'ironclad@upi');
$current_merchant = get_setting('upi_merchant_name', 'IRONCLAD GAMING SANCTUARY');
$current_instructions = get_setting('payment_instructions', 'Scan the UPI QR code using any UPI app. Enter the exact total amount. Submit the 12-digit UTR reference number.');

$admin_title = 'UPI QR Management';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row g-4 justify-content-center">
  <div class="col-lg-8">
    <div class="iron-card p-4 p-md-5">
      <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
        <div>
          <h3 class="h4 text-gold brand-font mb-1">UPI QR Code & Instructions</h3>
          <span class="text-muted-iron small">Configure the authentic business UPI QR scanner and payment instructions shown to booking customers.</span>
        </div>
        <a href="<?= BASE_URL ?>check-booking.php" target="_blank" class="btn btn-iron btn-iron-outline btn-iron-sm">
          <i class="bi bi-eye me-1"></i> View Customer Preview
        </a>
      </div>

      <form method="POST" action="<?= BASE_URL ?>admin/upi-qr.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="update_upi_settings" value="1">

        <div class="row g-4 align-items-center mb-4">
          <!-- Current QR Preview Box -->
          <div class="col-md-5 text-center">
            <label class="form-label d-block text-center mb-2">Active UPI QR Scanner</label>
            <div class="p-3 rounded bg-dark border border-secondary border-opacity-50 d-inline-block shadow-sm">
              <img src="<?= BASE_URL . e($current_qr) ?>?v=<?= time() ?>" alt="Active UPI QR" class="img-fluid rounded" style="max-height: 240px; width: 100%; object-fit: contain;">
            </div>
            <span class="text-muted-iron small d-block mt-2">File: <?= e($current_qr) ?></span>
          </div>

          <!-- File Upload Controls -->
          <div class="col-md-7">
            <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.02); border: 1px dashed var(--gold);">
              <label for="qrFile" class="form-label text-gold fw-bold mb-1">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload / Replace Authentic Business QR
              </label>
              <input type="file" name="qr_image" id="qrFile" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
              <div class="form-text text-muted-iron small mt-2">
                Supported formats: PNG, JPG, WEBP, SVG (Max 5MB). Replace with your actual merchant/scanner image.
              </div>
            </div>

            <div class="mb-3">
              <label for="upiId" class="form-label">UPI ID / VPA <span class="text-danger">*</span></label>
              <input type="text" name="upi_id" id="upiId" class="form-control font-monospace" value="<?= e($current_upi_id) ?>" required placeholder="e.g. yourbusiness@okaxis">
            </div>

            <div class="mb-3">
              <label for="merchantName" class="form-label">Payee / Merchant Name <span class="text-danger">*</span></label>
              <input type="text" name="upi_merchant_name" id="merchantName" class="form-control" value="<?= e($current_merchant) ?>" required placeholder="IRONCLAD GAMING SANCTUARY">
            </div>
          </div>
        </div>

        <div class="mb-4">
          <label for="payInstructions" class="form-label">Customer Payment Instructions</label>
          <textarea name="payment_instructions" id="payInstructions" rows="3" class="form-control"><?= e($current_instructions) ?></textarea>
          <div class="form-text text-muted-iron small">Explains to players how to scan the code and copy their 12-digit UTR.</div>
        </div>

        <button type="submit" class="btn btn-iron btn-iron-primary w-100 py-3">
          <i class="bi bi-save me-1"></i> Update UPI QR & Payment Configurations
        </button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
