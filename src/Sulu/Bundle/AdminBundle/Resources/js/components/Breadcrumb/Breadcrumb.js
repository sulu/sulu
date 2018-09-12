// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Icon from '../Icon';
import Item from './Item';
import breadcrumbStyles from './breadcrumb.scss';

const ICON_ANGLE_RIGHT = 'su-angle-right';

type Props = {
    children: ChildrenArray<Element<typeof Item>>,
    onItemClick?: (value?: string | number) => void,
};

export default class Breadcrumb extends React.PureComponent<Props> {
    static Item = Item;

    createItems(originalItems: ChildrenArray<Element<typeof Item>>) {
        const childrenCount = React.Children.count(originalItems);

        return React.Children.map(originalItems, (item, index) => {
            const lastItem = (index === childrenCount - 1);

            return (
                <li>
                    {React.cloneElement(item, {
                        value: item.props.value,
                        onClick: (!lastItem) ? this.handleItemClick : undefined,
                    })}
                    {!lastItem &&
                        <Icon className={breadcrumbStyles.arrow} name={ICON_ANGLE_RIGHT} />
                    }
                </li>
            );
        });
    }

    handleItemClick = (value?: string | number) => {
        const {onItemClick} = this.props;

        if (onItemClick) {
            onItemClick(value);
        }
    };

    render() {
        const {
            children,
        } = this.props;
        const items = this.createItems(children);

        return (
            <ul className={breadcrumbStyles.breadcrumb}>
                {items}
            </ul>
        );
    }
}
