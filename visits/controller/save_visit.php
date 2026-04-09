<?php
define('IS_API', true);

ob_start();
require_once '../../core/init.php';
requireRole(['admin','doctor']);

header('Content-Type: application/json');

try {
    $conn->begin_transaction();

    // ---------------- INPUT ----------------
    $visit_id       = isset($_POST['visit_id']) ? (int)$_POST['visit_id'] : 0;
    $appointment_id = isset($_POST['appointment_id']) ? (int)$_POST['appointment_id'] : 0;
    $patient_id     = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
    $doctor_id      = $_SESSION['user_id'] ?? 0;

    if (!$appointment_id || !$patient_id) {
        throw new Exception("Patient ID and Appointment ID are required.");
    }

    $visit_date = !empty($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d');
    $followup = !empty($_POST['followup']) ? $_POST['followup'] : null;
    $amount     = isset($_POST['amount']) && $_POST['amount'] !== '' ? (int)$_POST['amount'] : null;
    $note1      = strtoupper($_POST['note1'] ?? '');
    $note2      = strtoupper($_POST['note2'] ?? '');

    // ---------------- VISIT INSERT/UPDATE ----------------
    if (!$visit_id) {
        $stmt = $conn->prepare("
            INSERT INTO visits
            (appointment_id, patient_id, doctor_id, visit_date, followup, amount, note1, note2)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "iiississ",
            $appointment_id,
            $patient_id,
            $doctor_id,
            $visit_date,
            $followup,
            $amount,
            $note1,
            $note2
        );
        $stmt->execute();
        $visit_id = $stmt->insert_id;
        $stmt->close();

        // LINK EXISTING VITALS
        $linkVitals = $conn->prepare("
            UPDATE vitals 
            SET visit_id = ?
            WHERE appointment_id = ? 
            AND (visit_id IS NULL OR visit_id = 0)
        ");
        $linkVitals->bind_param("ii", $visit_id, $appointment_id);
        $linkVitals->execute();
        $linkVitals->close();
    } else {
        $stmt = $conn->prepare("
            UPDATE visits SET
                visit_date = ?,
                followup = ?,
                amount = ?,
                note1 = ?,
                note2 = ?
            WHERE visit_id = ?
        ");
        $stmt->bind_param(
            "ssissi",
            $visit_date,
            $followup,
            $amount,
            $note1,
            $note2,
            $visit_id
        );
        $stmt->execute();
        $stmt->close();
    }

    // ---------------- HELPER: DECODE TAGIFY ----------------
    function decodeTagifyArray($json) {
        $data = json_decode($json, true);
        if (!$data || !is_array($data)) return [];
        $values = [];
        foreach ($data as $item) {
            if (isset($item['value']) && trim($item['value']) !== '') {
                $values[] = strtoupper(trim($item['value']));
            }
        }
        return $values;
    }

    // ---------------- GENERIC FUNCTION FOR LISTS ----------------
    function saveList($conn, $table, $visit_id, $field, $values) {
        $conn->query("DELETE FROM $table WHERE visit_id='$visit_id'");
        if (!empty($values)) {
            $stmt = $conn->prepare("INSERT INTO $table (visit_id, $field) VALUES (?, ?)");
            foreach ($values as $val) {
                $stmt->bind_param("is", $visit_id, $val);
                $stmt->execute();
            }
            $stmt->close();
        }
    }

    // ---------------- SAVE COMPLAINTS, DIAGNOSIS, INVESTIGATIONS ----------------
    $complaints     = decodeTagifyArray($_POST['complaints'] ?? '');
    $diagnosis      = decodeTagifyArray($_POST['diagnosis'] ?? '');
    $investigations = decodeTagifyArray($_POST['investigations'] ?? '');

    saveList($conn, 'visit_complaints', $visit_id, 'complaint', $complaints);
    saveList($conn, 'visit_diagnosis', $visit_id, 'diagnosis', $diagnosis);
    saveList($conn, 'visit_investigations', $visit_id, 'investigation', $investigations);

    // ---------------- PRESCRIPTIONS ----------------
    $conn->query("DELETE FROM prescriptions WHERE visit_id='$visit_id'");

    if (!empty($_POST['medicine']) && is_array($_POST['medicine'])) {
        $stmt = $conn->prepare("
            INSERT INTO prescriptions
            (visit_id, type, medicine, dosage, duration, advice)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($_POST['medicine'] as $i => $med) {
            $med = trim($med);
            if ($med === '') continue;

            $type     = strtoupper($_POST['type'][$i] ?? '');
            $dosage   = strtoupper($_POST['dosage'][$i] ?? '');
            $duration = strtoupper($_POST['duration'][$i] ?? '');
            $advice   = strtoupper($_POST['advice'][$i] ?? '');

            $stmt->bind_param(
                "isssss",
                $visit_id,
                $type,
                $med,
                $dosage,
                $duration,
                $advice
            );
            $stmt->execute();
        }
        $stmt->close();
    }

    // ---------------- UPDATE APPOINTMENT STATUS ----------------
    if ($appointment_id) {
        $stmt = $conn->prepare("UPDATE appointments SET status='Completed' WHERE appointment_id=?");
        $stmt->bind_param("i", $appointment_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    ob_end_clean();

    echo json_encode([
        'status' => 'success',
        'visit_id' => $visit_id,
        'patient_id' => $patient_id
    ]);
    exit;

} catch (Exception $e) {
    $conn->rollback();
    ob_end_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
?>