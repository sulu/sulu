// @flow
import type {Node} from 'react';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named

export type DataItem = {
    id: string | number,
};

export type ColumnItem = DataItem & {
    hasChildren: boolean,
};

export type SchemaEntry = {
    label: string,
    type: string,
    sortable: boolean,
    visibility: 'always' | 'yes' | 'no' | 'never',
};

export type Schema = {
    [string]: SchemaEntry,
};

export type SortOrder = 'asc' | 'desc';

export type DatagridAdapterProps = {
    active: ?string | number,
    activeItems: ?Array<string | number>,
    data: Array<*>,
    disabledIds: Array<string | number>,
    loading: boolean,
    onAddClick: ?(id: string | number) => void,
    onDeleteClick: (id: string | number) => void,
    onAllSelectionChange: ?(selected?: boolean) => void,
    onItemClick: ?(itemId: string | number) => void,
    onItemActivation: (itemId: string | number) => void,
    onItemDeactivation: (itemId: string | number) => void,
    onItemSelectionChange: ?(rowId: string | number, selected?: boolean) => void,
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
    +data: Array<*>,
    +visibleItems: Array<Object>,
    +activeItems?: Array<*>,
    +activate?: (id: ?string | number) => void,
    +deactivate?: (id: ?string | number) => void,
    remove(id: string | number): void,
    getData(parent: ?string | number): ?Array<*>,
    enhanceItem(item: Object): Object,
    findById(identifier: string | number): ?Object,
    clear(): void,
}

export type TreeItem = {
    data: DataItem,
    children: Array<TreeItem>,
    hasChildren: boolean,
};

export interface FieldTransformer {
    transform(value: *): Node,
}
