// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Cell from './Cell';
import Row from './Row';
import type {RowProps, SelectMode, SelectedRows} from './types';

function formatCellKey(rowIndex, cellIndex) {
    return `body${rowIndex}-${cellIndex}`;
}

type Props = {
    /** Child nodes of the table body */
    children: ChildrenArray<Element<typeof Row>>,
    /** 
     * @ignore 
     * List of buttons to apply action handlers to every row (e.g. edit row) forwarded from table 
     */
    controls?: Array<any>,
    /** CSS classes to apply custom styles */
    className?: string,
    /**
     * @ignore
     * Can be set to "single" or "multiple". Defaults is "none".
     */
    selectMode?: SelectMode,
    /** 
     * @ignore Callback function to notify about the selected row(s) 
     */
    onRowSelection?: (rowId: SelectedRows) => void,
};

export default class Body extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    isSelectable = () => {
        const {selectMode} = this.props;

        return selectMode === 'single' || selectMode === 'multiple';
    };

    cloneRows = (originalRows: ChildrenArray<Element<typeof Row>>) => {
        return React.Children.map(originalRows, (row, index) => {
            if (React.isValidElement(row)) {
                const cells = this.createCells(row.props.children, row.props, index);

                return React.cloneElement(
                    row,
                    {
                        key: index,
                        ...row.props,
                    },
                    cells,
                );
            }
        });
    };

    createCells = (cells: ChildrenArray<Element<typeof Cell>>, rowProps: RowProps, rowIndex: number) => {
        const {
            controls,
        } = this.props;
        const prependedCells = [];
        let cellIndex = 0;

        // if (controls && controls.length > 0) {
        //     const cellKey = formatCellKey(rowIndex, cellIndex);
        //     cellIndex += 1;

        //     prependedCells.push(this.createControlCells(rowProps, cellKey));
        // }

        if (this.isSelectable()) {
            const cellKey = formatCellKey(rowIndex, cellIndex);
            cellIndex += 1;

            prependedCells.push(this.createCheckboxCell(rowProps, cellKey));
        }

        const clonedCells = this.cloneCells(cells, rowIndex, cellIndex);

        clonedCells.unshift(...prependedCells);

        return clonedCells;
    };

    cloneCells = (originalCells: ChildrenArray<Element<typeof Cell>>, rowIndex: number, cellIndex: number = 0) => {
        return React.Children.map(originalCells, (cell, index) => {
            if (React.isValidElement(cell)) {
                const cellKey = formatCellKey(rowIndex, cellIndex + index);

                return React.cloneElement(cell,
                    {
                        key: cellKey,
                    }
                );
            }
        });
    };

    createCheckboxCell = (rowProps: RowProps, key: string | number) => {
        return (
            <Cell key={key}>
                Checkbox
            </Cell>
        );
    };

    // createControlCells = (controls) => {

    // };

    onRowSelection = (rowIds: SelectedRows) => {
        if (this.props.onRowSelection) {
            this.props.onRowSelection(rowIds);
        }
    };

    render() {
        const {
            children,
            className,
        } = this.props;
        const rows = this.cloneRows(children);

        return (
            <tbody className={className}>
                {rows}
            </tbody>
        );
    }
}
