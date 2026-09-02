<?php
/**
 * Green "your bid was accepted" bar shown to the buyer on a product page.
 *
 * The product page includes this twice - once above the product and once
 * under the bid table - so the buyer can reach the payment page without
 * having to scroll back up. Expects $myDeal to be set by product.php.
 */
if (empty($myDeal)) {
    return;
}

$isPaid = ($myDeal['payment_status'] === 'Paid');
?>
<div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2">
  <span>
    <i class="bi bi-check-circle-fill"></i>
    <?php if ($isPaid): ?>
      <strong>You bought this item.</strong> Your invoice is ready.
    <?php else: ?>
      <strong>Your bid was accepted!</strong> Complete the payment to confirm your purchase.
    <?php endif; ?>
  </span>
  <a href="<?php echo BASE_URL; ?>/user/transaction/details.php?id=<?php echo (int) $myDeal['transaction_id']; ?>"
     class="btn btn-brand">
    <?php echo $isPaid ? 'View Order' : 'Go to Payment'; ?>
  </a>
</div>
