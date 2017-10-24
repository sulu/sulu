// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Item from './Item';
import Toolbar from './Toolbar';
import type {ButtonConfig, ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    index: number,
    children: ChildrenArray<Element<typeof Item>>,
    buttons?: Array<ButtonConfig>,
    active: boolean,
    onActive: (index: number) => void,
    onItemClick: (id: string | number) => void,
    toolbarItemConfigs: Array<ToolbarItemConfig>,
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
        if (!this.props.onActive) {
            return;
        }

        this.props.onActive(this.props.index);
    };

    render() {
        const {children, active, index, toolbarItemConfigs} = this.props;

        const columnContainerClass = classNames(
            columnListStyles.columnContainer,
            {
                [columnListStyles.isActive]: active,
            }
        );

        return (
            <div onMouseEnter={this.handleMouseEnter} className={columnContainerClass}>
                <Toolbar active={active} index={index} toolbarItemConfigs={toolbarItemConfigs} />
                <div className={columnListStyles.column}>
                    {this.cloneItems(children)}
                </div>
            </div>
        );
    }
}

