// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {ItemEnhancer, LoadOptions, LoadingStrategyInterface} from '../types';

export default class FullLoadingStrategy implements LoadingStrategyInterface {
    paginationAdapter = null;

    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer) {
        return ResourceRequester
            .getList(resourceKey, {...options, page: undefined, limit: undefined}).then(action((response) => {
                const responseData = response._embedded[resourceKey];
                data.splice(0, data.length);
                data.push(...responseData.map(enhanceItem));

                return response;
            }));
    }
}
