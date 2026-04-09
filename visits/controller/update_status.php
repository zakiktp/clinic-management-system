<?php
require_once __DIR__ . '/../../core/init.php';
requireRole(['admin','doctor']);

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

$appointment_id = (int)($data['appointment_id'] ?? 0);
$status = ucfirst(strtolower(trim($data['status'] ?? '')));

if (!$appointment_id || !$status) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid data'
    ]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE appointments
    SET status = ?
    WHERE appointment_id = ?
");

$stmt->bind_param("si", $status, $appointment_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'affected_rows' => $stmt->affected_rows
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $stmt->error
    ]);
}

$stmt->close();