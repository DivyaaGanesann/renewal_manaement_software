var table = $('#staffTable').DataTable({
    paging: true,
    searching: true,
    ordering: true,
    order: [[0, "desc"]],
    lengthChange: false,
    pageLength: 10,
    autoWidth: false,

    dom: 'Bfrtip',
    buttons: [
        { extend: 'excelHtml5', title: 'Staff Data', className: 'btnExcel d-none' },
        { extend: 'pdfHtml5', title: 'Staff Data', orientation: 'landscape', className: 'btnPDF d-none' },
        { extend: 'csvHtml5', title: 'Staff Data', className: 'btnCSV d-none' },
        { extend: 'print', title: 'Staff Data', className: 'btnPrint d-none' }
    ],
});

// EXPORT BUTTONS
$('#downloadExcel').click(() => table.button('.btnExcel').trigger());
$('#downloadPDF').click(() => table.button('.btnPDF').trigger());
$('#downloadCSV').click(() => table.button('.btnCSV').trigger());
$('#downloadPrint').click(() => table.button('.btnPrint').trigger());
