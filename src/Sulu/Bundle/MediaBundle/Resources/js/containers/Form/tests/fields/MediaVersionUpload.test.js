// @flow
import {mount, render} from 'enzyme/build';
import {observable} from 'mobx';
import React from 'react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaVersionUpload from '../../fields/MediaVersionUpload';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

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

jest.mock('sulu-media-bundle/containers/MediaVersionUpload/CropOverlay', () => function CropOverlay() {
    return <div />;
});

jest.mock('../../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.id = 1;
    this.media = {};
    this.getThumbnail = jest.fn((size) => size);
}));

test('Render a loading MediaVersionUpload field', () => {
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = true;
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );

    expect(render(
        <MediaVersionUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    )).toMatchSnapshot();
});

test('Render a non loading MediaVersionUpload field', () => {
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );

    expect(render(
        <MediaVersionUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
        />
    )).toMatchSnapshot();
});

test('Should update resourceStore after SingleMediaUpload has completed upload', () => {
    const testFile = {name: 'test.jpg'};
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );
    const mediaVersionUpload = mount(<MediaVersionUpload
        {...fieldTypeDefaultProps}
        formInspector={formInspector}
    />);

    mediaVersionUpload.update();
    mediaVersionUpload.find('SingleMediaUpload').prop('onUploadComplete')(testFile);
    expect(resourceStore.data).toEqual(testFile);
});
