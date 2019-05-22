// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import type {BaseItemProps} from './types';
import baseItemStyles from './baseItem.scss';

type Props = {|
    ...BaseItemProps,
    children: ?Node,
    className: string,
|};

export default class BaseItem extends React.PureComponent<Props> {
    render() {
        const {
            colSpan,
            children,
            className,
            spaceAfter,
            spaceBefore,
        } = this.props;

        const baseItemClass = classNames(
            className,
            baseItemStyles.colSpan,
            baseItemStyles['colSpan-' + colSpan],
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
