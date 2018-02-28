// @flow
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import DatagridStore from './stores/DatagridStore';

export type DataItem = {
    id: string | number,
};

export type Schema = {
    [string]: {},
};

export type DatagridAdapterProps = {
    active?: ?string | number,
    data: Array<*>,
    loading: boolean,
    onAllSelectionChange?: (selected?: boolean) => void,
    onItemClick?: (itemId: string | number) => void,
    onItemActivation?: (itemId: string | number) => void,
    onAddClick?: (id: string | number) => void,
    onItemSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    onPageChange: (page: number) => void,
    page: ?number,
    pageCount: number,
    schema: Schema,
    selections: Array<number | string>,
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
    constructor(): void,
    initialize(datagridStore: DatagridStore): void,
    reset(datagridStore: DatagridStore): void,
    destroy(): void,
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer): Promise<Object>,
}

export interface StructureStrategyInterface {
    constructor(): void,
    data: Array<*>,
    getData(parent: ?string | number): ?Array<*>,
    enhanceItem(item: Object): Object,
    clear(): void,
}

export type TreeItem = {
    data: DataItem,
    children: Array<TreeItem>,
};
