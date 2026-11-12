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
$images = $db->query("SELECT * FROM gallery_images ORDER BY display_order ASC, id DESC")->fetchAll();

$admin_title = 'Arena Gallery & Reels';
require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
  <div>
    <h3 class="h4 text-light mb-1 brand-font">Arena Visual Gallery & Reels</h3>
    <span class="text-muted-iron small">Curate authentic photography and tournament gameplay video clips of the sanctuary.</span>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-iron btn-iron-primary btn-iron-sm" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
      <i class="bi bi-camera-fill me-1"></i> Upload Photo
    </button>
    <button type="button" class="btn btn-iron btn-iron-cyan btn-iron-sm" data-bs-toggle="modal" data-bs-target="#uploadVideoModal">
      <i class="bi bi-camera-reels-fill me-1"></i> Upload Video / Reel
    </button>
  </div>
</div>

<div class="admin-table-card p-4">
  <div class="row g-4">
    <?php if (!empty($images)): ?>
      <?php foreach ($images as $img): ?>
        <div class="col-xl-3 col-lg-4 col-md-6">
          <div class="rounded-3 overflow-hidden border border-secondary border-opacity-25 p-2 bg-dark h-100 d-flex flex-column" style="background: #11151E !important;">
            
            <!-- Media Preview Container -->
            <div style="height: 180px; overflow: hidden; background: #000; position: relative;" class="rounded">
              <?php if (($img['media_type'] ?? 'image') === 'video' && !empty($img['video_url'])): ?>
                <video src="<?= BASE_URL . e($img['video_url']) ?>" class="w-100 h-100 object-fit-cover" muted preload="metadata"></video>
                <div class="position-absolute top-50 start-50 translate-middle pointer-events-none">
                  <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(0,0,0,0.65); border: 2px solid var(--rgb-cyan); color: #00E5FF; box-shadow: 0 0 15px rgba(0,229,255,0.5);">
                    <i class="bi bi-play-fill fs-3" style="margin-left: 3px;"></i>
                  </div>
                </div>
                <span class="position-absolute top-2 start-2 badge bg-danger text-white shadow-sm" style="font-size: 0.65rem;">
                  <i class="bi bi-camera-reels me-1"></i> VIDEO
                </span>
              <?php else: ?>
                <img src="<?= BASE_URL . e($img['image_url']) ?>" alt="<?= e($img['title']) ?>" class="w-100 h-100 object-fit-cover">
                <span class="position-absolute top-2 start-2 badge bg-dark border border-secondary text-gold shadow-sm" style="font-size: 0.65rem;">
                  <i class="bi bi-camera me-1"></i> PHOTO
                </span>
              <?php endif; ?>
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

            <!-- Actions -->
            <div class="pt-2 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
              <span class="text-muted-iron small">Order: <?= $img['display_order'] ?></span>
              <a href="<?= BASE_URL ?>admin/gallery.php?delete_image=<?= $img['id'] ?>&token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this gallery item permanently?');">
                <i class="bi bi-trash"></i> Delete
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
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

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
