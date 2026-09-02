<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

// Every bid this student has placed, with the product and seller details
$sql = "SELECT b.bid_id, b.bid_amount, b.counter_amount, b.status, b.created_at,
               p.product_id, p.title, p.price, p.status AS product_status,
               u.full_name AS seller_name,
               t.transaction_id
        FROM Bid b
        INNER JOIN Product p ON b.product_id = p.product_id
        INNER JOIN User u    ON p.seller_id  = u.user_id
        LEFT JOIN Transaction t ON t.bid_id = b.bid_id
        WHERE b.buyer_id = ?
        ORDER BY b.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$bids = mysqli_stmt_get_result($stmt);

$badgeColors = [
    'Pending'   => 'warning',
    'Countered' => 'info',
    'Accepted'  => 'success',
    'Rejected'  => 'danger',
];

$pageTitle = 'My Bids';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <h4 class="fw-bold mb-3"><i class="bi bi-hammer"></i> My Bids</h4>

  <?php if (mysqli_num_rows($bids) === 0): ?>
    <div class="empty-state bg-white rounded-3 border">
      <i class="bi bi-hammer" style="font-size:2.5rem;"></i>
      <p class="mt-2 mb-0">You haven't placed any bids yet.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive bg-white rounded-3 border">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>Product</th><th>Seller</th><th>Asking Price</th><th>My Bid</th>
              <th>Seller's Counter</th><th>Status</th><th>Placed</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($b = mysqli_fetch_assoc($bids)): ?>
          <tr>
            <td>
              <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $b['product_id']; ?>" class="fw-semibold">
                <?php echo sanitize($b['title']); ?>
              </a>
            </td>
            <td><?php echo sanitize($b['seller_name']); ?></td>
            <td>Tk <?php echo number_format($b['price'], 2); ?></td>
            <td class="fw-semibold">Tk <?php echo number_format($b['bid_amount'], 2); ?></td>
            <td>
              <?php if ($b['counter_amount'] !== null): ?>
                <span class="fw-semibold">Tk <?php echo number_format($b['counter_amount'], 2); ?></span>
              <?php else: ?>
                <span class="text-muted">&mdash;</span>
              <?php endif; ?>
            </td>
            <td><span class="badge bg-<?php echo $badgeColors[$b['status']] ?? 'secondary'; ?>"><?php echo $b['status']; ?></span></td>
            <td class="small text-muted"><?php echo time_ago($b['created_at']); ?></td>
            <td>
              <?php if ($b['status'] === 'Accepted' && $b['transaction_id']): ?>
                <a href="<?php echo BASE_URL; ?>/user/transaction/details.php?id=<?php echo $b['transaction_id']; ?>"
                   class="btn btn-sm btn-brand">Continue to Payment</a>

              <?php elseif ($b['status'] === 'Countered' && $b['product_status'] === 'Available'): ?>
                <!-- The seller wants a different price - the buyer decides -->
                <div class="d-flex flex-wrap gap-1">
                  <a href="<?php echo BASE_URL; ?>/user/bid/accept.php?bid_id=<?php echo $b['bid_id']; ?>"
                     class="btn btn-sm btn-success"
                     onclick="return confirm('Accept the counter offer of Tk <?php echo number_format($b['counter_amount'], 2); ?>?');">
                    Accept
                  </a>
                  <a href="<?php echo BASE_URL; ?>/user/bid/reject.php?bid_id=<?php echo $b['bid_id']; ?>"
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Turn down this counter offer?');">Decline</a>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
