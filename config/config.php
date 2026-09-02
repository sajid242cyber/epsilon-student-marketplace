<?php
/**
 * Global site configuration.
 * Included by every page before any output is sent.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL of the project as served by XAMPP (http://localhost/PROJECT12)
define('BASE_URL', '/PROJECT12');

// Absolute server path to the project root (C:\xampp\htdocs\PROJECT12)
define('ROOT_PATH', dirname(__DIR__));

// Upload folders
define('UPLOAD_URL', BASE_URL . '/assets/uploads/products');
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads/products');

/*
 * The categories the project starts with (created by database.sql).
 * Kept here for reference only - the site always reads the live list from
 * the Category table via getCategories(), because students can add their
 * own category while posting a product.
 */
define('DEFAULT_CATEGORIES', ['Books', 'Laptop', 'Phone', 'Calculator', 'Accessories', 'Others']);

/*
 * The departments a student can pick from when registering.
 *
 * This is a fixed list on purpose. If students typed it by hand, one would
 * write "CSE" and another "Computer Science and Engineering", and the same
 * department would end up stored under several different names.
 *
 * Keeping the list here needs no extra database table - the User table still
 * simply stores the chosen text in its existing department column.
 */
define('DEPARTMENTS', [
    'Computer Science and Engineering (CSE)',
    'Software Engineering (SWE)',
    'Business Administration (BBA)',
    'Electrical and Electronic Engineering (EEE)',
    'Civil Engineering (CE)',
    'Pharmacy',
    'English',
    'Law',
    'Architecture',
    'Textile Engineering',
    'Accounting',
    'Finance',
    'Journalism, Media and Communication (JMC)',
]);

date_default_timezone_set('Asia/Dhaka');

error_reporting(E_ALL);
ini_set('display_errors', 1);
