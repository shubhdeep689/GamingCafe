<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Offers & Promo Codes CRUD
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

// Handle Add Offer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_offer'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = in_array($_POST['discount_type'] ?? '', ['percentage', 'fixed']) ? $_POST['discount_type'] : 'percentage';
        $val = (float)($_POST['discount_value'] ?? 10);
        $min_hrs = (int)($_POST['min_booking_hours'] ?? 1);
        $from = trim($_POST['valid_from'] ?? date('Y-m-d'));
        $until = trim($_POST['valid_until'] ?? date('Y-12-31'));
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($code) || empty($title)) {
            set_flash('danger', 'Promo code and Title are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO offers (code, title, description, discount_type, discount_value, min_booking_hours, valid_from, valid_until, is_active)
                    VALUES (:c, :t, :d, :dt, :dv, :mh, :vf, :vu, :act)
                ");
                $stmt->execute([
                    ':c'   => $code,
                    ':t'   => $title,
                    ':d'   => $description ?: null,
                    ':dt'  => $type,
                    ':dv'  => $val,
                    ':mh'  => $min_hrs,
                    ':vf'  => $from,
                    ':vu'  => $until,
                    ':act' => $active
                ]);
                log_admin_activity($admin_user['id'], 'offer_added', "Created promo code: {$code}");
                set_flash('success', "Offer '{$code}' created.");
                redirect(BASE_URL . 'admin/offers.php');
            } catch (Exception $e) {
                set_flash('danger', 'Error adding offer: ' . $e->getMessage());
            }
        }
    }
}

// Handle Delete Offer
if (isset($_GET['delete_offer'])) {
    $del_id = (int)$_GET['delete_offer'];
    if (verify_csrf($_GET['token'] ?? '')) {
        $db->prepare("DELETE FROM offers WHERE id = :id")->execute([':id' => $del_id]);
        log_admin_activity($admin_user['id'], 'offer_deleted', "Deleted offer ID {$del_id}");
        set_flash('success', 'Promo offer removed.');
        redirect(BASE_URL . 'admin/offers.php');
    }
}

// Fetch Offers
$offers = $db->query("SELECT * FROM offers ORDER BY id DESC")->fetchAll();

$admin_title = 'Promotional Offers';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Promotional Deals & Coupons</h3>
    <span class="text-muted-iron small">Manage discount codes used by players during booking checkouts.</span>
  </div>
  <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" data-bs-toggle="modal" data-bs-target="#addOfferModal">
    <i class="bi bi-plus-circle me-1"></i> Add New Promo Code
  </button>
</div>

<div class="admin-table-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Promo Code</th>
          <th>Offer Title</th>
          <th>Discount Value</th>
          <th>Min Duration</th>
          <th>Validity Window</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($offers)): ?>
          <?php foreach ($offers as $o): ?>
            <tr>
              <td>
                <span class="font-monospace text-gold fw-bold fs-6"><?= e($o['code']) ?></span>
              </td>
              <td>
                <strong class="text-light d-block"><?= e($o['title']) ?></strong>
                <span class="text-muted-iron small"><?= e($o['description']) ?></span>
              </td>
              <td>
                <strong class="text-green">
                  <?= $o['discount_type'] === 'percentage' ? ((float)$o['discount_value'] . '%') : ('₹' . number_format((float)$o['discount_value'], 2)) ?>
                </strong>
              </td>
              <td>
                <span class="text-light small"><?= $o['min_booking_hours'] ?> Hour(s)</span>
              </td>
              <td>
                <span class="text-muted-iron small"><?= date('d M Y', strtotime($o['valid_from'])) ?> – <?= date('d M Y', strtotime($o['valid_until'])) ?></span>
              </td>
              <td>
                <?= $o['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
              </td>
              <td class="text-end">
                <a href="<?= BASE_URL ?>admin/offers.php?delete_offer=<?= $o['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this promo code?');">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted-iron small">
              No promotional offers created yet.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Offer Modal -->
<div class="modal fade" id="addOfferModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
      <form method="POST" action="<?= BASE_URL ?>admin/offers.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action_add_offer" value="1">

        <div class="modal-header border-secondary border-opacity-25">
          <h5 class="modal-title text-gold brand-font">Create New Promo Offer</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Coupon Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="e.g. MUKTSAR10" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Discount Type</label>
              <select name="discount_type" class="form-select">
                <option value="percentage">Percentage (%) Discount</option>
                <option value="fixed">Fixed Rupee (₹) Amount</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Offer Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Esports Squad Welcome Deal" required>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Terms and details..."></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label">Discount Value <span class="text-danger">*</span></label>
              <input type="number" step="0.5" name="discount_value" class="form-control" value="10.00" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Min Booking Duration (Hours)</label>
              <input type="number" name="min_booking_hours" class="form-control" value="1" min="1">
            </div>
            <div class="col-md-6">
              <label class="form-label">Valid From</label>
              <input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Valid Until</label>
              <input type="date" name="valid_until" class="form-control" value="<?= date('Y-12-31') ?>" required>
            </div>
            <div class="col-12">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="offerActiveCheck" checked>
                <label class="form-check-label text-muted-iron small" for="offerActiveCheck">Activate immediately for public bookings</label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary border-opacity-25">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">Create Promo Code</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
