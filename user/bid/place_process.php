<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$amount    = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT);
$buyerId   = (int) $_SESSION['user_id'];

if (!$productId || $amount === false || $amount <= 0) {
    $_SESSION['bid_error'] = 'Please enter a valid bid amount.';
    header('Location: ' . BASE_URL . '/product.php?id=' . $productId . '#bid');
    exit;
}

// sp_place_bid checks that the product exists, is still Available,
// and that the buyer is not the seller. It raises an error otherwise.
$stmt = mysqli_prepare($conn, 'CALL sp_place_bid(?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'iid', $productId, $buyerId, $amount);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['bid_success'] = 'Your bid has been placed successfully.';
} else {
    $_SESSION['bid_error'] = mysqli_stmt_error($stmt);
}

header('Location: ' . BASE_URL . '/product.php?id=' . $productId . '#bid');
exit;
