<?php
/**
 * Common page header.
 * Expects (optionally) $pageTitle to be set by the including page.
 * config/config.php, config/db.php and includes/functions.php must
 * already be required by the including page before this file.
 */
if (!isset($pageTitle)) {
    $pageTitle = 'Epsilon';
} else {
    $pageTitle = $pageTitle . ' - Epsilon';
}

/*
 * Browsers keep a copy of our CSS and JavaScript to load pages faster, but
 * that means an edited file can keep showing the old version. Adding the
 * file's "last changed" time to the address makes the browser see it as a
 * new file whenever we actually change it, and reuse its copy when we don't.
 */
$cssVersion = filemtime(ROOT_PATH . '/assets/css/style.css');
$jsVersion  = filemtime(ROOT_PATH . '/assets/js/script.js');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="icon" type="image/svg+xml" href="<?php echo BASE_URL; ?>/assets/images/logo.svg">

    <!-- Bootstrap 5 (self-hosted) -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/bootstrap/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/icons/bootstrap-icons.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo $cssVersion; ?>">
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

<main>
