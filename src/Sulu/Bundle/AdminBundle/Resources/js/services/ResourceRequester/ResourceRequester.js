// @flow
import Requester from '../Requester';
import resourceMetadataStore from '../../stores/ResourceMetadataStore';
import type {ListOptions} from './types';

const listDefaults = {
    flat: true,
    page: 1,
    limit: 10,
};

function buildQueryString(queryOptions: ?Object) {
    const options = queryOptions;
    if (!options) {
        return '';
    }

    const searchParameters = new URLSearchParams();
    Object.keys(options).forEach((key) => {
        searchParameters.set(key, options[key]);
    });

    return '?' + searchParameters.toString();
}

export default class ResourceRequester {
    static get(resourceKey: string, id: number | string, queryOptions: ?Object) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        return Requester.get(baseUrl + '/' + id + buildQueryString(queryOptions));
    }

    static put(resourceKey: string, id: number | string, data: Object, queryOptions: ?Object) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        return Requester.put(baseUrl + '/' + id + buildQueryString(queryOptions), data);
    }

    static getList(resourceKey: string, options: ListOptions = listDefaults) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        const searchParameters = new URLSearchParams();
        const searchOptions = {...listDefaults, ...options};

        Object.keys(searchOptions).forEach((searchKey) => {
            searchParameters.set(searchKey, searchOptions[searchKey]);
        });

        let queryString = searchParameters.toString();

        queryString = queryString.replace(/%2C/gi, ',');

        return Requester.get(baseUrl + (queryString ? '?' + queryString : ''));
    }

    static delete(resourceKey: string, id: number | string) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        return Requester.delete(baseUrl + '/' + id);
    }
}
