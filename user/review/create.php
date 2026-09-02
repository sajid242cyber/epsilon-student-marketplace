<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$transactionId = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);
$userId        = (int) $_SESSION['user_id'];

$txn = getTransactionForUser($conn, $transactionId, $userId);

// Only the buyer may review, and only their own purchase
if (!$txn || (int) $txn['buyer_id'] !== $userId) {
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

// Reviews are only allowed once the item has actually been delivered
$delivery = fetchOne($conn, "SELECT delivery_status FROM Delivery WHERE transaction_id = ?", 'i', [$transactionId]);
if (!$delivery || $delivery['delivery_status'] !== 'Delivered') {
    $_SESSION['review_error'] = 'You can only review a seller after the item has been delivered.';
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
    exit;
}

// One review per transaction
$existing = fetchOne($conn, "SELECT review_id FROM Review WHERE transaction_id = ?", 'i', [$transactionId]);
if ($existing) {
    $_SESSION['review_error'] = 'You have already reviewed this transaction.';
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
    exit;
}

$pageTitle = 'Write a Review';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-6">

      <?php if (isset($_SESSION['review_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['review_error']); unset($_SESSION['review_error']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <h4 class="fw-bold mb-1"><i class="bi bi-star"></i> Write a Review</h4>
        <p class="text-muted">
          How was buying <strong><?php echo sanitize($txn['title']); ?></strong>
          from <strong><?php echo sanitize($txn['seller_name']); ?></strong>?
        </p>

        <form method="POST" action="<?php echo BASE_URL; ?>/user/review/create_process.php">
          <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">

          <div class="mb-3">
            <label class="form-label">Your Rating</label>
            <div class="d-flex gap-2">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" class="btn-check" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                <label class="btn btn-outline-brand" for="star<?php echo $i; ?>">
                  <?php echo $i; ?> <i class="bi bi-star-fill"></i>
                </label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="comment" class="form-label">Your Review <span class="text-muted small">(optional)</span></label>
            <textarea class="form-control" id="comment" name="comment" rows="4"
                      placeholder="Was the item as described? Was the seller easy to deal with?"></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand">Submit Review</button>
            <a href="<?php echo BASE_URL; ?>/user/transaction/details.php?id=<?php echo $transactionId; ?>"
               class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
