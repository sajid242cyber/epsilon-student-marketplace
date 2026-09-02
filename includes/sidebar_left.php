<?php
// Icons for the categories that ship with the project.
// Any category added later falls back to the generic tag icon.
$categoryIcons = [
    'Books'       => 'bi-book',
    'Laptop'      => 'bi-laptop',
    'Phone'       => 'bi-phone',
    'Calculator'  => 'bi-calculator',
    'Accessories' => 'bi-usb-plug',
    'Others'      => 'bi-three-dots',
];
$activeCategory    = $_GET['category'] ?? '';
$sidebarCategories = getCategoriesWithCounts($conn);
$totalListings     = array_sum(array_column($sidebarCategories, 'product_count'));

// Keep whatever price range the visitor already typed
$currentMin = $_GET['min_price'] ?? '';
$currentMax = $_GET['max_price'] ?? '';
?>
<div class="cx-sidebar">
  <div class="cx-sidebar-card">
    <h6 class="cx-sidebar-title">Categories</h6>
    <div class="cx-catlist">
      <a href="<?php echo BASE_URL; ?>/search.php"
         class="cx-catlink <?php echo $activeCategory === '' ? 'active' : ''; ?>">
        <i class="bi bi-grid"></i>
        <span class="cx-catname">All Categories</span>
        <span class="cx-catcount"><?php echo $totalListings; ?></span>
      </a>
      <?php foreach ($sidebarCategories as $cat): ?>
        <?php $name = $cat['category_name']; ?>
        <a href="<?php echo BASE_URL; ?>/search.php?category=<?php echo urlencode($name); ?>"
           class="cx-catlink <?php echo $activeCategory === $name ? 'active' : ''; ?>">
          <i class="bi <?php echo $categoryIcons[$name] ?? 'bi-tag'; ?>"></i>
          <span class="cx-catname"><?php echo sanitize($name); ?></span>
          <span class="cx-catcount"><?php echo $cat['product_count']; ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Price range. Submits to the same search page as the category links, and
       carries the current keyword / category along so filters stack up. -->
  <div class="cx-sidebar-card">
    <h6 class="cx-sidebar-title">Price</h6>
    <form method="GET" action="<?php echo BASE_URL; ?>/search.php" class="cx-pricebox">
      <?php if (!empty($_GET['q'])): ?>
        <input type="hidden" name="q" value="<?php echo sanitize($_GET['q']); ?>">
      <?php endif; ?>
      <?php if ($activeCategory !== ''): ?>
        <input type="hidden" name="category" value="<?php echo sanitize($activeCategory); ?>">
      <?php endif; ?>

      <div class="cx-priceinput">
        <span class="cx-currency">&#2547;</span>
        <input type="number" name="min_price" min="0" step="1" placeholder="min"
               value="<?php echo sanitize((string) $currentMin); ?>" aria-label="Minimum price">
      </div>
      <div class="cx-priceinput">
        <span class="cx-currency">&#2547;</span>
        <input type="number" name="max_price" min="0" step="1" placeholder="max"
               value="<?php echo sanitize((string) $currentMax); ?>" aria-label="Maximum price">
      </div>
      <button type="submit" class="cx-priceapply">Apply</button>
    </form>
  </div>

  <?php if (isLoggedIn()): ?>
  <div class="cx-sidebar-card">
    <h6 class="cx-sidebar-title">My Account</h6>
    <div class="cx-catlist">
      <a href="<?php echo BASE_URL; ?>/user/product/my_products.php" class="cx-catlink">
        <i class="bi bi-box-seam"></i><span class="cx-catname">My Products</span>
      </a>
      <a href="<?php echo BASE_URL; ?>/user/wishlist/index.php" class="cx-catlink">
        <i class="bi bi-heart"></i><span class="cx-catname">Wishlist</span>
      </a>
      <a href="<?php echo BASE_URL; ?>/user/transaction/index.php" class="cx-catlink">
        <i class="bi bi-receipt"></i><span class="cx-catname">Transactions</span>
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>
