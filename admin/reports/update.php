<?php
/**
 * Changes the status of a report (Reviewed / Dismissed) without
 * touching the product itself.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$reportId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$status   = $_GET['status'] ?? '';

$validStatuses = ['Pending', 'Reviewed', 'Resolved', 'Dismissed'];

if ($reportId && in_array($status, $validStatuses, true)) {
    $stmt = mysqli_prepare($conn, "UPDATE Report SET status = ? WHERE report_id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $status, $reportId);
    mysqli_stmt_execute($stmt);
    $_SESSION['admin_success'] = 'The report was marked as ' . $status . '.';
} else {
    $_SESSION['admin_error'] = 'Invalid report update.';
}

header('Location: ' . BASE_URL . '/admin/reports/index.php');
exit;
