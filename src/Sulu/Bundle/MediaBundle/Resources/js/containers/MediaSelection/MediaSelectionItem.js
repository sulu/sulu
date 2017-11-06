// @flow
import React from 'react';
import mediaSelectionItemStyle from './mediaSelectionItem.scss';

type Props = {
    children: string,
    thumbnail: string,
};

export default class MediaSelectionItem extends React.PureComponent<Props> {
    render() {
        const {
            children,
            thumbnail,
        } = this.props;

        return (
            <div className={mediaSelectionItemStyle.mediaSelectionItem}>
                <div className={mediaSelectionItemStyle.thumbnail}>
                    <img alt={thumbnail} src={thumbnail} />
                </div>
                {children}
            </div>
        );
    }
}
