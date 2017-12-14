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
    schema: Schema,
    selections: Array<number | string>,
    onItemClick?: (itemId: string | number) => void,
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

export interface LoadingStrategyInterface {
    paginationAdapter: ?PaginationAdapter,
    load(data: Array<Object>, resourceKey: string, options: LoadOptions): Promise<Object>,
}

export interface StructureStrategyInterface {
    data: Array<*>,
    clear(): void,
}
