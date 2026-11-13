<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Pricing Plans & Multi-Hour Battle Passes Management
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin_owner();

$admin_user = current_admin();
$db = get_db();

// Handle Add Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_plan'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $title = trim($_POST['title'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $hours = (int)($_POST['duration_hours'] ?? 1);
        $price = (float)($_POST['price'] ?? 100);
        $orig_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
        $popular = isset($_POST['is_popular']) ? 1 : 0;
        $order = (int)($_POST['display_order'] ?? 0);
        
        $features_raw = explode("\n", trim($_POST['features_text'] ?? ''));
        $features = array_values(array_filter(array_map('trim', $features_raw)));

        try {
            $stmt = $db->prepare("
                INSERT INTO pricing_plans (title, badge, duration_hours, price, original_price, features_json, is_popular, is_active, display_order)
                VALUES (:t, :b, :h, :p, :op, :f, :pop, 1, :ord)
            ");
            $stmt->execute([
                ':t'   => $title,
                ':b'   => $badge ?: null,
                ':h'   => $hours,
                ':p'   => $price,
                ':op'  => $orig_price,
                ':f'   => json_encode($features),
                ':pop' => $popular,
                ':ord' => $order
            ]);
            log_admin_activity($admin_user['id'], 'plan_added', "Added pricing pass: {$title} (₹{$price})");
            set_flash('success', "Pricing pass '{$title}' created successfully.");
            redirect(BASE_URL . 'admin/pricing.php');
        } catch (Exception $e) {
            set_flash('danger', 'Error adding pass: ' . $e->getMessage());
        }
    }
}

// Handle Update Plan / Price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit_plan'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $id = (int)$_POST['plan_id'];
        $title = trim($_POST['title'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $hours = (int)($_POST['duration_hours'] ?? 1);
        $price = (float)($_POST['price'] ?? 100);
        $orig_price = !empty($_POST['original_price']) ? (float)$_POST['original_price'] : null;
        $popular = isset($_POST['is_popular']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;
        $order = (int)($_POST['display_order'] ?? 0);

        $features_raw = explode("\n", trim($_POST['features_text'] ?? ''));
        $features = array_values(array_filter(array_map('trim', $features_raw)));

        try {
            $stmt = $db->prepare("
                UPDATE pricing_plans 
                SET title = :t, badge = :b, duration_hours = :h, price = :p, original_price = :op, 
                    features_json = :f, is_popular = :pop, is_active = :act, display_order = :ord
                WHERE id = :id
            ");
            $stmt->execute([
                ':t'   => $title,
                ':b'   => $badge ?: null,
                ':h'   => $hours,
                ':p'   => $price,
                ':op'  => $orig_price,
                ':f'   => json_encode($features),
                ':pop' => $popular,
                ':act' => $active,
                ':ord' => $order,
                ':id'  => $id
            ]);
            log_admin_activity($admin_user['id'], 'plan_updated', "Updated pricing pass ID {$id} to ₹{$price}");
            set_flash('success', "Pricing pass '{$title}' updated successfully to " . format_currency($price) . ".");
            redirect(BASE_URL . 'admin/pricing.php');
        } catch (Exception $e) {
            set_flash('danger', 'Error updating pass: ' . $e->getMessage());
        }
    }
}

// Handle Delete Plan
if (isset($_GET['delete_plan'])) {
    $del_id = (int)$_GET['delete_plan'];
    if (verify_csrf($_GET['token'] ?? '')) {
        $db->prepare("DELETE FROM pricing_plans WHERE id = :id")->execute([':id' => $del_id]);
        log_admin_activity($admin_user['id'], 'plan_deleted', "Deleted pricing pass ID {$del_id}");
        set_flash('success', 'Pricing pass deleted.');
        redirect(BASE_URL . 'admin/pricing.php');
    }
}

// Fetch all plans
$plans = $db->query("SELECT * FROM pricing_plans ORDER BY display_order ASC, id ASC")->fetchAll();

$admin_title = 'Pricing & Passes';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Battle Passes & Pricing Packages</h3>
    <span class="text-muted-iron small">Configure hourly rates, multi-hour passes, and bundle discounts displayed on the site.</span>
  </div>
  <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" data-bs-toggle="modal" data-bs-target="#addPlanModal">
    <i class="bi bi-plus-circle me-1"></i> Create New Battle Pass
  </button>
</div>

<div class="admin-table-card p-4 mb-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Pass Title</th>
          <th>Badge Ribbon</th>
          <th>Duration</th>
          <th>Price</th>
          <th>Regular Price</th>
          <th>Popular</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($plans as $p): ?>
          <tr>
            <td>
              <strong class="text-gold"><?= e($p['title']) ?></strong>
            </td>
            <td>
              <?php if (!empty($p['badge'])): ?>
                <span class="badge bg-danger"><?= e($p['badge']) ?></span>
              <?php else: ?>
                <span class="text-secondary small">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="text-light"><?= $p['duration_hours'] ?> Hour(s)</span>
            </td>
            <td>
              <strong class="text-gold fs-6"><?= format_currency($p['price']) ?></strong>
            </td>
            <td>
              <?php if (!empty($p['original_price'])): ?>
                <del class="text-secondary"><?= format_currency($p['original_price']) ?></del>
              <?php else: ?>
                <span class="text-secondary small">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?= $p['is_popular'] ? '<span class="badge bg-gold text-dark">Featured</span>' : '<span class="text-secondary small">Standard</span>' ?>
            </td>
            <td>
              <?= $p['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
            </td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editPlanModal<?= $p['id'] ?>">
                <i class="bi bi-pencil me-1"></i> Edit Price & Details
              </button>
              <a href="<?= BASE_URL ?>admin/pricing.php?delete_plan=<?= $p['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this pricing pass?');">
                <i class="bi bi-trash"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- =================================================================
     EDIT MODALS (Placed outside the table container for valid DOM)
     ================================================================= -->
<?php foreach ($plans as $p): ?>
  <div class="modal fade" id="editPlanModal<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-start" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
        <form method="POST" action="<?= BASE_URL ?>admin/pricing.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action_edit_plan" value="1">
          <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">

          <div class="modal-header border-secondary border-opacity-25">
            <h5 class="modal-title text-gold brand-font">Edit Pass: <?= e($p['title']) ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Pass Title <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($p['title']) ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Badge Ribbon</label>
                <input type="text" name="badge" class="form-control" value="<?= e($p['badge'] ?? '') ?>" placeholder="e.g. Popular">
              </div>
              <div class="col-md-4">
                <label class="form-label">Duration (Hours) <span class="text-danger">*</span></label>
                <input type="number" name="duration_hours" class="form-control" value="<?= $p['duration_hours'] ?>" min="1" max="12" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Offer Price (₹) <span class="text-danger">*</span></label>
                <input type="number" step="1" name="price" class="form-control fw-bold text-gold" value="<?= $p['price'] ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Regular Price (₹)</label>
                <input type="number" step="1" name="original_price" class="form-control" value="<?= $p['original_price'] ?? '' ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Features Included (One line per feature)</label>
                <?php 
                  $f_list = json_decode($p['features_json'] ?? '[]', true) ?: [];
                  $f_text = implode("\n", $f_list);
                ?>
                <textarea name="features_text" class="form-control" rows="4"><?= e($f_text) ?></textarea>
              </div>
              <div class="col-md-4">
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="is_popular" value="1" id="popCheck<?= $p['id'] ?>" <?= $p['is_popular'] ? 'checked' : '' ?>>
                  <label class="form-check-label text-muted-iron small" for="popCheck<?= $p['id'] ?>">Highlight Popular</label>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" name="is_active" value="1" id="actCheck<?= $p['id'] ?>" <?= $p['is_active'] ? 'checked' : '' ?>>
                  <label class="form-check-label text-muted-iron small" for="actCheck<?= $p['id'] ?>">Active / Visible</label>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" class="form-control form-control-sm" value="<?= $p['display_order'] ?>">
              </div>
            </div>
          </div>

          <div class="modal-footer border-secondary border-opacity-25">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">
              <i class="bi bi-save me-1"></i> Save Updated Price
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<!-- =================================================================
     ADD PLAN MODAL
     ================================================================= -->
<div class="modal fade" id="addPlanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
      <form method="POST" action="<?= BASE_URL ?>admin/pricing.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action_add_plan" value="1">

        <div class="modal-header border-secondary border-opacity-25">
          <h5 class="modal-title text-gold brand-font">Create New Battle Pass</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Pass Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Squad Weekend Pass" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Badge Ribbon</label>
              <input type="text" name="badge" class="form-control" placeholder="e.g. Best Value">
            </div>
            <div class="col-md-4">
              <label class="form-label">Duration (Hours) <span class="text-danger">*</span></label>
              <input type="number" name="duration_hours" class="form-control" value="3" min="1" max="12" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Offer Price (₹) <span class="text-danger">*</span></label>
              <input type="number" step="1" name="price" class="form-control" value="270.00" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Regular Price (₹)</label>
              <input type="number" step="1" name="original_price" class="form-control" placeholder="300.00">
            </div>
            <div class="col-12">
              <label class="form-label">Features Included (One line per bullet)</label>
              <textarea name="features_text" class="form-control" rows="4" placeholder="3 Hours uninterrupted gaming&#10;Full game library access&#10;Priority seating"></textarea>
            </div>
            <div class="col-md-6">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_popular" value="1" id="addPopCheck">
                <label class="form-check-label text-muted-iron small" for="addPopCheck">Highlight as Most Popular</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" name="display_order" class="form-control form-control-sm" value="5">
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary border-opacity-25">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">Create Pass</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
