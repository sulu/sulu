// @flow
const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
};

function handleResponse(response: Object) {
    for (const handleResponseHook of Requester.handleResponseHooks) {
        handleResponseHook(response);
    }

    if (!response.ok) {
        return Promise.reject(response);
    }

    return response.json();
}

export default class Requester {
    static handleResponseHooks: Array<(response: Object) => void> = [];

    static get(url: string): Promise<Object> {
        return fetch(url, defaultOptions)
            .then(handleResponse);
    }

    static post(url: string, data: Object): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'POST', body: JSON.stringify(data)})
            .then(handleResponse);
    }

    static put(url: string, data: Object): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'PUT', body: JSON.stringify(data)})
            .then(handleResponse);
    }

    static delete(url: string): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'DELETE'})
            .then(handleResponse);
    }
}
