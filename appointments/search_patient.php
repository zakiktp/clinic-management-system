<?php
require_once __DIR__ . '/../core/init.php';
requireRole(['admin','doctor','reception']);

$q = $_GET['q'] ?? '';

if(strlen($q) < 2){
    exit;
}

/* ✅ SECURE SEARCH */
$stmt = $conn->prepare("
    SELECT patient_id, prefix, name, title, spouse, phone, address
    FROM patients
    WHERE name LIKE ? 
       OR phone LIKE ? 
       OR address LIKE ?
    ORDER BY name ASC
    LIMIT 100
");

$search = "%$q%";
$stmt->bind_param("sss", $search, $search, $search);
$stmt->execute();
$res = $stmt->get_result();

/* ================= RESULTS ================= */
if($res->num_rows > 0){

echo "
<table style='width:100%; border-collapse:collapse; font-size:12px;'>

<tr style='background:#0b6fa4; color:white; position:sticky; top:0;'>
<th style='padding:6px;'>ID</th>
<th style='padding:6px;'>Name</th>
<th style='padding:6px;'>Phone</th>
<th style='padding:6px;'>Address</th>
</tr>
";

$i = 0;

while($r = $res->fetch_assoc()){

    $pid = "AH".str_pad($r['patient_id'],5,"0",STR_PAD_LEFT);

    $prefix = strtoupper($r['prefix'] ?? '');
    $name   = strtoupper($r['name'] ?? '');
    $title  = strtoupper($r['title'] ?? '');
    $spouse = strtoupper($r['spouse'] ?? '');

    $fullName = trim("$prefix $name");

    if(!empty($title) && !empty($spouse)){
        $fullName .= " $title $spouse";
    }

    $fullName = htmlspecialchars($fullName);
    $phone = htmlspecialchars($r['phone']);
    $address = htmlspecialchars($r['address']);

    $rowColor = ($i % 2 == 0) ? "#f8fbff" : "#eef5ff";

    echo "
    <tr 
        onclick=\"selectPatient('{$r['patient_id']}')\" 
        style='cursor:pointer; background:$rowColor;'
        onmouseover=\"this.style.background='#d4edff'\" 
        onmouseout=\"this.style.background='$rowColor'\"
    >
        <td style='padding:6px;'>$pid</td>
        <td style='padding:6px; font-weight:bold; color:#0d6efd;'>$fullName</td>
        <td style='padding:6px;'>$phone</td>
        <td style='padding:6px;'>$address</td>
    </tr>
    ";

    $i++;
}

echo "</table>";

}

/* ================= NO RESULT ================= */
else{

echo "
<div style='padding:10px; text-align:center;'>

    <div style='color:red; font-size:14px; margin-bottom:10px;'>
        ❌ No patient found
    </div>

    <button 
        onclick=\"window.location.href='/clinic/patients/add_patient.php?search=".urlencode($q)."'\" 
        style='padding:8px 14px; 
               background:#28a745; 
               color:white; 
               border:none; 
               border-radius:4px;
               cursor:pointer;'>
        ➕ Add New Patient
    </button>

</div>
";

}
?>

</script>