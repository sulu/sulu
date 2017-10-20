// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import HeaderCell from './HeaderCell';
import type {ButtonConfig, SelectMode} from './types';
import treeStyles from './tree.scss';

type Props = {
    children?: Node,
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

@observer
export default class Header extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
        allSelected: false,
    };

    createHeader = (node: Node) => {
        const {buttons} = this.props;
        const prependCells = [];
        const cells = [this.createHeaderCell(node)];

        if (buttons && buttons.length > 0) {
            const buttonCells = this.createHeaderButtonCells();

            if (buttonCells) {
                prependCells.push(...buttonCells);
            }
        }

        cells.unshift(...prependCells);

        return cells;
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

    createHeaderCell = (node: Node) => {
        const key = 'header-checkbox';

        if (this.props.selectMode === 'multiple') {
            return (
                <HeaderCell key={key}>
                    <Checkbox
                        skin="light"
                        checked={!!this.props.allSelected}
                        onChange={this.handleAllSelectionChange}
                    >{node}</Checkbox>
                </HeaderCell>
            );
        }

        return (
            <HeaderCell key={key}>
                {node}
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
            <div className={treeStyles.header}>
                {cells}
            </div>
        );
    }
}
