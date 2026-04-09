<!-- ================= PRESCRIPTION ================= -->
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

<tr id="medicineTemplate" class="medRow d-none">
<td class="row-index"></td>
<td><input list="typelist" name="type[]"></td>
<td><input list="medlist" name="medicine[]"></td>
<td><input list="dosagelist" name="dosage[]"></td>
<td><input list="durationlist" name="duration[]"></td>
<td><input list="advicelist" name="advice[]"></td>
<td><button type="button" class="removeRowBtn">X</button></td>
</tr>

</tbody>
</table>
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
<div>
<button type="button" class="btn btn-primary" id="addMedicineBtn">
Add Medicine
</button>

<button type="button" class="btn btn-secondary" id="repeatLastBtn">
Repeat Last Prescription
</button>
</div>

<hr>

<!-- ================= BILLING ================= -->
<h3>Followup / Billing</h3>

<table style="width:100%">
<tr>
<td>Followup<br><input type="date" name="followup"></td>
<td>Amount<br><input type="number" name="amount"></td>
<td>For x<br><input type="text" name="note1"></td>
<td>Plus<br><input type="text" name="note2"></td>
</tr>
</table>

<br>

<button type="button" class="btn btn-success" id="saveVisitBtn">
Save Visit
</button>
