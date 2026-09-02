<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/user/product/my_products.php');
    exit;
}

$productId   = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$userId      = (int) $_SESSION['user_id'];
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');

// The seller either picked a category or typed a new one ("new" was selected)
$categoryId  = resolveCategoryId($conn, $_POST['category_id'] ?? '', $_POST['new_category'] ?? '');
$price       = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$condition   = $_POST['condition'] ?? '';
$status      = $_POST['status'] ?? '';

$validConditions = ['New', 'Like New', 'Good', 'Fair', 'Poor'];
$validStatuses   = ['Available', 'Pending', 'Sold'];

if (!$productId || $title === '' || $description === '' || !$categoryId || $price === false || $price < 0 ||
    !in_array($condition, $validConditions, true) || !in_array($status, $validStatuses, true)) {
    $_SESSION['product_error'] = 'Please fill in every field with valid information.';
    header('Location: ' . BASE_URL . '/user/product/edit.php?id=' . $productId);
    exit;
}

// The WHERE clause also checks seller_id so a user can never edit someone else's listing
$sql = "UPDATE Product
        SET title = ?, category_id = ?, description = ?, price = ?, `condition` = ?, status = ?
        WHERE product_id = ? AND seller_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'sisdssii', $title, $categoryId, $description, $price, $condition, $status, $productId, $userId);
mysqli_stmt_execute($stmt);

// Remove any images the seller ticked for deletion
if (!empty($_POST['remove_images'])) {
    foreach ($_POST['remove_images'] as $imageId) {
        $imageId = (int) $imageId;

        // Look up the filename first so the file itself can be deleted from disk
        $findSql = "SELECT pi.image_path FROM ProductImage pi
                    INNER JOIN Product p ON pi.product_id = p.product_id
                    WHERE pi.image_id = ? AND p.seller_id = ?";
        $findStmt = mysqli_prepare($conn, $findSql);
        mysqli_stmt_bind_param($findStmt, 'ii', $imageId, $userId);
        mysqli_stmt_execute($findStmt);
        $img = mysqli_fetch_assoc(mysqli_stmt_get_result($findStmt));

        if ($img) {
            $filePath = UPLOAD_PATH . '/' . $img['image_path'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
            $delStmt = mysqli_prepare($conn, "DELETE FROM ProductImage WHERE image_id = ?");
            mysqli_stmt_bind_param($delStmt, 'i', $imageId);
            mysqli_stmt_execute($delStmt);
        }
    }
}

// Save any newly uploaded images
if (!empty($_FILES['product_images'])) {
    saveProductImages($conn, $productId, $_FILES['product_images']);
}

header('Location: ' . BASE_URL . '/product.php?id=' . $productId);
exit;
