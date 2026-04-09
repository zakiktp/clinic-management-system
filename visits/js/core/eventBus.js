document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-action');
    if (!btn) return;

    if (btn.dataset.processing === 'true') return;
    btn.dataset.processing = 'true';

    const action = btn.dataset.action;
    const id = btn.dataset.id;

    const payload = { ...btn.dataset };
    payload.id = id;

    console.log("[EventBus]", action, payload);

    try {
        if (window.actionRegistry) {
            window.actionRegistry.run(action, payload);
        } else {
            console.error("actionRegistry is not defined");
        }
    } catch (err) {
        console.error("EventBus error:", err);
    } finally {
        setTimeout(() => {
            btn.dataset.processing = 'false';
        }, 300);
    }
});