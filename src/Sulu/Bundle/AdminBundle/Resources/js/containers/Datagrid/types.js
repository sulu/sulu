// @flow
import type {Node} from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named

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
    onAllSelectionChange?: (selected?: boolean) => void,
    onItemClick?: (itemId: string | number) => void,
    onItemActivation?: (itemId: string | number) => void,
    onAddClick?: (id: string | number) => void,
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
    page: IObservableValue<number>,
    locale?: IObservableValue<string>,
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
    load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer): Promise<Object>,
}

export interface StructureStrategyInterface {
    constructor(): void,
    data: Array<*>,
    getData(parent: ?string | number): ?Array<*>,
    enhanceItem(item: Object): Object,
    findById(identifier: string | number): ?Object,
    clear(): void,
}

export type TreeItem = {
    data: DataItem,
    children: Array<TreeItem>,
};

export interface FieldTransformer {
    transform(value: *): Node,
}
