// @flow
import type {ChildrenArray, Element} from 'react';
import Body from './Body';
import Header from './Header';
import Cell from './Cell';
import HeaderCell from './HeaderCell';

export type TableChildren = ChildrenArray<Element<typeof Header | typeof Body>>;

export type RowChildren = ChildrenArray<Element<Cell>>;

export type SelectMode = 'none' | 'single' | 'multi';

export type SelectedRows = string | number | Array<string | number>;

export type ControlConfig = {
    icon: string,
    onClick: () => void,
};

export type ControlItems = Array<ControlConfig>

export type RowProps = {
    /** Child nodes of the table row */
    children: ChildrenArray<Element<typeof HeaderCell | typeof Cell>>,
    /** 
     * @ignore 
     * List of buttons to apply action handlers to every row (e.g. edit row) forwarded from table body 
     */
    controls?: Array<any>,
    /** CSS classes to apply custom styles */
    className?: string,
    /** If set to true the row is selected */
    selected?: boolean,
    /** 
     * @ignore 
     * Callback function to notify about the selected row(s) 
     */
    onRowSelection?: (rowId: SelectedRows) => void,
};
