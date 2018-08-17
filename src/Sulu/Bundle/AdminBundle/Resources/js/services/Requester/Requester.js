// @flow
import type {HandleResponseHook} from './types';

const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
};

function transformResponseBody(data: Object) {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        const value = data[key];
        transformedData[key] = value === null || value === '' ? undefined : data[key];
        return transformedData;
    }, {});
}

function transformRequestData(data: Object) {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        transformedData[key] = data[key] === undefined ? null : data[key];
        return transformedData;
    }, {});
}

function handleResponse(response: Response) {
    for (const handleResponseHook of Requester.handleResponseHooks) {
        handleResponseHook(response);
    }

    if (!response.ok) {
        return Promise.reject(response);
    }

    if (response.status === 204) {
        // Return nothing if status code says that there is no content
        return {};
    }

    return response.json().then((data) => {
        return transformResponseBody(data);
    });
}

export default class Requester {
    static handleResponseHooks: Array<HandleResponseHook> = [];

    static get(url: string): Promise<Object> {
        return fetch(url, defaultOptions)
            .then(handleResponse);
    }

    static post(url: string, data: ?Object): Promise<Object> {
        return fetch(
            url,
            {...defaultOptions, method: 'POST', body: data ? JSON.stringify(transformRequestData(data)) : undefined}
        ).then(handleResponse);
    }

    static put(url: string, data: Object): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'PUT', body: JSON.stringify(transformRequestData(data))})
            .then(handleResponse);
    }

    static delete(url: string): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'DELETE'})
            .then(handleResponse);
    }
}
