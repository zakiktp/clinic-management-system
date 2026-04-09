<?php
require_once __DIR__ . '/core/init.php';
requireRole(['admin']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>OPD List</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
</head>
<style>
#opdTable { table-layout: fixed; width: 100%; }
#opdTable th, #opdTable td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
.filter-row input { width: 100%; font-size: 12px; padding: 3px; }
</style>

<body class="container mt-5">

<h3 class="mb-4 text-primary fw-bold">OPD Records</h3>

<div class="row mb-3 g-2">
    <div class="col-md-2"><input type="text" id="searchBox" class="form-control" placeholder="Search"></div>
    <div class="col-md-2">
        <select id="pageLength" class="form-select">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="-1">All</option>
        </select>
    </div>
    <div class="col-md-3 d-flex gap-1">
        <input type="date" id="startDate" class="form-control">
        <input type="date" id="endDate" class="form-control">
    </div>
    <div class="col-md-5 text-end">
        <a href="index.php" class="btn btn-outline-primary btn-sm" role="button" aria-pressed="true">Dashboard</a>
        <button id="todayBtn" class="btn btn-info btn-sm">Today</button>
        <button id="filterBtn" class="btn btn-primary btn-sm">Filter</button>
        <button id="clearFilter" class="btn btn-secondary btn-sm">Clear</button>
        <button id="excelBtn" class="btn btn-success btn-sm">Excel</button>
        <button id="pdfBtn" class="btn btn-danger btn-sm">PDF</button>
    </div>
</div>

<table id="opdTable" class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th style="width:6%">ID</th>
            <th style="width:18%">Name</th>
            <th style="width:6%">Age</th>
            <th style="width:6%">Sex</th>
            <th style="width:28%">Address</th>
            <th style="width:10%; text-align:right;">Fee</th>
            <th style="width:16%">Date</th>
        </tr>
        <tr class="filter-row">
            <th class="text-center"><input class="form-control form-control-sm text-center" placeholder="ID"></th>
            <th class="text-start"><input class="form-control form-control-sm" placeholder="Name"></th>
            <th class="text-center"><input class="form-control form-control-sm text-center" placeholder="Age"></th>
            <th class="text-center"><input class="form-control form-control-sm text-center" placeholder="Sex"></th>
            <th class="text-start"><input class="form-control form-control-sm" placeholder="Address"></th>
            <th class="text-end"><input class="form-control form-control-sm text-end" placeholder="Fee"></th>
            <th class="text-center"><input class="form-control form-control-sm text-center" placeholder="Date"></th>
        </tr>
    </thead>

    <tfoot>
        <tr class="table-info fw-bold">
            <td colspan="7">
                Records: <span id="totalRows"></span> |
                Page Total: ₹ <span id="pageTotal"></span> |
                Grand Total: ₹ <span id="grandTotal"></span>
            </td>
        </tr>
    </tfoot>
</table>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>

<script>
$(function(){

let table = $('#opdTable').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    pageLength: 25,
    lengthMenu: [[10,25,50,100,-1],[10,25,50,100,"All"]],
    ajax: {
        url: 'opd_ajax.php',
        type: 'POST',
        data: function(d){
            d.startDate = $('#startDate').val();
            d.endDate = $('#endDate').val();
        }
    },
    dom: 'Bfrtip', // Include Buttons
    buttons: [
        {
            extend: 'excelHtml5',
            text: 'Export Excel',
            className: 'd-none', // hide default button
            title: 'OPD Records',
            exportOptions: { columns: ':visible' }
        },
        {
            extend: 'pdfHtml5',
            text: 'Export PDF',
            className: 'd-none', // hide default button
            title: 'OPD Records',
            exportOptions: { columns: ':visible' },
            orientation: 'landscape',
            pageSize: 'A4'
        }
    ],
    columnDefs: [
        { targets: 0, className: 'text-center' },
        { targets: 1, className: 'text-start' },
        { targets: 2, className: 'text-center' },
        { targets: 3, className: 'text-center' },
        { targets: 4, className: 'text-start' },
        { targets: 5, className: 'text-end' },
        { targets: 6, className: 'text-center' }
    ],
    drawCallback: function(settings){
        let json = settings.json || {};
        $('#totalRows').html(json.recordsFiltered || 0);
        $('#grandTotal').html(parseFloat(json.totalAmount || 0).toFixed(2));
        $('#pageTotal').html(parseFloat(json.pageTotal || 0).toFixed(2));
    }
});

// Page length change
$('#pageLength').change(function(){
    table.page.len(parseInt(this.value)).draw();
});

// Global search with debounce
let searchTimer;
$('#searchBox').keyup(function(){
    clearTimeout(searchTimer);
    let val = this.value;
    searchTimer = setTimeout(()=>{ table.search(val).draw(); },300);
});

// Date filter
$('#filterBtn').click(()=>{ table.ajax.reload(); });

// Clear filter
$('#clearFilter').click(()=>{
    $('#searchBox').val('');
    $('#startDate').val('');
    $('#endDate').val('');
    $('#opdTable thead input').val('');
    table.search('').columns().search('').draw();
});

// Column filters with debounce
let colTimer;
$('#opdTable thead tr.filter-row th').each(function(i){
    $('input', this).on('keyup change', function(){
        clearTimeout(colTimer);
        let val = this.value;
        colTimer = setTimeout(()=>{ table.column(i).search(val).draw(); },300);
    });
});

$('#todayBtn').click(() => {
    const today = new Date().toISOString().split('T')[0];  // yyyy-mm-dd
    $('#startDate').val(today);
    $('#endDate').val(today);
    $('#filterBtn').click();  // trigger filtering
});

// Trigger export buttons
$('#excelBtn').click(()=>{ table.button(0).trigger(); });
$('#pdfBtn').click(()=>{ table.button(1).trigger(); });

});
</script>

</body>
</html>