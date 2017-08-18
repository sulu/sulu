// @flow
import type {Element, ChildrenArray} from 'react';
import React from 'react';
import HeaderCell from './HeaderCell';
import Row from './Row';
import type {SelectMode} from './types';
import tableStyles from './table.scss';

function formatHeaderCellKey(cellIndex) {
    return `header-${cellIndex}`;
}

type Props = {
    /** Child nodes of the header */
    children: ChildrenArray<Element<typeof Row>>,
    /** 
     * List of buttons to apply action handlers to every row (e.g. edit row).
     * The header will display the icons.
     */
    controls?: Array<any>,
    /** CSS classes to apply custom styles */
    className?: string,
    /** Can be set to "single" or "multiple". Defaults is "none". */
    selectMode?: SelectMode,
    /** 
     * Called when the "select all" checkbox was clicked. The checkbos only shows up on "selectMode: 'multiple'"
     * Returns the checked state.
     */
    onSelectAll?: (checked: boolean) => void,
};

export default class Header extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    isMultipleSelect = () => {
        return this.props.selectMode === 'multiple';
    };

    isSingleSelect = () => {
        return this.props.selectMode === 'single';
    };

    createHeaderRow = (originalRows: ChildrenArray<Element<typeof Row>>) => {
        const rows = React.Children.toArray(originalRows);

        if (rows.length > 1) {
            throw new Error(`Expected one header row, got ${rows.length}`);
        }

        const row = rows[0];
        const cells = this.createHeaderCells(row.props.children);

        if (this.isMultipleSelect()) {
            cells.unshift(this.createCheckboxCell());
        } else if (this.isSingleSelect()) {
            cells.unshift(this.createEmptyCell());
        }

        return React.cloneElement(
            row,
            {},
            cells,
        );
    };

    createHeaderCells = (headerCells: ChildrenArray<Element<typeof HeaderCell>>) => {
        return React.Children.map(headerCells, (headerCell: Element<typeof HeaderCell>, index: number) => {
            return React.cloneElement(
                headerCell,
                {
                    ...headerCell.props,
                    key: formatHeaderCellKey(index),
                },
            );
        });
    };

    createCheckboxCell = () => {
        return (
            <HeaderCell>
                Checkbox
            </HeaderCell>
        );
    };

    createEmptyCell = () => {
        return (
            <HeaderCell />
        );
    };

    render() {
        const {
            children,
        } = this.props;
        const cells = this.createHeaderRow(children);

        return (
            <thead className={tableStyles.header}>
                {cells}
            </thead>
        );
    }
}
