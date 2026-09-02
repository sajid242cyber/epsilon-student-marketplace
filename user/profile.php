<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

$user = fetchOne($conn, "SELECT * FROM User WHERE user_id = ?", 'i', [$userId]);

// Selling and buying activity for this student
$stats = fetchOne($conn, "
    SELECT
      (SELECT COUNT(*) FROM Product WHERE seller_id = ?)                          AS products_listed,
      (SELECT COUNT(*) FROM Product WHERE seller_id = ? AND status = 'Sold')      AS products_sold,
      (SELECT COUNT(*) FROM Bid WHERE buyer_id = ?)                               AS bids_placed,
      (SELECT COUNT(*) FROM Transaction WHERE buyer_id = ?)                       AS items_bought,
      (SELECT COUNT(*) FROM Wishlist WHERE user_id = ?)                           AS wishlist_items",
    'iiiii', [$userId, $userId, $userId, $userId, $userId]);

$rating = getSellerRating($conn, $userId);

// Reviews other students left for this seller
$reviewStmt = mysqli_prepare($conn, "
    SELECT r.rating, r.comment, r.created_at, u.full_name AS buyer_name, p.title
    FROM Review r
    INNER JOIN User u        ON r.buyer_id       = u.user_id
    INNER JOIN Transaction t ON r.transaction_id = t.transaction_id
    INNER JOIN Product p     ON t.product_id     = p.product_id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC");
mysqli_stmt_bind_param($reviewStmt, 'i', $userId);
mysqli_stmt_execute($reviewStmt);
$reviews = mysqli_stmt_get_result($reviewStmt);

$pageTitle = 'My Profile';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center g-3">

    <!-- Profile details -->
    <div class="col-12 col-lg-5">
      <div class="cx-sidebar-card text-center">
        <span class="badge rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center"
              style="width:80px;height:80px;font-size:2rem;">
          <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
        </span>
        <h5 class="fw-bold mt-3 mb-1"><?php echo sanitize($user['full_name']); ?></h5>
        <p class="text-muted small mb-2"><?php echo sanitize($user['department']); ?> &middot; Batch <?php echo sanitize($user['batch']); ?></p>

        <?php if ($rating['total'] > 0): ?>
          <div class="rating-stars mb-2">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="bi bi-star<?php echo $i <= round($rating['avg_rating']) ? '-fill' : ''; ?>"></i>
            <?php endfor; ?>
            <span class="text-muted small">
              <?php echo round($rating['avg_rating'], 1); ?> from <?php echo $rating['total']; ?> review<?php echo $rating['total'] > 1 ? 's' : ''; ?>
            </span>
          </div>
        <?php else: ?>
          <p class="text-muted small mb-2">No reviews yet</p>
        <?php endif; ?>

        <hr>
        <div class="text-start small">
          <div class="mb-2"><i class="bi bi-person-badge text-muted"></i> <strong>Student ID:</strong> <?php echo sanitize($user['student_id']); ?></div>
          <div class="mb-2"><i class="bi bi-envelope text-muted"></i> <strong>Email:</strong> <?php echo sanitize($user['email']); ?></div>
          <div class="mb-2"><i class="bi bi-telephone text-muted"></i> <strong>Phone:</strong> <?php echo sanitize($user['phone']); ?></div>
          <div><i class="bi bi-calendar text-muted"></i> <strong>Joined:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
        </div>
      </div>
    </div>

    <!-- Activity + reviews -->
    <div class="col-12 col-lg-7">

      <div class="cx-sidebar-card">
        <h6 class="fw-bold mb-3">My Activity</h6>
        <div class="row g-2 text-center">
          <div class="col-4">
            <div class="fs-4 fw-bold text-primary"><?php echo $stats['products_listed']; ?></div>
            <div class="small text-muted">Listed</div>
          </div>
          <div class="col-4">
            <div class="fs-4 fw-bold text-success"><?php echo $stats['products_sold']; ?></div>
            <div class="small text-muted">Sold</div>
          </div>
          <div class="col-4">
            <div class="fs-4 fw-bold text-warning"><?php echo $stats['bids_placed']; ?></div>
            <div class="small text-muted">Bids Placed</div>
          </div>
          <div class="col-6 mt-2">
            <div class="fs-4 fw-bold text-info"><?php echo $stats['items_bought']; ?></div>
            <div class="small text-muted">Items Bought</div>
          </div>
          <div class="col-6 mt-2">
            <div class="fs-4 fw-bold text-danger"><?php echo $stats['wishlist_items']; ?></div>
            <div class="small text-muted">In Wishlist</div>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <a href="<?php echo BASE_URL; ?>/user/product/my_products.php" class="btn btn-sm btn-outline-brand">My Products</a>
          <a href="<?php echo BASE_URL; ?>/user/bid/my_bids.php" class="btn btn-sm btn-outline-brand">My Bids</a>
          <a href="<?php echo BASE_URL; ?>/user/transaction/index.php" class="btn btn-sm btn-outline-brand">Transactions</a>
        </div>
      </div>

      <div class="cx-sidebar-card">
        <h6 class="fw-bold mb-3">Reviews About Me</h6>
        <?php if (mysqli_num_rows($reviews) === 0): ?>
          <p class="text-muted small mb-0">No one has reviewed you yet. Sell an item to get your first review.</p>
        <?php else: ?>
          <?php while ($rev = mysqli_fetch_assoc($reviews)): ?>
            <div class="border-bottom pb-2 mb-2">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <span class="fw-semibold"><?php echo sanitize($rev['buyer_name']); ?></span>
                  <span class="rating-stars ms-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="bi bi-star<?php echo $i <= $rev['rating'] ? '-fill' : ''; ?>"></i>
                    <?php endfor; ?>
                  </span>
                </div>
                <small class="text-muted"><?php echo time_ago($rev['created_at']); ?></small>
              </div>
              <div class="small text-muted">on <?php echo sanitize($rev['title']); ?></div>
              <?php if ($rev['comment']): ?>
                <p class="small mb-0 mt-1"><?php echo sanitize($rev['comment']); ?></p>
              <?php endif; ?>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
