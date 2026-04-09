<?php
require_once '../core/init.php';
requireRole(['admin','doctor']);

/* --- FILTERS --- */
$search     = $_GET['search'] ?? '';
$patient_id = $_GET['patient_id'] ?? '';
$from       = $_GET['from'] ?? '';
$to         = $_GET['to'] ?? '';
$page       = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$limit = 10;
$offset = ($page - 1) * $limit;

/* --- SEARCH RESULTS (PATIENT LIST) --- */
$patients = [];

if(!empty($search)){
    $q = $conn->query("
        SELECT patient_id, prefix, name, phone 
        FROM patients
        WHERE prefix LIKE '%$search%'
        OR name LIKE '%$search%' 
        OR phone LIKE '%$search%' 
        OR patient_id LIKE '%$search%'
        LIMIT 50
    ");

    while($r = $q->fetch_assoc()){
        $patients[] = $r;
    }
}

/* --- BUILD VISIT QUERY --- */
$where = "WHERE 1=1";

if(!empty($patient_id)){
    $where .= " AND v.patient_id='$patient_id'";
}

if(!empty($from)){
    $where .= " AND DATE(v.visit_date) >= '$from'";
}

if(!empty($to)){
    $where .= " AND DATE(v.visit_date) <= '$to'";
}

/* --- TOTAL COUNT (for pagination) --- */
$countQ = $conn->query("
    SELECT COUNT(*) as total
    FROM visits v
    $where
");
$total = $countQ->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($total / $limit);

/* --- FETCH VISITS --- */
$visits = $conn->query("
    SELECT 
        v.*, 
        p.prefix, 
        p.name, 
        p.title,
        p.spouse,
        p.phone
    FROM visits v
    JOIN patients p ON p.patient_id = v.patient_id
    $where
    ORDER BY v.visit_date DESC
    LIMIT $limit OFFSET $offset
");

/* --- CLASSIFY VITALS --- */
function cls($type, $val){
    if($val === null || $val === '') return '';
    switch($type){
        case 'bp_sys': return $val>=140?'alert-high':($val<90?'alert-low':'alert-normal');
        case 'bp_dia': return $val>=90?'alert-high':($val<60?'alert-low':'alert-normal');
        case 'pulse': return $val>100?'alert-high':($val<60?'alert-low':'alert-normal');
        case 'sugar': return $val>200?'alert-high':($val<70?'alert-low':'alert-normal');
        case 'spo2': return $val<95?'alert-high':'alert-normal';
        case 'temp': return $val<97.4?'alert-low':($val>98.4?'alert-high':'alert-normal');
    }
    return '';
}
?>

<html>
<head>
<title>Visit History</title>
<style>
body{font-family:Arial;background:#f5f5f5;}
.container{width:95%;margin:auto;background:#fff;padding:20px;}
input,button{padding:6px;margin:3px;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #ccc;padding:6px;font-size:13px;text-align:center;}
th{background:#e6f2ff;}
.search-box{position:relative;}
.search-results{position:absolute;background:#fff;border:1px solid #ccc;width:300px;max-height:200px;overflow:auto;}
.search-results div{padding:6px;cursor:pointer;}
.search-results div:hover{background:#eee;}
.alert-high {color: #b30000;font-weight: bold;}
.alert-low {color: #004085;font-weight: bold;}
.alert-normal {color: #006600;}
</style>

<script>
function selectPatient(id){
    window.location = "visit_history.php?patient_id="+id;
}
</script>

</head>
<body>
<div class="container">
<h2>Visit History</h2>

<!-- 🔍 SEARCH -->
<div class="search-box">
<form method="GET">
    <input type="text" name="search" placeholder="Search patient..." value="<?php echo $search; ?>">
    <button type="submit">Search</button>
</form>

<?php if(!empty($patients)){ ?>
<div class="search-results">
<?php foreach($patients as $p){ ?>
<div onclick="selectPatient(<?php echo $p['patient_id']; ?>)">
    <?php echo htmlspecialchars(trim(($p['prefix'] ?? '') . " " . ($p['name'] ?? '-') . " (" . ($p['phone'] ?? '-') . ")")); ?>
</div>
<?php } ?>
</div>
<?php } ?>
</div>

<!-- 📅 FILTERS -->
<form method="GET">
<input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
From: <input type="date" name="from" value="<?php echo $from; ?>">
To: <input type="date" name="to" value="<?php echo $to; ?>">
<button type="submit">Filter</button>
<a href="visit_history.php"><button type="button">Reset</button></a>
<a href="../index.php" class="btn exit-btn">Exit Dashboard</a>
</form>

<?php
$total_amount = 0;
$total_records = 0;
?>

<table>
<tr>
<th>Date</th>
<th>Patient ID</th>
<th>Patient Name</th>
<th>Vitals</th>
<th>Complaints</th>
<th>Diagnosis</th>
<th>Investigations</th>
<th>Prescription</th>
<th>Followup</th>
<th>Amount</th>
<th>Note1</th>
<th>Note2</th>
<th>Action</th>
</tr>

<?php while($v = $visits->fetch_assoc()) { 
    $total_records++;
    $total_amount += !empty($v['amount']) ? (float)$v['amount'] : 0;
    $visit_id = (int)$v['visit_id'];

    $prefix = strtoupper($v['prefix'] ?? '');
    $name   = strtoupper($v['name'] ?? '');
    $title  = strtoupper($v['title'] ?? '');
    $spouse = strtoupper($v['spouse'] ?? '');

    $patientName = trim("$prefix $name");
    if(!empty($title) && !empty($spouse)){
        $patientName .= " $title $spouse";
    }
?>
<tr>
<td><?php echo date('d-m-Y', strtotime($v['visit_date'])); ?></td>
<td><?php echo "AH".str_pad($v['patient_id'],5,"0",STR_PAD_LEFT); ?></td>
<td><?php echo htmlspecialchars($patientName ?: '-'); ?></td>


<!-- ✅ VITALS -->
<td>
<?php
$vt = $conn->query("SELECT * FROM vitals WHERE visit_id=$visit_id ORDER BY vital_id DESC LIMIT 1")->fetch_assoc();
if(!$vt){ 
    $vt = $conn->query("SELECT * FROM vitals WHERE appointment_id=".$v['appointment_id']." ORDER BY vital_id DESC LIMIT 1")->fetch_assoc();
}
if($vt){
    $vitalText = [];
    if(!empty($vt['bp_sys']) || !empty($vt['bp_dia'])) $vitalText[] = "BP: <span class='".cls('bp_sys',$vt['bp_sys'])."'>".$vt['bp_sys']."</span> / <span class='".cls('bp_dia',$vt['bp_dia'])."'>".$vt['bp_dia']."</span>";
    if(!empty($vt['pulse'])) $vitalText[] = "Pulse: <span class='".cls('pulse',$vt['pulse'])."'>".$vt['pulse']."</span>";
    if(!empty($vt['bsugar'])) $vitalText[] = "Sugar: <span class='".cls('sugar',$vt['bsugar'])."'>".$vt['bsugar']."</span>";
    if(!empty($vt['height'])) $vitalText[] = "Height: ".$vt['height'];
    if(!empty($vt['weight'])) $vitalText[] = "Weight: ".$vt['weight'];
    if(!empty($vt['bmi'])) $vitalText[] = "BMI: ".$vt['bmi'];
    if(!empty($vt['temp'])) $vitalText[] = "Temp: <span class='".cls('temp',$vt['temp'])."'>".$vt['temp']."</span>";
    if(!empty($vt['spo2'])) $vitalText[] = "SpO2: <span class='".cls('spo2',$vt['spo2'])."'>".$vt['spo2']."</span>";
    echo implode("<br>", $vitalText);
}
?>
</td>

<!-- ✅ COMPLAINTS -->
<td>
<?php
$complaints = $conn->query("SELECT complaint FROM visit_complaints WHERE visit_id=$visit_id");
while($c = $complaints->fetch_assoc()){
    echo htmlspecialchars($c['complaint'])."<br>";
}
?>
</td>

<!-- ✅ DIAGNOSIS -->
<td>
<?php
$diagnosis = $conn->query("SELECT diagnosis FROM visit_diagnosis WHERE visit_id=$visit_id");
while($d = $diagnosis->fetch_assoc()){
    echo htmlspecialchars($d['diagnosis'])."<br>";
}
?>
</td>

<!-- ✅ INVESTIGATIONS -->
<td>
<?php
$investigations = $conn->query("SELECT investigation FROM visit_investigations WHERE visit_id=$visit_id");
while($i = $investigations->fetch_assoc()){
    echo htmlspecialchars($i['investigation'])."<br>";
}
?>
</td>

<!-- ✅ PRESCRIPTIONS -->
<td>
<?php
$rx = $conn->query("SELECT medicine,dosage,duration FROM prescriptions WHERE visit_id=$visit_id");
while($m = $rx->fetch_assoc()){
    echo htmlspecialchars($m['medicine']." ".$m['dosage']." ".$m['duration'])."<br>";
}
?>
</td>

<td><?php echo !empty($v['followup']) ? date('d-m-Y', strtotime($v['followup'])) : '-'; ?></td>
<td><?php echo !empty($v['amount']) ? "₹ ".$v['amount'] : '-'; ?></td>
<td><?php echo !empty($v['note1']) ? htmlspecialchars($v['note1']) : '-'; ?></td>
<td><?php echo !empty($v['note2']) ? htmlspecialchars($v['note2']) : '-'; ?></td>

<td>
<button onclick="window.open('../prescriptions/print_prescription.php?visit_id=<?php echo $visit_id; ?>','_blank','width=900,height=700')">Print</button>
<a href="add_visit.php?visit_id=<?php echo $visit_id; ?>"><button>Open</button></a>
</td>
</tr>

<?php } ?>

<!-- FOOTER TOTAL -->
<tr style="background:#e6f2ff; font-weight:bold;">
<td colspan="9" style="text-align:right;">Total Records:</td>
<td><?php echo $total_records; ?></td>
<td colspan="1" style="text-align:right;">Total Amount:</td>
<td colspan="2">₹ <?php echo number_format($total_amount,2); ?></td>
</tr>
</table>

<!-- PAGINATION -->
<div style="margin-top:10px; text-align:center;">
<?php for($i=1;$i<=$totalPages;$i++){ ?>
<a href="?page=<?php echo $i; ?>&patient_id=<?php echo $patient_id; ?>&from=<?php echo $from; ?>&to=<?php echo $to; ?>">
<button <?php if($i==$page) echo "style='background:blue;color:white;'"; ?>>
<?php echo $i; ?>
</button>
</a>
<?php } ?>
</div>

</div>
</body>
</html>