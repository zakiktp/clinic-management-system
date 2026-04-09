<?php
require_once '../core/init.php';
requireRole(['admin','doctor']);

header('Content-Type: application/json');

$visit_id = $_GET['visit_id'] ?? 0;
$appointment_id = $_GET['appointment_id'] ?? 0;

if(!$visit_id && !$appointment_id){
    echo json_encode([
        'status' => 'error',
        'message' => 'Visit ID or Appointment ID required'
    ]);
    exit;
}

/* ---------------- VISIT ---------------- */
$visitQ = $conn->query("
    SELECT v.*, p.prefix, p.name, p.phone
    FROM visits v
    JOIN patients p ON p.patient_id = v.patient_id
    WHERE 
        (v.visit_id = '$visit_id' AND '$visit_id' != 0)
        OR
        (v.appointment_id = '$appointment_id' AND '$visit_id' = 0)
    LIMIT 1
");

$visit = $visitQ->fetch_assoc();

if(!$visit){
    echo json_encode([
        'status' => 'error',
        'message' => 'Visit not found'
    ]);
    exit;
}

/* ---------------- VITALS ---------------- */
$vitals = [
    'bp_sys' => '',
    'bp_dia' => '',
    'pulse' => '',
    'bsugar' => '',
    'height' => '',
    'weight' => '',
    'bmi' => '',
    'temp' => '',
    'spo2' => ''
];

$vq = $conn->query("
    SELECT *
    FROM vitals
    WHERE 
        (visit_id = '$visit_id' AND '$visit_id' != 0)
        OR
        (appointment_id = '$appointment_id' AND '$visit_id' = 0)
    ORDER BY vital_id DESC
    LIMIT 1
");

if($vq && $vq->num_rows > 0){
    $vitals = $vq->fetch_assoc();
}

/* ---------------- COMPLAINTS ---------------- */
$complaints = [];
$cq = $conn->query("
    SELECT complaint
    FROM visit_complaints
    WHERE visit_id = '$visit_id'
");

while($row = $cq->fetch_assoc()){
    $complaints[] = $row['complaint'];
}

/* ---------------- DIAGNOSIS ---------------- */
$diagnosis = [];
$dq = $conn->query("
    SELECT diagnosis
    FROM visit_diagnosis
    WHERE visit_id = '$visit_id'
");

while($row = $dq->fetch_assoc()){
    $diagnosis[] = $row['diagnosis'];
}

/* ---------------- INVESTIGATIONS ---------------- */
$investigations = [];
$iq = $conn->query("
    SELECT investigation
    FROM visit_investigations
    WHERE visit_id = '$visit_id'
");

while($row = $iq->fetch_assoc()){
    $investigations[] = $row['investigation'];
}

/* ---------------- PRESCRIPTIONS ---------------- */
$prescriptions = [];
$rx = $conn->query("
    SELECT medicine, dosage, duration, advice
    FROM prescriptions
    WHERE visit_id = '$visit_id'
");

while($row = $rx->fetch_assoc()){
    $prescriptions[] = $row;
}

/* ---------------- RESPONSE ---------------- */
echo json_encode([
    'status' => 'success',
    'data' => [
        'visit' => $visit,
        'vitals' => $vitals,
        'complaints' => $complaints,
        'diagnosis' => $diagnosis,
        'investigations' => $investigations,
        'prescriptions' => $prescriptions
    ]
]);