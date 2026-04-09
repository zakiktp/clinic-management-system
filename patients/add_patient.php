<?php
require_once '../core/init.php';
requireRole(['admin','reception']);

date_default_timezone_set("Asia/Kolkata");


/* Generate next patient code */
$result = $conn->query("SELECT AUTO_INCREMENT FROM information_schema.TABLES 
                        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'patients'");
$row = $result->fetch_assoc();
$next_id = $row['AUTO_INCREMENT'];
$next_code = "AH" . str_pad($next_id, 5, "0", STR_PAD_LEFT);

$today = date("Y-m-d");
?>

<!DOCTYPE html>
<html>
<head>
<title>ANSAR POLYCLINIC</title>
<style>
body{font-family: Arial,sans-serif; background:#f2f7fb; margin:0; padding:0;}
.header{background:#0b6fa4;color:white;padding:15px;text-align:center;font-size:28px;font-weight:bold;letter-spacing:1px;}
.form-box{width:420px;margin:40px auto;background:#DEFEE2;padding:30px;border:1px solid #278154;border-radius:10px;box-shadow:0 0 10px rgba(236, 214, 214, 0.1);}
label{font-weight:bold;color:#333;}
input, select, textarea{width:100%;padding:10px;margin-top:5px;margin-bottom:15px;border:1px solid #ccc;border-radius:5px;font-size:14px;}
input[type="text"], textarea{text-transform:uppercase;}
input:focus, select:focus{border-color:#0b6fa4;outline:none;box-shadow:0 0 3px #0b6fa4;}
button{width:100%;padding:12px;background:#0b6fa4;color:white;border:none;border-radius:5px;font-size:16px;cursor:pointer;}
button:hover{background:#09567f;}
.top-buttons{display:flex;gap:10px;margin-bottom:20px;}
.top-buttons button{padding:8px 15px;background:#0b6fa4;color:white;border:none;border-radius:5px;cursor:pointer;}
.top-buttons button:hover{background:#084f73;}
input::placeholder, textarea::placeholder {color: #ccc;opacity: 1;        /* ensures it displays consistently across browsers */
}
</style>
<style>
.row{display:flex; gap:10px; margin-bottom:12px;}
.col{flex:1;}
.col-2{flex:2;}
</style>
</head>
<body>

<div class="header">ANSAR POLYCLINIC</div>

<div class="form-box">

<?php if(isset($_GET['msg']) && $_GET['msg']=='saved'){
    echo "<p style='color:green;font-weight:bold;'>Patient Saved Successfully</p>";
} ?>

<div class="top-buttons">
<a href="patient_list.php"><button type="button">Patient List</button></a>
<a href="../index.php"><button type="button">Exit to Dashboard</button></a>
</div>

<form action="save_patient.php" method="POST">

<!-- ROW 1 -->
<div class="row">
    <div class="col">
        <label>Patient ID</label>
        <input type="text" value="<?php echo $next_code; ?>" readonly>
    </div>

    <div class="col">
        <label>Prefix</label>
        <select name="prefix" id="prefix" required>
            <option value="">Select</option>
            <option>Mr</option>
            <option>Mrs</option>
            <option>Ms</option>
            <option>Master</option>
            <option>Baby</option>
        </select>
    </div>
</div>

<!-- ROW 2 -->
<div class="row">
    <div class="col-2">
        <label>Patient Name</label>
        <input type="text" name="name" required>
    </div>

    <div class="col">
        <label>Title</label>
        <select name="title" id="title">
            <option value="">Select</option>
            <option value="S/O">S/O</option>
            <option value="D/O">D/O</option>
            <option value="W/O">W/O</option>
            <option value="C/O">C/O</option>
        </select>
    </div>

    <div class="col">
        <label>Spouse</label>
        <input type="text" name="spouse" style="text-transform:uppercase;">
    </div>
</div>

<!-- ROW 3 -->
<div class="row">
    <div class="col">
        <label>Age</label>
        <input type="text" name="age" id="age" placeholder="25 3 10" required>
    </div>

    <div class="col">
        <label>DOB</label>
        <input type="date" name="dob" id="dob">
    </div>

    <div class="col">
        <label>Gender</label>
        <select name="gender" id="gender">
            <option value="">Gender</option>
            <option>Male</option>
            <option>Female</option>
        </select>
    </div>
</div>


<!-- ROW 4 -->
<div class="row">
    <div class="col">
        <label>Phone</label>
        <input type="text" name="phone" placeholder="Phone" maxlength="10" pattern="[0-9]{10}" title="Enter 10 digit phone number" required>
    </div>
    <div class="col-2">
        <label>Address</label>
        <input type="text" name="address" list="addresslist" placeholder="Address" required>
        <datalist id="addresslist">
        <?php
        $a = $conn->query("SELECT DISTINCT address FROM patients WHERE address IS NOT NULL AND address!='' ORDER BY address");
        if($a){
            while($r=$a->fetch_assoc()){
                echo "<option value='".$r['address']."'>";
            }
        }
        ?>
        </datalist>
    </div>
</div>

<button type="submit">Save Patient</button>

</form>
</div>

<script>
// DOB → AGE
document.getElementById("dob").addEventListener("change", function(){
    let dob = new Date(this.value);
    let today = new Date();
    let y = today.getFullYear() - dob.getFullYear();
    let m = today.getMonth() - dob.getMonth();
    let d = today.getDate() - dob.getDate();
    if(d < 0){ m--; d += 30; }
    if(m < 0){ y--; m += 12; }
    document.getElementById("age").value = y+" "+m+" "+d;
});

// AGE → DOB
document.getElementById("age").addEventListener("blur", function(){
    let val = this.value.trim();
    if(val == "") return;
    let parts = val.split(" ");
    let years = parseInt(parts[0]) || 0;
    let months = parseInt(parts[1]) || 0;
    let days = parseInt(parts[2]) || 0;
    let today = new Date();
    today.setFullYear(today.getFullYear() - years);
    today.setMonth(today.getMonth() - months);
    today.setDate(today.getDate() - days);
    let dob = today.toISOString().split('T')[0];
    document.getElementById("dob").value = dob;
});

// Phone input cleanup
document.querySelector("input[name='phone']").addEventListener("input", function () {
    this.value = this.value.replace(/[^0-9]/g,''); 
    if(this.value.length > 10){ this.value = this.value.slice(0,10); }
});
</script>
<script>
// Auto gender from prefix
document.addEventListener("DOMContentLoaded", function(){

    const prefixEl = document.getElementById("prefix");
    const genderEl = document.getElementById("gender");
    const titleEl  = document.getElementById("title");

    if(prefixEl){
        prefixEl.addEventListener("change", function(){
            let prefix = this.value;

            if(prefix === "Mr" || prefix === "Master"){
                genderEl.value = "Male";
                titleEl.value  = "S/O";
            }
            else if(prefix === "Mrs"){
                genderEl.value = "Female";
                titleEl.value  = "W/O";
            }
            else if(prefix === "Ms" || prefix === "Baby"){
                genderEl.value = "Female";
                titleEl.value  = "D/O";
            }
            else{
                genderEl.value = "";
                titleEl.value  = "";
            }
        });
    }

});
</script>
</body>
</html>