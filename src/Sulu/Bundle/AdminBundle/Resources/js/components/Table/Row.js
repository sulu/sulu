// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Checkbox from '../Checkbox';
import {Radio} from '../Radio';
import type {ButtonConfig, SelectMode} from './types';
import ButtonCell from './ButtonCell';
import Cell from './Cell';
import tableStyles from './table.scss';

type Props = {
    children: ChildrenArray<Element<typeof Cell>>,
    /** The index of the row inside the body */
    rowIndex: number,
    /** The id will be used to mark the selected row inside the onRowSelection callback. */
    id?: string | number,
    /** @ignore */
    buttons?: Array<ButtonConfig>,
    /** @ignore */
    selectMode?: SelectMode,
    /** If set to true the row is selected */
    selected?: boolean,
    /** @ignore */
    onSelectionChange?: (rowId: string | number, checked?: boolean) => void,
};

export default class Row extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
        rowIndex: 0,
    };

    isMultipleSelect = () => {
        return 'multiple' === this.props.selectMode;
    };

    isSingleSelect = () => {
        return 'single' === this.props.selectMode;
    };

    createCells = (cells: ChildrenArray<Element<typeof Cell>>) => {
        const {buttons} = this.props;
        const prependedCells = [];

        if (buttons && 0 < buttons.length) {
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

    cloneCells = (originalCells: ChildrenArray<Element<typeof Cell>>) => {
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
                small={true}
            >
                <Radio
                    skin="dark"
                    value={identifier}
                    checked={!!selected}
                    onChange={this.handleSingleSelectionChange}
                />
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
                small={true}
            >
                <Checkbox
                    skin="dark"
                    value={identifier}
                    checked={!!selected}
                    onChange={this.handleMultipleSelectionChange}
                />
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
                    onClick={handleClick}
                />
            );
        });
    };

    handleSingleSelectionChange = (rowId?: string | number) => {
        if (this.props.onSelectionChange && rowId) {
            this.props.onSelectionChange(rowId);
        }
    };

    handleMultipleSelectionChange = (checked: boolean, rowId?: string | number) => {
        if (this.props.onSelectionChange && rowId) {
            this.props.onSelectionChange(rowId, checked);
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
