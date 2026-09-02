<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Payments';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_header.php';

$methodFilter = $_GET['method'] ?? '';

$sql = "SELECT pay.payment_id, pay.payment_method, pay.amount, pay.payment_status,
               pay.paid_at, pay.created_at,
               t.transaction_id, p.title,
               buyer.full_name  AS buyer_name,
               seller.full_name AS seller_name,
               i.invoice_number
        FROM Payment pay
        INNER JOIN Transaction t ON pay.transaction_id = t.transaction_id
        INNER JOIN Product p     ON t.product_id  = p.product_id
        INNER JOIN User buyer    ON t.buyer_id    = buyer.user_id
        INNER JOIN User seller   ON t.seller_id   = seller.user_id
        LEFT JOIN Invoice i      ON t.transaction_id = i.transaction_id";

$params = [];
$types  = '';

if (in_array($methodFilter, ['bKash', 'Nagad', 'Rocket', 'Bank Transfer'], true)) {
    $sql .= " WHERE pay.payment_method = ?";
    $params[] = $methodFilter;
    $types    = 's';
}

$sql .= " ORDER BY pay.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$payments = mysqli_stmt_get_result($stmt);

/*
 * Money received grouped by payment method.
 * HAVING filters out methods that have not been used yet.
 */
$byMethod = mysqli_query($conn, "
    SELECT payment_method,
           COUNT(*)    AS total_payments,
           SUM(amount) AS total_amount
    FROM Payment
    WHERE payment_status = 'Paid'
    GROUP BY payment_method
    HAVING total_payments > 0
    ORDER BY total_amount DESC");

$totalPaid = fetchOne($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM Payment WHERE payment_status = 'Paid'")['total'];

$payColors = ['Pending' => 'warning', 'Paid' => 'success', 'Failed' => 'danger'];
?>

<h4 class="fw-bold mb-3"><i class="bi bi-credit-card"></i> Payments</h4>

<div class="row g-3 mb-3">
  <div class="col-12 col-lg-4">
    <div class="cx-sidebar-card mb-0">
      <div class="small text-muted">Total Received</div>
      <div class="fs-3 fw-bold text-success">Tk <?php echo number_format($totalPaid, 2); ?></div>
    </div>
  </div>
  <div class="col-12 col-lg-8">
    <div class="cx-sidebar-card mb-0">
      <h6 class="fw-bold mb-2">Received by Method</h6>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Method</th><th>Payments</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
          <?php if (mysqli_num_rows($byMethod) === 0): ?>
            <tr><td colspan="3" class="text-muted small">No completed payments yet.</td></tr>
          <?php else: ?>
            <?php while ($m = mysqli_fetch_assoc($byMethod)): ?>
              <tr>
                <td><?php echo sanitize($m['payment_method']); ?></td>
                <td><?php echo $m['total_payments']; ?></td>
                <td class="text-end">Tk <?php echo number_format($m['total_amount'], 2); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<form method="GET" class="row g-2 mb-3">
  <div class="col-6 col-md-3">
    <select name="method" class="form-select" onchange="this.form.submit()">
      <option value="">All methods</option>
      <?php foreach (['bKash', 'Nagad', 'Rocket', 'Bank Transfer'] as $m): ?>
        <option value="<?php echo $m; ?>" <?php echo $methodFilter === $m ? 'selected' : ''; ?>><?php echo $m; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
</form>

<div class="table-responsive bg-white rounded-3 border">
  <table class="table align-middle mb-0">
    <thead class="table-light">
      <tr><th>#</th><th>Transaction</th><th>Product</th><th>Buyer</th><th>Seller</th>
          <th>Method</th><th>Amount</th><th>Status</th><th>Invoice</th><th>Paid At</th></tr>
    </thead>
    <tbody>
    <?php if (mysqli_num_rows($payments) === 0): ?>
      <tr><td colspan="10" class="text-center text-muted py-4">No payments found.</td></tr>
    <?php else: ?>
      <?php while ($pay = mysqli_fetch_assoc($payments)): ?>
        <tr>
          <td class="text-muted"><?php echo $pay['payment_id']; ?></td>
          <td class="text-muted">#<?php echo $pay['transaction_id']; ?></td>
          <td class="fw-semibold"><?php echo sanitize($pay['title']); ?></td>
          <td><?php echo sanitize($pay['buyer_name']); ?></td>
          <td><?php echo sanitize($pay['seller_name']); ?></td>
          <td><?php echo sanitize($pay['payment_method']); ?></td>
          <td>Tk <?php echo number_format($pay['amount'], 2); ?></td>
          <td><span class="badge bg-<?php echo $payColors[$pay['payment_status']]; ?>"><?php echo $pay['payment_status']; ?></span></td>
          <td class="small"><?php echo $pay['invoice_number'] ? '<code>' . sanitize($pay['invoice_number']) . '</code>' : '<span class="text-muted">&mdash;</span>'; ?></td>
          <td class="small text-muted"><?php echo $pay['paid_at'] ? date('d M Y, h:i A', strtotime($pay['paid_at'])) : '&mdash;'; ?></td>
        </tr>
      <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_footer.php'; ?>
