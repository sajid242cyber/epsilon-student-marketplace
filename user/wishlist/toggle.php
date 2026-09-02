<?php
/**
 * Adds a product to the wishlist, or removes it if it is already saved.
 * The same link is therefore used for both actions.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
$userId    = (int) $_SESSION['user_id'];

// Where to send the user back to after the change
$returnUrl = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/index.php');

if (!$productId) {
    header('Location: ' . $returnUrl);
    exit;
}

$existing = fetchOne($conn, "SELECT wishlist_id FROM Wishlist WHERE user_id = ? AND product_id = ?", 'ii', [$userId, $productId]);

if ($existing) {
    $stmt = mysqli_prepare($conn, "DELETE FROM Wishlist WHERE wishlist_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $existing['wishlist_id']);
    mysqli_stmt_execute($stmt);
    $_SESSION['wishlist_success'] = 'Removed from your wishlist.';
} else {
    $stmt = mysqli_prepare($conn, "INSERT INTO Wishlist (user_id, product_id) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $productId);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['wishlist_success'] = 'Added to your wishlist.';
    } else {
        $_SESSION['wishlist_error'] = 'Could not add this product to your wishlist.';
    }
}

header('Location: ' . $returnUrl);
exit;
