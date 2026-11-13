<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Admin Activity & Audit Trail
 */

$admin_title = 'Activity Logs';
require_once __DIR__ . '/includes/admin-header.php';

$db = get_db();

$logs_stmt = $db->query("
    SELECT l.*, a.username, a.full_name
    FROM admin_activity_logs l
    LEFT JOIN admins a ON l.admin_id = a.id
    ORDER BY l.id DESC
    LIMIT 100
");
$logs = $logs_stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Audit Trail & System Activity</h3>
    <span class="text-muted-iron small">History of administrative events, logins, and status updates.</span>
  </div>
</div>

<div class="admin-table-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Admin User</th>
          <th>Action</th>
          <th>Details</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($logs)): ?>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td>
                <span class="text-muted-iron small d-block"><?= date('d M Y, g:i A', strtotime($l['created_at'])) ?></span>
                <span class="text-secondary small" style="font-size: 0.72rem;"><?= time_ago($l['created_at']) ?></span>
              </td>
              <td>
                <strong class="text-light"><?= e($l['username'] ?: 'System / Guest') ?></strong>
                <?php if (!empty($l['full_name'])): ?>
                  <span class="text-muted-iron small d-block"><?= e($l['full_name']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge bg-secondary text-gold font-monospace"><?= e($l['action']) ?></span>
              </td>
              <td>
                <span class="text-light small"><?= e($l['details'] ?: '—') ?></span>
              </td>
              <td>
                <span class="text-secondary small font-monospace"><?= e($l['ip_address'] ?: '127.0.0.1') ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" class="text-center py-5 text-muted-iron small">
              No activity logs recorded yet.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

