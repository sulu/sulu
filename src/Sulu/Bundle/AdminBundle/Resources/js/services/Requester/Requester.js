// @flow
import type {HandleResponseHook} from './types';

const defaultOptions = {
    credentials: 'same-origin',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
};

function transformResponseObject(data: Object) {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        const value = data[key];
        transformedData[key] = value === null ? undefined : data[key];
        return transformedData;
    }, {});
}

function transformResponseArray(data: Array<Object>) {
    return data.map(transformResponseObject);
}

function transformRequestObject(data: Object): Object {
    return Object.keys(data).reduce((transformedData: Object, key) => {
        transformedData[key] = data[key] === undefined ? null : data[key];
        return transformedData;
    }, {});
}

function transformRequestData(data: Object | Array<Object>): Object | Array<Object> {
    if (Array.isArray(data)) {
        return data.map(transformRequestObject);
    }

    return transformRequestObject(data);
}

function handleResponse(response: Response): Promise<Object | Array<Object>> {
    for (const handleResponseHook of Requester.handleResponseHooks) {
        handleResponseHook(response);
    }

    if (!response.ok) {
        return Promise.reject(response);
    }

    if (response.status === 204) {
        // Return empty object if status code says that there is no content
        return Promise.resolve({});
    }

    return response.json().then((data) => {
        if (Array.isArray(data)) {
            return transformResponseArray(data);
        }

        return transformResponseObject(data);
    });
}

function handleObjectResponse(response: Response): Promise<Object> {
    return handleResponse(response).then((response) => {
        if (Array.isArray(response)) {
            throw Error('Response was expected to be an object, but an array was given');
        }

        return response;
    });
}

export default class Requester {
    static handleResponseHooks: Array<HandleResponseHook> = [];

    static get(url: string): Promise<Object> {
        return fetch(url, defaultOptions)
            .then(handleObjectResponse);
    }

    static post(url: string, data: ?Object): Promise<Object> {
        return fetch(
            url,
            {...defaultOptions, method: 'POST', body: data ? JSON.stringify(transformRequestData(data)) : undefined}
        ).then(handleObjectResponse);
    }

    static put(url: string, data: Object): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'PUT', body: JSON.stringify(transformRequestData(data))})
            .then(handleObjectResponse);
    }

    static patch(url: string, data: Array<Object> | Object): Promise<Array<Object> | Object> {
        return fetch(url, {...defaultOptions, method: 'PATCH', body: JSON.stringify(transformRequestData(data))})
            .then(handleResponse);
    }

    static delete(url: string): Promise<Object> {
        return fetch(url, {...defaultOptions, method: 'DELETE'})
            .then(handleObjectResponse);
    }
}
