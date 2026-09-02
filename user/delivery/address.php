<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$transactionId = filter_input(INPUT_GET, 'transaction_id', FILTER_VALIDATE_INT);
$userId        = (int) $_SESSION['user_id'];

$txn = getTransactionForUser($conn, $transactionId, $userId);

// Only the buyer sets the delivery address
if (!$txn || (int) $txn['buyer_id'] !== $userId) {
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

// Once paid the address is locked so the seller always ships to the agreed place
if ($txn['payment_status'] === 'Paid') {
    $_SESSION['delivery_error'] = 'The delivery address cannot be changed after payment.';
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
    exit;
}

$address = fetchOne($conn, "SELECT * FROM DeliveryAddress WHERE transaction_id = ?", 'i', [$transactionId]);

$pageTitle = 'Delivery Address';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-7">

      <?php if (isset($_SESSION['delivery_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['delivery_error']); unset($_SESSION['delivery_error']); ?></div>
      <?php endif; ?>
      <?php if (isset($_SESSION['payment_error'])): ?>
        <div class="alert alert-warning alert-auto-dismiss"><?php echo sanitize($_SESSION['payment_error']); unset($_SESSION['payment_error']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <h4 class="fw-bold mb-1"><i class="bi bi-geo-alt"></i> Delivery Address</h4>
        <p class="text-muted">Where should <strong><?php echo sanitize($txn['title']); ?></strong> be delivered?</p>

        <form method="POST" action="<?php echo BASE_URL; ?>/user/delivery/address_process.php">
          <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label for="receiver_name" class="form-label">Receiver Name</label>
              <input type="text" class="form-control" id="receiver_name" name="receiver_name" required
                     value="<?php echo sanitize($address['receiver_name'] ?? $_SESSION['full_name']); ?>">
            </div>
            <div class="col-md-6">
              <label for="phone" class="form-label">Phone Number</label>
              <input type="tel" class="form-control" id="phone" name="phone" required
                     value="<?php echo sanitize($address['phone'] ?? $txn['buyer_phone']); ?>">
            </div>
            <div class="col-md-6">
              <label for="district" class="form-label">District</label>
              <input type="text" class="form-control" id="district" name="district" required
                     value="<?php echo sanitize($address['district'] ?? ''); ?>" placeholder="e.g. Dhaka">
            </div>
            <div class="col-md-6">
              <label for="area" class="form-label">Area</label>
              <input type="text" class="form-control" id="area" name="area" required
                     value="<?php echo sanitize($address['area'] ?? ''); ?>" placeholder="e.g. Mirpur">
            </div>
            <div class="col-12">
              <label for="full_address" class="form-label">Full Address</label>
              <textarea class="form-control" id="full_address" name="full_address" rows="3" required
                        placeholder="House / Road / Hall / Room number"><?php echo sanitize($address['full_address'] ?? ''); ?></textarea>
            </div>
          </div>

          <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-brand">Save Address</button>
            <a href="<?php echo BASE_URL; ?>/user/transaction/details.php?id=<?php echo $transactionId; ?>"
               class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
