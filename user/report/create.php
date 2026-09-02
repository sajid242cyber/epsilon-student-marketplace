<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$userId    = (int) $_SESSION['user_id'];

$product = fetchOne($conn, "SELECT product_id, title, seller_id FROM Product WHERE product_id = ?", 'i', [$productId]);

if (!$product) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Reporting your own listing makes no sense
if ((int) $product['seller_id'] === $userId) {
    $_SESSION['report_error'] = 'You cannot report your own product.';
    header('Location: ' . BASE_URL . '/product.php?id=' . $productId);
    exit;
}

$reasons = ['Fake Product', 'Spam', 'Scam', 'Wrong Information', 'Other'];

$pageTitle = 'Report Product';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-6">

      <?php if (isset($_SESSION['report_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['report_error']); unset($_SESSION['report_error']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <h4 class="fw-bold mb-1"><i class="bi bi-flag"></i> Report Product</h4>
        <p class="text-muted">
          Reporting <strong><?php echo sanitize($product['title']); ?></strong>.
          An admin will review this listing.
        </p>

        <form method="POST" action="<?php echo BASE_URL; ?>/user/report/create_process.php">
          <input type="hidden" name="product_id" value="<?php echo $productId; ?>">

          <div class="mb-3">
            <label for="reason" class="form-label">Reason</label>
            <select class="form-select" id="reason" name="reason" required>
              <?php foreach ($reasons as $r): ?>
                <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Details <span class="text-muted small">(optional)</span></label>
            <textarea class="form-control" id="description" name="description" rows="4"
                      placeholder="Tell us what is wrong with this listing."></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger">Submit Report</button>
            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $productId; ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
