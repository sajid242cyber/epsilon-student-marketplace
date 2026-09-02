<?php
/**
 * Lets an admin remove a listing that breaks the marketplace rules.
 * Sold products are kept so that past transactions stay intact.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$returnUrl = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/admin/products/index.php');

if ($productId) {
    // Read the image filenames first so the files can be cleared from disk
    $imgStmt = mysqli_prepare($conn, "SELECT image_path FROM ProductImage WHERE product_id = ?");
    mysqli_stmt_bind_param($imgStmt, 'i', $productId);
    mysqli_stmt_execute($imgStmt);
    $images = mysqli_stmt_get_result($imgStmt);

    $files = [];
    while ($row = mysqli_fetch_assoc($images)) {
        $files[] = $row['image_path'];
    }

    $delStmt = mysqli_prepare($conn, "DELETE FROM Product WHERE product_id = ? AND status <> 'Sold'");
    mysqli_stmt_bind_param($delStmt, 'i', $productId);
    mysqli_stmt_execute($delStmt);

    if (mysqli_stmt_affected_rows($delStmt) > 0) {
        foreach ($files as $file) {
            $path = UPLOAD_PATH . '/' . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }
        $_SESSION['admin_success'] = 'The product listing has been deleted.';
    } else {
        $_SESSION['admin_error'] = 'That product could not be deleted (sold items are kept for records).';
    }
}

header('Location: ' . $returnUrl);
exit;
