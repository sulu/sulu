// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ItemButtonConfig, ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    index?: number,
    children?: ChildrenArray<Element<typeof Item>>,
    buttons?: Array<ItemButtonConfig>,
    active: boolean,
    onActive?: (index?: number) => void,
    onItemClick?: (id: string | number) => void,
    toolbarItems: Array<ToolbarItemConfig>,
};

export default class Column extends React.Component<Props> {
    static defaultProps = {
        active: false,
        toolbarItems: [],
    };

    cloneItems = (originalItems?: ChildrenArray<Element<typeof Item>>) => {
        if (!originalItems) {
            return null;
        }

        const {buttons, onItemClick} = this.props;

        return React.Children.map(originalItems, (column) => {
            return React.cloneElement(
                column,
                {
                    buttons: buttons,
                    onClick: onItemClick,
                }
            );
        });
    };

    handleMouseEnter = () => {
        const {index, onActive} = this.props;

        if (!onActive) {
            return;
        }

        onActive(index);
    };

    render() {
        const {children, active, index, toolbarItems} = this.props;

        const columnContainerClass = classNames(
            columnListStyles.columnContainer,
            {
                [columnListStyles.active]: active,
            }
        );

        return (
            <div onMouseEnter={this.handleMouseEnter} className={columnContainerClass}>
                <Toolbar active={active} columnIndex={index} toolbarItems={toolbarItems} />
                <div className={columnListStyles.column}>
                    {this.cloneItems(children)}
                </div>
            </div>
        );
    }
}

