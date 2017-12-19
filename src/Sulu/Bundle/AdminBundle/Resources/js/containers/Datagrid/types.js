// @flow
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {PaginationAdapter} from '../../types';

export type DataItem = {
    id: string | number,
};

export type Schema = {
    [string]: {},
};

export type DatagridAdapterProps = {
    data: Array<DataItem>,
    active?: ?string | number,
    schema: Schema,
    selections: Array<number | string>,
    onItemClick?: (itemId: string | number) => void,
    onItemActivation?: (itemId: string | number) => void,
    onItemSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    onAllSelectionChange?: (selected?: boolean) => void,
};

export type ObservableOptions = {
    page: IObservableValue<number>,
    locale?: IObservableValue<string>,
};

export type LoadOptions = {
    page?: number,
    locale?: ?string,
};

export type ItemEnhancer = (item: Object) => Object;

export interface LoadingStrategyInterface {
    paginationAdapter: ?PaginationAdapter,
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer): Promise<Object>,
}

export interface StructureStrategyInterface {
    data: Array<*>,
    getData(parent: ?string | number): ?Array<*>,
    enhanceItem(item: Object): Object,
    clear(): void,
}

export type TreeItem = {
    data: DataItem,
    children: Array<TreeItem>,
};
