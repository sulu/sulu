// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Dropzone from 'react-dropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';
import MediaItem from './MediaItem';
import DropzoneOverlay from './DropzoneOverlay';

type Props = {
    children: any,
    locale: observable,
    collectionId: ?string | number,
    onUploaded: (files: Array<File>) => void,
};

@observer
export default class MultiMediaDropzone extends React.PureComponent<Props> {
    @observable overlayOpen: boolean;

    @observable mediaUploadStores: Array<MediaUploadStore> = [];

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    @action addMediaUploadStore(mediaUploadStore: MediaUploadStore) {
        this.mediaUploadStores.push(mediaUploadStore);
    }

    @action destroyMediaUploadStores() {
        this.mediaUploadStores = [];
    }

    createMediaItems() {
        return this.mediaUploadStores.map((mediaUploadStore, index) =>( 
            <MediaItem key={index} store={mediaUploadStore} />
        ));
    }

    handleDragEnter = () => {
        const {collectionId} = this.props;

        if (collectionId) {
            this.openOverlay();
        }
    };

    handleDragLeave = () => {
        this.closeOverlay();
    };

    handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleDrop = (files: Array<File>) => {
        const {
            locale,
            collectionId,
        } = this.props;
        const uploadPromises = [];

        if (!collectionId) {
            return;
        }

        files.forEach((file) => {
            const mediaUploadStore = new MediaUploadStore(locale);
            const uploadPromise = mediaUploadStore.create(collectionId, file);

            uploadPromises.push(uploadPromise);
            this.addMediaUploadStore(mediaUploadStore);
        });

        Promise.all(uploadPromises).then(() => {
            this.closeOverlay();
            this.destroyMediaUploadStores();
        });
    };

    render() {
        const {children} = this.props;

        return (
            <Dropzone
                style={{}} // to disable default style
                disableClick={true}
                onDragEnter={this.handleDragEnter}
                onDragLeave={this.handleDragLeave}
                onDrop={this.handleDrop}
            >
                <DropzoneOverlay
                    open={this.overlayOpen}
                    onClose={this.handleOverlayClose}
                >
                    {this.createMediaItems()}
                </DropzoneOverlay>
                {children}
            </Dropzone>
        );
    }
}
