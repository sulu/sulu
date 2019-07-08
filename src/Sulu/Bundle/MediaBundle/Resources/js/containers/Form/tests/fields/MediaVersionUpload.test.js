// @flow
import {mount, render} from 'enzyme/build';
import {observable} from 'mobx';
import React from 'react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
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
    put: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('sulu-media-bundle/views/MediaDetails/CropOverlay', () => function CropOverlay() {
    return <div />;
});

jest.mock('../../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.id = 1;
    this.media = {};
    this.update = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.upload = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester/ResourceRequester', () => jest.fn());

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

test('Should open and close crop overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

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
    expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(false);
    expect(mediaVersionUpload.find('CropOverlay').prop('image')).toEqual('image.jpg');
    expect(mediaVersionUpload.find('CropOverlay').prop('id')).toEqual(4);
    expect(mediaVersionUpload.find('CropOverlay').prop('locale')).toEqual('de');

    mediaVersionUpload.find('Button[icon="su-cut"]').prop('onClick')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(true);

    mediaVersionUpload.find('CropOverlay').prop('onClose')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(false);
});

test('Should open and close focus point overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});

    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

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
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(false);

    mediaVersionUpload.find('Button[icon="su-focus"]').prop('onClick')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(true);

    mediaVersionUpload.find('FocusPointOverlay').prop('onClose')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(false);
});

test('Should save focus point overlay', (done) => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});

    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

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
    mediaVersionUpload.find('Button[icon="su-focus"]').prop('onClick')();

    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(true);

    mediaVersionUpload.find('ImageFocusPoint').prop('onChange')({x: 0, y: 2});
    mediaVersionUpload.find('FocusPointOverlay Overlay').prop('onConfirm')();

    expect(ResourceRequester.put).toBeCalledWith(
        'media',
        {focusPointX: 0, focusPointY: 2, url: 'image.jpg'},
        {id: 4, locale: 'de'}
    );

    setTimeout(() => {
        mediaVersionUpload.update();
        expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(false);
        done();
    });
});

test('Should call update method of MediaUploadStore if a file was dropped', () => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};

    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    resourceStore.set('id', testId);
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
    mediaVersionUpload.find('SingleMediaDropzone').prop('onDrop')(testFile);

    expect(mediaVersionUpload.instance().mediaUploadStore.update).toHaveBeenCalledWith(testFile);
});
