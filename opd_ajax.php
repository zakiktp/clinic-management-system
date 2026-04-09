<?php
require_once __DIR__ . '/core/init.php';

$columns = ['id','name','age','sex','address','fee','visit_datetime'];

// Read POST parameters safely
$limit = isset($_POST['length']) ? intval($_POST['length']) : 25;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$search = $_POST['search']['value'] ?? '';
$startDate = $_POST['startDate'] ?? '';
$endDate = $_POST['endDate'] ?? '';

// Base query
$where = " WHERE 1=1 ";
$params = [];
$types = "";

// Global search
if(!empty($search)){
    $where .= " AND (id LIKE ? OR name LIKE ? OR age LIKE ? OR sex LIKE ? OR address LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam,$searchParam,$searchParam,$searchParam,$searchParam]);
    $types .= str_repeat("s",5);
}

// Column filters
if(isset($_POST['columns'])){
    foreach($_POST['columns'] as $i => $col){
        $val = $col['search']['value'] ?? '';
        if($val !== '' && isset($columns[$i])){
            $where .= " AND {$columns[$i]} LIKE ?";
            $params[] = "%$val%";
            $types .= "s";
        }
    }
}

// Date filters
if(!empty($startDate)){
    $where .= " AND visit_datetime >= ?";
    $params[] = $startDate . " 00:00:00";
    $types .= "s";
}
if(!empty($endDate)){
    $where .= " AND visit_datetime <= ?";
    $params[] = $endDate . " 23:59:59";
    $types .= "s";
}

// TOTAL COUNT
$totalData = $conn->query("SELECT COUNT(*) as t FROM opd_n")->fetch_assoc()['t'];
$totalFilteredQuery = "SELECT COUNT(*) as t FROM opd_n $where";
$stmt = $conn->prepare($totalFilteredQuery);
if($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalFiltered = $stmt->get_result()->fetch_assoc()['t'];
$stmt->close();

// TOTAL AMOUNT
$totalAmountQuery = "SELECT SUM(fee) as total FROM opd_n $where";
$stmt = $conn->prepare($totalAmountQuery);
if($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalAmount = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// DATA
$dataQuery = "SELECT * FROM opd_n $where ORDER BY id DESC ".($limit > 0 ? "LIMIT ?,?" : "");
$stmt = $conn->prepare($dataQuery);
if($limit > 0){
    $bindParams = array_merge($params, [$start, $limit]);
    $bindTypes = $types . "ii";
    $stmt->bind_param($bindTypes, ...$bindParams);
}else{
    if($types) $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
$pageTotal = 0;

while($row = $result->fetch_assoc()){
    $pageTotal += $row['fee'];
    $data[] = [
        $row['id'],
        $row['name'],
        $row['age'],
        $row['sex'],
        $row['address'],
        number_format($row['fee'],2),
        $row['visit_datetime']
    ];
}
$stmt->close();

echo json_encode([
    "draw" => intval($_POST['draw']),
    "recordsTotal" => $totalData,
    "recordsFiltered" => $totalFiltered,
    "data" => $data,
    "totalAmount" => floatval($totalAmount),
    "pageTotal" => floatval($pageTotal)
]);