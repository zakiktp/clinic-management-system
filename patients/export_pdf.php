<?php
include '../config/db.php';

$patients = $conn->query("SELECT * FROM patients");
?>

<!DOCTYPE html>
<html>
<head>
<title>Patient List</title>

<style>
body{
    font-family: Arial, sans-serif;
    margin:0;
    padding:0;
}

/* A4 Layout */
@page {
    size: A4;
    margin: 10mm;
}

.container{
    width: 100%;
}

/* HEADER */
.header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-bottom:2px solid #000;
    margin-bottom:10px;
    padding-bottom:5px;
}

.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.top-logo{
    height:50px;
    width:auto;
    object-fit:contain;
}

/* Hide button on print (keep logo visible) */
@media print{
    .top-logo{
        height:40px;
    }
}
/* Hide print button in print view, keep title/logo */
@media print {
    .print-btn {
        display: none;
    }
}
/* Clinic title centered */
.clinic-title {
    text-align: center;
    flex: 1; /* takes remaining space between button and logo */
}
.clinic-title h2 {
    margin: 0;           /* remove all margin */
    font-size: 20px;     /* adjust size */
    line-height: 1.2;    /* reduce vertical spacing */
}

.clinic-title p {
    margin: 2px 0;       /* small vertical margin */
    font-size: 12px;
    line-height: 1.2;    /* tighter spacing */
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed; /* auto-fit */
}

th, td{
    border:1px solid #000;
    padding:6px;
    font-size:12px;
    word-wrap:break-word;
}

/* ALIGNMENT */
th{
    background:#f0f0f0;
    text-align:center;
}

td.center{
    text-align:center;
}

td.left{
    text-align:left;
}

td.right{
    text-align:right;
}

/* COLUMN WIDTHS */
.col-id{width:8%;}
.col-name{width:22%;}
.col-dob{width:12%;}
.col-age{width:10%;}
.col-gender{width:10%;}
.col-phone{width:13%;}
.col-address{width:25%;}

/* PRINT BUTTON */
.print-btn{
    margin:10px;
    padding:8px 15px;
    background:#27ae60;
    color:white;
    border:none;
    cursor:pointer;
}

</style>
</head>

<body>

<div class="top-bar">
    <!-- Print button on left -->
    <button class="print-btn" onclick="window.print()">Print</button>

    <!-- Clinic title centered -->
    <div class="clinic-title">
        <h2>ANSAR POLYCLINIC</h2>
        <p>Main Market, Kiratpur, BIJNOR (UP) 246731</p>
        <p>Phone: 9219568512</p>
    </div>

    <!-- Logo on right -->
    <img src="../visits/assets/images/logo_clinic.png" class="top-logo" alt="Logo">
</div>

<!-- ✅ TABLE -->
<table>

<tr>
<th class="col-id">ID</th>
<th class="col-name">Name</th>
<th class="col-dob">DOB</th>
<th class="col-age">Age</th>
<th class="col-gender">Gender</th>
<th class="col-phone">Phone</th>
<th class="col-address">Address</th>
</tr>

<?php
while($p = $patients->fetch_assoc()){

    // ✅ FULL NAME
    $prefix  = strtoupper($p['prefix'] ?? '');
    $name    = strtoupper($p['name'] ?? '');
    $title   = strtoupper($p['title'] ?? '');
    $spouse  = strtoupper($p['spouse'] ?? '');

    $fullName = trim("$prefix $name");
    if($title && $spouse){
        $fullName .= " $title $spouse";
    }

    // ✅ CLEAN ADDRESS
    $address = str_replace(["\n","\r","\t"], ' ', $p['address']);

    echo "<tr>";

    echo "<td class='center col-id'>AH".str_pad($p['patient_id'],5,"0",STR_PAD_LEFT)."</td>";
    echo "<td class='left col-name'>".htmlspecialchars($fullName)."</td>";
    echo "<td class='center col-dob'>".$p['dob']."</td>";
    echo "<td class='center col-age'>".$p['age']."</td>";
    echo "<td class='center col-gender'>".$p['gender']."</td>";
    echo "<td class='center col-phone'>".$p['phone']."</td>";
    echo "<td class='left col-address'>".htmlspecialchars($address)."</td>";

    echo "</tr>";
}
?>

</table>

</div>

</body>
</html>