<?php
require_once __DIR__ . '/../core/init.php';

header('Content-Type: application/json');

if(!isset($_GET['visit_id'])){
    echo json_encode([]);
    exit;
}

$visit_id = (int)$_GET['visit_id'];

$stmt = $conn->prepare("
    SELECT 
        v.visit_id,
        v.patient_id,
        v.doctor_id,
        v.visit_date,
        v.followup,
        v.amount,
        v.note1,
        v.note2,

        vt.bp_sys,
        vt.bp_dia,
        vt.pulse,
        vt.bsugar,
        vt.height,
        vt.weight,
        vt.bmi,
        vt.temp,
        vt.spo2

    FROM visits v

    LEFT JOIN vitals vt 
        ON vt.visit_id = v.visit_id

    WHERE v.visit_id = ?
");

$stmt->bind_param("i", $visit_id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

if(!$data){
    echo json_encode([]);
    exit;
}

// ✅ CLEAN VALUES
foreach(['bp_sys','bp_dia','pulse','bsugar','height','weight','bmi','temp','spo2'] as $key){
    if(!isset($data[$key]) || $data[$key] === null || $data[$key] == 0){
        $data[$key] = '';
    }
}

echo json_encode($data);