// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Icon from '../Icon';
import Item from './Item';
import breadcrumbStyles from './breadcrumb.scss';

const ARROW_RIGHT = 'chevron-right';

type Props = {
    children: ChildrenArray<Element<typeof Item>>,
    onItemClick: (value?: string | number) => void,
};

export default class Breadcrumb extends React.PureComponent<Props> {
    static Item = Item;

    createItems(originalItems: ChildrenArray<Element<typeof Item>>) {
        return React.Children.map(originalItems, (item, index) => {
            const lastItem = (index === React.Children.count(originalItems) - 1);

            return (
                <li>
                    {React.cloneElement(item, {
                        value: item.props.value,
                        onClick: (!lastItem) ? this.handleItemClick : undefined,
                    })}
                    {!lastItem &&
                        <Icon name={ARROW_RIGHT} className={breadcrumbStyles.arrow} />
                    }
                </li>
            );
        });
    }

    handleItemClick = (value?: string | number) => {
        this.props.onItemClick(value);
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
