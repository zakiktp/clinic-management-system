console.log("🔥 actionRegistry FILE EXECUTED");

window.actionRegistry = {
    handlers: {},

    register(action, handler) {
        console.log("📌 Register:", action);
        this.handlers[action] = handler;
    },

    run(action, payload) {
        console.log("▶ Run:", action, payload);

        if (this.handlers[action]) {
            this.handlers[action](payload);
        } else {
            console.warn("❌ No handler for action:", action);
        }
    }
};

console.log("✅ actionRegistry initialized");