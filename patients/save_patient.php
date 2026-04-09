<?php

include '../config/db.php';

/* GET VALUES */

$prefix  = trim($_POST['prefix']);
$name    = trim($_POST['name']);
$title   = $_POST['title'] ?? '';
$spouse  = $_POST['spouse'] ?? '';
$dob     = $_POST['dob'];
$age     = trim($_POST['age']);
$gender  = trim($_POST['gender']);
$phone   = trim($_POST['phone']);
$address = trim($_POST['address']);

/* REQUIRED FIELD VALIDATION */

if($prefix=="" || $name=="" || $age=="" || $phone=="" || $address==""){
    echo "<script>
    alert('Prefix, Name, Age, Phone and Address are required');
    window.history.back();
    </script>";
    exit;
}

/* PHONE VALIDATION */

if(!preg_match('/^[0-9]{10}$/', $phone)){
    echo "<script>
    alert('Invalid phone number. Must be 10 digits.');
    window.history.back();
    </script>";
    exit;
}

/* CONVERT TO UPPERCASE */

$prefix  = strtoupper($prefix);
$name    = strtoupper($name);
$title  = strtoupper(trim($title));
$spouse = strtoupper(trim($spouse));
$gender  = strtoupper($gender);
$address = strtoupper($address);

/* INSERT */

$sql="INSERT INTO patients 
(prefix, name, title, spouse, dob, age, gender, phone, address)
VALUES 
('$prefix', '$name', '$title', '$spouse', 
 ".(!empty($dob) ? "'$dob'" : "NULL").", 
 '$age', '$gender', '$phone', '$address')";

$conn->query($sql);

/* REDIRECT */

header("Location: add_patient.php?msg=saved");
exit();

?>