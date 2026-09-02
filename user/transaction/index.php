<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

// Every transaction where this student is either the buyer or the seller
$sql = "SELECT t.transaction_id, t.status, t.transaction_date,
               p.title, COALESCE(b.counter_amount, b.bid_amount) AS bid_amount,
               buyer.full_name  AS buyer_name,
               seller.full_name AS seller_name,
               t.buyer_id, t.seller_id,
               pay.payment_status,
               d.delivery_status
        FROM Transaction t
        INNER JOIN Product p    ON t.product_id = p.product_id
        INNER JOIN Bid b        ON t.bid_id     = b.bid_id
        INNER JOIN User buyer   ON t.buyer_id   = buyer.user_id
        INNER JOIN User seller  ON t.seller_id  = seller.user_id
        LEFT JOIN Payment pay   ON t.transaction_id = pay.transaction_id
        LEFT JOIN Delivery d    ON t.transaction_id = d.transaction_id
        WHERE t.buyer_id = ? OR t.seller_id = ?
        ORDER BY t.transaction_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $userId, $userId);
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);

$txnColors     = ['Pending' => 'warning', 'Completed' => 'success', 'Cancelled' => 'secondary'];
$payColors     = ['Pending' => 'warning', 'Paid' => 'success', 'Failed' => 'danger'];
$deliveryColors = ['Pending' => 'secondary', 'Packed' => 'info', 'Shipped' => 'primary', 'Delivered' => 'success'];

$pageTitle = 'My Transactions';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <h4 class="fw-bold mb-3"><i class="bi bi-receipt"></i> My Transactions</h4>

  <?php if (isset($_SESSION['txn_success'])): ?>
    <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['txn_success']); unset($_SESSION['txn_success']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['txn_error'])): ?>
    <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['txn_error']); unset($_SESSION['txn_error']); ?></div>
  <?php endif; ?>

  <?php if (mysqli_num_rows($transactions) === 0): ?>
    <div class="empty-state bg-white rounded-3 border">
      <i class="bi bi-receipt" style="font-size:2.5rem;"></i>
      <p class="mt-2 mb-0">You don't have any transactions yet.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive bg-white rounded-3 border">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>Product</th><th>Role</th><th>Other Party</th><th>Amount</th>
              <th>Transaction</th><th>Payment</th><th>Delivery</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($t = mysqli_fetch_assoc($transactions)):
            $isBuyer = ((int) $t['buyer_id'] === $userId);
        ?>
          <tr>
            <td class="text-muted">#<?php echo $t['transaction_id']; ?></td>
            <td class="fw-semibold"><?php echo sanitize($t['title']); ?></td>
            <td><span class="badge bg-<?php echo $isBuyer ? 'primary' : 'dark'; ?>"><?php echo $isBuyer ? 'Buyer' : 'Seller'; ?></span></td>
            <td><?php echo sanitize($isBuyer ? $t['seller_name'] : $t['buyer_name']); ?></td>
            <td>Tk <?php echo number_format($t['bid_amount'], 2); ?></td>
            <td><span class="badge bg-<?php echo $txnColors[$t['status']]; ?>"><?php echo $t['status']; ?></span></td>
            <td>
              <?php if ($t['payment_status']): ?>
                <span class="badge bg-<?php echo $payColors[$t['payment_status']]; ?>"><?php echo $t['payment_status']; ?></span>
              <?php else: ?>
                <span class="badge bg-light text-dark border">Not Started</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($t['delivery_status']): ?>
                <span class="badge bg-<?php echo $deliveryColors[$t['delivery_status']]; ?>"><?php echo $t['delivery_status']; ?></span>
              <?php else: ?>
                <span class="text-muted small">&mdash;</span>
              <?php endif; ?>
            </td>
            <td class="small text-muted"><?php echo date('d M Y', strtotime($t['transaction_date'])); ?></td>
            <td>
              <a href="<?php echo BASE_URL; ?>/user/transaction/details.php?id=<?php echo $t['transaction_id']; ?>"
                 class="btn btn-sm btn-outline-brand">View</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
