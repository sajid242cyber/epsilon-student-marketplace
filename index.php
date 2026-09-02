<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Home';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';

/*
 * A listing stays in the feed until it is actually paid for. While a deal is
 * being completed the product sits at 'Pending', and it only drops out once
 * payment turns it into 'Sold'.
 */
$sortOptions = [
    'latest'     => 'created_at DESC',
    'price_low'  => 'price ASC',
    'price_high' => 'price DESC',
];
$sort    = isset($_GET['sort'], $sortOptions[$_GET['sort']]) ? $_GET['sort'] : 'latest';
$orderBy = $sortOptions[$sort];

$feedSql    = "SELECT * FROM vw_product_feed WHERE status <> 'Sold' ORDER BY $orderBy LIMIT 30";
$feedResult = mysqli_query($conn, $feedSql);
$feedCount  = mysqli_num_rows($feedResult);

// Line under the heading: how much is on offer and when it last changed
$summary = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS total,
            COUNT(DISTINCT category_id) AS categories,
            MAX(created_at) AS newest
     FROM Product WHERE status <> 'Sold'"));

$sortLabels = ['latest' => 'Newest', 'price_low' => 'Price &uarr;', 'price_high' => 'Price &darr;'];
?>

<div class="container-fluid cx-page py-3">
  <div class="row g-3">

    <!-- Left Sidebar -->
    <div class="col-lg-3 col-xl-2 d-none d-lg-block">
      <?php require_once __DIR__ . '/includes/sidebar_left.php'; ?>
    </div>

    <!-- Center Feed -->
    <div class="col-12 col-lg-9 col-xl-8">
      <div class="cx-feed-head">
        <div>
          <h5 class="section-title mb-0">Latest Listings</h5>
          <p class="cx-feed-sub mb-0">
            <?php echo (int) $summary['total']; ?> items in <?php echo (int) $summary['categories']; ?> categories
            <?php if ($summary['newest']): ?>
              &middot; newest <?php echo time_ago($summary['newest']); ?>
            <?php endif; ?>
          </p>
        </div>
        <div class="cx-sortrow">
          <?php foreach ($sortLabels as $key => $label): ?>
            <a href="?sort=<?php echo $key; ?>"
               class="cx-sortpill <?php echo $sort === $key ? 'active' : ''; ?>"><?php echo $label; ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="row g-3" id="product-feed">
        <?php if ($feedCount === 0): ?>
          <div class="col-12">
            <div class="empty-state">
              <i class="bi bi-box-seam" style="font-size:2.5rem;"></i>
              <p class="mt-2 mb-0">No products have been listed yet. Be the first to sell something!</p>
            </div>
          </div>
        <?php else: ?>
          <?php while ($product = mysqli_fetch_assoc($feedResult)): ?>
            <?php require __DIR__ . '/includes/product_card.php'; ?>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-xl-2 d-none d-xl-block">
      <?php require_once __DIR__ . '/includes/sidebar_right.php'; ?>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
