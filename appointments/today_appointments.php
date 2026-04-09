<?php
require_once __DIR__ . '/../core/init.php';
$role = $_SESSION['role'] ?? ''; // define role first
requireRole(['admin','doctor','reception']);

// include __DIR__ . '/../visits/partials/vitals.php';  //

date_default_timezone_set("Asia/Kolkata");

/* AGE FORMAT */
function formatAge($dob){
    if(empty($dob)) return '-';
    $dobDate = new DateTime($dob);
    $today   = new DateTime();
    $diff    = $today->diff($dobDate);
    return $diff->y . "Y " . $diff->m . "M " . $diff->d . "D";
}

/* SEARCH */
$search = $_GET['search'] ?? '';
$whereSearch = "";
if(!empty($search)){
    $searchEsc = $conn->real_escape_string($search);
    $whereSearch = " AND (
        p.prefix LIKE '%$searchEsc%' 
        OR p.name LIKE '%$searchEsc%' 
        OR p.phone LIKE '%$searchEsc%' 
        OR p.address LIKE '%$searchEsc%' 
        OR p.patient_id LIKE '%$searchEsc%'
    )";
}

/* FETCH TODAY APPOINTMENTS + VITALS */
$today = date('Y-m-d');
$sql = "
SELECT 
    a.appointment_id,
    p.prefix, p.name, p.title, p.spouse, p.phone, p.patient_id, p.address, p.dob,
    d.name AS doctor,
    u.name AS user_name,   -- ✅ NEW
    a.date, a.time, a.status,
    v.visit_id,
    vt.vital_id, vt.bp_sys, vt.bp_dia, vt.pulse, vt.bsugar,
    vt.height, vt.weight, vt.bmi, vt.temp, vt.spo2
FROM appointments a
JOIN patients p ON p.patient_id = a.patient_id
LEFT JOIN doctors d ON d.doctor_id = a.doctor_id
LEFT JOIN users u ON a.created_by = u.id   -- ✅ NEW JOIN
LEFT JOIN visits v ON v.appointment_id = a.appointment_id
LEFT JOIN vitals vt 
    ON (vt.visit_id = v.visit_id OR (vt.appointment_id = a.appointment_id AND v.visit_id IS NULL))
    AND vt.vital_id = (
        SELECT MAX(vital_id) 
        FROM vitals 
        WHERE visit_id = v.visit_id OR (appointment_id = a.appointment_id AND v.visit_id IS NULL)
    )
WHERE a.date = '$today' $whereSearch
ORDER BY a.time ASC
";

$result = $conn->query($sql);
$appointments = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $appointments[] = $row;
    }
}

/* ADDRESS LIST */
$addresses = $conn->query("
    SELECT DISTINCT address 
    FROM patients 
    WHERE address IS NOT NULL AND address != ''
");

/* DOCTOR COUNT */
$doctorCounts = $conn->query("
    SELECT d.name AS doctor, COUNT(*) AS total
    FROM appointments a
    JOIN patients p ON p.patient_id = a.patient_id
    LEFT JOIN doctors d ON d.doctor_id = a.doctor_id
    WHERE DATE(a.date) = CURDATE() $whereSearch
    GROUP BY d.name
");
$totalAppointments = count($appointments);
?>

<style>
/* Basic Styles */
body { font-family: Arial; background:#f5f6fa; padding:20px; }
table { width: 100%; border-collapse: collapse; background: white;}
th, td { border: 1px solid #0c0b0b;}
th { background: #007bff; color: white; padding: 10px; text-align: center;}
td { padding: 8px; text-align: center;}
.filter-row input { text-align:center; width:100%; padding:5px; box-sizing: border-box; }
.row-completed { background:#d4edda; }
.row-progress { background:#fff3cd; }

/* Buttons */
.btn { padding:5px 10px; border:none; border-radius:4px; color:white; cursor:pointer; margin:2px; }
.btn-orange { background:orange; }
.btn-green { background:#28a745; }
.btn-blue { background:#007bff; }
.btn-gray { background:gray; }
.btn-edit { background:#6c757d; }

/* Patient Modal */
#patientModal {
    display:none;
    position:fixed;
    top:10%;
    left:50%;
    transform:translateX(-50%);
    background:#fff;
    padding:20px;
    width:400px;
    border-radius:8px;
    box-shadow:0 0 10px rgba(0,0,0,0.3);
    z-index:9999;
}
#patientModal input, #patientModal select { width:100%; padding:6px; margin-top:4px; cursor:auto; }
#patientModalHeader { font-weight:bold; margin-bottom:10px; cursor:move; }
.search-box button { padding:6px 10px; border:none; border-radius:4px; cursor:pointer; }
.search-box button[type="submit"] { background:#28a745; color:white; }
.search-box button[type="button"] { background:#dc3545; color:white; }
.row {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    flex-wrap: wrap; /* ✅ prevents overlap */
}

.col {
    flex: 1;
    min-width: 150px; /* ✅ prevents shrinking too small */
}

.col input,
.col select {
    width: 100%;
    box-sizing: border-box; /* ✅ fixes overflow */
}

#vitalsModal {
    position: fixed;          /* ✅ makes it popup */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%); /* center */

    width: 520px;             /* compact width */
    max-width: 95%;

    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);

    z-index: 9999;            /* ✅ above everything */
    display: none;
}

/* overlay background */
#vitalsOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;

    background: rgba(0,0,0,0.4);
    z-index: 9998;
    display: none;
}

.vitals-table {
    width: 100%;
    text-align: center;
    border-collapse: separate;   /* ✅ FIX */
    border-spacing: 5px;         /* gives breathing space */
}

.vitals-table td {
    padding: 6px;
    font-size: 12px;
    text-align: center;
    vertical-align: middle;

    overflow: visible;   /* ✅ prevents border clipping */
}
.vitals-table input {
    width: 65px;
    padding: 4px;
    font-size: 12px;
    text-align: center;

    border: 1px solid #999;     /* force all 4 borders */
    border-radius: 4px;

    box-sizing: border-box;     /* ✅ CRITICAL FIX */
    display: inline-block;      /* prevents collapse issues */
}
/* OVERLAY */
#vitalsOverlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;

    background: rgba(0,0,0,0.4);
    display: none;
    z-index: 9998;
}

/* MODAL */
#vitalsModal {
    position: fixed;   /* 🔥 KEY FIX */
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);

    width: 600px;
    max-width: 95%;

    background: #fff;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);

    display: none;
    z-index: 9999;
}
.vitals-table {
    width: 100%;
    border-collapse: collapse;
    text-align: center;
}

.vitals-table td {
    padding: 6px;
    font-size: 12px;
}

/* uniform inputs */
.vitals-table input {
    width: 65px;
    padding: 4px;
    font-size: 12px;
    text-align: center;
}

/* BP inputs tighter */
#v_bp_sys, #v_bp_dia {
    width: 55px;
    border: 1px solid #999;
}
#vitalsModal h3 {
    margin: 0 0 10px;
    text-align: center;
}
</style>

<!-- TOP BAR -->
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
    <div class="btn btn-blue">Today Appointments: <?php echo $totalAppointments; ?></div>
    <div style="display:flex; gap:8px;">
        <?php while($dc = $doctorCounts->fetch_assoc()){ ?>
            <div class="btn btn-gray"><?php echo htmlspecialchars($dc['doctor'] ?? 'Unassigned'); ?>: <?php echo (int)$dc['total']; ?></div>
        <?php } ?>
    </div>
    <form method="GET" class="search-box" style="display:flex; gap:5px; align-items:center;">
        <input type="text" name="search" id="searchBox"
            placeholder="Search patient..."
            value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
        <button type="button" onclick="resetSearch()">Reset</button>
        <a href="../index.php" class="btn btn-sm" style="background-color:#007bff; color:white;">Dashboard</a>
        <a href="../appointments/appointment_list.php" class="btn btn-sm" style="background-color:#007bff; color:white;">Appointment List</a>
    </form>
</div>

<!-- TABLE -->
<table>
<colgroup>
  <col style="width:30px">   <!-- Token -->
  <col style="width:30px">   <!-- Appt ID -->
  <col style="width:30px">   <!-- Time -->
  <col style="width:30px">  <!-- Patient ID -->
  <col style="width:100px">  <!-- Patient -->
  <col style="width:150px">  <!-- Address -->
  <col style="width:80px">   <!-- Age -->
  <col style="width:80px">  <!-- Phone -->
  <col style="width:120px">  <!-- Doctor -->
  <col style="width:120px">  <!-- Created By -->
  <col style="width:100px">  <!-- Status -->
  <col style="width:200px">  <!-- Action -->
</colgroup>
<tr>
<th>Token</th>
<th>Appt ID</th>
<th>Time</th>
<th>Patient ID</th>
<th>Patient</th>
<th>Address</th>
<th>Age</th>
<th>Phone</th>
<th>Doctor</th>
<th>Created By</th>
<th>Status</th>
<th>Action</th>
</tr>
<tr class="filter-row">
<td><input placeholder="Token"></td>
<td><input placeholder="Appt ID"></td>
<td><input placeholder="Time"></td>
<td><input placeholder="Patient ID"></td>
<td><input placeholder="Name"></td>
<td><input placeholder="Address"></td>
<td><input placeholder="Age"></td>
<td><input placeholder="Phone"></td>
<td><input placeholder="Doctor"></td>
<td><input placeholder="Created By"></td>
<td><input placeholder="Status"></td>
<td><input placeholder="Action"></td>
<tr>
<?php
$token = 1;
foreach($appointments as $row){
    $appointment_id = $row['appointment_id'];
    $visit_id = $row['visit_id'];
    $status = strtolower(trim($row['status'] ?? ''));
    $rowClass = $status=='completed'?'row-completed':($status=='in progress'?'row-progress':'');
    $patientCode = "AH" . str_pad($row['patient_id'],5,"0",STR_PAD_LEFT);
    $prefix = strtoupper($row['prefix'] ?? '');
    $name   = strtoupper($row['name'] ?? '');
    $title  = strtoupper($row['title'] ?? '');
    $spouse = strtoupper($row['spouse'] ?? '');

    $patientName = trim("$prefix $name");

    if(!empty($title) && !empty($spouse)){
        $patientName .= " $title $spouse";
    }
    $jsonRow = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

    echo "<tr class='$rowClass' data-appointment-id='$appointment_id'>";
    echo "<td>".$token++."</td>";
    echo "<td>$appointment_id</td>";
    echo "<td>{$row['time']}</td>";
    echo "<td>$patientCode</td>";
    echo "<td>$patientName</td>";
    echo "<td>{$row['address']}</td>";
    echo "<td>".formatAge($row['dob'])."</td>";
    echo "<td>{$row['phone']}</td>";
    echo "<td>{$row['doctor']}</td>";
    echo "<td>".($row['user_name'] ?? '')."</td>";
    echo "<td>".ucfirst($status)."</td>";
    echo "<td>";

    // --- VITALS BUTTON ---
    if(in_array($role,['admin','reception','doctor'])){
        $vitalsText = empty($visit_id) ? "Add Vitals" : "Edit Vitals";
        echo "<button class='btn btn-blue' onclick='openVitalsModal($jsonRow)'>$vitalsText</button>";
    }

    // --- EDIT PATIENT ---
    if(in_array($role,['admin','reception','doctor'])){
        echo "<button class='btn btn-edit' onclick='openPatientModal($jsonRow)'>Edit Patient</button>";
    }

    // --- START VISIT / CONSULT ---
    if(in_array($role,['admin','doctor'])){
        $visitBtnText = empty($visit_id) ? "Start Visit" : "Consult";
        $visitLink = "../visits/add_visit.php?appointment_id=$appointment_id";
        if(!empty($visit_id)) $visitLink .= "&visit_id=$visit_id";
        echo "<a href='$visitLink'><button class='btn btn-green'>$visitBtnText</button></a>";
    }

    echo "</td></tr>";
}
?>
</table>

<!-- EDIT PATIENT MODAL -->
<div id="patientModal">
    <div id="patientModalHeader">Edit Patient</div>

    <form method="POST" action="../patients/update_patient.php">

        <!-- ROW 1 -->
        <div class="row">
            <div class="col">
                <label>Patient ID</label>
                <input type="text" id="modal_patient_code" readonly>
                <input type="hidden" name="patient_id" id="modal_patient_id">
            </div>

            <div class="col">
                <label>Prefix</label>
                <select name="prefix" id="modal_prefix" onchange="setGenderFromPrefix()">
                    <option value="">Select</option>
                    <option>MR</option>
                    <option>MRS</option>
                    <option>MS</option>
                    <option>MASTER</option>
                    <option>BABY</option>
                </select>
            </div>
        </div>

        <!-- ROW 2 -->
        <div class="row">
            <div class="col">
                <label>Name</label>
                <input type="text" name="name" id="modal_name" class="uc">
            </div>

            <div class="col">
                <label>Title</label>
                <input type="text" name="title" id="modal_title" class="uc" placeholder="S/O, W/O, C/O">
            </div>

            <div class="col">
                <label>Spouse</label>
                <input type="text" name="spouse" id="modal_spouse" class="uc">
            </div>
        </div>

        <!-- ROW 3 -->
        <div class="row">
            <div class="col">
                <label>Sex</label>
                <input type="text" name="gender" id="modal_gender" readonly>
            </div>

            <div class="col">
                <label>Age</label>
                <input type="text" id="modal_age" oninput="ageToDOBPatient()">
            </div>

            <div class="col">
                <label>DOB</label>
                <input type="date" name="dob" id="modal_dob" onchange="dobToAgePatient()">
            </div>
        </div>

        <!-- ROW 4 -->
        <div class="row">
            <div class="col">
                <label>Mobile</label>
                <input type="text" name="phone" id="modal_phone" maxlength="10">
            </div>

            <div class="col">
                <label>Address</label>
                <input list="addressList" name="address" id="modal_address" class="uc">
            </div>
        </div>
        <datalist id="addressList">
        <?php
        $a = $conn->query("SELECT DISTINCT address FROM patients 
                        WHERE address IS NOT NULL AND address!='' 
                        ORDER BY address");

        if($a){
            while($r = $a->fetch_assoc()){
                echo "<option value='".htmlspecialchars($r['address'], ENT_QUOTES)."'>";
            }
        }
        ?>
        </datalist>

        <br>

        <button class="btn btn-green" type="submit">Save</button>
        <button class="btn btn-gray" type="button" onclick="closePatientModal()">Close</button>

    </form>
</div>

<!-- EDIT VITAL MODAL -->
<div id="vitalsOverlay" onclick="closeVitalsModal()"></div>
<div id="vitalsModal" style="display:none;">
    <h3>Vitals</h3>

    <input type="hidden" id="v_appointment_id">
    <input type="hidden" id="v_visit_id">

    <table class="vitals-table">

    <!-- ROW 1 -->
    <tr>
        <td>
            BP<br>
            <input type="number" id="v_bp_sys" placeholder="Sys"> /
            <input type="number" id="v_bp_dia" placeholder="Dia">
        </td>

        <td>
            Pulse<br>
            <input type="number" id="v_pulse">
        </td>

        <td>
            Sugar<br>
            <input type="number" id="v_bsugar">
        </td>

        <td>
            Height<br>
            <input type="number" id="v_height" oninput="calcBMI()">
        </td>
    </tr>

    <!-- ROW 2 -->
    <tr>
        <td>
            Weight<br>
            <input type="number" id="v_weight" oninput="calcBMI()">
        </td>

        <td>
            BMI<br>
            <input type="text" id="v_bmi" readonly>
        </td>

        <td>
            Temp<br>
            <input type="number" id="v_temp">
        </td>

        <td>
            SpO2<br>
            <input type="number" id="v_spo2">
        </td>
    </tr>

</table>

    <br>

    <button onclick="saveVitals()" class="btn btn-green">Save</button>
    <button onclick="closeVitalsModal()" class="btn btn-gray">Close</button>
</div>

<script>
function openPatientModal(row){

    document.getElementById("modal_patient_id").value = row.patient_id;
    document.getElementById("modal_patient_code").value = "AH" + String(row.patient_id).padStart(5,'0');

    document.getElementById("modal_prefix").value = (row.prefix || '').toUpperCase();
    document.getElementById("modal_name").value   = (row.name || '').toUpperCase();
    document.getElementById("modal_title").value  = (row.title || '').toUpperCase();
    document.getElementById("modal_spouse").value = (row.spouse || '').toUpperCase();

    document.getElementById("modal_phone").value  = row.phone || '';
    document.getElementById("modal_address").value= (row.address || '').toUpperCase();
    document.getElementById("modal_dob").value    = row.dob || '';

    // ✅ IMPORTANT FIX
    setGenderFromPrefix();   // 👈 THIS LINE ADDED

    // OR if you want exact DB value instead:
    // document.getElementById("modal_gender").value = row.gender || '';

    dobToAgePatient();

    document.getElementById("patientModal").style.display = "block";
}

function closePatientModal(){ document.getElementById('patientModal').style.display='none'; }

function setGenderFromPrefix(){
    let prefix = document.getElementById("modal_prefix").value.toUpperCase();
    let gender = document.getElementById("modal_gender");

    if(prefix === "MR" || prefix === "MASTER"){
        gender.value = "Male";
    } 
    else if(prefix === "MRS" || prefix === "MS" || prefix === "BABY"){
        gender.value = "Female";
    } 
    else{
        gender.value = "";
    }
}

function dobToAgePatient(){
    let dob=document.getElementById('modal_dob').value;
    if(!dob) return;
    let birth=new Date(dob), today=new Date();
    let years=today.getFullYear()-birth.getFullYear();
    let months=today.getMonth()-birth.getMonth();
    let days=today.getDate()-birth.getDate();
    if(days<0){months--; days+=new Date(today.getFullYear(),today.getMonth(),0).getDate();}
    if(months<0){years--; months+=12;}
    document.getElementById('modal_age').value=years+' '+months+' '+days;
}

function ageToDOBPatient(){
    let ageStr=document.getElementById('modal_age').value.trim();
    if(!ageStr) return;
    let parts=ageStr.split(' ').map(Number);
    let years=parts[0]||0, months=parts[1]||0, days=parts[2]||0;
    let today=new Date(), dob=new Date(today.getFullYear()-years, today.getMonth()-months, today.getDate()-days);
    let month=("0"+(dob.getMonth()+1)).slice(-2), day=("0"+dob.getDate()).slice(-2);
    document.getElementById('modal_dob').value=dob.getFullYear()+'-'+month+'-'+day;
}

// Draggable
dragElement(document.getElementById("patientModal"));
function dragElement(elmnt){
    let header=document.getElementById("patientModalHeader");
    (header||elmnt).onmousedown=dragMouseDown;
    let pos1=0,pos2=0,pos3=0,pos4=0;
    function dragMouseDown(e){ e.preventDefault(); pos3=e.clientX; pos4=e.clientY; document.onmouseup=closeDragElement; document.onmousemove=elementDrag;}
    function elementDrag(e){ e.preventDefault(); pos1=pos3-e.clientX; pos2=pos4-e.clientY; pos3=e.clientX; pos4=e.clientY; elmnt.style.top=(elmnt.offsetTop-pos2)+'px'; elmnt.style.left=(elmnt.offsetLeft-pos1)+'px';}
    function closeDragElement(){document.onmouseup=null; document.onmousemove=null;}
}

// Placeholder Vitals Modal
function openVitalsModal(patient){
    alert('Vitals modal for '+patient.name+' (visit_id: '+patient.visit_id+')');
}

/* OPEN VITALS MODAL */
function openVitalsModal(v){
    if(!v) return alert("No data passed");

    document.getElementById('v_appointment_id').value = v.appointment_id || '';
    document.getElementById('v_visit_id').value = v.visit_id || '';

    document.getElementById('v_bp_sys').value = v.bp_sys || '';
    document.getElementById('v_bp_dia').value = v.bp_dia || '';
    document.getElementById('v_pulse').value = v.pulse || '';
    document.getElementById('v_bsugar').value = v.bsugar || '';
    document.getElementById('v_height').value = v.height || '';
    document.getElementById('v_weight').value = v.weight || '';
    document.getElementById('v_bmi').value = v.bmi || '';
    document.getElementById('v_temp').value = v.temp || '';
    document.getElementById('v_spo2').value = v.spo2 || '';

    // ✅ SHOW MODAL + OVERLAY
    document.getElementById('vitalsModal').style.display = 'block';
    document.getElementById('vitalsOverlay').style.display = 'block';

    // ✅ autofocus
    setTimeout(() => {
        document.getElementById('v_bp_sys').focus();
    }, 100);
}

/* CLOSE VITALS MODAL */
function closeVitalsModal(){
    document.getElementById('vitalsModal').style.display = 'none';
    document.getElementById('vitalsOverlay').style.display = 'none';
}

/* CALCULATE BMI */
function calcBMI(){
    let h = document.getElementById('v_height').value;
    let w = document.getElementById('v_weight').value;
    if(!h || !w) return;
    h = h / 100; // cm → m
    document.getElementById('v_bmi').value = (w / (h*h)).toFixed(2);
}

/* SAVE VITALS VIA AJAX AND UPDATE ROW */
function saveVitals(){
    let data = {
        appointment_id: document.getElementById('v_appointment_id').value,
        visit_id: document.getElementById('v_visit_id').value,
        bp_sys: document.getElementById('v_bp_sys').value,
        bp_dia: document.getElementById('v_bp_dia').value,
        pulse: document.getElementById('v_pulse').value,
        bsugar: document.getElementById('v_bsugar').value,
        height: document.getElementById('v_height').value,
        weight: document.getElementById('v_weight').value,
        bmi: document.getElementById('v_bmi').value,
        temp: document.getElementById('v_temp').value,
        spo2: document.getElementById('v_spo2').value
    };

    fetch('../visits/controller/save_vitals.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest' // 🔥 needed for auth
        },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(res => {
        if(res.success){
            alert("Vitals saved successfully!");
            closeVitalsModal();

            // Update table row status to "Completed" and change row color
            let appointmentId = data.appointment_id;
            let row = document.querySelector('tr[data-appointment-id="'+appointmentId+'"]');
            if(row){
                row.classList.remove('row-progress', 'row-pending');
                row.classList.add('row-completed');
                row.querySelector('td:nth-child(9)').innerText = 'Completed';
            }
        } else {
            alert("Error saving vitals: " + res.message);
        }
    })
    .catch(err => alert("AJAX error: "+err));
}
</script>
<script>
function resetSearch(){
    // Clear input
    document.getElementById('searchBox').value = '';

    // Reload page WITHOUT query string
    window.location.href = window.location.pathname;
}
</script>
<script>
document.getElementById('searchBox').addEventListener('input', function(){
    if(this.value.trim() === ''){
        window.location.href = window.location.pathname;
    }
});

document.addEventListener("input", function(e){
    if(e.target.classList.contains("uc")){
        e.target.value = e.target.value.toUpperCase();
    }
});
</script>
