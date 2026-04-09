<?php

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

require_once '../../core/init.php';

$id = $_GET['id'] ?? 0;
$id = (int)$id;

$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$result = $stmt->get_result();
$patient = $result->fetch_assoc();

echo json_encode([
    'success' => $patient ? true : false,
    'patient' => $patient
]);