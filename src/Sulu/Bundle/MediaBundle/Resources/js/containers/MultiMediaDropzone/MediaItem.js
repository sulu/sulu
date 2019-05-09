// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {CircularProgressbar} from 'sulu-admin-bundle/components';
import MediaUploadStore from '../../stores/MediaUploadStore';
import mediaItemStyles from './mediaItem.scss';

const THUMBNAIL_SIZE = 'sulu-100x100';

type Props = {
    store: MediaUploadStore,
};

@observer
class MediaItem extends React.Component<Props> {
    render() {
        const {store} = this.props;

        return (
            <div className={mediaItemStyles.mediaItem}>
                {store.uploading &&
                    <div className={mediaItemStyles.progressbarContainer}>
                        <CircularProgressbar
                            hidePercentageText={true}
                            percentage={store.progress}
                            size={50}
                        />
                    </div>
                }
                <img src={store.getThumbnail(THUMBNAIL_SIZE)} />
            </div>
        );
    }
}

export default MediaItem;
