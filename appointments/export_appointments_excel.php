<?php
date_default_timezone_set("Asia/Kolkata");

include '../config/db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=appointments.xls");

$sql="
SELECT a.appointment_id,p.name,p.phone,p.gender,p.address,
d.name doctor,a.date,a.time
FROM appointments a
LEFT JOIN patients p ON a.patient_id=p.patient_id
LEFT JOIN doctors d ON a.doctor_id=d.doctor_id
ORDER BY a.date DESC
";

$data=$conn->query($sql);

echo "ID\tName\tPhone\tGender\tAddress\tDoctor\tDate\tTime\n";

while($r=$data->fetch_assoc()){

echo $r['appointment_id']."\t";
echo $r['name']."\t";
echo $r['phone']."\t";
echo $r['gender']."\t";
echo $r['address']."\t";
echo $r['doctor']."\t";
echo $r['date']."\t";
echo $r['time']."\n";

}
?>