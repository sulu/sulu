// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
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
        return 'multiple' === this.props.selectMode;
    };

    isSingleSelect = () => {
        return 'single' === this.props.selectMode;
    };

    createHeader = (originalCells: ChildrenArray<Element<typeof HeaderCell>>) => {
        const {buttons} = this.props;
        const prependCells = [];
        const cells = this.createHeaderCells(originalCells);

        if (buttons && 0 < buttons.length) {
            const buttonCells = this.createHeaderButtonCells();

            if (buttonCells) {
                prependCells.push(...buttonCells);
            }
        }

        if (this.isMultipleSelect()) {
            prependCells.push(this.createCheckboxCell());
        } else if (this.isSingleSelect()) {
            prependCells.push(this.createEmptyCell());
        }

        cells.unshift(...prependCells);

        return cells;
    };

    createHeaderCells = (headerCells: ChildrenArray<Element<typeof HeaderCell>>) => {
        return React.Children.map(headerCells, (headerCell, index) => {
            const key = `header-${index}`;

            return React.cloneElement(
                headerCell,
                {
                    ...headerCell.props,
                    key,
                }
            );
        });
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
                    key={key}
                    className={tableStyles.headerButtonCell}
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
                    skin="light"
                    checked={!!this.props.allSelected}
                    onChange={this.handleAllSelectionChange}
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
        if (this.props.onAllSelectionChange) {
            this.props.onAllSelectionChange(checked);
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
