// @flow
import {action} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import type {LoadOptions} from '../types';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';

export default class FullLoadingStrategy extends AbstractLoadingStrategy {
    paginated: boolean;

    constructor(paginated: boolean = true) {
        super();
        this.paginated = paginated;
    }

    load(resourceKey: string, options: LoadOptions, parentId: ?string | number) {
        if (!this.paginated) {
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
