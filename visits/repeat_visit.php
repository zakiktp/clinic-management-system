<?php

include '../config/db.php';

if (!isset($_GET['visit_id']) || empty($_GET['visit_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "visit_id is required"
    ]);
    exit;
}

$visit_id = $_GET['visit_id'];

$rx = $conn->query("
SELECT type, medicine, dosage, duration, advice
FROM prescriptions
WHERE visit_id = '$visit_id'
");

$data = [];

while ($r = $rx->fetch_assoc()) {
    $data[] = $r;
}

echo json_encode([
    "success" => true,
    "medicines" => $data
]);

?>