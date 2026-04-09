<?php
date_default_timezone_set("Asia/Kolkata");
include '../config/db.php';

if(isset($_POST['appointment_id'])){

$appointment_id = $_POST['appointment_id'];
$date = $_POST['date'];
$time = $_POST['time'];

/* VALIDATION */

if($date=="" || $time==""){
    echo "<script>alert('Date and Time required');window.history.back();</script>";
    exit;
}

$sql = "UPDATE appointments 
        SET date='$date', time='$time'
        WHERE appointment_id='$appointment_id'";

if($conn->query($sql)){

echo "<script>
alert('Appointment Updated Successfully');
window.location='appointment_list.php';
</script>";

}else{

echo "Update Error : ".$conn->error;

}

}else{

echo "Invalid Request";

}
?>