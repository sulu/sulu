// @flow
import type {LoadingStrategyInterface} from '../types';
import DatagridStore from '../stores/DatagridStore';

export default class AbstractLoadingStrategy implements LoadingStrategyInterface {
    paginationAdapter = undefined;

    initialize(datagridStore: DatagridStore) {
        if (datagridStore.loadingStrategy) {
            datagridStore.pageCount = 0;
            datagridStore.setPage(1);
        }
    }

    destroy() {}

    load(...rest: Array<*>) {
        throw Error('Not implemented. Passed parameters are [' + rest.join(', ') + ']');
    }
}
