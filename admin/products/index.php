<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Products';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_header.php';

$keyword  = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';

/*
 * Products with their seller, category and report count.
 * The LEFT JOIN keeps products that have never been reported.
 */
$sql = "SELECT p.product_id, p.title, p.price, p.`condition`, p.status, p.created_at,
               c.category_name, u.full_name AS seller_name,
               COUNT(r.report_id) AS report_count
        FROM Product p
        INNER JOIN Category c ON p.category_id = c.category_id
        INNER JOIN User u     ON p.seller_id   = u.user_id
        LEFT JOIN Report r    ON p.product_id  = r.product_id
        WHERE 1 = 1";

$params = [];
$types  = '';

if ($keyword !== '') {
    $sql .= " AND p.title LIKE ?";
    $params[] = '%' . $keyword . '%';
    $types   .= 's';
}

if (in_array($statusFilter, ['Available', 'Pending', 'Sold'], true)) {
    $sql .= " AND p.status = ?";
    $params[] = $statusFilter;
    $types   .= 's';
}

$sql .= " GROUP BY p.product_id ORDER BY p.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

$statusColors = ['Available' => 'success', 'Pending' => 'warning', 'Sold' => 'secondary'];
?>

<h4 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Manage Products</h4>

<?php if (isset($_SESSION['admin_success'])): ?>
  <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_success']); unset($_SESSION['admin_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['admin_error'])): ?>
  <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?></div>
<?php endif; ?>

<div class="alert alert-info small">
  <i class="bi bi-info-circle"></i>
  Products are published immediately and do not need admin approval.
  Use this page to remove listings that break the rules.
</div>

<form method="GET" class="row g-2 mb-3">
  <div class="col-12 col-md-4">
    <input type="text" name="q" class="form-control" placeholder="Search by title" value="<?php echo sanitize($keyword); ?>">
  </div>
  <div class="col-6 col-md-3">
    <select name="status" class="form-select">
      <option value="">All statuses</option>
      <?php foreach (['Available', 'Pending', 'Sold'] as $s): ?>
        <option value="<?php echo $s; ?>" <?php echo $statusFilter === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-auto"><button class="btn btn-brand"><i class="bi bi-search"></i> Filter</button></div>
  <div class="col-auto"><a href="<?php echo BASE_URL; ?>/admin/products/index.php" class="btn btn-outline-secondary">Reset</a></div>
</form>

<div class="table-responsive bg-white rounded-3 border">
  <table class="table align-middle mb-0">
    <thead class="table-light">
      <tr><th>Title</th><th>Category</th><th>Seller</th><th>Price</th><th>Condition</th>
          <th>Status</th><th>Reports</th><th>Posted</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php if (mysqli_num_rows($products) === 0): ?>
      <tr><td colspan="9" class="text-center text-muted py-4">No products found.</td></tr>
    <?php else: ?>
      <?php while ($p = mysqli_fetch_assoc($products)): ?>
        <tr>
          <td>
            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $p['product_id']; ?>" class="fw-semibold" target="_blank">
              <?php echo sanitize($p['title']); ?>
            </a>
          </td>
          <td><?php echo sanitize($p['category_name']); ?></td>
          <td><?php echo sanitize($p['seller_name']); ?></td>
          <td>Tk <?php echo number_format($p['price'], 2); ?></td>
          <td><?php echo sanitize($p['condition']); ?></td>
          <td><span class="badge bg-<?php echo $statusColors[$p['status']]; ?>"><?php echo $p['status']; ?></span></td>
          <td>
            <?php if ($p['report_count'] > 0): ?>
              <span class="badge bg-danger"><?php echo $p['report_count']; ?></span>
            <?php else: ?>
              <span class="text-muted small">0</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted"><?php echo date('d M Y', strtotime($p['created_at'])); ?></td>
          <td>
            <?php if ($p['status'] !== 'Sold'): ?>
              <a href="<?php echo BASE_URL; ?>/admin/products/delete.php?id=<?php echo $p['product_id']; ?>"
                 class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Permanently delete this product listing?');">
                <i class="bi bi-trash"></i> Delete
              </a>
            <?php else: ?>
              <span class="text-muted small">Sold &mdash; kept for records</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_footer.php'; ?>
