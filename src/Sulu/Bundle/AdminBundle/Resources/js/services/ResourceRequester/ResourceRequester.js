// @flow
import Requester from '../Requester';
import resourceEndpointRegistry from './registries/ResourceEndpointRegistry';
import type {ListOptions} from './types';

export default class ResourceRequester {
    static get(resourceKey: string, parameters: ?Object) {
        return Requester.get(resourceEndpointRegistry.getDetailUrl(resourceKey, {...parameters}));
    }

    static post(resourceKey: string, data: ?Object, parameters: ?Object) {
        return Requester.post(resourceEndpointRegistry.getDetailUrl(resourceKey, {...parameters}), data);
    }

    static put(resourceKey: string, data: Object, parameters: ?Object) {
        return Requester.put(resourceEndpointRegistry.getDetailUrl(resourceKey, {...parameters}), data);
    }

    static patchList(resourceKey: string, data: Array<Object>) {
        return Requester.patch(resourceEndpointRegistry.getListUrl(resourceKey), data);
    }

    static getList(resourceKey: string, options: ListOptions = {}) {
        return Requester.get(resourceEndpointRegistry.getListUrl(resourceKey, {...options, flat: true}));
    }

    static delete(resourceKey: string, parameters: ?Object) {
        return Requester.delete(resourceEndpointRegistry.getDetailUrl(resourceKey, {...parameters}));
    }

    static deleteList(resourceKey: string, parameters: Object){
        return Requester.delete(resourceEndpointRegistry.getListUrl(resourceKey, parameters));
    }
}
