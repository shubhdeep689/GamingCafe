<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Customer Reviews Moderation
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

// Handle Approve
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    if (verify_csrf($_GET['token'] ?? '')) {
        $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = :id")->execute([':id' => $id]);
        log_admin_activity($admin_user['id'], 'review_approved', "Approved review ID {$id}");
        set_flash('success', 'Review approved and published to website.');
        redirect(BASE_URL . 'admin/reviews.php');
    }
}

// Handle Toggle Feature
if (isset($_GET['toggle_feature'])) {
    $id = (int)$_GET['toggle_feature'];
    if (verify_csrf($_GET['token'] ?? '')) {
        $db->prepare("UPDATE reviews SET is_featured = NOT is_featured WHERE id = :id")->execute([':id' => $id]);
        log_admin_activity($admin_user['id'], 'review_toggled_feature', "Toggled featured flag on review ID {$id}");
        set_flash('success', 'Featured review status updated.');
        redirect(BASE_URL . 'admin/reviews.php');
    }
}

// Handle Delete Review
if (isset($_GET['delete_review'])) {
    $id = (int)$_GET['delete_review'];
    if (verify_csrf($_GET['token'] ?? '')) {
        $db->prepare("DELETE FROM reviews WHERE id = :id")->execute([':id' => $del_id = $id]);
        log_admin_activity($admin_user['id'], 'review_deleted', "Deleted review ID {$id}");
        set_flash('success', 'Review deleted.');
        redirect(BASE_URL . 'admin/reviews.php');
    }
}

// Filter
$status_filter = trim($_GET['status'] ?? '');
$sql = "SELECT * FROM reviews WHERE 1=1";
$params = [];

if ($status_filter === 'pending') {
    $sql .= " AND is_approved = 0";
} elseif ($status_filter === 'approved') {
    $sql .= " AND is_approved = 1";
}
$sql .= " ORDER BY (is_approved = 0) DESC, id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

$admin_title = 'Player Reviews Moderation';
require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Filter Nav -->
<div class="iron-card p-3 mb-4">
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>admin/reviews.php" class="btn btn-iron btn-iron-sm <?= empty($status_filter) ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
      All Reviews
    </a>
    <a href="<?= BASE_URL ?>admin/reviews.php?status=pending" class="btn btn-iron btn-iron-sm <?= $status_filter === 'pending' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
      <i class="bi bi-clock-history me-1 text-warning"></i> Pending Approval
    </a>
    <a href="<?= BASE_URL ?>admin/reviews.php?status=approved" class="btn btn-iron btn-iron-sm <?= $status_filter === 'approved' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
      <i class="bi bi-check-circle me-1 text-success"></i> Approved & Live
    </a>
  </div>
</div>

<div class="admin-table-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Rating</th>
          <th>Game</th>
          <th>Review Content</th>
          <th>Submitted On</th>
          <th>Status</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($reviews)): ?>
          <?php foreach ($reviews as $r): ?>
            <tr>
              <td>
                <strong class="text-light"><?= e($r['customer_name']) ?></strong>
              </td>
              <td>
                <div class="rating-stars small">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi <?= $i <= $r['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                  <?php endfor; ?>
                </div>
              </td>
              <td>
                <span class="text-cyan small"><?= e($r['game_played'] ?: 'General') ?></span>
              </td>
              <td>
                <p class="text-muted-iron small mb-0" style="max-width: 380px;">"<?= e($r['review_text']) ?>"</p>
              </td>
              <td>
                <span class="text-muted-iron small"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
              </td>
              <td>
                <?php if ($r['is_approved']): ?>
                  <span class="badge bg-success">Approved</span>
                  <?php if ($r['is_featured']): ?>
                    <span class="badge bg-gold text-dark d-block mt-1">Homepage Featured</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge bg-warning text-dark">Pending</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if (!$r['is_approved']): ?>
                  <a href="<?= BASE_URL ?>admin/reviews.php?approve=<?= $r['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-success" title="Approve Review">
                    <i class="bi bi-check-lg"></i> Approve
                  </a>
                <?php else: ?>
                  <a href="<?= BASE_URL ?>admin/reviews.php?toggle_feature=<?= $r['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-warning" title="Toggle Homepage Feature">
                    <i class="bi <?= $r['is_featured'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                  </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>admin/reviews.php?delete_review=<?= $r['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this review?');">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted-iron small">
              No reviews found matching filter.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

