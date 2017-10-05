// @flow
import type {ComponentType} from 'react';
import {observable} from 'mobx';

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
    onItemClick?: (rowId: string | number) => void,
    onItemSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    onAllSelectionChange?: (selected?: boolean) => void,
};

export type DatagridAdapter = ComponentType<DatagridAdapterProps>;

export type ObservableOptions = {
    page: observable,
    locale: observable,
};
