<?php
$ageText = '';

if (!empty($data['dob'])) {
    $dob = new DateTime($data['dob']);
    $today = new DateTime();
    $diff = $today->diff($dob);
    $ageText = "{$diff->y}Y {$diff->m}M {$diff->d}D";
}

// ✅ Visit Date (fallback to today if invalid)
if (!empty($data['visit_date']) && $data['visit_date'] != '0000-00-00') {
    $visitDate = date('d/m/Y', strtotime($data['visit_date']));
} else {
    $visitDate = date('d/m/Y'); // fallback to current date
}

// ✅ Patient ID format
$patientCode = !empty($data['patient_id']) 
    ? 'AH' . str_pad($data['patient_id'], 5, '0', STR_PAD_LEFT) 
    : '';

// ✅ Full Name
$fullName = trim(
    ($data['prefix'] ?? '') . ' ' .
    ($data['name'] ?? '') . ' ' .
    ($data['title'] ?? '') . ' ' .
    ($data['spouse'] ?? '')
);

// ✅ Address
$address = $data['address'] ?? '';
?>

<div class="card p-2 mb-2 bg-light border">

    <!-- 🔹 TOP LINE -->
    <div>
        <b>
            <?= $visitDate ? $visitDate . ' | ' : '' ?>
            <?= $patientCode ? $patientCode . ' | ' : '' ?>
            <?= strtoupper($fullName) ?>
        </b>
    </div>

    <!-- 🔹 SECOND LINE -->
    <div style="font-size:13px;">
        <?= $ageText ? $ageText . ' | ' : '' ?>
        <?= !empty($data['gender']) ? strtoupper($data['gender']) . ' | ' : '' ?>
        <?= !empty($data['phone']) ? $data['phone'] . ' | ' : '' ?>
        <?= $address ?>
    </div>

</div>