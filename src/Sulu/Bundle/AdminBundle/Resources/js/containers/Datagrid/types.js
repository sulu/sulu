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
    limit: number,
    loading: boolean,
    onAllSelectionChange: ?(selected?: boolean) => void,
    onItemActivate: (itemId: string | number) => void,
    onItemAdd: ?(id: string | number) => void,
    onItemClick: ?(itemId: string | number) => void,
    onItemDeactivate: (itemId: string | number) => void,
    onItemSelectionChange: ?(rowId: string | number, selected?: boolean) => void,
    onLimitChange: (limit: number) => void,
    onPageChange: (page: number) => void,
    onRequestItemCopy: ?(id: string | number) => Promise<{copied: boolean, parent: ?Object}>,
    onRequestItemDelete: ?(id: string | number) => Promise<{deleted: boolean}>,
    onRequestItemMove: ?(id: string | number) => Promise<{moved: boolean, parent: ?Object}>,
    onRequestItemOrder: ?(id: string | number, position: number) => Promise<{ordered: boolean}>,
    onSort: (column: string, order: SortOrder) => void,
    options: Object,
    page: ?number,
    pageCount: ?number,
    schema: Schema,
    selections: Array<number | string>,
    sortColumn: ?string,
    sortOrder: ?SortOrder,
};

export type ObservableOptions = {
    page: IObservableValue<number>,
    locale?: ?IObservableValue<string>,
};

export type LoadOptions = {
    locale?: ?string,
    page?: number,
    limit?: number,
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
    order(id: string | number, position: number): void,
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

export type ResolveCopyArgument = {copied: boolean, parent?: ?Object};
export type ResolveDeleteArgument = {deleted: boolean};
export type ResolveMoveArgument = {moved: boolean, parent?: ?Object};
export type ResolveOrderArgument = {ordered: boolean};
