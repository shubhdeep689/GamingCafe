<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Bookings Management Module with Live WhatsApp Delivery Tracking & "WhatsApp Again" Engine
 */

$admin_title = 'Manage Bookings';
require_once __DIR__ . '/includes/admin-header.php';

$db = get_db();

// Filter & Search Parameters
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$station_filter = (int)($_GET['station_id'] ?? 0);
$date_filter = trim($_GET['date'] ?? '');
$wa_filter = trim($_GET['wa_status'] ?? '');

$sql = "
    SELECT b.*, s.name AS station_name, s.code AS station_code, 
           cust.full_name AS customer_name, cust.phone AS customer_phone,
           p.status AS payment_status, p.upi_transaction_id
    FROM bookings b
    JOIN stations s ON b.station_id = s.id
    JOIN customers cust ON b.customer_id = cust.id
    LEFT JOIN payments p ON p.booking_id = b.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (b.booking_ref LIKE :s OR cust.full_name LIKE :s2 OR cust.phone LIKE :s3 OR p.upi_transaction_id LIKE :s4)";
    $params[':s'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
    $params[':s4'] = "%{$search}%";
}

if (!empty($status_filter)) {
    $sql .= " AND b.status = :st";
    $params[':st'] = $status_filter;
}

if ($station_filter > 0) {
    $sql .= " AND b.station_id = :sid";
    $params[':sid'] = $station_filter;
}

if (!empty($date_filter)) {
    $sql .= " AND b.booking_date = :bdate";
    $params[':bdate'] = $date_filter;
}

if ($wa_filter === 'pending') {
    $sql .= " AND b.whatsapp_status = 'pending'";
} elseif ($wa_filter === 'sent') {
    $sql .= " AND b.whatsapp_status = 'sent'";
}

$sql .= " ORDER BY (b.whatsapp_status = 'pending') DESC, b.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Live WhatsApp Counts for Filter Tabs
$total_all_count = (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$pending_wa_count = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE whatsapp_status = 'pending'")->fetchColumn();
$sent_wa_count = (int)$db->query("SELECT COUNT(*) FROM bookings WHERE whatsapp_status = 'sent'")->fetchColumn();

// Stations for filter dropdown
$stations = $db->query("SELECT id, name FROM stations ORDER BY display_order ASC")->fetchAll();
?>

<!-- Filter & Search Bar -->
<div class="iron-card p-3 mb-4">
  <form method="GET" action="<?= BASE_URL ?>admin/bookings.php" class="row g-2 align-items-center">
    <div class="col-md-3">
      <input type="text" name="search" class="form-control form-control-sm" placeholder="Search Ref, Phone, Name, UTR..." value="<?= e($search) ?>">
    </div>

    <div class="col-md-2">
      <select name="status" class="form-select form-select-sm">
        <option value="">-- All Statuses --</option>
        <option value="pending_payment" <?= $status_filter === 'pending_payment' ? 'selected' : '' ?>>Pending Payment</option>
        <option value="confirmed" <?= $status_filter === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="in_session" <?= $status_filter === 'in_session' ? 'selected' : '' ?>>In Session</option>
        <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
        <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
      </select>
    </div>

    <div class="col-md-2">
      <select name="station_id" class="form-select form-select-sm">
        <option value="0">-- All Stations --</option>
        <?php foreach ($stations as $st): ?>
          <option value="<?= $st['id'] ?>" <?= $station_filter === (int)$st['id'] ? 'selected' : '' ?>>
            <?= e($st['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-2">
      <input type="date" name="date" class="form-control form-control-sm" value="<?= e($date_filter) ?>">
    </div>

    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm flex-grow-1">
        <i class="bi bi-filter me-1"></i> Filter
      </button>
      <a href="<?= BASE_URL ?>admin/bookings.php" class="btn btn-iron btn-iron-outline btn-iron-sm">
        Reset
      </a>
    </div>
  </form>
</div>

<!-- WhatsApp Notification Delivery Filter Tabs -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="<?= BASE_URL ?>admin/bookings.php" class="btn btn-sm <?= empty($wa_filter) ? 'btn-iron-primary' : 'btn-outline-secondary' ?>">
    All Bookings (<?= $total_all_count ?>)
  </a>
  <a href="<?= BASE_URL ?>admin/bookings.php?wa_status=pending" class="btn btn-sm <?= $wa_filter === 'pending' ? 'btn-warning fw-bold text-dark' : 'btn-outline-warning' ?>">
    <i class="bi bi-clock-history me-1"></i> Needs WhatsApp (<?= $pending_wa_count ?>)
  </a>
  <a href="<?= BASE_URL ?>admin/bookings.php?wa_status=sent" class="btn btn-sm <?= $wa_filter === 'sent' ? 'btn-success fw-bold' : 'btn-outline-success' ?>">
    <i class="bi bi-check2-all me-1"></i> Whatsapped (<?= $sent_wa_count ?>)
  </a>
</div>

<!-- Bookings Table -->
<div class="admin-table-card p-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
      <h4 class="h5 text-light mb-0 brand-font">All Customer Reservations</h4>
      <span class="text-muted-iron small">Showing <?= count($bookings) ?> reservation(s) &bull; <?= $pending_wa_count ?> need WhatsApp notification</span>
    </div>
    <a href="<?= BASE_URL ?>admin/booking-create.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
      <i class="bi bi-plus-circle me-1"></i> New Walk-In Booking
    </a>
  </div>

  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Reference</th>
          <th>Customer</th>
          <th>Station</th>
          <th>Date & Slot</th>
          <th>Payable</th>
          <th>Payment</th>
          <th>Status</th>
          <th style="min-width: 150px;">WhatsApp Delivery</th>
          <th class="text-end" style="min-width: 130px;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($bookings)): ?>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td>
                <span class="font-monospace text-gold fw-bold"><?= e($b['booking_ref']) ?></span>
                <span class="badge bg-secondary bg-opacity-25 small text-uppercase d-block mt-1" style="font-size: 0.65rem;"><?= e($b['booking_type']) ?></span>
              </td>
              <td>
                <strong class="text-light d-block"><?= e($b['customer_name']) ?></strong>
                <a href="tel:<?= e($b['customer_phone']) ?>" class="text-muted-iron small text-decoration-none">+91 <?= e($b['customer_phone']) ?></a>
              </td>
              <td>
                <span class="text-cyan small fw-bold d-block"><?= e($b['station_name']) ?></span>
                <span class="text-secondary small"><?= e($b['station_code']) ?></span>
              </td>
              <td>
                <span class="text-light small d-block"><?= date('d M Y', strtotime($b['booking_date'])) ?></span>
                <span class="text-gold small"><?= date('g:i A', strtotime($b['start_time'])) ?> – <?= date('g:i A', strtotime($b['end_time'])) ?></span>
                <span class="text-muted-iron small d-block" style="font-size: 0.68rem;"><?= $b['duration_hours'] ?> hr(s)</span>
              </td>
              <td>
                <strong class="text-light"><?= format_currency($b['final_amount']) ?></strong>
              </td>
              <td>
                <?= payment_status_badge($b['payment_status'] ?? 'pending') ?>
                <?php if (!empty($b['upi_transaction_id'])): ?>
                  <span class="font-monospace text-secondary small d-block" style="font-size: 0.7rem;"><?= e($b['upi_transaction_id']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?= booking_status_badge($b['status']) ?>
                <?php if (($b['reschedule_status'] ?? '') === 'pending'): ?>
                  <span class="badge bg-warning text-dark small d-block mt-1 fw-bold" style="font-size: 0.65rem;">
                    <i class="bi bi-clock-history"></i> Reschedule Req
                  </span>
                <?php endif; ?>
              </td>

              <!-- Live WhatsApp Delivery Status Column -->
              <td>
                <?php if (($b['whatsapp_status'] ?? 'pending') === 'pending'): ?>
                  <!-- UNSENT / PENDING -->
                  <span class="badge bg-warning text-dark d-inline-block py-1 px-2" style="font-size: 0.68rem;">
                    <i class="bi bi-clock-history me-1"></i> Needs WhatsApp
                  </span>
                  <span class="text-warning d-block mt-1" style="font-size: 0.65rem; line-height: 1.2;">
                    <?= e($b['whatsapp_pending_reason'] ?: 'Pass Pending') ?>
                  </span>
                  <a href="<?= BASE_URL ?>admin/whatsapp-send.php?id=<?= $b['id'] ?>&type=<?= $b['status'] === 'rejected' ? 'payment_rejected' : 'confirmed' ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-success fw-bold py-0 px-2 mt-1" style="font-size: 0.72rem;">
                    <i class="bi bi-whatsapp me-1"></i> Send Now
                  </a>

                <?php else: ?>
                  <!-- ALREADY SENT: WHATSAPPED (With WhatsApp Again Feature) -->
                  <span class="badge bg-success bg-opacity-25 text-green border border-success d-inline-block py-1 px-2" style="font-size: 0.68rem;">
                    <i class="bi bi-check2-all me-1"></i> Whatsapped (<?= $b['whatsapp_send_count'] ?>x)
                  </span>
                  <span class="text-muted-iron d-block mt-1" style="font-size: 0.65rem;">
                    <?= time_ago($b['whatsapp_sent_at']) ?>
                  </span>
                  <!-- WHATSAPP AGAIN BUTTON -->
                  <a href="<?= BASE_URL ?>admin/whatsapp-send.php?id=<?= $b['id'] ?>&type=<?= $b['whatsapp_last_type'] ?: 'confirmed' ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-success py-0 px-2 mt-1" style="font-size: 0.72rem;" title="Send WhatsApp Again">
                    <i class="bi bi-arrow-repeat me-1"></i> WA Again
                  </a>
                <?php endif; ?>
              </td>

              <td class="text-end">
                <div class="d-flex justify-content-end gap-1">
                  <?php if ($b['status'] === 'in_session' || ($b['status'] === 'confirmed' && $b['booking_date'] === date('Y-m-d') && $b['start_time'] <= date('H:i:s') && $b['end_time'] > date('H:i:s'))): ?>
                    <a href="<?= BASE_URL ?>admin/availability.php?free_rig=<?= $b['id'] ?>&token=<?= csrf_token() ?>" 
                       class="btn btn-sm btn-outline-danger" 
                       onclick="return confirm('Free station <?= e($b['station_name']) ?> now? Marks session completed and opens rig for waiting walk-ins.');" 
                       title="Player left early? Free Rig Now">
                      <i class="bi bi-box-arrow-right"></i>
                    </a>
                  <?php endif; ?>
                  <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($b['booking_ref']) ?>&download=pdf" target="_blank" class="btn btn-sm btn-outline-danger" title="Download Official PDF Pass (to attach on WhatsApp)">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                  </a>
                  <a href="<?= BASE_URL ?>receipt.php?ref=<?= urlencode($b['booking_ref']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Print Official 1-Page e-Receipt">
                    <i class="bi bi-printer"></i>
                  </a>
                  <a href="<?= BASE_URL ?>admin/booking-detail.php?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-warning" title="View Booking Details & Update Status">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-muted-iron small">
              No matching bookings found with the specified filter criteria.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
