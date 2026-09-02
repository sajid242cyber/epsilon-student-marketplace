<?php
$currentQuery = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$loggedIn = isLoggedIn();
$wishlistCount = 0;
$notifCount = 0;
if ($loggedIn && isset($conn)) {
    $wishlistCount = getWishlistCount($conn, $_SESSION['user_id']);
    $notifCount = getUnreadNotificationCount($conn, $_SESSION['user_id']);
}
?>
<!-- The bar floats on the page background rather than spanning edge to edge, so
     the dark colour is applied to the inner rounded block, not to <nav>. -->
<nav class="cx-navbar sticky-top">
  <div class="cx-navbar-inner navbar navbar-expand-lg py-2">
    <div class="container-fluid px-3">

    <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/index.php">
      <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="" width="34" height="34" class="brand-logo">
      <span class="brand-word">Epsilon</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNavbar">

      <!-- Search -->
      <form class="d-flex flex-grow-1 mx-lg-4 my-3 my-lg-0" role="search" method="GET" action="<?php echo BASE_URL; ?>/search.php">
        <div class="input-group">
          <span class="input-group-text border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" name="q" class="form-control border-start-0" placeholder="Search books, laptops, phones..." value="<?php echo $currentQuery; ?>">
        </div>
      </form>

      <!-- Right side links -->
      <ul class="navbar-nav align-items-lg-center gap-2 ms-auto">

        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>/index.php" class="nav-icon-link" title="Home">
            <i class="bi bi-house-door"></i>
          </a>
        </li>

        <?php if ($loggedIn): ?>
          <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/product/post.php" class="nav-icon-link" title="Sell Something">
              <i class="bi bi-plus-square"></i>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/wishlist/index.php" class="nav-icon-link" title="Wishlist">
              <i class="bi bi-heart"></i>
              <?php if ($wishlistCount > 0): ?>
                <span class="badge rounded-pill bg-danger badge-count"><?php echo $wishlistCount; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/user/notification/index.php" class="nav-icon-link" title="Notifications">
              <i class="bi bi-bell"></i>
              <?php if ($notifCount > 0): ?>
                <span class="badge rounded-pill bg-danger badge-count"><?php echo $notifCount; ?></span>
              <?php endif; ?>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
              <span class="badge rounded-circle bg-secondary" style="width:36px;height:36px;line-height:24px;">
                <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
              </span>
              <span class="d-none d-lg-inline"><?php echo sanitize($_SESSION['full_name'] ?? 'User'); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/product/my_products.php"><i class="bi bi-box-seam me-2"></i>My Products</a></li>
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/bid/my_bids.php"><i class="bi bi-hammer me-2"></i>My Bids</a></li>
              <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/user/transaction/index.php"><i class="bi bi-receipt me-2"></i>My Transactions</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-outline-brand">Login</a>
          </li>
          <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/auth/register.php" class="btn btn-brand">Register</a>
          </li>
        <?php endif; ?>

      </ul>
    </div>
    </div>
  </div>
</nav>
