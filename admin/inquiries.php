<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Customer Inquiries & Contact Messages Hub
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

// Handle Actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        set_flash('danger', 'Security token expired. Please retry.');
        redirect(BASE_URL . 'admin/inquiries.php');
    }

    $action = $_POST['action'];

    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $new_status = $_POST['status'] ?? 'read';
        if (in_array($new_status, ['unread', 'read', 'replied', 'archived']) && $id > 0) {
            $stmt = $db->prepare("UPDATE inquiries SET status = :st WHERE id = :id");
            $stmt->execute([':st' => $new_status, ':id' => $id]);
            log_admin_activity($admin_user['id'], 'inquiry_status_updated', "Updated inquiry #{$id} status to {$new_status}");
            set_flash('success', "Inquiry #{$id} marked as " . ucfirst($new_status) . ".");
        }
        redirect(BASE_URL . 'admin/inquiries.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }

    if ($action === 'save_notes') {
        $id = (int)($_POST['id'] ?? 0);
        $notes = trim($_POST['admin_notes'] ?? '');
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE inquiries SET admin_notes = :notes WHERE id = :id");
            $stmt->execute([':notes' => $notes ?: null, ':id' => $id]);
            log_admin_activity($admin_user['id'], 'inquiry_note_saved', "Saved admin note on inquiry #{$id}");
            set_flash('success', "Note saved for inquiry #{$id}.");
        }
        redirect(BASE_URL . 'admin/inquiries.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''));
    }

    if ($action === 'delete_inquiry') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $db->prepare("DELETE FROM inquiries WHERE id = :id");
            $stmt->execute([':id' => $id]);
            log_admin_activity($admin_user['id'], 'inquiry_deleted', "Deleted inquiry #{$id}");
            set_flash('success', "Inquiry #{$id} deleted successfully.");
        }
        redirect(BASE_URL . 'admin/inquiries.php');
    }

    if ($action === 'mark_all_read') {
        $db->exec("UPDATE inquiries SET status = 'read' WHERE status = 'unread'");
        log_admin_activity($admin_user['id'], 'inquiries_all_read', "Marked all unread inquiries as read");
        set_flash('success', 'All unread inquiries marked as read.');
        redirect(BASE_URL . 'admin/inquiries.php');
    }
}

// Fetch Metrics
$total_inquiries = (int)$db->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
$unread_inquiries = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'")->fetchColumn();
$replied_inquiries = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'replied'")->fetchColumn();
$phone_leads_count = (int)$db->query("SELECT COUNT(*) FROM inquiries WHERE phone IS NOT NULL AND phone != ''")->fetchColumn();

// Filter & Search
$status_filter = trim($_GET['status'] ?? '');
$search_query  = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM inquiries WHERE 1=1";
$params = [];

if (in_array($status_filter, ['unread', 'read', 'replied', 'archived'])) {
    $sql .= " AND status = :st";
    $params[':st'] = $status_filter;
}

if (!empty($search_query)) {
    $sql .= " AND (name LIKE :q OR contact_raw LIKE :q OR email LIKE :q OR phone LIKE :q OR subject LIKE :q OR message LIKE :q)";
    $params[':q'] = '%' . $search_query . '%';
}

$sql .= " ORDER BY (status = 'unread') DESC, id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$admin_title = 'Inquiries & Contact Messages';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="container-fluid py-4">

  <!-- Header with Live Actions -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h1 class="h3 brand-font text-gold mb-1">
        <i class="bi bi-chat-left-dots text-cyan me-2"></i>Inquiries &amp; Contact Messages
      </h1>
      <p class="text-muted-iron small mb-0">Review incoming customer messages, private tournament requests, and dispatch instant WhatsApp replies.</p>
    </div>
    <div class="d-flex gap-2">
      <?php if ($unread_inquiries > 0): ?>
        <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php" onsubmit="return confirm('Mark all unread inquiries as read?');">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="mark_all_read">
          <button type="submit" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-check2-all me-1"></i> Mark All as Read
          </button>
        </form>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>contact.php" target="_blank" class="btn btn-outline-info btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i> View Live Form
      </a>
    </div>
  </div>

  <?= display_flash() ?>

  <!-- KPI Metric Cards -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
      <div class="admin-stat-card p-3 rounded" style="background: rgba(18, 24, 35, 0.9); border: 1px solid var(--border-color);">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-muted-iron small d-block mb-1">Total Inquiries</span>
            <span class="fs-4 fw-bold text-light brand-font"><?= number_format($total_inquiries) ?></span>
          </div>
          <div class="p-2 rounded bg-dark border border-secondary text-cyan fs-4">
            <i class="bi bi-chat-square-text"></i>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
      <div class="admin-stat-card p-3 rounded position-relative overflow-hidden" style="background: rgba(18, 24, 35, 0.9); border: 1px solid <?= $unread_inquiries > 0 ? 'rgba(230, 57, 70, 0.5)' : 'var(--border-color)' ?>;">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-muted-iron small d-block mb-1">Awaiting Response</span>
            <span class="fs-4 fw-bold text-danger brand-font"><?= number_format($unread_inquiries) ?></span>
          </div>
          <div class="p-2 rounded bg-dark border border-danger text-danger fs-4">
            <i class="bi bi-envelope-exclamation"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="admin-stat-card p-3 rounded" style="background: rgba(18, 24, 35, 0.9); border: 1px solid var(--border-color);">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-muted-iron small d-block mb-1">Replied / Resolved</span>
            <span class="fs-4 fw-bold text-green brand-font"><?= number_format($replied_inquiries) ?></span>
          </div>
          <div class="p-2 rounded bg-dark border border-success text-success fs-4">
            <i class="bi bi-check2-circle"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-sm-6 col-xl-3">
      <div class="admin-stat-card p-3 rounded" style="background: rgba(18, 24, 35, 0.9); border: 1px solid var(--border-color);">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <span class="text-muted-iron small d-block mb-1">Phone Leads Captured</span>
            <span class="fs-4 fw-bold text-gold brand-font"><?= number_format($phone_leads_count) ?></span>
          </div>
          <div class="p-2 rounded bg-dark border border-warning text-gold fs-4">
            <i class="bi bi-whatsapp"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Filter Navigation & Search Bar -->
  <div class="iron-card p-3 mb-4">
    <div class="row g-3 align-items-center justify-content-between">
      
      <!-- Filter Tabs -->
      <div class="col-md-7">
        <ul class="nav nav-pills gap-1">
          <li class="nav-item">
            <a class="nav-link btn-sm <?= empty($status_filter) ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/inquiries.php<?= !empty($search_query) ? '?q=' . urlencode($search_query) : '' ?>">
              All (<?= $total_inquiries ?>)
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-sm <?= $status_filter === 'unread' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/inquiries.php?status=unread<?= !empty($search_query) ? '&q=' . urlencode($search_query) : '' ?>">
              <span class="badge bg-danger me-1"><?= $unread_inquiries ?></span> Unread
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-sm <?= $status_filter === 'replied' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/inquiries.php?status=replied<?= !empty($search_query) ? '&q=' . urlencode($search_query) : '' ?>">
              Replied
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-sm <?= $status_filter === 'read' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/inquiries.php?status=read<?= !empty($search_query) ? '&q=' . urlencode($search_query) : '' ?>">
              Read
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link btn-sm <?= $status_filter === 'archived' ? 'active' : '' ?>" href="<?= BASE_URL ?>admin/inquiries.php?status=archived<?= !empty($search_query) ? '&q=' . urlencode($search_query) : '' ?>">
              Archived
            </a>
          </li>
        </ul>
      </div>

      <!-- Search Input -->
      <div class="col-md-5">
        <form method="GET" action="<?= BASE_URL ?>admin/inquiries.php" class="d-flex gap-2">
          <?php if (!empty($status_filter)): ?>
            <input type="hidden" name="status" value="<?= e($status_filter) ?>">
          <?php endif; ?>
          <div class="input-group input-group-sm">
            <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-search"></i></span>
            <input type="text" name="q" class="form-control bg-dark text-light border-secondary" placeholder="Search name, phone, message..." value="<?= e($search_query) ?>">
            <?php if (!empty($search_query)): ?>
              <a href="<?= BASE_URL ?>admin/inquiries.php<?= !empty($status_filter) ? '?status=' . urlencode($status_filter) : '' ?>" class="btn btn-outline-secondary text-light">Clear</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-warning text-dark fw-bold">Search</button>
          </div>
        </form>
      </div>

    </div>
  </div>

  <!-- Inquiries Table -->
  <div class="iron-card p-0 overflow-hidden">
    <div class="table-responsive">
      <table class="table table-dark table-hover align-middle mb-0">
        <thead style="background: rgba(0,0,0,0.4); border-bottom: 2px solid var(--border-color);">
          <tr>
            <th class="ps-3" style="width: 50px;">ID</th>
            <th>Customer &amp; Contact</th>
            <th>Subject</th>
            <th>Message Preview</th>
            <th>Date &amp; Time</th>
            <th>Status</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($inquiries)): ?>
            <?php foreach ($inquiries as $inq): ?>
              <?php
                $is_unread = ($inq['status'] === 'unread');
                $has_phone = !empty($inq['phone']);
                $wa_phone = $has_phone ? '91' . $inq['phone'] : '';
                $clean_name = $inq['name'];
                $default_wa_reply = "Hi {$clean_name}! Thank you for contacting " . get_brand_short() . " regarding \"{$inq['subject']}\". We would be happy to help!";
                $wa_url = $has_phone ? "https://api.whatsapp.com/send?phone={$wa_phone}&text=" . urlencode($default_wa_reply) : '';
              ?>
              <tr class="<?= $is_unread ? 'table-active' : '' ?>" style="<?= $is_unread ? 'background: rgba(230, 57, 70, 0.08); font-weight: 600;' : '' ?>">
                
                <!-- ID -->
                <td class="ps-3 font-monospace text-muted-iron">
                  #<?= $inq['id'] ?>
                </td>

                <!-- Customer Details -->
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-dark fw-bold" style="width: 36px; height: 36px; background: <?= $is_unread ? 'var(--gold)' : '#334155' ?>; font-size: 0.82rem;">
                      <?= strtoupper(substr($inq['name'], 0, 2)) ?>
                    </div>
                    <div>
                      <div class="fw-bold text-light"><?= e($inq['name']) ?></div>
                      <div class="small text-muted-iron" style="font-size: 0.74rem;">
                        <?php if ($has_phone): ?>
                          <a href="tel:<?= e($inq['phone']) ?>" class="text-cyan text-decoration-none me-2">
                            <i class="bi bi-telephone-fill me-1"></i>+91 <?= e($inq['phone']) ?>
                          </a>
                        <?php endif; ?>
                        <?php if (!empty($inq['email'])): ?>
                          <a href="mailto:<?= e($inq['email']) ?>" class="text-muted-iron text-decoration-none">
                            <i class="bi bi-envelope-fill me-1"></i><?= e($inq['email']) ?>
                          </a>
                        <?php elseif (!$has_phone): ?>
                          <span class="text-light"><?= e($inq['contact_raw']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Subject -->
                <td>
                  <span class="badge bg-dark border border-secondary text-gold font-monospace" style="font-size: 0.72rem;">
                    <?= e($inq['subject']) ?>
                  </span>
                </td>

                <!-- Message Preview -->
                <td style="max-width: 280px;">
                  <div class="text-truncate text-light small" title="<?= e($inq['message']) ?>">
                    <?= e($inq['message']) ?>
                  </div>
                  <?php if (!empty($inq['admin_notes'])): ?>
                    <small class="text-warning d-block" style="font-size: 0.68rem;">
                      <i class="bi bi-pin-angle-fill me-1"></i>Note: <?= e($inq['admin_notes']) ?>
                    </small>
                  <?php endif; ?>
                </td>

                <!-- Date -->
                <td>
                  <div class="text-light small"><?= date('d M Y', strtotime($inq['created_at'])) ?></div>
                  <div class="text-muted-iron" style="font-size: 0.7rem;"><?= date('g:i A', strtotime($inq['created_at'])) ?></div>
                </td>

                <!-- Status Badge -->
                <td>
                  <?php if ($inq['status'] === 'unread'): ?>
                    <span class="badge bg-danger text-uppercase px-2 py-1"><i class="bi bi-bell-fill me-1"></i>Unread</span>
                  <?php elseif ($inq['status'] === 'replied'): ?>
                    <span class="badge bg-success text-uppercase px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Replied</span>
                  <?php elseif ($inq['status'] === 'read'): ?>
                    <span class="badge bg-secondary text-uppercase px-2 py-1">Read</span>
                  <?php else: ?>
                    <span class="badge bg-dark border border-secondary text-muted-iron text-uppercase px-2 py-1">Archived</span>
                  <?php endif; ?>
                </td>

                <!-- Action Buttons -->
                <td class="text-end pe-3">
                  <div class="btn-group btn-group-sm">
                    
                    <!-- View Modal Trigger -->
                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#inquiryModal<?= $inq['id'] ?>" title="View Message &amp; Notes">
                      <i class="bi bi-eye"></i>
                    </button>

                    <!-- WhatsApp Reply (If Phone exists) -->
                    <?php if ($has_phone): ?>
                      <a href="<?= $wa_url ?>" target="_blank" class="btn btn-outline-success" title="WhatsApp Reply to Customer" onclick="markRepliedAsync(<?= $inq['id'] ?>)">
                        <i class="bi bi-whatsapp"></i>
                      </a>
                    <?php endif; ?>

                    <!-- Quick Status Toggle -->
                    <div class="btn-group btn-group-sm" role="group">
                      <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                        <li><h6 class="dropdown-header">Update Status</h6></li>
                        <li>
                          <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                            <input type="hidden" name="status" value="read">
                            <button type="submit" class="dropdown-item"><i class="bi bi-eye me-2 text-info"></i>Mark as Read</button>
                          </form>
                        </li>
                        <li>
                          <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                            <input type="hidden" name="status" value="replied">
                            <button type="submit" class="dropdown-item"><i class="bi bi-check2-circle me-2 text-success"></i>Mark as Replied</button>
                          </form>
                        </li>
                        <li>
                          <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                            <input type="hidden" name="status" value="unread">
                            <button type="submit" class="dropdown-item"><i class="bi bi-bell me-2 text-danger"></i>Mark as Unread</button>
                          </form>
                        </li>
                        <li>
                          <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                            <input type="hidden" name="status" value="archived">
                            <button type="submit" class="dropdown-item"><i class="bi bi-archive me-2 text-muted"></i>Archive</button>
                          </form>
                        </li>
                        <li><hr class="dropdown-divider border-secondary border-opacity-25 my-1"></li>
                        <li>
                          <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php" onsubmit="return confirm('Permanently delete inquiry #<?= $inq['id'] ?>?');">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="delete_inquiry">
                            <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash3 me-2"></i>Delete</button>
                          </form>
                        </li>
                      </ul>
                    </div>

                  </div>
                </td>

              </tr>

              <!-- View & Notes Modal -->
              <div class="modal fade" id="inquiryModal<?= $inq['id'] ?>" tabindex="-1" aria-labelledby="inquiryModalLabel<?= $inq['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                  <div class="modal-content" style="background: #11151E; border: 1px solid var(--border-color); color: #fff;">
                    
                    <div class="modal-header border-secondary border-opacity-25 py-3">
                      <div>
                        <h5 class="modal-title brand-font text-gold mb-0" id="inquiryModalLabel<?= $inq['id'] ?>">
                          <i class="bi bi-envelope-paper-fill me-2 text-cyan"></i>Inquiry #<?= $inq['id'] ?>: <?= e($inq['subject']) ?>
                        </h5>
                        <small class="text-muted-iron">Received on <?= date('D, d M Y at g:i A', strtotime($inq['created_at'])) ?> &bull; IP: <?= e($inq['ip_address'] ?? 'Unknown') ?></small>
                      </div>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                      
                      <!-- Customer Information Row -->
                      <div class="row g-3 p-3 rounded mb-4" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-color);">
                        <div class="col-md-4">
                          <span class="text-muted-iron small d-block">Full Name</span>
                          <strong class="text-light"><?= e($inq['name']) ?></strong>
                        </div>
                        <div class="col-md-4">
                          <span class="text-muted-iron small d-block">Direct Mobile Phone</span>
                          <?php if ($has_phone): ?>
                            <a href="tel:<?= e($inq['phone']) ?>" class="text-cyan fw-bold text-decoration-none">
                              <i class="bi bi-telephone-fill me-1"></i>+91 <?= e($inq['phone']) ?>
                            </a>
                          <?php else: ?>
                            <span class="text-muted-iron">Not provided</span>
                          <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                          <span class="text-muted-iron small d-block">Email Address</span>
                          <?php if (!empty($inq['email'])): ?>
                            <a href="mailto:<?= e($inq['email']) ?>" class="text-gold fw-bold text-decoration-none">
                              <i class="bi bi-envelope-fill me-1"></i><?= e($inq['email']) ?>
                            </a>
                          <?php else: ?>
                            <span class="text-muted-iron">Not provided</span>
                          <?php endif; ?>
                        </div>
                        <div class="col-12 pt-2 border-top border-secondary border-opacity-25">
                          <span class="text-muted-iron small me-2">Raw Contact Provided:</span>
                          <span class="font-monospace text-light"><?= e($inq['contact_raw']) ?></span>
                        </div>
                      </div>

                      <!-- Message Body -->
                      <div class="mb-4">
                        <label class="form-label text-gold small fw-bold text-uppercase" style="letter-spacing: 0.08em;">Customer Inquiry Message:</label>
                        <div class="p-3 rounded text-light" style="background: #0B0E14; border: 1px solid var(--border-color); font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap;"><?= e($inq['message']) ?></div>
                      </div>

                      <!-- Quick WhatsApp Action Box -->
                      <?php if ($has_phone): ?>
                        <div class="p-3 rounded mb-4" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25);">
                          <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong class="text-success small fw-bold">
                              <i class="bi bi-whatsapp me-1"></i> Instant WhatsApp Dispatch:
                            </strong>
                            <a href="<?= $wa_url ?>" target="_blank" class="btn btn-success btn-sm px-3 fw-bold">
                              <i class="bi bi-send-fill me-1"></i> Open WhatsApp Web / App
                            </a>
                          </div>
                          <div class="text-muted-iron small">Prefilled Message: <em>"<?= e($default_wa_reply) ?>"</em></div>
                        </div>
                      <?php endif; ?>

                      <!-- Internal Admin Notes Form -->
                      <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="save_notes">
                        <input type="hidden" name="id" value="<?= $inq['id'] ?>">

                        <div class="mb-3">
                          <label for="notes<?= $inq['id'] ?>" class="form-label text-light small fw-bold">
                            <i class="bi bi-pin-angle-fill text-gold me-1"></i>Internal Staff Notes / Follow-Up Actions:
                          </label>
                          <textarea name="admin_notes" id="notes<?= $inq['id'] ?>" rows="2" class="form-control bg-dark text-light border-secondary" placeholder="Write internal notes (e.g. Quoted ₹1,500 for squad LAN on Saturday, customer confirmed)..."><?= e($inq['admin_notes'] ?? '') ?></textarea>
                        </div>
                        <div class="text-end">
                          <button type="submit" class="btn btn-outline-warning btn-sm">Save Internal Note</button>
                        </div>
                      </form>

                    </div>

                    <div class="modal-footer border-secondary border-opacity-25 justify-content-between">
                      <!-- Status Quick Update inside modal -->
                      <form method="POST" action="<?= BASE_URL ?>admin/inquiries.php" class="d-flex align-items-center gap-2">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?= $inq['id'] ?>">
                        <span class="text-muted-iron small">Status:</span>
                        <select name="status" class="form-select form-select-sm bg-dark text-light border-secondary" onchange="this.form.submit()" style="width: auto;">
                          <option value="unread" <?= $inq['status'] === 'unread' ? 'selected' : '' ?>>Unread</option>
                          <option value="read" <?= $inq['status'] === 'read' ? 'selected' : '' ?>>Read</option>
                          <option value="replied" <?= $inq['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
                          <option value="archived" <?= $inq['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                        </select>
                      </form>

                      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>

                  </div>
                </div>
              </div>

            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="text-center py-5 text-muted-iron">
                <i class="bi bi-chat-square-text fs-1 text-gold d-block mb-2"></i>
                <p class="mb-1">No contact inquiries found matching the current criteria.</p>
                <?php if (!empty($status_filter) || !empty($search_query)): ?>
                  <a href="<?= BASE_URL ?>admin/inquiries.php" class="btn btn-outline-secondary btn-sm mt-2">Reset Filters</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
// Mark as replied via async fetch when WhatsApp button is clicked
function markRepliedAsync(inquiryId) {
  const formData = new FormData();
  formData.append('csrf_token', '<?= csrf_token() ?>');
  formData.append('action', 'update_status');
  formData.append('id', inquiryId);
  formData.append('status', 'replied');

  fetch('<?= BASE_URL ?>admin/inquiries.php', {
    method: 'POST',
    body: formData
  }).catch(() => {});
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
