// @flow
import {action} from 'mobx';
import log from 'loglevel';
import ResourceRequester from '../../../services/ResourceRequester';
import type {LoadOptions} from '../types';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';

export default class FullLoadingStrategy extends AbstractLoadingStrategy {
    // @deprecated
    constructor() {
        super();
        log.warn(
            'The "FullLoadingStrategy" is deprecated since 2.1.9 and will be removed. ' +
            'Use "DefaultLoadingStrategy()" instead.'
        );
    }

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
