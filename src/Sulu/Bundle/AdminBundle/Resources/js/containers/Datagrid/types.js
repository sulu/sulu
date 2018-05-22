// @flow
import type {Node} from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import DatagridStore from './stores/DatagridStore';

export type DataItem = {
    id: string | number,
};

export type SchemaEntry = {
    label: string,
    type: string,
    visibility: 'always' | 'yes' | 'no' | 'never',
};

export type Schema = {
    [string]: SchemaEntry,
};

export type SortOrder = 'asc' | 'desc';

export type DatagridAdapterProps = {
    active?: ?string | number,
    data: Array<*>,
    disabledIds: Array<string | number>,
    loading: boolean,
    onAddClick?: (id: string | number) => void,
    onAllSelectionChange?: (selected?: boolean) => void,
    onItemActivation?: (itemId: string | number) => void,
    onItemClick?: (itemId: string | number) => void,
    onItemSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    onPageChange: (page: number) => void,
    onSort: (column: string, order: SortOrder) => void,
    page: ?number,
    pageCount: number,
    schema: Schema,
    selections: Array<number | string>,
    sortColumn: ?string,
    sortOrder: ?SortOrder,
};

export type ObservableOptions = {
    locale?: IObservableValue<string>,
    page: IObservableValue<number>,
};

export type LoadOptions = {
    locale?: ?string,
    page?: number,
    sortBy?: string,
    sortOrder?: SortOrder,
};

export type ItemEnhancer = (item: Object) => Object;

export interface LoadingStrategyInterface {
    constructor(): void,
    destroy(): void,
    initialize(datagridStore: DatagridStore): void,
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer): Promise<Object>,
    reset(datagridStore: DatagridStore): void,
}

export interface StructureStrategyInterface {
    clear(): void,
    constructor(): void,
    data: Array<*>,
    enhanceItem(item: Object): Object,
    findById(identifier: string | number): ?Object,
    getData(parent: ?string | number): ?Array<*>,
}

export type TreeItem = {
    children: Array<TreeItem>,
    data: DataItem,
};

export interface FieldTransformer {
    transform(value: *): Node,
}
