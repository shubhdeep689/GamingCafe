<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Dynamic Website Settings & White-Label Configuration Engine
 * Allows owner to completely rebrand and customize cafe name, logo, video, hero text, and location.
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

$upload_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        // 1. Text & Config Fields
        $settings_to_update = [
            'business_name'    => 'general',
            'brand_short_name' => 'general',
            'tagline'          => 'general',
            'hero_badge'       => 'landing',
            'hero_headline_1'  => 'landing',
            'hero_headline_2'  => 'landing',
            'hero_description' => 'landing',
            'address'          => 'general',
            'city'             => 'general',
            'state'            => 'general',
            'pincode'          => 'general',
            'contact_phone'    => 'general',
            'contact_email'    => 'general',
            'whatsapp_number'  => 'general',
            'maps_embed_url'   => 'general',
            'meta_title'       => 'seo',
            'meta_description' => 'seo',
            'meta_keywords'    => 'seo',
            'instagram_url'       => 'social',
            'discord_url'         => 'social',
            'youtube_url'         => 'social',
            'razorpay_key_id'     => 'payment_gateway',
            'razorpay_key_secret' => 'payment_gateway',
            'razorpay_mode'       => 'payment_gateway'
        ];

        foreach ($settings_to_update as $key => $group) {
            if (isset($_POST[$key])) {
                update_setting($key, trim($_POST[$key]), $group);
            }
        }

        // Razorpay Gateway Enable/Disable Toggle
        $razorpay_enabled = isset($_POST['razorpay_enabled']) ? '1' : '0';
        update_setting('razorpay_enabled', $razorpay_enabled, 'payment_gateway');

        // 2. Handle Logo Image Upload or Reset
        if (!empty($_FILES['logo_file']['name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $brand_dir = UPLOAD_DIR . 'branding' . DIRECTORY_SEPARATOR;
            if (!is_dir($brand_dir)) {
                @mkdir($brand_dir, 0755, true);
            }
            $logo_res = handle_file_upload($_FILES['logo_file'], 'branding', 15 * 1024 * 1024, ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);
            if ($logo_res['success']) {
                update_setting('logo_image', $logo_res['file_path'], 'branding');
            } else {
                $upload_errors[] = 'Logo upload failed: ' . $logo_res['error'];
            }
        } elseif (isset($_POST['reset_logo']) && $_POST['reset_logo'] === '1') {
            update_setting('logo_image', 'images/NOBG_IRONCLADLOGO.png', 'branding');
        } elseif (!empty($_POST['logo_image_manual'])) {
            update_setting('logo_image', trim($_POST['logo_image_manual']), 'branding');
        }

        // 3. Handle Arena Tour Video Upload or Reset
        if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
            $brand_dir = UPLOAD_DIR . 'branding' . DIRECTORY_SEPARATOR;
            if (!is_dir($brand_dir)) {
                @mkdir($brand_dir, 0755, true);
            }
            $video_res = handle_file_upload($_FILES['video_file'], 'branding', 150 * 1024 * 1024, ['video/mp4', 'video/webm', 'video/quicktime']);
            if ($video_res['success']) {
                update_setting('arena_tour_video', $video_res['file_path'], 'branding');
            } else {
                $upload_errors[] = 'Video upload failed: ' . $video_res['error'];
            }
        } elseif (isset($_POST['reset_video']) && $_POST['reset_video'] === '1') {
            update_setting('arena_tour_video', 'images/intro_clad.mp4', 'branding');
        } elseif (!empty($_POST['arena_tour_video_manual'])) {
            update_setting('arena_tour_video', trim($_POST['arena_tour_video_manual']), 'branding');
        }

        log_admin_activity($admin_user['id'], 'update_site_settings', 'Updated website business branding, assets & configuration');

        if (!empty($upload_errors)) {
            set_flash('warning', 'Settings updated with notices: ' . implode('; ', $upload_errors));
        } else {
            set_flash('success', 'Gaming cafe identity, assets, and website settings updated successfully!');
        }
        redirect(BASE_URL . 'admin/settings.php');
    }
}

$current_logo_src = get_brand_logo();
$current_video_src = get_brand_video();

$admin_title = 'Cafe Branding & Website Settings';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
  <div class="col-xl-10 col-lg-11">
    <div class="iron-card p-4 p-md-5">
      
      <!-- Top Title & Description -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25 gap-3">
        <div>
          <h3 class="h4 text-gold brand-font mb-1">
            <i class="bi bi-sliders text-cyan me-2"></i>Cafe Customization & Branding Center
          </h3>
          <span class="text-muted-iron small">
            Fully white-label this platform for any gaming cafe. Update business name, logos, tour video, hero headlines, and location in 1 click.
          </span>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-iron btn-iron-cyan btn-iron-sm">
            <i class="bi bi-box-arrow-up-right me-1"></i> Preview Live Website
          </a>
        </div>
      </div>

      <form method="POST" action="<?= BASE_URL ?>admin/settings.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="save_settings" value="1">

        <!-- ==========================================
             SECTION 1: CAFE IDENTITY & BRANDING
             ========================================== -->
        <div class="mb-5">
          <h5 class="text-gold brand-font mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-shield-shaded text-gold fs-5"></i> 1. Core Cafe Identity
          </h5>

          <div class="row g-3">
            <div class="col-md-7">
              <label class="form-label">Full Gaming Cafe Name <span class="text-danger">*</span></label>
              <input type="text" name="business_name" class="form-control" value="<?= e(get_setting('business_name', 'IRONCLAD GAMING SANCTUARY')) ?>" required placeholder="e.g. IRONCLAD GAMING SANCTUARY">
              <div class="form-text text-muted-iron small">Appears across all page headers, receipts, footer, and WhatsApp passes.</div>
            </div>

            <div class="col-md-5">
              <label class="form-label">Short Brand Code / Name <span class="text-danger">*</span></label>
              <input type="text" name="brand_short_name" class="form-control" value="<?= e(get_setting('brand_short_name', 'IRONCLAD')) ?>" required placeholder="e.g. IRONCLAD">
              <div class="form-text text-muted-iron small">Shown in the top navigation bar and logo crest badges.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Cafe Tagline / Slogan <span class="text-danger">*</span></label>
              <input type="text" name="tagline" class="form-control" value="<?= e(get_setting('tagline', 'Gaming. Competition. Community.')) ?>" required placeholder="e.g. Gaming. Competition. Community.">
            </div>
          </div>
        </div>

        <!-- ==========================================
             SECTION 2: LOGO & VISUAL MEDIA MANAGEMENT
             ========================================== -->
        <div class="mb-5 pt-4 border-top border-secondary border-opacity-25">
          <h5 class="text-cyan brand-font mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-images text-cyan fs-5"></i> 2. Logo & Arena Video Tour Management
          </h5>

          <div class="row g-4">
            <!-- Logo Management Card -->
            <div class="col-lg-6">
              <div class="p-4 rounded h-100" style="background: rgba(0,0,0,0.35); border: 1px solid var(--border-color);">
                <span class="d-block fw-bold text-light mb-2"><i class="bi bi-image me-1 text-gold"></i> Official Cafe Logo</span>
                
                <!-- Current Logo Preview -->
                <div class="text-center p-3 mb-3 rounded" style="background: radial-gradient(circle, #1B2230 0%, #0E121A 100%); border: 1px dashed rgba(214,168,75,0.4);">
                  <img src="<?= e($current_logo_src) ?>" alt="Current Logo" class="img-fluid" style="max-height: 90px; filter: drop-shadow(0 0 15px rgba(214,168,75,0.3));">
                  <div class="small text-muted-iron mt-2" style="font-size: 0.72rem;">
                    Active Logo: <code class="text-cyan"><?= e(get_setting('logo_image', 'images/NOBG_IRONCLADLOGO.png')) ?></code>
                  </div>
                </div>

                <!-- Upload New Logo -->
                <div class="mb-3">
                  <label class="form-label small text-light">Upload New Logo (PNG, SVG, WEBP, JPG)</label>
                  <input type="file" name="logo_file" class="form-control form-control-sm" accept=".png,.svg,.webp,.jpg,.jpeg">
                  <div class="form-text text-muted-iron small">Transparent PNG or SVG recommended (max 15MB).</div>
                </div>

                <!-- Manual Path / Reset -->
                <div class="mb-2">
                  <label class="form-label small text-muted-iron">Or Custom Relative Path / URL</label>
                  <input type="text" name="logo_image_manual" class="form-control form-control-sm font-monospace" placeholder="e.g. images/NOBG_IRONCLADLOGO.png" value="<?= e(get_setting('logo_image', 'images/NOBG_IRONCLADLOGO.png')) ?>">
                </div>

                <div class="form-check mt-3">
                  <input class="form-check-input" type="checkbox" name="reset_logo" value="1" id="chkResetLogo">
                  <label class="form-check-label small text-muted-iron" for="chkResetLogo">
                    Reset back to default Ironclad Logo (<span class="text-gold">images/NOBG_IRONCLADLOGO.png</span>)
                  </label>
                </div>
              </div>
            </div>

            <!-- Video Tour Management Card -->
            <div class="col-lg-6">
              <div class="p-4 rounded h-100" style="background: rgba(0,0,0,0.35); border: 1px solid var(--border-color);">
                <span class="d-block fw-bold text-light mb-2"><i class="bi bi-camera-reels text-cyan me-1"></i> Arena Tour Video</span>
                
                <!-- Current Video Preview -->
                <div class="text-center p-2 mb-3 rounded overflow-hidden" style="background: #000; border: 1px dashed rgba(0,229,255,0.4);">
                  <video src="<?= e($current_video_src) ?>" controls playsinline style="max-height: 140px; width: 100%; object-fit: cover; outline: none;"></video>
                  <div class="small text-muted-iron mt-1" style="font-size: 0.72rem;">
                    Active Video: <code class="text-cyan"><?= e(get_setting('arena_tour_video', 'images/intro_clad.mp4')) ?></code>
                  </div>
                </div>

                <!-- Upload New Video -->
                <div class="mb-3">
                  <label class="form-label small text-light">Upload New Arena Video (MP4, WEBM)</label>
                  <input type="file" name="video_file" class="form-control form-control-sm" accept=".mp4,.webm,.mov">
                  <div class="form-text text-muted-iron small">Real arena walkthrough video (MP4/WEBM, max 150MB).</div>
                </div>

                <!-- Manual Path / Reset -->
                <div class="mb-2">
                  <label class="form-label small text-muted-iron">Or Custom Video Path / URL</label>
                  <input type="text" name="arena_tour_video_manual" class="form-control form-control-sm font-monospace" placeholder="e.g. images/intro_clad.mp4" value="<?= e(get_setting('arena_tour_video', 'images/intro_clad.mp4')) ?>">
                </div>

                <div class="form-check mt-3">
                  <input class="form-check-input" type="checkbox" name="reset_video" value="1" id="chkResetVideo">
                  <label class="form-check-label small text-muted-iron" for="chkResetVideo">
                    Reset back to default Arena Video (<span class="text-cyan">images/intro_clad.mp4</span>)
                  </label>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ==========================================
             SECTION 3: LANDING PAGE HERO CUSTOMIZATION
             ========================================== -->
        <div class="mb-5 pt-4 border-top border-secondary border-opacity-25">
          <h5 class="text-red brand-font mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-display text-red fs-5"></i> 3. Landing Page Hero Section Content
          </h5>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Hero Top Pill Badge Text</label>
              <input type="text" name="hero_badge" class="form-control" value="<?= e(get_setting('hero_badge', 'Now Open in Sri Muktsar Sahib')) ?>" placeholder="e.g. Now Open in Sri Muktsar Sahib">
              <div class="form-text text-muted-iron small">Pill badge with green pulse dot shown above the main title.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Hero Headline — Line 1 (White)</label>
              <input type="text" name="hero_headline_1" class="form-control" value="<?= e(get_setting('hero_headline_1', 'ENTER THE ARENA.')) ?>" placeholder="e.g. ENTER THE ARENA.">
            </div>

            <div class="col-md-6">
              <label class="form-label">Hero Headline — Line 2 (Accent / Gradient)</label>
              <input type="text" name="hero_headline_2" class="form-control" value="<?= e(get_setting('hero_headline_2', 'EXPERIENCE THE BATTLE.')) ?>" placeholder="e.g. EXPERIENCE THE BATTLE.">
            </div>

            <div class="col-12">
              <label class="form-label">Hero Subtitle Paragraph</label>
              <textarea name="hero_description" class="form-control" rows="2" placeholder="Brief description of your cafe..."><?= e(get_setting('hero_description', 'Your gaming destination in Sri Muktsar Sahib. Experience esports-grade PC gaming, console lounges, competitive showdowns, and community under one roof.')) ?></textarea>
            </div>
          </div>
        </div>

        <!-- ==========================================
             SECTION 4: PHYSICAL LOCATION & CONTACT
             ========================================== -->
        <div class="mb-5 pt-4 border-top border-secondary border-opacity-25">
          <h5 class="text-green brand-font mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-geo-alt-fill text-green fs-5"></i> 4. Physical Location & Official Contact Channels
          </h5>

          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Full Street Address</label>
              <input type="text" name="address" class="form-control" value="<?= e(get_setting('address', 'Behind Redtape Showroom, Near PSPCL Office, Guru Angad Dev Nagar, Sri Muktsar Sahib, Punjab – 152026')) ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" value="<?= e(get_setting('city', 'Sri Muktsar Sahib')) ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">State</label>
              <input type="text" name="state" class="form-control" value="<?= e(get_setting('state', 'Punjab')) ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">PIN Code</label>
              <input type="text" name="pincode" class="form-control" value="<?= e(get_setting('pincode', '152026')) ?>" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Contact Phone (Voice)</label>
              <input type="tel" name="contact_phone" class="form-control" value="<?= e(get_setting('contact_phone', '')) ?>" placeholder="e.g. 9812345678">
            </div>

            <div class="col-md-4">
              <label class="form-label">Official Contact Email</label>
              <input type="email" name="contact_email" class="form-control" value="<?= e(get_setting('contact_email', 'contact@ironcladgaming.in')) ?>" placeholder="e.g. play@yourcafe.com">
            </div>

            <div class="col-md-4">
              <label class="form-label">WhatsApp Contact Number</label>
              <input type="tel" name="whatsapp_number" class="form-control" value="<?= e(get_setting('whatsapp_number', '')) ?>" placeholder="e.g. 9812345678">
            </div>

            <div class="col-12">
              <label class="form-label">Google Maps Embed URL (Iframe src)</label>
              <input type="text" name="maps_embed_url" class="form-control" value="<?= e(get_setting('maps_embed_url', '')) ?>" placeholder="https://www.google.com/maps/embed?pb=...">
              <div class="form-text text-muted-iron small">Get this from Google Maps &rarr; Share &rarr; Embed a map &rarr; Copy only the <code>src="..."</code> URL.</div>
            </div>
          </div>
        </div>

        <!-- ==========================================
             SECTION 5: SOCIAL MEDIA & SEO DISCOVERABILITY
             ========================================== -->
        <div class="mb-5 pt-4 border-top border-secondary border-opacity-25">
          <h5 class="text-purple brand-font mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-search text-purple fs-5"></i> 5. Social Media & SEO Optimization
          </h5>

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label">Instagram Profile URL</label>
              <input type="url" name="instagram_url" class="form-control" value="<?= e(get_setting('instagram_url', 'https://instagram.com/')) ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Discord Server Invite</label>
              <input type="url" name="discord_url" class="form-control" value="<?= e(get_setting('discord_url', 'https://discord.gg/')) ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">YouTube Channel URL</label>
              <input type="url" name="youtube_url" class="form-control" value="<?= e(get_setting('youtube_url', 'https://youtube.com/')) ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Default SEO Meta Title</label>
              <input type="text" name="meta_title" class="form-control" value="<?= e(get_setting('meta_title', 'IRONCLAD GAMING SANCTUARY | Gaming Cafe in Sri Muktsar Sahib')) ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Default SEO Meta Description</label>
              <textarea name="meta_description" class="form-control" rows="2"><?= e(get_setting('meta_description', '')) ?></textarea>
            </div>

            <div class="col-12">
              <label class="form-label">Target SEO Keywords</label>
              <input type="text" name="meta_keywords" class="form-control" value="<?= e(get_setting('meta_keywords', '')) ?>">
            </div>
          </div>
        </div>

        <!-- ==========================================
             SECTION 6: ONLINE PAYMENT GATEWAY (RAZORPAY)
             ========================================== -->
        <div class="mb-5 pt-4 border-top border-secondary border-opacity-25">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="text-gold brand-font mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-credit-card-2-front text-gold fs-5"></i> 6. Razorpay Payment Gateway Integration
            </h5>
            <span class="badge bg-dark border border-secondary text-cyan font-monospace">API v1</span>
          </div>

          <p class="text-muted-iron small mb-4">
            Connect Razorpay to accept instant payments via UPI (Google Pay, PhonePe, Paytm, BHIM), Credit/Debit Cards, and NetBanking. Customers receive instant booking confirmation without requiring manual counter verification.
          </p>

          <div class="row g-3">
            <div class="col-12">
              <div class="p-3 rounded" style="background: rgba(214,168,75,0.06); border: 1px solid rgba(214,168,75,0.25);">
                <div class="form-check form-switch mb-0">
                  <input class="form-check-input" type="checkbox" role="switch" id="razorpaySwitch" name="razorpay_enabled" value="1" <?= is_razorpay_enabled() ? 'checked' : '' ?>>
                  <label class="form-check-label fw-bold text-light ms-2" for="razorpaySwitch">
                    Enable Razorpay Online Checkout on Website
                  </label>
                </div>
                <div class="form-text text-muted-iron small ms-4 ps-2 mt-1">
                  When enabled, customers can pay instantly with cards and UPI apps. If disabled, users will use the manual UPI QR code scanner.
                </div>
              </div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Gateway Operating Mode</label>
              <select name="razorpay_mode" class="form-select">
                <option value="test" <?= get_razorpay_mode() === 'test' ? 'selected' : '' ?>>Test / Sandbox Mode (Recommended)</option>
                <option value="live" <?= get_razorpay_mode() === 'live' ? 'selected' : '' ?>>Live / Production Mode</option>
              </select>
              <div class="form-text text-muted-iron small">Use test keys for simulated payments; switch to live keys to collect real bank settlements.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Razorpay Key ID <span class="text-danger">*</span></label>
              <input type="text" name="razorpay_key_id" class="form-control font-monospace" value="<?= e(get_razorpay_key_id()) ?>" placeholder="rzp_test_... or rzp_live_...">
              <div class="form-text text-muted-iron small">Available in Razorpay Dashboard &rarr; Settings &rarr; API Keys.</div>
            </div>

            <div class="col-md-4">
              <label class="form-label">Razorpay Key Secret <span class="text-danger">*</span></label>
              <input type="password" name="razorpay_key_secret" class="form-control font-monospace" value="<?= e(get_razorpay_key_secret()) ?>" placeholder="Enter Razorpay Secret Key">
              <div class="form-text text-muted-iron small">Keep this secret secure. Used to sign and verify payments cryptographically.</div>
            </div>
          </div>
        </div>

        <!-- Submit Button Bar -->
        <div class="p-3 rounded d-flex flex-wrap justify-content-between align-items-center gap-3 sticky-bottom" style="background: rgba(14, 18, 26, 0.95); border: 1.5px solid var(--gold); backdrop-filter: blur(10px);">
          <div>
            <span class="text-light fw-bold d-block"><i class="bi bi-check2-circle text-green me-1"></i> Ready to Save?</span>
            <span class="text-muted-iron small">Changes apply immediately across all public pages, booking receipt, and admin dashboards.</span>
          </div>
          <button type="submit" class="btn btn-iron btn-iron-primary py-3 px-5 fs-6">
            <i class="bi bi-save-fill me-2"></i> Save & Apply Cafe Settings
          </button>
        </div>

      </form>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
