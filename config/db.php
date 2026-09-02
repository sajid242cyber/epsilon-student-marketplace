<?php
/**
 * MySQL database connection (mysqli).
 * Matches the credentials used by a default XAMPP installation.
 */

/*
 * Turn off mysqli's automatic exceptions so that failed queries simply
 * return false. Every query in this project checks that return value and
 * shows a friendly message, which keeps the code simple and readable.
 * This also lets the validation errors raised by our stored procedures
 * (for example "You cannot bid on your own product") be displayed to
 * the user instead of crashing the page.
 */
mysqli_report(MYSQLI_REPORT_OFF);

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'epsilon_db';

$conn = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
