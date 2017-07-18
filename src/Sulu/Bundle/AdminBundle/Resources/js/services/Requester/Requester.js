// @flow
const defaultOptions = {credentials: 'same-origin'};

export default class Requester {
    static get(url: string): Promise<Object> {
        return fetch(url, defaultOptions)
            .then((response) => response.json());
    }
}
