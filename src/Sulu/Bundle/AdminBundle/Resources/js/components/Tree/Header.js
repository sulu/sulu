// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Checkbox from '../Checkbox';
import Icon from '../Icon';
import HeaderCell from './HeaderCell';
import type {ButtonConfig, SelectMode} from './types';
import treeStyles from './tree.scss';

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
        return this.props.selectMode === 'multiple';
    };

    isSingleSelect = () => {
        return this.props.selectMode === 'single';
    };

    createHeader = (originalCells: ChildrenArray<Element<typeof HeaderCell>>) => {
        const {buttons} = this.props;
        const prependCells = [];
        const cells = this.createHeaderCells(originalCells);

        if (buttons && buttons.length > 0) {
            const buttonCells = this.createHeaderButtonCells();

            if (buttonCells) {
                prependCells.push(...buttonCells);
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

            if (0 === index && this.isMultipleSelect()) {
                children = <div className={treeStyles.cellContent}>
                    <div className={treeStyles.cellChoice}>
                        <Checkbox
                            skin="light"
                            checked={!!this.props.allSelected}
                            onChange={this.handleAllSelectionChange}
                        />
                    </div>
                    {children}
                </div>
            }

            return React.cloneElement(
                headerCell,
                {
                    ...props,
                    key,
                    children: children
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
                    className={treeStyles.headerButtonCell}
                >
                    <Icon name={button.icon} />
                </HeaderCell>
            );
        });
    };

    createCheckboxCell = (cell) => {
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
            <thead className={treeStyles.header}>
                <tr>
                    {cells}
                </tr>
            </thead>
        );
    }
}
