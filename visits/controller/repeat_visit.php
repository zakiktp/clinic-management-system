<?php
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

require_once __DIR__ . '/../../core/init.php';

$visit_id = isset($_GET['visit_id']) ? (int)$_GET['visit_id'] : 0;

if ($visit_id <= 0) {
    echo json_encode([
        'medicines' => [],
        'complaints_arr' => [],
        'diagnosis_arr' => [],
        'investigations_arr' => []
    ]);
    exit;
}

$response = [
    'medicines' => [],
    'complaints_arr' => [],
    'diagnosis_arr' => [],
    'investigations_arr' => []
];

// ================= MEDICINES =================
$q = $conn->query("
    SELECT type, medicine, dosage, duration, advice
    FROM prescriptions
    WHERE visit_id = $visit_id
");

if ($q) {
    while ($row = $q->fetch_assoc()) {
        $response['medicines'][] = $row;
    }
}

// ================= COMPLAINTS =================
$res = $conn->query("
    SELECT complaint FROM visit_complaints WHERE visit_id = $visit_id
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $response['complaints_arr'][] = $row['complaint'];
    }
}

// ================= DIAGNOSIS =================
$res = $conn->query("
    SELECT diagnosis FROM visit_diagnosis WHERE visit_id = $visit_id
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $response['diagnosis_arr'][] = $row['diagnosis'];
    }
}

// ================= INVESTIGATIONS =================
$res = $conn->query("
    SELECT investigation FROM visit_investigations WHERE visit_id = $visit_id
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $response['investigations_arr'][] = $row['investigation'];
    }
}

echo json_encode($response);
exit;