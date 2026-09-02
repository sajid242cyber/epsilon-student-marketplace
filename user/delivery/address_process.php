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
$userId        = (int) $_SESSION['user_id'];
$receiverName  = trim($_POST['receiver_name'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$district      = trim($_POST['district'] ?? '');
$area          = trim($_POST['area'] ?? '');
$fullAddress   = trim($_POST['full_address'] ?? '');

$txn = getTransactionForUser($conn, $transactionId, $userId);

if (!$txn || (int) $txn['buyer_id'] !== $userId || $txn['payment_status'] === 'Paid') {
    $_SESSION['delivery_error'] = 'You cannot set the delivery address for this transaction.';
    header('Location: ' . BASE_URL . '/user/transaction/index.php');
    exit;
}

if ($receiverName === '' || $phone === '' || $district === '' || $area === '' || $fullAddress === '') {
    $_SESSION['delivery_error'] = 'Please fill in every address field.';
    header('Location: ' . BASE_URL . '/user/delivery/address.php?transaction_id=' . $transactionId);
    exit;
}

$existing = fetchOne($conn, "SELECT address_id FROM DeliveryAddress WHERE transaction_id = ?", 'i', [$transactionId]);

if ($existing) {
    $sql = "UPDATE DeliveryAddress
            SET receiver_name = ?, phone = ?, district = ?, area = ?, full_address = ?
            WHERE transaction_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssssi', $receiverName, $phone, $district, $area, $fullAddress, $transactionId);
} else {
    $sql = "INSERT INTO DeliveryAddress (transaction_id, receiver_name, phone, district, area, full_address)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'isssss', $transactionId, $receiverName, $phone, $district, $area, $fullAddress);
}

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['delivery_success'] = 'Delivery address saved.';
} else {
    $_SESSION['delivery_error'] = 'Could not save the address. Please try again.';
}

header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . $transactionId);
exit;
