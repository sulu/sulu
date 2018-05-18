// @flow
import React from 'react';
import type {Element} from 'react';
import type {BaseItemProps, Size} from './types';

type Props = BaseItemProps & {
    className: string,
    children: ?Element<*>,
};

export default class BaseItem extends React.PureComponent<Props> {
    static getWidthFromSize(size: Size) {
        return size * 100 / 12;
    }

    render() {
        const {
            size,
            children,
            className,
            spaceAfter,
            spaceBefore,
        } = this.props;
        const sizeStyle = {};

        sizeStyle.width = `${BaseItem.getWidthFromSize(size)}%`;
        sizeStyle.marginLeft = `${BaseItem.getWidthFromSize(spaceBefore)}%`;
        sizeStyle.marginRight = `${BaseItem.getWidthFromSize(spaceAfter)}%`;

        return (
            <div
                className={className}
                style={sizeStyle}
            >
                {children}
            </div>
        );
    }
}
