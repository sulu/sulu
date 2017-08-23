// @flow
import type {Element, ChildrenArray} from 'react';
import React from 'react';
import Icon from '../Icon';
import Checkbox from '../Checkbox';
import HeaderCell from './HeaderCell';
import Row from './Row';
import type {ControlItems, ControlConfig, SelectMode} from './types';
import tableStyles from './table.scss';

type Props = {
    children: ChildrenArray<Element<typeof Row>>,
    /** 
     * List of buttons to apply action handlers to every row (e.g. edit row).
     * The header will display the icons.
     */
    controls?: ControlItems,
    /** Can be set to "single" or "multiple". Defaults is "none". */
    selectMode?: SelectMode,
    /**
     * @ignore
     * Called when the "select all" checkbox was clicked. The checkbox only shows up on "selectMode: 'multiple'"
     * Returns the checked state.
     */
    onAllSelectionChange?: (checked: boolean) => void,
    /** If true the "select all" checkbox is checked. */
    allSelected?: boolean,
};

export default class Header extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
        allSelected: false,
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
            throw new Error(`Expected one row inside the header but found ${rows.length}`);
        }

        const {controls} = this.props;
        const row = rows[0];
        const prependCells = [];
        const cells = this.createHeaderCells(row.props.children);

        if (controls && controls.length > 0) {
            const controlCells = this.createHeaderControlCells();

            prependCells.push(...controlCells);
        }

        if (this.isMultipleSelect()) {
            prependCells.push(this.createCheckboxCell());
        } else if (this.isSingleSelect()) {
            prependCells.push(this.createEmptyCell());
        }

        cells.unshift(...prependCells);

        return React.cloneElement(
            row,
            {},
            cells,
        );
    };

    createHeaderCells = (headerCells: ChildrenArray<Element<typeof HeaderCell>>) => {
        return React.Children.map(headerCells, (headerCell: Element<typeof HeaderCell>, index) => {
            const key = `header-${index}`;

            return React.cloneElement(
                headerCell,
                {
                    key,
                    ...headerCell.props,
                },
            );
        });
    };

    createHeaderControlCells = () => {
        const {controls} = this.props;

        if (!controls) {
            return null;
        }

        return controls.map((controlItem: ControlConfig, index: number) => {
            const key = `header-control-${index}`;

            return (
                <HeaderCell
                    key={key}
                    isControl={true}>
                    <Icon name={controlItem.icon} />
                </HeaderCell>
            );
        });
    };

    createCheckboxCell = () => {
        const key = 'header-checkbox';

        return (
            <HeaderCell key={key}>
                <Checkbox
                    skin="light"
                    checked={!!this.props.allSelected}
                    onChange={this.handleAllSelectionChange} />
            </HeaderCell>
        );
    };

    createEmptyCell = () => {
        const key = 'header-empty';

        return (
            <HeaderCell key={key} />
        );
    };

    handleAllSelectionChange = (checked: boolean) => {
        if (this.props.onAllSelectionChange) {
            this.props.onAllSelectionChange(checked);
        }
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
