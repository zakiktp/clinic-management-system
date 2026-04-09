<?php
include 'config/db.php';

$q = $_GET['q'] ?? '';
$q = trim($q);
if($q=='') exit;

$q_safe = $conn->real_escape_string($q);

$sql = "
SELECT patient_id, name, phone, address
FROM patients
WHERE patient_id LIKE '%$q_safe%'
OR name LIKE '%$q_safe%'
OR phone LIKE '%$q_safe%'
ORDER BY patient_id DESC
LIMIT 20
";

$res = $conn->query($sql);

if($res->num_rows == 0){
    echo "<div style='padding:10px; color:red;'>No patient found</div>";
    exit;
}

echo "<table style='width:100%; border-collapse:collapse; font-size:12px;'>";

while($row = $res->fetch_assoc()){
    $pid = "AH".str_pad($row['patient_id'],5,"0",STR_PAD_LEFT);
    echo "<tr style='cursor:pointer; border-bottom:1px solid #ccc;' 
              onclick='showPatientVisits(".$row['patient_id'].")'>
            <td style='padding:5px; font-weight:bold; color:#0b6fa4;'>$pid</td>
            <td style='padding:5px;'>".$row['name']."</td>
            <td style='padding:5px;'>".$row['phone']."</td>
            <td style='padding:5px;'>".$row['address']."</td>
          </tr>";
}

echo "</table>";
?>