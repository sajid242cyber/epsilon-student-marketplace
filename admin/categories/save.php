<?php
/**
 * Handles both adding a new category and renaming an existing one.
 */
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$name   = trim($_POST['category_name'] ?? '');

if ($name === '') {
    $_SESSION['admin_error'] = 'Please enter a category name.';
    header('Location: ' . BASE_URL . '/admin/categories/index.php');
    exit;
}

// The category_name column is UNIQUE, so check for a duplicate first
// to show a friendly message instead of a database error.
$duplicateSql = "SELECT category_id FROM Category WHERE category_name = ?";
$duplicate = fetchOne($conn, $duplicateSql, 's', [$name]);

if ($action === 'add') {
    if ($duplicate) {
        $_SESSION['admin_error'] = 'That category already exists.';
    } else {
        $stmt = mysqli_prepare($conn, "INSERT INTO Category (category_name) VALUES (?)");
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        $_SESSION['admin_success'] = 'Category added.';
    }
} elseif ($action === 'rename') {
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);

    if (!$categoryId) {
        $_SESSION['admin_error'] = 'Invalid category.';
    } elseif ($duplicate && (int) $duplicate['category_id'] !== $categoryId) {
        $_SESSION['admin_error'] = 'Another category already uses that name.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE Category SET category_name = ? WHERE category_id = ?");
        mysqli_stmt_bind_param($stmt, 'si', $name, $categoryId);
        mysqli_stmt_execute($stmt);
        $_SESSION['admin_success'] = 'Category updated.';
    }
}

header('Location: ' . BASE_URL . '/admin/categories/index.php');
exit;
