// @flow
import type {ChildrenArray, Element} from 'react';
import Body from './Body';
import Header from './Header';
import Cell from './Cell';
import HeaderCell from './HeaderCell';

export type TableChildren = ChildrenArray<Element<typeof Header | typeof Body>>;

export type RowChildren = ChildrenArray<Element<typeof Cell>>;

export type RowProps = {
    children: ChildrenArray<Element<typeof HeaderCell | typeof Cell>>,
};
