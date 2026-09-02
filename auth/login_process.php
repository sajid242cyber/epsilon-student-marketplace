<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$loginId  = trim($_POST['login_id'] ?? '');
$password = $_POST['password'] ?? '';

if ($loginId === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter your Email/Student ID and password.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$sql = "SELECT user_id, student_id, full_name, password, role, status
        FROM User WHERE email = ? OR student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'ss', $loginId, $loginId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = 'Invalid Email/Student ID or password.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

if ($user['status'] === 'banned') {
    $_SESSION['login_error'] = 'Your account has been suspended. Please contact the admin.';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Credentials are valid - start the session
$_SESSION['user_id']    = $user['user_id'];
$_SESSION['student_id'] = $user['student_id'];
$_SESSION['full_name']  = $user['full_name'];
$_SESSION['role']       = $user['role'];

if ($user['role'] === 'admin') {
    header('Location: ' . BASE_URL . '/admin/index.php');
    exit;
}

if (!empty($_SESSION['redirect_after_login'])) {
    $redirect = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . $redirect);
    exit;
}

header('Location: ' . BASE_URL . '/index.php');
exit;
