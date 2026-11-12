<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Customers Directory & Player CRM
 */

$admin_title = 'Customers CRM';
require_once __DIR__ . '/includes/admin-header.php';

$db = get_db();
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT c.*, COUNT(b.id) AS actual_bookings, COALESCE(SUM(b.final_amount), 0) AS actual_spent
    FROM customers c
    LEFT JOIN bookings b ON b.customer_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.full_name LIKE :s OR c.phone LIKE :s2 OR c.email LIKE :s3)";
    $params[':s'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

$sql .= " GROUP BY c.id ORDER BY actual_spent DESC, c.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();
?>

<!-- Header & Search -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Customer Directory</h3>
    <span class="text-muted-iron small">Registered players, booking frequency, and lifetime spend in Sri Muktsar Sahib.</span>
  </div>

  <form method="GET" action="<?= BASE_URL ?>admin/customers.php" class="d-flex gap-2">
    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Name, Phone, Email..." value="<?= e($search) ?>" style="min-width: 250px;">
    <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">
      <i class="bi bi-search"></i>
    </button>
  </form>
</div>

<div class="admin-table-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Customer Name</th>
          <th>Mobile Phone</th>
          <th>Email Address</th>
          <th>Total Bookings</th>
          <th>Lifetime Spend</th>
          <th>Joined</th>
          <th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($customers)): ?>
          <?php foreach ($customers as $c): ?>
            <tr>
              <td>
                <strong class="text-light fs-6"><?= e($c['full_name']) ?></strong>
              </td>
              <td>
                <a href="tel:<?= e($c['phone']) ?>" class="text-gold fw-bold text-decoration-none">
                  +91 <?= e($c['phone']) ?>
                </a>
              </td>
              <td>
                <span class="text-muted-iron small"><?= e($c['email'] ?: '—') ?></span>
              </td>
              <td>
                <span class="badge bg-secondary"><?= $c['actual_bookings'] ?> Session(s)</span>
              </td>
              <td>
                <strong class="text-green"><?= format_currency($c['actual_spent']) ?></strong>
              </td>
              <td>
                <span class="text-muted-iron small"><?= date('d M Y', strtotime($c['created_at'])) ?></span>
              </td>
              <td class="text-end">
                <a href="<?= BASE_URL ?>admin/bookings.php?search=<?= urlencode($c['phone']) ?>" class="btn btn-sm btn-outline-warning" title="View Customer Reservations">
                  <i class="bi bi-calendar2-week me-1"></i> Bookings
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted-iron small">
              No customers found in database.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

