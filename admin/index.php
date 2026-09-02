<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Dashboard';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_header.php';

// Headline counters
$totalUsers    = fetchOne($conn, "SELECT COUNT(*) AS c FROM User WHERE role = 'student'")['c'];
$totalProducts = fetchOne($conn, "SELECT COUNT(*) AS c FROM Product")['c'];
$totalSold     = fetchOne($conn, "SELECT COUNT(*) AS c FROM Product WHERE status = 'Sold'")['c'];
$totalBids     = fetchOne($conn, "SELECT COUNT(*) AS c FROM Bid")['c'];
$pendingReports = fetchOne($conn, "SELECT COUNT(*) AS c FROM Report WHERE status = 'Pending'")['c'];
$totalRevenue  = fetchOne($conn, "SELECT COALESCE(SUM(amount), 0) AS total FROM Payment WHERE payment_status = 'Paid'")['total'];

// Products grouped by category (GROUP BY with an aggregate function)
$byCategory = mysqli_query($conn, "
    SELECT c.category_name, COUNT(p.product_id) AS total
    FROM Category c
    LEFT JOIN Product p ON c.category_id = p.category_id
    GROUP BY c.category_id, c.category_name
    ORDER BY total DESC");

// The five most recent transactions
$recentTxns = mysqli_query($conn, "
    SELECT t.transaction_id, t.status, t.transaction_date, p.title, COALESCE(b.counter_amount, b.bid_amount) AS bid_amount,
           buyer.full_name AS buyer_name, seller.full_name AS seller_name
    FROM Transaction t
    INNER JOIN Product p   ON t.product_id = p.product_id
    INNER JOIN Bid b       ON t.bid_id     = b.bid_id
    INNER JOIN User buyer  ON t.buyer_id   = buyer.user_id
    INNER JOIN User seller ON t.seller_id  = seller.user_id
    ORDER BY t.transaction_date DESC
    LIMIT 5");

$stats = [
    ['label' => 'Students',        'value' => $totalUsers,     'icon' => 'bi-people',      'color' => 'primary'],
    ['label' => 'Products',        'value' => $totalProducts,  'icon' => 'bi-box-seam',    'color' => 'info'],
    ['label' => 'Items Sold',      'value' => $totalSold,      'icon' => 'bi-bag-check',   'color' => 'success'],
    ['label' => 'Total Bids',      'value' => $totalBids,      'icon' => 'bi-hammer',      'color' => 'warning'],
    ['label' => 'Pending Reports', 'value' => $pendingReports, 'icon' => 'bi-flag',        'color' => 'danger'],
];
?>

<h4 class="fw-bold mb-3"><i class="bi bi-speedometer2"></i> Dashboard</h4>

<!-- Stat cards -->
<div class="row g-3 mb-4">
  <?php foreach ($stats as $s): ?>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="cx-sidebar-card mb-0 h-100">
        <div class="d-flex align-items-center gap-2">
          <i class="bi <?php echo $s['icon']; ?> text-<?php echo $s['color']; ?>" style="font-size:1.6rem;"></i>
          <div>
            <div class="fs-4 fw-bold"><?php echo $s['value']; ?></div>
            <div class="small text-muted"><?php echo $s['label']; ?></div>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="cx-sidebar-card mb-0 h-100">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-cash-stack text-success" style="font-size:1.6rem;"></i>
        <div>
          <div class="fs-5 fw-bold">Tk <?php echo number_format($totalRevenue, 0); ?></div>
          <div class="small text-muted">Total Paid</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">

  <!-- Products per category -->
  <div class="col-12 col-lg-5">
    <div class="cx-sidebar-card">
      <h6 class="fw-bold mb-3">Products by Category</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light"><tr><th>Category</th><th class="text-end">Products</th></tr></thead>
          <tbody>
          <?php while ($row = mysqli_fetch_assoc($byCategory)): ?>
            <tr>
              <td><?php echo sanitize($row['category_name']); ?></td>
              <td class="text-end"><span class="badge bg-light text-dark border"><?php echo $row['total']; ?></span></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Recent transactions -->
  <div class="col-12 col-lg-7">
    <div class="cx-sidebar-card">
      <h6 class="fw-bold mb-3">Recent Transactions</h6>
      <?php if (mysqli_num_rows($recentTxns) === 0): ?>
        <p class="text-muted small mb-0">No transactions yet.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>#</th><th>Product</th><th>Buyer</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            <?php while ($t = mysqli_fetch_assoc($recentTxns)): ?>
              <tr>
                <td class="text-muted">#<?php echo $t['transaction_id']; ?></td>
                <td><?php echo sanitize($t['title']); ?></td>
                <td><?php echo sanitize($t['buyer_name']); ?></td>
                <td>Tk <?php echo number_format($t['bid_amount'], 2); ?></td>
                <td>
                  <span class="badge bg-<?php echo $t['status'] === 'Completed' ? 'success' : 'warning'; ?>">
                    <?php echo $t['status']; ?>
                  </span>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_footer.php'; ?>
