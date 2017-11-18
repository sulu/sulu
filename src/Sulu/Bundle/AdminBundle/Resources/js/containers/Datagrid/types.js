// @flow
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
    onLoadChildren?: (id: string | number, columnId: number, hasChildren: boolean) => void,
    depthLoading?: number,
};

export type ObservableOptions = {
    page: observable,
    locale?: observable,
};
