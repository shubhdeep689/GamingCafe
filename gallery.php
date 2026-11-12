<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Arena Photography & Visual Video Gallery - Dynamically White-Labeled
 */

require_once __DIR__ . '/config/config.php';

$brand_name    = get_brand_name();
$brand_short   = get_brand_short();
$brand_city    = get_brand_city();
$brand_logo    = get_brand_logo();
$brand_address = get_brand_address();

$page_title = 'Arena Gallery & Reels | ' . $brand_name;
$page_desc = 'Explore high-FPS battle stations, ambient RGB lighting, esports rigs, and gameplay reels at ' . $brand_name . ' in ' . $brand_city . '.';

require_once __DIR__ . '/includes/header.php';

$db = get_db();

$filter = trim($_GET['category'] ?? 'all');
$sql = "SELECT * FROM gallery_images WHERE 1=1";
$params = [];

if ($filter === 'videos') {
    $sql .= " AND media_type = 'video'";
} elseif ($filter === 'photos') {
    $sql .= " AND media_type = 'image'";
} elseif ($filter !== 'all' && in_array($filter, ['pc_arena', 'console_lounge', 'tournaments', 'rgb_vibes'])) {
    $sql .= " AND category = :cat";
    $params[':cat'] = $filter;
}
$sql .= " ORDER BY is_featured DESC, display_order ASC, id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$gallery_items = $stmt->fetchAll();
?>

<!-- Page Banner -->
<section class="py-5 position-relative overflow-hidden" style="background: radial-gradient(circle at 50% 20%, rgba(34, 43, 61, 0.6) 0%, var(--bg-primary) 80%); border-bottom: 1px solid var(--border-color);">
  <div class="container py-4 text-center position-relative z-2">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(214,168,75,0.1); border: 1px solid rgba(214,168,75,0.3);">
      <i class="bi bi-camera-reels-fill text-gold"></i>
      <span class="small text-gold text-uppercase fw-bold" style="letter-spacing: 0.1em;">Authentic Sanctuary Media</span>
    </div>
    <h1 class="display-5 fw-bold mt-1 text-light brand-font">Arena Visual Gallery & Reels</h1>
    <p class="text-muted-iron mx-auto mb-0" style="max-width: 680px;">
      Explore real photography and live video clips of our 240Hz PC battle rigs, PS5 console lounge, tournament setup, and ambient RGB atmosphere in <?= e($brand_city) ?>.
    </p>
  </div>
</section>

<!-- Gallery Filter & Grid -->
<section class="py-5">
  <div class="container py-2">
    
    <!-- Filter Tabs (GUI styled) -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
      <a href="<?= BASE_URL ?>gallery.php?category=all" class="btn btn-iron btn-iron-sm <?= $filter === 'all' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-grid-fill me-1"></i> All Media
      </a>
      <a href="<?= BASE_URL ?>gallery.php?category=videos" class="btn btn-iron btn-iron-sm <?= $filter === 'videos' ? 'btn-iron-cyan' : 'btn-iron-outline' ?>">
        <i class="bi bi-play-circle-fill me-1 text-danger"></i> Video Reels
      </a>
      <a href="<?= BASE_URL ?>gallery.php?category=pc_arena" class="btn btn-iron btn-iron-sm <?= $filter === 'pc_arena' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-display me-1"></i> PC Arena
      </a>
      <a href="<?= BASE_URL ?>gallery.php?category=console_lounge" class="btn btn-iron btn-iron-sm <?= $filter === 'console_lounge' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-controller me-1"></i> Console Lounge
      </a>
      <a href="<?= BASE_URL ?>gallery.php?category=tournaments" class="btn btn-iron btn-iron-sm <?= $filter === 'tournaments' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-trophy-fill me-1"></i> Tournaments
      </a>
      <a href="<?= BASE_URL ?>gallery.php?category=rgb_vibes" class="btn btn-iron btn-iron-sm <?= $filter === 'rgb_vibes' ? 'btn-iron-primary' : 'btn-iron-outline' ?>">
        <i class="bi bi-lightning-charge-fill me-1"></i> RGB Vibes
      </a>
    </div>

    <!-- Gallery Grid with Clickable Expanded View -->
    <div class="row g-4" id="ironcladGalleryGrid">
      <?php if (!empty($gallery_items)): ?>
        <?php foreach ($gallery_items as $index => $item): ?>
          <?php 
            $is_video = (($item['media_type'] ?? 'image') === 'video'); 
            $media_src = $is_video ? BASE_URL . $item['video_url'] : BASE_URL . $item['image_url'];
            $poster_src = BASE_URL . $item['image_url'];
          ?>
          <div class="col-lg-4 col-md-6">
            <div class="gallery-interactive-card h-100 d-flex flex-column"
                 data-gallery-item
                 data-index="<?= $index ?>"
                 data-media-type="<?= $is_video ? 'video' : 'image' ?>"
                 data-media-url="<?= e($media_src) ?>"
                 data-poster-url="<?= e($poster_src) ?>"
                 data-title="<?= e($item['title']) ?>"
                 data-caption="<?= e($item['caption'] ?? '') ?>"
                 data-category="<?= e(str_replace('_', ' ', $item['category'])) ?>">
              
              <!-- Thumbnail / Poster -->
              <div class="position-relative overflow-hidden" style="height: 250px; background: #000;">
                <?php if ($is_video): ?>
                  <video src="<?= e($media_src) ?>" class="w-100 h-100 object-fit-cover" muted preload="metadata"></video>
                  
                  <!-- Center Play Trigger -->
                  <div class="position-absolute top-50 start-50 translate-middle">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(9,11,16,0.85); border: 2px solid var(--rgb-cyan); color: #00E5FF; box-shadow: 0 0 20px rgba(0,229,255,0.6);">
                      <i class="bi bi-play-fill fs-2" style="margin-left: 3px;"></i>
                    </div>
                  </div>

                  <span class="position-absolute top-3 start-3 badge bg-danger text-white shadow-sm" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                    <i class="bi bi-camera-reels me-1"></i> VIDEO REEL
                  </span>
                <?php else: ?>
                  <img src="<?= e($poster_src) ?>" alt="<?= e($item['title']) ?>" class="w-100 h-100 object-fit-cover" loading="lazy">
                  <span class="position-absolute top-3 start-3 badge bg-dark border border-secondary text-gold shadow-sm" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                    <i class="bi bi-image me-1"></i> PHOTO
                  </span>
                <?php endif; ?>

                <!-- Hover Overlay with Expand Cue -->
                <div class="gallery-zoom-overlay">
                  <div class="text-center text-light">
                    <i class="bi <?= $is_video ? 'bi-play-circle-fill' : 'bi-arrows-fullscreen' ?> fs-1 text-gold mb-2 d-block"></i>
                    <span class="small text-uppercase fw-bold brand-font" style="letter-spacing: 0.1em;">
                      <?= $is_video ? 'Play Arena Video' : 'Expand Photo View' ?>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Content Meta -->
              <div class="p-3 flex-grow-1 d-flex flex-column justify-content-between" style="background: #11151E;">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-secondary bg-opacity-35 text-gold small text-uppercase" style="font-size: 0.65rem;">
                      <?= e(str_replace('_', ' ', $item['category'])) ?>
                    </span>
                    <?php if ($item['is_featured']): ?>
                      <span class="badge bg-gold text-dark small fw-bold" style="font-size: 0.65rem;">FEATURED</span>
                    <?php endif; ?>
                  </div>
                  <h5 class="text-light mb-1 fs-6 fw-bold"><?= e($item['title']) ?></h5>
                  <?php if (!empty($item['caption'])): ?>
                    <p class="text-muted-iron mb-0 small" style="font-size: 0.8rem;"><?= e($item['caption']) ?></p>
                  <?php endif; ?>
                </div>

                <div class="pt-2 mt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-muted-iron" style="font-size: 0.75rem;">
                  <span><i class="bi bi-geo-alt text-red me-1"></i> <?= e($brand_city) ?> Arena</span>
                  <span class="text-gold fw-bold"><i class="bi bi-eye me-1"></i> View Fullscreen</span>
                </div>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="iron-card text-center p-5 mx-auto" style="max-width: 700px;">
            <img src="<?= $brand_logo ?>" alt="<?= e($brand_name) ?>" class="img-fluid mb-4" style="max-height: 140px; filter: drop-shadow(0 0 20px rgba(214,168,75,0.4));">
            <h4 class="text-gold brand-font mb-2">ARENA VISUAL ARCHIVE</h4>
            <p class="text-muted-iron small mb-4">
              Real high-resolution photographs and gameplay videos of our battle arena, tournament stages, and console lounges in <?= e($brand_city) ?> are managed through the owner's dashboard.
            </p>
            <div class="p-3 rounded bg-black bg-opacity-40 border border-secondary border-opacity-25 d-inline-block text-start small text-muted-iron">
              <i class="bi bi-camera-fill text-gold me-2"></i>
              Visit the sanctuary in person at <?= e($brand_address) ?> to experience the arena live!
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- =================================================================
     EXPANDED FULLSCREEN LIGHTBOX MODAL
     ================================================================= -->
<div id="ironcladLightbox" class="ironclad-lightbox" role="dialog" aria-modal="true" aria-label="Expanded Media View">
  <button type="button" class="lightbox-close-btn" id="lightboxCloseBtn" title="Close Expanded View (Esc)">
    <i class="bi bi-x-lg"></i>
  </button>

  <button type="button" class="lightbox-nav-btn lightbox-prev" id="lightboxPrevBtn" title="Previous (Left Arrow)">
    <i class="bi bi-chevron-left"></i>
  </button>
  <button type="button" class="lightbox-nav-btn lightbox-next" id="lightboxNextBtn" title="Next (Right Arrow)">
    <i class="bi bi-chevron-right"></i>
  </button>

  <div class="lightbox-content-box">
    <!-- Media Player / Image Area -->
    <div class="lightbox-media-wrapper" id="lightboxMediaArea">
      <!-- Dynamically Populated via JavaScript -->
    </div>

    <!-- Info Bar -->
    <div class="lightbox-info-bar">
      <div>
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="badge bg-gold text-dark fw-bold text-uppercase" id="lightboxCategory" style="font-size: 0.7rem;"></span>
          <span class="text-muted-iron small" id="lightboxCounter"></span>
        </div>
        <h4 class="text-light brand-font mb-0" id="lightboxTitle"></h4>
        <p class="text-muted-iron small mb-0 mt-1" id="lightboxCaption"></p>
      </div>

      <div class="d-flex align-items-center gap-2">
        <a href="<?= BASE_URL ?>book.php" class="btn btn-iron btn-iron-primary btn-iron-sm">
          <i class="bi bi-controller me-1"></i> Book This Station
        </a>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const items = Array.from(document.querySelectorAll('[data-gallery-item]'));
  if (!items.length) return;

  const lightbox = document.getElementById('ironcladLightbox');
  const mediaArea = document.getElementById('lightboxMediaArea');
  const titleEl = document.getElementById('lightboxTitle');
  const captionEl = document.getElementById('lightboxCaption');
  const catEl = document.getElementById('lightboxCategory');
  const counterEl = document.getElementById('lightboxCounter');
  const closeBtn = document.getElementById('lightboxCloseBtn');
  const prevBtn = document.getElementById('lightboxPrevBtn');
  const nextBtn = document.getElementById('lightboxNextBtn');

  let currentIndex = 0;

  function openLightbox(index) {
    currentIndex = (index + items.length) % items.length;
    const el = items[currentIndex];
    const type = el.getAttribute('data-media-type');
    const url = el.getAttribute('data-media-url');
    const poster = el.getAttribute('data-poster-url');
    const title = el.getAttribute('data-title');
    const caption = el.getAttribute('data-caption');
    const category = el.getAttribute('data-category');

    // Populate media
    mediaArea.innerHTML = '';
    if (type === 'video') {
      const vid = document.createElement('video');
      vid.src = url;
      vid.controls = true;
      vid.autoplay = true;
      vid.playsInline = true;
      vid.className = 'w-100 h-100';
      if (poster) vid.poster = poster;
      mediaArea.appendChild(vid);
    } else {
      const img = document.createElement('img');
      img.src = url;
      img.alt = title;
      img.className = 'img-fluid';
      mediaArea.appendChild(img);
    }

    titleEl.textContent = title || '<?= e($brand_short) ?> Sanctuary';
    captionEl.textContent = caption || '';
    catEl.textContent = category || 'ARENA';
    counterEl.textContent = `Media ${currentIndex + 1} of ${items.length}`;

    lightbox.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeLightbox() {
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
    // Pause any playing video
    const vid = mediaArea.querySelector('video');
    if (vid) vid.pause();
    mediaArea.innerHTML = '';
  }

  items.forEach((item, idx) => {
    item.addEventListener('click', () => openLightbox(idx));
  });

  closeBtn.addEventListener('click', closeLightbox);
  prevBtn.addEventListener('click', () => openLightbox(currentIndex - 1));
  nextBtn.addEventListener('click', () => openLightbox(currentIndex + 1));

  // Close when clicking outside content box
  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  // Keyboard navigation
  window.addEventListener('keydown', (e) => {
    if (!lightbox.classList.contains('active')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') openLightbox(currentIndex - 1);
    if (e.key === 'ArrowRight') openLightbox(currentIndex + 1);
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
