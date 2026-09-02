<?php
/**
 * Renders a single product card for the marketplace feed / search results.
 * Expects $product to be one row from the vw_product_feed view.
 */

$imageUrl = $product['primary_image']
    ? UPLOAD_URL . '/' . $product['primary_image']
    : null;

$detailsUrl  = BASE_URL . '/product.php?id=' . (int) $product['product_id'];
$wishlistUrl = isLoggedIn()
    ? BASE_URL . '/user/wishlist/toggle.php?product_id=' . (int) $product['product_id']
    : BASE_URL . '/auth/login.php';

// A short hint for the hatched placeholder when a listing has no photo yet
$placeholderLabels = [
    'Books'       => 'book cover',
    'Laptop'      => 'laptop photo',
    'Phone'       => 'phone photo',
    'Calculator'  => 'calculator',
    'Accessories' => 'accessory',
];
$placeholderLabel = $placeholderLabels[$product['category_name']] ?? 'no photo';
?>
<!-- 2 across on a phone, 3 across from tablet width upwards -->
<div class="col-6 col-md-4">
  <div class="product-card">
    <!-- The heart is a sibling of the photo link, never nested inside it: a link
         inside a link is invalid HTML and browsers move it out of the card. -->
    <div class="product-media">
      <a href="<?php echo $detailsUrl; ?>" class="product-img-wrap d-block">
        <?php if ($imageUrl): ?>
          <img src="<?php echo $imageUrl; ?>" alt="<?php echo sanitize($product['title']); ?>" loading="lazy">
        <?php else: ?>
          <!-- No photo uploaded: a hatched panel keeps the grid even -->
          <span class="product-img-empty"><?php echo $placeholderLabel; ?></span>
        <?php endif; ?>
      </a>

      <?php if ($product['status'] === 'Pending'): ?>
        <span class="product-flag"><i class="bi bi-lock-fill"></i> Reserved</span>
      <?php endif; ?>

      <a href="<?php echo $wishlistUrl; ?>" class="product-save" title="Save for later">
        <i class="bi bi-heart"></i>
      </a>
    </div>

    <div class="card-body">
      <a href="<?php echo $detailsUrl; ?>" class="product-title"><?php echo sanitize($product['title']); ?></a>

      <div class="product-tagrow">
        <span class="cx-chip"><?php echo $product['condition']; ?></span>
        <span class="product-meta">
          <?php echo sanitize($product['category_name']); ?> &middot; <?php echo time_ago($product['created_at']); ?>
        </span>
      </div>

      <div class="product-price">&#2547; <?php echo number_format($product['price'], 0); ?></div>

      <div class="product-seller">
        <i class="bi bi-person-circle"></i> <?php echo sanitize($product['seller_name']); ?>
        <?php if ($product['seller_rating']): ?>
          <span class="product-rating"><i class="bi bi-star-fill"></i> <?php echo $product['seller_rating']; ?></span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
