<?php
// ✅ SAFE SESSION START
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set India timezone
date_default_timezone_set('Asia/Kolkata');

// DB connection
require_once __DIR__ . '/../config/db.php';

// Auth check
require_once __DIR__ . '/auth.php';

// Role functions
require_once __DIR__ . '/role.php';


// ================= FORCE PASSWORD CHANGE GUARD =================
if (!empty($_SESSION['user_id'])) {

    $currentPage = basename($_SERVER['PHP_SELF']);

    $allowedPages = [
        'change_password.php',
        'save_password.php',
        'logout.php'
    ];

    if (!in_array($currentPage, $allowedPages)) {

        $stmt = $conn->prepare("
            SELECT force_password_change
            FROM users
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!empty($user['force_password_change'])) {
            header("Location: /clinic/users/change_password.php");
            exit;
        }
    }
}