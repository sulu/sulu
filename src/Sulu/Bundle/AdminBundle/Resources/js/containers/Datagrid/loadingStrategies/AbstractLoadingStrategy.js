// @flow
import type {LoadingStrategyInterface, LoadOptions, ItemEnhancer} from '../types';
import DatagridStore from '../stores/DatagridStore';

export default class AbstractLoadingStrategy implements LoadingStrategyInterface {
    paginationAdapter = undefined;

    // eslint-disable-next-line no-unused-vars
    initialize(datagridStore: DatagridStore) {}

    reset(datagridStore: DatagridStore) {
        datagridStore.pageCount = 0;
        datagridStore.setPage(1);
    }

    destroy() {}

    // eslint-disable-next-line no-unused-vars
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer): Promise<Object> {
        throw new Error('Not implemented');
    }
}
