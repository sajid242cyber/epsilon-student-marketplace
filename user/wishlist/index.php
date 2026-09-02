<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

// Saved products, newest saved first
$sql = "SELECT v.*, w.created_at AS saved_at
        FROM Wishlist w
        INNER JOIN vw_product_feed v ON w.product_id = v.product_id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$items = mysqli_stmt_get_result($stmt);

$pageTitle = 'My Wishlist';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <h4 class="fw-bold mb-3"><i class="bi bi-heart"></i> My Wishlist</h4>

  <?php if (isset($_SESSION['wishlist_success'])): ?>
    <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['wishlist_success']); unset($_SESSION['wishlist_success']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['wishlist_error'])): ?>
    <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['wishlist_error']); unset($_SESSION['wishlist_error']); ?></div>
  <?php endif; ?>

  <?php if (mysqli_num_rows($items) === 0): ?>
    <div class="empty-state bg-white rounded-3 border">
      <i class="bi bi-heart" style="font-size:2.5rem;"></i>
      <p class="mt-2">Your wishlist is empty.</p>
      <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-brand">Browse Products</a>
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php while ($product = mysqli_fetch_assoc($items)): ?>
        <div class="col-12 col-sm-6 col-lg-4">
          <div class="product-card">
            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['product_id']; ?>" class="product-img-wrap d-block">
              <img src="<?php echo $product['primary_image'] ? UPLOAD_URL . '/' . $product['primary_image'] : BASE_URL . '/assets/images/no-image.svg'; ?>"
                   alt="<?php echo sanitize($product['title']); ?>" loading="lazy">
            </a>
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span class="product-price">Tk <?php echo number_format($product['price'], 2); ?></span>
                <span class="badge bg-<?php echo $product['status'] === 'Available' ? 'success' : 'secondary'; ?> condition-badge">
                  <?php echo $product['status']; ?>
                </span>
              </div>
              <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['product_id']; ?>" class="product-title fw-semibold d-block mb-1">
                <?php echo sanitize($product['title']); ?>
              </a>
              <div class="product-meta"><i class="bi bi-person-circle"></i> <?php echo sanitize($product['seller_name']); ?></div>
              <div class="product-meta"><i class="bi bi-bookmark"></i> Saved <?php echo time_ago($product['saved_at']); ?></div>
            </div>
            <div class="card-footer d-flex gap-2">
              <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-outline-brand flex-grow-1">View Details</a>
              <a href="<?php echo BASE_URL; ?>/user/wishlist/toggle.php?product_id=<?php echo $product['product_id']; ?>"
                 class="btn btn-sm btn-outline-danger" title="Remove from wishlist">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
