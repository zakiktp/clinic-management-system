<?php
require_once __DIR__ . '/../core/init.php';
requireRole(['admin','doctor','reception']);

if(!isset($_GET['id'])){
    die("Patient not found");
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if(!$data){
    die("Patient not found");
}

$formatted_id = sprintf("AH%05d", $data['patient_id']);
?>

<div class="card p-3">

    <h3 class="text-center text-primary mb-3">Edit Patient</h3>

    <form id="editPatientForm" action="<?= BASE_URL ?>patients/update_patient.php" method="POST">

        <!-- Row 1 -->
        <div class="row mb-2">
            <div class="col">
                <label>Patient ID</label>
                <input type="text" class="form-control" value="<?= $formatted_id ?>" readonly>
                <input type="hidden" name="patient_id" value="<?= $data['patient_id'] ?>">
            </div>

            <div class="col">
                <label>Prefix</label>
                <select name="prefix" id="prefix" class="form-control" required>
                    <?php
                    $prefixes = ['MR','MRS','MS','BABY','MASTER'];
                    foreach($prefixes as $p){
                        $selected = ($data['prefix'] === $p) ? 'selected' : '';
                        echo "<option value='$p' $selected>$p</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col">
                <label>Name</label>
                <input type="text" id="name" name="name" class="form-control"
                       value="<?= htmlspecialchars($data['name']) ?>" required>
            </div>
        </div>

        <!-- Row 2 -->
        <div class="row mb-2">
            <div class="col">
                <label>Title</label>
                <input type="text" id="title" name="title" class="form-control"
                       value="<?= htmlspecialchars($data['title']) ?>">
            </div>

            <div class="col">
                <label>Spouse</label>
                <input type="text" id="spouse" name="spouse" class="form-control"
                       value="<?= htmlspecialchars($data['spouse']) ?>">
            </div>

            <div class="col">
                <label>Gender</label>
                <input type="text" id="gender" name="gender" class="form-control"
                       value="<?= htmlspecialchars($data['gender']) ?>" readonly>
            </div>
        </div>

        <!-- Row 3 -->
        <div class="row mb-2">
            <div class="col">
                <label>Age</label>
                <input type="text" id="age" name="age" class="form-control"
                       placeholder="YY MM DD"
                       value="<?= htmlspecialchars($data['age']) ?>">
            </div>

            <div class="col">
                <label>DOB</label>
                <input type="date" id="dob" name="dob" class="form-control"
                       value="<?= htmlspecialchars($data['dob']) ?>" required>
            </div>

            <div class="col">
                <label>Phone</label>
                <input type="tel" id="phone" name="phone" class="form-control"
                       value="<?= htmlspecialchars($data['phone']) ?>"
                       pattern="\d{10}" maxlength="10" required>
            </div>
        </div>

        <!-- Address -->
        <div class="row mb-3">
            <div class="col-12">
                <label>Address</label>
                <input list="address_list" name="address" class="form-control" value="<?= htmlspecialchars($data['address']) ?>">
                <datalist id="address_list">
                    <?php
                    $a = $conn->query("SELECT DISTINCT address FROM patients WHERE address!=''");
                    while($r = $a->fetch_assoc()){
                        echo "<option value='".htmlspecialchars($r['address'])."'>";
                    }
                    ?>
                </datalist>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary">Update Patient</button>
            <button type="button" class="btn btn-secondary" onclick="closePatientModal()">Exit</button>
        </div>

    </form>
</div>