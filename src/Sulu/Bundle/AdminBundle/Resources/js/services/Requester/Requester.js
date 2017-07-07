// @flow
const defaultOptions = {credentials: 'same-origin'};

export default class Requester {
    static get(url: string): Promise<Response> {
        return fetch(url, defaultOptions);
    }
}
