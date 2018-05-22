// @flow
import React from 'react';
import MimeTypeIndicator from '../../components/MimeTypeIndicator';
import mediaSelectionItemStyle from './mediaSelectionItem.scss';

type Props = {
    children: string,
    mimeType: string,
    thumbnail: ?string,
};

export default class MediaSelectionItem extends React.PureComponent<Props> {
    render() {
        const {
            mimeType,
            children,
            thumbnail,
        } = this.props;

        return (
            <div className={mediaSelectionItemStyle.mediaSelectionItem}>
                <div className={mediaSelectionItemStyle.thumbnail}>
                    {thumbnail
                        ? <img alt={thumbnail} src={thumbnail} />
                        : <MimeTypeIndicator
                            height={25}
                            iconSize={16}
                            mimeType={mimeType}
                        />
                    }
                </div>
                {children}
            </div>
        );
    }
}
