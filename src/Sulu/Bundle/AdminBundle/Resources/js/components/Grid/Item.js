// @flow
import React from 'react';
import type {Element} from 'react';
import classNames from 'classnames';
import type {BaseItemProps} from './types';
import BaseItem from './BaseItem';
import itemStyles from './item.scss';

type Props = BaseItemProps & {
    children?: Element<*>,
    className?: string,
};

export default class Item extends React.PureComponent<Props> {
    static defaultProps = {
        size: 12,
        spaceAfter: 0,
        spaceBefore: 0,
    };

    render() {
        const {
            children,
            className,
            ...others
        } = this.props;

        const itemClass = classNames([
            itemStyles.item,
            className,
        ]);

        return (
            <BaseItem {...others} className={itemClass}>
                {children}
            </BaseItem>
        );
    }
}
