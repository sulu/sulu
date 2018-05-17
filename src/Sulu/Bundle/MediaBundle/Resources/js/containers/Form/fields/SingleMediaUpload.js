// @flow
import React from 'react';
import {observable} from 'mobx';
import type {FieldTypeProps} from 'sulu-admin-bundle';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import MediaUploadStore from '../../../stores/MediaUploadStore';
import SingleMediaUploadComponent from '../../SingleMediaUpload';

export default class SingleMediaUpload extends React.Component<FieldTypeProps<Object>> {
    mediaUploadStore: MediaUploadStore;

    constructor(props: FieldTypeProps<Object>) {
        super(props);

        const {value} = this.props;

        this.mediaUploadStore = new MediaUploadStore(
            new ResourceStore('media', value ? value.id : undefined, {locale: observable.box('en')})
        );
    }

    handleUploadComplete = (media: Object) => {
        const {onChange, onFinish} = this.props;

        onChange(media);
        if (onFinish) {
            onFinish();
        }
    };

    render() {
        const {
            schemaOptions: {
                collection_id: {
                    value: collectionId,
                } = {},
                empty_icon: {
                    value: emptyIcon,
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

        if (skin !== 'default' && skin !== 'round') {
            throw new Error('The "skin" schema option must either be "default" or "round"!');
        }

        // TODO add correct collectionId
        return (
            <SingleMediaUploadComponent
                collectionId={collectionId}
                emptyIcon={emptyIcon}
                mediaUploadStore={this.mediaUploadStore}
                onUploadComplete={this.handleUploadComplete}
                skin={skin}
                uploadText={uploadText && uploadText.infoText}
            />
        );
    }
}
