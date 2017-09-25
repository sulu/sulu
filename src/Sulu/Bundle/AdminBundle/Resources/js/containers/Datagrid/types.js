// @flow
import type {ComponentType} from 'react';

export type DataItem = {
    id: string | number,
};

export type Schema = {
    [string]: {},
};

export type AdapterProps = {
    data: Array<DataItem>,
    schema: Schema,
    selections: Array<number | string>,
    onItemEditClick?: (rowId: string | number) => void,
    onItemSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    onAllSelectionChange?: (selected?: boolean) => void,
};

export type Adapter = ComponentType<AdapterProps>;
