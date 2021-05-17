// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';
import type {LoadingStrategyOptions, LoadOptions} from '../types';

export default class DefaultLoadingStrategy extends AbstractLoadingStrategy {
    options: LoadingStrategyOptions;

    constructor(options: LoadingStrategyOptions = {}) {
        super();
        this.options = options;
    }

    load(resourceKey: string, options: LoadOptions, parentId: ?string | number) {
        if (!this.options.paginated) {
            options.page = undefined;
            options.limit = undefined;
        }

        return ResourceRequester.getList(resourceKey, {...options}).then(action((response) => {
            const responseData = response._embedded[resourceKey];
            this.structureStrategy.clear(parentId);
            responseData.forEach((item) => this.structureStrategy.addItem(item, parentId));

            return response;
        }));
    }
}
