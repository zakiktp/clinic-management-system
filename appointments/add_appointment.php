<?php
require_once __DIR__ . '/../core/init.php';
requireRole(['admin','reception']);

date_default_timezone_set("Asia/Kolkata");

$today = date("Y-m-d");
$now   = date("H:i");

$patients = $conn->query("SELECT * FROM patients");
$doctors  = $conn->query("SELECT * FROM doctors");

$patient_id = $_GET['patient_id'] ?? '';
$patient = null;

if(!empty($patient_id)){
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id=?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if($res->num_rows > 0){
        $patient = $res->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Ansar Polyclinic</title>

<style>

/* PAGE CENTERING */
body{
    font-family:Arial;
    background:#f4f8fb;
    margin:0;
}

/* HEADER */
.header{
    background:#0b6fa4;
    color:white;
    text-align:center;
    padding:15px;
    font-size:24px;
    font-weight:bold;
}

/* TOP BAR */
.top-bar{
    max-width:700px;
    margin:20px auto 0;
    display:flex;
    justify-content:space-between;
}

/* FORM CONTAINER */
.form-container{
    max-width:700px;          /* ✅ responsive width */
    margin:20px auto;
    padding:10px;
}

/* FORM CARD */
.form-box{
    width:100%;
    background:#ffffff;
    padding:25px;
    border-radius:10px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
}

/* INPUTS */
label{
    font-weight:bold;
    font-size:14px;
}

input,select{
    width:100%;
    padding:10px;
    margin-top:5px;
    margin-bottom:15px;
    border:1px solid #ccc;
    border-radius:6px;
    font-size:14px;
}

/* BUTTON */
button{
    width:100%;
    background:#27ae60;
    color:white;
    padding:12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-size:16px;
}

button:hover{
    background:#1e8449;
}

/* SEARCH WRAPPER */
.search-wrapper{
    position:relative;
}

/* CLEAR BUTTON */
#clearSearch{
    position:absolute;
    right:12px;
    top:35px;
    cursor:pointer;
    font-size:18px;
    color:#999;
}
#clearSearch:hover{
    color:red;
}

/* SEARCH DROPDOWN */
.search-dropdown{
    position:absolute;
    top:100%;
    left:0;
    width:100%;
    max-height:250px;
    overflow:auto;
    background:#fff;
    border:1px solid #ccc;
    border-radius:6px;
    z-index:999;
}

/* SEARCH TABLE */
.search-table{
    width:100%;
    border-collapse:collapse;
    font-size:12px;
}

.search-table th{
    background:#0b6fa4;
    color:#fff;
    position:sticky;
    top:0;
}

.search-table th,
.search-table td{
    padding:6px;
    border:1px solid #ddd;
}

.search-table tr:hover{
    background:#d4edff;
    cursor:pointer;
}

/* BUTTONS */
.btn{
    padding:8px 12px;
    border-radius:5px;
    text-decoration:none;
    color:white;
    font-weight:bold;
}

.exit-btn{background:#c0392b;}
.list-btn{background:#2980b9;}

input.uppercase{
    text-transform: uppercase;
}
</style>

</head>

<body>

<div class="header">
ANSAR POLYCLINIC
</div>

<div class="top-bar">

<a href="../index.php" class="btn exit-btn">Exit Dashboard</a>

<a href="appointment_list.php" class="btn list-btn">Appointment List</a>

</div>

<div class="form-container">
<div class="form-box">

<form action="save_appointment.php" method="POST">



<input type="hidden" name="patient_id" id="patient_id"
value="<?= $patient['patient_id'] ?? '' ?>">

<label>Selected Patient</label>

<input type="text" id="patient_display"
value="<?php 
if($patient){
    echo htmlspecialchars($patient['name']." (".$patient['phone'].")");
}
?>"
placeholder="Selected Patient" readonly>


<label>Doctor</label>

<select name="doctor_id" required>

<option value="">Select Doctor</option>

<?php
while($d=$doctors->fetch_assoc()){
echo "<option value='".$d['doctor_id']."'>".$d['name']."</option>";
}
?>

</select>


<label>Date</label>

<input type="date" name="date" value="<?php echo $today; ?>" required>


<label>Time</label>
<input type="time" name="time" value="<?php echo $now; ?>" required>

<button type="submit">Save Appointment</button>

</form>

</div>

<script>

let timer;

document.getElementById("patient_search").addEventListener("keyup", function(){

    clearTimeout(timer);

    let q = this.value.trim();

    if(q.length < 2){
        document.getElementById("patient_results").innerHTML = "";
        return;
    }

    timer = setTimeout(() => {

        fetch("search_patient.php?q=" + encodeURIComponent(q))
        .then(res => res.text())
        .then(data => {
            document.getElementById("patient_results").innerHTML = data;
        });

    }, 300); // delay

});

function selectPatient(id,name,phone){

    document.getElementById("patient_id").value = id;
    document.getElementById("patient_display").value = name + " (" + phone + ")";

    document.getElementById("patient_results").innerHTML = "";
}
</script>
<script id="jsupper">
document.querySelectorAll('.uppercase').forEach(function(el){
    el.addEventListener('input', function(){
        this.value = this.value.toUpperCase();
    });
});

const searchInput = document.getElementById("patient_search");
const resultsBox  = document.getElementById("patient_results");
const clearBtn    = document.getElementById("clearSearch");

// ✅ Only run if element exists
if(searchInput){

    searchInput.addEventListener("keydown", function(e){
        if(e.key === "Escape"){
            clearSearch();
        }
    });
}

// ✅ Clear button safe check
if(clearBtn){
    clearBtn.addEventListener("click", clearSearch);
}

function clearSearch(){
    if(searchInput) searchInput.value = "";
    if(resultsBox) resultsBox.innerHTML = "";
    
    let pid = document.getElementById("patient_id");
    let display = document.getElementById("patient_display");

    if(pid) pid.value = "";
    if(display) display.value = "";

    if(searchInput) searchInput.focus();
}
</script>
</body>
</html>