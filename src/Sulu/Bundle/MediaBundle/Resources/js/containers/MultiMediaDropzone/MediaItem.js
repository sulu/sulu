// @flow
import React from 'react';
import type {ChildrenArray} from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import {CircularProgressbar} from 'sulu-admin-bundle/components';
import MediaUploadStore from '../../stores/MediaUploadStore';
import mediaItemStyles from './mediaItem.scss';

type Props = {
    store: MediaUploadStore,
};

@observer
export default class MediaItem extends React.PureComponent<Props> {
    render() {
        return (
            <div className={mediaItemStyles.mediaItem}>
                <div className={mediaItemStyles.progressbarContainer}>
                    <CircularProgressbar
                        size={50}
                        percentage={60}
                        hidePercentageText={true}
                    />
                </div>
                <img src="https://source.unsplash.com/random/100x100" />
            </div>
        );
    }
}
