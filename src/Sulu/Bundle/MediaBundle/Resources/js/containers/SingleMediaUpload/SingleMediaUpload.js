// @flow
import React from 'react';
import {observer} from 'mobx-react';
import SingleMediaDropzone from '../../components/SingleMediaDropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';

const THUMBNAIL_SIZE = 'sulu-400x400-inset';

type Props = {|
    collectionId?: number,
    mediaUploadStore: MediaUploadStore,
    onUploadComplete?: (media: Object) => void,
    uploadText: string,
|};

@observer
export default class SingleMediaUpload extends React.Component<Props> {
    constructor(props: Props) {
        super(props);

        const {
            collectionId,
            mediaUploadStore,
        } = this.props;

        if (!mediaUploadStore.id && !collectionId) {
            throw new Error('If a new item is supposed to be uploaded a "collectionId" is required!');
        }
    }

    handleMediaDrop = (file: File) => {
        const {
            collectionId,
            mediaUploadStore,
        } = this.props;

        if (mediaUploadStore.id) {
            mediaUploadStore.update(file)
                .then(this.callUploadComplete);
        } else if(collectionId) {
            mediaUploadStore.create(collectionId, file)
                .then(this.callUploadComplete);
        }
    };

    callUploadComplete = (media: Object) => {
        const {onUploadComplete} = this.props;

        if (onUploadComplete) {
            onUploadComplete(media);
        }
    };

    render() {
        const {
            mediaUploadStore,
            uploadText,
        } = this.props;

        const {
            mimeType,
            progress,
            uploading,
        } = mediaUploadStore;

        return (
            <SingleMediaDropzone
                image={mediaUploadStore.getThumbnail(THUMBNAIL_SIZE)}
                mimeType={mimeType}
                onDrop={this.handleMediaDrop}
                progress={progress}
                uploading={uploading}
                uploadText={uploadText}
            />
        );
    }
}
