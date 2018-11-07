// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {SortableContainer, SortableElement} from 'react-sortable-hoc';
import classNames from 'classnames';
import type {Button} from './types';
import Header from './Header';
import Item from './Item';
import multiItemSelectionStyles from './multiItemSelection.scss';

type Props<T> = {|
    disabled?: boolean,
    children?: ChildrenArray<Element<typeof Item>>,
    label?: string,
    onItemRemove?: (itemid: T) => void,
    onItemsSorted?: (oldIndex: number, newIndex: number) => void,
    leftButton?: Button,
    rightButton?: Button,
    loading: boolean,
    sortable: boolean,
|};

export default class MultiItemSelection<T> extends React.PureComponent<Props<T>> {
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

    handleItemRemove = (itemId: T) => {
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
            disabled,
            children,
            label,
            leftButton,
            loading,
            rightButton,
        } = this.props;
        const emptyList = !React.Children.count(children);
        const List = this.createList();

        const multiItemSelectionClass = classNames(
            multiItemSelectionStyles.multiItemSelectionClass,
            {
                [multiItemSelectionStyles.disabled]: disabled,
            }
        );

        return (
            <div className={multiItemSelectionClass}>
                <Header
                    emptyList={emptyList}
                    label={label}
                    leftButton={leftButton ? {disabled, ...leftButton} : undefined}
                    loading={loading}
                    rightButton={rightButton ? {disabled, ...rightButton} : undefined}
                />
                <List
                    axis="y"
                    helperClass={multiItemSelectionStyles.dragging}
                    lockAxis="y"
                    onSortEnd={this.handleItemsSorted}
                    useDragHandle={true}
                >
                    {children}
                </List>
            </div>
        );
    }
}
