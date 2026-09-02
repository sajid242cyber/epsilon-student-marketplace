<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$userId    = (int) $_SESSION['user_id'];

if (!$productId) {
    header('Location: ' . BASE_URL . '/user/product/my_products.php');
    exit;
}

// Collect the image filenames first so the files can be removed from disk.
// (The ProductImage rows themselves are removed by ON DELETE CASCADE.)
$imgSql = "SELECT pi.image_path FROM ProductImage pi
           INNER JOIN Product p ON pi.product_id = p.product_id
           WHERE pi.product_id = ? AND p.seller_id = ?";
$imgStmt = mysqli_prepare($conn, $imgSql);
mysqli_stmt_bind_param($imgStmt, 'ii', $productId, $userId);
mysqli_stmt_execute($imgStmt);
$images = mysqli_stmt_get_result($imgStmt);

$filesToDelete = [];
while ($row = mysqli_fetch_assoc($images)) {
    $filesToDelete[] = $row['image_path'];
}

// Deleting is only allowed for the owner and only while nothing has been sold yet
$delSql = "DELETE FROM Product WHERE product_id = ? AND seller_id = ? AND status <> 'Sold'";
$delStmt = mysqli_prepare($conn, $delSql);
mysqli_stmt_bind_param($delStmt, 'ii', $productId, $userId);
mysqli_stmt_execute($delStmt);

if (mysqli_stmt_affected_rows($delStmt) > 0) {
    foreach ($filesToDelete as $file) {
        $filePath = UPLOAD_PATH . '/' . $file;
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
    $_SESSION['product_success'] = 'Product deleted successfully.';
} else {
    $_SESSION['product_error'] = 'This product could not be deleted.';
}

header('Location: ' . BASE_URL . '/user/product/my_products.php');
exit;
