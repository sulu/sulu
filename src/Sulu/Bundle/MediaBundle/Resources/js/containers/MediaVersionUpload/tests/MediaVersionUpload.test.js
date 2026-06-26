// @flow
import {observable} from 'mobx';
import React from 'react';
import {render} from '@testing-library/react';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {findAllElementsByType, findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import MediaVersionUpload from '../MediaVersionUpload';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
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

function getButton(mediaVersionUpload, icon) {
    const button = findAllElementsByType(mediaVersionUpload.render(), 'Button')
        .find((button) => button.props.icon === icon);

    if (!button) {
        throw new Error('Button not found!');
    }

    return button;
}

test('Render a MediaVersionUpload field for images', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isImage = true;

    const {container} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos without assigned preview image', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isVideo = true;

    const {container} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isVideo = true;
    resourceStore.data.previewImageId = 5;

    const {container} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Should update resourceStore and call onSuccess after SingleMediaUpload has completed upload', () => {
    const successSpy = jest.fn();
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    findElementByType(mediaVersionUpload.render(), 'SingleMediaUpload').props.onUploadComplete(testFile);
    expect(resourceStore.data).toEqual(testFile);
    expect(successSpy).toHaveBeenCalled();
});

test('Should open and close crop overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={undefined}
        resourceStore={resourceStore}
    />);

    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.open).toEqual(false);
    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.image).toEqual('image.jpg');
    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.id).toEqual(4);
    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.locale).toEqual('de');

    getButton(mediaVersionUpload, 'su-cut').props.onClick();
    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.open).toEqual(true);

    findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.onClose();
    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.open).toEqual(false);
});

test('Should open and close focus point overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={undefined}
        resourceStore={resourceStore}
    />);

    expect(findElementByType(mediaVersionUpload.render(), 'FocusPointOverlay').props.open).toEqual(false);

    getButton(mediaVersionUpload, 'su-focus').props.onClick();
    expect(findElementByType(mediaVersionUpload.render(), 'FocusPointOverlay').props.open).toEqual(true);

    findElementByType(mediaVersionUpload.render(), 'FocusPointOverlay').props.onClose();
    expect(findElementByType(mediaVersionUpload.render(), 'FocusPointOverlay').props.open).toEqual(false);
});

test('Should close focus point overlay and call onSuccess when confirmed', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.url = 'image.jpg';
    resourceStore.data.isImage = true;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    getButton(mediaVersionUpload, 'su-focus').props.onClick();
    expect(findElementByType(mediaVersionUpload.render(), 'FocusPointOverlay').props.open).toEqual(true);

    findElementByType(mediaVersionUpload.render(), 'FocusPointOverlay').props.onConfirm();

    expect(findElementByType(mediaVersionUpload.render(), 'FocusPointOverlay').props.open).toEqual(false);
    expect(successSpy).toHaveBeenCalledWith();
});

test('Should close crop overlay and call onSuccess when confirmed', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    getButton(mediaVersionUpload, 'su-cut').props.onClick();
    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.open).toEqual(true);

    findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.onConfirm();

    expect(findElementByType(mediaVersionUpload.render(), 'CropOverlay').props.open).toEqual(false);
    expect(successSpy).toHaveBeenCalledWith();
});

test('Should call update method of MediaUploadStore if a file was dropped', () => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={undefined}
        resourceStore={resourceStore}
    />);

    findElementByType(mediaVersionUpload.render(), 'SingleMediaUpload').props.mediaUploadStore.update(testFile);

    expect(mediaVersionUpload.mediaUploadStore.update).toHaveBeenCalledWith(testFile);
});

test('Should call updatePreviewImage method of MediaUploadStore if a new preview image is uploaded', () => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    const updatePreviewPromise = Promise.resolve({name: 'test.jpg'});
    mediaVersionUpload.mediaUploadStore.updatePreviewImage.mockReturnValue(updatePreviewPromise);
    findElementByType(mediaVersionUpload.render(), 'FileUploadButton').props.onUpload(testFile);

    expect(mediaVersionUpload.mediaUploadStore.updatePreviewImage).toHaveBeenCalledWith(testFile);

    return updatePreviewPromise.then(() => {
        expect(successSpy).toHaveBeenCalledWith();
    });
});

test('Should call deletePreviewImage method of MediaUploadStore if the button to delete a preview is clicked', () => {
    const testId = 1;
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    const deletePreviewPromise = Promise.resolve({name: 'test.jpg'});
    mediaVersionUpload.mediaUploadStore.deletePreviewImage.mockReturnValue(deletePreviewPromise);
    getButton(mediaVersionUpload, 'su-trash-alt').props.onClick();

    findElementByType(mediaVersionUpload.render(), 'Dialog').props.onConfirm();

    expect(mediaVersionUpload.mediaUploadStore.deletePreviewImage).toHaveBeenCalledWith();

    return deletePreviewPromise.then(() => {
        expect(successSpy).toHaveBeenCalledWith();
    });
});

test('Should not call deletePreviewImage method of MediaUploadStore if the delete preview dialog is cancelled', () => {
    const testId = 1;
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const {instance: mediaVersionUpload} = renderWithRef(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    getButton(mediaVersionUpload, 'su-trash-alt').props.onClick();
    findElementByType(mediaVersionUpload.render(), 'Dialog').props.onCancel();

    expect(mediaVersionUpload.mediaUploadStore.deletePreviewImage).not.toHaveBeenCalled();
});
