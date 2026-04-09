window.UI = {

    showLoader(btn) {
        if (!btn) return;
        btn.disabled = true;
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = 'Loading...';
    },

    hideLoader(btn) {
        if (!btn) return;
        btn.disabled = false;
        if (btn.dataset.originalText) {
            btn.innerHTML = btn.dataset.originalText;
        }
    },

    toast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerText = message;

        Object.assign(toast.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '10px 15px',
            background: type === 'error' ? '#dc3545' : '#28a745',
            color: '#fff',
            borderRadius: '5px',
            zIndex: 9999
        });

        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    }

};