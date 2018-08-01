// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {LoadOptions} from '../types';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';

export default class FullLoadingStrategy extends AbstractLoadingStrategy {
    load(resourceKey: string, options: LoadOptions, parentId: ?string | number) {
        return ResourceRequester
            .getList(resourceKey, {...options, page: undefined, limit: undefined}).then(action((response) => {
                const responseData = response._embedded[resourceKey];
                this.structureStrategy.clear(parentId);
                responseData.forEach((item) => this.structureStrategy.addItem(item, parentId));
                return response;
            }));
    }
}
