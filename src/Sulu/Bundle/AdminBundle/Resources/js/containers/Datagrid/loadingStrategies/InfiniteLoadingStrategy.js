// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {ItemEnhancer, LoadOptions} from '../types';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';

export default class InfiniteLoadingStrategy extends AbstractLoadingStrategy {
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 50}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            data.push(...responseData.map(enhanceItem));

            return response;
        }));
    }
}
