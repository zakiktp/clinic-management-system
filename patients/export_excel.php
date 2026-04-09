<?php
include '../config/db.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=patients.xls");

// ✅ IMPORTANT: fetch title + spouse also
$patients = $conn->query("SELECT * FROM patients");

// ✅ HEADER UPDATED (Prefix removed)
echo "ID\tName\tDOB\tAge\tGender\tPhone\tAddress\n";

while($p = $patients->fetch_assoc()){

    // ✅ BUILD FULL NAME
    $prefix  = strtoupper($p['prefix'] ?? '');
    $name    = strtoupper($p['name'] ?? '');
    $title   = strtoupper($p['title'] ?? '');
    $spouse  = strtoupper($p['spouse'] ?? '');

    $fullName = trim("$prefix $name");

    if(!empty($title) && !empty($spouse)){
        $fullName .= " $title $spouse";
    }

    // ✅ PRINT ROW
    echo $p['patient_id']."\t";
    echo $fullName."\t"; // ✅ merged column
    echo $p['dob']."\t";
    echo $p['age']."\t";
    echo $p['gender']."\t";
    echo $p['phone']."\t";
    echo $p['address']."\n";
}
?>