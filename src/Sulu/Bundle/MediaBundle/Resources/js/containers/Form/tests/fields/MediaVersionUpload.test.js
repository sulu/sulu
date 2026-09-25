// @flow
import {observable} from 'mobx';
import React from 'react';
import {render} from '@testing-library/react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaVersionUploadComponent from '../../../MediaVersionUpload/MediaVersionUpload';
import MediaVersionUpload from '../../fields/MediaVersionUpload';

jest.mock('sulu-admin-bundle/containers/Form/stores/metadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve(null)),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('../../../MediaVersionUpload/MediaVersionUpload', () => jest.fn(() => null));

test('Pass ResourceStore from FormInspector to MediaVersionUpload component', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );

    render(
        <MediaVersionUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onSuccess={successSpy}
        />
    );

    const MediaVersionUploadMock = (MediaVersionUploadComponent: any);

    expect(MediaVersionUploadMock.mock.calls[0][0].resourceStore).toEqual(resourceStore);
    expect(MediaVersionUploadMock.mock.calls[0][0].onSuccess).toEqual(successSpy);
});
