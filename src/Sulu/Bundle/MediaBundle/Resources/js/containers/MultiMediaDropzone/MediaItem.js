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
export default class MediaItem extends React.Component<Props> {
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
                <img src={store.getThumbnail(THUMBNAIL_SIZE)} />
            </div>
        );
    }
}
