window.API = {

    post(url, data = {}) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data)
        }).then(res => res.json());
    },

    get(url) {
        return fetch(url).then(res => res.text());
    }

};