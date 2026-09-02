<?php
/**
 * Bans an active student, or unbans a banned one.
 * A banned student can no longer log in (checked in auth/login_process.php).
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

// Only an admin may reach this script
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($userId) {
    // The role check stops an admin account from ever being banned
    $sql = "UPDATE User
            SET status = IF(status = 'active', 'banned', 'active')
            WHERE user_id = ? AND role = 'student'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        $_SESSION['admin_success'] = 'The student account status has been updated.';
    } else {
        $_SESSION['admin_error'] = 'That account could not be updated.';
    }
}

header('Location: ' . BASE_URL . '/admin/users/index.php');
exit;
