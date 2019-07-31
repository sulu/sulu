// @flow
import React from 'react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import MediaVersionUploadComponent from '../../MediaVersionUpload';

class MediaVersionUpload extends React.Component<FieldTypeProps<void>> {
    resourceStore: ResourceStore;

    constructor(props: FieldTypeProps<void>) {
        super(props);
        const {formInspector} = this.props;

        // $FlowFixMe
        if (!formInspector.formStore.resourceStore){
            throw new Error('The formStore must provide a resourceStore!');
        }
        this.resourceStore = (formInspector.formStore.resourceStore: ResourceStore);

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
