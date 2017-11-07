// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ItemButtonConfig, ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    index: number,
    children: ChildrenArray<Element<typeof Item>>,
    buttons?: Array<ItemButtonConfig>,
    active: boolean,
    onActive: (index: number) => void,
    onItemClick: (id: string | number) => void,
    toolbarItems: Array<ToolbarItemConfig>,
};

export default class ColumnList extends React.Component<Props> {
    cloneItems = (originalItems: ChildrenArray<Element<typeof Item>>) => {
        return React.Children.map(originalItems, (column) => {
            return React.cloneElement(
                column,
                {
                    buttons: this.props.buttons,
                    onClick: this.props.onItemClick,
                }
            );
        });
    };

    handleMouseEnter = () => {
        const {onActive} = this.props;

        if (!onActive) {
            return;
        }

        onActive(this.props.index);
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
                <Toolbar active={active} index={index} toolbarItems={toolbarItems} />
                <div className={columnListStyles.column}>
                    {this.cloneItems(children)}
                </div>
            </div>
        );
    }
}

