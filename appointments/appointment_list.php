<?php
require_once '../core/init.php';

// Allow ONLY admin & doctor
requireRole(['admin','doctor']);

date_default_timezone_set("Asia/Kolkata");

$sql="
SELECT 
a.*, 
p.patient_id, p.prefix, p.name, p.title, p.spouse, p.phone, p.gender, p.address, 
d.name doctor,
u.name AS user_name
FROM appointments a
LEFT JOIN patients p ON a.patient_id = p.patient_id
LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
LEFT JOIN users u ON a.created_by = u.id
WHERE a.status != 'cancelled'
ORDER BY a.date DESC
";

$data=$conn->query($sql);
?>

<h2>Ansar Polyclinic - Appointment List</h2>

<style>
body{font-family:Arial;background:#f4f8fb;}
.top-bar{display:flex;align-items:center;gap:15px;flex-wrap:wrap;margin-bottom:15px;}
.top-bar input{padding:6px;border:1px solid #ccc;border-radius:4px;}
.exit-btn{background:#c0392b;color:white;padding:8px 14px;text-decoration:none;border-radius:5px;}
.exit-btn:hover{background:#922b21;}
table{border-collapse:collapse;background:white;width:100%;}
th{background:#0b6fa4;color:white;padding:8px;}
td{padding:6px;text-align:center;}
.update-btn{background:#27ae60;color:white;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;}
.update-btn:hover{background:#1e8449;}
#popup{display:none;position:fixed;top:20%;left:35%;background:white;padding:20px;border:1px solid #ccc;box-shadow:0 0 10px #999;}
</style>

<div class="top-bar">

<a href="../index.php" class="exit-btn">Exit Dashboard</a>

<a href="export_excel.php">Export Excel</a>
<a href="export_pdf.php" target="_blank">Export PDF</a>

<button onclick="todayFilter()">Today</button>

<label>From</label>
<input type="date" id="fromDate">

<label>To</label>
<input type="date" id="toDate">

<input type="text" id="searchBox" placeholder="Global Search">
<a href="../appointments/today_appointments.php" class="btn btn-sm" style="background-color:#007bff; color:white;" role="button">Today Appointment</a>

</div>

<table id="apptTable" border="1">
<tr>
<th>No</th>
<th>Appointment ID</th>
<th>Patient ID</th>
<th>Name</th>
<th>Phone</th>
<th>Doctor</th>
<th>Created By</th>
<th>Date</th>
<th>Time</th>
<th>Update</th>
</tr>

<?php
$no=1;

while($r=$data->fetch_assoc()){

$patient_code = "AH".str_pad($r['patient_id'],5,"0",STR_PAD_LEFT);

echo "<tr>";

echo "<td>".$no++."</td>";
echo "<td>".$r['appointment_id']."</td>";
echo "<td>".$patient_code."</td>";
$prefix = strtoupper($r['prefix'] ?? '');
$name   = strtoupper($r['name'] ?? '');
$title  = strtoupper($r['title'] ?? '');
$spouse = strtoupper($r['spouse'] ?? '');

$fullName = trim("$prefix $name");

if(!empty($title) && !empty($spouse)){
    $fullName .= " $title $spouse";
}
echo "<td><strong>$fullName</strong></td>";
echo "<td>".$r['phone']."</td>";
echo "<td>".$r['doctor']."</td>";
echo "<td>".($r['user_name'] ?? '')."</td>";
echo "<td>".$r['date']."</td>";
echo "<td>".$r['time']."</td>";

echo "<td>
<button class='update-btn'
data-id='".$r['appointment_id']."'
data-date='".$r['date']."'
data-time='".$r['time']."'
onclick='openPopup(this)'>
Update
</button>

<button onclick='cancelAppointment(".$r['appointment_id'].")'>Cancel</button>

</td>";
echo "</tr>";
}
?>

</table>

<div style="margin-top:10px;font-weight:bold;">
Total Records: <span id="rowCount">0</span>
</div>

<!-- POPUP -->

<div id="popup">

<h3>Update Appointment</h3>

<form action="update_appointment.php" method="POST">

<label>Appointment ID</label>
<input type="text" name="appointment_id" id="edit_id" readonly>

<label>Date</label>
<input type="date" name="date" id="edit_date" required>

<label>Time</label>
<input type="time" name="time" id="edit_time" required>

<br><br>

<button type="submit">Save</button>
<button type="button" onclick="closePopup()">Cancel</button>

</form>

</div>

<script>

/* OPEN POPUP */

function openPopup(btn){

document.getElementById("popup").style.display="block";

document.getElementById("edit_id").value = btn.dataset.id;
document.getElementById("edit_date").value = btn.dataset.date;
document.getElementById("edit_time").value = btn.dataset.time;

}

/* CLOSE */

function closePopup(){
document.getElementById("popup").style.display="none";
}

/* GLOBAL SEARCH */

document.getElementById("searchBox").addEventListener("keyup", function(){

let filter=this.value.toUpperCase();
let rows=document.querySelectorAll("#apptTable tr");

rows.forEach((row,i)=>{

if(i==0) return;

let text=row.innerText.toUpperCase();

row.style.display = text.includes(filter) ? "" : "none";

});

});
function applyFilters(){

let search = document.getElementById("searchBox").value.toUpperCase();
let from = document.getElementById("fromDate").value;
let to = document.getElementById("toDate").value;

let rows = document.querySelectorAll("table tr");

let count = 0;

rows.forEach((row,index)=>{

if(index === 0) return;

let text = row.innerText.toUpperCase();

/* DATE COLUMN INDEX (CHANGE PER PAGE) */
let dateCell = row.querySelectorAll("td")[6]; // appointment: date column
if(!dateCell) return;

let rowDate = dateCell.innerText;

/* CONDITIONS */

let show = true;

/* SEARCH */
if(search && !text.includes(search)){
show = false;
}

/* DATE RANGE */
if(from && rowDate < from){
show = false;
}

if(to && rowDate > to){
show = false;
}

/* APPLY */

row.style.display = show ? "" : "none";

if(show) count++;

});

document.getElementById("rowCount").innerText = count;

}

/* EVENTS */

document.getElementById("searchBox").addEventListener("keyup", applyFilters);
document.getElementById("fromDate").addEventListener("change", applyFilters);
document.getElementById("toDate").addEventListener("change", applyFilters);

/* TODAY FILTER */

function todayFilter(){

let today = new Date().toLocaleDateString('en-CA');

document.getElementById("fromDate").value = today;
document.getElementById("toDate").value = today;

applyFilters();

}

/* INITIAL LOAD */

applyFilters();


function cancelAppointment(id){

    if(!confirm("Cancel this appointment?")) return;

    fetch("cancel_appointment.php?id="+id)
    .then(res=>res.text())
    .then(msg=>{
        alert(msg);
        location.reload();
    });

}

function startVisit(id, status) {
  if(status.toLowerCase() === 'cancelled') {
    alert('Patient appointment is cancelled. Cannot start visit.');
    return;
  }
  window.location.href = 'visit.php?appointment_id=' + id;
}
</script>