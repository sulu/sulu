// @flow
import type {Element} from 'react';
import React from 'react';
import HeaderCell from './HeaderCell';
import Row from './Row';
import type {SelectMode} from './types';
import tableStyles from './table.scss';

function formatHeaderCellKey(rowIndex, cellIndex) {
    return `header-${cellIndex}`;
}

export default class Header extends React.PureComponent {
    props: {
        /** Child nodes of the header */
        children: Element<Row>,
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

    createHeaderRow = (originalRows: Element<Row>) => {
        const rows = React.Children.toArray(originalRows);

        if (rows.length > 1) {
            throw new Error(`Expected one header row, got ${rows.length}`);
        }

        const row = rows[0];

        const cells = this.createHeaderCells(row.props.children);

        return React.cloneElement(
            row,
            {},
            cells,
        );
    };

    createHeaderCells = (headerCells: Element<HeaderCell>) => {
        return React.Children.map(headerCells, (headerCell: Element<HeaderCell>) => {
            return React.cloneElement(
                headerCell,
                {...headerCell.props},
            );
        });
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
