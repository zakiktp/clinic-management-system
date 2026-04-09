<?php
$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['user_id']) && $current_page !== 'login.php') {

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Session expired'
        ]);
        exit;
    } else {
        header("Location: /clinic/login.php"); // ✅ FIXED PATH
        exit;
    }
}