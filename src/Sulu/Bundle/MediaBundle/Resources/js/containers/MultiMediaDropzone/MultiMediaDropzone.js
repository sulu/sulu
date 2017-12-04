// @flow
import React from 'react';
import type {ChildrenArray} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Dropzone from 'react-dropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';
import MediaItem from './MediaItem';
import DropzoneOverlay from './DropzoneOverlay';

type Props = {
    children: ChildrenArray<*>,
    disabled?: boolean,
    onUploaded: (files: Array<File>) => void,
};

@observer
export default class MultiMediaDropzone extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };

    @observable overlayOpen: boolean;

    @observable mediaUploadStores: Array<MediaUploadStore> = [];

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action closeOverlay() {
        this.overlayOpen = false;
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
        this.openOverlay();
    };

    handleDragLeave = () => {
        this.closeOverlay();
    };

    handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleDrop = (files: Array<File>) => {
        const uploadPromises = [];

        files.forEach((file) => {
            const mediaUploadStore = new MediaUploadStore();
            const uploadPromise = mediaUploadStore.create(file);

            uploadPromises.push(uploadPromise);
            this.mediaUploadStores.push(mediaUploadStore);
        });

        Promise.all(uploadPromises).then(this.destroyMediaUploadStores);
    };

    render() {
        const {
            children,
            disabled,
        } = this.props;

        return (
            <Dropzone
                style={{}} // to disable default style
                disabled={disabled}
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
