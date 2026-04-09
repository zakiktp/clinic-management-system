<?php
date_default_timezone_set("Asia/Kolkata");

include '../config/db.php';

$sql="
SELECT a.appointment_id,p.name,p.phone,p.gender,p.address,
d.name doctor,a.date,a.time
FROM appointments a
LEFT JOIN patients p ON a.patient_id=p.patient_id
LEFT JOIN doctors d ON a.doctor_id=d.doctor_id
ORDER BY a.date DESC
";

$data=$conn->query($sql);

echo "<h2>ANSAR POLYCLINIC - Appointment List</h2>";

echo "<table border='1' cellpadding='5' cellspacing='0'>";

echo "<tr>
<th>ID</th>
<th>Name</th>
<th>Phone</th>
<th>Gender</th>
<th>Address</th>
<th>Doctor</th>
<th>Date</th>
<th>Time</th>
</tr>";

while($r=$data->fetch_assoc()){

echo "<tr>";

echo "<td>".$r['appointment_id']."</td>";
echo "<td>".$r['name']."</td>";
echo "<td>".$r['phone']."</td>";
echo "<td>".$r['gender']."</td>";
echo "<td>".$r['address']."</td>";
echo "<td>".$r['doctor']."</td>";
echo "<td>".$r['date']."</td>";
echo "<td>".$r['time']."</td>";

echo "</tr>";

}

echo "</table>";
?>