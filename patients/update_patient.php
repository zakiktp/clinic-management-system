<?php
require_once '../core/init.php';
requireRole(['admin','doctor','reception']);

// AJAX detection
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    if($isAjax){ echo json_encode(['success'=>false,'message'=>'Invalid request']); exit; }
    die("Invalid request");
}

$patient_id = $_POST['patient_id'] ?? '';
$patient_id = preg_replace('/[^0-9]/','',$patient_id); // extract numeric

$prefix  = strtoupper(trim($_POST['prefix'] ?? ''));
$name    = strtoupper(trim($_POST['name'] ?? ''));
$title   = strtoupper(trim($_POST['title'] ?? ''));
$spouse  = strtoupper(trim($_POST['spouse'] ?? ''));
$dob     = $_POST['dob'] ?? '';
$gender  = strtoupper(trim($_POST['gender'] ?? ''));
$phone   = trim($_POST['phone'] ?? '');
$address = strtoupper(trim($_POST['address'] ?? ''));
$age     = trim($_POST['age'] ?? '');

// Validation
if(empty($patient_id)){
    if($isAjax){ echo json_encode(['success'=>false,'message'=>'Patient ID missing']); exit; }
    die("Patient ID missing");
}
if(!preg_match('/^\d{10}$/',$phone)){
    if($isAjax){ echo json_encode(['success'=>false,'message'=>'Phone must be 10 digits']); exit; }
    die("Phone must be 10 digits");
}

// Update using prepared statement
$stmt = $conn->prepare("
    UPDATE patients SET
        prefix = ?, name = ?, title = ?, spouse = ?, dob = ?, gender = ?, phone = ?, address = ?, age = ?
    WHERE patient_id = ?
");
$dobParam = !empty($dob) ? $dob : NULL;
$stmt->bind_param("sssssssssi",$prefix,$name,$title,$spouse,$dobParam,$gender,$phone,$address,$age,$patient_id);
$ok = $stmt->execute();

// Response
if($isAjax){
    header('Content-Type: application/json');
    echo json_encode([
        'success'=> $ok,
        'message'=> $ok ? 'Patient updated' : 'Update failed',
        'patient'=> [
            'patient_id'=>$patient_id,'prefix'=>$prefix,'name'=>$name,
            'dob'=>$dob,'gender'=>$gender,'phone'=>$phone,'address'=>$address,'age'=>$age
        ]
    ]);
    exit;
}

// Normal redirect
$redirect = $_SERVER['HTTP_REFERER'] ?? 'patients_list.php';
header("Location: ".$redirect);
exit();