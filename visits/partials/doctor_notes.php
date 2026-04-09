<?php
// Prepare Tagify whitelists
$complaints = [];
$r = $conn->query("SELECT DISTINCT complaint FROM visit_complaints WHERE complaint!='' ORDER BY complaint ASC");
if ($r) while ($row = $r->fetch_assoc()) $complaints[] = htmlspecialchars($row['complaint'], ENT_QUOTES);

$diagnosis = [];
$r = $conn->query("SELECT DISTINCT diagnosis FROM visit_diagnosis WHERE diagnosis!='' ORDER BY diagnosis ASC");
if ($r) while ($row = $r->fetch_assoc()) $diagnosis[] = htmlspecialchars($row['diagnosis'], ENT_QUOTES);

$investigations = [];
$r = $conn->query("SELECT DISTINCT investigation FROM visit_investigations WHERE investigation!='' ORDER BY investigation ASC");
if ($r) while ($row = $r->fetch_assoc()) $investigations[] = htmlspecialchars($row['investigation'], ENT_QUOTES);
?>

<!-- Pass whitelist data to visit.js -->
<script>
window.DOCTOR_NOTES_WHITELISTS = {
    complaints: <?= json_encode($complaints) ?>,
    diagnosis: <?= json_encode($diagnosis) ?>,
    investigations: <?= json_encode($investigations) ?>
};
</script>

<form id="visitForm">
<div class="card p-3 mb-3">

    <input type="hidden" name="visit_id" id="visit_id">
    <input type="hidden" name="appointment_id" id="appointment_id">
    <input type="hidden" name="patient_id" id="patient_id">

    <!-- Complaints -->
    <label>Complaints</label>
    <input
        id="complaints_input"
        class="form-control text-uppercase tagify-input"
        placeholder="Add complaints">
    <input type="hidden" name="complaints" id="complaints">

    <!-- Diagnosis -->
    <label class="mt-2">Diagnosis</label>
    <input
        id="diagnosis_input"
        class="form-control text-uppercase tagify-input"
        placeholder="Add diagnosis">
    <input type="hidden" name="diagnosis" id="diagnosis">

    <!-- Investigations -->
    <label class="mt-2">Investigations</label>
    <input
        id="investigations_input"
        class="form-control text-uppercase tagify-input"
        placeholder="Add investigations">
    <input type="hidden" name="investigations" id="investigations">

</div>

<br>

<h3>Prescription</h3>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <th>Type</th>
            <th>Medicine</th>
            <th>Dosage</th>
            <th>Duration</th>
            <th>Advice</th>
            <th>Remove</th>
        </tr>
    </thead>
    <tbody id="medicineBody">
        <tr id="medicineTemplate" class="d-none">
            <td class="row-index"></td>
            <td><input list="typelist" name="type[]" style="text-transform:uppercase"></td>
            <td><input list="medlist" name="medicine[]" style="text-transform:uppercase"></td>
            <td><input list="dosagelist" name="dosage[]" style="text-transform:uppercase"></td>
            <td><input list="durationlist" name="duration[]" style="text-transform:uppercase"></td>
            <td><input list="advicelist" name="advice[]" style="text-transform:uppercase"></td>
            <td><button type="button" class="removeRowBtn">X</button></td>
        </tr>
    </tbody>
</table>

<!-- Prescription Suggestions -->
<datalist id="typelist">
<?php
$r = $conn->query("SELECT DISTINCT type FROM prescriptions WHERE type!=''");
while($row = $r->fetch_assoc()){
    echo "<option value='".htmlspecialchars($row['type'], ENT_QUOTES)."'>";
}
?>
</datalist>

<datalist id="medlist">
<?php
$r = $conn->query("SELECT DISTINCT medicine FROM prescriptions WHERE medicine!=''");
while($row = $r->fetch_assoc()){
    echo "<option value='".htmlspecialchars($row['medicine'], ENT_QUOTES)."'>";
}
?>
</datalist>

<datalist id="dosagelist">
<?php
$r = $conn->query("SELECT DISTINCT dosage FROM prescriptions WHERE dosage!=''");
while($row = $r->fetch_assoc()){
    echo "<option value='".htmlspecialchars($row['dosage'], ENT_QUOTES)."'>";
}
?>
</datalist>

<datalist id="durationlist">
<?php
$r = $conn->query("SELECT DISTINCT duration FROM prescriptions WHERE duration!=''");
while($row = $r->fetch_assoc()){
    echo "<option value='".htmlspecialchars($row['duration'], ENT_QUOTES)."'>";
}
?>
</datalist>

<datalist id="advicelist">
<?php
$r = $conn->query("SELECT DISTINCT advice FROM prescriptions WHERE advice!=''");
while($row = $r->fetch_assoc()){
    echo "<option value='".htmlspecialchars($row['advice'], ENT_QUOTES)."'>";
}
?>
</datalist>

<div class="mt-2">
    <button type="button" class="btn btn-primary" id="addMedicineBtn">Add Medicine</button>
    <button type="button" class="btn btn-secondary" id="repeatLastBtn">Repeat Last Prescription</button>
</div>

<hr>

<h3>Followup / Billing</h3>
<table style="width:100%">
    <tr>
        <td><strong>Followup</strong><br><input type="date" name="followup"></td>
        <td><strong>Amount</strong><br><input type="number" name="amount" id="amount"></td>
        <td><strong>FOR X</strong><br><input type="text" name="note1" id="note1" style="text-transform:uppercase"></td>
        <td><strong>PLUS</strong><br><input type="text" name="note2" id="note2" style="text-transform:uppercase"></td>
    </tr>
</table>

<br>

<button type="submit" id="saveVisitBtn" class="btn btn-success">
    Save Visit
</button>

</form>