// @flow
import type {ChildrenArray, Element} from 'react';
import React, {Fragment} from 'react';
import Checkbox from '../Checkbox';
import Icon from '../Icon';
import HeaderCell from './HeaderCell';
import type {ButtonConfig, SelectMode} from './types';
import tableStyles from './table.scss';

type Props = {
    children: ChildrenArray<Element<typeof HeaderCell>>,
    /**
     * @ignore
     * The header will just display the icons.
     */
    buttons?: Array<ButtonConfig>,
    /** @ignore */
    selectMode?: SelectMode,
    /** @ignore */
    selectInFirstCell: boolean,
    /** @ignore */
    onAllSelectionChange?: (checked: boolean) => void,
    /** If true the "select all" checkbox is checked. */
    allSelected: boolean,
};

export default class Header extends React.PureComponent<Props> {
    static defaultProps = {
        allSelected: false,
        selectInFirstCell: false,
        selectMode: 'none',
    };

    isMultipleSelect = () => {
        return this.props.selectMode === 'multiple';
    };

    isSingleSelect = () => {
        return this.props.selectMode === 'single';
    };

    createHeader = (originalCells: ChildrenArray<Element<typeof HeaderCell>>) => {
        const {buttons, selectInFirstCell} = this.props;
        const prependCells = [];
        const cells = this.createHeaderCells(originalCells);

        if (buttons && buttons.length > 0) {
            const buttonCells = this.createHeaderButtonCells();

            if (buttonCells) {
                prependCells.push(...buttonCells);
            }
        }

        if (!selectInFirstCell) {
            if (this.isMultipleSelect()) {
                prependCells.push(this.createCheckboxCell());
            } else if (this.isSingleSelect()) {
                prependCells.push(this.createEmptyCell());
            }
        }

        cells.unshift(...prependCells);

        return cells;
    };

    createHeaderCells = (headerCells: ChildrenArray<Element<typeof HeaderCell>>) => {
        return React.Children.map(headerCells, (headerCell, index) => {
            const key = `header-${index}`;
            const {props} = headerCell;
            let {children} = props;

            if (index === 0) {
                children = this.createFirstCell(children);
            }

            return React.cloneElement(
                headerCell,
                {
                    ...props,
                    key,
                    children: children,
                }
            );
        });
    };

    createFirstCell = (children: *) => {
        const {allSelected, selectInFirstCell, onAllSelectionChange} = this.props;
        if (!selectInFirstCell || !this.isMultipleSelect() || !onAllSelectionChange) {
            return children;
        }

        return (
            <Fragment>
                <span className={tableStyles.cellSelect}>
                    <Checkbox
                        checked={allSelected}
                        onChange={this.handleAllSelectionChange}
                        skin="light"
                    />
                </span>
                {children}
            </Fragment>
        );
    };

    createHeaderButtonCells = () => {
        const {buttons} = this.props;

        if (!buttons) {
            return null;
        }

        return buttons.map((button: ButtonConfig, index: number) => {
            const key = `header-button-${index}`;

            return (
                <HeaderCell
                    className={tableStyles.headerButtonCell}
                    key={key}
                >
                    <Icon name={button.icon} />
                </HeaderCell>
            );
        });
    };

    createCheckboxCell = () => {
        const key = 'header-checkbox';

        return (
            <HeaderCell key={key}>
                <Checkbox
                    checked={this.props.allSelected}
                    onChange={this.handleAllSelectionChange}
                    skin="light"
                />
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
        const {onAllSelectionChange} = this.props;
        if (onAllSelectionChange) {
            onAllSelectionChange(checked);
        }
    };

    render() {
        const {
            children,
        } = this.props;
        const cells = this.createHeader(children);

        return (
            <thead className={tableStyles.header}>
                <tr>
                    {cells}
                </tr>
            </thead>
        );
    }
}
