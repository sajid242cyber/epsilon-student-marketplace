<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$transactionId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId        = (int) $_SESSION['user_id'];

// Only the buyer or the seller of this transaction may open the page
$txn = getTransactionForUser($conn, $transactionId, $userId);
if (!$txn) {
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

$isBuyer  = ((int) $txn['buyer_id'] === $userId);
$isSeller = ((int) $txn['seller_id'] === $userId);

// Related records (any of these may not exist yet)
$address  = fetchOne($conn, "SELECT * FROM DeliveryAddress WHERE transaction_id = ?", 'i', [$transactionId]);
$delivery = fetchOne($conn, "SELECT * FROM Delivery WHERE transaction_id = ?", 'i', [$transactionId]);
$invoice  = fetchOne($conn, "SELECT * FROM Invoice WHERE transaction_id = ?", 'i', [$transactionId]);
$review   = fetchOne($conn, "SELECT * FROM Review WHERE transaction_id = ?", 'i', [$transactionId]);

$isPaid = ($txn['payment_status'] === 'Paid');

$payColors      = ['Pending' => 'warning', 'Paid' => 'success', 'Failed' => 'danger'];
$deliveryColors = ['Pending' => 'secondary', 'Packed' => 'info', 'Shipped' => 'primary', 'Delivered' => 'success'];
$txnColors      = ['Pending' => 'warning', 'Completed' => 'success', 'Cancelled' => 'secondary'];

$pageTitle = 'Transaction #' . $transactionId;
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-10">

      <?php foreach (['txn_success' => 'success', 'payment_success' => 'success', 'delivery_success' => 'success',
                      'txn_error' => 'danger', 'payment_error' => 'danger', 'delivery_error' => 'danger'] as $key => $type): ?>
        <?php if (isset($_SESSION[$key])): ?>
          <div class="alert alert-<?php echo $type; ?> alert-auto-dismiss"><?php echo sanitize($_SESSION[$key]); unset($_SESSION[$key]); ?></div>
        <?php endif; ?>
      <?php endforeach; ?>

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold mb-0">Transaction #<?php echo $transactionId; ?></h4>
        <span class="badge bg-<?php echo $txnColors[$txn['status']]; ?> fs-6"><?php echo $txn['status']; ?></span>
      </div>

      <div class="row g-3">

        <!-- Order Summary -->
        <div class="col-12 col-lg-7">
          <div class="cx-sidebar-card">
            <h6 class="fw-bold mb-3">Order Summary</h6>
            <div class="d-flex gap-3">
              <img src="<?php echo $txn['primary_image'] ? UPLOAD_URL . '/' . $txn['primary_image'] : BASE_URL . '/assets/images/no-image.svg'; ?>"
                   style="width:90px;height:90px;object-fit:cover;" class="rounded border" alt="">
              <div class="flex-grow-1">
                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $txn['product_id']; ?>" class="fw-semibold">
                  <?php echo sanitize($txn['title']); ?>
                </a>
                <div class="text-muted small"><?php echo sanitize($txn['category_name']); ?> &middot; <?php echo $txn['condition']; ?></div>
                <div class="text-muted small">Asking price: Tk <?php echo number_format($txn['price'], 2); ?></div>
                <div class="fs-5 fw-bold text-primary mt-1">Accepted Bid: Tk <?php echo number_format($txn['bid_amount'], 2); ?></div>
              </div>
            </div>
            <hr>
            <div class="row small">
              <div class="col-6">
                <div class="text-muted">Buyer</div>
                <div class="fw-semibold"><?php echo sanitize($txn['buyer_name']); ?></div>
                <div class="text-muted"><?php echo sanitize($txn['buyer_phone']); ?></div>
              </div>
              <div class="col-6">
                <div class="text-muted">Seller</div>
                <div class="fw-semibold"><?php echo sanitize($txn['seller_name']); ?></div>
                <div class="text-muted"><?php echo sanitize($txn['seller_phone']); ?></div>
              </div>
            </div>
            <hr>
            <div class="small text-muted">
              <i class="bi bi-calendar"></i> Created <?php echo date('d M Y, h:i A', strtotime($txn['transaction_date'])); ?>
            </div>
          </div>
        </div>

        <!-- Progress / Actions -->
        <div class="col-12 col-lg-5">

          <!-- Payment -->
          <div class="cx-sidebar-card">
            <h6 class="fw-bold mb-2"><i class="bi bi-credit-card"></i> Payment</h6>
            <?php if ($txn['payment_status']): ?>
              <p class="mb-1">
                Status:
                <span class="badge bg-<?php echo $payColors[$txn['payment_status']]; ?>"><?php echo $txn['payment_status']; ?></span>
              </p>
              <p class="small text-muted mb-1">Method: <?php echo sanitize($txn['payment_method']); ?></p>
              <?php if (!$isPaid): ?>
                <!-- Cash on Delivery: money changes hands at handover, not now -->
                <p class="small mb-0">
                  <i class="bi bi-cash-coin"></i>
                  Pay <strong>Tk <?php echo number_format($txn['bid_amount'], 2); ?></strong> in cash
                  <?php echo $isBuyer ? 'when you collect the item.' : 'is collected on delivery.'; ?>
                </p>
              <?php endif; ?>
            <?php elseif ($isBuyer): ?>
              <p class="small text-muted">Confirm your order &mdash; you pay in cash on delivery.</p>
              <a href="<?php echo BASE_URL; ?>/user/payment/pay.php?transaction_id=<?php echo $transactionId; ?>"
                 class="btn btn-brand w-100">Confirm Order</a>
            <?php else: ?>
              <p class="small text-muted mb-0">Waiting for the buyer to confirm the order.</p>
            <?php endif; ?>
          </div>

          <?php
            // Once the seller names a pickup point the buyer collects the item,
            // so their own address is no longer the place it is going.
            $hasPickup = !empty($delivery['pickup_address']);
          ?>
          <div class="cx-sidebar-card">
            <h6 class="fw-bold mb-2">
              <i class="bi bi-geo-alt"></i>
              <?php echo $hasPickup ? 'Buyer\'s Address' : 'Delivery Address'; ?>
            </h6>
            <?php if ($address): ?>
              <div class="small">
                <div class="fw-semibold"><?php echo sanitize($address['receiver_name']); ?></div>
                <div><?php echo sanitize($address['phone']); ?></div>
                <div class="text-muted">
                  <?php echo sanitize($address['full_address']); ?>,
                  <?php echo sanitize($address['area']); ?>, <?php echo sanitize($address['district']); ?>
                </div>
              </div>
              <?php if ($hasPickup): ?>
                <p class="small text-muted mt-2 mb-0">
                  <i class="bi bi-info-circle"></i>
                  Kept on record only &mdash; this order is being collected in person.
                </p>
              <?php endif; ?>
              <?php if ($isBuyer && !$isPaid): ?>
                <a href="<?php echo BASE_URL; ?>/user/delivery/address.php?transaction_id=<?php echo $transactionId; ?>"
                   class="btn btn-sm btn-outline-brand mt-2">Edit Address</a>
              <?php endif; ?>
            <?php elseif ($isBuyer): ?>
              <p class="small text-muted">Add where you want this item delivered.</p>
              <a href="<?php echo BASE_URL; ?>/user/delivery/address.php?transaction_id=<?php echo $transactionId; ?>"
                 class="btn btn-outline-brand w-100">Add Delivery Address</a>
            <?php else: ?>
              <p class="small text-muted mb-0">The buyer has not added an address yet.</p>
            <?php endif; ?>
          </div>

          <!-- Pickup point: the seller says where the buyer can collect the item -->
          <?php if ($delivery): ?>
            <div class="cx-sidebar-card">
              <h6 class="fw-bold mb-2"><i class="bi bi-shop"></i> Collect From</h6>
              <?php if (!empty($delivery['pickup_address'])): ?>
                <div class="small">
                  <div class="fw-semibold"><?php echo sanitize($txn['seller_name']); ?></div>
                  <div><?php echo sanitize($txn['seller_phone']); ?></div>
                  <div class="text-muted" style="white-space:pre-line;"><?php echo sanitize($delivery['pickup_address']); ?></div>
                </div>
              <?php elseif ($isBuyer): ?>
                <p class="small text-muted mb-0">
                  <i class="bi bi-hourglass-split"></i>
                  Waiting for the seller to share a pickup point.
                </p>
              <?php else: ?>
                <p class="small text-muted mb-0">
                  Tell the buyer where to collect the item using the form below.
                </p>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Delivery Tracking -->
          <?php if ($delivery): ?>
            <div class="cx-sidebar-card">
              <h6 class="fw-bold mb-2"><i class="bi bi-truck"></i> Delivery</h6>
              <p class="mb-1">
                Status:
                <span class="badge bg-<?php echo $deliveryColors[$delivery['delivery_status']]; ?>"><?php echo $delivery['delivery_status']; ?></span>
              </p>
              <?php if ($delivery['tracking_number']): ?>
                <p class="small text-muted mb-2">Tracking: <code><?php echo sanitize($delivery['tracking_number']); ?></code></p>
              <?php endif; ?>

              <?php if ($isSeller && $delivery['delivery_status'] !== 'Delivered'): ?>
                <form method="POST" action="<?php echo BASE_URL; ?>/user/delivery/update_process.php" class="mt-2">
                  <input type="hidden" name="transaction_id" value="<?php echo $transactionId; ?>">
                  <div class="mb-2">
                    <label class="form-label small fw-semibold">Update Status</label>
                    <select name="delivery_status" class="form-select form-select-sm" required>
                      <?php foreach (['Pending', 'Packed', 'Shipped', 'Delivered'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $delivery['delivery_status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-2">
                    <label class="form-label small fw-semibold">Pickup Point</label>
                    <textarea name="pickup_address" rows="3" maxlength="255"
                              class="form-control form-control-sm"
                              placeholder="Where should the buyer collect it?"><?php echo sanitize($delivery['pickup_address'] ?? ''); ?></textarea>
                    <div class="form-text small">The buyer sees this straight away.</div>
                  </div>
                  <div class="mb-2">
                    <label class="form-label small fw-semibold">Tracking Number</label>
                    <input type="text" name="tracking_number" class="form-control form-control-sm"
                           value="<?php echo sanitize($delivery['tracking_number'] ?? ''); ?>" placeholder="Optional">
                  </div>
                  <button type="submit" class="btn btn-sm btn-brand w-100">Update Delivery</button>
                  <p class="form-text small mb-0">
                    Marking it <strong>Delivered</strong> records the cash as received
                    and creates the invoice.
                  </p>
                </form>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- Invoice -->
          <?php if ($invoice): ?>
            <div class="cx-sidebar-card">
              <h6 class="fw-bold mb-2"><i class="bi bi-file-earmark-text"></i> Invoice</h6>
              <p class="small mb-2">Invoice No: <code><?php echo sanitize($invoice['invoice_number']); ?></code></p>
              <a href="<?php echo BASE_URL; ?>/user/invoice/view.php?transaction_id=<?php echo $transactionId; ?>"
                 class="btn btn-outline-brand w-100">View / Print Invoice</a>
            </div>
          <?php endif; ?>

          <!-- Review -->
          <?php if ($isBuyer && $delivery && $delivery['delivery_status'] === 'Delivered'): ?>
            <div class="cx-sidebar-card">
              <h6 class="fw-bold mb-2"><i class="bi bi-star"></i> Review</h6>
              <?php if ($review): ?>
                <div class="rating-stars mb-1">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                  <?php endfor; ?>
                </div>
                <p class="small text-muted mb-0"><?php echo sanitize($review['comment']); ?></p>
              <?php else: ?>
                <p class="small text-muted">How was your experience with this seller?</p>
                <a href="<?php echo BASE_URL; ?>/user/review/create.php?transaction_id=<?php echo $transactionId; ?>"
                   class="btn btn-brand w-100">Write a Review</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>

      <a href="<?php echo BASE_URL; ?>/user/transaction/index.php" class="btn btn-outline-secondary mt-3">
        <i class="bi bi-arrow-left"></i> Back to Transactions
      </a>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
