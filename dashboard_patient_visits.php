<?php
include 'config/db.php';

$patient_id = intval($_GET['patient_id']);
if(!$patient_id) exit;

// Get patient info
$patient = $conn->query("SELECT * FROM patients WHERE patient_id='$patient_id'")->fetch_assoc();

// Get all visits
$visits = $conn->query("
    SELECT *
    FROM visits
    WHERE patient_id='$patient_id'
    ORDER BY visit_date DESC
");

echo "<h4 style='margin:5px 0; color:#0b6fa4;'>Visits of ".$patient['name']." (AH".str_pad($patient['patient_id'],5,'0',STR_PAD_LEFT).")</h4>";

if($visits->num_rows==0){
    echo "<div style='padding:5px; color:red;'>No visits found</div>";
    exit;
}

echo "<table border='1' style='width:100%; border-collapse:collapse; font-size:12px; margin-top:5px;'>";
echo "<tr style='background:#B7FFD8; font-weight:bold; color:darkblue;'>
        <th>Date</th>
        <th>Complaints</th>
        <th>Diagnosis</th>
        <th>Investigations</th>
        <th>Prescription</th>
        <th>Followup</th>
        <th>Amount</th>
        <th>Note1</th>
        <th>Note2</th>
      </tr>";

while($v = $visits->fetch_assoc()){

    // Fetch prescriptions for this visit
    $rx = $conn->query("SELECT type, medicine, dosage, duration, advice FROM prescriptions WHERE visit_id='".$v['visit_id']."'");
    $meds = "";
    while($r = $rx->fetch_assoc()){
        if(!empty($r['medicine']) || !empty($r['dosage']) || !empty($r['duration']) || !empty($r['advice'])){
            $meds .= trim($r['type']." ".$r['medicine']." ".$r['dosage']." ".$r['duration']." ".$r['advice'])."<br>";
        }
    }

    echo "<tr>
            <td>".date('d/m/Y', strtotime($v['visit_date']))."</td>
            <td>".$v['complaints']."</td>
            <td>".$v['diagnosis']."</td>
            <td>".$v['investigations']."</td>
            <td>".$meds."</td>
            <td>".$v['followup']."</td>
            <td>".$v['amount']."</td>
            <td>".$v['note1']."</td>
            <td>".$v['note2']."</td>
          </tr>";
}

echo "</table>";
?>