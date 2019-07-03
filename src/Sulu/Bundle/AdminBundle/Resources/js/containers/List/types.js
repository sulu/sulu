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
    sortable: boolean,
    type: string,
    visibility: 'always' | 'yes' | 'no' | 'never',
};

export type Schema = {
    [string]: SchemaEntry,
};

export type SortOrder = 'asc' | 'desc';

export type Action = {|
    icon: string,
    onClick: ?(itemId: string | number, index: number) => void,
|};

export type ListAdapterProps = {
    actions?: Array<Action>,
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
    locale?: ?IObservableValue<string>,
    page: IObservableValue<number>,
};

export type LoadOptions = {
    limit?: number,
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
    +activate?: (id: ?string | number) => void,
    +activeItems?: Array<*>,
    +addItem: (item: Object, parentId: ?string | number) => void,
    +clear: (parentId: ?string | number) => void,
    +constructor: () => void,
    +data: Array<*>,
    +deactivate?: (id: ?string | number) => void,
    +findById: (identifier: string | number) => ?Object,
    +order: (id: string | number, position: number) => void,
    +remove: (id: string | number) => void,
    +visibleItems: Array<Object>,
}

export type TreeItem = {
    children: Array<TreeItem>,
    data: DataItem,
    hasChildren: boolean,
};

export interface FieldTransformer {
    transform(value: *): Node,
}

export type ResolveCopyArgument = {copied: boolean, parent?: ?Object};
export type ResolveDeleteArgument = {deleted: boolean};
export type ResolveMoveArgument = {moved: boolean, parent?: ?Object};
export type ResolveOrderArgument = {ordered: boolean};
