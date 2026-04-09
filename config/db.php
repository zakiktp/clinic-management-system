<?php
require_once __DIR__ . '/env.php';

if(ENV == 'local'){
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db   = "clinic_db";
} else {
    $host = 'localhost';               // Hostinger DB host (usually localhost)
    $user = 'u581711737_clinic_db';   // Update to your actual DB user (matches your screenshot)
    $pass = 'Clinic#240324';       // Your DB user password
    $db   = 'u581711737_clinic_db';   // Your DB name
}

$conn = new mysqli($host, $user, $pass, $db);

if($conn->connect_error){
    die("DB Connection Failed: " . $conn->connect_error);
}
?>