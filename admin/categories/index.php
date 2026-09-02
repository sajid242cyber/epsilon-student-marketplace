<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

$pageTitle = 'Categories';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_header.php';

/*
 * Every category with how many products use it.
 * HAVING is used further below to list only the categories actually in use.
 */
$categories = mysqli_query($conn, "
    SELECT c.category_id, c.category_name, COUNT(p.product_id) AS total_products
    FROM Category c
    LEFT JOIN Product p ON c.category_id = p.category_id
    GROUP BY c.category_id, c.category_name
    ORDER BY c.category_id");
?>

<h4 class="fw-bold mb-3"><i class="bi bi-tags"></i> Manage Categories</h4>

<?php if (isset($_SESSION['admin_success'])): ?>
  <div class="alert alert-success alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_success']); unset($_SESSION['admin_success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['admin_error'])): ?>
  <div class="alert alert-danger alert-auto-dismiss"><?php echo sanitize($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?></div>
<?php endif; ?>

<div class="row g-3">

  <!-- Add a new category -->
  <div class="col-12 col-lg-4">
    <div class="cx-sidebar-card">
      <h6 class="fw-bold mb-3">Add New Category</h6>
      <form method="POST" action="<?php echo BASE_URL; ?>/admin/categories/save.php">
        <input type="hidden" name="action" value="add">
        <div class="mb-3">
          <label for="category_name" class="form-label">Category Name</label>
          <input type="text" class="form-control" id="category_name" name="category_name" maxlength="50" required>
        </div>
        <button type="submit" class="btn btn-brand w-100"><i class="bi bi-plus-lg"></i> Add Category</button>
      </form>
    </div>
  </div>

  <!-- Existing categories -->
  <div class="col-12 col-lg-8">
    <div class="table-responsive bg-white rounded-3 border">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>Category Name</th><th>Products</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($c = mysqli_fetch_assoc($categories)): ?>
          <tr>
            <td class="text-muted"><?php echo $c['category_id']; ?></td>
            <td>
              <form method="POST" action="<?php echo BASE_URL; ?>/admin/categories/save.php" class="d-flex gap-2">
                <input type="hidden" name="action" value="rename">
                <input type="hidden" name="category_id" value="<?php echo $c['category_id']; ?>">
                <input type="text" name="category_name" class="form-control form-control-sm"
                       value="<?php echo sanitize($c['category_name']); ?>" maxlength="50" required>
                <button type="submit" class="btn btn-sm btn-outline-brand">Save</button>
              </form>
            </td>
            <td><span class="badge bg-light text-dark border"><?php echo $c['total_products']; ?></span></td>
            <td>
              <?php if ($c['total_products'] == 0): ?>
                <a href="<?php echo BASE_URL; ?>/admin/categories/delete.php?id=<?php echo $c['category_id']; ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Delete this category?');">
                  <i class="bi bi-trash"></i>
                </a>
              <?php else: ?>
                <span class="text-muted small">In use</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <p class="text-muted small mt-2">
      <i class="bi bi-info-circle"></i>
      A category can only be deleted when no product is using it.
    </p>
  </div>

</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/admin_footer.php'; ?>
