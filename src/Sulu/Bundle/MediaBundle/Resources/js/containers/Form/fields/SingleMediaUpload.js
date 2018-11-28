// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import MediaUploadStore from '../../../stores/MediaUploadStore';
import SingleMediaUploadComponent from '../../SingleMediaUpload';
import type {Media} from '../../../types';

export default class SingleMediaUpload extends React.Component<FieldTypeProps<Media>> {
    mediaUploadStore: MediaUploadStore;

    constructor(props: FieldTypeProps<Object>) {
        super(props);

        const {formInspector, value} = this.props;

        if (!formInspector || !formInspector.locale) {
            throw new Error('The single media upload needs a locale to work properly');
        }

        const {locale} = formInspector;

        this.mediaUploadStore = new MediaUploadStore(
            value,
            locale
        );
    }

    handleUploadComplete = (media: Object) => {
        const {onChange, onFinish} = this.props;

        onChange(media);
        onFinish();
    };

    render() {
        const {
            disabled,
            schemaOptions: {
                collection_id: {
                    value: collectionId,
                } = {},
                empty_icon: {
                    value: emptyIcon,
                } = {},
                image_size: {
                    value: imageSize,
                } = {},
                skin: {
                    value: skin,
                } = {value: 'default'},
                upload_text: uploadText,
            } = {},
        } = this.props;

        if (typeof collectionId !== 'number') {
            throw new Error('The "collection_id" schema option is mandatory and must a number!');
        }

        if (typeof emptyIcon !== 'undefined' && typeof emptyIcon !== 'string') {
            throw new Error('The "empty_icon" schema option must be a string!');
        }

        if (typeof imageSize !== 'undefined' && typeof imageSize !== 'string') {
            throw new Error('The "image_size" schema option must be a string!');
        }

        if (skin !== 'default' && skin !== 'round') {
            throw new Error('The "skin" schema option must either be "default" or "round"!');
        }

        return (
            <SingleMediaUploadComponent
                collectionId={collectionId}
                disabled={!!disabled}
                emptyIcon={emptyIcon}
                imageSize={imageSize}
                mediaUploadStore={this.mediaUploadStore}
                onUploadComplete={this.handleUploadComplete}
                skin={skin}
                uploadText={uploadText && uploadText.infoText}
            />
        );
    }
}
