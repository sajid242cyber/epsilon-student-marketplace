<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/user/product/post.php');
    exit;
}

$sellerId    = (int) $_SESSION['user_id'];
$title       = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price       = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
$condition   = $_POST['condition'] ?? '';

// The seller either picked a category or typed a new one ("new" was selected)
$categoryId = resolveCategoryId($conn, $_POST['category_id'] ?? '', $_POST['new_category'] ?? '');

$validConditions = ['New', 'Like New', 'Good', 'Fair', 'Poor'];

if ($title === '' || $description === '' || !$categoryId || $price === false || $price < 0 ||
    !in_array($condition, $validConditions, true)) {
    $_SESSION['product_error'] = 'Please fill in every field with valid information.';
    header('Location: ' . BASE_URL . '/user/product/post.php');
    exit;
}

$sql = "INSERT INTO Product (seller_id, category_id, title, description, price, `condition`)
        VALUES (?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iissds', $sellerId, $categoryId, $title, $description, $price, $condition);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['product_error'] = 'Could not publish the product. Please try again.';
    header('Location: ' . BASE_URL . '/user/product/post.php');
    exit;
}

$productId = mysqli_insert_id($conn);

// Save any uploaded images (optional - a listing can be published without them)
if (!empty($_FILES['product_images'])) {
    saveProductImages($conn, $productId, $_FILES['product_images']);
}

header('Location: ' . BASE_URL . '/product.php?id=' . $productId);
exit;
