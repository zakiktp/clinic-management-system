<?php
include '../config/db.php';

$id = $_GET['id'] ?? '';

if($id != ''){
    $conn->query("UPDATE appointments SET status='Cancelled' WHERE appointment_id='$id'");
    echo "Appointment Cancelled";
}else{
    echo "Invalid ID";
}
?>