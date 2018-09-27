// @flow
import Requester from '../Requester';
import resourceMetadataStore from '../../stores/ResourceMetadataStore';
import {buildQueryString} from '../../utils/Request';
import type {ListOptions} from './types';

export default class ResourceRequester {
    static get(resourceKey: string, id: ?number | string, queryOptions: ?Object) {
        const endpoint = resourceMetadataStore.getEndpoint(resourceKey);
        return Requester.get(endpoint + (id ? '/' + id : '') + buildQueryString(queryOptions));
    }

    static post(resourceKey: string, data: Object, queryOptions: ?Object) {
        const endpoint = resourceMetadataStore.getEndpoint(resourceKey);
        return Requester.post(endpoint + buildQueryString(queryOptions), data);
    }

    static postWithId(resourceKey: string, id: number | string, data: ?Object, queryOptions: ?Object) {
        const endpoint = resourceMetadataStore.getEndpoint(resourceKey);
        return Requester.post(endpoint + '/' + id + buildQueryString(queryOptions), data);
    }

    static put(resourceKey: string, id: number | string, data: Object, queryOptions: ?Object) {
        const endpoint = resourceMetadataStore.getEndpoint(resourceKey);
        return Requester.put(endpoint + '/' + id + buildQueryString(queryOptions), data);
    }

    static getList(resourceKey: string, options: ListOptions = {}) {
        const endpoint = resourceMetadataStore.getEndpoint(resourceKey);
        const queryOptions = {...options, flat: true};

        return Requester.get(endpoint + buildQueryString(queryOptions));
    }

    static delete(resourceKey: string, id: number | string, queryOptions: ?Object) {
        const endpoint = resourceMetadataStore.getEndpoint(resourceKey);
        return Requester.delete(endpoint + '/' + id + buildQueryString(queryOptions));
    }
}
