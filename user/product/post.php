<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$categories = getCategories($conn);

$pageTitle = 'Post a Product';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <?php if (isset($_SESSION['product_error'])): ?>
        <div class="alert alert-danger alert-auto-dismiss"><?php echo $_SESSION['product_error']; unset($_SESSION['product_error']); ?></div>
      <?php endif; ?>

      <div class="auth-card">
        <h4 class="fw-bold mb-3"><i class="bi bi-plus-square"></i> Post a Product</h4>

        <form method="POST" action="<?php echo BASE_URL; ?>/user/product/post_process.php" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="title" class="form-label">Product Title</label>
            <input type="text" class="form-control" id="title" name="title" maxlength="150" required>
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label for="category_id" class="form-label">Category</label>
              <select class="form-select" id="category_id" name="category_id" required>
                <option value="">Choose...</option>
                <?php foreach ($categories as $cat): ?>
                  <option value="<?php echo $cat['category_id']; ?>"><?php echo sanitize($cat['category_name']); ?></option>
                <?php endforeach; ?>
                <option value="new">+ Add a new category</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="price" class="form-label">Price (Tk)</label>
              <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
            </div>
            <div class="col-md-4">
              <label for="condition" class="form-label">Condition</label>
              <select class="form-select" id="condition" name="condition" required>
                <option value="New">New</option>
                <option value="Like New">Like New</option>
                <option value="Good" selected>Good</option>
                <option value="Fair">Fair</option>
                <option value="Poor">Poor</option>
              </select>
            </div>
          </div>

          <!-- Only shown when "Add a new category" is selected above -->
          <div class="mt-3 d-none" id="newCategoryWrap">
            <label for="new_category" class="form-label">New Category Name</label>
            <input type="text" class="form-control" id="new_category" name="new_category" maxlength="50">
            <div class="form-text">
              If a category with this name already exists, your product is added to it instead.
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
          </div>

          <div class="mb-3">
            <label for="product_images" class="form-label">Product Images <span class="text-muted small">(JPG/PNG/WEBP, up to 5)</span></label>
            <input type="file" class="form-control" id="product_images" name="product_images[]" accept="image/*" multiple>
            <div class="row g-2 mt-2" id="imagePreviewWrap"></div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-brand">Publish Product</button>
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/footer.php'; ?>
