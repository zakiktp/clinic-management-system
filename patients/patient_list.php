<?php
require_once '../core/init.php';

// Allow ONLY admin & doctor
requireRole(['admin','doctor']);

function formatAge($dob){
    if(empty($dob)) return '-';
    $dobDate = new DateTime($dob);
    $today   = new DateTime();
    $diff    = $today->diff($dobDate);
    return $diff->y . "Y " . $diff->m . "M " . $diff->d . "D";
}

// Fetch patients
$patients = $conn->query("SELECT * FROM patients ORDER BY patient_id DESC");

// Fetch distinct addresses for datalist
$addresses = $conn->query("SELECT DISTINCT address FROM patients ORDER BY address");

$stmt = $conn->prepare("
    UPDATE patients SET
        prefix=?, name=?, title=?, spouse=?, dob=?, gender=?, phone=?, address=?
    WHERE patient_id=?
");

$stmt->bind_param(
    "ssssssssi",
    $prefix, $name, $title, $spouse, $dob, $gender, $phone, $address, $patient_id
);

$ok = $stmt->execute();
?>

<!DOCTYPE html>
<html>
<head>
<title>Patient List</title>
<style>
body{font-family:Arial; background:#f4f8fb;}
.top-bar{display:flex;align-items:center;gap:15px;margin-bottom:15px;flex-wrap:wrap;}
.top-bar a{text-decoration:none;}
.search-box{margin-left:auto;}
.search-box input{padding:8px;width:250px;border:1px solid #ccc;border-radius:4px;}
.exit-btn{background:#c0392b;color:white;padding:8px 14px;text-decoration:none;border-radius:5px;font-weight:bold;}
.exit-btn:hover{background:#922b21;}
table{border-collapse:collapse;background:white;width:100%;}
th{background:#0b6fa4;color:white;padding:8px;}
td{padding:6px;}
.update-btn{background:#27ae60;color:white;border:none;padding:6px 10px;border-radius:4px;cursor:pointer;}
.update-btn:hover{background:#1e8449;}
.delete-btn {background:#dc3545;color:white;border:none;padding:5px 8px;cursor:pointer;}
.delete-btn:hover{background:#a71d2a;}
#editPopup{display:none;position:fixed;top:20%;left:35%;background:white;padding:20px;border:1px solid #ccc;box-shadow:0 0 10px #999;z-index:1000;}
#editPopup input, #editPopup select{width:100%;padding:6px;margin-bottom:10px;}
.filter-row input {width: 90%;padding: 5px;text-align: center;}
.modal{display:none;position:fixed;top:10%;
    left:50%;
    transform:translateX(-50%);
    width:420px;
    background:#fff;
    border-radius:8px;
    box-shadow:0 5px 15px rgba(0,0,0,0.3);
    z-index:1000;
    padding:15px;
}

.modal-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-weight:bold;
    margin-bottom:10px;
}

.modal-header button{
    background:none;
    border:none;
    font-size:18px;
    cursor:pointer;
}

.modal-form label{
    font-size:12px;
    font-weight:bold;
}

.modal-form input,
.modal-form select{
    width:100%;
    padding:5px;
    margin-top:3px;
    margin-bottom:8px;
    font-size:13px;
}

.grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    gap:10px;
}

.modal-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-top:10px;
}

.btn-save{
    background:#27ae60;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:4px;
}

.btn-cancel{
    background:#777;
    color:white;
    border:none;
    padding:6px 12px;
    border-radius:4px;
}
.grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr); /* 3 columns layout */
    gap: 10px;
}

.grid div {
    display: flex;
    flex-direction: column;
}

/* Row-specific adjustments */
.grid div:nth-child(1),
.grid div:nth-child(2) {
    grid-column: span 1;
}

/* Row 2 (Name + Title + Spouse) → already 3 columns */

/* Address full width */
.grid div:last-child {
    grid-column: span 2;
}

/* Inputs fix */
.grid input,
.grid select {
    width: 100%;
    box-sizing: border-box;
}

/* Uppercase */
.uc {
    text-transform: uppercase;
}
</style>
</head>
<body>

<h2>Patient List</h2>

<div class="top-bar">
    <a href="../index.php" class="exit-btn">Exit to Dashboard</a>
    <a href="export_excel.php">Export Excel</a>
    <a href="export_pdf.php" target="_blank">Export PDF</a>
    <a href="patient_list.php"><button type="button">Reset</button></a>
    <div class="search-box">
        <input type="text" id="searchBox" placeholder="Search Name / Phone / Address">
    </div>
</div>

<table id="patientTable" border="1">
<tr>
    <th style="text-align:center;">Patient ID</th>
    <th style="text-align:left;">Name</th>
    <th style="text-align:center;">DOB</th>
    <th style="text-align:center;">Age</th>
    <th style="text-align:center;">Gender</th>
    <th style="text-align:center;">Phone</th>
    <th style="text-align:left;">Address</th>
    <th>Action</th>
</tr>

<tr class="filter-row">
    <td><input placeholder="Patient ID"></td>
    <td><input placeholder="Name"></td>
    <td><input placeholder="DD/MM/YYYY"></td>
    <td><input placeholder="Age (12Y 3M 2D)"></td>
    <td><input placeholder="Gender"></td>
    <td><input placeholder="Phone"></td>
    <td><input placeholder="Address"></td>
    <td></td>
</tr>

<?php while($p = $patients->fetch_assoc()): 
    $patient_code = "AH" . str_pad($p['patient_id'], 5, "0", STR_PAD_LEFT);
?>
<?php 
$prefix  = strtoupper($p['prefix'] ?? '');
$name    = strtoupper($p['name'] ?? '');
$title   = strtoupper($p['title'] ?? '');
$spouse  = strtoupper($p['spouse'] ?? '');

// Build full name
$fullName = trim("$prefix $name");

if(!empty($title) && !empty($spouse)){
    $fullName .= " $title $spouse";
}
?>
<tr>
    <td style="text-align:center;"><?= $patient_code ?></td>
    <td><?= htmlspecialchars($fullName) ?></td>
    <td style="text-align:center;"><?= !empty($p['dob']) ? date('d/m/Y', strtotime($p['dob'])) : '-' ?></td>
    <td style="text-align:center;"><?= formatAge($p['dob']) ?></td>
    <td style="text-align:center;"><?= htmlspecialchars($p['gender']) ?></td>
    <td style="text-align:center;"><?= htmlspecialchars($p['phone']) ?></td>
    <td><?= htmlspecialchars($p['address']) ?></td>
    <td>
        <button class="update-btn" 
            data-id="<?= $p['patient_id'] ?>"
            data-prefix="<?= htmlspecialchars($p['prefix']) ?>"
            data-name="<?= htmlspecialchars($p['name']) ?>"
            data-title="<?= htmlspecialchars($p['title']) ?>"   
            data-spouse="<?= htmlspecialchars($p['spouse']) ?>"
            data-dob="<?= $p['dob'] ?>"
            data-phone="<?= htmlspecialchars($p['phone']) ?>"
            data-address="<?= htmlspecialchars($p['address']) ?>"
            onclick="openEdit(this)">
            Update
        </button>
        <button class="delete-btn" onclick="deletePatient(<?= $p['patient_id'] ?>)">Delete</button>
    </td>
</tr>
<?php endwhile; ?>
</table>

<div style="margin-top:10px;font-weight:bold;">
    Total Records: <span id="rowCount">0</span>
</div>

<!-- EDIT POPUP -->
<div id="editPopup" class="modal">
    <div class="modal-header">
        <span>Edit Patient</span>
        <button onclick="closePopup()">✖</button>
    </div>

    <form action="update_patient.php" method="POST" class="modal-form">

        <div class="grid">

            <!-- ROW 1 -->
            <div>
                <label>Patient ID</label>
                <input type="hidden" name="patient_id" id="edit_id">
                <input type="text" id="edit_display_id" readonly>
            </div>

            <div>
                <label>Prefix</label>
                <select name="prefix" id="edit_prefix" onchange="setGenderFromPrefix()">
                    <option value="">Select</option>
                    <option>Mr</option>
                    <option>Mrs</option>
                    <option>Ms</option>
                    <option>Master</option>
                    <option>Baby</option>
                </select>
            </div>

            <!-- ROW 2 -->
            <div>
                <label>Name</label>
                <input type="text" name="name" id="edit_name" required class="uc">
            </div>

            <div>
                <label>Title</label>
                <input type="text" name="title" id="edit_title" placeholder="S/O, W/O" class="uc">
            </div>

            <div>
                <label>Spouse</label>
                <input type="text" name="spouse" id="edit_spouse" class="uc">
            </div>

            <!-- ROW 3 -->
            <div>
                <label>Age</label>
                <input type="text" id="edit_age" placeholder="25 3 10" oninput="ageToDOB()">
            </div>

            <div>
                <label>DOB</label>
                <input type="date" name="dob" id="edit_dob" onchange="dobToAge()">
            </div>

            <div>
                <label>Gender</label>
                <input type="text" name="gender" id="edit_gender" readonly>
            </div>

            <!-- ROW 4 -->
            <div>
                <label>Phone</label>
                <input type="text" name="phone" id="edit_phone" maxlength="10">
            </div>

            <div style="grid-column: span 2;">
                <label>Address</label>
                <input list="addressList" name="address" id="edit_address" class="uc">
            </div>
            <datalist id="addressList">
                    <?php 
                    $addr = $conn->query("SELECT DISTINCT address FROM patients WHERE address IS NOT NULL AND address!=''");
                    while($a = $addr->fetch_assoc()){ ?>
                        <option value="<?php echo htmlspecialchars($a['address']); ?>">
                    <?php } ?>
                    </datalist>

        </div>
            
        <div class="modal-actions">
            <button type="submit" class="btn-save">Save</button>
            <button type="button" onclick="closePopup()" class="btn-cancel">Cancel</button>
        </div>

    </form>
</div>

<script>
// OPEN EDIT POPUP
function openEdit(btn){
    document.getElementById("editPopup").style.display="block";

    let prefix = (btn.dataset.prefix || '').trim();
    prefix = prefix.charAt(0).toUpperCase() + prefix.slice(1).toLowerCase();

    let id = btn.dataset.id;
    let patientCode = "AH" + String(id).padStart(5, '0');

    // Send real ID to backend
    document.getElementById("edit_id").value = id;

    // Show formatted ID to user
    document.getElementById("edit_display_id").value = patientCode;
    document.getElementById("edit_prefix").value = prefix;
    document.getElementById("edit_name").value = btn.dataset.name || '';
    document.getElementById('edit_title').value = btn.dataset.title || '';
    document.getElementById('edit_spouse').value = btn.dataset.spouse || '';
    document.getElementById("edit_phone").value = btn.dataset.phone || '';
    document.getElementById("edit_address").value = btn.dataset.address || '';
    document.getElementById("edit_dob").value = btn.dataset.dob || '';
    if(btn.dataset.dob) dobToAge();

    setGenderFromPrefix();
}

// CLOSE POPUP
function closePopup(){
    document.getElementById("editPopup").style.display="none";
}

// PREFIX → GENDER
function setGenderFromPrefix(){
    let prefix = document.getElementById("edit_prefix").value;
    let gender = document.getElementById("edit_gender");

    if(prefix === "Mr" || prefix === "Master"){
        gender.value = "Male";
    }
    else if(prefix === "Mrs" || prefix === "Ms" || prefix === "Baby"){
        gender.value = "Female";
    }
    else{
        gender.value = "";
    }
}

// DOB → AGE (YY MM DD)
function dobToAge(){
    let dob = document.getElementById("edit_dob").value;
    if(!dob){
        document.getElementById("edit_age").value = "";
        return;
    }

    let birth = new Date(dob);
    let today = new Date();

    let y = today.getFullYear() - birth.getFullYear();
    let m = today.getMonth() - birth.getMonth();
    let d = today.getDate() - birth.getDate();

    if(d < 0){
        m--;
        d += new Date(today.getFullYear(), today.getMonth(), 0).getDate();
    }

    if(m < 0){
        y--;
        m += 12;
    }

    document.getElementById("edit_age").value = `${y}Y ${m}M ${d}D`;
}

// AGE → DOB (accepts flexible input)
function ageToDOB(){

    let age = document.getElementById("edit_age").value.trim();
    if(!age) return;

    // Accept formats:
    // 12Y 11M 22D
    // 12 11 22
    let y=0,m=0,d=0;

    let match = age.match(/(\d+)\s*Y?\s*(\d*)\s*M?\s*(\d*)\s*D?/i);

    if(match){
        y = parseInt(match[1]) || 0;
        m = parseInt(match[2]) || 0;
        d = parseInt(match[3]) || 0;
    } else {
        return;
    }

    let today = new Date();
    let dob = new Date(
        today.getFullYear() - y,
        today.getMonth() - m,
        today.getDate() - d
    );

    let month = ("0" + (dob.getMonth()+1)).slice(-2);
    let day   = ("0" + dob.getDate()).slice(-2);

    document.getElementById("edit_dob").value =
        dob.getFullYear()+"-"+month+"-"+day;
}

// PHONE validation
document.getElementById("edit_phone").addEventListener("input", function(e){
    e.target.value = e.target.value.replace(/\D/g,'').slice(0,10);
});

// Delete patient
function deletePatient(id){
    if(confirm("Are you sure you want to delete this patient?")){
        window.location.href = "delete_patient.php?patient_id=" + id;
    }
}

</script>

<script>
// Cache rows and their text content
const table = document.getElementById("patientTable");
const rows = Array.from(table.querySelectorAll("tr")).slice(2); // skip header + filter row

// Build an array of cell texts for faster search
const rowData = rows.map(row => {
    const cells = Array.from(row.querySelectorAll("td"));
    return cells.map(c => c.innerText.toLowerCase());
});

// Global function to filter table
function filterTable(){
    const globalSearch = document.getElementById("searchBox").value.toLowerCase();
    const columnInputs = Array.from(document.querySelectorAll("#patientTable .filter-row input")).map(i => i.value.toLowerCase());

    let count = 0;
    rows.forEach((row, idx) => {
        const cells = rowData[idx];

        // Global search across all columns
        let show = !globalSearch || cells.some(c => c.includes(globalSearch));

        // Column filters
        columnInputs.forEach((filter, colIdx) => {
            if(filter && !cells[colIdx].includes(filter)){
                show = false;
            }
        });

        row.style.display = show ? "" : "none";
        if(show) count++;
    });

    document.getElementById("rowCount").innerText = count;
}

// Debounce to reduce calls while typing
function debounce(func, delay=200){
    let timer;
    return function(...args){
        clearTimeout(timer);
        timer = setTimeout(()=>func.apply(this,args), delay);
    }
}

// Event listeners
document.getElementById("searchBox").addEventListener("keyup", debounce(filterTable));
document.querySelectorAll("#patientTable .filter-row input").forEach(input => {
    input.addEventListener("keyup", debounce(filterTable));
});
document.getElementById("searchBox").addEventListener("input", function(){
    if(this.value === "") filterTable();
});

// Initial count
filterTable();

document.addEventListener("input", function(e){
    if(e.target.tagName === "INPUT" || e.target.tagName === "TEXTAREA"){
        if(e.target.type !== "email" && e.target.type !== "password"){
            e.target.value = e.target.value.toUpperCase();
        }
    }
});
</script>
</body>
</html>