<?php
require_once __DIR__ . '/../core/init.php';

if(!isset($_GET['visit_id'])){
    die("Visit not found");
}

$visit_id = $_GET['visit_id'];

/* --- GET VISIT + PATIENT --- */
$q = $conn->query("
SELECT v.*, p.*, d.name doctor
FROM visits v
JOIN patients p ON p.patient_id = v.patient_id
LEFT JOIN doctors d ON d.doctor_id = v.doctor_id
WHERE v.visit_id = '$visit_id'
");

$data = $q->fetch_assoc();

if(!$data){
    die("Invalid visit");
}

/* --- AGE --- */
$ageText = 'N/A';
if(!empty($data['dob'])){
    $dob = new DateTime($data['dob']);
    $today = new DateTime();
    $diff = $today->diff($dob);
    $ageText = $diff->y . "Y " . $diff->m . "M " . $diff->d . "D";
}

/* --- VITALS --- */
$appointment_id = $data['appointment_id'];

// ================= VITALS =================
$vitals = $conn->query("
SELECT *
FROM vitals
WHERE appointment_id='$appointment_id'
ORDER BY vital_id DESC
LIMIT 1
");

$vt = ($vitals && $vitals->num_rows) ? $vitals->fetch_assoc() : null;

/* --- MEDICINES --- */
$rx = $conn->query("
SELECT *
FROM prescriptions
WHERE visit_id='$visit_id'
");

$rxData = [];
$showType = $showMedicine = $showDosage = $showDuration = $showAdvice = false;

while($row = $rx->fetch_assoc()){
    $rxData[] = $row;
    if(!empty($row['type'])) $showType = true;
    if(!empty($row['medicine'])) $showMedicine = true;
    if(!empty($row['dosage'])) $showDosage = true;
    if(!empty($row['duration'])) $showDuration = true;
    if(!empty($row['advice'])) $showAdvice = true;
}

/* --- COMPLAINTS --- */
$complaints = [];
$res = $conn->query("SELECT complaint FROM visit_complaints WHERE visit_id='$visit_id'");
while($row = $res->fetch_assoc()){
    $complaints[] = $row['complaint'];
}

/* --- DIAGNOSIS --- */
$diagnosis = [];
$res = $conn->query("SELECT diagnosis FROM visit_diagnosis WHERE visit_id='$visit_id'");
while($row = $res->fetch_assoc()){
    $diagnosis[] = $row['diagnosis'];
}

/* --- INVESTIGATIONS --- */
$investigations = [];
$res = $conn->query("SELECT investigation FROM visit_investigations WHERE visit_id='$visit_id'");
while($row = $res->fetch_assoc()){
    $investigations[] = $row['investigation'];
}
?>

<!DOCTYPE html>
<html>
<head>
<style>
body{font-family:Arial; background:#f5f5f5;}
.container{width:40%; margin:20px auto; background:#FFFFDD; padding:15px; border:1px solid #ccc;}
table{width:100%; border-collapse:collapse; margin-bottom:10px;}
td,th{border:1px solid #000; padding:6px; font-size:13px;}
h2,h3{margin:5px 0;}
.print-btn{padding:8px 15px; background:#27ae60; color:white; border:none; cursor:pointer; margin-bottom:10px;}
.close-btn{padding:8px 15px; background:#777; color:white; border:none; cursor:pointer; margin-bottom:10px;}
@media print{body{background:#fff;} .container{width:100%;border:none;} .print-btn,.close-btn{display:none;}}
</style>
</head>
<body>

<!-- MANUAL PRINT & CLOSE -->
<div style="text-align:center; margin-bottom:15px;">
    <button class="print-btn" onclick="window.print()">Print</button>
    <button class="close-btn" onclick="window.close()">Close</button>
</div>

<div class="container">
<h2 style="text-align:center;">ANSAR POLYCLINIC</h2>

<table style="text-align:center;">
<tr>
    <td><b>ID:</b> AH<?php echo str_pad($data['patient_id'],5,"0",STR_PAD_LEFT); ?></td>
    <td><b>Name:</b> <?php echo $data['name']; ?></td>
    <td><b>Address:</b> <?php echo !empty($data['address']) ? $data['address'] : 'N/A'; ?></td>
    <td><b>Age:</b> <?php echo $ageText; ?></td>
    <td><b>Gender:</b> <?php echo $data['gender']; ?></td>
    <td><b>Phone:</b> <?php echo $data['phone']; ?></td>
</tr>
</table>

<?php if($vt){ 
    $vitalItems = [];
    if(!empty($vt['bp_sys']) && !empty($vt['bp_dia'])) $vitalItems[] = "BP: ".$vt['bp_sys']."/".$vt['bp_dia']." mmHg";
    if(!empty($vt['pulse']))   $vitalItems[] = "Pulse: ".$vt['pulse']." bpm";
    if(!empty($vt['bsugar']))  $vitalItems[] = "Sugar: ".$vt['bsugar']." mg/dl";
    if(!empty($vt['height']))  $vitalItems[] = "Height: ".$vt['height']." cm";
    if(!empty($vt['weight']))  $vitalItems[] = "Weight: ".$vt['weight']." kg";
    if(!empty($vt['bmi']))     $vitalItems[] = "BMI: ".$vt['bmi'];
    if(!empty($vt['temp']))    $vitalItems[] = "Temp: ".$vt['temp']." °F";
    if(!empty($vt['spo2']))    $vitalItems[] = "SpO2: ".$vt['spo2']." %";

    if(!empty($vitalItems)){ ?>
        <p><b>Vitals:</b> <?php echo implode(" | ", $vitalItems); ?></p>
<?php } } ?>

<?php if(!empty($complaints)){ ?>
<p><b>Complaints:</b> <?php echo implode(", ", $complaints); ?></p>
<?php } ?>

<?php if(!empty($diagnosis)){ ?>
<p><b>Diagnosis:</b> <?php echo implode(", ", $diagnosis); ?></p>
<?php } ?>

<?php if(!empty($investigations)){ ?>
<p><b>Investigations:</b> <?php echo implode(", ", $investigations); ?></p>
<?php } ?>

<h3>Rx:</h3>
<table style="text-align:center;">
<tr>
<th>No</th>
<?php if($showType) echo "<th>Type</th>"; ?>
<?php if($showMedicine) echo "<th>Medicine</th>"; ?>
<?php if($showDosage) echo "<th>Dosage</th>"; ?>
<?php if($showDuration) echo "<th>Duration</th>"; ?>
<?php if($showAdvice) echo "<th>Advice</th>"; ?>
</tr>

<?php
$i=1;
foreach($rxData as $m){
    echo "<tr>";
    echo "<td>".$i++."</td>";
    if($showType) echo "<td>".$m['type']."</td>";
    if($showMedicine) echo "<td>".$m['medicine']."</td>";
    if($showDosage) echo "<td>".$m['dosage']."</td>";
    if($showDuration) echo "<td>".$m['duration']."</td>";
    if($showAdvice) echo "<td>".$m['advice']."</td>";
    echo "</tr>";
}
?>
</table>

<?php
$note1 = trim((string)($data['note1'] ?? ''));
$note2 = trim((string)($data['note2'] ?? ''));

$hasFooter = !empty($data['followup']) || !empty($data['amount']) || $note1 !== '' || $note2 !== '';

if($hasFooter){ ?>
<br>
<table style="text-align:center;">
<tr>
    <?php if(!empty($data['followup'])){ ?>
    <td><b>Follow Up:</b> <?php echo date('d-m-Y', strtotime($data['followup'])); ?></td>
    <?php } ?>
    
    <?php if(!empty($data['amount'])){ ?>
    <td><b>Amount:</b> ₹ <?php echo $data['amount']; ?></td>
    <?php } ?>
    
    <?php if($note1 !== ''){ ?>
    <td><b>For x </b> <?php echo $note1; ?></td>
    <?php } ?>
    
    <?php if($note2 !== ''){ ?>
    <td><b>Plus:-</b> <?php echo $note2; ?></td>
    <?php } ?>
</tr>
</table>
<?php } ?>

</div>

</body>
</html>