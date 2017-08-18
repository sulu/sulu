// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Icon from '../Icon';
import Cell from './Cell';
import Row from './Row';
import type {ControlItems, ControlConfig, RowProps, SelectMode, SelectedRows} from './types';
import tableStyles from './table.scss';

type Props = {
    /** Child nodes of the table body */
    children: ChildrenArray<Element<typeof Row>>,
    /** 
     * @ignore 
     * List of buttons to apply action handlers to every row (e.g. edit row) forwarded from table 
     */
    controls?: ControlItems,
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
                        key: `body-row-${index}`,
                        ...row.props,
                    },
                    cells,
                );
            }
        });
    };

    createCells = (cells: ChildrenArray<Element<typeof Cell>>, rowProps: RowProps, rowIndex: number) => {
        const {controls} = this.props;
        const prependedCells = [];

        if (controls && controls.length > 0) {
            const createdItems = this.createControlCells(rowProps, rowIndex);

            prependedCells.push(...createdItems);
        }

        if (this.isSelectable()) {
            prependedCells.push(this.createCheckboxCell(rowProps, rowIndex));
        }

        const clonedCells = this.cloneCells(cells, rowIndex);

        clonedCells.unshift(...prependedCells);

        return clonedCells;
    };

    cloneCells = (originalCells: ChildrenArray<Element<typeof Cell>>, rowIndex: number) => {
        return React.Children.map(originalCells, (cell, index) => {
            if (React.isValidElement(cell)) {
                return React.cloneElement(
                    cell,
                    {
                        key: `body-cell-${rowIndex}-${index}`,
                    }
                );
            }
        });
    };

    createCheckboxCell = (rowProps: RowProps, rowIndex: number) => {
        const key = `body-checkbox-${rowIndex}`;

        return (
            <Cell key={key}>
                Checkbox
            </Cell>
        );
    };

    createControlCells = (rowProps: RowProps, rowIndex: number) => {
        const {controls} = this.props;

        if (!controls) {
            return null;
        }

        return controls.map((controlItem: ControlConfig, index) => {
            const key = `body-control-${rowIndex}-${index}`;
            const handleControlClick = controlItem.onClick;

            return (
                <Cell
                    key={key}
                    className={tableStyles.controlCell}>
                    <button onClick={handleControlClick}>
                        <Icon name={controlItem.icon} />
                    </button>
                </Cell>
            );
        });
    };

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
