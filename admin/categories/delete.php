<?php
/**
 * Deletes a category, but only when no product is using it.
 * (The foreign key uses ON DELETE RESTRICT as a second safety net.)
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($categoryId) {
    $inUse = fetchOne($conn, "SELECT COUNT(*) AS c FROM Product WHERE category_id = ?", 'i', [$categoryId]);

    if ((int) $inUse['c'] > 0) {
        $_SESSION['admin_error'] = 'This category cannot be deleted because products are using it.';
    } else {
        $stmt = mysqli_prepare($conn, "DELETE FROM Category WHERE category_id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $categoryId);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['admin_success'] = 'Category deleted.';
        } else {
            $_SESSION['admin_error'] = 'That category could not be deleted.';
        }
    }
}

header('Location: ' . BASE_URL . '/admin/categories/index.php');
exit;
