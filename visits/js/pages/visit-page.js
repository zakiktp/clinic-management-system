document.addEventListener('DOMContentLoaded', function () {
    
    console.log("Initializing modules...");
    
    // Initialize modules
    if (window.PatientModule) PatientModule.init();
    if (window.VitalsModule) VitalsModule.init();
    if (window.VisitModule) VisitModule.init();

});


// -----------------------------
// Shared Modal Utility
// -----------------------------
function showModal(html) {
    const modalBody = document.querySelector('#mainModal .modal-body');

    if (!modalBody) {
        console.error("Modal body not found");
        return;
    }

    modalBody.innerHTML = html;

    const modalEl = document.getElementById('mainModal');
    if (!modalEl) {
        console.error("Modal element not found");
        return;
    }

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}


// -----------------------------
// Optional: expose globally
// -----------------------------
window.showModal = showModal;

