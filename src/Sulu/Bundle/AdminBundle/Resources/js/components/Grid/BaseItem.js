// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import type {BaseItemProps} from './types';
import baseItemStyles from './baseItem.scss';

type Props = BaseItemProps & {
    className: string,
    children: ?Node,
};

export default class BaseItem extends React.PureComponent<Props> {
    render() {
        const {
            colspan,
            children,
            className,
            spaceAfter,
            spaceBefore,
        } = this.props;

        const baseItemClass = classNames(
            className,
            baseItemStyles.colspan,
            baseItemStyles['colspan-' + colspan],
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
