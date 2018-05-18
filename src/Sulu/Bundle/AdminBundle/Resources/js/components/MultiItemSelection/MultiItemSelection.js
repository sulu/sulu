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
    /** The text inside the header bar of the `MultiItemSelection` */
    label?: string,
    /** Called when the remove button is clicked on an item */
    onItemRemove?: (itemid: string | number) => void,
    /** Called after a drag and drop action was executed */
    onItemsSorted?: (oldIndex: number, newIndex: number) => void,
    /** The config of the button placed left side of the header */
    leftButton?: Button,
    /** The config of the button placed right side of the header */
    rightButton?: Button,
    /** Show loading indicator or not */
    loading: boolean,
};

export default class MultiItemSelection extends React.PureComponent<Props> {
    static defaultProps = {
        loading: false,
    };

    static Item = Item;

    createItem(originalItem: Element<typeof Item>) {
        return (
            <li className={multiItemSelectionStyles.listElement}>
                {
                    React.cloneElement(
                        originalItem,
                        {
                            ...originalItem.props,
                            onRemove: this.props.onItemRemove && this.handleItemRemove,
                        }
                    )
                }
            </li>
        );
    }

    createSortableList() {
        const SortableItem = this.createSortableItem();

        return SortableContainer(({children}) => (
            <ul className={multiItemSelectionStyles.list}>
                {React.Children.map(children, (item, index) => (
                    <SortableItem index={index}>
                        {item}
                    </SortableItem>
                ))}
            </ul>
        ));
    }

    createSortableItem() {
        return SortableElement(({children}) => {
            return this.createItem(children);
        });
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
            label,
            loading,
            children,
            leftButton,
            rightButton,
        } = this.props;
        const emptyList = !React.Children.count(children);
        const SortableList = this.createSortableList();

        return (
            <div>
                <Header
                    emptyList={emptyList}
                    label={label}
                    leftButton={leftButton}
                    loading={loading}
                    rightButton={rightButton}
                />
                <SortableList
                    axis="y"
                    helperClass={multiItemSelectionStyles.dragging}
                    lockAxis="y"
                    onSortEnd={this.handleItemsSorted}
                    useDragHandle={true}
                >
                    {children}
                </SortableList>
            </div>
        );
    }
}
