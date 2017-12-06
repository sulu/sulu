// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';

export default class PaginationStrategy {
    load(data: Array<Object>, resourceKey: string, options: {page: number, locale: string}) {
        return ResourceRequester.getList(resourceKey, options).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            data.splice(0, data.length);
            data.push(...responseData);

            return response;
        }));
    }
}
