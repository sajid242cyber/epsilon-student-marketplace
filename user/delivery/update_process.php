<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

$transactionId  = filter_input(INPUT_POST, 'transaction_id', FILTER_VALIDATE_INT);
$status         = $_POST['delivery_status'] ?? '';
$trackingNumber = trim($_POST['tracking_number'] ?? '');
$userId         = (int) $_SESSION['user_id'];

$validStatuses = ['Pending', 'Packed', 'Shipped', 'Delivered'];

$txn = getTransactionForUser($conn, $transactionId, $userId);

// Only the seller updates the shipment progress
if (!$txn || (int) $txn['seller_id'] !== $userId || !in_array($status, $validStatuses, true)) {
    $_SESSION['delivery_error'] = 'You are not allowed to update this delivery.';
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

$pickupAddress = trim($_POST['pickup_address'] ?? '');

/*
 * Updating this row fires trg_delivery_after_update, which sends the buyer
 * a "Delivery Update" notification whenever the status actually changes.
 */
$sql = "UPDATE Delivery
        SET delivery_status = ?, tracking_number = ?, pickup_address = ?
        WHERE transaction_id = ?";
$stmt = mysqli_prepare($conn, $sql);
$trackingValue = ($trackingNumber === '')  ? null : $trackingNumber;
$pickupValue   = ($pickupAddress === '')   ? null : $pickupAddress;
mysqli_stmt_bind_param($stmt, 'sssi', $status, $trackingValue, $pickupValue, $transactionId);

if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['delivery_error'] = 'Could not update the delivery. The tracking number may already be in use.';
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
    exit;
}

$_SESSION['delivery_success'] = 'Delivery updated to ' . $status . '.';

/*
 * Cash on Delivery: the buyer hands over the money at the moment the item is
 * delivered, so that is when the payment becomes Paid. Setting it here fires
 * trg_payment_after_update, which completes the transaction, marks the product
 * Sold, generates the invoice and notifies both students.
 */
if ($status === 'Delivered' && $txn['payment_status'] === 'Pending') {
    $paidSql = "UPDATE Payment SET payment_status = 'Paid', paid_at = NOW()
                WHERE transaction_id = ? AND payment_status = 'Pending'";
    $paidStmt = mysqli_prepare($conn, $paidSql);
    mysqli_stmt_bind_param($paidStmt, 'i', $transactionId);
    mysqli_stmt_execute($paidStmt);

    $_SESSION['delivery_success'] = 'Delivered and paid. The invoice is now ready.';
}

header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
exit;
