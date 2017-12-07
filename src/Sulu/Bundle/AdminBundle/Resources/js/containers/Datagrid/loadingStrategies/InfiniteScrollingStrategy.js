// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {LoadOptions, LoadingStrategyInterface} from '../types';

export default class InfiniteScrollingStrategy implements LoadingStrategyInterface {
    load(data: Array<Object>, resourceKey: string, options: LoadOptions) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 10}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            data.push(...responseData);

            return response;
        }));
    }
}
