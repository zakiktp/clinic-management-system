<?php
$visit_id = $visit_id ?? ($_GET['visit_id'] ?? 0);
$appointment_id = $appointment_id ?? ($_GET['appointment_id'] ?? 0);

// ✅ HELPER FUNCTION
function v($x){
    return ($x !== null && $x !== '' && $x != 0) ? $x : '';
}
?>
<div class="card p-3 mb-3">

<h5 class="text-primary text-center">Vitals</h5>

<form id="vitalsForm">

<input type="hidden" name="visit_id" value="<?= $visit_id ?>">
<input type="hidden" name="appointment_id" value="<?= $appointment_id ?>">

<script>
console.log("HTML appointment_id:", "<?= $appointment_id ?>");
</script>

<div class="table-responsive">
<table class="table table-bordered text-center align-middle">
<thead class="table-light">
<tr>
<th>BP</th>
<th>Pulse</th>
<th>Sugar</th>
<th>Height</th>
<th>Weight</th>
<th>BMI</th>
<th>Temp</th>
<th>SPO2</th>
</tr>
</thead>

<tbody>
  <tr>
    <td class="d-flex justify-content-center align-items-center gap-1">
    <input id="bp_sys" name="bp_sys" class="form-control form-control-sm text-center" style="width:60px;">
    <span>/</span>
    <input id="bp_dia" name="bp_dia" class="form-control form-control-sm text-center" style="width:60px;">
</td>

<td><input id="pulse" name="pulse" class="form-control form-control-sm text-center"></td>
<td><input id="bsugar" name="bsugar" class="form-control form-control-sm text-center"></td>
<td><input id="height" name="height" class="form-control form-control-sm text-center"></td>
<td><input id="weight" name="weight" class="form-control form-control-sm text-center"></td>
<td><input id="bmi" name="bmi" class="form-control form-control-sm text-center"></td>
<td><input id="temp" name="temp" class="form-control form-control-sm text-center"></td>
<td><input id="spo2" name="spo2" class="form-control form-control-sm text-center"></td>
</tr>
</tbody>

</table>
</div>

<div class="text-center mt-3">
    <button type="button" class="btn btn-success" id="saveVitalsBtn">
        Save Vitals
    </button>
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
        Exit
    </button>
</div>

</form>

</div>

