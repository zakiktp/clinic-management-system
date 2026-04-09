<?php
require_once 'core/init.php';
requireRole(['admin','doctor','reception']);

$today = date("Y-m-d");

/* Today's Collection */
$sql = "
SELECT COUNT(DISTINCT v.patient_id) AS total_patients,
       SUM(v.amount) AS total_amount
FROM visits v
JOIN appointments a ON a.appointment_id = v.appointment_id
WHERE a.date = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();
$consultData = $result->fetch_assoc();

$totalPatients = $consultData['total_patients'] ?? 0;
$totalAmount   = $consultData['total_amount'] ?? 0.00;

/* Today's Appointments */
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM appointments WHERE date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();
$today_appointments_count = $res->fetch_assoc()['total'];

/* Today's Visits */
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM visits WHERE visit_date = ?");
$stmt->bind_param("s", $today);
$stmt->execute();
$res = $stmt->get_result();
$today_visits_count = $res->fetch_assoc()['total'];

/* Total Patients */
$res = $conn->query("SELECT COUNT(*) as total FROM patients");
$total_patients_count = $res->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<!-- IMPORTANT -->
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Clinic Dashboard</title>

<!-- ✅ BOOTSTRAP CSS (CRITICAL FIX) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- ✅ BOOTSTRAP ICONS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
/* --- Sidebar (existing) --- */
.sidebar {
    width: 270px;
    min-height: 100vh;
    background: linear-gradient(135deg, #007bff, #043b75);
    color: white;
    flex-shrink: 0;
}

.sidebar .nav-link {
    color: white;
}

.sidebar .nav-link:hover {
    background: rgba(255,255,255,0.1);
    border-radius: 5px;
}

/* --- SEARCH POPUP FIX --- */
.search-container {
    position: relative;
}

#searchResults {
    position: absolute;  
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    max-height: 300px;
    overflow-y: auto;
    display: none;            /* hidden until JS populates */
    z-index: 9999;
}

/* Table styling */
.search-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.search-table th {
    background: #007bff;
    color: #fff;
    padding: 6px;
    text-align: left;
}

.search-table td {
    padding: 6px;
    border-bottom: 1px solid #eee;
}

/* ✅ Zebra striping */
.search-table tr:nth-child(even) {
    background-color: #f9f9f9;
}
.search-table tr:nth-child(odd) {
    background-color: #fff;
}

/* Hover effect */
.search-table tr:hover {
    background-color: #e6f2ff;
    cursor: pointer;
}

.no-data {
    padding: 10px;
    text-align: center;
    color: red;
}


/* Action buttons */
.action-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    justify-content: center;
}

.action-buttons .btn {
    white-space: nowrap;
    font-size: 12px;
    padding: 2px 6px;
}
body {
    background: #f4f8fc;
    overflow-x: hidden;
    position: relative;
}

/* Soft animated background */
.dashboard-bg {
    position: fixed;
    inset: 0;
    z-index: -1;
    background:
        radial-gradient(circle at 20% 20%, rgba(13,110,253,0.08), transparent 30%),
        radial-gradient(circle at 80% 30%, rgba(0,200,255,0.08), transparent 30%),
        radial-gradient(circle at 50% 80%, rgba(13,110,253,0.05), transparent 35%);
    animation: floatBg 18s ease-in-out infinite alternate;
}

@keyframes floatBg {
    from { transform: translateY(0px) scale(1); }
    to   { transform: translateY(-20px) scale(1.05); }
}

/* Dashboard Cards */
.card {
    border: none;
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    transition: all .25s ease;
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(10px);
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 28px rgba(0,0,0,0.10);
}

/* Clock */
#liveClock {
    font-family: monospace;
    letter-spacing: 1px;
}
.sidebar-brand{
    padding-bottom:15px;
    border-bottom:1px solid rgba(255,255,255,0.15);
    margin-bottom:20px;
}

.sidebar-logo{
    width:72px;
    height:72px;
    object-fit:contain;
    border-radius:16px;
    background:rgba(255,255,255,0.12);
    padding:8px;
    box-shadow:0 4px 12px rgba(0,0,0,0.15);
    transition:transform .3s ease;
}

.sidebar-logo:hover{
    transform:scale(1.06);
}
.sidebar{
    box-shadow: 4px 0 18px rgba(0,0,0,0.12);
}
.sidebar{
    width:270px;
    min-height:100vh;
    background:linear-gradient(180deg,#0b5ed7,#052c65);
    color:#fff;
    position:relative;
    transition:all .3s ease;
    box-shadow:4px 0 20px rgba(0,0,0,.12);
    z-index:1000;
}

.sidebar.collapsed{
    width:85px;
}

.sidebar-brand{
    display:flex;
    align-items:center;
    gap:12px;
    padding:20px 15px;
    border-bottom:1px solid rgba(255,255,255,.15);
}

.sidebar-logo{
    width:52px;
    height:52px;
    border-radius:14px;
    background:rgba(255,255,255,.15);
    padding:6px;
    object-fit:contain;
}

.brand-text h5{
    margin:0;
    font-size:16px;
    font-weight:700;
}

.brand-text small{
    opacity:.75;
}

.sidebar.collapsed .brand-text,
.sidebar.collapsed .nav-link span{
    display:none;
}

.sidebar-menu{
    padding:15px 10px;
}

.sidebar-menu li{
    margin-bottom:8px;
}

.sidebar .nav-link{
    color:#fff;
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 14px;
    border-radius:12px;
    transition:.25s;
    font-weight:500;
}

.sidebar .nav-link i{
    font-size:18px;
    min-width:22px;
    text-align:center;
}

.sidebar .nav-link:hover{
    background:rgba(255,255,255,.14);
    box-shadow:0 0 12px rgba(255,255,255,.12);
    transform:translateX(3px);
}

.sidebar .nav-link.active{
    background:#fff;
    color:#0b5ed7;
    font-weight:700;
}

.logout-link:hover{
    background:rgba(220,53,69,.25) !important;
}

/* Toggle Button */
.sidebar-toggle{
    position:fixed;
    top:15px;
    left:15px;
    z-index:1100;
    border:none;
    background:#0b5ed7;
    color:#fff;
    width:42px;
    height:42px;
    border-radius:10px;
    display:none;
    box-shadow:0 4px 12px rgba(0,0,0,.2);
}

/* Mobile */
@media(max-width:992px){
    .sidebar{
        position:fixed;
        left:-280px;
        top:0;
    }

    .sidebar.mobile-open{
        left:0;
    }

    .sidebar-toggle{
        display:block;
    }
}
/* HEADER */
.dashboard-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:28px;
    padding:20px 24px;
    border-radius:18px;
    background:rgba(255,255,255,0.9);
    backdrop-filter:blur(12px);
    box-shadow:0 8px 28px rgba(0,0,0,.06);
}

.dashboard-title{
    font-size:28px;
    font-weight:800;
    margin:0;
    color:#0b5ed7;
}

.dashboard-subtitle{
    margin:4px 0 0;
    color:#666;
}

.role-badge{
    background:#e9f2ff;
    color:#0b5ed7;
    padding:4px 10px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
    margin-left:8px;
}

.header-right{
    display:flex;
    align-items:center;
    gap:14px;
    flex-wrap:wrap;
}

.clock-card{
    padding:10px 18px;
    border-radius:14px;
    background:linear-gradient(135deg,#0b5ed7,#0dcaf0);
    color:#fff;
    text-align:center;
    min-width:180px;
    box-shadow:0 8px 18px rgba(10, 76, 175, 0.22);
}

#liveClock{
    font-size:20px;
    font-weight:800;
    line-height:1.2;
}

#liveDate{
    font-size:12px;
    opacity:.9;
}

/* DASHBOARD CARDS */
.stats-card{
    max-width:220px;
    width:100%;
    min-height:110px;
    padding:16px;
    border-radius:14px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.stats-card:hover{
    transform:translateY(-5px);
    box-shadow:0 14px 30px rgba(0,0,0,.10);
}

.stats-card::before{
    content:'';
    position:absolute;
    top:0;
    left:0;
    width:5px;
    height:100%;
    background:linear-gradient(#0b5ed7,#0dcaf0);
}

.stats-label{
    font-size:14px;
    color:#666;
    margin-bottom:8px;
}

.stats-value{
    font-size:28px;
    font-weight:800;
    color:#0b5ed7;
    margin:0;
}

.stats-sub{
    font-size:13px;
    color:#888;
    margin-top:6px;
}
.user-avatar{
    width:70px;
    height:70px;
    border-radius:50%;
    object-fit:cover;
    border:1px solid #0b5ed7;
    box-shadow:0 4px 12px rgba(0,0,0,0.15);
}
</style>

</head>

<body>

<div class="dashboard-bg"></div>

<div class="d-flex">

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">

        <div class="sidebar-brand">
            <img src="/clinic/visits/assets/images/logo_clinic.png" class="sidebar-logo" alt="Clinic Logo">
            <div class="brand-text">
                <h5>Ansar Polyclinic</h5>
                <small>Management System</small>
            </div>
        </div>

        <ul class="nav flex-column sidebar-menu">

            <?php if(in_array($_SESSION['role'], ['admin','doctor','reception'])): ?>
            <li>
                <a href="opd_n.php" class="nav-link">
                    <i class="bi bi-clipboard-plus"></i>
                    <span>OPD Entry</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if($_SESSION['role'] == 'admin'): ?>
            <li>
                <a href="users/user_list.php" class="nav-link">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
            </li>

            <li>
                <a href="patients/patient_list.php" class="nav-link">
                    <i class="bi bi-person-vcard"></i>
                    <span>Patients</span>
                </a>
            </li>

            <li>
                <a href="appointments/appointment_list.php" class="nav-link">
                    <i class="bi bi-calendar-check"></i>
                    <span>Appointments</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(in_array($_SESSION['role'], ['admin','doctor'])): ?>
            <li>
                <a href="visits/consultations.php" class="nav-link">
                    <i class="bi bi-journal-medical"></i>
                    <span>Consultations</span>
                </a>
            </li>

            <li>
                <a href="visits/visit_history.php" class="nav-link">
                    <i class="bi bi-clock-history"></i>
                    <span>Visit History</span>
                </a>
            </li>
            <?php endif; ?>

            <li class="mt-3">
                <a href="logout.php" class="nav-link logout-link">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </li>

        </ul>
    </div>
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- MAIN CONTENT -->
<div class="flex-grow-1 p-4">

    <!-- HEADER -->
    <div class="dashboard-header">

        <?php
        $userPhoto = '/clinic/visits/assets/images/default-user.png';

        $nameLower = strtolower(trim($_SESSION['name'] ?? ''));
        $roleLower = strtolower(trim($_SESSION['role'] ?? ''));

        /* ADMIN */
        if ($roleLower === 'admin') {
            $userPhoto = '/clinic/visits/assets/images/admin.png';
        }

        /* RECEPTION / STAFF */
        elseif (str_contains($nameLower, 'nazish')) {
            $userPhoto = '/clinic/visits/assets/images/staff_nazish.png';
        }
        elseif (str_contains($nameLower, 'saima')) {
            $userPhoto = '/clinic/visits/assets/images/staff_saima.png';
        }
        elseif (str_contains($nameLower, 'sidra')) {
            $userPhoto = '/clinic/visits/assets/images/staff_sidra.png';
        }
        elseif (str_contains($nameLower, 'vishal')) {
            $userPhoto = '/clinic/visits/assets/images/staff_vishal.png';
        }
        elseif (str_contains($nameLower, 'naghma')) {
            $userPhoto = '/clinic/visits/assets/images/staff_naghma.png';
        }
        elseif (str_contains($nameLower, 'adnan')) {
            $userPhoto = '/clinic/visits/assets/images/staff_adnan.png';
        }
        elseif (str_contains($nameLower, 'uzma')) {
            $userPhoto = '/clinic/visits/assets/images/staff_uzma.png';
        }
        elseif (str_contains($nameLower, 'staff1')) {
            $userPhoto = '/clinic/visits/assets/images/staff1.png';
        }
        ?>

        <!-- LEFT -->
    <div>
        <h2 class="dashboard-title">Clinic Dashboard</h2>
        <p class="dashboard-subtitle mb-0">
            Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>
            <span class="role-badge"><?= strtoupper($_SESSION['role']) ?></span>
        </p>
    </div>

    <!-- CENTER PHOTO -->
    <div class="header-user-photo">
        <img src="<?= $userPhoto ?>" 
             alt="User Photo"
             class="user-avatar">
    </div>

    <!-- RIGHT -->
    <div class="header-right">
        <div class="clock-card">
            <div id="liveClock"></div>
            <small id="liveDate"></small>
        </div>

        <a href="logout.php" class="btn btn-danger btn-sm px-3">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>

</div>

        <!-- SEARCH + DASHBOARD -->
        <div class="row mb-4">

            <!-- SEARCH -->
            <div class="col-md-4">
                <h5>🔍 Quick Patient Search</h5>

                <div style="position:relative;">
                    <input type="text" id="patientSearch" class="form-control" placeholder="Search patient...">

                    <span id="clearSearch"
                        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer;">
                        &times;
                    </span>

                    <div id="searchResults"></div>
                </div>
            </div>

            <!-- DASHBOARD -->
            <?php if(in_array($_SESSION['role'], ['admin','doctor'])): ?>

            <div class="col-md-2 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-label">Today's Collection</div>
                    <h3 class="stats-value">₹<?= number_format($totalAmount,2) ?></h3>
                    <div class="stats-sub"><?= $totalPatients ?> Patients</div>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-label">Appointments</div>
                    <h3 class="stats-value"><?= $today_appointments_count ?></h3>
                    <div class="stats-sub">Scheduled Today</div>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-label">Total Patients</div>
                    <h3 class="stats-value"><?= $total_patients_count ?></h3>
                    <div class="stats-sub">Registered</div>
                </div>
            </div>

            <div class="col-md-2 mb-3">
                <div class="stats-card h-100">
                    <div class="stats-label">Today's Visits</div>
                    <h3 class="stats-value"><?= $today_visits_count ?></h3>
                    <div class="stats-sub">Consultations</div>
                </div>
            </div>

            <?php endif; ?>

        </div>

        <!-- TODAY APPOINTMENTS -->
        <div class="card mt-4">
            <div class="card-header bg-primary text-white">
                Today's Appointments
            </div>

            <div class="card-body p-2">

                <div class="table-responsive">

                <?php
                $sql = "
                SELECT 
                    a.appointment_id,
                    a.patient_id,
                    a.time, 
                    a.status,
                    p.prefix,
                    p.name,
                    p.title,
                    p.spouse,
                    p.dob,
                    p.address,
                    p.phone,
                    d.name AS doctor,
                    u.name AS user_name,   -- ✅ comma added
                    v.appointment_id AS has_visit
                FROM appointments a
                JOIN patients p ON p.patient_id = a.patient_id
                LEFT JOIN doctors d ON d.doctor_id = a.doctor_id
                LEFT JOIN users u ON a.created_by = u.id
                LEFT JOIN vitals v ON v.appointment_id = a.appointment_id
                WHERE a.date = '$today'
                ORDER BY a.time ASC";

                $result = $conn->query($sql);
                $appointments = $result->fetch_all(MYSQLI_ASSOC);

                include 'appointments/partials/today_table.php';
                ?>

                </div>

            </div>
        </div>

    </div>
</div>

<!-- MODAL -->
    <div class="modal fade" id="mainModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
            <div class="modal-body"></div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = "/clinic";
</script>

<script src="/clinic/visits/assets/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Core -->
<script src="/clinic/visits/js/core/api.js"></script>
<script src="/clinic/visits/js/core/actionRegistry.js"></script>

<script>
/* ✅ GUARANTEE actionRegistry exists BEFORE anything else */
if (!window.actionRegistry) {
    console.error("❌ actionRegistry failed to load");
} else {
    console.log("✅ actionRegistry ready globally");
}
</script>

<script src="/clinic/visits/js/core/eventBus.js"></script>

<!-- Modules -->
<script src="/clinic/visits/js/modules/patientModal.js"></script>
<script src="/clinic/visits/js/modules/vitals.js"></script>
<script src="/clinic/visits/js/modules/visit.js"></script>

<!-- Page -->
<script src="/clinic/visits/js/pages/dashboard.js"></script>
<script src="/clinic/visits/js/pages/visit-page.js"></script>

<script src="/clinic/visits/js/core/patientSearch.js"></script>
<script>
const mainModal = document.getElementById('mainModal');

if (mainModal) {
    mainModal.addEventListener('hidden.bs.modal', function () {
        // ✅ safest fix (no layout break)
        location.reload();
    });
}
</script>
<script>
function updateClock() {
    const now = new Date();

    document.getElementById('liveClock').textContent =
        now.toLocaleTimeString('en-IN', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });

    document.getElementById('liveDate').textContent =
        now.toLocaleDateString('en-IN', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
}

updateClock();
setInterval(updateClock, 1000);
</script>
<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

toggleBtn.addEventListener('click', () => {

    if(window.innerWidth <= 992){
        sidebar.classList.toggle('mobile-open');
    }else{
        sidebar.classList.toggle('collapsed');
    }

});
</script>
</body>
</html> 