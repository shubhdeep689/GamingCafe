<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Contact & Location Directions - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name     = get_brand_name();
$brand_short    = get_brand_short();
$brand_city     = get_brand_city();
$brand_state    = get_brand_state();
$brand_address  = get_brand_address();
$email          = get_brand_email();
$phone          = get_brand_phone();
$maps_url       = get_setting('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13788.134560790832!2d74.5029415!3d30.477028!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x39174542ee59815d%3A0xe6bf448f43058a28!2sSri%20Muktsar%20Sahib%2C%20Punjab%20152026!5e0!3m2!1sen!2sin!4v1700000000000');

$page_title = 'Contact & Location Directions | ' . $brand_name;
$page_desc = 'Find ' . $brand_name . ' in ' . $brand_city . ', ' . $brand_state . '. Contact us for tournament bookings, squad reservations, and inquiries.';

require_once __DIR__ . '/includes/header.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    if (!verify_csrf()) {
        $errors[] = 'Security token expired. Please refresh and try again.';
    } else {
        $name    = to_title_case(trim($_POST['name'] ?? ''));
        $contact = trim($_POST['contact'] ?? '');
        $subject = trim($_POST['subject'] ?? 'General Inquiry');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || mb_strlen($name) < 2) {
            $errors[] = 'Please provide your full name (at least 2 characters).';
        }
        if (empty($contact) || strlen($contact) < 5) {
            $errors[] = 'Please provide a valid mobile number or email address.';
        }
        if (empty($message) || mb_strlen($message) < 8) {
            $errors[] = 'Please write a message with at least 8 characters.';
        }

        if (empty($errors)) {
            $db = get_db();

            // Parse contact details
            $parsed_email = null;
            $parsed_phone = null;

            if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
                $parsed_email = strtolower($contact);
            }

            $clean_ph = clean_indian_phone($contact);
            if (!empty($clean_ph) && preg_match('/^[6-9]\d{9}$/', $clean_ph)) {
                $parsed_phone = $clean_ph;
            }

            // Save inquiry to backend inquiries table
            $stmt_inq = $db->prepare("
                INSERT INTO inquiries (name, email, phone, contact_raw, subject, message, status, ip_address)
                VALUES (:n, :e, :p, :cr, :s, :m, 'unread', :ip)
            ");
            $stmt_inq->execute([
                ':n'  => $name,
                ':e'  => $parsed_email,
                ':p'  => $parsed_phone,
                ':cr' => $contact,
                ':s'  => $subject,
                ':m'  => $message,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
            $inquiry_id = (int)$db->lastInsertId();

            // Automatically capture lead in customers table if phone number is provided
            if ($parsed_phone) {
                $chk_c = $db->prepare("SELECT id FROM customers WHERE phone = :p LIMIT 1");
                $chk_c->execute([':p' => $parsed_phone]);
                $c_exists = $chk_c->fetch();

                if (!$c_exists) {
                    $ins_c = $db->prepare("
                        INSERT INTO customers (full_name, phone, email, notes, total_bookings, total_spent)
                        VALUES (:fn, :ph, :em, :notes, 0, 0.00)
                    ");
                    $ins_c->execute([
                        ':fn'    => $name,
                        ':ph'    => $parsed_phone,
                        ':em'    => $parsed_email ?: null,
                        ':notes' => "Lead captured via Contact Form Inquiry #{$inquiry_id} ({$subject})"
                    ]);
                }
            }

            // Log activity for owner review
            log_admin_activity(null, 'contact_inquiry', "New inquiry #{$inquiry_id} from {$name} ({$contact}): {$subject}");

            set_flash('success', "Thank you, {$name}! Your message has been received by our arena team. We will contact you at {$contact} shortly.");
            redirect(BASE_URL . 'contact.php');
        }
    }
}
?>

<!-- Page Banner -->
<section class="py-5 section-gta6-bg position-relative overflow-hidden" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.45) 0%, rgba(9, 11, 16, 0.18) 50%, rgba(9, 11, 16, 0.50) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-bottom: 1px solid rgba(0, 229, 255, 0.35);">
  <div class="container py-4 text-center position-relative z-2">
    <span class="section-subtitle">Reach The Sanctuary</span>
    <h1 class="display-5 fw-bold mt-2">Contact & Location</h1>
    <p class="text-muted-iron mx-auto" style="max-width: 650px;">
      Visit us in <?= e($brand_city) ?>, <?= e($brand_state) ?>, or drop us a line for private squad LAN bookings and tournament arrangements.
    </p>
  </div>
</section>

<section class="py-5 section-cafe-bg position-relative" data-bg-url="<?= BASE_URL ?>images/cafe_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.50) 0%, rgba(9, 11, 16, 0.25) 50%, rgba(9, 11, 16, 0.55) 100%), url('<?= BASE_URL ?>images/cafe_bg.jpeg');">
  <div class="container py-2">
    
    <?= display_flash() ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 small ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= e($err) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="row g-5">
      
      <!-- Contact Info & Directions -->
      <div class="col-lg-5">
        <h3 class="h4 text-gold mb-3 brand-font">Visit Our Arena</h3>
        <p class="text-muted-iron small mb-4">
          Located in a prime, central spot in <?= e($brand_city) ?> with direct road connectivity and ample vehicle parking.
        </p>

        <div class="iron-card p-4 mb-4">
          <div class="d-flex align-items-start gap-3 mb-4 pb-3 border-bottom border-secondary border-opacity-25">
            <div class="feature-icon-box text-red mb-0" style="width: 44px; height: 44px;">
              <i class="bi bi-geo-alt-fill"></i>
            </div>
            <div>
              <strong class="text-light d-block mb-1">Confirmed Address:</strong>
              <div class="text-muted-iron small"><?= nl2br(e($brand_address)) ?></div>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3 mb-4 pb-3 border-bottom border-secondary border-opacity-25">
            <div class="feature-icon-box text-gold mb-0" style="width: 44px; height: 44px;">
              <i class="bi bi-clock-history"></i>
            </div>
            <div>
              <strong class="text-light d-block mb-1">Operating Hours:</strong>
              <div class="text-green fw-bold small">Monday – Sunday: 10:00 AM – 11:00 PM</div>
              <div class="text-muted-iron small">Open on weekends and major public holidays.</div>
            </div>
          </div>

          <div class="d-flex align-items-start gap-3 <?= !empty($phone) ? 'mb-4 pb-3 border-bottom border-secondary border-opacity-25' : '' ?>">
            <div class="feature-icon-box text-cyan mb-0" style="width: 44px; height: 44px;">
              <i class="bi bi-envelope-fill"></i>
            </div>
            <div>
              <strong class="text-light d-block mb-1">Electronic Mail:</strong>
              <div class="text-muted-iron small"><a href="mailto:<?= e($email) ?>" class="text-cyan text-decoration-none"><?= e($email) ?></a></div>
            </div>
          </div>

          <?php if (!empty($phone)): ?>
          <div class="d-flex align-items-start gap-3">
            <div class="feature-icon-box text-green mb-0" style="width: 44px; height: 44px;">
              <i class="bi bi-telephone-fill"></i>
            </div>
            <div>
              <strong class="text-light d-block mb-1">Direct Phone / Helpline:</strong>
              <div class="text-muted-iron small"><a href="tel:<?= e($phone) ?>" class="text-green text-decoration-none"><?= e($phone) ?></a></div>
            </div>
          </div>
          <?php endif; ?>

          <div class="mt-3 pt-3 border-top border-secondary border-opacity-25">
            <div class="small fw-bold text-gold mb-2"><i class="bi bi-patch-check-fill me-1"></i> Arena Perks &amp; Hospitality:</div>
            <div class="d-flex flex-wrap gap-2">
              <span class="badge bg-dark border border-cyan text-cyan small"><i class="bi bi-tv-fill me-1"></i> 4K + QLED Screens</span>
              <span class="badge bg-dark border border-warning text-gold small"><i class="bi bi-stars me-1"></i> Cozy &amp; Aesthetic Ambience</span>
              <span class="badge bg-dark border border-danger text-danger small"><i class="bi bi-cup-straw me-1"></i> Beverage Cans &amp; Packed Food</span>
              <span class="badge bg-dark border border-success text-success small"><i class="bi bi-bag-check-fill me-1"></i> Outside Food Allowed</span>
            </div>
          </div>
        </div>

        <a href="https://maps.google.com/?q=<?= urlencode($brand_address) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-iron btn-iron-primary w-100 py-3">
          <i class="bi bi-geo-fill me-1"></i> Open Google Maps Navigation
        </a>
      </div>

      <!-- Contact Message Form -->
      <div class="col-lg-7">
        <div class="iron-card p-4 p-md-5 h-100">
          <h3 class="h4 text-gold mb-2 brand-font">Send Us an Inquiry</h3>
          <p class="text-muted-iron small mb-4">
            Interested in booking all stations for a private squad showdown, tournament hosting, or have hardware questions? Fill out the form below.
          </p>

          <form method="POST" action="<?= BASE_URL ?>contact.php">
            <?= csrf_field() ?>
            <input type="hidden" name="submit_contact" value="1">

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label for="cName" class="form-label">Full Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="cName" class="form-control" placeholder="Your name" required>
              </div>

              <div class="col-md-6">
                <label for="cContact" class="form-label">Phone or Email <span class="text-danger">*</span></label>
                <input type="text" name="contact" id="cContact" class="form-control" placeholder="Mobile number or email" required>
              </div>

              <div class="col-12">
                <label for="cSubject" class="form-label">Inquiry Subject</label>
                <select name="subject" id="cSubject" class="form-select">
                  <option value="General Inquiry">General Question / Session Booking</option>
                  <option value="Squad LAN Tournament">Private Squad LAN / Tournament Booking</option>
                  <option value="Hardware Suggestion">Hardware / Game Library Suggestion</option>
                  <option value="Partnership / Sponsorship">Brand Partnership / College Esports</option>
                </select>
              </div>

              <div class="col-12">
                <label for="cMessage" class="form-label">Your Message <span class="text-danger">*</span></label>
                <textarea name="message" id="cMessage" rows="5" class="form-control" placeholder="Write your message here..." required></textarea>
              </div>
            </div>

            <button type="submit" class="btn btn-iron btn-iron-primary py-3 px-5">
              <i class="bi bi-send-fill me-1"></i> Dispatch Message
            </button>
          </form>
        </div>
      </div>

    </div>

    <!-- Map View -->
    <div class="mt-5 rounded-4 overflow-hidden border border-secondary border-opacity-25" style="height: 380px;">
      <iframe 
        src="<?= e($maps_url) ?>" 
        width="100%" 
        height="100%" 
        style="border:0; filter: grayscale(80%) invert(90%) contrast(85%);" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade"
        title="<?= e($brand_name) ?> <?= e($brand_city) ?>">
      </iframe>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

