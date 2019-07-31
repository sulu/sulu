// @flow
import {shallow} from 'enzyme';
import {observable} from 'mobx';
import React from 'react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaVersionUploadComponent from '../../../MediaVersionUpload/MediaVersionUpload';
import MediaVersionUpload from '../../fields/MediaVersionUpload';

jest.mock('sulu-admin-bundle/containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve([])),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
}));

test('Pass ResourceStore from FormInspector to MediaVersionUpload component', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );

    const mediaVersionUpload = shallow(<MediaVersionUpload
        {...fieldTypeDefaultProps}
        formInspector={formInspector}
        onSuccess={successSpy}
    />);

    expect(mediaVersionUpload.find(MediaVersionUploadComponent).prop('resourceStore')).toEqual(resourceStore);
    expect(mediaVersionUpload.find(MediaVersionUploadComponent).prop('onSuccess')).toEqual(successSpy);
});
