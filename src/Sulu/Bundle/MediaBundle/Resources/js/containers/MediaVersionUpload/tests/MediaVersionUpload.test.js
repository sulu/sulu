// @flow
import {mount, render} from 'enzyme';
import {observable} from 'mobx';
import React from 'react';
import ResourceRequester from 'sulu-admin-bundle/services/ResourceRequester';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import FormatStore from '../../../stores/formatStore';
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

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.deletePreviewImage = jest.fn();
    this.id = 1;
    this.media = {};
    this.update = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.updatePreviewImage = jest.fn();
    this.upload = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
}));

jest.mock('../../../stores/formatStore', () => ({
    loadFormats: jest.fn().mockReturnValue(Promise.resolve([{key: 'test', scale: {}}])),
}));

jest.mock('../../../stores/MediaFormatStore', () => jest.fn(function() {
    this.getFormatOptions = jest.fn();
    this.updateFormatOptions = jest.fn();
    this.loading = false;
}));

test('Render a MediaVersionUpload field for images', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isImage = true;

    expect(render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    )).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isVideo = true;

    expect(render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    )).toMatchSnapshot();
});

test('Should update resourceStore and call onSuccess after SingleMediaUpload has completed upload', () => {
    const successSpy = jest.fn();
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    mediaVersionUpload.update();
    mediaVersionUpload.find('SingleMediaUpload').prop('onUploadComplete')(testFile);
    expect(resourceStore.data).toEqual(testFile);
    expect(successSpy).toBeCalled();
});

test('Should open and close crop overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';
    resourceStore.data.isImage = true;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={undefined}
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
    resourceStore.data.isImage = true;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={undefined}
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

test('Should save focus point overlay and call onSuccess', (done) => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';
    resourceStore.data.isImage = true;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    mediaVersionUpload.update();
    mediaVersionUpload.find('Button[icon="su-focus"]').prop('onClick')();

    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(true);
    expect(mediaVersionUpload.find('FocusPointOverlay Overlay').prop('confirmDisabled')).toEqual(false);

    mediaVersionUpload.find('ImageFocusPoint').prop('onChange')({x: 0, y: 2});
    mediaVersionUpload.find('FocusPointOverlay Overlay').prop('onConfirm')();

    expect(ResourceRequester.put).toBeCalledWith(
        'media',
        {focusPointX: 0, focusPointY: 2, isImage: true, url: 'image.jpg'},
        {id: 4, locale: 'de'}
    );

    setTimeout(() => {
        mediaVersionUpload.update();
        expect(mediaVersionUpload.find('FocusPointOverlay').find('Overlay').prop('confirmDisabled')).toEqual(true);
        expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(false);
        expect(successSpy).toBeCalled();
        done();
    });
});

test('Should save crop overlay and call onSuccess', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';
    resourceStore.data.isImage = true;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    const formatsPromise = Promise.resolve([]);
    FormatStore.loadFormats.mockReturnValue(formatsPromise);

    mediaVersionUpload.find('Button[icon="su-cut"]').prop('onClick')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(true);

    return formatsPromise.then(() => {
        mediaVersionUpload.update();
        mediaVersionUpload.find('withContainerSize(ImageRectangleSelection)').prop('onChange')(
            {height: 60, left: 200, top: 20, width: 20}
        );
        mediaVersionUpload.update();
        expect(mediaVersionUpload.find('CropOverlay Overlay').prop('confirmDisabled')).toEqual(false);
        expect(mediaVersionUpload.find('withContainerSize(ImageRectangleSelection)').props())
            .toEqual(expect.objectContaining({
                value: {
                    height: 60,
                    left: 200,
                    top: 20,
                    width: 20,
                },
            }));

        const putPromise = Promise.resolve({});
        mediaVersionUpload.find('CropOverlay').instance().mediaFormatStore.updateFormatOptions
            .mockReturnValue(putPromise);
        mediaVersionUpload.find('CropOverlay').find('Overlay').prop('onConfirm')();

        expect(mediaVersionUpload.find('CropOverlay').instance().mediaFormatStore.updateFormatOptions).toBeCalledWith(
            {
                test: {cropHeight: 60, cropWidth: 20, cropX: 200, cropY: 20},
            }
        );

        return putPromise.then(() => {
            mediaVersionUpload.find('CropOverlay').update();
            expect(mediaVersionUpload.find('CropOverlay').find('Overlay').prop('confirmDisabled')).toEqual(true);
            expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(false);
            expect(successSpy).toBeCalled();
        });
    });
});

test('Should call update method of MediaUploadStore if a file was dropped', () => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={undefined}
        resourceStore={resourceStore}
    />);

    mediaVersionUpload.update();
    mediaVersionUpload.find('SingleMediaDropzone').prop('onDrop')(testFile);

    expect(mediaVersionUpload.instance().mediaUploadStore.update).toHaveBeenCalledWith(testFile);
});

test('Should call updatePreviewImage method of MediaUploadStore if a new preview image is uploaded', () => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    mediaVersionUpload.update();

    const updatePreviewPromise = Promise.resolve({name: 'test.jpg'});
    mediaVersionUpload.instance().mediaUploadStore.updatePreviewImage.mockReturnValue(updatePreviewPromise);
    mediaVersionUpload.find('FileUploadButton').prop('onUpload')(testFile);

    expect(mediaVersionUpload.instance().mediaUploadStore.updatePreviewImage).toHaveBeenCalledWith(testFile);

    return updatePreviewPromise.then(() => {
        expect(successSpy).toBeCalledWith();
    });
});

test('Should call deletePreviewImage method of MediaUploadStore if the button to delete a preview is clicked', () => {
    const testId = 1;
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const mediaVersionUpload = mount(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    mediaVersionUpload.update();

    const deletePreviewPromise = Promise.resolve({name: 'test.jpg'});
    mediaVersionUpload.instance().mediaUploadStore.deletePreviewImage.mockReturnValue(deletePreviewPromise);
    mediaVersionUpload.find('Button[icon="su-trash-alt"]').prop('onClick')();

    expect(mediaVersionUpload.instance().mediaUploadStore.deletePreviewImage).toHaveBeenCalledWith();

    return deletePreviewPromise.then(() => {
        expect(successSpy).toBeCalledWith();
    });
});
