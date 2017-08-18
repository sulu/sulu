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
    /** CSS classes to apply custom styles */
    className?: string,
};
