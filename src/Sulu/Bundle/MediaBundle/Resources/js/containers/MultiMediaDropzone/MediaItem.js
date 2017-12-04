// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import {CircularProgressbar} from 'sulu-admin-bundle/components';
import MediaUploadStore from '../../stores/MediaUploadStore';
import mediaItemStyles from './mediaItem.scss';

const THUMBNAIL_SIZE = 'sulu-100x100-inset';

type Props = {
    store: MediaUploadStore,
};

@observer
export default class MediaItem extends React.PureComponent<Props> {
    @computed get thumbnail(): ?string {
        const {store} = this.props;
        const {thumbnails} = store.data;

        if (!thumbnails || !thumbnails[THUMBNAIL_SIZE]) {
            return null;
        }

        return `${window.location.origin}${thumbnails[THUMBNAIL_SIZE]}`;
    }

    render() {
        const {store} = this.props;

        return (
            <div className={mediaItemStyles.mediaItem}>
                {store.uploading &&
                    <div className={mediaItemStyles.progressbarContainer}>
                        <CircularProgressbar
                            size={50}
                            percentage={store.progress}
                            hidePercentageText={true}
                        />
                    </div>
                }
                <img src={this.thumbnail} />
            </div>
        );
    }
}
