<?php
/**
 * The seller answers a bid with a different price.
 * The bid then sits as "Countered" until the buyer accepts or rejects it.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$bidId  = filter_input(INPUT_POST, 'bid_id', FILTER_VALIDATE_INT);
$amount = filter_input(INPUT_POST, 'counter_amount', FILTER_VALIDATE_FLOAT);
$userId = (int) $_SESSION['user_id'];

if (!$bidId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Only the seller who owns the product may counter a bid on it
$row = fetchOne($conn,
    "SELECT p.product_id
     FROM Bid b
     INNER JOIN Product p ON b.product_id = p.product_id
     WHERE b.bid_id = ? AND p.seller_id = ? AND b.status = 'Pending'",
    'ii', [$bidId, $userId]);

if (!$row) {
    $_SESSION['bid_error'] = 'You are not allowed to counter this bid.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$productId = (int) $row['product_id'];

if ($amount === false || $amount <= 0) {
    $_SESSION['bid_error'] = 'Please enter a valid counter amount.';
    header('Location: ' . BASE_URL . '/product.php?id=' . $productId . '#bid');
    exit;
}

// sp_counter_bid re-checks that the bid is still pending before saving
$stmt = mysqli_prepare($conn, 'CALL sp_counter_bid(?, ?)');
mysqli_stmt_bind_param($stmt, 'id', $bidId, $amount);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['bid_success'] = 'Counter offer of Tk ' . number_format($amount, 2) . ' sent to the buyer.';
} else {
    $_SESSION['bid_error'] = mysqli_stmt_error($stmt);
}

header('Location: ' . BASE_URL . '/product.php?id=' . $productId . '#bid');
exit;
