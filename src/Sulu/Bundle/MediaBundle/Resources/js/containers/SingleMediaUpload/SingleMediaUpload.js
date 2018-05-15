// @flow
import React from 'react';
import {observer} from 'mobx-react';
import SingleMediaDropzone from '../../components/SingleMediaDropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';

type Props = {|
    collectionId?: number,
    imageSize: string,
    mediaUploadStore: MediaUploadStore,
    onUploadComplete?: (media: Object) => void,
    skin: 'default' | 'round',
    uploadText: string,
|};

@observer
export default class SingleMediaUpload extends React.Component<Props> {
    static defaultProps = {
        imageSize: 'sulu-400x400',
        skin: 'default',
    };

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
            imageSize,
            skin,
            uploadText,
        } = this.props;

        const {
            mimeType,
            progress,
            uploading,
        } = mediaUploadStore;

        return (
            <SingleMediaDropzone
                image={mediaUploadStore.getThumbnail(imageSize)}
                mimeType={mimeType}
                onDrop={this.handleMediaDrop}
                progress={progress}
                skin={skin}
                uploading={uploading}
                uploadText={uploadText}
            />
        );
    }
}
