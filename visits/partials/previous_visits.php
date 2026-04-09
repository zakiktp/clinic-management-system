<?php

// ================= HELPER =================
if (!function_exists('e')) {
    function e($val) {
        return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// ================= CONFIG =================
$visitsData = [];
$show = [
    'vitals'=>false,
    'complaints'=>false,
    'diagnosis'=>false,
    'investigations'=>false,
    'prescription'=>false,
    'followup'=>false,
    'amount'=>false,
    'note1'=>false,
    'note2'=>false
];

$showAll = isset($_GET['all_visits']) && $_GET['all_visits'] == 1;
$limitSQL = $showAll ? "" : "LIMIT 5";

// ================= FETCH VISITS =================
$visits = [];
$q = $conn->query("
    SELECT *
    FROM visits
    WHERE patient_id = '".intval($patient_id)."'
    ORDER BY visit_date DESC
    $limitSQL
");

if ($q) {
    while ($row = $q->fetch_assoc()) {
        $visits[] = $row;
    }
}

// ================= LOOP =================
foreach ($visits as $v) {

    $visit_id = (int)$v['visit_id'];
    $appointment_id = (int)($v['appointment_id'] ?? 0);

    // ================= VITALS =================
    $vt = null;

    $res = $conn->query("
        SELECT *
        FROM vitals
        WHERE visit_id = '$visit_id'
        OR appointment_id = '$appointment_id'
        ORDER BY vital_id DESC
        LIMIT 1
    ");

    if ($res) {
        $vt = $res->fetch_assoc();
    }

    $vitalText = "";

    if (!empty($vt['bp_sys']) && !empty($vt['bp_dia'])) {
        $vitalText .= "BP: ".e($vt['bp_sys'])."/".e($vt['bp_dia'])."<br>";
    }
    if (!empty($vt['pulse']))  $vitalText .= "Pulse: ".e($vt['pulse'])."<br>";
    if (!empty($vt['bsugar'])) $vitalText .= "Sugar: ".e($vt['bsugar'])."<br>";
    if (!empty($vt['height'])) $vitalText .= "Ht: ".e($vt['height'])." cm<br>";
    if (!empty($vt['weight'])) $vitalText .= "Wt: ".e($vt['weight'])." kg<br>";
    if (!empty($vt['bmi']))    $vitalText .= "BMI: ".e($vt['bmi'])."<br>";
    if (!empty($vt['temp']))   $vitalText .= "Temp: ".e($vt['temp'])."<br>";
    if (!empty($vt['spo2']))   $vitalText .= "SpO2: ".e($vt['spo2'])."<br>";

    if ($vitalText) $show['vitals'] = true;

    // ================= COMPLAINTS =================
    $complaintsText = "";
    $res = $conn->query("SELECT complaint FROM visit_complaints WHERE visit_id='$visit_id'");
    if ($res) {
        $arr = [];
        while ($r = $res->fetch_assoc()) {
            $arr[] = e($r['complaint']);
        }
        $complaintsText = implode(", ", $arr);
        if ($complaintsText) $show['complaints'] = true;
    }

    // ================= DIAGNOSIS =================
    $diagnosisText = "";
    $res = $conn->query("SELECT diagnosis FROM visit_diagnosis WHERE visit_id='$visit_id'");
    if ($res) {
        $arr = [];
        while ($r = $res->fetch_assoc()) {
            $arr[] = e($r['diagnosis']);
        }
        $diagnosisText = implode(", ", $arr);
        if ($diagnosisText) $show['diagnosis'] = true;
    }

    // ================= INVESTIGATIONS =================
    $investText = "";
    $res = $conn->query("SELECT investigation FROM visit_investigations WHERE visit_id='$visit_id'");
    if ($res) {
        $arr = [];
        while ($r = $res->fetch_assoc()) {
            $arr[] = e($r['investigation']);
        }
        $investText = implode(", ", $arr);
        if ($investText) $show['investigations'] = true;
    }

    // ================= PRESCRIPTION =================
    $meds = "";
    $res = $conn->query("
        SELECT type, medicine, dosage, duration, advice
        FROM prescriptions
        WHERE visit_id='$visit_id'
    ");

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $line = trim("{$r['type']} {$r['medicine']} {$r['dosage']} {$r['duration']} {$r['advice']}");
            if ($line) {
                $meds .= e($line) . "<br>";
            }
        }
        if ($meds) $show['prescription'] = true;
    }

    // ================= DATES =================
    $visitDate = (!empty($v['visit_date']) && $v['visit_date'] != '0000-00-00')
        ? date('d/m/Y', strtotime($v['visit_date']))
        : '';

    $followupDate = (!empty($v['followup']) && $v['followup'] != '0000-00-00')
        ? date('d/m/Y', strtotime($v['followup']))
        : '';

    // ================= OTHER FIELDS =================
    foreach(['followup','amount','note1','note2'] as $col){
        if (!empty($v[$col])) $show[$col] = true;
    }

    // ================= PUSH =================
    $visitsData[] = [
        'visit_id'      => $visit_id,
        'date'          => $visitDate,
        'vitals'        => $vitalText,
        'complaints'    => $complaintsText,
        'diagnosis'     => $diagnosisText,
        'investigations'=> $investText,
        'prescription'  => $meds,
        'followup'      => $followupDate,
        'amount'        => e($v['amount']),
        'note1'         => e($v['note1']),
        'note2'         => e($v['note2']),
    ];
}
?>

<!-- ================= UI ================= -->

<div style="display:flex; justify-content:space-between; align-items:center; margin:10px 0;">
    <h3 style="color: blue; font-weight: bold; font-size: 16px; margin:0;">
        Previous Visits
    </h3>

    <div>
        <?php if (!$showAll) { ?>
            <a href="?visit_id=<?= $visit_id ?>&all_visits=1" class="btn btn-sm btn-primary">Show All</a>
        <?php } else { ?>
            <a href="?visit_id=<?= $visit_id ?>" class="btn btn-sm btn-secondary">Show Less</a>
        <?php } ?>
    </div>
</div>

<table border="1" style="width:auto;margin:10px auto;text-align:center;border-collapse:collapse;background:#B7FFD8;white-space:nowrap;">
<thead style="font-size:12px;font-weight:bold;color:darkblue;">
<tr>
    <th>Date</th>
    <?php foreach($show as $k => $v){ if($v) echo "<th>".ucfirst($k)."</th>"; } ?>
    <th>Repeat</th>
</tr>
</thead>

<tbody style="font-size:12px;">
<?php foreach($visitsData as $row){ ?>
<tr>
    <td><?= $row['date'] ?: '-' ?></td>

    <?php foreach($show as $k => $v){
        if($v){
            echo "<td style='text-align:left;'>".($row[$k] ?: '-')."</td>";
        }
    } ?>

    <td>
        <button class="repeatVisitBtn" data-visit-id="<?= $row['visit_id']; ?>">
            Repeat
        </button>
    </td>
</tr>
<?php } ?>
</tbody>
</table>