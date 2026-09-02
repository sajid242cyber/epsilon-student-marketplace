<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Users';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_header.php';

$keyword = trim($_GET['q'] ?? '');

// Each student with how many products they listed and their average rating
$sql = "SELECT u.user_id, u.student_id, u.full_name, u.department, u.batch,
               u.email, u.phone, u.status, u.created_at,
               COUNT(DISTINCT p.product_id) AS total_products,
               (SELECT ROUND(AVG(r.rating), 1) FROM Review r WHERE r.seller_id = u.user_id) AS rating
        FROM User u
        LEFT JOIN Product p ON u.user_id = p.seller_id
        WHERE u.role = 'student'";

$params = [];
$types  = '';

if ($keyword !== '') {
    $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ?)";
    $like = '%' . $keyword . '%';
    $params = [$like, $like, $like];
    $types  = 'sss';
}

$sql .= " GROUP BY u.user_id ORDER BY u.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$users = mysqli_stmt_get_result($stmt);
?>

<h4 class="fw-bold mb-3"><i class="bi bi-people"></i> Manage Users</h4>

<?php if (isset($_SESSION['admin_success'])): ?>
  <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_success']); unset($_SESSION['admin_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['admin_error'])): ?>
  <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?></div>
<?php endif; ?>

<form method="GET" class="row g-2 mb-3">
  <div class="col-12 col-md-5">
    <input type="text" name="q" class="form-control" placeholder="Search by name, email or student ID" value="<?php echo sanitize($keyword); ?>">
  </div>
  <div class="col-auto">
    <button class="btn btn-brand"><i class="bi bi-search"></i> Search</button>
  </div>
  <?php if ($keyword !== ''): ?>
    <div class="col-auto"><a href="<?php echo BASE_URL; ?>/admin/users/index.php" class="btn btn-outline-secondary">Reset</a></div>
  <?php endif; ?>
</form>

<div class="table-responsive bg-white rounded-3 border">
  <table class="table align-middle mb-0">
    <thead class="table-light">
      <tr><th>Student ID</th><th>Name</th><th>Department</th><th>Batch</th><th>Contact</th>
          <th>Products</th><th>Rating</th><th>Status</th><th>Joined</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php if (mysqli_num_rows($users) === 0): ?>
      <tr><td colspan="10" class="text-center text-muted py-4">No users found.</td></tr>
    <?php else: ?>
      <?php while ($u = mysqli_fetch_assoc($users)): ?>
        <tr>
          <td><code><?php echo sanitize($u['student_id']); ?></code></td>
          <td class="fw-semibold"><?php echo sanitize($u['full_name']); ?></td>
          <td><?php echo sanitize($u['department']); ?></td>
          <td><?php echo sanitize($u['batch']); ?></td>
          <td class="small">
            <?php echo sanitize($u['email']); ?><br>
            <span class="text-muted"><?php echo sanitize($u['phone']); ?></span>
          </td>
          <td><span class="badge bg-light text-dark border"><?php echo $u['total_products']; ?></span></td>
          <td>
            <?php if ($u['rating']): ?>
              <span class="rating-stars"><i class="bi bi-star-fill"></i> <?php echo $u['rating']; ?></span>
            <?php else: ?>
              <span class="text-muted small">&mdash;</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge bg-<?php echo $u['status'] === 'active' ? 'success' : 'danger'; ?>">
              <?php echo ucfirst($u['status']); ?>
            </span>
          </td>
          <td class="small text-muted"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
          <td>
            <?php if ($u['status'] === 'active'): ?>
              <a href="<?php echo BASE_URL; ?>/admin/users/toggle_status.php?id=<?php echo $u['user_id']; ?>"
                 class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Ban this student? They will not be able to log in.');">Ban</a>
            <?php else: ?>
              <a href="<?php echo BASE_URL; ?>/admin/users/toggle_status.php?id=<?php echo $u['user_id']; ?>"
                 class="btn btn-sm btn-outline-success"
                 onclick="return confirm('Unban this student?');">Unban</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_footer.php'; ?>
