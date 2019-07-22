// @flow
import {mount} from 'enzyme/build';
import {observable} from 'mobx';
import React from 'react';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import MediaVersionUpload from '../MediaVersionUpload';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('sulu-media-bundle/containers/MediaVersionUpload/CropOverlay', () => function CropOverlay() {
    return <div />;
});

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.id = 1;
    this.media = {};
    this.update = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.upload = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
}));

test('Should open and close crop overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

    const mediaVersionUpload = mount(<MediaVersionUpload
        resourceStore={resourceStore}
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

    const mediaVersionUpload = mount(<MediaVersionUpload
        resourceStore={resourceStore}
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

    const mediaVersionUpload = mount(<MediaVersionUpload
        resourceStore={resourceStore}
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
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const mediaVersionUpload = mount(<MediaVersionUpload
        resourceStore={resourceStore}
    />);

    mediaVersionUpload.update();
    mediaVersionUpload.find('SingleMediaDropzone').prop('onDrop')(testFile);

    expect(mediaVersionUpload.instance().mediaUploadStore.update).toHaveBeenCalledWith(testFile);
});
