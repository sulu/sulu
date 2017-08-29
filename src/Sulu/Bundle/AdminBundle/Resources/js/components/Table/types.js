// @flow
import type {ChildrenArray, Element} from 'react';
import Body from './Body';
import Header from './Header';
import Cell from './Cell';

export type TableChildren = ChildrenArray<Element<typeof Header | typeof Body>>;

export type RowChildren = ChildrenArray<Element<typeof Cell>>;

export type SelectMode = 'none' | 'single' | 'multiple';

export type ButtonConfig = {
    icon: string,
    onClick: (string | number) => void,
};

export type RowProps = {
    children: ChildrenArray<any>,
    /** The index of the row inside the body */
    rowIndex: number,
    /** The id will be used to mark the selected row inside the onRowSelection callback. */
    id?: string | number,
    /** 
     * @ignore 
     * List of buttons to apply action handlers to every row (e.g. edit row) forwarded from table body 
     */
    buttons?: Array<ButtonConfig>,
    /**
     * @ignore
     * Can be set to "single" or "multiple". Defaults is "none".
     */
    selectMode?: SelectMode,
    /** If set to true the row is selected */
    selected?: boolean,
    /** 
     * @ignore 
     * Callback function to notify about the selected row(s) in single selection mode
     */
    onSingleSelectionChange?: (rowId: string | number) => void,
    /** 
     * @ignore 
     * Callback function to notify about the selected row(s) in multiple selection mode
     */
    onMultipleSelectionChange?: (checked: boolean, rowId: string | number) => void,
};
