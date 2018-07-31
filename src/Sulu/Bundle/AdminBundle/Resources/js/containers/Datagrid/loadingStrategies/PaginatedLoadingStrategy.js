// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {LoadOptions} from '../types';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';

export default class PaginatedLoadingStrategy extends AbstractLoadingStrategy {
    load(resourceKey: string, options: LoadOptions, parent: ?string | number) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 10}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            this.structureStrategy.clear(parent);
            responseData.forEach((item) => this.structureStrategy.addItem(item, parent));

            return response;
        }));
    }
}
