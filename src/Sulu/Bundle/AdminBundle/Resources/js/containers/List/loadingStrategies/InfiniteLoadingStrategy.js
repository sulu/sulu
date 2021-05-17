// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';
import type {LoadOptions} from '../types';

export default class InfiniteLoadingStrategy extends AbstractLoadingStrategy {
    load(resourceKey: string, options: LoadOptions, parentId: ?string | number) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 50}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            responseData.forEach((item) => this.structureStrategy.addItem(item, parentId));

            return response;
        }));
    }
}
