<?php
/**
 * IRONCLAD GAMING SANCTUARY
 * Admin Panel Layout Footer
 */
?>
    </div> <!-- End admin-content-container -->

    <footer class="p-3 text-center text-muted small border-top border-secondary border-opacity-25" style="background: rgba(10, 13, 19, 0.9);">
      <span>&copy; <?= date('Y') ?> <strong>IRONCLAD GAMING SANCTUARY</strong> &bull; Sri Muktsar Sahib, Punjab &bull; System Version <?= APP_VERSION ?></span>
    </footer>
  </div> <!-- End admin-main-wrapper -->

  <!-- Bootstrap 5 Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  
  <script>
    // Admin Sidebar Toggle for Mobile Devices
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const sidebar = document.getElementById('adminSidebar');
    if (toggleBtn && sidebar) {
      toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('show');
      });
      // Click outside closes sidebar on small screens
      document.addEventListener('click', (e) => {
        if (window.innerWidth < 992 && !sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
          sidebar.classList.remove('show');
        }
      });
    }
  </script>
</body>
</html>

