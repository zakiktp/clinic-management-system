<?php
require_once __DIR__ . '/../core/init.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$role = $_SESSION['role'] ?? '';
requireRole(['admin','doctor','reception']);

$visit_id = (int)($_GET['visit_id'] ?? 0);

if ($visit_id <= 0) {
    die("Invalid visit");
}

/* GET appointment_id from visit */
$stmt = $conn->prepare("SELECT * FROM visits WHERE visit_id=? LIMIT 1");
$stmt->bind_param("i", $visit_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die("Invalid visit");
}

$visit = $res->fetch_assoc();
$appointment_id = $visit['appointment_id'];
$patient_id = $visit['patient_id'];

/* ================= FETCH PATIENT & APPOINTMENT DATA ================= */
$q = $conn->query("
    SELECT a.*, p.patient_id, p.name, p.phone, p.address, p.prefix, p.gender, p.dob, p.title, p.spouse
    FROM appointments a
    JOIN patients p ON p.patient_id = a.patient_id
    WHERE a.appointment_id='$appointment_id'
");
if(!$q || $q->num_rows == 0) die("Invalid appointment");
$data = $q->fetch_assoc();
$patient_id = $data['patient_id'];

/* ================= VISIT ================= */
$v = $conn->query("SELECT * FROM visits WHERE appointment_id='$appointment_id' LIMIT 1");
$visit = ($v && $v->num_rows) ? $v->fetch_assoc() : null;
$visit_id = $visit['visit_id'] ?? 0;

/* ================= VITALS ================= */
$vt = $conn->query("
    SELECT * FROM vitals 
    WHERE appointment_id='$appointment_id'
    ORDER BY vital_id DESC 
    LIMIT 1
");

$vitals = ($vt && $vt->num_rows) ? $vt->fetch_assoc() : [];

/* ================= HISTORY ================= */
$history = $conn->query("
    SELECT * FROM visits 
    WHERE patient_id='$patient_id'
    ORDER BY visit_id DESC 
    LIMIT 5
");

/* ================= PATIENT DATA FOR MODAL ================= */
$patient_data = [
    'patient_id' => $data['patient_id'] ?? '',
    'name'       => $data['name'] ?? '',
    'phone'      => $data['phone'] ?? '',
    'address'    => $data['address'] ?? '',
    'prefix'     => $data['prefix'] ?? '',
    'gender'     => $data['gender'] ?? '',
    'dob'        => $data['dob'] ?? '',
    'title'      => $data['title'] ?? '',
    'spouse'     => $data['spouse'] ?? ''
];
$vitals_json = json_encode($vitals ?: [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$visit_json = json_encode($visit ?: [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$patient_json = json_encode($patient_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Visit</title>

    <!-- CSS -->
    <link rel="stylesheet" href="./assets/visit.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- JS -->
     <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</head>

<body class="p-3">

<!-- ================= TOP BAR ================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Doctor Dashboard</h2>
    <div>
        <button class="btn btn-primary" id="editPatientBtn">Edit Patient</button>
        <a href="/clinic/index.php" class="btn btn-secondary">Exit</a>
    </div>
</div>

<!-- ================= PATIENT INFO ================= -->
<?php include __DIR__ . '/partials/patient_info.php'; ?>

<?php

$show = [
    'vitals' => true,
    'complaints' => true,
    'diagnosis' => true,
    'investigations' => true,
    'prescription' => true,
    'followup' => true,
    'amount' => true,
    'note1' => true,
    'note2' => true
];

$visitsData = [];

$history = $conn->query("
    SELECT * FROM visits 
    WHERE patient_id='$patient_id'
    ORDER BY visit_id DESC 
    LIMIT 5
");

if($history && $history->num_rows){
    while($row = $history->fetch_assoc()){

        // get medicines
        $p = $conn->query("SELECT medicine FROM prescriptions WHERE visit_id='".$row['visit_id']."'");
        $meds = [];
        while($m = $p->fetch_assoc()){
            $meds[] = $m['medicine'];
        }

        $visitsData[] = [
            'visit_id' => $row['visit_id'],
            'date' => date('d-m-Y', strtotime($row['visit_date'] ?? $row['date'] ?? 'now')),
            'vitals' => '',
            'complaints' => $row['complaints'] ?? '',
            'diagnosis' => $row['diagnosis'] ?? '',
            'investigations' => $row['investigations'] ?? '',
            'prescription' => implode(', ', $meds),
            'followup' => $row['followup'] ?? '',
            'amount' => $row['amount'] ?? '',
            'note1' => $row['note1'] ?? '',
            'note2' => $row['note2'] ?? ''
        ];
    }
}
?>


<!-- ================= PREVIOUS VISITS ================= -->
<?php include __DIR__ . '/partials/previous_visits.php'; ?>

<!-- Make sure vitals HTML is included before JS -->
<?php include_once __DIR__ . '/partials/vitals.php'; ?>

<!-- ================= DOCTOR NOTES ================= -->
<?php if($role !== 'reception'){ ?>
    <?php include __DIR__ . '/partials/doctor_notes.php'; ?>
<?php } ?>

<!-- ================= EDIT PATIENT MODAL ================= -->
<div id="editPatientModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content p-3">
            <div class="modal-header">
                <h5 class="modal-title">Edit Patient</h5>
                <button class="btn btn-primary" id="editPatientBtn">
                    Edit Patient
                </button>
            </div>
            <div class="modal-body">
                <form id="editPatientForm">
                    <input type="hidden" name="patient_id" id="modal_patient_id">
                    <input type="hidden" name="appointment_id" id="modal_appointment_id" value="<?= $appointment_id ?>">
                    <input type="hidden" name="visit_id" id="visit_id" value="<?= $visit_id ?>">
                    <div class="row g-2">
                        <div class="col-md-4"><label>Patient ID</label><input type="text" id="modal_display_id" class="form-control" readonly></div>
                        <div class="col-md-4"><label>Prefix</label>
                            <select name="prefix" id="modal_prefix" class="form-control">
                                <option value="">Select</option>
                                <option value="MR">Mr</option>
                                <option value="MRS">Mrs</option>
                                <option value="MS">Ms</option>
                                <option value="MASTER">Master</option>
                                <option value="BABY">Baby</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label>Name</label><input type="text" name="name" id="modal_name" class="form-control" required></div>

                        <div class="col-md-4"><label>Title</label><input type="text" name="title" id="modal_title" class="form-control"></div>
                        <div class="col-md-4"><label>Spouse</label><input type="text" name="spouse" id="modal_spouse" class="form-control"></div>
                        <div class="col-md-4"><label>Gender</label><input type="text" name="gender" id="modal_gender" class="form-control" readonly></div>

                        <div class="col-md-4"><label>Age</label><input type="text" id="modal_age" class="form-control"></div>
                        <div class="col-md-4"><label>DOB</label><input type="date" name="dob" id="modal_dob" class="form-control"></div>
                        <div class="col-md-4"><label>Phone</label><input type="text" name="phone" id="modal_phone" class="form-control" placeholder="Phone" maxlength="10" pattern="[0-9]{10}"></div>

                        <div class="col-12"><label>Address</label>
                            <input type="text" name="address" id="modal_address" class="form-control" list="modal_addresslist" required>
                            <datalist id="modal_addresslist">
                                <?php
                                $a = $conn->query("SELECT DISTINCT address FROM patients WHERE address IS NOT NULL AND address!='' ORDER BY address");
                                if($a){ while($r=$a->fetch_assoc()){ echo "<option value='".htmlspecialchars($r['address'], ENT_QUOTES)."'>"; } }
                                ?>
                            </datalist>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-success">Save Patient</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ================= MAIN JS ================= -->

<?php
$dataForJS = [
    'appointment_id' => $appointment_id,
    'patient_id'     => $patient_id,
    'vitals'         => $vitals ?: [],
    'visit'          => $visit ?: [],
    'patient'        => $patient_data
];
?>
<script>
window.APP_DATA = <?= json_encode($dataForJS, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
console.log("✅ APP_DATA LOADED:", window.APP_DATA);

document.addEventListener("DOMContentLoaded", () => {

    const btn = document.getElementById("editPatientBtn");
    if (!btn) return;

    btn.addEventListener("click", () => {

        const p = window.APP_DATA?.patient;
        if (!p) return alert("Patient data missing");

        // ✅ Format Patient ID
        const formattedId = "AH" + String(p.patient_id).padStart(5, '0');

        document.getElementById('modal_display_id').value = formattedId;
        document.getElementById('modal_patient_id').value = p.patient_id || '';

        document.getElementById('modal_prefix').value = p.prefix || '';
        document.getElementById('modal_name').value = p.name || '';
        document.getElementById('modal_title').value = p.title || '';
        document.getElementById('modal_spouse').value = p.spouse || '';
        document.getElementById('modal_gender').value = p.gender || '';
        document.getElementById('modal_phone').value = p.phone || '';
        document.getElementById('modal_address').value = p.address || '';
        document.getElementById('modal_dob').value = p.dob || '';

        const dobEl = document.getElementById('modal_dob');
        const ageEl = document.getElementById('modal_age');
        const prefixEl = document.getElementById('modal_prefix');
        const genderEl = document.getElementById('modal_gender');

        // ✅ AGE CALC
        function calculateAge(dob) {
            if (!dob) return '';
            const birth = new Date(dob);
            const today = new Date();

            let y = today.getFullYear() - birth.getFullYear();
            let m = today.getMonth() - birth.getMonth();
            let d = today.getDate() - birth.getDate();

            if (d < 0) { m--; d += 30; }
            if (m < 0) { y--; m += 12; }

            return `${y}Y ${m}M ${d}D`;
        }

        function ageToDOB(ageStr) {
            const match = ageStr.match(/(\d+)Y\s*(\d+)M\s*(\d+)D/);
            if (!match) return '';

            const y = parseInt(match[1]) || 0;
            const m = parseInt(match[2]) || 0;
            const d = parseInt(match[3]) || 0;

            const today = new Date();

            let year = today.getFullYear() - y;
            let month = today.getMonth() - m;
            let day = today.getDate() - d;

            const dob = new Date(year, month, day);

            return dob.toISOString().split('T')[0];
        }

        if (p.dob) {
            ageEl.value = calculateAge(p.dob);
        }

        dobEl.addEventListener('change', () => {
            ageEl.value = calculateAge(dobEl.value);
        });

        ageEl.addEventListener('input', () => {
            const dob = ageToDOB(ageEl.value);
            if (dob) dobEl.value = dob;
        });

        // ✅ PREFIX → GENDER
        prefixEl.addEventListener('change', () => {
            const val = prefixEl.value;
            if (val === 'MR' || val === 'MASTER') genderEl.value = 'Male';
            else if (val === 'MRS' || val === 'MS' || val === 'BABY') genderEl.value = 'Female';
            else genderEl.value = '';
        });

        prefixEl.dispatchEvent(new Event('change'));

        // ✅ OPEN MODAL
        const modal = new bootstrap.Modal(document.getElementById('editPatientModal'));
        modal.show();
    });

});

document.getElementById("editPatientForm")?.addEventListener("submit", async function(e) {
    e.preventDefault();

    const form = this;

    try {
        const res = await fetch("/clinic/patients/update_patient.php", {
            method: "POST",
            body: new FormData(form)
        });

        const data = await res.text(); // or .json()

        alert("✅ Patient updated");

        // optional reload
        location.reload();

    } catch (err) {
        console.error(err);
        alert("❌ Error saving patient");
    }
});
</script>

<script>
// Wait until vitals inputs exist, then call callback
function waitForVitalsDOM(callback) {
    const interval = setInterval(() => {
        if (document.getElementById('bp_sys')) {
            clearInterval(interval);
            callback();
        }
    }, 50);
}
</script>
<script src="/clinic/visits/js/modules/visit.js?v=9"></script>

<script>
waitForVitalsDOM(() => {
    console.log("Vitals DOM ready, binding values...");

    if (window.APP_DATA?.vitals) {

        if (typeof bindVitals === "function") {
            bindVitals(window.APP_DATA.vitals);
        } 
        else if (typeof VisitApp !== "undefined" && typeof VisitApp.loadVitals === "function") {
            VisitApp.loadVitals();
        } 
        else {
            console.warn("⚠️ bindVitals not found, skipping...");
        }

    }
});
</script>
</body>
</html>