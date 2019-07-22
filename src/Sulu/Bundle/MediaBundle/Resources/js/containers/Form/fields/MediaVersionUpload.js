// @flow
import React from 'react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import MediaVersionUploadComponent from '../../MediaVersionUpload';

class MediaVersionUpload extends React.Component<FieldTypeProps<void>> {
    resourceStore: ResourceStore;

    constructor(props: FieldTypeProps<void>) {
        super(props);
        const {formInspector, router} = this.props;

        // $FlowFixMe
        if (!formInspector.formStore.resourceStore){
            throw new Error();
        }
        this.resourceStore = (formInspector.formStore.resourceStore: ResourceStore);

        const locale = this.resourceStore.locale;
        if (!locale) {
            throw new Error('The resourceStore for the MediaVersionUpload must have a locale');
        }
        if (router){
            router.bind('locale', locale);
        }
    }

    render() {
        return (
            <MediaVersionUploadComponent
                resourceStore={this.resourceStore}
            />
        );
    }
}

export default MediaVersionUpload;
