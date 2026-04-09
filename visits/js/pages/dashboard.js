console.log("✅ dashboard.js loaded");

document.addEventListener("DOMContentLoaded", () => {

    console.log("📦 DOM ready");

    const actionRegistry = window.actionRegistry;

    if (!actionRegistry) {
        console.error("❌ actionRegistry STILL missing");
        return;
    }

    console.log("✅ actionRegistry found");

    actionRegistry.register('start-visit', async (data) => {
        console.log("🚀 Start visit:", data);

        if (!data?.id) {
            return alert("Invalid appointment ID");
        }

        try {

            // ✅ STEP 1 — UPDATE STATUS → in progress
            await fetch("/clinic/visits/controller/update_status.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    appointment_id: data.id,
                    status: "in progress"
                })
            });

            // ✅ STEP 2 — CREATE / GET VISIT
            const res = await fetch(`/clinic/visits/controller/create_or_get_visit.php?appointment_id=${data.id}`);
            const result = await res.json();

            if (!result.success) {
                return alert(result.message || "Failed");
            }

            // ✅ STEP 3 — REDIRECT
            window.location.href = `/clinic/visits/add_visit.php?visit_id=${result.visit_id}`;

        } catch (err) {
            console.error(err);
            alert("Error starting visit");
        }
    });
});