// @flow
import React from 'react';
import type {Element} from 'react';
import classNames from 'classnames';
import type {BaseItemProps} from './types';
import baseItemStyles from './baseItem.scss';

type Props = BaseItemProps & {
    className: string,
    children: ?Element<*>,
};

export default class BaseItem extends React.PureComponent<Props> {
    render() {
        const {
            size,
            children,
            className,
            spaceAfter,
            spaceBefore,
        } = this.props;

        const baseItemClass = classNames(
            className,
            baseItemStyles.size,
            baseItemStyles['size-' + size],
            baseItemStyles['space-before-' + spaceBefore],
            baseItemStyles['space-after-' + spaceAfter]
        );

        return (
            <div
                className={baseItemClass}
            >
                {children}
            </div>
        );
    }
}
