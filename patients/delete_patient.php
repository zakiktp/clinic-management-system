<?php
require_once '../core/init.php';
requireRole(['admin']); // Only admin can delete

// Validate patient ID
$patient_id = (int) ($_GET['patient_id'] ?? 0);

if (!$patient_id) {
    die("Invalid Patient ID");
}

/* CHECK FOR ACTIVE APPOINTMENTS ONLY */
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM appointments 
    WHERE patient_id = ? 
    AND status IN ('Pending','In Progress')
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['total'] > 0) {
    die("Cannot delete. Patient has active appointments.");
}

/* OPTIONAL: DELETE OLD/CANCELLED APPOINTMENTS (cleanup) */
$stmt = $conn->prepare("
    DELETE FROM appointments 
    WHERE patient_id = ? 
    AND status IN ('Cancelled','Completed')
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();

/* DELETE PATIENT */
$stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);

if ($stmt->execute()) {
    header("Location: patient_list.php?msg=deleted");
    exit();
} else {
    die("Error deleting patient");
}
?>