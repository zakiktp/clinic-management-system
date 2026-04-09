console.log("PatientModule loaded");

const PatientModule = {

    init() {
        console.log("PatientModule init");
        window.actionRegistry.register('edit-patient', this.editPatient.bind(this));
    },

    async editPatient({ id }) {

        const html = await API.get(`${BASE_URL}/patients/edit_patient.php?id=${id}`);
        showModal(html);

        // 🔥 Wait until form exists (no modal dependency)
        const waitForForm = () => {
            return new Promise(resolve => {
                const interval = setInterval(() => {

                    const form = document.querySelector('#editPatientForm');

                    if (form) {
                        clearInterval(interval);
                        resolve(form);
                    }

                }, 50);
            });
        };

        const form = await waitForForm();

        console.log("Form detected");

        this.attachLogic(form);
    },

    attachLogic(form) {

        const prefix = form.querySelector('#prefix');
        const gender = form.querySelector('#gender');
        const age    = form.querySelector('#age');
        const dob    = form.querySelector('#dob');

        if (!prefix || !gender || !age || !dob) {
            console.error("Form elements missing");
            return;
        }

        // ----------------------------
        // ✅ FORCE UPPERCASE (ALL TEXT INPUTS)
        // ----------------------------
        form.querySelectorAll('input[type="text"], input[type="tel"]').forEach(input => {
            input.addEventListener('input', () => {
                input.value = input.value.toUpperCase();
            });
        });

        // ----------------------------
        // ✅ PREFIX → GENDER
        // ----------------------------
        const setGender = () => {
            const val = prefix.value.toUpperCase();

            if (val === 'MR' || val === 'MASTER') gender.value = 'MALE';
            else if (val === 'MRS' || val === 'MS' || val === 'BABY') gender.value = 'FEMALE';
            else gender.value = '';
        };

        // ----------------------------
        // ✅ DOB → AGE (Already Working)
        // ----------------------------
        const calculateAge = (dobVal) => {
            if (!dobVal) return '';

            const birth = new Date(dobVal);
            const today = new Date();

            let y = today.getFullYear() - birth.getFullYear();
            let m = today.getMonth() - birth.getMonth();
            let d = today.getDate() - birth.getDate();

            if (d < 0) {
                m--;
                d += new Date(today.getFullYear(), today.getMonth(), 0).getDate();
            }
            if (m < 0) {
                y--;
                m += 12;
            }

            return `${y}Y ${m}M ${d}D`;
        };

        // ----------------------------
        // ✅ AGE → DOB (FIXED)
        // ----------------------------
        const ageToDOB = (ageStr) => {

            if (!ageStr) return '';

            // support flexible input: 25 / 25Y / 25Y 2M / 25Y 2M 10D
            const y = parseInt(ageStr.match(/(\d+)\s*Y?/i)?.[1] || 0);
            const m = parseInt(ageStr.match(/(\d+)\s*M/i)?.[1] || 0);
            const d = parseInt(ageStr.match(/(\d+)\s*D/i)?.[1] || 0);

            if (!y && !m && !d) return '';

            const today = new Date();

            today.setFullYear(today.getFullYear() - y);
            today.setMonth(today.getMonth() - m);
            today.setDate(today.getDate() - d);

            return today.toISOString().split('T')[0];
        };

        // ----------------------------
        // ✅ INIT
        // ----------------------------
        setGender();

        if (dob.value) {
            age.value = calculateAge(dob.value);
        }

        // ----------------------------
        // EVENTS
        // ----------------------------
        prefix.addEventListener('change', setGender);

        dob.addEventListener('change', () => {
            age.value = calculateAge(dob.value);
        });

        // 🔥 FIX: use 'input' instead of blur (REALTIME)
        age.addEventListener('input', () => {
            const dobVal = ageToDOB(age.value);
            if (dobVal) dob.value = dobVal;
        });
    }
};

// expose globally
window.PatientModule = PatientModule;

function closePatientModal() {

    // Try Bootstrap modal first
    const modalEl = document.querySelector('.modal');

    if (modalEl) {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
            return;
        }
    }

    // Fallback (your custom modal)
    const form = document.querySelector('#editPatientForm');
    if (form) {
        const container = form.closest('div');
        if (container) container.remove();
    }
}