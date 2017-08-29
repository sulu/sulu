// @flow
import React from 'react';
import Checkbox from '../Checkbox';
import {Radio} from '../Radio';
import type {ButtonConfig, RowProps} from './types';
import Cell from './Cell';
import ButtonCell from './ButtonCell';
import tableStyles from './table.scss';

export default class Row extends React.PureComponent<RowProps> {
    static defaultProps = {
        selected: false,
        rowIndex: 0,
    };

    isMultipleSelect = () => {
        return this.props.selectMode === 'multiple';
    };

    isSingleSelect = () => {
        return this.props.selectMode === 'single';
    };

    createCells = (cells: any) => {
        const {buttons} = this.props;
        const prependedCells = [];

        if (buttons && buttons.length > 0) {
            const createdItems = this.createButtonCells();

            if (createdItems) {
                prependedCells.push(...createdItems);
            }
        }

        if (this.isSingleSelect()) {
            prependedCells.push(this.createRadioCell());
        } else if (this.isMultipleSelect()) {
            prependedCells.push(this.createCheckboxCell());
        }

        const clonedCells = this.cloneCells(cells);

        clonedCells.unshift(...prependedCells);

        return clonedCells;
    };

    cloneCells = (originalCells: any) => {
        const {rowIndex} = this.props;

        return React.Children.map(originalCells, (cell, index) => {
            return React.cloneElement(
                cell,
                {
                    key: `cell-${rowIndex}-${index}`,
                }
            );
        });
    };

    createRadioCell = () => {
        const {id, selected, rowIndex} = this.props;
        const key = `radio-${rowIndex}`;
        const identifier = id || rowIndex;

        return (
            <Cell
                key={key}
                small={true}>
                <Radio
                    skin="dark"
                    value={identifier}
                    checked={!!selected}
                    onChange={this.handleSingleSelectionChange} />
            </Cell>
        );
    };

    createCheckboxCell = () => {
        const {id, selected, rowIndex} = this.props;
        const key = `checkbox-${rowIndex}`;
        const identifier = id || rowIndex;

        return (
            <Cell
                key={key}
                small={true}>
                <Checkbox
                    skin="dark"
                    value={identifier}
                    checked={!!selected}
                    onChange={this.handleMultipleSelectionChange} />
            </Cell>
        );
    };

    createButtonCells = () => {
        const {id, rowIndex} = this.props;
        const {buttons} = this.props;

        if (!buttons) {
            return null;
        }

        return buttons.map((button: ButtonConfig, index) => {
            const key = `control-${rowIndex}-${index}`;
            const handleClick = button.onClick;
            const identifier = id || rowIndex;

            return (
                <ButtonCell
                    key={key}
                    icon={button.icon}
                    rowId={identifier}
                    onClick={handleClick} />
            );
        });
    };

    handleSingleSelectionChange = (rowId?: string | number) => {
        if (this.props.onSingleSelectionChange && rowId) {
            this.props.onSingleSelectionChange(rowId);
        }
    };

    handleMultipleSelectionChange = (checked: boolean, rowId?: string | number) => {
        if (this.props.onMultipleSelectionChange && rowId) {
            this.props.onMultipleSelectionChange(checked, rowId);
        }
    };

    render() {
        const {
            children,
        } = this.props;
        const cells = this.createCells(children);

        return (
            <tr className={tableStyles.row}>
                {cells}
            </tr>
        );
    }
}
