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
    return response.json();
}

export default class Requester {
    static get(url: string): Promise<Object> {
        return fetch(url, defaultOptions)
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
