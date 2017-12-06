// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';

export default class InfiniteScrollingStrategy {
    load(data: Array<Object>, resourceKey: string, options: {page: number, locale: string}) {
        return ResourceRequester.getList(resourceKey, options).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            data.push(...responseData);

            return response;
        }));
    }
}
