// @flow
import Requester from '../Requester';
import resourceRouteRegistry from './registries/ResourceRouteRegistry';
import type {ListOptions} from './types';

export default class ResourceRequester {
    static get(resourceKey: string, parameters: ?Object) {
        return Requester.get(resourceRouteRegistry.getDetailUrl(resourceKey, {...parameters}));
    }

    static post(resourceKey: string, data: ?Object, parameters: ?Object) {
        return Requester.post(resourceRouteRegistry.getDetailUrl(resourceKey, {...parameters}), data);
    }

    static put(resourceKey: string, data: Object, parameters: ?Object) {
        return Requester.put(resourceRouteRegistry.getDetailUrl(resourceKey, {...parameters}), data);
    }

    static patchList(resourceKey: string, data: Array<Object>) {
        return Requester.patch(resourceRouteRegistry.getListUrl(resourceKey), data);
    }

    static getList(resourceKey: string, options: ListOptions = {}) {
        return Requester.get(resourceRouteRegistry.getListUrl(resourceKey, {...options, flat: true}));
    }

    static delete(resourceKey: string, parameters: ?Object) {
        return Requester.delete(resourceRouteRegistry.getDetailUrl(resourceKey, {...parameters}));
    }

    static deleteList(resourceKey: string, parameters: Object){
        return Requester.delete(resourceRouteRegistry.getListUrl(resourceKey, parameters));
    }
}
