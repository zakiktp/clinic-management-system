<?php
require_once '../../core/init.php';

header('Content-Type: application/json');

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;

if ($appointment_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid appointment ID (empty)'
    ]);
    exit;
}

// ✅ UPDATE STATUS → IN PROGRESS
$update = $conn->prepare("
    UPDATE appointments 
    SET status='In Progress' 
    WHERE appointment_id=?
");
$update->bind_param("i", $appointment_id);
$update->execute();
$update->close();

/* ================= CHECK APPOINTMENT ================= */
$stmt = $conn->prepare("SELECT appointment_id, patient_id FROM appointments WHERE appointment_id=? LIMIT 1");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid appointment (not found)'
    ]);
    exit;
}
 
$row = $res->fetch_assoc();
$patient_id = $row['patient_id'];

/* ================= CHECK EXISTING VISIT ================= */
$stmt = $conn->prepare("SELECT visit_id FROM visits WHERE appointment_id=? LIMIT 1");
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    $visit_id = $res->fetch_assoc()['visit_id'];

    echo json_encode([
        'success' => true,
        'visit_id' => $visit_id
    ]);
    exit;
}

/* ================= CREATE VISIT ================= */
$now = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    INSERT INTO visits (patient_id, appointment_id, created_at)
    VALUES (?, ?, ?)
");

$stmt->bind_param("iis", $patient_id, $appointment_id, $now);

if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => $stmt->error
    ]);
    exit;
}

$visit_id = $stmt->insert_id;

echo json_encode([
    'success' => true,
    'visit_id' => $visit_id
]);