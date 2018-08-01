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

export interface LoadingStrategyInterface {
    constructor(): void,
    load(resourceKey: string, options: LoadOptions, parentId: ?string | number): Promise<Object>,
    setStructureStrategy(structureStrategy: StructureStrategyInterface): void,
}

export interface StructureStrategyInterface {
    constructor(): void,
    +data: Array<*>,
    +visibleItems: Array<Object>,
    +activeItems?: Array<*>,
    +activate?: (id: ?string | number) => void,
    +deactivate?: (id: ?string | number) => void,
    addItem(item: Object, parentId: ?string | number): void,
    remove(id: string | number): void,
    findById(identifier: string | number): ?Object,
    clear(parentId: ?string | number): void,
}

export type TreeItem = {
    data: DataItem,
    children: Array<TreeItem>,
    hasChildren: boolean,
};

export interface FieldTransformer {
    transform(value: *): Node,
}
