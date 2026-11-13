<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Customers Directory, Player CRM & Broadcast Marketing Center
 */

$admin_title = 'Customers CRM & Marketing Broadcast';
require_once __DIR__ . '/includes/admin-header.php';

$db = get_db();
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'all'); // all, active, vip

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Clear any previous output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $export_sql = "
        SELECT c.id, c.full_name, c.phone, c.email, c.created_at,
               COUNT(b.id) AS actual_bookings, COALESCE(SUM(b.final_amount), 0) AS actual_spent
        FROM customers c
        LEFT JOIN bookings b ON b.customer_id = c.id
        GROUP BY c.id 
        ORDER BY actual_spent DESC, c.id DESC
    ";
    $export_stmt = $db->query($export_sql);
    $rows = $export_stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="customers_broadcast_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    // BOM for Excel compatibility with Unicode
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['Customer ID', 'Full Name', 'Mobile Phone', 'Email Address', 'Total Bookings', 'Lifetime Spend (INR)', 'Joined Date']);

    foreach ($rows as $r) {
        fputcsv($output, [
            $r['id'],
            $r['full_name'],
            $r['phone'],
            $r['email'] ?: 'N/A',
            $r['actual_bookings'],
            number_format((float)$r['actual_spent'], 2, '.', ''),
            date('Y-m-d', strtotime($r['created_at']))
        ]);
    }
    fclose($output);
    exit;
}

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

$sql .= " GROUP BY c.id";

if ($filter === 'active') {
    $sql .= " HAVING actual_bookings > 0";
} elseif ($filter === 'vip') {
    $sql .= " HAVING actual_spent >= 500";
}

$sql .= " ORDER BY actual_spent DESC, c.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Extract unique clean phone numbers and emails for broadcasts
$all_phones = [];
$all_emails = [];

foreach ($customers as $c) {
    $clean = preg_replace('/[^0-9]/', '', $c['phone']);
    if (strlen($clean) >= 10) {
        $clean10 = substr($clean, -10);
        $all_phones[] = $clean10;
    }
    if (!empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
        $all_emails[] = strtolower(trim($c['email']));
    }
}

$all_phones = array_values(array_unique($all_phones));
$all_emails = array_values(array_unique($all_emails));
$phones_comma_separated = implode(', ', $all_phones);
$phones_newline_separated = implode("\n", $all_phones);
$emails_comma_separated = implode(', ', $all_emails);
$emails_bcc_param = implode(',', $all_emails);

$brand_short = get_brand_short();
$brand_name  = get_brand_name();
$brand_city  = get_brand_city();
$booking_url = BASE_URL . 'book.php';
?>

<!-- Header & Quick Action Row -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Customer Directory &amp; Marketing Broadcast</h3>
    <span class="text-muted-iron small">Direct access to player phone numbers &amp; emails for festive offers, tournaments, and promotional announcements.</span>
  </div>

  <div class="d-flex flex-wrap gap-2">
    <!-- Broadcast Campaign Modal Launcher -->
    <button type="button" class="btn btn-warning btn-sm fw-bold text-dark px-3 shadow" data-bs-toggle="modal" data-bs-target="#broadcastCampaignModal">
      <i class="bi bi-megaphone-fill me-1"></i> Send Festival / Promo Offer
    </button>
    <!-- Export CSV -->
    <a href="<?= BASE_URL ?>admin/customers.php?export=csv" class="btn btn-iron btn-iron-outline btn-iron-sm" title="Export full customer database to Excel CSV">
      <i class="bi bi-file-earmark-excel me-1"></i> Export CSV
    </a>
  </div>
</div>

<!-- Broadcast Quick Stats & 1-Click Copy Hub -->
<div class="row g-3 mb-4">
  <!-- Total Players -->
  <div class="col-md-4 col-sm-6">
    <div class="admin-table-card p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted-iron small fw-bold text-uppercase">Total Customers</span>
        <span class="badge bg-gold text-dark fw-bold"><?= count($customers) ?> Players</span>
      </div>
      <div class="fs-4 text-light fw-bold font-monospace"><?= count($customers) ?></div>
      <div class="text-muted-iron small mt-1">Filtered from database</div>
    </div>
  </div>

  <!-- Mobile Numbers Hub -->
  <div class="col-md-4 col-sm-6">
    <div class="admin-table-card p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted-iron small fw-bold text-uppercase"><i class="bi bi-whatsapp text-success me-1"></i> Mobile Numbers</span>
        <span class="badge bg-success text-white fw-bold"><?= count($all_phones) ?> Contacts</span>
      </div>
      <div class="d-flex gap-2 mt-2">
        <button type="button" class="btn btn-outline-success btn-sm flex-fill" onclick="copyToClipboard('<?= addslashes($phones_comma_separated) ?>', 'btnCopyPhonesComma')">
          <i class="bi bi-clipboard-check me-1"></i> <span id="btnCopyPhonesComma">Copy Numbers (CSV)</span>
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" title="Copy Line by Line for Broadcast lists" onclick="copyToClipboard('<?= addslashes($phones_newline_separated) ?>', 'btnCopyPhonesLine')">
          <i class="bi bi-list-ol"></i> <span id="btnCopyPhonesLine" class="d-none d-sm-inline">Lines</span>
        </button>
      </div>
      <div class="text-muted-iron small mt-2" style="font-size: 0.72rem;">Ready to paste into WhatsApp Business or SMS tools.</div>
    </div>
  </div>

  <!-- Email Addresses Hub -->
  <div class="col-md-4 col-sm-12">
    <div class="admin-table-card p-3 h-100">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted-iron small fw-bold text-uppercase"><i class="bi bi-envelope-fill text-gold me-1"></i> Email Addresses</span>
        <span class="badge bg-gold text-dark fw-bold"><?= count($all_emails) ?> Emails</span>
      </div>
      <div class="d-flex gap-2 mt-2">
        <button type="button" class="btn btn-outline-warning btn-sm flex-fill" onclick="copyToClipboard('<?= addslashes($emails_comma_separated) ?>', 'btnCopyEmails')">
          <i class="bi bi-clipboard-check me-1"></i> <span id="btnCopyEmails">Copy All Emails</span>
        </button>
        <?php if (!empty($all_emails)): ?>
          <a href="mailto:?bcc=<?= urlencode($emails_bcc_param) ?>&subject=<?= urlencode('Special Offer from ' . $brand_name) ?>" class="btn btn-outline-secondary btn-sm" title="Launch default email client with all customers in BCC">
            <i class="bi bi-send-fill text-gold"></i> Email All (BCC)
          </a>
        <?php endif; ?>
      </div>
      <div class="text-muted-iron small mt-2" style="font-size: 0.72rem;">BCC protects customer privacy when sending blast updates.</div>
    </div>
  </div>
</div>

<!-- Search & Filter Controls -->
<div class="admin-table-card p-3 mb-4">
  <div class="row g-2 align-items-center">
    <div class="col-md-6 col-lg-5">
      <form method="GET" action="<?= BASE_URL ?>admin/customers.php" class="d-flex gap-2">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-dark border-secondary text-gold"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control bg-dark text-light border-secondary" placeholder="Search Player Name, Mobile Phone, Email..." value="<?= e($search) ?>">
        </div>
        <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">Search</button>
        <?php if (!empty($search) || $filter !== 'all'): ?>
          <a href="<?= BASE_URL ?>admin/customers.php" class="btn btn-outline-secondary btn-sm">Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <div class="col-md-6 col-lg-7 text-md-end">
      <div class="btn-group btn-group-sm" role="group">
        <a href="<?= BASE_URL ?>admin/customers.php?filter=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn <?= $filter === 'all' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary text-light' ?>">
          All Customers (<?= count($customers) ?>)
        </a>
        <a href="<?= BASE_URL ?>admin/customers.php?filter=active<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn <?= $filter === 'active' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary text-light' ?>">
          Active Bookers
        </a>
        <a href="<?= BASE_URL ?>admin/customers.php?filter=vip<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn <?= $filter === 'vip' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary text-light' ?>">
          VIP Players (₹500+)
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Customer Data Table with Direct WhatsApp Launcher -->
<div class="admin-table-card p-4">
  <div class="table-responsive">
    <table class="table table-dark align-middle">
      <thead>
        <tr>
          <th>Player Name</th>
          <th>Mobile Phone</th>
          <th>Email Address</th>
          <th class="text-center">Bookings</th>
          <th>Lifetime Spend</th>
          <th>Registered</th>
          <th class="text-end">Direct Message &amp; History</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($customers)): ?>
          <?php foreach ($customers as $c): ?>
            <?php 
              $c_phone = preg_replace('/[^0-9]/', '', $c['phone']);
              if (strlen($c_phone) >= 10) $c_phone = substr($c_phone, -10);
              $wa_prefill = "Hello " . $c['full_name'] . "! 🎮 Greetings from " . $brand_name . " (" . $brand_city . "). We have an exciting offer for you today! Check our live battle station slots: " . $booking_url;
              $wa_link = "https://wa.me/91" . $c_phone . "?text=" . urlencode($wa_prefill);
            ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="rounded-circle bg-dark border border-secondary text-gold d-flex align-items-center justify-content-center" style="width: 34px; height: 34px; font-size: 0.85rem;">
                    <i class="bi bi-person"></i>
                  </div>
                  <div>
                    <strong class="text-light fs-6 d-block"><?= e($c['full_name']) ?></strong>
                    <?php if ((float)$c['actual_spent'] >= 1000): ?>
                      <span class="badge bg-gold text-dark fw-bold" style="font-size: 0.65rem;">VIP GAMER</span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td>
                <a href="tel:<?= e($c['phone']) ?>" class="text-gold fw-bold text-decoration-none font-monospace">
                  <i class="bi bi-telephone-fill me-1" style="font-size: 0.8rem;"></i> +91 <?= e($c_phone) ?>
                </a>
              </td>
              <td>
                <?php if (!empty($c['email'])): ?>
                  <a href="mailto:<?= e($c['email']) ?>" class="text-muted-iron text-decoration-none small">
                    <i class="bi bi-envelope me-1"></i> <?= e($c['email']) ?>
                  </a>
                <?php else: ?>
                  <span class="text-muted-iron small" style="opacity: 0.5;">—</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <span class="badge bg-secondary"><?= $c['actual_bookings'] ?> Session(s)</span>
              </td>
              <td>
                <strong class="text-green"><?= format_currency($c['actual_spent']) ?></strong>
              </td>
              <td>
                <span class="text-muted-iron small"><?= date('d M Y', strtotime($c['created_at'])) ?></span>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <!-- Direct WhatsApp Message with Prefilled Text -->
                  <a href="<?= $wa_link ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-success" title="Send WhatsApp Message">
                    <i class="bi bi-whatsapp me-1"></i> WhatsApp
                  </a>
                  <!-- View Booking History -->
                  <a href="<?= BASE_URL ?>admin/bookings.php?search=<?= urlencode($c['phone']) ?>" class="btn btn-outline-warning" title="View Customer Reservations">
                    <i class="bi bi-calendar2-week me-1"></i> History
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted-iron small">
              <i class="bi bi-people fs-2 d-block text-gold mb-2"></i>
              No customers found matching your criteria.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- =================================================================
     MODAL: FESTIVAL & PROMOTIONAL MARKETING BROADCAST CENTER
     ================================================================= -->
<div class="modal fade" id="broadcastCampaignModal" tabindex="-1" aria-labelledby="broadcastModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="background: #11151E; border: 1px solid var(--border-color); color: #fff;">
      
      <div class="modal-header border-secondary border-opacity-25 py-3">
        <div>
          <h5 class="modal-title text-gold brand-font mb-0" id="broadcastModalLabel">
            <i class="bi bi-megaphone-fill me-2 text-warning"></i>Festival &amp; Offer Broadcast Campaign
          </h5>
          <small class="text-muted-iron">Craft a message to broadcast to all <?= count($all_phones) ?> customer mobile numbers &amp; <?= count($all_emails) ?> emails.</small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4">

        <!-- 1. Pretyped Festival Template Selector -->
        <div class="mb-3">
          <label for="templateSelect" class="form-label text-light small fw-bold">Select Pre-Typed Template / Occasion</label>
          <select id="templateSelect" class="form-select bg-dark text-light border-secondary">
            <option value="diwali">🪔 Diwali / Festive Season Special (Flat 50% Off)</option>
            <option value="tournament">⚔️ Weekend Clan Tournament (Valorant &amp; CS2)</option>
            <option value="vip_gamer">⚡ Exclusive Gamer Discount (1 Extra Free Hour)</option>
            <option value="new_year">🎆 New Year / Midnight Gaming Bash</option>
            <option value="custom">✍️ Custom Message (Write Your Own)</option>
          </select>
        </div>

        <!-- 2. Message Editor -->
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="broadcastMessage" class="form-label text-light small fw-bold mb-0">Broadcast Message Text</label>
            <span class="text-muted-iron small" id="charCounter">0 characters</span>
          </div>
          <textarea id="broadcastMessage" class="form-control bg-dark text-light border-secondary font-monospace" rows="5" style="font-size: 0.85rem; line-height: 1.45;"></textarea>
          <div class="form-text text-muted-iron" style="font-size: 0.72rem;">
            Tip: Includes your arena booking link so customers can tap and reserve their slots immediately.
          </div>
        </div>

        <!-- 3. One-Click Copy & Blast Options -->
        <div class="row g-2 mb-4">
          <div class="col-sm-4">
            <button type="button" class="btn btn-warning btn-sm w-100 fw-bold text-dark" onclick="copyBroadcastMessage()">
              <i class="bi bi-clipboard-check me-1"></i> <span id="copyMsgBtnLabel">Copy Message Text</span>
            </button>
          </div>
          <div class="col-sm-4">
            <button type="button" class="btn btn-success btn-sm w-100 fw-bold" onclick="copyToClipboard('<?= addslashes($phones_comma_separated) ?>', 'modalCopyPhonesBtn')">
              <i class="bi bi-telephone me-1"></i> <span id="modalCopyPhonesBtn">Copy <?= count($all_phones) ?> Mobile Nos</span>
            </button>
          </div>
          <div class="col-sm-4">
            <a id="btnEmailBccLink" href="#" class="btn btn-outline-info btn-sm w-100 fw-bold">
              <i class="bi bi-envelope-at me-1"></i> Email All (BCC)
            </a>
          </div>
        </div>

        <!-- 4. Individual 1-Click WhatsApp Trigger Table -->
        <div class="border-top border-secondary border-opacity-25 pt-3">
          <h6 class="text-gold small brand-font mb-2">
            <i class="bi bi-whatsapp text-success me-1"></i> 1-Click WhatsApp Dispatch List (Total <?= count($customers) ?> Players)
          </h6>
          <div class="p-2 rounded" style="background: rgba(0,0,0,0.3); max-height: 220px; overflow-y: auto;">
            <table class="table table-sm table-dark mb-0 align-middle">
              <tbody>
                <?php foreach ($customers as $idx => $c): ?>
                  <?php 
                    $cp = preg_replace('/[^0-9]/', '', $c['phone']);
                    if (strlen($cp) >= 10) $cp = substr($cp, -10);
                  ?>
                  <tr>
                    <td class="small py-2">
                      <strong class="text-light"><?= e($c['full_name']) ?></strong>
                      <span class="text-muted-iron ms-1">(+91 <?= e($cp) ?>)</span>
                    </td>
                    <td class="text-end py-2">
                      <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.72rem;" onclick="launchCustomerWhatsApp('<?= e($cp) ?>', '<?= e(addslashes($c['full_name'])) ?>')">
                        <i class="bi bi-whatsapp me-1"></i> Send
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <small class="text-muted-iron d-block mt-2" style="font-size: 0.72rem;">
            Clicking <strong>Send</strong> opens WhatsApp Web / mobile app with your currently selected festival message ready to fire!
          </small>
        </div>

      </div>

      <div class="modal-footer border-secondary border-opacity-25 py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close Window</button>
      </div>

    </div>
  </div>
</div>

<script>
// Templates Dictionary
const templates = {
  diwali: `🪔 Happy Festive Season from <?= e(addslashes($brand_name)) ?>! 🎮\n\nCelebrate this week with exciting discounts on all 4K Esports PC & PS5 sessions in <?= e(addslashes($brand_city)) ?>!\n\nUse Promo Code: FESTIVAL50\nReserve your battle station online now:\n<?= $booking_url ?>\n\nSee you at the sanctuary! GG! 🚀`,
  
  tournament: `🔥 WEEKEND CLAN TOURNAMENT ALERT! 🏆\n\nCompete in Valorant & CS2 squad battles this Sunday at <?= e(addslashes($brand_name)) ?>.\n\n✨ Cash prizes + Energy Drinks for winners!\nLimited rigs available. Book your slot in advance:\n<?= $booking_url ?>\n\nBring your squad and take the crown! 🎮`,
  
  vip_gamer: `⚡ GAMER APPRECIATION SPECIAL ⚡\n\nWe miss you at <?= e(addslashes($brand_short)) ?>! Enjoy 1 Extra FREE Gaming Hour when you book a 2-hour session or more this week.\n\nReserve your slot now:\n<?= $booking_url ?>\n\nHigh FPS, zero lag, aesthetic ambience. Let's play! 🎯`,
  
  new_year: `🎉 NEW YEAR GAMING BASH at <?= e(addslashes($brand_name)) ?>! 🎆\n\nMidnight multiplayer gaming, 4K QLED displays, and cozy gaming lounge.\n\nReserve your squad slots before they sell out:\n<?= $booking_url ?>\n\nLet's kick off the new year with victory royales! 🥂`,
  
  custom: `Hello Gamer! 🎮\n\nSpecial announcement from <?= e(addslashes($brand_name)) ?> in <?= e(addslashes($brand_city)) ?>.\n\nBook your gaming rig online:\n<?= $booking_url ?>\n\nHave a great game!`
};

const textarea = document.getElementById('broadcastMessage');
const selector = document.getElementById('templateSelect');
const counter  = document.getElementById('charCounter');
const emailBccLink = document.getElementById('btnEmailBccLink');

function updateMessage() {
  const chosen = selector.value;
  textarea.value = templates[chosen] || templates.custom;
  updateCounter();
  updateEmailBccLink();
}

function updateCounter() {
  const len = textarea.value.length;
  counter.textContent = `${len} characters (approx. ${Math.ceil(len / 160) || 1} SMS)`;
}

function updateEmailBccLink() {
  const bcc = '<?= addslashes($emails_bcc_param) ?>';
  const subject = encodeURIComponent('Special Offer & Announcement from <?= e(addslashes($brand_name)) ?>');
  const body = encodeURIComponent(textarea.value);
  emailBccLink.href = `mailto:?bcc=${encodeURIComponent(bcc)}&subject=${subject}&body=${body}`;
}

selector.addEventListener('change', updateMessage);
textarea.addEventListener('input', () => {
  updateCounter();
  updateEmailBccLink();
});

// Initial populate
updateMessage();

function copyBroadcastMessage() {
  navigator.clipboard.writeText(textarea.value).then(() => {
    const label = document.getElementById('copyMsgBtnLabel');
    const original = label.innerText;
    label.innerText = 'Copied to Clipboard!';
    setTimeout(() => { label.innerText = original; }, 2500);
  });
}

function copyToClipboard(text, btnId) {
  navigator.clipboard.writeText(text).then(() => {
    const el = document.getElementById(btnId);
    if (el) {
      const original = el.innerText;
      el.innerText = 'Copied!';
      setTimeout(() => { el.innerText = original; }, 2500);
    }
  });
}

function launchCustomerWhatsApp(phone, name) {
  const customGreeting = `Hello ${name}! 👋\n\n`;
  const fullText = customGreeting + textarea.value;
  const url = `https://wa.me/91${phone}?text=${encodeURIComponent(fullText)}`;
  window.open(url, '_blank');
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
