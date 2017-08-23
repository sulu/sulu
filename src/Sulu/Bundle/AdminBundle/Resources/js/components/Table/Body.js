// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Checkbox from '../Checkbox';
import Radio from '../Radio';
import Icon from '../Icon';
import Cell from './Cell';
import Row from './Row';
import type {ControlItems, ControlConfig, RowProps, SelectMode} from './types';

type Props = {
    children?: ChildrenArray<Element<typeof Row>>,
    /** 
     * @ignore 
     * List of buttons to apply action handlers to every row (e.g. edit row) forwarded from table 
     */
    controls?: ControlItems,
    /**
     * @ignore
     * Can be set to "single" or "multiple". Defaults is "none".
     */
    selectMode?: SelectMode,
    /** 
     * @ignore 
     * Callback function to notify about selection and deselection of a row
     */
    onRowSelectionChange?: (rowId: string | number, selected: boolean) => void,
    /**
     * @ignore
     * Called when all the rows got selected or when all rows are selected and one gets deselected.
     */
    onAllRowsSelectedChange?: (allRowsSelected: boolean) => void,
};

export default class Body extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    componentWillReceiveProps = (nextProps: Props) => {
        this.handleAllRowSelectedChange(nextProps.children);
    };

    isMultipleSelect = () => {
        return this.props.selectMode === 'multiple';
    };

    isSingleSelect = () => {
        return this.props.selectMode === 'single';
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

        if (this.isSingleSelect()) {
            prependedCells.push(this.createRadioCell(rowProps, rowIndex));
        } else if (this.isMultipleSelect()) {
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

    createRadioCell = (rowProps: RowProps, rowIndex: number) => {
        const key = `body-radio-${rowIndex}`;
        const handleOnChange = (value: string) => {
            const identifier = rowProps.id || rowIndex;

            this.handleRowSelectionChange(identifier, !!value);
        };

        return (
            <Cell key={key}>
                <Radio
                    skin="dark"
                    value={true}
                    checked={rowProps.selected}
                    onChange={handleOnChange} />
            </Cell>
        );
    };

    createCheckboxCell = (rowProps: RowProps, rowIndex: number) => {
        const key = `body-checkbox-${rowIndex}`;
        const handleOnChange = (value: string | true) => {
            const identifier = rowProps.id || rowIndex;

            this.handleRowSelectionChange(identifier, !!value);
        };

        return (
            <Cell key={key}>
                <Checkbox
                    skin="dark"
                    value={true}
                    checked={rowProps.selected}
                    onChange={handleOnChange} />
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
                    isControl={true}>
                    <button onClick={handleControlClick}>
                        <Icon name={controlItem.icon} />
                    </button>
                </Cell>
            );
        });
    };

    handleAllRowSelectedChange = (rows: ChildrenArray<Element<typeof Row>>) => {
        const rowSelections = rows.map((row) => row.props.selected);
        const allRowsSelected = !rowSelections.includes(false);

        if (this.props.onAllRowsSelectedChange) {
            this.props.onAllRowsSelectedChange(allRowsSelected);
        }
    };

    handleRowSelectionChange = (rowId: string | number, selected: boolean) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, selected);
        }
    };

    render() {
        const {children} = this.props;
        const rows = this.cloneRows(children);

        return (
            <tbody>
                {rows}
            </tbody>
        );
    }
}
