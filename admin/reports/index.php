<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Reports';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_header.php';

$statusFilter = $_GET['status'] ?? 'Pending';

$sql = "SELECT r.report_id, r.reason, r.description, r.status, r.created_at,
               p.product_id, p.title, p.status AS product_status,
               reporter.full_name AS reporter_name,
               seller.full_name   AS seller_name
        FROM Report r
        INNER JOIN Product p       ON r.product_id  = p.product_id
        INNER JOIN User reporter   ON r.reported_by = reporter.user_id
        INNER JOIN User seller     ON p.seller_id   = seller.user_id";

$params = [];
$types  = '';

if (in_array($statusFilter, ['Pending', 'Reviewed', 'Resolved', 'Dismissed'], true)) {
    $sql .= " WHERE r.status = ?";
    $params[] = $statusFilter;
    $types    = 's';
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$reports = mysqli_stmt_get_result($stmt);

$statusColors = ['Pending' => 'warning', 'Reviewed' => 'info', 'Resolved' => 'success', 'Dismissed' => 'secondary'];
?>

<h4 class="fw-bold mb-3"><i class="bi bi-flag"></i> Reported Products</h4>

<?php if (isset($_SESSION['admin_success'])): ?>
  <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_success']); unset($_SESSION['admin_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['admin_error'])): ?>
  <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?></div>
<?php endif; ?>

<!-- Status tabs -->
<ul class="nav nav-pills mb-3 flex-wrap gap-1">
  <?php foreach (['Pending', 'Reviewed', 'Resolved', 'Dismissed', 'All'] as $tab):
      $value = ($tab === 'All') ? '' : $tab;
      $isActive = ($statusFilter === $value) || ($tab === 'All' && !in_array($statusFilter, ['Pending','Reviewed','Resolved','Dismissed'], true));
  ?>
    <li class="nav-item">
      <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>"
         href="<?php echo BASE_URL; ?>/admin/reports/index.php?status=<?php echo urlencode($value); ?>">
        <?php echo $tab; ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<?php if (mysqli_num_rows($reports) === 0): ?>
  <div class="empty-state bg-white rounded-3 border">
    <i class="bi bi-shield-check" style="font-size:2.5rem;"></i>
    <p class="mt-2 mb-0">No reports in this category.</p>
  </div>
<?php else: ?>
  <div class="table-responsive bg-white rounded-3 border">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Product</th><th>Seller</th><th>Reason</th><th>Details</th>
            <th>Reported By</th><th>Status</th><th>Date</th><th>Action</th></tr>
      </thead>
      <tbody>
      <?php while ($r = mysqli_fetch_assoc($reports)): ?>
        <tr>
          <td class="text-muted"><?php echo $r['report_id']; ?></td>
          <td>
            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $r['product_id']; ?>" target="_blank" class="fw-semibold">
              <?php echo sanitize($r['title']); ?>
            </a>
            <div class="small text-muted"><?php echo $r['product_status']; ?></div>
          </td>
          <td><?php echo sanitize($r['seller_name']); ?></td>
          <td><span class="badge bg-danger"><?php echo sanitize($r['reason']); ?></span></td>
          <td class="small" style="max-width:240px;"><?php echo sanitize($r['description']); ?></td>
          <td class="small"><?php echo sanitize($r['reporter_name']); ?></td>
          <td><span class="badge bg-<?php echo $statusColors[$r['status']]; ?>"><?php echo $r['status']; ?></span></td>
          <td class="small text-muted"><?php echo date('d M Y', strtotime($r['created_at'])); ?></td>
          <td>
            <div class="d-flex flex-wrap gap-1">
              <?php if ($r['status'] === 'Pending'): ?>
                <a href="<?php echo BASE_URL; ?>/admin/reports/update.php?id=<?php echo $r['report_id']; ?>&status=Reviewed"
                   class="btn btn-sm btn-outline-info">Mark Reviewed</a>
              <?php endif; ?>
              <?php if ($r['status'] !== 'Dismissed' && $r['status'] !== 'Resolved'): ?>
                <a href="<?php echo BASE_URL; ?>/admin/reports/update.php?id=<?php echo $r['report_id']; ?>&status=Dismissed"
                   class="btn btn-sm btn-outline-secondary">Dismiss</a>
                <a href="<?php echo BASE_URL; ?>/admin/reports/resolve.php?id=<?php echo $r['report_id']; ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Delete this product listing and resolve the report?');">
                  Remove Product
                </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_footer.php'; ?>
