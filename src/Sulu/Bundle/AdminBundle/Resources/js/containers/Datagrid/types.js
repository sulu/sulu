// @flow
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import type {Element} from 'react';

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

export type PaginationProps = {
    children: Element<*>,
    current: ?number,
    loading: boolean,
    onChange: (page: number) => void,
    total: ?number,
};

export type ObservableOptions = {
    page: IObservableValue<number>,
    locale?: IObservableValue<string>,
};
