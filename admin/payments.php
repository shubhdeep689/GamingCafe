<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Manual UPI Payment Verification Queue
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

// Handle One-Click Verify
if (isset($_POST['action_verify_payment'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $payment_id = (int)$_POST['payment_id'];
        $booking_id = (int)$_POST['booking_id'];

        try {
            $db->beginTransaction();

            $upd_p = $db->prepare("
                UPDATE payments 
                SET status = 'verified', 
                    verified_by = :aid, 
                    verified_at = NOW() 
                WHERE id = :pid
            ");
            $upd_p->execute([':aid' => $admin_user['id'], ':pid' => $payment_id]);

            $upd_b = $db->prepare("
                UPDATE bookings 
                SET status = 'confirmed',
                    whatsapp_status = 'pending',
                    whatsapp_pending_reason = 'Payment Verified & Confirmed Pass'
                WHERE id = :bid
            ");
            $upd_b->execute([':bid' => $booking_id]);

            // Fetch booking for WhatsApp URL
            $b_stmt = $db->prepare("
                SELECT b.*, s.name AS station_name, cust.full_name AS customer_name, cust.phone AS customer_phone
                FROM bookings b
                JOIN stations s ON b.station_id = s.id
                JOIN customers cust ON b.customer_id = cust.id
                WHERE b.id = :bid
            ");
            $b_stmt->execute([':bid' => $booking_id]);
            $b_info = $b_stmt->fetch();

            $db->commit();
            log_admin_activity($admin_user['id'], 'verify_payment', "Verified payment ID #{$payment_id} for booking #{$booking_id}");

            if ($b_info) {
                $_SESSION['flash_wa_url'] = BASE_URL . "admin/whatsapp-send.php?id={$booking_id}&type=confirmed";
                $_SESSION['flash_wa_ref'] = $b_info['booking_ref'];
            }

            set_flash('success', "Payment successfully verified! Booking slot is now officially CONFIRMED.");
            redirect(BASE_URL . 'admin/payments.php');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            set_flash('danger', 'Error verifying payment: ' . $e->getMessage());
        }
    }
}

// Handle Reject Payment
if (isset($_POST['action_reject_payment'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $payment_id = (int)$_POST['payment_id'];
        $booking_id = (int)$_POST['booking_id'];
        $reason = trim($_POST['reject_reason'] ?? 'Invalid UPI UTR reference');

        try {
            $db->beginTransaction();

            $upd_p = $db->prepare("
                UPDATE payments 
                SET status = 'rejected', 
                    admin_notes = :notes,
                    verified_by = :aid, 
                    verified_at = NOW() 
                WHERE id = :pid
            ");
            $upd_p->execute([':notes' => $reason, ':aid' => $admin_user['id'], ':pid' => $payment_id]);

            $upd_b = $db->prepare("
                UPDATE bookings 
                SET status = 'rejected',
                    whatsapp_status = 'pending',
                    whatsapp_pending_reason = 'Payment Rejection Notice'
                WHERE id = :bid
            ");
            $upd_b->execute([':bid' => $booking_id]);

            // Fetch booking for WhatsApp URL
            $b_stmt = $db->prepare("
                SELECT b.*, s.name AS station_name, cust.full_name AS customer_name, cust.phone AS customer_phone
                FROM bookings b
                JOIN stations s ON b.station_id = s.id
                JOIN customers cust ON b.customer_id = cust.id
                WHERE b.id = :bid
            ");
            $b_stmt->execute([':bid' => $booking_id]);
            $b_info = $b_stmt->fetch();

            $db->commit();
            log_admin_activity($admin_user['id'], 'reject_payment', "Rejected payment ID #{$payment_id}: {$reason}");

            if ($b_info) {
                $_SESSION['flash_wa_url'] = BASE_URL . "admin/whatsapp-send.php?id={$booking_id}&type=payment_rejected&reason=" . urlencode($reason);
                $_SESSION['flash_wa_ref'] = $b_info['booking_ref'];
            }

            set_flash('warning', "Payment marked as rejected. Customer will be notified on status check.");
            redirect(BASE_URL . 'admin/payments.php');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            set_flash('danger', 'Error rejecting payment: ' . $e->getMessage());
        }
    }
}

// Filter
$status_filter = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT p.*, b.booking_ref, b.booking_date, b.start_time, b.end_time, b.final_amount,
           b.whatsapp_status, b.whatsapp_sent_at, b.whatsapp_last_type, b.whatsapp_send_count, b.whatsapp_pending_reason,
           s.name AS station_name, cust.full_name AS customer_name, cust.phone AS customer_phone
    FROM payments p
    JOIN bookings b ON p.booking_id = b.id
    JOIN stations s ON b.station_id = s.id
    JOIN customers cust ON b.customer_id = cust.id
    WHERE 1=1
";
$params = [];

if (!empty($status_filter)) {
    $sql .= " AND p.status = :st";
    $params[':st'] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (p.upi_transaction_id LIKE :s OR b.booking_ref LIKE :s2 OR cust.phone LIKE :s3 OR cust.full_name LIKE :s4)";
    $params[':s'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
    $params[':s4'] = "%{$search}%";
}

$sql .= " ORDER BY (p.status = 'pending') DESC, p.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$admin_title = 'Payment Verification';
require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if (!empty($_SESSION['flash_wa_url'])): 
  $wa_url = $_SESSION['flash_wa_url'];
  $wa_ref = $_SESSION['flash_wa_ref'] ?? '';
  unset($_SESSION['flash_wa_url'], $_SESSION['flash_wa_ref']);
?>
  <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 mb-4 shadow-sm" style="background: rgba(57, 255, 136, 0.12); border: 1.5px solid var(--rgb-green); color: #fff;">
    <div>
      <h6 class="alert-heading fw-bold mb-1 text-green">
        <i class="bi bi-whatsapp fs-5 me-2"></i>Send Payment Status Update to Customer via WhatsApp
      </h6>
      <span class="small text-light">Booking #<strong><?= e($wa_ref) ?></strong> payment status was updated. Click to dispatch official receipt & entry pass.</span>
    </div>
    <a href="<?= $wa_url ?>" target="_blank" rel="noopener noreferrer" class="btn btn-success fw-bold px-3 py-2">
      <i class="bi bi-whatsapp me-1"></i> Send on WhatsApp
    </a>
  </div>
<?php endif; ?>

<!-- Filter Nav -->
<div class="iron-card p-3 mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>admin/payments.php" class="btn btn-iron btn-iron-sm <?= empty($status_filter) ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        All Payments (<?= count($payments) ?>)
      </a>
      <a href="<?= BASE_URL ?>admin/payments.php?status=pending" class="btn btn-iron btn-iron-sm <?= $status_filter === 'pending' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-hourglass-split me-1 text-warning"></i> Pending Verification
      </a>
      <a href="<?= BASE_URL ?>admin/payments.php?status=verified" class="btn btn-iron btn-iron-sm <?= $status_filter === 'verified' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-shield-check me-1 text-success"></i> Verified
      </a>
      <a href="<?= BASE_URL ?>admin/payments.php?status=rejected" class="btn btn-iron btn-iron-sm <?= $status_filter === 'rejected' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-x-octagon me-1 text-danger"></i> Rejected
      </a>
    </div>

    <form method="GET" action="<?= BASE_URL ?>admin/payments.php" class="d-flex gap-2">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search UTR, Ref, Phone..." value="<?= e($search) ?>">
      <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">
        <i class="bi bi-search"></i>
      </button>
    </form>
  </div>
</div>

<div class="admin-table-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Submitted UTR / Proof</th>
          <th>Booking Ref</th>
          <th>Customer</th>
          <th>Session Slot</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Verification</th>
          <th class="text-end">Verification Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($payments)): ?>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td>
                <?php if (!empty($p['upi_transaction_id'])): ?>
                  <span class="font-monospace text-gold fw-bold d-block fs-6"><?= e($p['upi_transaction_id']) ?></span>
                <?php else: ?>
                  <span class="text-secondary small fst-italic">No UTR submitted yet</span>
                <?php endif; ?>

                <?php if (!empty($p['proof_file'])): ?>
                  <a href="<?= BASE_URL . e($p['proof_file']) ?>" target="_blank" class="badge bg-dark border border-secondary text-cyan text-decoration-none mt-1 d-inline-block">
                    <i class="bi bi-image me-1"></i> View Screenshot
                  </a>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $p['booking_id'] ?>" class="font-monospace text-light fw-bold text-decoration-none">
                  <?= e($p['booking_ref']) ?>
                </a>
              </td>
              <td>
                <strong class="text-light d-block"><?= e($p['customer_name']) ?></strong>
                <span class="text-muted-iron small">+91 <?= e($p['customer_phone']) ?></span>
              </td>
              <td>
                <span class="text-cyan small fw-bold d-block"><?= e($p['station_name']) ?></span>
                <span class="text-muted-iron small"><?= date('d M Y', strtotime($p['booking_date'])) ?> &bull; <?= date('g:i A', strtotime($p['start_time'])) ?></span>
              </td>
              <td>
                <strong class="text-gold"><?= format_currency($p['amount']) ?></strong>
              </td>
              <td>
                <span class="badge bg-secondary text-uppercase" style="font-size: 0.65rem;"><?= e($p['payment_method']) ?></span>
              </td>
              <td>
                <?= payment_status_badge($p['status']) ?>
              </td>
              <td class="text-end">
                <?php $wa_row = generate_whatsapp_url($p['status'] === 'rejected' ? 'payment_rejected' : 'confirmed', $p); ?>
                <div class="d-flex justify-content-end gap-1">
                  <?php if ($p['status'] === 'pending'): ?>
                    <!-- Verify Form -->
                    <form method="POST" action="<?= BASE_URL ?>admin/payments.php" onsubmit="return confirm('Confirm verifying this UPI transaction?');" class="d-inline">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action_verify_payment" value="1">
                      <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                      <input type="hidden" name="booking_id" value="<?= $p['booking_id'] ?>">
                      <button type="submit" class="btn btn-sm btn-success" title="Approve & Confirm Booking">
                        <i class="bi bi-check-lg me-1"></i> Verify
                      </button>
                    </form>

                    <!-- Reject Modal Trigger -->
                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $p['id'] ?>" title="Reject Payment">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $p['booking_id'] ?>" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-eye"></i> Details
                    </a>
                  <?php endif; ?>
                  
                  <?php if (($p['whatsapp_status'] ?? '') === 'sent'): ?>
                    <a href="<?= BASE_URL ?>admin/whatsapp-send.php?id=<?= $p['booking_id'] ?>&type=<?= $p['whatsapp_last_type'] ?: 'confirmed' ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success py-1 px-2" title="Whatsapped <?= $p['whatsapp_send_count'] ?>x (Last: <?= time_ago($p['whatsapp_sent_at']) ?>). Click to WhatsApp Again!">
                      <i class="bi bi-arrow-repeat me-1"></i> WA Again (<?= $p['whatsapp_send_count'] ?>x)
                    </a>
                  <?php else: ?>
                    <a href="<?= BASE_URL ?>admin/whatsapp-send.php?id=<?= $p['booking_id'] ?>&type=<?= $p['status'] === 'rejected' ? 'payment_rejected' : 'confirmed' ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success fw-bold py-1 px-2" title="Notification pending. Click to dispatch WhatsApp pass!">
                      <i class="bi bi-whatsapp me-1"></i> Send WA
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted-iron small">
              No payments found in the verification queue.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Reject Modals (Outside Table for Valid HTML & Reliable Form Submission) -->
<?php if (!empty($payments)): ?>
  <?php foreach ($payments as $p): ?>
    <?php if ($p['status'] === 'pending'): ?>
      <div class="modal fade" id="rejectModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content text-start" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
            <form method="POST" action="<?= BASE_URL ?>admin/payments.php">
              <?= csrf_field() ?>
              <input type="hidden" name="action_reject_payment" value="1">
              <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
              <input type="hidden" name="booking_id" value="<?= $p['booking_id'] ?>">

              <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title text-danger brand-font">Reject Payment for #<?= e($p['booking_ref']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">
                <label class="form-label">Rejection Reason (Visible to Customer)</label>
                <textarea name="reject_reason" class="form-control" rows="3" required placeholder="e.g. Transaction ID not found in bank statement / Amount does not match session total..."></textarea>
              </div>

              <div class="modal-footer border-secondary border-opacity-25">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger btn-sm">Confirm Rejection</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

