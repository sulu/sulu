// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Checkbox from '../Checkbox';
import {Radio} from '../Radio';
import type {ButtonConfig, SelectMode} from './types';
import ButtonCell from './ButtonCell';
import Cell from './Cell';
import tableStyles from './table.scss';
import Icon from "../Icon/Icon";
import Loader from "../Loader/Loader";

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
    /** If set to true the select is in first cell */
    selectInFirstCell?: boolean,
    /** If set to true the row is selected */
    selected?: boolean,
    /** If set to true the row can load children */
    hasChildren?: boolean,
    /** If set to true the row child are open */
    expanded?: boolean,
    /** If set to true the childs will be loaded */
    isLoading?: boolean,
    /** The depth of the element in the row */
    depth?: number,
    /** @ignore */
    onToggleChange?: (rowId: string | number, checked?: boolean) => void,
    /** @ignore */
    onSelectionChange?: (rowId: string | number, checked?: boolean) => void,
};

export default class Row extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
        rowIndex: 0,
        depth: 0
    };

    isMultipleSelect = () => {
        return this.props.selectMode === 'multiple';
    };

    isSingleSelect = () => {
        return this.props.selectMode === 'single';
    };

    createCells = (cells: ChildrenArray<Element<typeof Cell>>) => {
        const {buttons} = this.props;
        const prependedCells = [];

        if (buttons && buttons.length > 0) {
            const createdItems = this.createButtonCells();

            if (createdItems) {
                prependedCells.push(...createdItems);
            }
        }

        if (!this.props.selectInFirstCell) {
            let select = this.createSelect();

            if (select) {
                prependedCells.push(
                    <Cell key={'choice'} small={true}>
                        {select}
                    </Cell>
                );
            }
        }

        const clonedCells = this.cloneCells(cells);

        clonedCells.unshift(...prependedCells);

        return clonedCells;
    };

    cloneCells = (originalCells: ChildrenArray<Element<typeof Cell>>) => {
        return React.Children.map(originalCells, (cell, index) => {
            const key = `row-${index}`;
            const {props} = cell;
            let {children} = props;

            if (0 === index) {
                children = this.modifyFirstCell(children);
            }

            return React.cloneElement(
                cell,
                {
                    ...props,
                    key,
                    children: children
                }
            );
        });
    };

    modifyFirstCell = (children: ChildrenArray<Element <*>>) => {
        let toggler = null;
        let select = null;
        let style = {};

        if (this.props.hasChildren) {
            toggler = this.createToggler();
        }

        if (this.props.depth) {
            style.paddingLeft = (this.props.depth * 20) + 'px'
        }

        if (this.props.selectInFirstCell) {
            select = <div className={tableStyles.cellSelect}>
                {this.createSelect()}
            </div>;
        }

        return <div className={tableStyles.cellContent} style={style}>
            {select}
            {toggler}
            {children}
        </div>;
    };

    createSelect = () => {
        if (this.isSingleSelect()) {
            return this.createRadioCell();
        } else if (this.isMultipleSelect()) {
            return this.createCheckboxCell();
        }
    };

    createToggler = () => {
        let icon = <Icon name={this.props.expanded ? 'su-arrow-filled-down' : 'su-arrow-filled-right'} />

        if (this.props.isLoading) {
            icon = <Loader size={10}/>
        }

        return <span onClick={this.handleToggleChange} className={tableStyles.toggleIcon}>
                {icon}
            </span>
    };

    createRadioCell = () => {
        const {id, selected, rowIndex} = this.props;
        const identifier = id || rowIndex;

        return (
            <Radio
                skin="dark"
                value={identifier}
                checked={!!selected}
                onChange={this.handleSingleSelectionChange}
            />
        );
    };

    createCheckboxCell = () => {
        const {id, selected, rowIndex} = this.props;
        const identifier = id || rowIndex;

        return (
            <Checkbox
                skin="dark"
                value={identifier}
                checked={!!selected}
                onChange={this.handleMultipleSelectionChange}
            />
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

    handleToggleChange = () => {
        const {id, rowIndex, expanded} = this.props;
        const identifier = id || rowIndex;

        if (this.props.onToggleChange && identifier) {
            this.props.onToggleChange(identifier, !expanded);
        }
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
