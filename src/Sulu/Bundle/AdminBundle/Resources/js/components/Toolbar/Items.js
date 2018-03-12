// @flow
import type {ChildrenArray} from 'react';
import React from 'react';
import classNames from 'classnames';
import type {Item, Skin} from './types';
import itemsStyles from './items.scss';

type Props = {
    children: ChildrenArray<Item>,
    skin?: Skin,
};

export default class Items extends React.PureComponent<Props> {
    static defaultProps = {
        skin: 'light',
    };

    render() {
        const {
            skin,
            children,
        } = this.props;

        const itemsClass = classNames(
            itemsStyles.items,
            itemsStyles[skin]
        );

        return (
            <ul className={itemsClass}>
                {children && React.Children.map(children, (item, index) => {
                    return (
                        <li key={index}>
                            {React.cloneElement(item, {
                                ...item.props,
                                skin: skin,
                            })}
                        </li>
                    );
                })}
            </ul>
        );
    }
}
