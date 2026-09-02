<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId    = (int) $_SESSION['user_id'];

// Only the seller who owns this product may edit it
$stmt = mysqli_prepare($conn, "SELECT * FROM Product WHERE product_id = ? AND seller_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $productId, $userId);
mysqli_stmt_execute($stmt);
$product = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$product) {
    header('Location: ' . BASE_URL . '/user/product/my_products.php');
    exit;
}

$categories = getCategories($conn);

// Existing images so the seller can remove individual ones
$imgStmt = mysqli_prepare($conn, "SELECT image_id, image_path FROM ProductImage WHERE product_id = ? ORDER BY image_id");
mysqli_stmt_bind_param($imgStmt, 'i', $productId);
mysqli_stmt_execute($imgStmt);
$existingImages = mysqli_stmt_get_result($imgStmt);

$conditions = ['New', 'Like New', 'Good', 'Fair', 'Poor'];
$statuses   = ['Available', 'Pending', 'Sold'];

$pageTitle = 'Edit Product';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <?php if (isset($_SESSION['product_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo $_SESSION['product_error']; unset($_SESSION['product_error']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <h4 class="fw-bold mb-3"><i class="bi bi-pencil"></i> Edit Product</h4>

        <form method="POST" action="<?php echo BASE_URL; ?>/user/product/edit_process.php" enctype="multipart/form-data">
          <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">

          <div class="mb-3">
            <label for="title" class="form-label">Product Title</label>
            <input type="text" class="form-control" id="title" name="title" maxlength="150"
                   value="<?php echo sanitize($product['title']); ?>" required>
          </div>

          <div class="row g-3">
            <div class="col-md-3">
              <label for="category_id" class="form-label">Category</label>
              <select class="form-select" id="category_id" name="category_id" required>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['category_id']; ?>"
                    <?php echo $cat['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                    <?php echo sanitize($cat['category_name']); ?>
                  </option>
                <?php endforeach; ?>
                <option value="new">+ Add a new category</option>
              </select>
            </div>
            <div class="col-md-3">
              <label for="price" class="form-label">Price (Tk)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="price" name="price"
                     value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="col-md-3">
              <label for="condition" class="form-label">Condition</label>
              <select class="form-select" id="condition" name="condition" required>
                <?php foreach ($conditions as $c): ?>
                  <option value="<?php echo $c; ?>" <?php echo $product['condition'] === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label for="status" class="form-label">Status</label>
              <select class="form-select" id="status" name="status" required>
                <?php foreach ($statuses as $s): ?>
                  <option value="<?php echo $s; ?>" <?php echo $product['status'] === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Only shown when "Add a new category" is selected above -->
          <div class="mt-3 d-none" id="newCategoryWrap">
            <label for="new_category" class="form-label">New Category Name</label>
            <input type="text" class="form-control" id="new_category" name="new_category" maxlength="50">
            <div class="form-text">
              If a category with this name already exists, your product is moved into it instead.
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="5" required><?php echo sanitize($product['description']); ?></textarea>
          </div>

          <?php if (mysqli_num_rows($existingImages) > 0): ?>
            <div class="mb-3">
              <label class="form-label">Current Images <span class="text-muted small">(tick to remove)</span></label>
              <div class="row g-2">
                <?php while ($img = mysqli_fetch_assoc($existingImages)): ?>
                  <div class="col-4 col-md-3">
                    <img src="<?php echo UPLOAD_URL . '/' . $img['image_path']; ?>" class="img-fluid rounded border">
                    <div class="form-check mt-1">
                      <input class="form-check-input" type="checkbox" name="remove_images[]"
                             value="<?php echo (int) $img['image_id']; ?>" id="rm<?php echo $img['image_id']; ?>">
                      <label class="form-check-label small text-danger" for="rm<?php echo $img['image_id']; ?>">Remove</label>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label for="product_images" class="form-label">Add More Images</label>
            <input type="file" class="form-control" id="product_images" name="product_images[]" accept="image/*" multiple>
            <div class="row g-2 mt-2" id="imagePreviewWrap"></div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand">Save Changes</button>
            <a href="<?php echo BASE_URL; ?>/product.php?id=<?php echo (int) $product['product_id']; ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
