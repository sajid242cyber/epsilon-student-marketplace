<?php
/**
 * Accepting a bid happens in two situations:
 *   - the seller accepts a buyer's original bid, or
 *   - the buyer accepts the seller's counter offer.
 * Either way the agreed deal becomes a Transaction.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$bidId  = filter_input(INPUT_GET, 'bid_id', FILTER_VALIDATE_INT);
$userId = (int) $_SESSION['user_id'];

if (!$bidId) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

/*
 * The WHERE clause allows exactly the two cases above and nothing else,
 * so a crafted link cannot accept a bid on someone else's behalf.
 */
$sql = "SELECT p.product_id
        FROM Bid b
        INNER JOIN Product p ON b.product_id = p.product_id
        WHERE b.bid_id = ?
          AND (
                (p.seller_id = ? AND b.status = 'Pending')
             OR (b.buyer_id  = ? AND b.status = 'Countered')
          )";
$row = fetchOne($conn, $sql, 'iii', [$bidId, $userId, $userId]);

if (!$row) {
    $_SESSION['bid_error'] = 'You are not allowed to accept this bid.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$productId = (int) $row['product_id'];

// sp_accept_bid rejects the other open bids, marks the product Pending
// and creates the Transaction - all inside one database transaction.
$stmt = mysqli_prepare($conn, 'CALL sp_accept_bid(?)');
mysqli_stmt_bind_param($stmt, 'i', $bidId);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['bid_success'] = 'Deal agreed. All other open bids were rejected and a transaction has been created.';
} else {
    $_SESSION['bid_error'] = mysqli_stmt_error($stmt);
}

header('Location: ' . BASE_URL . '/product.php?id=' . $productId . '#bid');
exit;
