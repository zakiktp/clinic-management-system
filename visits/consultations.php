<?php
require_once '../core/init.php';
requireRole(['admin','doctor']);

// Handle filters from GET
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$todayFilter = isset($_GET['today']) ? true : false;

// Base SQL
$sql = "
SELECT v.visit_id,
       v.visit_date,
       v.patient_id,
       v.amount,
       p.prefix,
       p.name AS patient_name,
       p.title,
       p.spouse,
       p.gender,
       p.dob,
       p.age AS stored_age,
       p.address,
       a.doctor_id,
       d.name AS doctor_name
FROM visits v
JOIN patients p ON p.patient_id = v.patient_id
LEFT JOIN appointments a ON a.appointment_id = v.appointment_id
LEFT JOIN doctors d ON d.doctor_id = a.doctor_id
WHERE 1
";

// Apply date filters
if($todayFilter){
    $sql .= " AND DATE(v.visit_date) = CURDATE()";
} else {
    if($from) $sql .= " AND DATE(v.visit_date) >= '$from'";
    if($to)   $sql .= " AND DATE(v.visit_date) <= '$to'";
}

$sql .= " ORDER BY v.visit_date DESC";

$result = $conn->query($sql);

// Fetch all rows for JS filtering & pagination
$allRows = [];
while($r = $result->fetch_assoc()){
    // Age in Y M D
    $ageText = '';
    if(!empty($r['dob'])){
        $dob = new DateTime($r['dob']);
        $today = new DateTime();
        $diff = $today->diff($dob);
        $ageText = $diff->y . "Y " . $diff->m . "M " . $diff->d . "D";
    } elseif(!empty($r['stored_age'])){
        $ageText = $r['stored_age'] . "Y";
    }
    // Build full name
    $prefix  = strtoupper($r['prefix'] ?? '');
    $name    = strtoupper($r['patient_name'] ?? '');
    $title   = strtoupper($r['title'] ?? '');
    $spouse  = strtoupper($r['spouse'] ?? '');

    $fullName = trim("$prefix $name");

    if(!empty($title) && !empty($spouse)){
        $fullName .= " $title $spouse";
    }
    $allRows[] = [
        'visit_date'=> $r['visit_date'],
        'patient_id'=> "AH".str_pad($r['patient_id'],5,"0",STR_PAD_LEFT),
        'patient_name'=> $fullName,
        'age'=> $ageText,
        'gender'=> $r['gender'],
        'address'=> $r['address'],
        'amount' => ($r['amount'] !== '' && $r['amount'] !== null) ? (float)$r['amount'] : '',
        'doctor_name'=> $r['doctor_name']
    ];
}
?>
<h2>Consultations</h2>
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; align-items:center;">
    <label>Show Records:</label>
    <select id="rowsPerPageSelect">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
        <option value="all">All</option>
    </select>
</div>
<div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px; align-items:center;">
    <a href="../index.php"><button>Dashboard</button></a>
    <a href="../patients/export_excel.php?<?php echo http_build_query($_GET); ?>"><button>Export Excel</button></a>
    <a href="../patients/export_pdf.php?<?php echo http_build_query($_GET); ?>" target="_blank"><button>Export PDF</button></a>
    <button onclick="window.location='?today=1'">Today</button>
    <label>From</label><input type="date" id="fromDate" value="<?php echo $from; ?>">
    <label>To</label><input type="date" id="toDate" value="<?php echo $to; ?>">
    <a href="consultations.php"><button type="button">Reset</button></a>
    <input type="text" id="globalSearch" placeholder="Search Table..." style="padding:5px;">
    <div id="doctorCards" style="display:flex; flex-wrap:wrap; gap:15px; margin-bottom:15px;"></div>
</div>

<table id="consultTable" border="1" cellpadding="6" style="border-collapse:collapse; width:100%;">
<tr style="background:#0b6fa4; color:white;">
<th style="text-align:center;">No</th>
<th style="text-align:center;">Date</th>
<th style="text-align:center;">Patient ID</th>
<th style="text-align:left;">Name</th>
<th style="text-align:center;">Age</th>
<th style="text-align:center;">Gender</th>
<th style="text-align:left;">Address</th>
<th style="text-align:right;">Amount</th>
<th style="text-align:left;">Doctor</th>
</tr>
<tbody id="tableBody">
<!-- JS will populate -->
</tbody>
</table>

<div style="margin-top:10px; font-weight:bold;">
Total Visits: <span id="totalVisits">0</span> |
Total Amount: <span id="totalAmount">0.00</span>
</div>

<!-- Pagination Controls -->
<div style="margin-top:10px;">
<button id="prevPage">Prev</button>
<span>Page <span id="currentPage">1</span> / <span id="totalPages">1</span></span>
<button id="nextPage">Next</button>
</div>

<script>

// Data
const allRows = <?php echo json_encode($allRows); ?>;

let filteredRows = [...allRows];
let currentPage = 1;
let rowsPerPage = 25;

// Elements
const tableBody = document.getElementById('tableBody');
const totalVisitsEl = document.getElementById('totalVisits');
const totalAmountEl = document.getElementById('totalAmount');
const currentPageEl = document.getElementById('currentPage');
const totalPagesEl = document.getElementById('totalPages');

const globalSearch = document.getElementById('globalSearch');
const fromDate = document.getElementById('fromDate');
const toDate = document.getElementById('toDate');


// ✅ DOCTOR CARDS FUNCTION (MOVE IT ABOVE renderTable)
function renderDoctorCards() {
    const container = document.getElementById('doctorCards');
    container.innerHTML = '';

    const doctorTotals = {};

    filteredRows.forEach(row => {
        const doctor = row.doctor_name || 'Unknown';

        let amt = row.amount;
        if (amt === '' || amt === null || amt === undefined) return;

        if (typeof amt === 'string') {
            amt = amt.replace(/,/g, '');
        }

        amt = parseFloat(amt);
        if (isNaN(amt)) return;

        if (!doctorTotals[doctor]) {
            doctorTotals[doctor] = 0;
        }

        doctorTotals[doctor] += amt;
    });

    Object.entries(doctorTotals)
    .sort((a,b) => b[1] - a[1])
    .forEach(([doc, total]) => {
        const card = document.createElement('div');

        card.style = `
            padding:2px 8px;
            border-radius:10px;
            text-align:center;
            background:#DAF6DB;
            box-shadow:0 2px 6px rgba(0,0,0,0.1);
            min-width:120px;
        `;

        card.innerHTML = `
            <div style="font-weight:bold;">${doc}</div>
            <div style="color:#2c7be5;">₹ ${total.toFixed(2)}</div>
        `;

        container.appendChild(card);
    });
}


// ✅ TABLE FUNCTION
function renderTable(){
    tableBody.innerHTML = '';

    let start = (currentPage-1)*rowsPerPage;
    let end = start + rowsPerPage;

    let pageRows = filteredRows.slice(start,end);

    pageRows.forEach((row,i)=>{
        let tr = document.createElement('tr');

        tr.innerHTML = `
        <td style="text-align:center;">${start+i+1}</td>
        <td style="text-align:center;">${formatDate(row.visit_date)}</td>
        <td style="text-align:center;">${row.patient_id}</td>
        <td>${row.patient_name}</td>
        <td style="text-align:center;">${row.age}</td>
        <td style="text-align:center;">${row.gender}</td>
        <td>${row.address}</td>
        <td style="text-align:right;">
            ${row.amount !== '' && row.amount !== null && row.amount !== undefined 
                ? parseFloat(row.amount).toFixed(2) 
                : ''}
        </td>
        <td>${row.doctor_name}</td>
        `;

        tableBody.appendChild(tr);
    });

    // Totals
    const totalVisits = filteredRows.length;

    const totalAmount = filteredRows.reduce((sum, row) => {
        let amt = parseFloat(row.amount);
        return sum + (isNaN(amt) ? 0 : amt);
    }, 0);

    totalVisitsEl.innerText = totalVisits;
    totalAmountEl.innerText = totalAmount.toFixed(2);

    totalPagesEl.innerText = Math.ceil(filteredRows.length/rowsPerPage);
    currentPageEl.innerText = currentPage;

    // ✅ IMPORTANT
    renderDoctorCards();
}


// Format date
function formatDate(d){
    let dt = new Date(d);
    return dt.toLocaleDateString('en-GB');
}


// Filters
function applyFilters(){
    const search = globalSearch.value.toUpperCase();
    const from = fromDate.value;
    const to = toDate.value;

    filteredRows = allRows.filter(row=>{
        let text = Object.values(row).join(' ').toUpperCase();

        let show = true;

        if(search && !text.includes(search)) show = false;

        if(from && row.visit_date < from) show = false;
        if(to && row.visit_date > to) show = false;

        return show;
    });

    currentPage = 1;
    renderTable();
}


// Events
globalSearch.addEventListener('keyup', applyFilters);
fromDate.addEventListener('change', applyFilters);
toDate.addEventListener('change', applyFilters);


// Pagination
document.getElementById('prevPage').addEventListener('click', ()=>{
    if(currentPage>1){
        currentPage--;
        renderTable();
    }
});

document.getElementById('nextPage').addEventListener('click', ()=>{
    if(currentPage < Math.ceil(filteredRows.length/rowsPerPage)){
        currentPage++;
        renderTable();
    }
});


// Initial load
renderTable();

</script>