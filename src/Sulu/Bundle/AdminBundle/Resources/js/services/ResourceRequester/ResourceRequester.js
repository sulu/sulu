// @flow
import Requester from '../Requester';
import resourceMetadataStore from '../../stores/ResourceMetadataStore';
import type {CGetOptions} from './types';

const cgetDefaults = {
    flat: true,
    page: 1,
    limit: 10,
};

export default class ResourceRequester {
    static cget(resourceKey: string, options: CGetOptions = cgetDefaults) {
        const baseUrl = resourceMetadataStore.getBaseUrl(resourceKey);
        const searchParameters = new URLSearchParams();
        const searchOptions = {...cgetDefaults, ...options};

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
