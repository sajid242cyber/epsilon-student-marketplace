</main>

<footer class="bg-white border-top mt-4 py-4">
  <div class="container-fluid px-3 px-lg-4">
    <div class="row gy-3">
      <div class="col-12 col-md-6">
        <h6 class="fw-bold d-flex align-items-center gap-2">
          <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="" width="26" height="26">
          <span class="brand-word">Epsilon</span>
        </h6>
        <p class="text-muted small mb-0">A second-hand book and gadget marketplace built exclusively for university students.</p>
      </div>
      <div class="col-6 col-md-3">
        <h6 class="fw-bold">Categories</h6>
        <ul class="list-unstyled small">
          <li><a href="<?php echo BASE_URL; ?>/search.php?category=Books" class="text-muted">Books</a></li>
          <li><a href="<?php echo BASE_URL; ?>/search.php?category=Laptop" class="text-muted">Laptops</a></li>
          <li><a href="<?php echo BASE_URL; ?>/search.php?category=Phone" class="text-muted">Phones</a></li>
        </ul>
      </div>
      <div class="col-6 col-md-3">
        <h6 class="fw-bold">Account</h6>
        <ul class="list-unstyled small">
          <li><a href="<?php echo BASE_URL; ?>/auth/login.php" class="text-muted">Login</a></li>
          <li><a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-muted">Register</a></li>
        </ul>
      </div>
    </div>
    <hr>
    <p class="text-muted small mb-0 text-center">&copy; <?php echo date('Y'); ?> Epsilon. University Student Marketplace.</p>
  </div>
</footer>

<!-- Bootstrap 5 JS Bundle (self-hosted) -->
<script src="<?php echo BASE_URL; ?>/assets/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/script.js?v=<?php echo filemtime(ROOT_PATH . "/assets/js/script.js"); ?>"></script>

</body>
</html>
