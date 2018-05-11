// @flow
import React from 'react';
import {observer} from 'mobx-react';
import SingleMediaDropzone from '../../components/SingleMediaDropzone';
import MediaUploadStore from '../../stores/MediaUploadStore';

const THUMBNAIL_SIZE = 'sulu-400x400-inset';

type Props = {|
    mediaUploadStore: MediaUploadStore,
    uploadText: string,
|};

@observer
export default class SingleMediaUpload extends React.Component<Props> {
    handleMediaDrop = (file: File) => {
        const {
            mediaUploadStore,
        } = this.props;

        if (!mediaUploadStore.id){
            return;
        }

        mediaUploadStore.update(file);
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
