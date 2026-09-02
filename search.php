<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

// Read the filters from the query string
$keyword    = trim($_GET['q'] ?? '');
$category   = trim($_GET['category'] ?? '');
$minPrice   = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? filter_input(INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT) : null;
$maxPrice   = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT) : null;
$sort       = $_GET['sort'] ?? 'latest';

// Build the WHERE clause piece by piece so every value stays parameterised
// Same rule as the home feed: everything except items that are already sold
$where  = ["status <> 'Sold'"];
$params = [];
$types  = '';

if ($keyword !== '') {
    $where[] = '(title LIKE ? OR description LIKE ?)';
    $like = '%' . $keyword . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}

if ($category !== '') {
    $where[] = 'category_name = ?';
    $params[] = $category;
    $types   .= 's';
}

if ($minPrice !== null && $minPrice !== false) {
    $where[] = 'price >= ?';
    $params[] = $minPrice;
    $types   .= 'd';
}

if ($maxPrice !== null && $maxPrice !== false) {
    $where[] = 'price <= ?';
    $params[] = $maxPrice;
    $types   .= 'd';
}

// Only a fixed set of sort options is allowed, so this can never be injected
$sortOptions = [
    'latest'     => 'created_at DESC',
    'price_low'  => 'price ASC',
    'price_high' => 'price DESC',
];
$orderBy = $sortOptions[$sort] ?? $sortOptions['latest'];

$sql = "SELECT * FROM vw_product_feed WHERE " . implode(' AND ', $where) . " ORDER BY $orderBy LIMIT 60";
$stmt = mysqli_prepare($conn, $sql);
if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$results = mysqli_stmt_get_result($stmt);
$resultCount = mysqli_num_rows($results);

$pageTitle = 'Search Products';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/header.php';
?>

<div class="container-fluid cx-page py-3">
  <div class="row g-3">

    <!-- Left Sidebar: categories -->
    <div class="col-lg-3 col-xl-2 d-none d-lg-block">
      <?php require_once __DIR__ . '/includes/sidebar_left.php'; ?>
    </div>

    <!-- Results -->
    <div class="col-12 col-lg-9 col-xl-10">

      <!-- Filter bar -->
      <div class="cx-sidebar-card">
        <form method="GET" action="<?php echo BASE_URL; ?>/search.php" class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label for="q" class="form-label small fw-semibold">Keyword</label>
            <input type="text" class="form-control" id="q" name="q" value="<?php echo sanitize($keyword); ?>" placeholder="Book, laptop...">
          </div>
          <div class="col-6 col-md-2">
            <label for="category" class="form-label small fw-semibold">Category</label>
            <select class="form-select" id="category" name="category">
              <option value="">All</option>
              <?php foreach (getCategories($conn) as $cat): ?>
                <?php $name = $cat['category_name']; ?>
                <option value="<?php echo sanitize($name); ?>" <?php echo $category === $name ? 'selected' : ''; ?>>
                  <?php echo sanitize($name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label for="min_price" class="form-label small fw-semibold">Min Price</label>
            <input type="number" min="0" step="0.01" class="form-control" id="min_price" name="min_price"
                   value="<?php echo $minPrice !== null && $minPrice !== false ? $minPrice : ''; ?>">
          </div>
          <div class="col-6 col-md-2">
            <label for="max_price" class="form-label small fw-semibold">Max Price</label>
            <input type="number" min="0" step="0.01" class="form-control" id="max_price" name="max_price"
                   value="<?php echo $maxPrice !== null && $maxPrice !== false ? $maxPrice : ''; ?>">
          </div>
          <div class="col-6 col-md-2">
            <label for="sort" class="form-label small fw-semibold">Sort By</label>
            <select class="form-select" id="sort" name="sort">
              <option value="latest"     <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
              <option value="price_low"  <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
              <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
            </select>
          </div>
          <div class="col-12 d-flex gap-2 mt-2">
            <button type="submit" class="btn btn-brand"><i class="bi bi-search"></i> Search</button>
            <a href="<?php echo BASE_URL; ?>/search.php" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>

      <p class="text-muted small mb-3">
        <?php echo $resultCount; ?> product<?php echo $resultCount === 1 ? '' : 's'; ?> found
        <?php if ($keyword !== ''): ?> for "<strong><?php echo sanitize($keyword); ?></strong>"<?php endif; ?>
      </p>

      <div class="row g-3">
        <?php if ($resultCount === 0): ?>
          <div class="col-12">
            <div class="empty-state bg-white rounded-3 border">
              <i class="bi bi-search" style="font-size:2.5rem;"></i>
              <p class="mt-2 mb-0">No products matched your search. Try different keywords or filters.</p>
            </div>
          </div>
        <?php else: ?>
          <?php while ($product = mysqli_fetch_assoc($results)): ?>
            <?php require __DIR__ . '/includes/product_card.php'; ?>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
