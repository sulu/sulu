// @flow
const defaultOptions = {credentials: 'same-origin'};

export default class Requester {
    static get(url: string) {
        return fetch(url, defaultOptions);
    }
}
