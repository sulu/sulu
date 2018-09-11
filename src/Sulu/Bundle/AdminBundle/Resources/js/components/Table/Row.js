// @flow
import React, {Fragment} from 'react';
import type {ChildrenArray, Element} from 'react';
import Checkbox from '../Checkbox';
import {Radio} from '../Radio';
import Icon from '../Icon/Icon';
import Loader from '../Loader/Loader';
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
    selectInFirstCell: boolean,
    selected: boolean,
    hasChildren: boolean,
    expanded: boolean,
    isLoading: boolean,
    depth?: number,
    /** @ignore */
    onExpand?: (rowId: string | number) => void,
    /** @ignore */
    onCollapse?: (rowId: string | number) => void,
    /** @ignore */
    onSelectionChange?: (rowId: string | number, checked?: boolean) => void,
};

export default class Row extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
        selectInFirstCell: false,
        hasChildren: false,
        expanded: false,
        isLoading: false,
        rowIndex: 0,
        depth: 0,
    };

    getIdentifier = (): (string | number) => {
        const {id, rowIndex} = this.props;
        return id || rowIndex;
    };

    isMultipleSelect = () => {
        return this.props.selectMode === 'multiple';
    };

    isSingleSelect = () => {
        return this.props.selectMode === 'single';
    };

    createCells = (cells: ChildrenArray<Element<typeof Cell>>) => {
        const {buttons, selectInFirstCell} = this.props;
        const prependedCells = [];

        if (buttons && buttons.length > 0) {
            const createdItems = this.createButtonCells();

            if (createdItems) {
                prependedCells.push(...createdItems);
            }
        }

        if (!selectInFirstCell) {
            const select = this.createSelect();

            if (select) {
                prependedCells.push(
                    <Cell key={'choice'} small={true}>
                        {select}
                    </Cell>
                );
            }
        }

        const clonedCells = this.cloneCells(cells);

        clonedCells.unshift(prependedCells);

        return clonedCells;
    };

    cloneCells = (originalCells: ChildrenArray<Element<typeof Cell>>) => {
        return React.Children.map(originalCells, (cell: Element<typeof Cell>, index) => {
            const key = `cell-${index}`;
            const {props} = cell;
            const firstCell = index === 0;
            const {depth} = this.props;
            let {children} = props;

            if (firstCell) {
                children = this.createFirstCell(children);
            }

            return React.cloneElement(
                cell,
                {
                    ...props,
                    key,
                    children,
                    depth: firstCell && depth ? depth : undefined,
                }
            );
        });
    };

    createFirstCell = (children: *) => {
        const {hasChildren, selectInFirstCell, onSelectionChange} = this.props;

        return (
            <Fragment>
                {selectInFirstCell && onSelectionChange &&
                    <div className={tableStyles.cellSelect}>
                        {this.createSelect()}
                    </div>
                }
                {hasChildren &&
                    this.createToggler()
                }
                {children}
            </Fragment>
        );
    };

    createSelect = () => {
        if (!this.props.onSelectionChange) {
            return null;
        }

        if (this.isSingleSelect()) {
            return this.createRadioCell();
        } else if (this.isMultipleSelect()) {
            return this.createCheckboxCell();
        }
    };

    createToggler = () => {
        const {isLoading, expanded} = this.props;

        return (
            <span
                className={tableStyles.toggleIcon}
            >
                {isLoading
                    ? <Loader size={10} />
                    : <Icon
                        name={expanded === true ? 'su-angle-down' : 'su-angle-right'}
                        onClick={expanded === false ? this.handleExpand : this.handleCollapse}
                    />
                }
            </span>
        );
    };

    createRadioCell = () => {
        const {selected} = this.props;

        return (
            <Radio
                checked={selected}
                onChange={this.handleSingleSelectionChange}
                skin="dark"
                value={this.getIdentifier()}
            />
        );
    };

    createCheckboxCell = () => {
        const {selected} = this.props;

        return (
            <Checkbox
                checked={selected}
                onChange={this.handleMultipleSelectionChange}
                skin="dark"
                value={this.getIdentifier()}
            />
        );
    };

    createButtonCells = () => {
        const {buttons, rowIndex} = this.props;

        if (!buttons) {
            return null;
        }

        return buttons.map((button: ButtonConfig, index) => {
            const key = `control-${rowIndex}-${index}`;
            const handleClick = button.onClick;

            return (
                <ButtonCell
                    icon={button.icon}
                    key={key}
                    onClick={handleClick}
                    rowId={this.getIdentifier()}
                />
            );
        });
    };

    handleCollapse = () => {
        const {onCollapse} = this.props;
        if (onCollapse) {
            onCollapse(this.getIdentifier());
        }
    };

    handleExpand = () => {
        const {onExpand} = this.props;
        if (onExpand) {
            onExpand(this.getIdentifier());
        }
    };

    handleSingleSelectionChange = (rowId?: string | number) => {
        const {onSelectionChange} = this.props;
        if (onSelectionChange && rowId) {
            onSelectionChange(rowId);
        }
    };

    handleMultipleSelectionChange = (checked: boolean, rowId?: string | number) => {
        const {onSelectionChange} = this.props;
        if (onSelectionChange && rowId) {
            onSelectionChange(rowId, checked);
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
