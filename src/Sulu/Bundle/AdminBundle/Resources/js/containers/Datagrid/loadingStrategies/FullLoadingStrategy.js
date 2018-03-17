// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {ItemEnhancer, LoadOptions} from '../types';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';

export default class FullLoadingStrategy extends AbstractLoadingStrategy {
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer) {
        return ResourceRequester
            .getList(resourceKey, {...options, page: undefined, limit: undefined}).then(action((response) => {
                const responseData = response._embedded[resourceKey] || response._embedded.items;

                data.splice(0, data.length);
                data.push(...responseData.map(enhanceItem));

                return response;
            }));
    }
}
