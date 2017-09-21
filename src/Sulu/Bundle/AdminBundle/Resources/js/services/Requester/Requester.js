// @flow
const defaultOptions = {credentials: 'same-origin'};

function handleResponse(response) {
    return response.json();
}

export default class Requester {
    static get(url: string): Promise<Object> {
        return fetch(url, defaultOptions)
            .then(handleResponse);
    }

    static delete(url: string): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'DELETE'})
            .then(handleResponse);
    }
}
