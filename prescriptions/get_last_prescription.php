<?php
require_once __DIR__ . '/../core/init.php';

header('Content-Type: application/json');

/* ================= INPUT ================= */
$visit_id   = $_GET['visit_id']   ?? null;
$patient_id = $_GET['patient_id'] ?? null;

/* ================= GET PATIENT ================= */
if($visit_id){
    $q = $conn->query("SELECT patient_id FROM visits WHERE visit_id='$visit_id'");
    $v = $q->fetch_assoc();
    $patient_id = $v['patient_id'] ?? null;
}

if(!$patient_id){
    echo json_encode([
        "success" => false,
        "message" => "patient_id required"
    ]);
    exit;
}

/* ================= GET LAST PRESCRIPTION ================= */
$sql = "
SELECT p.type, p.medicine, p.dosage, p.duration, p.advice
FROM prescriptions p
JOIN visits v ON v.visit_id = p.visit_id
WHERE v.patient_id = '$patient_id'
ORDER BY v.visit_date DESC
LIMIT 50
";

$res = $conn->query($sql);

$data = [];

while($row = $res->fetch_assoc()){
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "medicines" => $data
]);