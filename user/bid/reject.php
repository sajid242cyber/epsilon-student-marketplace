<?php
/**
 * Rejecting a bid happens in two situations:
 *   - the seller turns down a buyer's bid, or
 *   - the buyer turns down the seller's counter offer.
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
    $_SESSION['bid_error'] = 'You are not allowed to reject this bid.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$productId = (int) $row['product_id'];

$stmt = mysqli_prepare($conn, 'CALL sp_reject_bid(?)');
mysqli_stmt_bind_param($stmt, 'i', $bidId);
mysqli_stmt_execute($stmt);

$_SESSION['bid_success'] = 'Bid rejected.';
header('Location: ' . BASE_URL . '/product.php?id=' . $productId . '#bid');
exit;
