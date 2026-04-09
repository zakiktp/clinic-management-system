<?php
function requireRole($roles = []) {

    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {

        // ✅ API RESPONSE
        if (defined('IS_API') && IS_API) {
            header('Content-Type: application/json');
            echo json_encode([
                "status" => "error",
                "message" => "Access Denied"
            ]);
            exit;
        }

        // ✅ NORMAL PAGE
        die("Access Denied");
    }
}