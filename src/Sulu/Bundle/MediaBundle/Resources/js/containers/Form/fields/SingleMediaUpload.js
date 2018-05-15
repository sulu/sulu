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
                skin = {value: 'default'},
                upload_text: uploadText,
            } = {},
        } = this.props;

        // TODO add correct collectionId
        return (
            <SingleMediaUploadComponent
                collectionId={1}
                mediaUploadStore={this.mediaUploadStore}
                onUploadComplete={this.handleUploadComplete}
                skin={skin.value}
                uploadText={uploadText && uploadText.infotext}
            />
        );
    }
}
