var ALERTS = window.ALERTS || {
    bp_sys: { high: 140, low: 90 },
    bp_dia: { high: 90, low: 60 },
    sugar: { high: 200, low: 70 },
    pulse: { high: 100, low: 60 },
    spo2: { low: 95 },
    temp: { normal_min: 97.4, normal_max: 98.4 }
};

// ================= SAFE INIT =================
if (window.VISIT_APP_LOADED) {
    console.warn("visit.js already loaded");
} else {

window.VISIT_APP_LOADED = true;

console.log("Visit.js Loaded");

// ================= MAIN APP =================
const VisitApp = {

    init() {
        console.log("VisitApp init");

        if (window.APP_DATA) {
            this.afterLoad();
        } else {
            this.loadVisitData();
        }

        this.bindEvents();
        this.initBMI();
        this.initDefaultRow();
        this.initDoctorNotesInputs();
        this.initVitalsAlerts();
    },

    afterLoad() {
        this.fillPatient();
        this.fillVisit();
        this.loadDoctorNotesData();
        this.loadVitals();
    },

 // ================= DOCTOR NOTES MULTI-VALUE =================
initDoctorNotesInputs() {
    const fields = [
        {
            input: '#complaints_input',
            hidden: '#complaints',
            tagVar: 'complaintsTag',
            whitelist: window.DOCTOR_NOTES_WHITELISTS?.complaints || []
        },
        {
            input: '#diagnosis_input',
            hidden: '#diagnosis',
            tagVar: 'diagnosisTag',
            whitelist: window.DOCTOR_NOTES_WHITELISTS?.diagnosis || []
        },
        {
            input: '#investigations_input',
            hidden: '#investigations',
            tagVar: 'investigationsTag',
            whitelist: window.DOCTOR_NOTES_WHITELISTS?.investigations || []
        }
    ];

    fields.forEach(f => {
        const input = document.querySelector(f.input);
        const hidden = document.querySelector(f.hidden);

        if (!input || !hidden) return;

        // Prevent duplicate Tagify init
        if (input._tagify) {
            this[f.tagVar] = input._tagify;
            return;
        }

        const tagify = new Tagify(input, {
            whitelist: f.whitelist,
            dropdown: {
                enabled: 1,
                maxItems: 20,
                classname: 'tags-look',
                fuzzySearch: true
            },
            enforceWhitelist: false,
            duplicates: false
        });

        tagify.on('change', () => {
            hidden.value = JSON.stringify(
                tagify.value
                    .map(v => ({
                        value: v.value.toUpperCase().trim()
                    }))
                    .filter(v => v.value !== '')
            );
        });

        this[f.tagVar] = tagify;
    });
},

loadDoctorNotesData() {
    const visit = window.APP_DATA?.visit || {};

    const dataMap = [
        { tag: this.complaintsTag, values: visit.complaints },
        { tag: this.diagnosisTag, values: visit.diagnosis },
        { tag: this.investigationsTag, values: visit.investigations }
    ];

    dataMap.forEach(d => {
        if (!d.tag || !d.values) return;

        d.tag.removeAllTags();

        d.values
            .split(',')
            .map(v => v.trim())
            .filter(v => v !== '')
            .forEach(v => d.tag.addTags(v));
    });

    this.syncDoctorNotesBeforeSave();
},

syncDoctorNotesBeforeSave() {
    const map = [
        { tag: this.complaintsTag, hidden: '#complaints' },
        { tag: this.diagnosisTag, hidden: '#diagnosis' },
        { tag: this.investigationsTag, hidden: '#investigations' }
    ];

    map.forEach(f => {
        const hidden = document.querySelector(f.hidden);

        if (!f.tag || !hidden) return;

        hidden.value = JSON.stringify(
            f.tag.value
                .map(v => ({
                    value: v.value.toUpperCase().trim()
                }))
                .filter(v => v.value !== '')
        );
    });
},
    // ================= BIND HIDDEN INPUTS =================
    bindDropdowns() {
        const complaintsInput = document.getElementById('complaints_input');
        const diagnosisInput = document.getElementById('diagnosis_input');
        const investigationsInput = document.getElementById('investigations_input');

        if (complaintsInput) {
            complaintsInput.addEventListener('change', e => {
                document.getElementById('complaints').value = e.target.value.toUpperCase();
            });
        }

        if (diagnosisInput) {
            diagnosisInput.addEventListener('change', e => {
                document.getElementById('diagnosis').value = e.target.value.toUpperCase();
            });
        }

        if (investigationsInput) {
            investigationsInput.addEventListener('change', e => {
                document.getElementById('investigations').value = e.target.value.toUpperCase();
            });
        }
    },


    // ================= LOAD VISIT =================
    async loadVisitData() {

        const params = new URLSearchParams(window.location.search);

        const visitId = params.get("visit_id");
        const appointmentId = params.get("appointment_id");

        if (!visitId && !appointmentId) {
            return; // silent (dashboard page)
        }

        try {

            let url = `/clinic/visits/controller/load_visit.php?`;

            if (visitId) {
                url += `visit_id=${visitId}`;
            } else {
                url += `appointment_id=${appointmentId}`;
            }

            const res = await fetch(url);
            const data = await res.json();

            if (data.status === "success") {

                window.APP_DATA = data.data;
                console.log("APP_DATA set:", window.APP_DATA);

                this.afterLoad();

            } else {
                console.error("Load failed:", data.message);
            }

        } catch (err) {
            console.error("Error loading visit:", err);
        }
    },

    // ================= LOAD VITALS =================
    async loadVitals() {

        const appointmentId = window.APP_DATA?.appointment_id;
        const visitId = window.APP_DATA?.visit?.visit_id || 0;

        if (!appointmentId) return;

        try {
            const res = await fetch(
                `/clinic/visits/controller/get_vitals.php?appointment_id=${appointmentId}&visit_id=${visitId}`
            );

            const data = await res.json();

            if (data.success && data.vitals) {

                console.log("Vitals loaded:", data.vitals);

                window.APP_DATA.vitals = data.vitals;

                // 🔥🔥🔥 DELAY FIX (VERY IMPORTANT)
                this.waitForVitalsDOM(() => {
                    this.bindVitals();
                });
            }

        } catch (err) {
            console.error("Error loading vitals:", err);
        }
    },

    // ================= WAIT FOR VITALS =================
    waitForVitalsDOM(callback) {

        let tries = 0;

        const interval = setInterval(() => {

            const el = document.getElementById('bp_sys');

            if (el) {
                clearInterval(interval);
                callback();
            }

            tries++;

            if (tries > 20) { // ~2 seconds max
                clearInterval(interval);
                console.warn("Vitals DOM not found");
            }

        }, 100);
    },

    // ================= VITALS =================
    async saveVitals() {

        const form = document.getElementById("vitalsForm");

        if (!form) {
            alert("Vitals form not found");
            return;
        }

        const formData = new FormData(form);

        // 🔥 ALWAYS GET FROM HIDDEN INPUT FIRST (MOST RELIABLE)
        let appointmentId = form.querySelector('[name="appointment_id"]')?.value;

        // fallback
        if (!appointmentId || appointmentId == 0) {
            appointmentId = window.APP_DATA?.appointment_id || 0;
        }

        let visitId = form.querySelector('[name="visit_id"]')?.value;

        if (!visitId || visitId == 0) {
            visitId = window.APP_DATA?.visit?.visit_id || 0;
        }

        console.log("🔍 FINAL IDS:", { appointmentId, visitId });

        // ❌ HARD STOP
        if (!appointmentId || appointmentId == 0) {
            alert("❌ Appointment ID missing (JS)");
            console.error("appointment_id missing in JS");
            return;
        }

        // 🔥 FORCE INTO FORM DATA
        formData.set("appointment_id", appointmentId);
        formData.set("visit_id", visitId);

        console.log("📦 FINAL DATA:", Object.fromEntries(formData));

        try {

            const res = await fetch("/clinic/visits/controller/save_vitals.php", {
                method: "POST",
                body: formData
            });

            const data = await res.json();
            console.log("SERVER RESPONSE:", data);

            if (data.success) {

                console.log("✅ Vitals saved");
                alert("Vitals saved successfully ✅");

                await this.loadVitals();

            } else {
                alert(data.message || "Vitals save failed");
            }

        } catch (err) {
            console.error("Save vitals error:", err);
            alert("Server error while saving vitals");
        }
    },

    
    // ================= BIND VITALS =================
    bindVitals() {

        const v = window.APP_DATA?.vitals;

        if (!v) {
            console.warn("No vitals found");
            return;
        }

        console.log("Binding vitals:", v);

        const fields = [
            'bp_sys','bp_dia','pulse','bsugar',
            'height','weight','bmi','temp','spo2'
        ];

        fields.forEach(id => {

            const el = document.getElementById(id);

            if (!el) return;

            // 🔥 CLEAN VALUE (handle null, undefined, 0)
            let value = v[id];

            if (value === null || value === undefined || value == 0) {
                value = '';
            }

            el.value = String(value);

        });

        // ================= FIXES =================

        // ✅ 1. Always recalculate BMI (do NOT trust DB)
        if (typeof this.calculateBMI === "function") {
            this.calculateBMI();
        }

        // ✅ 2. Apply alerts (if enabled)
        if (typeof this.checkVitalsAlert === "function") {
            this.checkVitalsAlert();
        }

    },

    // ================= EVENTS =================
    bindEvents() {

    document.addEventListener("click", (e) => {

        // ✅ SAVE VITALS
        if (e.target && e.target.id === "saveVitalsBtn") {
            console.log("🟢 Save Vitals Clicked");
            this.saveVitals();
        }

        if (e.target.id === "addMedicineBtn") {
            this.addRow();
        }

        if (e.target.id === "saveVisitBtn") {
            this.saveVisit();
        }

        // ================= REPEAT VISIT =================
        if (e.target.classList.contains("repeatVisitBtn")) {
            const visit_id = e.target.dataset.visitId;

            fetch(`/clinic/visits/controller/repeat_visit.php?visit_id=${visit_id}`)
            .then(res => res.json())
            .then(data => {
                console.log("REPEAT DATA:", data);

                const tbody = document.querySelector("#medicineBody");
                const template = document.querySelector("#medicineTemplate");

                if (!tbody || !template) {
                    alert("Prescription table not ready");
                    return;
                }

                Array.from(tbody.children).forEach(row => {
                    if (row.id !== "medicineTemplate") {
                        row.remove();
                    }
                });

                if (data.medicines && data.medicines.length > 0) {
                    data.medicines.forEach(med => this.addRow(med));
                } else {
                    this.initDefaultRow();
                }
            })
            .catch(err => {
                console.error(err);
                alert("Error loading prescription");
            });
        }

        // ================= ✅ FIXED: REPEAT LAST =================
        if (e.target.id === "repeatLastBtn") {

            const visit_id = window.APP_DATA?.visit?.visit_id;

            if (!visit_id) {
                alert("Visit ID missing");
                return;
            }

            fetch(`/clinic/visits/controller/repeat_visit.php?visit_id=${visit_id}`)
            .then(res => res.json())
            .then(data => {

                console.log("REPEAT LAST DATA:", data);

                const tbody = document.querySelector("#medicineBody");
                const template = document.querySelector("#medicineTemplate");

                if (!tbody || !template) {
                    alert("Prescription table not ready");
                    return;
                }

                Array.from(tbody.children).forEach(row => {
                    if (row.id !== "medicineTemplate") {
                        row.remove();
                    }
                });

                if (data.medicines && data.medicines.length > 0) {
                    data.medicines.forEach(med => this.addRow(med));
                } else {
                    this.initDefaultRow();
                }

            })
            .catch(err => {
                console.error(err);
                alert("Error loading last prescription");
            });
        }

    }); // ✅ END OF CLICK LISTENER
},

    // ================= BMI CALCULATION =================
    calculateBMI() {

    const heightEl = document.getElementById("height");
    const weightEl = document.getElementById("weight");
    const bmiEl = document.getElementById("bmi");

    if (!heightEl || !weightEl || !bmiEl) return;

    const h = parseFloat(heightEl.value);
    const w = parseFloat(weightEl.value);

    if (h > 0 && w > 0) {
        const bmi = w / ((h / 100) * (h / 100));
        bmiEl.value = bmi.toFixed(1);
    } else {
        bmiEl.value = '';
    }
},

    // ================= BMI =================
    initBMI() {

        const height = document.getElementById("height");
        const weight = document.getElementById("weight");
        const bmi = document.getElementById("bmi");

        if (!height || !weight || !bmi) return;

        const calc = () => {
            const h = parseFloat(height.value);
            const w = parseFloat(weight.value);

            bmi.value = (h > 0 && w > 0)
                ? (w / ((h/100)*(h/100))).toFixed(1)
                : '';
        };

        height.addEventListener("input", calc);
        weight.addEventListener("input", calc);
    },

    // ================= VITAL LIVE ALERT =================
    initVitalsAlerts() {

        const fields = [
            "bp_sys","bp_dia","pulse","bsugar","spo2","temp"
        ];

        fields.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;

            el.addEventListener("input", () => {
                this.checkVitalsAlert();
            });
        });
    },

    // ================= MEDICINE ROW =================
    initDefaultRow() {

        const tbody = document.getElementById("medicineBody");
        const template = document.getElementById("medicineTemplate");

        if (!tbody || !template) return;

        // Check visible rows
        const rows = tbody.querySelectorAll("tr.medRow:not(#medicineTemplate)");

        if (rows.length === 0) {
            // 👉 Create a REAL row from template (not show template)
            this.addRow({});
        }

        this.updateRowNumbers();
    },

    addRow(data = {}) {

        const tbody = document.querySelector("#medicineBody");
        const template = document.querySelector("#medicineTemplate");

        if (!tbody || !template) {
            console.error("Template or tbody missing");
            return;
        }

        const clone = template.cloneNode(true);

        // 🔥 IMPORTANT: FULL RESET
        clone.id = "";
        clone.className = "medRow";   // instead of remove/add

        const inputs = clone.querySelectorAll("input");

        if (inputs.length >= 5) {
            inputs[0].value = data.type || '';
            inputs[1].value = data.medicine || '';
            inputs[2].value = data.dosage || '';
            inputs[3].value = data.duration || '';
            inputs[4].value = data.advice || '';
        }

        tbody.appendChild(clone);

        this.bindRemoveButton(clone);
        this.updateRowNumbers();
    },

    bindRemoveButton(row) {
        const tbody = document.getElementById("medicineBody");
        const btn = row.querySelector(".removeRowBtn");
        if (!btn) return;

        btn.onclick = () => {
            const visibleRows = tbody.querySelectorAll("tr.medRow:not(.d-none)");
            if (visibleRows.length > 1) {
                row.remove();
                this.updateRowNumbers();
            } else {
                alert("At least one prescription row must remain.");
            }
        };
    },

    updateRowNumbers() {
        document.querySelectorAll("#medicineBody .medRow:not(.d-none)").forEach((row, i) => {
            const cell = row.querySelector(".row-index");
            if (cell) cell.innerText = i + 1;
        });
    },

    fillPatient() {
        const p = window.APP_DATA?.patient;
        if (!p) return;

        const name = `${p.prefix || ''} ${p.name || ''} ${p.title || ''} ${p.spouse || ''}`.trim();

        const patientName = document.getElementById('patient_name');
        if (patientName) patientName.value = name;

        const phone = document.getElementById('phone');
        if (phone) phone.value = p.phone || '';

        const address = document.getElementById('address');
        if (address) address.value = p.address || '';

        const gender = document.getElementById('gender');
        if (gender) gender.value = p.gender || '';
    },

    fillVisit() {
        const v = window.APP_DATA?.visit;
        if (!v) return;

        const appointmentId = window.APP_DATA?.appointment_id || '';
        const patientId = window.APP_DATA?.patient_id || '';
        const vitalsAppointment = document.querySelector('#vitalsForm input[name="appointment_id"]');
        if (vitalsAppointment) {
            vitalsAppointment.value = window.APP_DATA?.appointment_id || '';
        }

        const vitalsVisit = document.querySelector('#vitalsForm input[name="visit_id"]');
        if (vitalsVisit) {
            vitalsVisit.value = v.visit_id || '';
        }

        // ===== MAIN VISIT FORM =====
        document.getElementById('visit_id').value = v.visit_id || '';
        document.getElementById('appointment_id').value = appointmentId;
        document.getElementById('patient_id').value = patientId;

        const amount = document.getElementById('amount');
        if (amount) amount.value = v.amount || '';

        const note1 = document.getElementById('note1');
        if (note1) note1.value = v.note1 || '';

        const note2 = document.getElementById('note2');
        if (note2) note2.value = v.note2 || '';

        // ===== 🔥 VITALS FORM (THIS WAS MISSING) =====
        const vitalsForm = document.getElementById('vitalsForm');

        if (vitalsForm) {
            const visitInput = vitalsForm.querySelector('[name="visit_id"]');
            const appointmentInput = vitalsForm.querySelector('[name="appointment_id"]');

            if (visitInput) visitInput.value = v.visit_id || '';
            if (appointmentInput) appointmentInput.value = appointmentId;

            console.log("✅ Vitals form updated:", {
                visit_id: v.visit_id,
                appointment_id: appointmentId
            });
        }
    },

    // ================= VITAL ALERT =================
    checkVitalsAlert() {

        const map = {
            bp_sys: "bp_sys",
            bp_dia: "bp_dia",
            pulse: "pulse",
            bsugar: "sugar",
            spo2: "spo2",
            temp: "temp"
        };

        Object.keys(map).forEach(field => {

            const el = document.getElementById(field);
            if (!el) return;

            const val = parseFloat(el.value);

            // reset styles
            el.classList.remove("border-danger", "bg-danger-subtle", "border-warning", "bg-warning-subtle");
            el.removeAttribute("title");

            if (!val) return;

            const rule = ALERTS[map[field]];
            if (!rule) return;

            let message = "";

            // 🔴 HIGH
            if (rule.high && val > rule.high) {
                el.classList.add("border-danger", "bg-danger-subtle");
                message = "High";
            }

            // 🔴 LOW
            if (rule.low && val < rule.low) {
                el.classList.add("border-danger", "bg-danger-subtle");
                message = "Low";
            }

            // 🌡 TEMP SPECIAL
            if (field === "temp") {
                if (val < rule.normal_min) {
                    el.classList.add("border-warning", "bg-warning-subtle");
                    message = "Low Temp";
                }
                if (val > rule.normal_max) {
                    el.classList.add("border-danger", "bg-danger-subtle");
                    message = "Fever";
                }
            }

            // 🫁 SPO2 LOW ONLY
            if (field === "spo2" && rule.low && val < rule.low) {
                el.classList.add("border-danger", "bg-danger-subtle");
                message = "Low Oxygen";
            }

            if (message) {
                el.title = message;
            }

        });
    },

 
// ================= SAVE VISIT =================
async saveVisit() {
    const form = document.getElementById("visitForm");
    if (!form) return alert("Form not found");

    console.log("🚀 Save clicked");

    // 🔹 Ensure Tagify fields are synced
    this.syncDoctorNotesBeforeSave();

    // ✅ MUST be immediate
    const printWindow = window.open("about:blank", "_blank");

    try {
        const res = await fetch("/clinic/visits/controller/save_visit.php", {
            method: "POST",
            body: new FormData(form)
        });

        const data = await res.json();
        console.log("✅ RESPONSE:", data);

        if (data.status === "success") {

            // update visit id
            const visitInput = document.getElementById("visit_id");
            if (visitInput) visitInput.value = data.visit_id;

            // ✅ FORCE navigation immediately
            printWindow.location.replace(
                `/clinic/prescriptions/print_prescription.php?visit_id=${data.visit_id}`
            );

        } else {
            printWindow.close();
            alert("Error: " + (data.message || "Unknown"));
        }

    } catch (err) {
        console.error(err);
        printWindow.close();
        alert("Server error");
    }
}
};

// ================= INIT on DOMContentLoaded =================
document.addEventListener("DOMContentLoaded", () => {
    VisitApp.init();
});
}

