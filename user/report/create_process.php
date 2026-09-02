<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$productId   = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$reason      = $_POST['reason'] ?? '';
$description = trim($_POST['description'] ?? '');
$userId      = (int) $_SESSION['user_id'];

$validReasons = ['Fake Product', 'Spam', 'Scam', 'Wrong Information', 'Other'];

$product = fetchOne($conn, "SELECT product_id, seller_id FROM Product WHERE product_id = ?", 'i', [$productId]);

if (!$product || !in_array($reason, $validReasons, true) || (int) $product['seller_id'] === $userId) {
    $_SESSION['report_error'] = 'This product could not be reported.';
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Don't let the same student report the same product twice while it is still pending
$existing = fetchOne($conn,
    "SELECT report_id FROM Report WHERE product_id = ? AND reported_by = ? AND status = 'Pending'",
    'ii', [$productId, $userId]);

if ($existing) {
    $_SESSION['report_error'] = 'You have already reported this product. An admin is reviewing it.';
    header('Location: ' . BASE_URL . '/product.php?id=' . $productId);
    exit;
}

$sql = "INSERT INTO Report (product_id, reported_by, reason, description) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'iiss', $productId, $userId, $reason, $description);

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['report_success'] = 'Thank you. Your report has been sent to the admin team.';
} else {
    $_SESSION['report_error'] = 'Could not submit your report. Please try again.';
}

header('Location: ' . BASE_URL . '/product.php?id=' . $productId);
exit;
