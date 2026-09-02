<?php
/**
 * Resolves a report by removing the offending product listing.
 * Every other report on the same product is resolved at the same time.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$reportId) {
    header('Location: ' . BASE_URL . '/admin/reports/index.php');
    exit;
}

$report = fetchOne($conn, "SELECT product_id FROM Report WHERE report_id = ?", 'i', [$reportId]);

if (!$report) {
    $_SESSION['admin_error'] = 'That report no longer exists.';
    header('Location: ' . BASE_URL . '/admin/reports/index.php');
    exit;
}

$productId = (int) $report['product_id'];

// Grab the image filenames before the rows disappear via ON DELETE CASCADE
$imgStmt = mysqli_prepare($conn, "SELECT image_path FROM ProductImage WHERE product_id = ?");
mysqli_stmt_bind_param($imgStmt, 'i', $productId);
mysqli_stmt_execute($imgStmt);
$images = mysqli_stmt_get_result($imgStmt);

$files = [];
while ($row = mysqli_fetch_assoc($images)) {
    $files[] = $row['image_path'];
}

/*
 * Deleting the product would also delete its reports (ON DELETE CASCADE),
 * so a sold product is kept and only the report is closed instead.
 */
$product = fetchOne($conn, "SELECT status FROM Product WHERE product_id = ?", 'i', [$productId]);

if ($product && $product['status'] !== 'Sold') {
    $delStmt = mysqli_prepare($conn, "DELETE FROM Product WHERE product_id = ?");
    mysqli_stmt_bind_param($delStmt, 'i', $productId);
    mysqli_stmt_execute($delStmt);

    foreach ($files as $file) {
        $path = UPLOAD_PATH . '/' . $file;
        if (is_file($path)) {
            unlink($path);
        }
    }

    $_SESSION['admin_success'] = 'The reported product was removed and the report is resolved.';
} else {
    // Sold item: keep the record, just close every open report on it
    $stmt = mysqli_prepare($conn, "UPDATE Report SET status = 'Resolved' WHERE product_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);

    $_SESSION['admin_success'] = 'This product is already sold, so the report was resolved without deleting it.';
}

header('Location: ' . BASE_URL . '/admin/reports/index.php');
exit;
