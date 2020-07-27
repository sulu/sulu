// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {SortableContainer, SortableElement} from 'react-sortable-hoc';
import classNames from 'classnames';
import type {Button} from './types';
import Header from './Header';
import Item from './Item';
import multiItemSelectionStyles from './multiItemSelection.scss';

type Props<T, U, V, W> = {|
    children?: ChildrenArray<Element<typeof Item>>,
    disabled: boolean,
    label?: string,
    leftButton?: Button<U>,
    loading: boolean,
    onItemClick?: (itemId: T, value: ?W) => void,
    onItemEdit?: (itemId: T) => void,
    onItemRemove?: (itemId: T) => void,
    onItemsSorted?: (oldIndex: number, newIndex: number) => void,
    rightButton?: Button<V>,
    sortable: boolean,
|};

type ItemWrapperProps = {
    children: Element<typeof Item>,
    isDisabled: boolean,
};

// Cannot use `disabled`, because that doesn't get passed down by SortableElement hoc
const ItemWrapper = ({children, isDisabled: disabled}: ItemWrapperProps) => {
    const listElementClass = classNames(
        multiItemSelectionStyles.listElement,
        {
            [multiItemSelectionStyles.disabled]: disabled,
        }
    );

    return (
        <li className={listElementClass}>
            {children}
        </li>
    );
};

const SortableItemWrapper = SortableElement(ItemWrapper);

const ListWrapper = ({children}: Object) => (
    <ul className={multiItemSelectionStyles.list}>
        {children}
    </ul>
);

const SortableListWrapper = SortableContainer(ListWrapper);

class MultiItemSelection<T, U: string | number, V: string | number, W> extends React.PureComponent<Props<T, U, V, W>> {
    static defaultProps = {
        disabled: false,
        loading: false,
        sortable: true,
    };

    static Item = Item;

    handleItemEdit = (itemId: T) => {
        const {onItemEdit} = this.props;
        if (onItemEdit) {
            onItemEdit(itemId);
        }
    };

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
            onItemClick,
            onItemEdit,
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
                    disabled={disabled}
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
                        <ItemWrapperComponent index={index} isDisabled={disabled}>
                            {
                                React.cloneElement(
                                    item,
                                    {
                                        ...item.props,
                                        onClick: onItemClick ? onItemClick : item.props.onClick,
                                        onEdit: onItemEdit ? this.handleItemEdit : item.props.onEdit,
                                        onRemove: onItemRemove ? this.handleItemRemove : item.props.onRemove,
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

export default MultiItemSelection;
