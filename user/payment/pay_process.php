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
$method        = $_POST['payment_method'] ?? '';
$userId        = (int) $_SESSION['user_id'];

// Cash on Delivery is the only method the marketplace offers
$validMethods = ['Cash on Delivery'];

$txn = getTransactionForUser($conn, $transactionId, $userId);

// Only the buyer can pay, the method must be one we support,
// and a transaction can never be paid twice.
if (!$txn || (int) $txn['buyer_id'] !== $userId || !in_array($method, $validMethods, true)) {
    $_SESSION['payment_error'] = 'Invalid payment request.';
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}
if ($txn['payment_status'] === 'Paid') {
    $_SESSION['payment_error'] = 'This transaction has already been paid.';
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
    exit;
}

$amount = $txn['bid_amount'];

/*
 * With Cash on Delivery no money changes hands yet, so the payment is only
 * recorded as Pending. It becomes Paid when the seller marks the delivery as
 * Delivered, and that is what fires trg_payment_after_update to mark the
 * product Sold and generate the invoice.
 */
$insertSql = "INSERT INTO Payment (transaction_id, payment_method, amount, payment_status)
              VALUES (?, ?, ?, 'Pending')";
$insertStmt = mysqli_prepare($conn, $insertSql);
mysqli_stmt_bind_param($insertStmt, 'isd', $transactionId, $method, $amount);

if (!mysqli_stmt_execute($insertStmt)) {
    $_SESSION['payment_error'] = 'Could not place the order. Please try again.';
    header('Location: ' . BASE_URL . '/user/payment/pay.php?transaction_id=' . $transactionId);
    exit;
}

// Open the delivery record so the seller can add a pickup point and track it
$deliverySql = "INSERT INTO Delivery (transaction_id, delivery_status) VALUES (?, 'Pending')";
$deliveryStmt = mysqli_prepare($conn, $deliverySql);
mysqli_stmt_bind_param($deliveryStmt, 'i', $transactionId);
mysqli_stmt_execute($deliveryStmt);

// Let the seller know an order is waiting for them
$sellerId = (int) $txn['seller_id'];
$notifySql = "INSERT INTO Notification (user_id, type, transaction_id, message)
              VALUES (?, 'Delivery Update', ?, ?)";
$notifyStmt = mysqli_prepare($conn, $notifySql);
$notifyMsg = 'New cash-on-delivery order. Share a pickup point with the buyer.';
mysqli_stmt_bind_param($notifyStmt, 'iis', $sellerId, $transactionId, $notifyMsg);
mysqli_stmt_execute($notifyStmt);

$_SESSION['payment_success'] = 'Order confirmed! Pay in cash when you collect the item.';
header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
exit;
