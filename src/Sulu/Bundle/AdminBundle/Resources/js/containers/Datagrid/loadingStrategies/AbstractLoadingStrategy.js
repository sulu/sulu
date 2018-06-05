// @flow
import type {LoadingStrategyInterface, LoadOptions, ItemEnhancer} from '../types';

export default class AbstractLoadingStrategy implements LoadingStrategyInterface {
    paginationAdapter = undefined;

    // eslint-disable-next-line no-unused-vars
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer): Promise<Object> {
        throw new Error('Not implemented');
    }
}
