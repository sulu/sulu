// @flow
import Requester from '../Requester';
import resourceMetadataStore from '../../stores/ResourceMetadataStore';
import type {ListOptions} from './types';

const listDefaults = {
    flat: true,
    page: 1,
    limit: 10,
};

export default class ResourceRequester {
    static get(resourceKey: string, id: number | string) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        return Requester.get(baseUrl + '/' + id);
    }

    static put(resourceKey: string, id: number | string, data: Object) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        return Requester.put(baseUrl + '/' + id, data);
    }

    static getList(resourceKey: string, options: ListOptions = listDefaults) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        const searchParameters = new URLSearchParams();
        const searchOptions = {...listDefaults, ...options};

        Object.keys(searchOptions).forEach((searchKey) => {
            searchParameters.set(searchKey, searchOptions[searchKey]);
        });

        const queryString = searchParameters.toString();

        return Requester.get(baseUrl + (queryString ? '?' + queryString : ''));
    }

    static delete(resourceKey: string, id: number | string) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        return Requester.delete(baseUrl + '/' + id);
    }
}
