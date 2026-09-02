<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Transactions';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_header.php';

$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT t.transaction_id, t.status, t.transaction_date,
               p.title, COALESCE(b.counter_amount, b.bid_amount) AS bid_amount,
               buyer.full_name  AS buyer_name,
               seller.full_name AS seller_name,
               pay.payment_status, pay.payment_method,
               d.delivery_status,
               i.invoice_number
        FROM Transaction t
        INNER JOIN Product p   ON t.product_id = p.product_id
        INNER JOIN Bid b       ON t.bid_id     = b.bid_id
        INNER JOIN User buyer  ON t.buyer_id   = buyer.user_id
        INNER JOIN User seller ON t.seller_id  = seller.user_id
        LEFT JOIN Payment pay  ON t.transaction_id = pay.transaction_id
        LEFT JOIN Delivery d   ON t.transaction_id = d.transaction_id
        LEFT JOIN Invoice i    ON t.transaction_id = i.transaction_id";

$params = [];
$types  = '';

if (in_array($statusFilter, ['Pending', 'Completed', 'Cancelled'], true)) {
    $sql .= " WHERE t.status = ?";
    $params[] = $statusFilter;
    $types    = 's';
}

$sql .= " ORDER BY t.transaction_date DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$transactions = mysqli_stmt_get_result($stmt);

// Totals shown above the table (aggregate functions)
$summary = fetchOne($conn, "
    SELECT COUNT(*) AS total,
           SUM(status = 'Completed') AS completed,
           SUM(status = 'Pending')   AS pending
    FROM Transaction");

$txnColors      = ['Pending' => 'warning', 'Completed' => 'success', 'Cancelled' => 'secondary'];
$payColors      = ['Pending' => 'warning', 'Paid' => 'success', 'Failed' => 'danger'];
$deliveryColors = ['Pending' => 'secondary', 'Packed' => 'info', 'Shipped' => 'primary', 'Delivered' => 'success'];
?>

<h4 class="fw-bold mb-3"><i class="bi bi-receipt"></i> All Transactions</h4>

<div class="row g-3 mb-3">
  <div class="col-4"><div class="cx-sidebar-card mb-0 text-center"><div class="fs-4 fw-bold"><?php echo (int) $summary['total']; ?></div><div class="small text-muted">Total</div></div></div>
  <div class="col-4"><div class="cx-sidebar-card mb-0 text-center"><div class="fs-4 fw-bold text-success"><?php echo (int) $summary['completed']; ?></div><div class="small text-muted">Completed</div></div></div>
  <div class="col-4"><div class="cx-sidebar-card mb-0 text-center"><div class="fs-4 fw-bold text-warning"><?php echo (int) $summary['pending']; ?></div><div class="small text-muted">Pending</div></div></div>
</div>

<form method="GET" class="row g-2 mb-3">
  <div class="col-6 col-md-3">
    <select name="status" class="form-select" onchange="this.form.submit()">
      <option value="">All statuses</option>
      <?php foreach (['Pending', 'Completed', 'Cancelled'] as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="table-responsive bg-white rounded-3 border">
  <table class="table align-middle mb-0">
    <thead class="table-light">
      <tr><th>#</th><th>Product</th><th>Buyer</th><th>Seller</th><th>Amount</th>
          <th>Transaction</th><th>Payment</th><th>Delivery</th><th>Invoice</th><th>Date</th></tr>
    </thead>
    <tbody>
    <?php if (mysqli_num_rows($transactions) === 0): ?>
      <tr><td colspan="10" class="text-center text-muted py-4">No transactions found.</td></tr>
    <?php else: ?>
      <?php while ($t = mysqli_fetch_assoc($transactions)): ?>
        <tr>
          <td class="text-muted">#<?php echo $t['transaction_id']; ?></td>
          <td class="fw-semibold"><?php echo sanitize($t['title']); ?></td>
          <td><?php echo sanitize($t['buyer_name']); ?></td>
          <td><?php echo sanitize($t['seller_name']); ?></td>
          <td>Tk <?php echo number_format($t['bid_amount'], 2); ?></td>
          <td><span class="badge bg-<?php echo $txnColors[$t['status']]; ?>"><?php echo $t['status']; ?></span></td>
          <td>
            <?php if ($t['payment_status']): ?>
              <span class="badge bg-<?php echo $payColors[$t['payment_status']]; ?>"><?php echo $t['payment_status']; ?></span>
              <div class="small text-muted"><?php echo sanitize($t['payment_method']); ?></div>
            <?php else: ?>
              <span class="text-muted small">&mdash;</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($t['delivery_status']): ?>
              <span class="badge bg-<?php echo $deliveryColors[$t['delivery_status']]; ?>"><?php echo $t['delivery_status']; ?></span>
            <?php else: ?>
              <span class="text-muted small">&mdash;</span>
            <?php endif; ?>
          </td>
          <td class="small"><?php echo $t['invoice_number'] ? '<code>' . sanitize($t['invoice_number']) . '</code>' : '<span class="text-muted">&mdash;</span>'; ?></td>
          <td class="small text-muted"><?php echo date('d M Y', strtotime($t['transaction_date'])); ?></td>
        </tr>
      <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_footer.php'; ?>
