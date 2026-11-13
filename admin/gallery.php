<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Arena Visual Gallery & Video Reels Management
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_admin();

$admin_user = current_admin();
$db = get_db();

// Handle Upload Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_upload_gallery'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $title = trim($_POST['title'] ?? 'Arena Station View');
        $caption = trim($_POST['caption'] ?? '');
        $cat = in_array($_POST['category'] ?? '', ['pc_arena', 'console_lounge', 'tournaments', 'rgb_vibes']) ? $_POST['category'] : 'pc_arena';
        $order = (int)($_POST['display_order'] ?? 0);
        $featured = isset($_POST['is_featured']) ? 1 : 0;

        if (!isset($_FILES['gallery_file']) || $_FILES['gallery_file']['error'] !== UPLOAD_ERR_OK) {
            set_flash('danger', 'Please choose an image file to upload.');
        } else {
            $upload = handle_file_upload($_FILES['gallery_file'], 'gallery/');
            if ($upload['success']) {
                $stmt = $db->prepare("
                    INSERT INTO gallery_images (title, caption, category, media_type, image_url, display_order, is_featured)
                    VALUES (:t, :cap, :c, 'image', :u, :ord, :feat)
                ");
                $stmt->execute([
                    ':t'    => $title,
                    ':cap'  => $caption ?: null,
                    ':c'    => $cat,
                    ':u'    => $upload['path'],
                    ':ord'  => $order,
                    ':feat' => $featured
                ]);
                log_admin_activity($admin_user['id'], 'gallery_image_added', "Uploaded gallery image: {$title}");
                set_flash('success', "Image '{$title}' successfully uploaded to arena gallery.");
                redirect(BASE_URL . 'admin/gallery.php');
            } else {
                set_flash('danger', 'Upload Error: ' . $upload['error']);
            }
        }
    }
}

// Handle Upload Video / Reel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_upload_video'])) {
    if (!verify_csrf()) {
        set_flash('danger', 'CSRF verification failed.');
    } else {
        $title = trim($_POST['title'] ?? 'Arena Gameplay Clip');
        $caption = trim($_POST['caption'] ?? '');
        $cat = in_array($_POST['category'] ?? '', ['pc_arena', 'console_lounge', 'tournaments', 'rgb_vibes']) ? $_POST['category'] : 'pc_arena';
        $order = (int)($_POST['display_order'] ?? 0);
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $video_source = trim($_POST['video_source'] ?? 'upload');

        $video_path = null;
        $thumb_path = 'images/NOBG_IRONCLADLOGO.png';

        if ($video_source === 'existing') {
            $chosen = trim($_POST['existing_video'] ?? '');
            if (in_array($chosen, ['images/intro_clad.mp4', 'images/ironclad_boss.mp4'])) {
                $video_path = $chosen;
            } else {
                set_flash('danger', 'Selected preset video is not recognized.');
            }
        } else {
            if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
                set_flash('danger', 'Please choose a video file to upload (MP4, WEBM, MOV).');
            } else {
                $upload = handle_file_upload($_FILES['video_file'], 'gallery/videos/', 104857600, ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime']);
                if ($upload['success']) {
                    $video_path = $upload['path'];
                } else {
                    set_flash('danger', 'Video Upload Error: ' . $upload['error']);
                }
            }
        }

        // Optional custom poster thumbnail
        if (isset($_FILES['video_thumbnail']) && $_FILES['video_thumbnail']['error'] === UPLOAD_ERR_OK) {
            $t_upload = handle_file_upload($_FILES['video_thumbnail'], 'gallery/thumbnails/');
            if ($t_upload['success']) {
                $thumb_path = $t_upload['path'];
            }
        }

        if ($video_path) {
            $stmt = $db->prepare("
                INSERT INTO gallery_images (title, caption, category, media_type, image_url, video_url, display_order, is_featured)
                VALUES (:t, :cap, :c, 'video', :thumb, :vurl, :ord, :feat)
            ");
            $stmt->execute([
                ':t'     => $title,
                ':cap'   => $caption ?: null,
                ':c'     => $cat,
                ':thumb' => $thumb_path,
                ':vurl'  => $video_path,
                ':ord'   => $order,
                ':feat'  => $featured
            ]);
            log_admin_activity($admin_user['id'], 'gallery_video_added', "Uploaded gallery video reel: {$title}");
            set_flash('success', "Video '{$title}' successfully added to arena gallery!");
            redirect(BASE_URL . 'admin/gallery.php');
        }
    }
}

// Handle Delete Item (Photo or Video)
if (isset($_GET['delete_image'])) {
    $del_id = (int)$_GET['delete_image'];
    if (verify_csrf($_GET['token'] ?? '')) {
        $stmt = $db->prepare("SELECT image_url, video_url, title FROM gallery_images WHERE id = :id");
        $stmt->execute([':id' => $del_id]);
        $item = $stmt->fetch();

        if ($item) {
            if (!empty($item['image_url']) && strpos($item['image_url'], 'uploads/') === 0) {
                $filepath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $item['image_url']);
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }
            if (!empty($item['video_url']) && strpos($item['video_url'], 'uploads/') === 0) {
                $vpath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $item['video_url']);
                if (file_exists($vpath)) {
                    @unlink($vpath);
                }
            }

            $db->prepare("DELETE FROM gallery_images WHERE id = :id")->execute([':id' => $del_id]);
            log_admin_activity($admin_user['id'], 'gallery_item_deleted', "Deleted gallery item ID {$del_id} ({$item['title']})");
            set_flash('success', 'Gallery item deleted successfully.');
        }
        redirect(BASE_URL . 'admin/gallery.php');
    }
}

// Fetch Gallery Items
$all_media = $db->query("SELECT * FROM gallery_images ORDER BY display_order ASC, id DESC")->fetchAll();

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

$admin_title = 'Arena Gallery & Reels';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h3 class="h4 text-light mb-1 brand-font"><i class="bi bi-camera-reels me-2 text-gold"></i>Arena Visual Gallery & Reels Management</h3>
    <span class="text-muted-iron small">Curate authentic photography and tournament gameplay video clips of the sanctuary.</span>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
      <i class="bi bi-camera-fill me-1"></i> Upload Photo
    </button>
    <button type="button" class="btn btn-iron btn-iron-cyan btn-iron-sm" data-bs-toggle="modal" data-bs-target="#uploadVideoModal">
      <i class="bi bi-camera-reels-fill me-1"></i> Upload Video Reel
    </button>
    <a href="<?= BASE_URL ?>gallery.php" target="_blank" class="btn btn-outline-secondary btn-sm" title="View Public Arena Gallery">
      <i class="bi bi-box-arrow-up-right me-1"></i> Public Gallery
    </a>
  </div>
</div>

<!-- Compact & Beautiful Cybernetic Filter Dock (Matches Main Gallery) -->
<div class="gallery-filter-wrapper text-center mb-3">
  <div class="gallery-filter-dock">
    <a href="<?= BASE_URL ?>admin/gallery.php?category=all" class="gallery-filter-btn <?= $filter === 'all' ? 'active' : '' ?>" data-filter="all">
      <i class="bi bi-grid-fill"></i>
      <span>All</span>
      <span class="filter-count"><?= $count_all ?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/gallery.php?category=videos" class="gallery-filter-btn btn-cyan-theme <?= $filter === 'videos' ? 'active' : '' ?>" data-filter="videos">
      <i class="bi bi-camera-reels-fill text-danger"></i>
      <span>Reels</span>
      <span class="filter-count"><?= $count_videos ?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/gallery.php?category=photos" class="gallery-filter-btn <?= $filter === 'photos' ? 'active' : '' ?>" data-filter="photos">
      <i class="bi bi-camera-fill text-gold"></i>
      <span>Photos</span>
      <span class="filter-count"><?= $count_photos ?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/gallery.php?category=pc_arena" class="gallery-filter-btn <?= $filter === 'pc_arena' ? 'active' : '' ?>" data-filter="pc_arena">
      <i class="bi bi-display"></i>
      <span>PC Arena</span>
      <span class="filter-count"><?= $count_pc ?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/gallery.php?category=console_lounge" class="gallery-filter-btn <?= $filter === 'console_lounge' ? 'active' : '' ?>" data-filter="console_lounge">
      <i class="bi bi-controller"></i>
      <span>Console</span>
      <span class="filter-count"><?= $count_console ?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/gallery.php?category=tournaments" class="gallery-filter-btn <?= $filter === 'tournaments' ? 'active' : '' ?>" data-filter="tournaments">
      <i class="bi bi-trophy-fill"></i>
      <span>Tournaments</span>
      <span class="filter-count"><?= $count_tournaments ?></span>
    </a>
    <a href="<?= BASE_URL ?>admin/gallery.php?category=rgb_vibes" class="gallery-filter-btn <?= $filter === 'rgb_vibes' ? 'active' : '' ?>" data-filter="rgb_vibes">
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
    <span><i class="bi bi-cursor-fill text-cyan me-1"></i> Hover to preview &bull; Click card to expand</span>
  </div>
</div>

<div class="admin-table-card p-4">
  <div class="row g-4" id="adminGalleryGrid">
    <?php if (!empty($all_media)): ?>
      <?php foreach ($all_media as $imgIndex => $img): ?>
        <?php
          $is_video   = (($img['media_type'] ?? 'image') === 'video');
          $video_src  = ($is_video && !empty($img['video_url'])) ? BASE_URL . ltrim($img['video_url'], '/') : '';
          $image_src  = !empty($img['image_url']) ? BASE_URL . ltrim($img['image_url'], '/') : '';
          $media_src  = $is_video ? $video_src : $image_src;
          $poster_src = $image_src ?: (BASE_URL . 'images/NOBG_IRONCLADLOGO.png');

          // Initial visibility matching
          $matches_initial = true;
          if ($filter === 'videos' && !$is_video) $matches_initial = false;
          elseif ($filter === 'photos' && $is_video) $matches_initial = false;
          elseif ($filter !== 'all' && !in_array($filter, ['videos', 'photos']) && ($img['category'] ?? '') !== $filter) $matches_initial = false;
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6 gallery-item-col <?= !$matches_initial ? 'filter-hidden' : '' ?>"
             data-item-col
             data-col-cat="<?= e($img['category']) ?>"
             data-col-type="<?= $is_video ? 'videos' : 'photos' ?>">
          <div class="admin-gallery-card rounded-3 overflow-hidden border border-secondary border-opacity-25 p-2 bg-dark h-100 d-flex flex-column"
               data-gallery-item
               data-index="<?= $imgIndex ?>"
               data-media-type="<?= $is_video ? 'video' : 'image' ?>"
               data-media-url="<?= e($media_src) ?>"
               data-poster-url="<?= e($poster_src) ?>"
               data-title="<?= e($img['title']) ?>"
               data-caption="<?= e($img['caption'] ?? '') ?>"
               data-category="<?= e(str_replace('_', ' ', $img['category'])) ?>"
               data-order="<?= (int)$img['display_order'] ?>"
               data-featured="<?= $img['is_featured'] ? '1' : '0' ?>"
               style="background: #11151E !important;">
            
            <!-- Clickable Media Preview Container -->
            <div class="gallery-media-thumb position-relative" title="Click to <?= $is_video ? 'Play Video Reel' : 'Expand Photo View' ?>">
              <?php if ($is_video): ?>
                <video src="<?= e($media_src) ?>" class="w-100 h-100 object-fit-cover card-preview-video" muted loop playsinline preload="metadata"></video>
                <div class="position-absolute top-50 start-50 translate-middle">
                  <div class="rounded-circle d-flex align-items-center justify-content-center play-pulsing-badge" style="width: 48px; height: 48px; background: rgba(0,0,0,0.7); border: 2px solid var(--rgb-cyan); color: #00E5FF; box-shadow: 0 0 15px rgba(0,229,255,0.6);">
                    <i class="bi bi-play-fill fs-3" style="margin-left: 3px;"></i>
                  </div>
                </div>
                <span class="position-absolute top-2 start-2 badge bg-danger text-white shadow-sm" style="font-size: 0.65rem; z-index: 3;">
                  <i class="bi bi-camera-reels me-1"></i> VIDEO REEL
                </span>
              <?php else: ?>
                <img src="<?= e($poster_src) ?>" alt="<?= e($img['title']) ?>" class="w-100 h-100 object-fit-cover">
                <span class="position-absolute top-2 start-2 badge bg-dark border border-secondary text-gold shadow-sm" style="font-size: 0.65rem; z-index: 3;">
                  <i class="bi bi-camera me-1"></i> PHOTO
                </span>
              <?php endif; ?>

              <!-- Hover Action Overlay -->
              <div class="gallery-media-overlay">
                <div class="text-center text-light">
                  <i class="bi <?= $is_video ? 'bi-play-circle-fill' : 'bi-arrows-fullscreen' ?> fs-2 text-gold mb-1 d-block"></i>
                  <span class="small text-uppercase fw-bold brand-font" style="letter-spacing: 0.08em; font-size: 0.78rem;">
                    <?= $is_video ? 'Play Video Reel' : 'Expand Photo View' ?>
                  </span>
                </div>
              </div>
            </div>

            <!-- Content Meta -->
            <div class="p-2 flex-grow-1">
              <strong class="text-light d-block small mb-1"><?= e($img['title']) ?></strong>
              <?php if (!empty($img['caption'])): ?>
                <p class="text-muted-iron mb-2" style="font-size: 0.75rem;"><?= e($img['caption']) ?></p>
              <?php endif; ?>
              <div class="d-flex flex-wrap gap-1">
                <span class="badge bg-secondary bg-opacity-50 text-gold small text-uppercase" style="font-size: 0.65rem;">
                  <?= e(str_replace('_', ' ', $img['category'])) ?>
                </span>
                <?php if ($img['is_featured']): ?>
                  <span class="badge bg-gold text-dark small fw-bold" style="font-size: 0.65rem;">Featured</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Actions Bar -->
            <div class="pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center gap-2">
              <span class="text-muted-iron small">Order: <?= (int)$img['display_order'] ?></span>
              <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm <?= $is_video ? 'btn-outline-info' : 'btn-outline-warning' ?> py-1 px-2 preview-trigger-btn" title="<?= $is_video ? 'Play Reel' : 'Expand Image' ?>">
                  <i class="bi <?= $is_video ? 'bi-play-circle-fill' : 'bi-arrows-fullscreen' ?> me-1"></i><?= $is_video ? 'Play' : 'Expand' ?>
                </button>
                <a href="<?= BASE_URL ?>admin/gallery.php?delete_image=<?= $img['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Delete this gallery item permanently?');" title="Delete Item">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </div>

          </div>
        </div>
      <?php endforeach; ?>

      <!-- Empty State for Filter with 0 items -->
      <div class="col-12" id="galleryEmptyState" style="display: none;">
        <div class="p-5 text-center rounded-3 bg-dark border border-secondary border-opacity-25" style="background: #11151E !important;">
          <i class="bi bi-folder2-open display-5 text-gold d-block mb-3"></i>
          <h5 class="text-light brand-font mb-2">No Media in This Station Filter</h5>
          <p class="text-muted-iron small mx-auto mb-4" style="max-width: 480px;">
            There are currently no media items uploaded for this category. Click below to view all sanctuary items or upload new media.
          </p>
          <div class="d-flex justify-content-center gap-2">
            <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" onclick="applyFilter('all');">
              <i class="bi bi-grid-fill me-1"></i> View All Media
            </button>
            <button type="button" class="btn btn-iron btn-iron-cyan btn-iron-sm" data-bs-toggle="modal" data-bs-target="#uploadVideoModal">
              <i class="bi bi-camera-reels-fill me-1"></i> Upload Video Reel
            </button>
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="col-12 text-center py-5">
        <i class="bi bi-images text-gold display-5 d-block mb-3"></i>
        <h5 class="text-light">No Gallery Items Uploaded Yet</h5>
        <p class="text-muted-iron small mx-auto" style="max-width: 480px;">
          Upload real photos or video clips of your battle stations, tournament matches, and console bays to showcase them on the public website.
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Upload Photo Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
      <form method="POST" action="<?= BASE_URL ?>admin/gallery.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action_upload_gallery" value="1">

        <div class="modal-header border-secondary border-opacity-25">
          <h5 class="modal-title text-gold brand-font"><i class="bi bi-camera me-2"></i>Upload Arena Photography</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Image File <span class="text-danger">*</span></label>
              <input type="file" name="gallery_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml" required>
              <div class="form-text text-muted-iron small">Supported: JPG, PNG, WEBP, SVG (Max 100MB).</div>
            </div>
            <div class="col-12">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. PC Rig 01 RGB Setup" required>
            </div>
            <div class="col-12">
              <label class="form-label">Short Caption / Description</label>
              <input type="text" name="caption" class="form-control" placeholder="e.g. 240Hz competitive display and custom liquid cooling">
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category" class="form-select">
                <option value="pc_arena">PC Arena Rigs</option>
                <option value="console_lounge">Console Lounges</option>
                <option value="tournaments">Tournaments / Squads</option>
                <option value="rgb_vibes">RGB Lighting Vibes</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" name="display_order" class="form-control" value="0">
            </div>
            <div class="col-12">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featCheckPhoto" checked>
                <label class="form-check-label text-muted-iron small" for="featCheckPhoto">Feature on Homepage Gallery</label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary border-opacity-25">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-iron btn-iron-primary btn-iron-sm">Upload Photo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Upload Video Modal -->
<div class="modal fade" id="uploadVideoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #151922; border: 1px solid var(--border-color); color: var(--text-main);">
      <form method="POST" action="<?= BASE_URL ?>admin/gallery.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action_upload_video" value="1">

        <div class="modal-header border-secondary border-opacity-25">
          <h5 class="modal-title text-cyan brand-font"><i class="bi bi-camera-reels me-2"></i>Upload Arena Video / Reel</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Video Source</label>
              <select name="video_source" class="form-select" id="videoSourceSelect" onchange="document.getElementById('videoFileUploadBox').style.display = (this.value === 'upload') ? 'block' : 'none'; document.getElementById('videoPresetBox').style.display = (this.value === 'existing') ? 'block' : 'none';">
                <option value="upload">Upload Video File from Computer</option>
                <option value="existing">Select Sanctuary Asset Video (intro_clad.mp4 / ironclad_boss.mp4)</option>
              </select>
            </div>

            <!-- Upload File -->
            <div class="col-12" id="videoFileUploadBox">
              <label class="form-label">Video File (MP4, WEBM, MOV) <span class="text-danger">*</span></label>
              <input type="file" name="video_file" class="form-control" accept="video/mp4,video/webm,video/ogg,video/quicktime">
              <div class="form-text text-muted-iron small">Supported: MP4, WEBM, MOV (Max 100MB).</div>
            </div>

            <!-- Preset Video Selection -->
            <div class="col-12" id="videoPresetBox" style="display: none;">
              <label class="form-label">Select Sanctuary Video</label>
              <select name="existing_video" class="form-select">
                <option value="images/intro_clad.mp4">images/intro_clad.mp4 (Ironclad Arena Setup Tour)</option>
                <option value="images/ironclad_boss.mp4">images/ironclad_boss.mp4 (Ironclad Boss Battle Action)</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Optional Video Poster / Thumbnail</label>
              <input type="file" name="video_thumbnail" class="form-control" accept="image/jpeg,image/png,image/webp">
              <div class="form-text text-muted-iron small">Defaults to Ironclad brand logo if omitted.</div>
            </div>

            <div class="col-12">
              <label class="form-label">Video Title <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Epic Arena Squad Tournament Highlights" required>
            </div>

            <div class="col-12">
              <label class="form-label">Short Caption / Description</label>
              <input type="text" name="caption" class="form-control" placeholder="e.g. 5v5 competitive CS2 showdown at Guru Angad Dev Nagar">
            </div>

            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category" class="form-select">
                <option value="pc_arena">PC Arena Rigs</option>
                <option value="console_lounge">Console Lounges</option>
                <option value="tournaments" selected>Tournaments / Squads</option>
                <option value="rgb_vibes">RGB Lighting Vibes</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label">Display Order</label>
              <input type="number" name="display_order" class="form-control" value="0">
            </div>

            <div class="col-12">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="featCheckVideo" checked>
                <label class="form-check-label text-muted-iron small" for="featCheckVideo">Feature on Homepage Gallery</label>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer border-secondary border-opacity-25">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-iron btn-iron-cyan btn-iron-sm">Upload Video Reel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Master Admin Media Viewer Modal (Images & Video Reels) -->
<div class="modal fade" id="adminMediaViewerModal" tabindex="-1" aria-labelledby="mediaViewerTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content text-light border-0 shadow-lg" style="background: #11151E; border: 1px solid rgba(214, 168, 75, 0.4) !important; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.9), 0 0 35px rgba(214,168,75,0.2) !important;">
      
      <!-- Modal Header -->
      <div class="modal-header border-secondary border-opacity-25 px-4 py-3" style="background: #0B0E14;">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-gold text-dark fw-bold text-uppercase" id="viewerCategoryBadge" style="font-size: 0.72rem; letter-spacing: 0.05em;"></span>
          <span class="badge bg-secondary bg-opacity-50 text-white" id="viewerTypeBadge" style="font-size: 0.72rem;"></span>
          <span class="text-muted-iron small ms-2" id="viewerCounter"></span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a href="#" id="viewerOpenDirectBtn" target="_blank" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Open Original File in New Tab">
            <i class="bi bi-box-arrow-up-right me-1"></i> Open Raw
          </a>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <!-- Modal Body (Media Stage) -->
      <div class="modal-body p-0 position-relative bg-black d-flex align-items-center justify-content-center" style="min-height: 420px; max-height: 74vh; overflow: hidden;">
        
        <!-- Previous Button -->
        <button type="button" class="btn btn-dark position-absolute start-0 top-50 translate-middle-y z-3 rounded-circle d-flex align-items-center justify-content-center m-3 shadow" id="viewerPrevBtn" style="width: 48px; height: 48px; background: rgba(17, 21, 30, 0.85); border: 1px solid rgba(255,255,255,0.2); color: #FFF;" title="Previous Media (Left Arrow)">
          <i class="bi bi-chevron-left fs-4"></i>
        </button>

        <!-- Media Stage Area -->
        <div id="viewerMediaContainer" class="w-100 h-100 d-flex align-items-center justify-content-center p-2" style="max-height: 74vh;">
          <!-- Dynamically populated -->
        </div>

        <!-- Next Button -->
        <button type="button" class="btn btn-dark position-absolute end-0 top-50 translate-middle-y z-3 rounded-circle d-flex align-items-center justify-content-center m-3 shadow" id="viewerNextBtn" style="width: 48px; height: 48px; background: rgba(17, 21, 30, 0.85); border: 1px solid rgba(255,255,255,0.2); color: #FFF;" title="Next Media (Right Arrow)">
          <i class="bi bi-chevron-right fs-4"></i>
        </button>

      </div>

      <!-- Modal Footer (Meta Bar) -->
      <div class="modal-footer border-secondary border-opacity-25 px-4 py-3 d-flex justify-content-between align-items-center" style="background: #0B0E14;">
        <div class="text-start me-auto" style="max-width: 70%;">
          <h5 class="modal-title brand-font text-light mb-1" id="mediaViewerTitle"></h5>
          <p class="text-muted-iron small mb-0" id="viewerCaption"></p>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge" id="viewerFeaturedBadge"></span>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const grid = document.getElementById('adminGalleryGrid');
  const modalEl = document.getElementById('adminMediaViewerModal');
  const bsModal = new bootstrap.Modal(modalEl);
  const mediaContainer = document.getElementById('viewerMediaContainer');
  const titleEl = document.getElementById('mediaViewerTitle');
  const captionEl = document.getElementById('viewerCaption');
  const catBadge = document.getElementById('viewerCategoryBadge');
  const typeBadge = document.getElementById('viewerTypeBadge');
  const counterEl = document.getElementById('viewerCounter');
  const directBtn = document.getElementById('viewerOpenDirectBtn');
  const featuredBadge = document.getElementById('viewerFeaturedBadge');
  const prevBtn = document.getElementById('viewerPrevBtn');
  const nextBtn = document.getElementById('viewerNextBtn');

  const filterBtns = Array.from(document.querySelectorAll('.gallery-filter-btn'));
  const cols = Array.from(document.querySelectorAll('.gallery-item-col'));
  const countDisplay = document.getElementById('activeCountDisplay');
  const emptyState = document.getElementById('galleryEmptyState');
  const activeLabel = document.getElementById('activeCategoryLabel');

  let activeIndex = 0;

  function getVisibleCards() {
    return Array.from(document.querySelectorAll('.gallery-item-col:not(.filter-hidden) [data-gallery-item]'));
  }

  function loadMedia(index) {
    const visibleCards = getVisibleCards();
    if (!visibleCards.length) return;

    activeIndex = (index + visibleCards.length) % visibleCards.length;
    const el = visibleCards[activeIndex];
    const type = el.getAttribute('data-media-type');
    const mediaUrl = el.getAttribute('data-media-url');
    const posterUrl = el.getAttribute('data-poster-url');
    const title = el.getAttribute('data-title');
    const caption = el.getAttribute('data-caption');
    const category = el.getAttribute('data-category');
    const isFeatured = el.getAttribute('data-featured') === '1';

    titleEl.textContent = title || 'Arena Visual';
    captionEl.textContent = caption || 'No additional caption provided.';
    catBadge.textContent = category ? category.toUpperCase() : 'ARENA';
    typeBadge.innerHTML = (type === 'video') 
      ? '<i class="bi bi-camera-reels me-1 text-danger"></i> VIDEO REEL'
      : '<i class="bi bi-camera me-1 text-gold"></i> PHOTO';
    
    counterEl.textContent = `${activeIndex + 1} of ${visibleCards.length}`;
    directBtn.href = mediaUrl;

    if (isFeatured) {
      featuredBadge.className = 'badge bg-gold text-dark fw-bold';
      featuredBadge.textContent = 'Featured on Home';
      featuredBadge.style.display = 'inline-block';
    } else {
      featuredBadge.style.display = 'none';
    }

    mediaContainer.innerHTML = '';

    if (type === 'video') {
      const vid = document.createElement('video');
      vid.src = mediaUrl;
      vid.controls = true;
      vid.autoplay = true;
      vid.playsInline = true;
      vid.className = 'w-100';
      vid.style.maxHeight = '72vh';
      vid.style.objectFit = 'contain';
      vid.style.backgroundColor = '#000';
      if (posterUrl) vid.poster = posterUrl;

      mediaContainer.appendChild(vid);

      // Attempt autoplay with sound; fallback to muted autoplay if browser blocks
      const playPromise = vid.play();
      if (playPromise !== undefined) {
        playPromise.catch(function() {
          vid.muted = true;
          vid.play().catch(function() {});
        });
      }
    } else {
      const img = document.createElement('img');
      img.src = mediaUrl;
      img.alt = title || 'Arena photography';
      img.className = 'img-fluid';
      img.style.maxHeight = '72vh';
      img.style.objectFit = 'contain';
      img.style.boxShadow = '0 10px 30px rgba(0,0,0,0.8)';

      mediaContainer.appendChild(img);
    }
  }

  // Card click triggers modal (delegated, ignores delete button)
  if (grid) {
    grid.addEventListener('click', function(e) {
      if (e.target.closest('a[href*="delete_image"]')) return;
      const card = e.target.closest('[data-gallery-item]');
      if (!card) return;
      const visibleCards = getVisibleCards();
      const idx = visibleCards.indexOf(card);
      if (idx !== -1) {
        loadMedia(idx);
        bsModal.show();
      }
    });
  }

  // Video hover live preview
  cols.forEach(col => {
    const cardVideo = col.querySelector('.card-preview-video');
    if (cardVideo) {
      col.addEventListener('mouseenter', function() {
        cardVideo.play().catch(function() {});
      });
      col.addEventListener('mouseleave', function() {
        cardVideo.pause();
        cardVideo.currentTime = 0;
      });
    }
  });

  // Client-side Instant Filter Controller (Matches Main Gallery)
  window.applyFilter = function(filterVal, updateUrl = true) {
    let visibleCount = 0;

    filterBtns.forEach(btn => {
      btn.classList.toggle('active', btn.getAttribute('data-filter') === filterVal);
    });

    cols.forEach(col => {
      const cat = col.getAttribute('data-col-cat');
      const type = col.getAttribute('data-col-type');
      let show = false;

      if (filterVal === 'all') {
        show = true;
      } else if (filterVal === 'videos') {
        show = (type === 'videos');
      } else if (filterVal === 'photos') {
        show = (type === 'photos');
      } else {
        show = (cat === filterVal);
      }

      if (show) {
        col.classList.remove('filter-hidden');
        col.style.opacity = '0';
        col.style.transform = 'scale(0.96)';
        requestAnimationFrame(() => {
          col.style.opacity = '1';
          col.style.transform = 'scale(1)';
        });
        visibleCount++;
      } else {
        col.classList.add('filter-hidden');
      }
    });

    if (countDisplay) countDisplay.textContent = visibleCount;
    if (emptyState) emptyState.style.display = (visibleCount === 0) ? 'block' : 'none';

    const labels = {
      'all': 'All Media',
      'videos': 'Video Reels',
      'photos': 'Photography',
      'pc_arena': 'PC Arena Rigs',
      'console_lounge': 'Console Lounges',
      'tournaments': 'Tournaments & Squads',
      'rgb_vibes': 'RGB Lighting Vibes'
    };
    if (activeLabel) activeLabel.textContent = labels[filterVal] || 'Arena Media';

    if (updateUrl && window.history && window.history.pushState) {
      const newUrl = (filterVal === 'all') 
        ? '<?= BASE_URL ?>admin/gallery.php' 
        : `<?= BASE_URL ?>admin/gallery.php?category=${encodeURIComponent(filterVal)}`;
      window.history.pushState({ category: filterVal }, '', newUrl);
    }
  };

  filterBtns.forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      const val = this.getAttribute('data-filter');
      applyFilter(val, true);
    });
  });

  window.addEventListener('popstate', function(e) {
    const params = new URLSearchParams(window.location.search);
    applyFilter(params.get('category') || 'all', false);
  });

  if (prevBtn) {
    prevBtn.addEventListener('click', function() {
      loadMedia(activeIndex - 1);
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function() {
      loadMedia(activeIndex + 1);
    });
  }

  // Keyboard navigation when modal is open
  window.addEventListener('keydown', function(e) {
    if (!modalEl.classList.contains('show')) return;
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      loadMedia(activeIndex - 1);
    } else if (e.key === 'ArrowRight') {
      e.preventDefault();
      loadMedia(activeIndex + 1);
    }
  });

  // Cleanup video on modal dismiss
  modalEl.addEventListener('hide.bs.modal', function() {
    const vid = mediaContainer.querySelector('video');
    if (vid) {
      vid.pause();
      vid.currentTime = 0;
    }
    mediaContainer.innerHTML = '';
  });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
