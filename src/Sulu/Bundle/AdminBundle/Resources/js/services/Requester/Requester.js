// @flow
const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
    },
};

function handleResponse(response) {
    if (!response.ok) {
        throw new Error(response.statusText);
    }

    if (response.status === 204) {
        // Return nothing if status code says that there is no content
        return {};
    }

    return response.json();
}

export default class Requester {
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
