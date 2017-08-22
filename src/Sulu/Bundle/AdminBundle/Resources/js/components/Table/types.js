// @flow
import type {ChildrenArray, Element} from 'react';
import Body from './Body';
import Header from './Header';
import Cell from './Cell';
import HeaderCell from './HeaderCell';

export type TableChildren = ChildrenArray<Element<typeof Header | typeof Body>>;

export type RowChildren = ChildrenArray<Element<typeof Cell>>;

export type SelectMode = 'none' | 'single' | 'multiple';

export type ControlConfig = {
    icon: string,
    onClick: () => void,
};

export type ControlItems = Array<ControlConfig>

export type RowProps = {
    children: ChildrenArray<Element<typeof HeaderCell | typeof Cell>>,
    /** The id will be used to mark the selected row inside the onRowSelection callback. */
    id?: string | number,
    /** 
     * @ignore 
     * List of buttons to apply action handlers to every row (e.g. edit row) forwarded from table body 
     */
    controls?: Array<any>,
    /** If set to true the row is selected */
    selected?: boolean,
    /** 
     * @ignore 
     * Callback function to notify about the selected row(s) 
     */
    onRowSelection?: (rowId: SelectedRows) => void,
};
