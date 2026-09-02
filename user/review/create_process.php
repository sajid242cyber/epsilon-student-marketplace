<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

$transactionId = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
$rating        = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
$comment       = trim($_POST['comment'] ?? '');
$userId        = (int) $_SESSION['user_id'];

$txn = getTransactionForUser($conn, $transactionId, $userId);

if (!$txn || (int) $txn['buyer_id'] !== $userId || $rating === false || $rating < 1 || $rating > 5) {
    $_SESSION['review_error'] = 'Please choose a rating between 1 and 5.';
    header('Location: ' . BASE_URL . '/user/review/create.php?transaction_id=' . $transactionId);
    exit;
}

// Re-check the delivery status and the one-review rule on submit as well,
// so the checks cannot be skipped by posting the form directly
$delivery = fetchOne($conn, "SELECT delivery_status FROM Delivery WHERE transaction_id = ?", 'i', [$transactionId]);
$existing = fetchOne($conn, "SELECT review_id FROM Review WHERE transaction_id = ?", 'i', [$transactionId]);

if (!$delivery || $delivery['delivery_status'] !== 'Delivered' || $existing) {
    $_SESSION['review_error'] = 'This transaction cannot be reviewed.';
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
    exit;
}

$sellerId = (int) $txn['seller_id'];

$sql = "INSERT INTO Review (transaction_id, buyer_id, seller_id, rating, comment) VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iiiis', $transactionId, $userId, $sellerId, $rating, $comment);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['txn_success'] = 'Thank you! Your review has been posted.';
} else {
    $_SESSION['txn_error'] = 'Could not save your review. Please try again.';
}

header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
exit;
