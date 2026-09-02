<?php
/**
 * Marks one notification as read, or every notification when ?all=1 is used.
 *
 * For a single notification it then forwards the user to whatever the
 * notification was about - the product page or the transaction page - which
 * is what makes the notifications in the list clickable.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

// "Mark all as read" - nothing to open afterwards, so stay on the list
if (isset($_GET['all'])) {
    $stmt = mysqli_prepare($conn, "UPDATE Notification SET is_read = 1 WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    header('Location: ' . BASE_URL . '/user/notification/index.php');
    exit;
}

$notificationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$notificationId) {
    header('Location: ' . BASE_URL . '/user/notification/index.php');
    exit;
}

// The user_id check makes sure a user can only open their own notifications
$notification = fetchOne($conn,
    "SELECT product_id, transaction_id FROM Notification WHERE notification_id = ? AND user_id = ?",
    'ii', [$notificationId, $userId]);

if (!$notification) {
    header('Location: ' . BASE_URL . '/user/notification/index.php');
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE Notification SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, 'ii', $notificationId, $userId);
mysqli_stmt_execute($stmt);

// Send the user to whatever the notification was about
if ($notification['transaction_id']) {
    header('Location: ' . BASE_URL . '/user/transaction/details.php?id=' . (int) $notification['transaction_id']);
} elseif ($notification['product_id']) {
    header('Location: ' . BASE_URL . '/product.php?id=' . (int) $notification['product_id'] . '#bid');
} else {
    // Older notifications have no target, so just go back to the list
    header('Location: ' . BASE_URL . '/user/notification/index.php');
}
exit;
