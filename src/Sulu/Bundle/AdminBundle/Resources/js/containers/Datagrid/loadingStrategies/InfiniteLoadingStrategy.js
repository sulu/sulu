// @flow
import {action, intercept} from 'mobx';
import type {IValueWillChange} from 'mobx'; // eslint-disable-line import/named
import DatagridStore from '../stores/DatagridStore';
import ResourceRequester from '../../../services/ResourceRequester';
import type {ItemEnhancer, LoadOptions} from '../types';
import AbstractLoadingStrategy from './AbstractLoadingStrategy';

export default class InfiniteLoadingStrategy extends AbstractLoadingStrategy {
    localeInterceptionDisposer: () => void;

    initialize(datagridStore: DatagridStore) {
        if (datagridStore.observableOptions.locale) {
            this.localeInterceptionDisposer = intercept(
                datagridStore.observableOptions.locale,
                '',
                (change: IValueWillChange<number>) => {
                    if (datagridStore.observableOptions.locale !== change.newValue) {
                        datagridStore.structureStrategy.clear();
                        datagridStore.observableOptions.page.set(1);

                        return change;
                    }
                }
            );
        }
    }

    destroy() {
        if (this.localeInterceptionDisposer) {
            this.localeInterceptionDisposer();
        }
    }

    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer) {
        return ResourceRequester.getList(resourceKey, {...options, limit: 50}).then(action((response) => {
            const responseData = response._embedded[resourceKey] || response._embedded.items;
            data.push(...responseData.map(enhanceItem));

            return response;
        }));
    }
}
