console.log("VitalsModule loaded");

const VitalsModule = {

    init() {
        console.log("VitalsModule init");
    },

    // ================= OPEN MODAL =================
    async openVitalsModal(appointment_id) {

        try {

            // 🔥 STORE globally (MOST IMPORTANT FIX)
            this.currentAppointmentId = appointment_id;

            console.log("Opening vitals for:", appointment_id);

            // ✅ fetch vitals
            const res = await fetch(`/clinic/visits/controller/get_vitals.php?appointment_id=${appointment_id}`);
            const data = await res.json();

            // ✅ detect visit_id safely
            const visit_id =
                window.APP_DATA?.visit?.visit_id ||
                document.querySelector('[name="visit_id"]')?.value ||
                0;

            // ✅ load modal HTML
            const html = await fetch(`/clinic/visits/partials/vitals.php?appointment_id=${appointment_id}&visit_id=${visit_id}`)
                                .then(r => r.text());

            showModal(html);

            // ✅ wait for DOM render
            setTimeout(() => {

                this.initVitalsForm();

                if (data.success) {
                    this.bindVitals(data.vitals);
                }

            }, 100);

        } catch (err) {
            console.error(err);
            alert("Error loading vitals");
        }
    },

    // ================= INIT FORM =================
    initVitalsForm() {

        const form = document.getElementById("vitalsForm");
        if (!form) {
            console.warn("Vitals form not found in modal");
            return;
        }

        // 🔥 FORCE hidden appointment_id (CRITICAL FIX)
        const hiddenAppointment = form.querySelector('[name="appointment_id"]');
        if (hiddenAppointment && this.currentAppointmentId) {
            hiddenAppointment.value = this.currentAppointmentId;
        }

        // ================= BMI =================
        const height = document.getElementById("height");
        const weight = document.getElementById("weight");
        const bmi = document.getElementById("bmi");

        if (height && weight && bmi) {
            const calc = () => {
                const h = parseFloat(height.value);
                const w = parseFloat(weight.value);
                bmi.value = (h > 0 && w > 0)
                    ? (w / ((h/100)*(h/100))).toFixed(1)
                    : '';
            };
            height.addEventListener("input", calc);
            weight.addEventListener("input", calc);
        }

        
    },

    // ================= BIND =================
    bindVitals(v) {

        if (!v) return;

        console.log("Binding vitals:", v);

        document.querySelectorAll("#vitalsForm input").forEach(input => {

            const name = input.name;

            if (name && v[name] !== undefined) {
                input.value = v[name] ?? '';
            }

        });
    }
};

// GLOBAL
window.openVitalsModal = (id) => VitalsModule.openVitalsModal(id);

document.addEventListener("DOMContentLoaded", () => VitalsModule.init());