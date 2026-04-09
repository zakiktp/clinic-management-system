<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
$canStartVisit = in_array($role, ['doctor','admin']);
?>

<style>
.action-cell {
    min-width: 220px;
}
</style>

<table class="table table-sm table-bordered">
<thead class="table-primary">
<tr>
    <th class="text-center">Token</th>
    <th class="text-center">Time</th>
    <th class="text-center">Patient ID</th>
    <th>Patient</th>
    <th class="text-center">Age</th>
    <th>Address</th>
    <th class="text-center">Mobile</th>
    <th class="text-center">Doctor</th>
    <th class="text-center">Created By</th>
    <th class="text-center">Action</th>
    <th class="text-center">Status</th>
</tr>
</thead>

<tbody>
<?php
$token = 1;

foreach($appointments as $row){

    $status = strtolower(trim($row['status'] ?? ''));
    $rowClass = $status=='completed' ? 'table-success' : ($status=='in progress' ? 'table-warning' : '');

    $prefix = strtoupper($row['prefix'] ?? '');
    $name   = strtoupper($row['name'] ?? '');
    $title  = strtoupper($row['title'] ?? '');
    $spouse = strtoupper($row['spouse'] ?? '');

    $patientName = trim("$prefix $name");
    if(!empty($title) && !empty($spouse)){
        $patientName .= " $title $spouse";
    }

    $appointment_id = (int)$row['appointment_id'];
    $patient_id     = (int)$row['patient_id'];

    $doctor     = $row['doctor'] ?? '';
    $created_by = $row['user_name'] ?? '';

    $hasVitals = !empty($row['has_visit']);

    $vitalsBtn = $hasVitals
        ? "<button class='btn btn-sm btn-warning' onclick='openVitalsModal($appointment_id)'>Edit Vitals</button>"
        : "<button class='btn btn-sm btn-success' onclick='openVitalsModal($appointment_id)'>Add Vitals</button>";
    
        $ageFormatted = '';

if (!empty($row['dob'])) {
    try {
        $dob = new DateTime($row['dob']);
        $today = new DateTime();
        $diff = $today->diff($dob);

        $ageFormatted = sprintf(
            '%02dY %02dM %02dD',
            $diff->y,
            $diff->m,
            $diff->d
        );
    } catch (Exception $e) {
        $ageFormatted = '';
    }
}

    echo "<tr class='$rowClass'>";

    echo "<td class='text-center'>".$token++."</td>";
    echo "<td class='text-center'>".htmlspecialchars($row['time'] ?? '')."</td>";
    echo "<td class='text-center'>AH".str_pad($patient_id, 5, '0', STR_PAD_LEFT)."</td>";
    echo "<td>".htmlspecialchars($patientName)."</td>";
    echo "<td class='text-center'>".$ageFormatted."</td>";
    echo "<td>".htmlspecialchars($row['address'] ?? '')."</td>";
    echo "<td class='text-center'>".htmlspecialchars($row['phone'] ?? '')."</td>";
    echo "<td class='text-center'>".htmlspecialchars($doctor)."</td>";
    echo "<td class='text-center'>".htmlspecialchars($created_by)."</td>";

    echo "<td class='text-center action-cell'>
            <div class='action-buttons'>

                <button class='btn btn-sm btn-primary js-action'
                    data-action='edit-patient'
                    data-id='$patient_id'>
                    Edit Patient
                </button>

                $vitalsBtn";

    if($canStartVisit){
        echo "<button class='btn btn-sm btn-warning js-action'
            data-action='start-visit'
            data-id='".$appointment_id."'>
            Start Visit
        </button>";
    }

    echo "  </div>
          </td>";

    echo "<td class='text-center'>".ucfirst($status)."</td>";

    echo "</tr>";
}
?>
</tbody>
</table>