// @flow
import React from 'react';
import type {BaseItemProps} from './types';
import BaseItem from './BaseItem';
import itemStyles from './item.scss';

type Props = BaseItemProps;

export default class Item extends React.PureComponent<Props> {
    static defaultProps = {
        size: 12,
        spaceBefore: 0,
        spaceAfter: 0,
    };

    render() {
        const {
            children,
            ...others
        } = this.props;

        return (
            <BaseItem {...others} className={itemStyles.item}>
                {children}
            </BaseItem>
        );
    }
}
