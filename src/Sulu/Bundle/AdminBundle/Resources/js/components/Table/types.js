// @flow
import type {Element} from 'react';
import Body from './Body';
import Header from './Header';
import Cell from './Cell';
import HeaderCell from './HeaderCell';

export type TableChildren = Element<Header | Body>;

export type RowChildren = Element<Cell>;

export type SelectMode = 'none' | 'single' | 'multi';

export type SelectedRows = string | number | Array<string | number>;

export type RowProps = {
    /** Child nodes of the table row */
    children: Element<HeaderCell | Cell>,
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
