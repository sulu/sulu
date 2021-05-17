// @flow
import {action} from 'mobx';
import log from 'loglevel';
import ResourceRequester from '../../../services/ResourceRequester';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';
import type {LoadOptions} from '../types';

export default class PaginatedLoadingStrategy extends AbstractLoadingStrategy {
    // @deprecated
    constructor() {
        super();
        log.warn(
            'The "PaginatedLoadingStrategy" is deprecated since 2.1.9 and will be removed. ' +
            'Use "DefaultLoadingStrategy({paginated: true})" instead.'
        );
    }

    load(resourceKey: string, options: LoadOptions, parentId: ?string | number) {
        return ResourceRequester.getList(resourceKey, {...options}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            this.structureStrategy.clear(parentId);
            responseData.forEach((item) => this.structureStrategy.addItem(item, parentId));

            return response;
        }));
    }
}
