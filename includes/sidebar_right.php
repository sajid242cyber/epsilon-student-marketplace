<div class="cx-sidebar">

  <?php if (isLoggedIn()): ?>
  <div class="cx-sidebar-card cx-cta">
    <span class="cx-cta-icon"><i class="bi bi-plus-lg"></i></span>
    <p class="cx-cta-text">Got something to sell? Put it up in a minute.</p>
    <a href="<?php echo BASE_URL; ?>/user/product/post.php" class="btn btn-brand w-100">Post a Product</a>
  </div>
  <?php else: ?>
  <div class="cx-sidebar-card cx-cta">
    <span class="cx-cta-icon"><i class="bi bi-person-plus"></i></span>
    <p class="cx-cta-text">Join Epsilon to buy &amp; sell with fellow students.</p>
    <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-brand w-100">Create Account</a>
  </div>
  <?php endif; ?>

  <div class="cx-sidebar-card">
    <h6 class="cx-sidebar-title"><i class="bi bi-shield-check"></i> Safety Tips</h6>
    <ul class="cx-tips">
      <li>Meet in a safe, public campus location.</li>
      <li>Check the item before paying.</li>
      <li>Never share your password with anyone.</li>
      <li>Report suspicious listings immediately.</li>
    </ul>
  </div>

</div>
