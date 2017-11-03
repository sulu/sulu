// @flow
import {observer} from 'mobx-react';
import {observable} from 'mobx';
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Column from './Column';
import Item from './Item';
import type {ButtonConfig, ToolbarItemConfig} from './types';
import columnListStyles from './columnList.scss';

type Props = {
    children: ChildrenArray<Element<typeof Column>>,
    buttons?: Array<ButtonConfig>,
    toolbarItems: Array<ToolbarItemConfig>,
    onItemClick: (id: string | number) => void,
};

@observer
export default class ColumnList extends React.Component<Props> {
    static Column = Column;

    static Item = Item;

    @observable activeColumnIndex: number = 0;

    handleOnActive = (index: number) => {
        this.activeColumnIndex = index;
    };

    cloneColumns = (originalColumns: ChildrenArray<Element<typeof Column>>) => {
        const {onItemClick, toolbarItems} = this.props;

        return React.Children.map(originalColumns, (column, index) => {
            return React.cloneElement(
                column,
                {
                    index: index,
                    buttons: this.props.buttons,
                    active: this.activeColumnIndex === index,
                    onActive: this.handleOnActive,
                    onItemClick: onItemClick,
                    toolbarItems: toolbarItems,
                }
            );
        });
    };

    render() {
        const {children} = this.props;

        return (
            <div className={columnListStyles.columnListContainer}>
                <div className={columnListStyles.columnList}>
                    {this.cloneColumns(children)}
                </div>
            </div>
        );
    }
}
