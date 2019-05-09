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
    children?: ChildrenArray<Element<typeof Item>>,
    disabled: boolean,
    label?: string,
    leftButton?: Button,
    loading: boolean,
    onItemRemove?: (itemid: T) => void,
    onItemsSorted?: (oldIndex: number, newIndex: number) => void,
    rightButton?: Button,
    sortable: boolean,
|};

const ItemWrapper = ({children}: Object) => (
    <li className={multiItemSelectionStyles.listElement}>
        {children}
    </li>
);

const SortableItemWrapper = SortableElement(ItemWrapper);

const ListWrapper = ({children}: Object) => (
    <ul className={multiItemSelectionStyles.list}>
        {children}
    </ul>
);

const SortableListWrapper = SortableContainer(ListWrapper);

export default class MultiItemSelection<T> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        disabled: false,
        loading: false,
        sortable: true,
    };

    static Item = Item;

    handleItemRemove = (itemId: T) => {
        const {onItemRemove} = this.props;
        if (onItemRemove) {
            onItemRemove(itemId);
        }
    };

    handleItemsSorted = ({newIndex, oldIndex}: {newIndex: number, oldIndex: number}) => {
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
            onItemRemove,
            sortable,
        } = this.props;
        const emptyList = !React.Children.count(children);
        const ItemWrapperComponent = sortable ? SortableItemWrapper : ItemWrapper;
        const ListWrapperComponent = sortable ? SortableListWrapper : ListWrapper;

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
                <ListWrapperComponent
                    axis="y"
                    helperClass={multiItemSelectionStyles.dragging}
                    lockAxis="y"
                    onSortEnd={this.handleItemsSorted}
                    useDragHandle={true}
                >
                    {children && React.Children.map(children, (item, index) => (
                        <ItemWrapperComponent index={index}>
                            {
                                React.cloneElement(
                                    item,
                                    {
                                        ...item.props,
                                        onRemove: onItemRemove && this.handleItemRemove,
                                        sortable,
                                    }
                                )
                            }
                        </ItemWrapperComponent>
                    ))}
                </ListWrapperComponent>
            </div>
        );
    }
}
