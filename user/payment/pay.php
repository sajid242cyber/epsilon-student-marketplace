<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$transactionId = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);
$userId        = (int) $_SESSION['user_id'];

$txn = getTransactionForUser($conn, $transactionId, $userId);

// Only the buyer pays, and only once
if (!$txn || (int) $txn['buyer_id'] !== $userId) {
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}
if ($txn['payment_status'] === 'Paid') {
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
    exit;
}

// A delivery address is required before paying
$address = fetchOne($conn, "SELECT * FROM DeliveryAddress WHERE transaction_id = ?", 'i', [$transactionId]);
if (!$address) {
    $_SESSION['payment_error'] = 'Please add a delivery address before making the payment.';
    header('Location: ' . BASE_URL . '/user/delivery/address.php?transaction_id=' . $transactionId);
    exit;
}

/*
 * Cash on Delivery is the only method the marketplace offers, so this page is
 * an order confirmation rather than a payment form. The other methods still
 * exist in the Payment table's enum (older orders used them), they are simply
 * not offered here.
 */
$pageTitle = 'Confirm Order';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-7">

      <?php if (isset($_SESSION['payment_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['payment_error']); unset($_SESSION['payment_error']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <h4 class="fw-bold mb-1"><i class="bi bi-bag-check"></i> Confirm Order</h4>
        <p class="text-muted">Transaction #<?php echo $transactionId; ?></p>

        <!-- Amount summary -->
        <div class="bg-light rounded-3 p-3 mb-4">
          <div class="d-flex justify-content-between">
            <span class="text-muted"><?php echo sanitize($txn['title']); ?></span>
            <span>Tk <?php echo number_format($txn['bid_amount'], 2); ?></span>
          </div>
          <hr class="my-2">
          <div class="d-flex justify-content-between fw-bold fs-5">
            <span>Pay on delivery</span>
            <span class="text-primary">Tk <?php echo number_format($txn['bid_amount'], 2); ?></span>
          </div>
        </div>

        <!-- Delivering to -->
        <div class="mb-4 small">
          <div class="fw-semibold mb-1">Delivering to</div>
          <div class="text-muted">
            <?php echo sanitize($address['receiver_name']); ?> &middot; <?php echo sanitize($address['phone']); ?><br>
            <?php echo sanitize($address['full_address']); ?>, <?php echo sanitize($address['area']); ?>, <?php echo sanitize($address['district']); ?>
          </div>
        </div>

        <form method="POST" action="<?php echo BASE_URL; ?>/user/payment/pay_process.php">
          <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
          <input type="hidden" name="payment_method" value="Cash on Delivery">

          <label class="form-label fw-semibold">Payment Method</label>
          <!-- Only one method, so it is shown as a fixed choice rather than a list -->
          <div class="btn btn-outline-brand w-100 text-start p-3 payment-option payment-only mb-3">
            <span class="d-flex align-items-center gap-2">
              <i class="bi bi-cash-coin"></i>
              <strong class="flex-grow-1">Cash on Delivery</strong>
              <i class="bi bi-check-circle-fill payment-tick"></i>
            </span>
            <span class="small d-block payment-hint">
              Pay Tk <?php echo number_format($txn['bid_amount'], 2); ?> to the seller when you collect the item
            </span>
          </div>

          <p class="small text-muted">
            After you confirm, the seller will share a pickup point with you. Nothing is
            charged now &mdash; you pay in cash when you receive the item.
          </p>

          <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-brand">
              <i class="bi bi-bag-check"></i> Confirm Order
            </button>
            <a href="<?php echo BASE_URL; ?>/user/transaction/details.php?id=<?php echo $transactionId; ?>"
               class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
