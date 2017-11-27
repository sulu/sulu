// @flow
import React from 'react';
import {Icon} from 'sulu-admin-bundle/components';
import MimeTypeMapper from './MimeTypeMapper';
import mimeTypeIndicatorStyles from './mimeTypeIndicator.scss';

type Props = {
    width?: number,
    height?: number,
    iconSize?: number,
    mimeType?: string,
    inverted?: boolean,
};

export default class MimeTypeIndicator extends React.PureComponent<Props> {
    static defaultProps = {
        iconSize: 52,
        inverted: false,
    };

    render() {
        const {
            width,
            height,
            iconSize,
            inverted,
            mimeType,
        } = this.props;
        const {
            icon,
            mimeTypeColor,
        } = MimeTypeMapper.get(mimeType);
        const mimeTypeStyles = {};

        mimeTypeStyles.color = '#fff';
        mimeTypeStyles.fontSize = iconSize;
        mimeTypeStyles.backgroundColor = mimeTypeColor;

        if (width) {
            mimeTypeStyles.width = width;
        }

        if (height) {
            mimeTypeStyles.height = height;
        }

        if (inverted) {
            mimeTypeStyles.color = mimeTypeColor;
            mimeTypeStyles.backgroundColor = '#fff';
        }

        return (
            <div style={mimeTypeStyles} className={mimeTypeIndicatorStyles.container}>
                <Icon name={icon} />
            </div>
        );
    }
}
