<?php
/**
 * Shared helper functions used across the site.
 * Requires config/config.php and config/db.php to already be loaded.
 */

// Check whether a student is currently logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect guests to the login page, remembering where they wanted to go
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Clean up user-submitted text before displaying it
function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Turn a MySQL datetime into a human friendly "x minutes ago" string
function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('d M Y', $timestamp);
    }
}

// Count how many items are currently in the logged-in user's wishlist
function getWishlistCount($conn, $userId) {
    $sql = "SELECT COUNT(*) AS total FROM Wishlist WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return (int) $row['total'];
}

// Count how many notifications the logged-in user has not yet read
function getUnreadNotificationCount($conn, $userId) {
    $sql = "SELECT COUNT(*) AS total FROM Notification WHERE user_id = ? AND is_read = 0";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return (int) $row['total'];
}

/**
 * Returns every category as an array of rows.
 * Read live from the database so categories added by students
 * appear everywhere on the site straight away.
 */
function getCategories($conn) {
    $result = mysqli_query($conn, "SELECT category_id, category_name FROM Category ORDER BY category_name");
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
}

/**
 * The same list, plus how many listings are still up for sale in each one.
 * The sidebar shows these numbers beside every category name.
 *
 * A LEFT JOIN is used so a category with nothing in it still comes back, with
 * a count of zero, instead of disappearing from the menu.
 */
function getCategoriesWithCounts($conn) {
    $sql = "SELECT c.category_id, c.category_name,
                   COUNT(p.product_id) AS product_count
            FROM Category c
            LEFT JOIN Product p
                   ON p.category_id = c.category_id
                  AND p.status <> 'Sold'
            GROUP BY c.category_id, c.category_name
            ORDER BY c.category_name";
    $result = mysqli_query($conn, $sql);
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['product_count'] = (int) $row['product_count'];
        $categories[] = $row;
    }
    return $categories;
}

/**
 * Works out which category a product should be saved under.
 *
 * $selected is either an existing category_id, or the text "new" when the
 * seller chose "Add a new category" and typed their own name in $newName.
 *
 * Returns the category_id to use, or null when the input is not valid.
 */
function resolveCategoryId($conn, $selected, $newName) {
    // The seller picked an existing category from the list
    if ($selected !== 'new') {
        $categoryId = (int) $selected;
        $found = fetchOne($conn, "SELECT category_id FROM Category WHERE category_id = ?", 'i', [$categoryId]);
        return $found ? (int) $found['category_id'] : null;
    }

    // The seller is adding a brand new category
    $newName = trim($newName);

    if ($newName === '' || mb_strlen($newName) > 50) {
        return null;
    }

    /*
     * Reuse the category if it already exists. The column collation is
     * case-insensitive, so typing "books" matches the existing "Books"
     * instead of creating a near-duplicate.
     */
    $existing = fetchOne($conn, "SELECT category_id FROM Category WHERE category_name = ?", 's', [$newName]);
    if ($existing) {
        return (int) $existing['category_id'];
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO Category (category_name) VALUES (?)");
    mysqli_stmt_bind_param($stmt, 's', $newName);

    if (mysqli_stmt_execute($stmt)) {
        return mysqli_insert_id($conn);
    }

    // Another student created the same category a moment ago - use theirs
    $existing = fetchOne($conn, "SELECT category_id FROM Category WHERE category_name = ?", 's', [$newName]);
    return $existing ? (int) $existing['category_id'] : null;
}

/**
 * Runs a prepared SELECT and returns the first row, or null if there is none.
 * Keeps the one-row lookups throughout the project short and readable.
 */
function fetchOne($conn, $sql, $types = '', $params = []) {
    $stmt = mysqli_prepare($conn, $sql);
    if ($types !== '') {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

/**
 * Loads a full transaction (product, buyer, seller and payment details)
 * but only if the given user is the buyer or the seller on it.
 * Returns null for anyone else, which keeps other people's orders private.
 */
function getTransactionForUser($conn, $transactionId, $userId) {
    $sql = "SELECT t.*, COALESCE(b.counter_amount, b.bid_amount) AS bid_amount,
                   p.title, p.price, p.`condition`, p.product_id,
                   c.category_name,
                   buyer.full_name  AS buyer_name,  buyer.email  AS buyer_email,  buyer.phone  AS buyer_phone,
                   buyer.student_id AS buyer_student_id, buyer.department AS buyer_department,
                   seller.full_name AS seller_name, seller.email AS seller_email, seller.phone AS seller_phone,
                   seller.student_id AS seller_student_id, seller.department AS seller_department,
                   pay.payment_status, pay.payment_method, pay.amount AS paid_amount, pay.paid_at,
                   (SELECT pi.image_path FROM ProductImage pi
                     WHERE pi.product_id = p.product_id ORDER BY pi.image_id LIMIT 1) AS primary_image
            FROM Transaction t
            INNER JOIN Bid b       ON t.bid_id     = b.bid_id
            INNER JOIN Product p   ON t.product_id = p.product_id
            INNER JOIN Category c  ON p.category_id = c.category_id
            INNER JOIN User buyer  ON t.buyer_id   = buyer.user_id
            INNER JOIN User seller ON t.seller_id  = seller.user_id
            LEFT JOIN Payment pay  ON t.transaction_id = pay.transaction_id
            WHERE t.transaction_id = ? AND (t.buyer_id = ? OR t.seller_id = ?)";

    return fetchOne($conn, $sql, 'iii', [$transactionId, $userId, $userId]);
}

/**
 * Saves the uploaded product images and links them to a product.
 * Only real image files with an allowed extension and size are accepted.
 * Returns the number of images that were saved.
 */
function saveProductImages($conn, $productId, $files) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $maxFileSize = 3 * 1024 * 1024; // 3 MB per image
    $maxImages = 5;
    $savedCount = 0;

    if (empty($files['name'][0])) {
        return 0;
    }

    $totalFiles = min(count($files['name']), $maxImages);

    for ($i = 0; $i < $totalFiles; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }
        if ($files['size'][$i] > $maxFileSize) {
            continue;
        }

        // Confirm the file really is an image, not just a renamed file
        $imageInfo = getimagesize($files['tmp_name'][$i]);
        if ($imageInfo === false) {
            continue;
        }

        $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions, true)) {
            continue;
        }

        // Build a unique, safe filename so uploads never overwrite each other
        $newName = 'product_' . $productId . '_' . uniqid() . '.' . $extension;

        if (move_uploaded_file($files['tmp_name'][$i], UPLOAD_PATH . '/' . $newName)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO ProductImage (product_id, image_path) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'is', $productId, $newName);
            mysqli_stmt_execute($stmt);
            $savedCount++;
        }
    }

    return $savedCount;
}

// Fetch the logged-in user's average seller rating (1-5), or null if no reviews yet
function getSellerRating($conn, $sellerId) {
    $sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM Review WHERE seller_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $sellerId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row;
}

/*
 * ---------------------------------------------------------------------
 * Kick out an account that has been banned or deleted, straight away.
 *
 * Logging in checks the account status only once. Without the check below,
 * a student who was already signed in would keep browsing on their existing
 * session even after an admin banned them - the ban would only bite the next
 * time they tried to log in.
 *
 * This file is included exactly once per page, after the database
 * connection, so the check runs once on every request without every page
 * having to remember to call it.
 * ---------------------------------------------------------------------
 */
if (isset($_SESSION['user_id']) && isset($conn)) {
    $account = fetchOne($conn, "SELECT status FROM User WHERE user_id = ?", 'i', [(int) $_SESSION['user_id']]);

    // No row means the account was deleted; anything but 'active' means banned
    if (!$account || $account['status'] !== 'active') {
        // Drop everything the session held, then keep one message for the login page
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['login_error'] = 'Your account has been suspended. Please contact the admin.';

        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}
