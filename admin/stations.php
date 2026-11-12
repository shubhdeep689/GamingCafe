<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Gaming Stations & Rigs Management
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

// Handle Add Station
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_station'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $cat_id = (int)($_POST['category_id'] ?? 1);
        $rate = (float)($_POST['hourly_rate'] ?? 100);
        $specs = trim($_POST['specs'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'maintenance', 'inactive']) ? $_POST['status'] : 'active';
        $order = (int)($_POST['display_order'] ?? 0);

        if (empty($name) || empty($code)) {
            set_flash('danger', 'Name and Code are required.');
        } else {
            try {
                $stmt = $db->prepare("
                    INSERT INTO stations (name, code, category_id, hourly_rate, specs, status, display_order)
                    VALUES (:n, :c, :cat, :r, :s, :st, :ord)
                ");
                $stmt->execute([
                    ':n'   => $name,
                    ':c'   => $code,
                    ':cat' => $cat_id,
                    ':r'   => $rate,
                    ':s'   => $specs ?: null,
                    ':st'  => $status,
                    ':ord' => $order
                ]);
                log_admin_activity($admin_user['id'], 'station_added', "Added station {$name} ({$code}) at ₹{$rate}/hr");
                set_flash('success', "Station {$name} added successfully.");
                redirect(BASE_URL . 'admin/stations.php');
            } catch (Exception $e) {
                set_flash('danger', 'Error adding station: ' . $e->getMessage());
            }
        }
    }
}

// Handle Update Station / Price
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit_station'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $id = (int)$_POST['station_id'];
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $cat_id = (int)($_POST['category_id'] ?? 1);
        $rate = (float)($_POST['hourly_rate'] ?? 100);
        $specs = trim($_POST['specs'] ?? '');
        $status = in_array($_POST['status'] ?? '', ['active', 'maintenance', 'inactive']) ? $_POST['status'] : 'active';
        $order = (int)($_POST['display_order'] ?? 0);

        try {
            $stmt = $db->prepare("
                UPDATE stations 
                SET name = :n, code = :c, category_id = :cat, hourly_rate = :r, specs = :s, status = :st, display_order = :ord
                WHERE id = :id
            ");
            $stmt->execute([
                ':n'   => $name,
                ':c'   => $code,
                ':cat' => $cat_id,
                ':r'   => $rate,
                ':s'   => $specs ?: null,
                ':st'  => $status,
                ':ord' => $order,
                ':id'  => $id
            ]);
            log_admin_activity($admin_user['id'], 'station_updated', "Updated station ID {$id} hourly rate to ₹{$rate}");
            set_flash('success', "Station {$name} rate updated to " . format_currency($rate) . "/hr.");
            redirect(BASE_URL . 'admin/stations.php');
        } catch (Exception $e) {
            set_flash('danger', 'Error updating station: ' . $e->getMessage());
        }
    }
}

// Fetch Stations
$stations_stmt = $db->query("
    SELECT s.*, c.name AS category_name, c.icon AS category_icon
    FROM stations s
    JOIN station_categories c ON s.category_id = c.id
    ORDER BY s.display_order ASC, s.id ASC
");
$stations = $stations_stmt->fetchAll();

// Fetch Categories for modal
$categories = $db->query("SELECT * FROM station_categories ORDER BY display_order ASC")->fetchAll();

$admin_title = 'Gaming Stations';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Battle Stations & Equipment</h3>
    <span class="text-muted-iron small">Manage arena PC rigs, console lounges, squad desks, and hourly rates.</span>
  </div>
  <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" data-bs-toggle="modal" data-bs-target="#addStationModal">
    <i class="bi bi-plus-circle me-1"></i> Add New Station
  </button>
</div>

<div class="admin-table-card p-4 mb-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Code / Name</th>
          <th>Category</th>
          <th>Specs / Configuration</th>
          <th>Hourly Rate</th>
          <th>Status</th>
          <th>Order</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($stations as $st): ?>
          <tr>
            <td>
              <span class="font-monospace text-gold fw-bold d-block"><?= e($st['code']) ?></span>
              <strong class="text-light"><?= e($st['name']) ?></strong>
            </td>
            <td>
              <span class="text-cyan small"><i class="bi <?= e($st['category_icon']) ?> me-1"></i> <?= e($st['category_name']) ?></span>
            </td>
            <td>
              <span class="text-muted-iron small d-block" style="max-width: 320px;"><?= e($st['specs']) ?></span>
            </td>
            <td>
              <strong class="text-gold fs-6"><?= format_currency($st['hourly_rate']) ?></strong>/hr
            </td>
            <td>
              <span class="station-status-pill <?= $st['status'] === 'active' ? 'status-active' : 'status-maintenance' ?>" style="font-size: 0.7rem;">
                <?= ucfirst($st['status']) ?>
              </span>
            </td>
            <td><?= $st['display_order'] ?></td>
            <td class="text-end">
              <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editStationModal<?= $st['id'] ?>">
                <i class="bi bi-pencil me-1"></i> Edit Rate & Details
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- =================================================================
     EDIT MODALS (Placed outside table container for valid HTML DOM)
     ================================================================= -->
<?php foreach ($stations as $st): ?>
  <div class="modal fade" id="editStationModal<?= $st['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content text-start" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
        <form method="POST" action="<?= BASE_URL ?>admin/stations.php">
          <?= csrf_field() ?>
          <input type="hidden" name="action_edit_station" value="1">
          <input type="hidden" name="station_id" value="<?= $st['id'] ?>">

          <div class="modal-header border-secondary border-opacity-25">
            <h5 class="modal-title text-gold brand-font">Edit Station: <?= e($st['name']) ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-8">
                <label class="form-label">Station Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($st['name']) ?>" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control text-uppercase font-monospace" value="<?= e($st['code']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select" required>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $st['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Hourly Rate (₹) <span class="text-danger">*</span></label>
                <input type="number" step="1" name="hourly_rate" class="form-control fw-bold text-gold" value="<?= $st['hourly_rate'] ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                  <option value="active" <?= $st['status'] === 'active' ? 'selected' : '' ?>>Active / Operational</option>
                  <option value="maintenance" <?= $st['status'] === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                  <option value="inactive" <?= $st['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" class="form-control" value="<?= $st['display_order'] ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Hardware Specs / Notes</label>
                <textarea name="specs" class="form-control" rows="3"><?= e($st['specs']) ?></textarea>
              </div>
            </div>
          </div>

          <div class="modal-footer border-secondary border-opacity-25">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">
              <i class="bi bi-save me-1"></i> Update Station & Rate
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<!-- =================================================================
     ADD STATION MODAL
     ================================================================= -->
<div class="modal fade" id="addStationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
      <form method="POST" action="<?= BASE_URL ?>admin/stations.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action_add_station" value="1">

        <div class="modal-header border-secondary border-opacity-25">
          <h5 class="modal-title text-gold brand-font">Add New Battle Station</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Station Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Station Echo (PC Rig 05)" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control text-uppercase font-monospace" placeholder="ST-PC-05" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Hourly Rate (₹) <span class="text-danger">*</span></label>
              <input type="number" step="1" name="hourly_rate" class="form-control" value="100.00" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="active" selected>Active / Operational</option>
                <option value="maintenance">Maintenance</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" name="display_order" class="form-control" value="10">
            </div>
            <div class="col-12">
              <label class="form-label">Hardware Specs / Notes</label>
              <textarea name="specs" class="form-control" rows="3" placeholder="Display refresh rate, GPU, peripherals, chair..."></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary border-opacity-25">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">Create Station</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
