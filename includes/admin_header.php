<?php
/**
 * Header and sidebar shared by every admin page.
 * Blocks anyone who is not logged in with the admin role.
 */
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$adminPage = basename(dirname($_SERVER['SCRIPT_NAME'])) . '/' . basename($_SERVER['SCRIPT_NAME']);
$pageTitle = isset($pageTitle) ? $pageTitle . ' - Admin' : 'Admin';

$adminMenu = [
    ['url' => '/admin/index.php',              'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
    ['url' => '/admin/users/index.php',        'icon' => 'bi-people',       'label' => 'Users'],
    ['url' => '/admin/products/index.php',     'icon' => 'bi-box-seam',     'label' => 'Products'],
    ['url' => '/admin/categories/index.php',   'icon' => 'bi-tags',         'label' => 'Categories'],
    ['url' => '/admin/reports/index.php',      'icon' => 'bi-flag',         'label' => 'Reports'],
    ['url' => '/admin/transactions/index.php', 'icon' => 'bi-receipt',      'label' => 'Transactions'],
    ['url' => '/admin/payments/index.php',     'icon' => 'bi-credit-card',  'label' => 'Payments'],
];

$currentUrl = $_SERVER['SCRIPT_NAME'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Epsilon</title>
    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo filemtime(ROOT_PATH . "/assets/css/style.css"); ?>">
</head>
<body>

<!-- Same floating rounded bar as the public site; the dark colour lives on the
     inner block so <nav> can stay transparent against the page background. -->
<nav class="cx-navbar sticky-top">
  <div class="cx-navbar-inner navbar navbar-expand-lg py-2">
  <div class="container-fluid px-3">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo BASE_URL; ?>/admin/index.php">
      <img src="<?php echo BASE_URL; ?>/assets/images/logo.svg" alt="" width="32" height="32" class="brand-logo">
      <span class="brand-word">Epsilon</span>
      <span class="badge admin-badge align-middle">Admin</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="adminNav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>/index.php" class="nav-link"><i class="bi bi-shop"></i> View Site</a>
        </li>
        <li class="nav-item">
          <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-box-arrow-right"></i> Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
  </div>
</nav>

<div class="container-fluid cx-page py-3">
  <div class="row">

    <!-- Admin sidebar -->
    <div class="col-12 col-lg-2 mb-3">
      <div class="cx-sidebar">
        <div class="cx-sidebar-card p-2">
          <div class="list-group list-group-flush">
            <?php foreach ($adminMenu as $item): ?>
              <a href="<?php echo BASE_URL . $item['url']; ?>"
                 class="list-group-item d-flex align-items-center gap-2 <?php echo $currentUrl === BASE_URL . $item['url'] ? 'active' : ''; ?>">
                <i class="bi <?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Admin page content -->
    <div class="col-12 col-lg-10">
