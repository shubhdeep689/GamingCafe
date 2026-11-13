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

// Fetch all gallery items once for instantaneous client-side filtering + badge counts
$all_media = $db->query("SELECT * FROM gallery_images ORDER BY is_featured DESC, display_order ASC, id DESC")->fetchAll();

$count_all = count($all_media);
$count_videos = count(array_filter($all_media, fn($x) => ($x['media_type'] ?? '') === 'video'));
$count_photos = count(array_filter($all_media, fn($x) => ($x['media_type'] ?? '') === 'image'));
$count_pc = count(array_filter($all_media, fn($x) => ($x['category'] ?? '') === 'pc_arena'));
$count_console = count(array_filter($all_media, fn($x) => ($x['category'] ?? '') === 'console_lounge'));
$count_tournaments = count(array_filter($all_media, fn($x) => ($x['category'] ?? '') === 'tournaments'));
$count_rgb = count(array_filter($all_media, fn($x) => ($x['category'] ?? '') === 'rgb_vibes'));

$filter = trim($_GET['category'] ?? 'all');
if (!in_array($filter, ['all', 'videos', 'photos', 'pc_arena', 'console_lounge', 'tournaments', 'rgb_vibes'])) {
    $filter = 'all';
}
?>

<!-- Page Banner -->
<section class="py-5 section-gta6-bg position-relative overflow-hidden" data-bg-url="<?= BASE_URL ?>images/gta6_bg.jpeg" style="background-image: linear-gradient(180deg, rgba(9, 11, 16, 0.45) 0%, rgba(9, 11, 16, 0.18) 50%, rgba(9, 11, 16, 0.50) 100%), url('<?= BASE_URL ?>images/gta6_bg.jpeg'); border-bottom: 1px solid rgba(0, 229, 255, 0.35);">
  <div class="container py-4 text-center position-relative z-2">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-3" style="background: rgba(214,168,75,0.1); border: 1px solid rgba(214,168,75,0.3);">
      <i class="bi bi-camera-reels-fill text-gold"></i>
      <span class="small text-gold text-uppercase fw-bold" style="letter-spacing: 0.1em;">Authentic Sanctuary Media</span>
    </div>
    <h1 class="display-5 fw-bold mt-1 text-light brand-font">Arena Visual Gallery & Reels</h1>
    <p class="text-muted-iron mx-auto mb-3" style="max-width: 680px;">
      Explore real photography and live video clips of our 240Hz PC battle rigs, PS5 console lounge, tournament setup, and ambient RGB atmosphere in <?= e($brand_city) ?>.
    </p>

    <!-- Quick Stat Badges Strip -->
    <div class="d-inline-flex flex-wrap justify-content-center gap-2 text-muted-iron small">
      <span class="badge bg-black bg-opacity-60 border border-secondary border-opacity-25 px-2 py-1"><i class="bi bi-cpu text-gold me-1"></i> 240Hz Esports Rigs</span>
      <span class="badge bg-black bg-opacity-60 border border-secondary border-opacity-25 px-2 py-1"><i class="bi bi-controller text-cyan me-1"></i> PS5 4K Lounges</span>
      <span class="badge bg-black bg-opacity-60 border border-secondary border-opacity-25 px-2 py-1"><i class="bi bi-camera-reels text-danger me-1"></i> Live Action Reels</span>
    </div>
  </div>
</section>

<!-- Gallery Filter & Grid -->
<section class="py-5">
  <div class="container py-2">
    
    <!-- Compact & Beautiful Cybernetic Filter Dock -->
    <div class="gallery-filter-wrapper text-center">
      <div class="gallery-filter-dock">
        <a href="<?= BASE_URL ?>gallery.php?category=all" class="gallery-filter-btn <?= $filter === 'all' ? 'active' : '' ?>" data-filter="all">
          <i class="bi bi-grid-fill"></i>
          <span>All</span>
          <span class="filter-count"><?= $count_all ?></span>
        </a>
        <a href="<?= BASE_URL ?>gallery.php?category=videos" class="gallery-filter-btn btn-cyan-theme <?= $filter === 'videos' ? 'active' : '' ?>" data-filter="videos">
          <i class="bi bi-camera-reels-fill text-danger"></i>
          <span>Reels</span>
          <span class="filter-count"><?= $count_videos ?></span>
        </a>
        <a href="<?= BASE_URL ?>gallery.php?category=photos" class="gallery-filter-btn <?= $filter === 'photos' ? 'active' : '' ?>" data-filter="photos">
          <i class="bi bi-camera-fill text-gold"></i>
          <span>Photos</span>
          <span class="filter-count"><?= $count_photos ?></span>
        </a>
        <a href="<?= BASE_URL ?>gallery.php?category=pc_arena" class="gallery-filter-btn <?= $filter === 'pc_arena' ? 'active' : '' ?>" data-filter="pc_arena">
          <i class="bi bi-display"></i>
          <span>PC Arena</span>
          <span class="filter-count"><?= $count_pc ?></span>
        </a>
        <a href="<?= BASE_URL ?>gallery.php?category=console_lounge" class="gallery-filter-btn <?= $filter === 'console_lounge' ? 'active' : '' ?>" data-filter="console_lounge">
          <i class="bi bi-controller"></i>
          <span>Console</span>
          <span class="filter-count"><?= $count_console ?></span>
        </a>
        <a href="<?= BASE_URL ?>gallery.php?category=tournaments" class="gallery-filter-btn <?= $filter === 'tournaments' ? 'active' : '' ?>" data-filter="tournaments">
          <i class="bi bi-trophy-fill"></i>
          <span>Tournaments</span>
          <span class="filter-count"><?= $count_tournaments ?></span>
        </a>
        <a href="<?= BASE_URL ?>gallery.php?category=rgb_vibes" class="gallery-filter-btn <?= $filter === 'rgb_vibes' ? 'active' : '' ?>" data-filter="rgb_vibes">
          <i class="bi bi-lightning-charge-fill"></i>
          <span>RGB Vibes</span>
          <span class="filter-count"><?= $count_rgb ?></span>
        </a>
      </div>
    </div>

    <!-- Centered Sleek Status Pill -->
    <div class="gallery-status-bar">
      <div class="d-flex align-items-center gap-2">
        <span class="status-indicator"></span>
        <span class="text-light fw-bold" id="activeCategoryLabel"><?= e(ucfirst(str_replace('_', ' ', $filter === 'all' ? 'All Media' : $filter))) ?></span>
        <span class="text-secondary">&bull;</span>
        <span><span class="text-gold fw-bold" id="activeCountDisplay"><?= count(array_filter($all_media, function($x) use ($filter) {
          if ($filter === 'all') return true;
          if ($filter === 'videos') return ($x['media_type'] ?? '') === 'video';
          if ($filter === 'photos') return ($x['media_type'] ?? '') === 'image';
          return ($x['category'] ?? '') === $filter;
        })) ?></span> items</span>
      </div>
      <span class="text-secondary d-none d-sm-inline">&bull;</span>
      <div class="d-none d-sm-flex align-items-center text-muted-iron">
        <span><i class="bi bi-cursor-fill text-cyan me-1"></i> Click card to expand or play</span>
      </div>
    </div>

    <!-- Gallery Grid with Clickable Expanded View -->
    <div class="row g-4" id="ironcladGalleryGrid">
      <?php if (!empty($all_media)): ?>
        <?php foreach ($all_media as $index => $item): ?>
          <?php 
            $is_video = (($item['media_type'] ?? 'image') === 'video'); 
            $media_src = $is_video ? BASE_URL . ltrim($item['video_url'], '/') : BASE_URL . ltrim($item['image_url'], '/');
            $poster_src = BASE_URL . ltrim($item['image_url'], '/');
            
            // Server-side initial visibility matching
            $matches_initial = true;
            if ($filter === 'videos' && !$is_video) $matches_initial = false;
            elseif ($filter === 'photos' && $is_video) $matches_initial = false;
            elseif ($filter !== 'all' && !in_array($filter, ['videos', 'photos']) && ($item['category'] ?? '') !== $filter) $matches_initial = false;
          ?>
          <div class="col-lg-4 col-md-6 gallery-item-col <?= !$matches_initial ? 'filter-hidden' : '' ?>"
               data-item-col
               data-col-cat="<?= e($item['category']) ?>"
               data-col-type="<?= $is_video ? 'videos' : 'photos' ?>">
            <div class="gallery-interactive-card h-100 d-flex flex-column"
                 data-gallery-item
                 data-index="<?= $index ?>"
                 data-media-type="<?= $is_video ? 'video' : 'image' ?>"
                 data-media-url="<?= e($media_src) ?>"
                 data-poster-url="<?= e($poster_src) ?>"
                 data-title="<?= e($item['title']) ?>"
                 data-caption="<?= e($item['caption'] ?? '') ?>"
                 data-category="<?= e(str_replace('_', ' ', $item['category'])) ?>">
              
              <!-- Thumbnail / Poster Container -->
              <div class="position-relative overflow-hidden" style="height: 250px; background: #000;">
                <?php if ($is_video): ?>
                  <video src="<?= e($media_src) ?>" class="w-100 h-100 object-fit-cover" muted loop playsinline preload="metadata"></video>
                  
                  <!-- Center Pulsing Play Trigger -->
                  <div class="position-absolute top-50 start-50 translate-middle">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; background: rgba(9,11,16,0.85); border: 2px solid var(--rgb-cyan); color: #00E5FF; box-shadow: 0 0 20px rgba(0,229,255,0.6);">
                      <i class="bi bi-play-fill fs-2" style="margin-left: 3px;"></i>
                    </div>
                  </div>

                  <span class="position-absolute top-3 start-3 badge bg-danger text-white shadow-sm" style="font-size: 0.7rem; letter-spacing: 0.05em; z-index: 3;">
                    <i class="bi bi-camera-reels me-1"></i> VIDEO REEL
                  </span>
                <?php else: ?>
                  <img src="<?= e($poster_src) ?>" alt="<?= e($item['title']) ?>" class="w-100 h-100 object-fit-cover" loading="lazy">
                  <span class="position-absolute top-3 start-3 badge bg-dark border border-secondary text-gold shadow-sm" style="font-size: 0.7rem; letter-spacing: 0.05em; z-index: 3;">
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
                  <span><i class="bi bi-geo-alt-fill text-danger me-1"></i> <?= e($brand_city) ?> Arena</span>
                  <span class="text-gold fw-bold"><i class="bi bi-eye me-1"></i> <?= $is_video ? 'Watch Reel' : 'View Fullscreen' ?></span>
                </div>
              </div>

            </div>
          </div>
        <?php endforeach; ?>

        <!-- Empty State (Shown when filter has 0 items) -->
        <div class="col-12" id="galleryEmptyState" style="display: none;">
          <div class="iron-card text-center p-5 mx-auto" style="max-width: 650px; background: rgba(17, 21, 30, 0.85); border: 1px solid rgba(214,168,75,0.3); border-radius: 16px;">
            <div class="mb-3">
              <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 70px; height: 70px; background: rgba(214,168,75,0.1); border: 2px solid var(--gold); color: var(--gold);">
                <i class="bi bi-camera-reels fs-2"></i>
              </div>
            </div>
            <h4 class="text-gold brand-font mb-2">No Media in This Category Yet</h4>
            <p class="text-muted-iron small mb-4">
              Our team is currently staging fresh footage and photos for this station. Explore all our battle station captures below.
            </p>
            <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" onclick="applyFilter('all');">
              <i class="bi bi-grid-fill me-1"></i> View All Media
            </button>
          </div>
        </div>

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

      const playPromise = vid.play();
      if (playPromise !== undefined) {
        playPromise.catch(function() {
          vid.muted = true;
          vid.play().catch(function() {});
        });
      }
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

    // Live video thumbnail hover preview
    const cardVid = item.querySelector('video');
    if (cardVid) {
      item.addEventListener('mouseenter', () => { cardVid.play().catch(() => {}); });
      item.addEventListener('mouseleave', () => { cardVid.pause(); cardVid.currentTime = 0; });
    }
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
