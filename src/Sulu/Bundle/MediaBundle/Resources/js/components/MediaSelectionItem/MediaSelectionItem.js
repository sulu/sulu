// @flow
import React from 'react';
import MimeTypeIndicator from '../MimeTypeIndicator';
import mediaSelectionItemStyle from './mediaSelectionItem.scss';

type Props = {|
    mimeType: string,
    children: string,
    thumbnail?: string,
    thumbnailSize: number,
    thumbnailMargin: number,
|};

export default class MediaSelectionItem extends React.PureComponent<Props> {
    static defaultProps = {
        thumbnailMargin: 20,
        thumbnailSize: 25,
    };

    render() {
        const {
            mimeType,
            children,
            thumbnail,
            thumbnailSize,
            thumbnailMargin,
        } = this.props;

        const thumbnailStyles = {
            width: thumbnailSize,
            height: thumbnailSize,
            marginRight: thumbnailMargin,
        };

        return (
            <div className={mediaSelectionItemStyle.mediaSelectionItem}>
                <div style={thumbnailStyles}>
                    {thumbnail
                        ? <img
                            alt={thumbnail}
                            className={mediaSelectionItemStyle.thumbnailImage}
                            src={thumbnail}
                        />
                        : <MimeTypeIndicator
                            height={thumbnailSize}
                            iconSize={thumbnailSize / 1.6}
                            mimeType={mimeType}
                        />
                    }
                </div>
                {children}
            </div>
        );
    }
}
