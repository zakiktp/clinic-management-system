<?php
// Detect environment (WORKS for localhost:8080 also)
if (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
) {
    define('ENV', 'local');
} else {
    define('ENV', 'live');
}

// Base URL
if (ENV == 'local') {
    define('BASE_URL', 'http://localhost:8080/clinic/');
} else {
    define('BASE_URL', 'https://ansarhospital.in/clinic/');
}

// Debug (optional)
if (ENV == 'local') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>