// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {SortableContainer, SortableElement, SortableHandle, arrayMove} from 'react-sortable-hoc';
import type {Button} from './types';
import Header from './Header';
import Item from './Item';
import itemSelectionStyles from './itemSelection.scss';

type Props = {
    children: ChildrenArray<*>,
    /** The text inside the header bar of the `ItemSelection` */
    label?: string,
    /** Called when the remove button is clicked on an item */
    onItemRemove?: (itemid: string | number) => void,
    /** Called after a drag and drop action was executed */
    onItemsSorted?: (oldIndex: number, newIndex: number) => void,
    /** The config of the button placed left side of the header */
    leftButton?: Button,
    /** The config of the button placed right side of the header */
    rightButton?: Button,
};

export default class ItemSelection extends React.PureComponent<Props> {
    static Item = Item;

    static arrayMove = arrayMove;

    createItem(originalItem: Element<typeof Item>, index: number) {
        return (
            <li className={itemSelectionStyles.listElement}>
                {
                    React.cloneElement(
                        originalItem,
                        {
                            ...originalItem.props,
                            onRemove: this.handleItemRemove,
                            createDragHandle: this.createDragHandle,
                        },
                    )
                }
            </li>
        );
    }

    createSortableList() {
        const SortableItem = this.createSortableItem();

        return SortableContainer(({children}) => (
            <ul className={itemSelectionStyles.list}>
                {React.Children.map(children, (item, index) =>
                    <SortableItem index={index} displayIndex={index}>
                        {item}
                    </SortableItem>
                )}
            </ul>
        ));
    }

    createSortableItem() {
        return SortableElement(({children, displayIndex}) => {
            return this.createItem(children, displayIndex);
        });
    }

    createDragHandle() {
        return SortableHandle(({children, className}) => (
            <span className={className}>{children}</span>
        ));
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
            children,
            leftButton,
            rightButton,
        } = this.props;
        const emptyList = !React.Children.count(children);
        const SortableList = this.createSortableList();

        return (
            <div>
                <Header
                    label={label}
                    emptyList={emptyList}
                    leftButton={leftButton}
                    rightButton={rightButton}
                />
                <SortableList
                    axis="y"
                    lockAxis="y"
                    useDragHandle={true}
                    onSortEnd={this.handleItemsSorted}
                    helperClass={itemSelectionStyles.duringDrag}
                >
                    {children}
                </SortableList>                
            </div>
        );
    }
}
