// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Dropzone from 'react-dropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';
import MediaItem from './MediaItem';
import DropzoneOverlay from './DropzoneOverlay';
import dropzoneStyles from './dropzone.scss';

type Props = {
    children: any,
    locale: IObservableValue<string>,
    collectionId: ?string | number,
    onClose: () => void,
    onOpen: () => void,
    onUpload: (media: Array<Object>) => void,
    open: boolean,
};

@observer
export default class MultiMediaDropzone extends React.Component<Props> {
    dropzoneRef: ElementRef<Dropzone>;

    @observable mediaUploadStores: Array<MediaUploadStore> = [];

    setDropzoneRef = (ref: Dropzone) => {
        this.dropzoneRef = ref;
    };

    @action addMediaUploadStore(mediaUploadStore: MediaUploadStore) {
        this.mediaUploadStores.push(mediaUploadStore);
    }

    @action destroyMediaUploadStores() {
        this.mediaUploadStores = [];
    }

    createMediaItems(): Array<*> {
        return this.mediaUploadStores.map((mediaUploadStore, index) => (
            <MediaItem key={index} store={mediaUploadStore} />
        ));
    }

    handleDragEnter = () => {
        const {collectionId, onOpen} = this.props;

        if (collectionId) {
            onOpen();
        }
    };

    handleDragLeave = () => {
        this.props.onClose();
    };

    handleOverlayClose = () => {
        this.props.onClose();
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
            const mediaUploadStore = new MediaUploadStore(undefined, locale);
            const uploadPromise = mediaUploadStore.create(collectionId, file);

            uploadPromises.push(uploadPromise);
            this.addMediaUploadStore(mediaUploadStore);
        });

        return Promise.all(uploadPromises).then((...media) => {
            this.props.onUpload(...media);

            setTimeout(() => {
                this.props.onClose();
                this.destroyMediaUploadStores();
            }, 1000);
        });
    };

    handleOverlayClick = () => {
        this.dropzoneRef.open();
    };

    render() {
        const {children, open} = this.props;

        return (
            <Dropzone
                className={dropzoneStyles.dropzone}
                disableClick={true}
                onDragEnter={this.handleDragEnter}
                onDragLeave={this.handleDragLeave}
                onDrop={this.handleDrop}
                ref={this.setDropzoneRef}
                style={{}} // to disable default style
            >
                <DropzoneOverlay
                    onClick={this.handleOverlayClick}
                    onClose={this.handleOverlayClose}
                    open={open}
                >
                    {this.createMediaItems()}
                </DropzoneOverlay>
                {children}
            </Dropzone>
        );
    }
}
