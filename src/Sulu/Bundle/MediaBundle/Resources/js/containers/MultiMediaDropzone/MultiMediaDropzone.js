// @flow
import React from 'react';
import type {ElementRef, Node} from 'react';
import {observer, Observer} from 'mobx-react';
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Dropzone from 'react-dropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';
import MediaItem from './MediaItem';
import DropzoneOverlay from './DropzoneOverlay';
import dropzoneStyles from './dropzone.scss';

type Props = {
    children: Node,
    collectionId: ?string | number,
    locale: IObservableValue<string>,
    onClose: () => void,
    onOpen: () => void,
    onUpload: (media: Array<Object>) => void,
    open: boolean,
};

@observer
class MultiMediaDropzone extends React.Component<Props> {
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
                noClick={true}
                onDragEnter={this.handleDragEnter}
                onDrop={this.handleDrop}
                ref={this.setDropzoneRef}
                style={{}} // to disable default style
            >
                {({getInputProps, getRootProps}) => (
                    <Observer>
                        {() => (
                            <div {...getRootProps({className: dropzoneStyles.dropzone})}>
                                {children}
                                <input {...getInputProps()} />
                                <DropzoneOverlay
                                    onClick={this.handleOverlayClick}
                                    onClose={this.handleOverlayClose}
                                    onDragLeave={this.handleDragLeave}
                                    open={open}
                                >
                                    {this.createMediaItems()}
                                </DropzoneOverlay>
                            </div>
                        )}
                    </Observer>
                )}
            </Dropzone>
        );
    }
}

export default MultiMediaDropzone;
