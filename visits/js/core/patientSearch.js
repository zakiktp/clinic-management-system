document.addEventListener("DOMContentLoaded", function () {

    const input = document.getElementById("patientSearch");
    const resultsBox = document.getElementById("searchResults");
    const clearBtn = document.getElementById("clearSearch");

    let timeout = null;

    // --- SEARCH INPUT ---
    input.addEventListener("keyup", function (e) {

        // ESC key → clear
        if (e.key === "Escape") {
            input.value = "";
            resultsBox.innerHTML = "";
            resultsBox.style.display = "none";   // ✅ hide
            return;
        }


        clearTimeout(timeout);

        let query = this.value.trim();

        if (query.length < 2) {
            resultsBox.innerHTML = "";
            resultsBox.style.display = "none";
            return;
        }

        timeout = setTimeout(() => {

            fetch(`/clinic/patients/search_patient.php?q=${encodeURIComponent(query)}`)
                .then(res => res.text())
                .then(html => {
                    console.log("RESPONSE:", html);
                    resultsBox.innerHTML = html;
                    resultsBox.style.display = "block";

                    // Position popup below input
                    const rect = input.getBoundingClientRect();

                    resultsBox.style.top = (rect.bottom + window.scrollY + 5) + "px";
                    resultsBox.style.left = (rect.left + window.scrollX) + "px";
                    resultsBox.style.width = "700px";

                })
                .catch(err => {
                    console.error(err);
                    resultsBox.innerHTML = `<div class="no-data">Error loading</div>`;
                });

        }, 300);
    });

    // --- CLEAR BUTTON ---
    clearBtn.addEventListener("click", function () {
        input.value = "";
        resultsBox.innerHTML = "";
        resultsBox.style.display = "none";

    });

    // --- CLICK OUTSIDE CLOSE ---
    document.addEventListener("click", function (e) {
        if (!resultsBox.contains(e.target) && e.target !== input) {
            resultsBox.innerHTML = "";
            resultsBox.style.display = "none";
        }
    });

});

// --- GLOBAL CLICK HANDLER FROM PHP TABLE ---
function selectPatient(patient_id, name, phone) {

    // Close popup
    document.getElementById("searchResults").innerHTML = "";

    if (patient_id) {
        // Existing patient → open modal / appointment
        PatientModal.open(patient_id);

    } else {
        // New patient → open add patient form
        if (typeof PatientModal !== "undefined" && PatientModal.openNew) {
            PatientModal.openNew();
        } else {
            alert("New patient form not implemented");
        }
    }
}
function openAppointment(patient_id){
    window.location.href = `/clinic/appointments/add_appointment.php?patient_id=${patient_id}`;
}

function openNewPatient(){
    window.location.href = `/clinic/patients/add_patient.php`;
}