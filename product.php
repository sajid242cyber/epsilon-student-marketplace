<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$productId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Main product info (seller, category, rating, primary image already joined in the view)
$stmt = mysqli_prepare($conn, "SELECT * FROM vw_product_feed WHERE product_id = ?");
mysqli_stmt_bind_param($stmt, 'i', $productId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// All images for the gallery
$imgStmt = mysqli_prepare($conn, "SELECT image_path FROM ProductImage WHERE product_id = ? ORDER BY image_id ASC");
mysqli_stmt_bind_param($imgStmt, 'i', $productId);
mysqli_stmt_execute($imgStmt);
$images = mysqli_stmt_get_result($imgStmt);
$imageList = [];
while ($row = mysqli_fetch_assoc($images)) {
    $imageList[] = $row['image_path'];
}
if (empty($imageList)) {
    $imageList[] = null; // triggers the placeholder image below
}

// Existing bids on this product (visible to everyone, like a public auction).
// COALESCE gives the price currently on the table: the seller's counter offer
// if there is one, otherwise the buyer's original bid.
$bidStmt = mysqli_prepare($conn, "SELECT b.bid_id, b.bid_amount, b.counter_amount, b.status, b.created_at,
                                          COALESCE(b.counter_amount, b.bid_amount) AS current_amount,
                                          u.full_name, u.user_id AS buyer_id
                                   FROM Bid b INNER JOIN User u ON b.buyer_id = u.user_id
                                   WHERE b.product_id = ? ORDER BY b.bid_amount DESC");
mysqli_stmt_bind_param($bidStmt, 'i', $productId);
mysqli_stmt_execute($bidStmt);
$bids = mysqli_stmt_get_result($bidStmt);

$isOwner = isLoggedIn() && (int) $_SESSION['user_id'] === (int) $product['seller_id'];

/*
 * If the logged-in student won this product, show them a direct link to the
 * payment page. Without this the buyer has to hunt through My Bids after the
 * seller accepts.
 */
$myDeal = null;
if (isLoggedIn()) {
    $myDeal = fetchOne($conn,
        "SELECT t.transaction_id, pay.payment_status
         FROM Transaction t
         LEFT JOIN Payment pay ON pay.transaction_id = t.transaction_id
         WHERE t.product_id = ? AND t.buyer_id = ?",
        'ii', [$productId, (int) $_SESSION['user_id']]);
}

$conditionColors = [
    'New' => 'success', 'Like New' => 'primary', 'Good' => 'info', 'Fair' => 'warning', 'Poor' => 'secondary',
];
$statusColors = ['Available' => 'success', 'Pending' => 'warning', 'Sold' => 'secondary'];

$pageTitle = $product['title'];
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container-fluid px-3 px-lg-4 py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">

      <?php if (isset($_SESSION['bid_success'])): ?>
        <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['bid_success']); unset($_SESSION['bid_success']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['bid_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['bid_error']); unset($_SESSION['bid_error']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['report_success'])): ?>
        <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['report_success']); unset($_SESSION['report_success']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['report_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['report_error']); unset($_SESSION['report_error']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['wishlist_success'])): ?>
        <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['wishlist_success']); unset($_SESSION['wishlist_success']); ?></div>
      <?php endif; ?>

      <!-- Shown above the product; repeated under the bid table further down -->
      <?php require __DIR__ . '/includes/deal_banner.php'; ?>

      <div class="row g-4">

        <!-- Image Gallery -->
        <div class="col-12 col-md-6">
          <div id="productCarousel" class="carousel slide border rounded-3 overflow-hidden bg-white" data-bs-ride="false">
            <div class="carousel-inner">
              <?php foreach ($imageList as $i => $img): ?>
                <div class="carousel-item <?php echo $i === 0 ? 'active' : ''; ?>">
                  <img src="<?php echo $img ? UPLOAD_URL . '/' . $img : BASE_URL . '/assets/images/no-image.svg'; ?>"
                       class="d-block w-100" style="aspect-ratio:1/1;object-fit:cover;" alt="Product image">
                </div>
              <?php endforeach; ?>
            </div>
            <?php if (count($imageList) > 1): ?>
              <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon"></span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon"></span>
              </button>
            <?php endif; ?>
          </div>
        </div>

        <!-- Details -->
        <div class="col-12 col-md-6">
          <div class="d-flex justify-content-between align-items-start">
            <h3 class="fw-bold mb-1"><?php echo sanitize($product['title']); ?></h3>
            <span class="badge bg-<?php echo $statusColors[$product['status']]; ?>"><?php echo $product['status']; ?></span>
          </div>
          <div class="fs-3 fw-bold text-primary mb-2">Tk <?php echo number_format($product['price'], 2); ?></div>

          <div class="mb-3">
            <span class="badge bg-<?php echo $conditionColors[$product['condition']]; ?>"><?php echo $product['condition']; ?></span>
            <span class="badge bg-light text-dark border"><?php echo sanitize($product['category_name']); ?></span>
          </div>

          <p class="text-muted"><i class="bi bi-clock"></i> Posted <?php echo time_ago($product['created_at']); ?></p>

          <h6 class="fw-bold mt-4">Description</h6>
          <p style="white-space:pre-line;"><?php echo sanitize($product['description']); ?></p>

          <!-- Seller Info -->
          <div class="cx-sidebar-card">
            <h6 class="fw-bold mb-2">Seller Information</h6>
            <div class="d-flex align-items-center gap-2">
              <span class="badge rounded-circle bg-secondary" style="width:40px;height:40px;line-height:26px;">
                <?php echo strtoupper(substr($product['seller_name'], 0, 1)); ?>
              </span>
              <div>
                <div class="fw-semibold"><?php echo sanitize($product['seller_name']); ?></div>
                <div class="rating-stars">
                  <?php if ($product['seller_rating']): ?>
                    <i class="bi bi-star-fill"></i> <?php echo $product['seller_rating']; ?> / 5
                  <?php else: ?>
                    <span class="text-muted">No ratings yet</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-flex flex-wrap gap-2 mt-3">
            <?php if ($isOwner): ?>
              <a href="<?php echo BASE_URL; ?>/user/product/edit.php?id=<?php echo $productId; ?>" class="btn btn-outline-brand">
                <i class="bi bi-pencil"></i> Edit
              </a>
              <a href="<?php echo BASE_URL; ?>/user/product/delete_process.php?id=<?php echo $productId; ?>"
                 class="btn btn-outline-danger"
                 onclick="return confirm('Delete this product? This cannot be undone.');">
                <i class="bi bi-trash"></i> Delete
              </a>
            <?php else: ?>
              <a href="<?php echo isLoggedIn() ? BASE_URL . '/user/wishlist/toggle.php?product_id=' . $productId : BASE_URL . '/auth/login.php'; ?>"
                 class="btn btn-outline-secondary">
                <i class="bi bi-heart"></i> Wishlist
              </a>
              <a href="<?php echo isLoggedIn() ? BASE_URL . '/user/report/create.php?product_id=' . $productId : BASE_URL . '/auth/login.php'; ?>"
                 class="btn btn-outline-secondary">
                <i class="bi bi-flag"></i> Report
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Bidding Section -->
      <!-- Full width: the bid table carries the counter-offer controls too -->
      <div class="row mt-4" id="bid">
        <div class="col-12">
          <h5 class="section-title">Bids (<?php echo mysqli_num_rows($bids); ?>)</h5>

          <?php if (!$isOwner && isLoggedIn() && $product['status'] === 'Available'): ?>
            <form method="POST" action="<?php echo BASE_URL; ?>/user/bid/place_process.php" class="d-flex gap-2 mb-3">
              <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
              <div class="input-group" style="max-width:260px;">
                <span class="input-group-text">Tk</span>
                <input type="number" step="0.01" min="1" name="bid_amount" class="form-control" placeholder="Your offer" required>
              </div>
              <button type="submit" class="btn btn-brand">Place Bid</button>
            </form>
          <?php elseif (!isLoggedIn()): ?>
            <!-- Guests get a real button here, not a text link, so the action is obvious -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
              <a href="<?php echo BASE_URL; ?>/auth/login.php" class="btn btn-brand">
                <i class="bi bi-box-arrow-in-right"></i> Login to Place a Bid
              </a>
              <span class="text-muted small">
                New here?
                <a href="<?php echo BASE_URL; ?>/auth/register.php" class="text-decoration-underline">Create an account</a>
              </span>
            </div>
          <?php endif; ?>

          <?php if (mysqli_num_rows($bids) === 0): ?>
            <p class="text-muted">No bids yet. Be the first to make an offer!</p>
          <?php else: ?>
            <?php
              $statusColors = [
                  'Pending'   => 'warning',
                  'Countered' => 'info',
                  'Accepted'  => 'success',
                  'Rejected'  => 'danger',
              ];
              // The action column is useful to the seller and to the buyer
              // whose own bid has been countered, so show it to any member.
              $showActions = isLoggedIn();
            ?>
            <div class="table-responsive">
              <table class="table align-middle">
                <thead>
                  <tr>
                    <th>Buyer</th><th>Bid</th><th>Counter Offer</th><th>Status</th><th>Placed</th>
                    <?php if ($showActions): ?><th>Action</th><?php endif; ?>
                  </tr>
                </thead>
                <tbody>
                <?php while ($bid = mysqli_fetch_assoc($bids)):
                    $isMyBid  = isLoggedIn() && (int) $bid['buyer_id'] === (int) $_SESSION['user_id'];
                    $canTrade = ($product['status'] === 'Available');
                ?>
                  <tr>
                    <td>
                      <?php echo sanitize($bid['full_name']); ?>
                      <?php if ($isMyBid): ?><span class="badge bg-light text-dark border ms-1">You</span><?php endif; ?>
                    </td>
                    <td>Tk <?php echo number_format($bid['bid_amount'], 2); ?></td>
                    <td>
                      <?php if ($bid['counter_amount'] !== null): ?>
                        <span class="fw-semibold">Tk <?php echo number_format($bid['counter_amount'], 2); ?></span>
                      <?php else: ?>
                        <span class="text-muted">&mdash;</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge bg-<?php echo $statusColors[$bid['status']] ?? 'secondary'; ?>">
                        <?php echo $bid['status']; ?>
                      </span>
                    </td>
                    <td class="small text-muted"><?php echo time_ago($bid['created_at']); ?></td>

                    <?php if ($showActions): ?>
                      <td>
                        <?php if ($isOwner && $bid['status'] === 'Pending' && $canTrade): ?>
                          <!-- Seller: accept, counter with a different price, or reject -->
                          <div class="d-flex flex-wrap gap-1 mb-1">
                            <a href="<?php echo BASE_URL; ?>/user/bid/accept.php?bid_id=<?php echo $bid['bid_id']; ?>"
                               class="btn btn-sm btn-success"
                               onclick="return confirm('Accept this bid? All other open bids will be rejected.');">Accept</a>
                            <a href="<?php echo BASE_URL; ?>/user/bid/reject.php?bid_id=<?php echo $bid['bid_id']; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Reject this bid?');">Reject</a>
                          </div>
                          <form method="POST" action="<?php echo BASE_URL; ?>/user/bid/counter.php"
                                class="input-group input-group-sm" style="min-width:220px;">
                            <input type="hidden" name="bid_id" value="<?php echo $bid['bid_id']; ?>">
                            <span class="input-group-text">Tk</span>
                            <input type="number" step="0.01" min="1" name="counter_amount"
                                   class="form-control" placeholder="Ask for" required>
                            <button type="submit" class="btn btn-brand">Counter</button>
                          </form>

                        <?php elseif ($isOwner && $bid['status'] === 'Countered'): ?>
                          <span class="text-muted small">Waiting for the buyer to reply</span>

                        <?php elseif ($isMyBid && $bid['status'] === 'Countered' && $canTrade): ?>
                          <!-- Buyer: the seller wants a different price -->
                          <div class="d-flex flex-wrap gap-1">
                            <a href="<?php echo BASE_URL; ?>/user/bid/accept.php?bid_id=<?php echo $bid['bid_id']; ?>"
                               class="btn btn-sm btn-success"
                               onclick="return confirm('Accept the seller\'s counter offer of Tk <?php echo number_format($bid['counter_amount'], 2); ?>?');">
                              Accept Offer
                            </a>
                            <a href="<?php echo BASE_URL; ?>/user/bid/reject.php?bid_id=<?php echo $bid['bid_id']; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Turn down this counter offer?');">Decline</a>
                          </div>

                        <?php else: ?>
                          <span class="text-muted small">&mdash;</span>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>

          <!-- Same bar again, so the buyer does not have to scroll back up -->
          <div class="mt-3">
            <?php require __DIR__ . '/includes/deal_banner.php'; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
