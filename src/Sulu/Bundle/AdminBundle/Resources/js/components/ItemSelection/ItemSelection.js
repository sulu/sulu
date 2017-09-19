// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
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
    onItemMove?: (itemIds: Array<string | number>) => void,
    /** The config of the button placed left side of the header */
    leftButton?: Button,
    /** The config of the button placed right side of the header */
    rightButton?: Button,
};

export default class ItemSelection extends React.PureComponent<Props> {
    static Item = Item;

    createItems(originalItems: ChildrenArray<Element<typeof Item>>) {
        return React.Children.map(originalItems, (item) => (
            <li className={itemSelectionStyles.listElement}>
                {
                    React.cloneElement(
                        item,
                        {
                            ...item.props,
                            onRemove: this.handleItemRemove,
                        },
                    )
                }
            </li>
        ));
    }

    handleItemRemove = (itemId: string | number) => {
        if (this.props.onItemRemove) {
            this.props.onItemRemove(itemId);
        }
    };

    render() {
        const {
            label,
            children,
            leftButton,
            rightButton,
        } = this.props;
        const items = this.createItems(children);

        return (
            <div>
                <Header
                    label={label}
                    leftButton={leftButton}
                    rightButton={rightButton}
                />
                {items &&
                    <ul className={itemSelectionStyles.list}>
                        {items}
                    </ul>
                }
            </div>
        );
    }
}
