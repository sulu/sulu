// @flow
import {action} from 'mobx';
import InfiniteScroller from '../../../components/InfiniteScroller';
import ResourceRequester from '../../../services/ResourceRequester';
import type {ItemEnhancer, LoadOptions, LoadingStrategyInterface} from '../types';

export default class InfiniteLoadingStrategy implements LoadingStrategyInterface {
    paginationAdapter = InfiniteScroller;

    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 50}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            data.push(...responseData.map(enhanceItem));

            return response;
        }));
    }
}
