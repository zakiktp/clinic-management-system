<?php
require_once '../core/init.php';

$q = $_GET['q'] ?? '';
$q = trim($q);

if ($q === '') {
    exit;
}

/* =========================
   EXTRACT PATIENT ID
========================= */
$search_id = 0;

if (preg_match('/AH(\d+)/i', $q, $match)) {
    $search_id = (int)$match[1];
} elseif (is_numeric($q)) {
    $search_id = (int)$q;
}

/* =========================
   PREPARE SEARCH QUERY
========================= */
$sql = "
SELECT 
    patient_id, prefix, name, title, spouse,
    phone, age, gender, address
FROM patients
WHERE 
    patient_id = ?
    OR name LIKE ?
    OR phone LIKE ?
    OR address LIKE ?
ORDER BY patient_id DESC
LIMIT 50
";

$stmt = $conn->prepare($sql);

$like = "%{$q}%";

$stmt->bind_param("isss", $search_id, $like, $like, $like);
$stmt->execute();

$res = $stmt->get_result();

/* =========================
   NO RESULTS
========================= */
if (!$res || $res->num_rows === 0) {
    echo "
    <div class='search-popup'>
        <div class='no-data'>
            No patient found<br><br>
            <button class='btn btn-sm btn-primary' onclick='openNewPatient()'>
                + Add New Patient
            </button>
        </div>
    </div>";
    exit;
}

/* =========================
   RESULT TABLE
========================= */
echo "<div class='search-popup'>";

/* Header with new button */
echo "
<div style='padding:6px; border-bottom:1px solid #ddd; text-align:right;'>
    <button class='btn btn-sm btn-success' onclick='openNewPatient()'>
        + New Patient
    </button>
</div>
";

/* Table */
echo "<table class='search-table'>";

echo "
<tr>
    <th>ID</th>
    <th>Name</th>
    <th>Phone</th>
    <th>Age</th>
    <th>Gender</th>
    <th>Address</th>
</tr>
";

while ($row = $res->fetch_assoc()) {

    $pid = "AH" . str_pad($row['patient_id'], 5, "0", STR_PAD_LEFT);

    $prefix = strtoupper($row['prefix'] ?? '');
    $name   = strtoupper($row['name'] ?? '');
    $title  = strtoupper($row['title'] ?? '');
    $spouse = strtoupper($row['spouse'] ?? '');

    $fullName = trim("$prefix $name");

    if (!empty($title) && !empty($spouse)) {
        $fullName .= " $title $spouse";
    }

    echo "
    <tr onclick=\"openAppointment('{$row['patient_id']}')\">
        <td style='font-weight:bold; color:#0b6fa4;'>$pid</td>
        <td><strong>$fullName</strong></td>
        <td>{$row['phone']}</td>
        <td>{$row['age']}</td>
        <td>{$row['gender']}</td>
        <td>{$row['address']}</td>
    </tr>
    ";
}

echo "</table>";
echo "</div>";