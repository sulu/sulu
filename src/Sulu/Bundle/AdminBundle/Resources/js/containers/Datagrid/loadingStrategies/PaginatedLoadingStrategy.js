// @flow
import {action} from 'mobx';
import Pagination from '../../../components/Pagination';
import ResourceRequester from '../../../services/ResourceRequester';
import type {ItemEnhancer, LoadOptions, LoadingStrategyInterface} from '../types';

export default class PaginatedLoadingStrategy implements LoadingStrategyInterface {
    paginationAdapter = Pagination;

    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 10}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            data.splice(0, data.length);
            data.push(...responseData.map(enhanceItem));

            return response;
        }));
    }
}
