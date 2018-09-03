// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {SortableContainer, SortableElement} from 'react-sortable-hoc';
import type {Button} from './types';
import Header from './Header';
import Item from './Item';
import multiItemSelectionStyles from './multiItemSelection.scss';

type Props = {
    children?: ChildrenArray<Element<typeof Item>>,
    label?: string,
    onItemRemove?: (itemid: string | number) => void,
    onItemsSorted?: (oldIndex: number, newIndex: number) => void,
    leftButton?: Button,
    rightButton?: Button,
    loading: boolean,
    sortable: boolean,
};

export default class MultiItemSelection extends React.PureComponent<Props> {
    static defaultProps = {
        loading: false,
        sortable: true,
    };

    static Item = Item;

    createItem() {
        const {sortable} = this.props;

        const Item = ({children}: Object) => (
            <li className={multiItemSelectionStyles.listElement}>
                {
                    React.cloneElement(
                        children,
                        {
                            ...children.props,
                            onRemove: this.props.onItemRemove && this.handleItemRemove,
                            sortable,
                        }
                    )
                }
            </li>
        );

        if (!sortable) {
            return Item;
        }

        return SortableElement(Item);
    }

    createList() {
        const SortableItem = this.createItem();
        const {sortable} = this.props;

        const container = ({children}: Object) => (
            <ul className={multiItemSelectionStyles.list}>
                {React.Children.map(children, (item, index) => (
                    <SortableItem index={index}>
                        {item}
                    </SortableItem>
                ))}
            </ul>
        );

        if (!sortable) {
            return container;
        }

        return SortableContainer(container);
    }

    handleItemRemove = (itemId: string | number) => {
        if (this.props.onItemRemove) {
            this.props.onItemRemove(itemId);
        }
    };

    handleItemsSorted = ({oldIndex, newIndex}: {oldIndex: number, newIndex: number}) => {
        const {onItemsSorted} = this.props;

        if (onItemsSorted) {
            onItemsSorted(oldIndex, newIndex);
        }
    };

    render() {
        const {
            children,
            label,
            leftButton,
            loading,
            rightButton,
        } = this.props;
        const emptyList = !React.Children.count(children);
        const List = this.createList();

        return (
            <div>
                <Header
                    label={label}
                    loading={loading}
                    emptyList={emptyList}
                    leftButton={leftButton}
                    rightButton={rightButton}
                />
                <List
                    axis="y"
                    lockAxis="y"
                    useDragHandle={true}
                    onSortEnd={this.handleItemsSorted}
                    helperClass={multiItemSelectionStyles.dragging}
                >
                    {children}
                </List>
            </div>
        );
    }
}
