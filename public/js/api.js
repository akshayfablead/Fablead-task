(function (window) {
    'use strict';

    async function request(url, options = {}) {
        const headers = {
            Accept: 'application/json',
            ...(options.headers || {}),
        };

        if (
            options.body !== undefined
            && !(options.body instanceof FormData)
        ) {
            headers['Content-Type'] = 'application/json';
        }

        const response = await fetch(url, {
            ...options,
            headers,
        });

        const contentType = response.headers.get('content-type') || '';
        const body = contentType.includes('application/json')
            ? await response.json()
            : {};

        if (!response.ok) {
            const error = new Error(body.message || 'Request failed.');
            error.status = response.status;
            error.body = body;
            throw error;
        }

        return body;
    }

    window.crmApi = {
        get(url) {
            return request(url);
        },

        post(url, data) {
            return request(url, {
                method: 'POST',
                body: JSON.stringify(data),
            });
        },

        put(url, data) {
            return request(url, {
                method: 'PUT',
                body: JSON.stringify(data),
            });
        },

        delete(url) {
            return request(url, {
                method: 'DELETE',
            });
        },
    };
})(window);
