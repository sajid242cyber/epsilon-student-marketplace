<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

// Every listing owned by this student, newest first, with its bid count
$sql = "SELECT * FROM vw_product_feed WHERE seller_id = ? ORDER BY created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$products = mysqli_stmt_get_result($stmt);

$statusColors = ['Available' => 'success', 'Pending' => 'warning', 'Sold' => 'secondary'];

$pageTitle = 'My Products';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">

  <?php if (isset($_SESSION['product_success'])): ?>
    <div class="alert alert-success alert-auto-dismiss"><?php echo $_SESSION['product_success']; unset($_SESSION['product_success']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['product_error'])): ?>
    <div class="alert alert-danger alert-auto-dismiss"><?php echo $_SESSION['product_error']; unset($_SESSION['product_error']); ?></div>
  <?php endif; ?>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-box-seam"></i> My Products</h4>
    <a href="<?php echo BASE_URL; ?>/user/product/post.php" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Post New</a>
  </div>

  <?php if (mysqli_num_rows($products) === 0): ?>
    <div class="empty-state bg-white rounded-3 border">
      <i class="bi bi-box-seam" style="font-size:2.5rem;"></i>
      <p class="mt-2">You haven't listed any products yet.</p>
      <a href="<?php echo BASE_URL; ?>/user/product/post.php" class="btn btn-brand">Post Your First Product</a>
    </div>
  <?php else: ?>
    <div class="table-responsive bg-white rounded-3 border">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Product</th><th>Category</th><th>Price</th><th>Status</th><th>Bids</th><th>Posted</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while ($p = mysqli_fetch_assoc($products)): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <img src="<?php echo $p['primary_image'] ? UPLOAD_URL . '/' . $p['primary_image'] : BASE_URL . '/assets/images/no-image.svg'; ?>"
                     style="width:48px;height:48px;object-fit:cover;" class="rounded border" alt="">
                <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo $p['product_id']; ?>" class="fw-semibold">
                  <?php echo sanitize($p['title']); ?>
                </a>
              </div>
            </td>
            <td><?php echo sanitize($p['category_name']); ?></td>
            <td>Tk <?php echo number_format($p['price'], 2); ?></td>
            <td><span class="badge bg-<?php echo $statusColors[$p['status']]; ?>"><?php echo $p['status']; ?></span></td>
            <td><span class="badge bg-light text-dark border"><?php echo $p['bid_count']; ?></span></td>
            <td class="small text-muted"><?php echo time_ago($p['created_at']); ?></td>
            <td>
              <div class="d-flex gap-1">
                <a href="<?php echo BASE_URL; ?>/user/product/edit.php?id=<?php echo $p['product_id']; ?>"
                   class="btn btn-sm btn-outline-brand"><i class="bi bi-pencil"></i></a>
                <?php if ($p['status'] !== 'Sold'): ?>
                  <a href="<?php echo BASE_URL; ?>/user/product/delete_process.php?id=<?php echo $p['product_id']; ?>"
                     class="btn btn-sm btn-outline-danger"
                     onclick="return confirm('Delete this product? This cannot be undone.');"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
