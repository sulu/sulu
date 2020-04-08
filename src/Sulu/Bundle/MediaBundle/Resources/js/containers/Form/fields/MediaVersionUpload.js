// @flow
import React from 'react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import ResourceFormStore from 'sulu-admin-bundle/containers/Form/stores/ResourceFormStore';
import MediaVersionUploadComponent from '../../MediaVersionUpload';

class MediaVersionUpload extends React.Component<FieldTypeProps<void>> {
    resourceStore: ResourceStore;

    constructor(props: FieldTypeProps<void>) {
        super(props);
        const {formInspector} = this.props;

        const formStore = formInspector.formStore;
        if (!(formStore instanceof ResourceFormStore)) {
            throw new Error('The MediaVersionUpload field needs a ResourceFormStore instance!');
        }

        this.resourceStore = formStore.resourceStore;

        const locale = this.resourceStore.locale;
        if (!locale) {
            throw new Error('The resourceStore for the MediaVersionUpload must have a locale');
        }
    }

    render() {
        return (
            <MediaVersionUploadComponent
                onSuccess={this.props.onSuccess}
                resourceStore={this.resourceStore}
            />
        );
    }
}

export default MediaVersionUpload;
