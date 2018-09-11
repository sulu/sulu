// @flow
import React from 'react';
import {Icon} from 'sulu-admin-bundle/components';
import MimeTypeMapper from './MimeTypeMapper';
import mimeTypeIndicatorStyles from './mimeTypeIndicator.scss';

type Props = {
    width?: number,
    height?: number,
    iconSize?: number,
    mimeType: string,
};

export default class MimeTypeIndicator extends React.PureComponent<Props> {
    static defaultProps = {
        iconSize: 52,
    };

    render() {
        const {
            width,
            height,
            iconSize,
            mimeType,
        } = this.props;
        const {
            icon,
            backgroundColor,
        } = MimeTypeMapper.get(mimeType);
        const mimeTypeStyles = {};

        mimeTypeStyles.color = '#fff';
        mimeTypeStyles.fontSize = iconSize;
        mimeTypeStyles.backgroundColor = backgroundColor;

        if (width) {
            mimeTypeStyles.width = width;
        }

        if (height) {
            mimeTypeStyles.height = height;
        }

        return (
            <div className={mimeTypeIndicatorStyles.mimeTypeIndicator} style={mimeTypeStyles}>
                <Icon name={icon} />
            </div>
        );
    }
}
