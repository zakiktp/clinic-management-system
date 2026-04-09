<?php

include '../config/db.php';
session_start(); // ✅ REQUIRED

$patient_id = $_POST['patient_id'];
$doctor_id  = $_POST['doctor_id'];
$date       = $_POST['date'];
$time       = $_POST['time'];

/* GET LOGGED-IN USER */
$user_id = $_SESSION['user_id'] ?? 0;

/* INSERT WITH USER */
$sql = "INSERT INTO appointments (patient_id, doctor_id, created_by, date, time)
VALUES ('$patient_id','$doctor_id','$user_id','$date','$time')";

$conn->query($sql);

header("Location: ../index.php?msg=appointment_saved");
exit();

?>