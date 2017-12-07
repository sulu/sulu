// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {LoadOptions, LoadingStrategyInterface} from '../types';

export default class PaginationStrategy implements LoadingStrategyInterface {
    load(data: Array<Object>, resourceKey: string, options: LoadOptions) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 10}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            data.splice(0, data.length);
            data.push(...responseData);

            return response;
        }));
    }
}
