<?php
require_once '../../core/init.php';
header('Content-Type: application/json');

$appointment_id = isset($_GET['appointment_id']) ? (int)$_GET['appointment_id'] : 0;
$visit_id       = isset($_GET['visit_id']) ? (int)$_GET['visit_id'] : 0;

if (!$appointment_id && !$visit_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
    exit;
}

$vitals = null;

// ================= 1. STRICT VISIT_ID (HIGHEST PRIORITY) =================
if ($visit_id > 0) {

    $stmt = $conn->prepare("
        SELECT * 
        FROM vitals 
        WHERE visit_id = ? 
        ORDER BY vital_id DESC 
        LIMIT 1
    ");

    $stmt->bind_param("i", $visit_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $vitals = $result->fetch_assoc();

    $stmt->close();
}

// ================= 2. FALLBACK → APPOINTMENT (ONLY IF NO VISIT DATA) =================
if (!$vitals && $appointment_id > 0) {

    $stmt = $conn->prepare("
        SELECT * 
        FROM vitals 
        WHERE appointment_id = ? AND (visit_id = 0 OR visit_id IS NULL)
        ORDER BY vital_id DESC 
        LIMIT 1
    ");

    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $vitals = $result->fetch_assoc();

    $stmt->close();
}

// ================= 3. DEFAULT EMPTY =================
if (!$vitals) {

    $vitals = [
        'bp_sys' => '',
        'bp_dia' => '',
        'pulse' => '',
        'bsugar' => '',
        'height' => '',
        'weight' => '',
        'bmi' => '',
        'temp' => '',
        'spo2' => '',
        'visit_id' => $visit_id ?: '',
        'appointment_id' => $appointment_id ?: ''
    ];

} else {

    // ================= CLEAN VALUES =================
    foreach ([
        'bp_sys','bp_dia','pulse','bsugar',
        'height','weight','bmi','temp','spo2'
    ] as $key) {

        if (!isset($vitals[$key]) || $vitals[$key] === null || $vitals[$key] == 0) {
            $vitals[$key] = '';
        }
    }

    // ================= ENSURE CORRECT IDS =================
    $vitals['appointment_id'] = $appointment_id;
    $vitals['visit_id'] = $visit_id ?: ($vitals['visit_id'] ?? '');
}

// ================= RESPONSE =================
echo json_encode([
    'success' => true,
    'vitals'  => $vitals
]);