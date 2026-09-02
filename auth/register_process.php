<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/config/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/PROJECT12/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

// Collect and clean the submitted fields
$student_id = trim($_POST['student_id'] ?? '');
$full_name  = trim($_POST['full_name'] ?? '');
$department = trim($_POST['department'] ?? '');
$batch      = trim($_POST['batch'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$password   = $_POST['password'] ?? '';
$confirm    = $_POST['confirm_password'] ?? '';

// Basic server-side validation
if ($student_id === '' || $full_name === '' || $department === '' || $batch === '' ||
    $email === '' || $phone === '' || $password === '' || $confirm === '') {
    $_SESSION['register_error'] = 'Please fill in all fields.';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = 'Please enter a valid email address.';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

// The department must be one from the fixed list, not something typed in
if (!in_array($department, DEPARTMENTS, true)) {
    $_SESSION['register_error'] = 'Please choose your department from the list.';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

// Bangladeshi mobile numbers are exactly 11 digits, e.g. 01712345678
if (!preg_match('/^[0-9]{11}$/', $phone)) {
    $_SESSION['register_error'] = 'Phone number must be exactly 11 digits (for example 01712345678).';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['register_error'] = 'Password must be at least 6 characters long.';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

// Require at least one symbol so passwords are not plain words or numbers
if (!preg_match('/[^A-Za-z0-9]/', $password)) {
    $_SESSION['register_error'] = 'Password must include at least one symbol, for example ! @ # $ % & * ? _ -';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['register_error'] = 'Passwords do not match.';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

// Make sure the student ID or email is not already registered
$checkSql = "SELECT user_id FROM User WHERE student_id = ? OR email = ?";
$checkStmt = mysqli_prepare($conn, $checkSql);
mysqli_stmt_bind_param($checkStmt, 'ss', $student_id, $email);
mysqli_stmt_execute($checkStmt);
$existing = mysqli_stmt_get_result($checkStmt);

if (mysqli_num_rows($existing) > 0) {
    $_SESSION['register_error'] = 'An account with this Student ID or Email already exists.';
    header('Location: ' . BASE_URL . '/auth/register.php');
    exit;
}

// Create the account
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertSql = "INSERT INTO User (student_id, full_name, department, batch, email, phone, password)
              VALUES (?, ?, ?, ?, ?, ?, ?)";
$insertStmt = mysqli_prepare($conn, $insertSql);
mysqli_stmt_bind_param($insertStmt, 'sssssss', $student_id, $full_name, $department, $batch, $email, $phone, $hashedPassword);

if (mysqli_stmt_execute($insertStmt)) {
    $_SESSION['register_success'] = 'Account created successfully! Please login.';
    header('Location: ' . BASE_URL . '/auth/login.php');
} else {
    $_SESSION['register_error'] = 'Something went wrong. Please try again.';
    header('Location: ' . BASE_URL . '/auth/register.php');
}
exit;
