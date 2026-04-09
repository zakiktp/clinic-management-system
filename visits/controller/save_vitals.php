<?php
require_once __DIR__ . '/../../core/init.php';
header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);
ob_start();

error_log("🚀 saveVitals CALLED");

function respond($success, $message = '', $extra = []) {
    ob_clean();
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

// convert empty/0 → NULL
function val($v){
    return ($v === '' || $v === null || $v == 0) ? null : $v;
}

try {

    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);

    // 🔥 Proper fallback
    if (is_array($json) && !empty($json)) {
        $input = $json;
    } else {
        $input = $_POST;
    }

    $appointment_id = (int)($input['appointment_id'] ?? 0);
    $visit_id       = (int)($input['visit_id'] ?? 0);

    if ($appointment_id <= 0) {
        respond(false, 'Appointment ID is required');
    }

    // ================= GET PATIENT =================
    $stmt = $conn->prepare("
        SELECT patient_id 
        FROM appointments 
        WHERE appointment_id=? 
        LIMIT 1
    ");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        respond(false, 'Invalid appointment');
    }

    $patient_id = (int)$res->fetch_assoc()['patient_id'];

    if (!$patient_id) {
        respond(false, 'Patient not found');
    }

    $stmt->close();

    // ================= VITALS =================
    $bp_sys  = val($input['bp_sys'] ?? null);
    $bp_dia  = val($input['bp_dia'] ?? null);
    $pulse   = val($input['pulse'] ?? null);
    $bsugar  = val($input['bsugar'] ?? null);
    $height  = val($input['height'] ?? null);
    $weight  = val($input['weight'] ?? null);
    $bmi     = val($input['bmi'] ?? null);
    $temp    = val($input['temp'] ?? null);
    $spo2    = val($input['spo2'] ?? null);

    $now = date('Y-m-d H:i:s');

    // ================= ALWAYS USE APPOINTMENT_ID =================
    $stmt = $conn->prepare("
        SELECT vital_id 
        FROM vitals 
        WHERE appointment_id=? 
        ORDER BY vital_id DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $res = $stmt->get_result();

    // ================= UPDATE =================
    if ($res->num_rows > 0) {

        $vital_id = (int)$res->fetch_assoc()['vital_id'];
        $stmt->close();

        $update = $conn->prepare("
            UPDATE vitals SET
                patient_id=?,
                appointment_id=?,
                visit_id=?,
                bp_sys=?,
                bp_dia=?,
                pulse=?,
                bsugar=?,
                height=?,
                weight=?,
                bmi=?,
                temp=?,
                spo2=?,
                updated_at=?
            WHERE vital_id=?
        ");

        if (!$update) {
            respond(false, "Prepare failed: " . $conn->error);
        }

        // 14 params
        $update->bind_param(
            "iiiiddddddddsi",
            $patient_id,
            $appointment_id,
            $visit_id,
            $bp_sys,
            $bp_dia,
            $pulse,
            $bsugar,
            $height,
            $weight,
            $bmi,
            $temp,
            $spo2,
            $now,
            $vital_id
        );

        if (!$update->execute()) {
            respond(false, "Update failed: " . $update->error);
        }

        $update->close();

    } else {

        // ================= INSERT =================
        $insert = $conn->prepare("
            INSERT INTO vitals
            (patient_id, appointment_id, visit_id,
             bp_sys, bp_dia, pulse, bsugar,
             height, weight, bmi, temp, spo2,
             entry_time, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$insert) {
            respond(false, "Prepare failed: " . $conn->error);
        }

        // 14 params
        $insert->bind_param(
            "iiiiddddddddss",
            $patient_id,
            $appointment_id,
            $visit_id,
            $bp_sys,
            $bp_dia,
            $pulse,
            $bsugar,
            $height,
            $weight,
            $bmi,
            $temp,
            $spo2,
            $now,
            $now
        );

        if (!$insert->execute()) {
            respond(false, "Insert failed: " . $insert->error);
        }

        $insert->close();
    }

    respond(true, "Vitals saved successfully");

} catch (Throwable $e) {
    respond(false, $e->getMessage());
}