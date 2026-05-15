// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import {Button, Dialog, FileUploadButton} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import SingleMediaDropzone from '../../../components/SingleMediaDropzone';
import MediaUploadStore from '../../../stores/MediaUploadStore';
import CropOverlay from '../CropOverlay';
import FocusPointOverlay from '../FocusPointOverlay';
import MediaVersionUpload from '../MediaVersionUpload';

jest.mock('sulu-admin-bundle/components', () => {
    const Button = jest.fn(() => null);
    const Dialog = jest.fn(() => null);
    const FileUploadButton = jest.fn(() => null);

    return {Button, Dialog, FileUploadButton};
});

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
    put: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('../../../components/SingleMediaDropzone', () => jest.fn(() => null));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.deletePreviewImage = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.id = 1;
    this.media = {};
    this.update = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.updatePreviewImage = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.upload = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
}));

jest.mock('../CropOverlay', () => jest.fn(() => null));
jest.mock('../FocusPointOverlay', () => jest.fn(() => null));

function renderMediaVersionUpload(props: Object = {}) {
    const resourceStore = props.resourceStore || new ResourceStore('media', 4, {locale: observable.box('de')});

    return {
        ...render(
            <MediaVersionUpload
                onSuccess={jest.fn()}
                resourceStore={resourceStore}
                {...props}
            />
        ),
        resourceStore,
    };
}

function getButtonPropsByIcon(icon: string) {
    const calls = ((Button: any).mock.calls: any);
    const buttonCall = calls.find(([props]) => props.icon === icon);

    if (!buttonCall) {
        throw new Error(`Expected Button with icon "${icon}"`);
    }

    return buttonCall[0];
}

function getLatestDialogProps() {
    const calls = ((Dialog: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestFileUploadButtonProps() {
    const calls = ((FileUploadButton: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestDropzoneProps() {
    const calls = ((SingleMediaDropzone: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestCropOverlayProps() {
    const calls = ((CropOverlay: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestFocusPointOverlayProps() {
    const calls = ((FocusPointOverlay: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getMediaUploadStoreInstance(index: number = 0) {
    const instances = ((MediaUploadStore: any).mock.instances: any);
    return instances[index];
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render a MediaVersionUpload field for images', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isImage = true;

    const {container} = renderMediaVersionUpload({
        onSuccess: jest.fn(),
        resourceStore,
    });

    expect(container).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos without assigned preview image', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isVideo = true;

    const {container} = renderMediaVersionUpload({
        onSuccess: jest.fn(),
        resourceStore,
    });

    expect(container).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isVideo = true;
    resourceStore.data.previewImageId = 5;

    const {container} = renderMediaVersionUpload({
        onSuccess: jest.fn(),
        resourceStore,
    });

    expect(container).toMatchSnapshot();
});

test('Should update resourceStore and call onSuccess after media drop upload has completed', async() => {
    const successSpy = jest.fn();
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;

    renderMediaVersionUpload({
        onSuccess: successSpy,
        resourceStore,
    });

    const mediaUploadStore = getMediaUploadStoreInstance();
    const updatePromise = Promise.resolve(testFile);
    mediaUploadStore.update.mockReturnValue(updatePromise);

    act(() => {
        getLatestDropzoneProps().onDrop(testFile);
    });

    expect(mediaUploadStore.update).toBeCalledWith(testFile);

    await act(async() => {
        await updatePromise;
    });

    expect(resourceStore.data).toEqual(testFile);
    expect(successSpy).toBeCalled();
});

test('Should open and close crop overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    renderMediaVersionUpload({
        onSuccess: undefined,
        resourceStore,
    });

    expect(getLatestCropOverlayProps().open).toEqual(false);
    expect(getLatestCropOverlayProps().image).toEqual('image.jpg');
    expect(getLatestCropOverlayProps().id).toEqual(4);
    expect(getLatestCropOverlayProps().locale).toEqual('de');

    act(() => {
        getButtonPropsByIcon('su-cut').onClick();
    });

    expect(getLatestCropOverlayProps().open).toEqual(true);

    act(() => {
        getLatestCropOverlayProps().onClose();
    });

    expect(getLatestCropOverlayProps().open).toEqual(false);
});

test('Should open and close focus point overlay', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    renderMediaVersionUpload({
        onSuccess: undefined,
        resourceStore,
    });

    expect(getLatestFocusPointOverlayProps().open).toEqual(false);

    act(() => {
        getButtonPropsByIcon('su-focus').onClick();
    });

    expect(getLatestFocusPointOverlayProps().open).toEqual(true);

    act(() => {
        getLatestFocusPointOverlayProps().onClose();
    });

    expect(getLatestFocusPointOverlayProps().open).toEqual(false);
});

test('Should save focus point overlay and call onSuccess', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.url = 'image.jpg';
    resourceStore.data.isImage = true;

    renderMediaVersionUpload({
        onSuccess: successSpy,
        resourceStore,
    });

    act(() => {
        getButtonPropsByIcon('su-focus').onClick();
    });

    expect(getLatestFocusPointOverlayProps().open).toEqual(true);

    act(() => {
        getLatestFocusPointOverlayProps().onConfirm();
    });

    expect(getLatestFocusPointOverlayProps().open).toEqual(false);
    expect(successSpy).toBeCalled();
});

test('Should save crop overlay and call onSuccess', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    renderMediaVersionUpload({
        onSuccess: successSpy,
        resourceStore,
    });

    act(() => {
        getButtonPropsByIcon('su-cut').onClick();
    });

    expect(getLatestCropOverlayProps().open).toEqual(true);

    act(() => {
        getLatestCropOverlayProps().onConfirm();
    });

    expect(getLatestCropOverlayProps().open).toEqual(false);
    expect(successSpy).toBeCalled();
});

test('Should call updatePreviewImage method of MediaUploadStore if a new preview image is uploaded', async() => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    renderMediaVersionUpload({
        onSuccess: successSpy,
        resourceStore,
    });

    const mediaUploadStore = getMediaUploadStoreInstance();
    const updatePreviewPromise = Promise.resolve({name: 'test.jpg'});
    mediaUploadStore.updatePreviewImage.mockReturnValue(updatePreviewPromise);

    act(() => {
        getLatestFileUploadButtonProps().onUpload(testFile);
    });

    expect(mediaUploadStore.updatePreviewImage).toHaveBeenCalledWith(testFile);

    await act(async() => {
        await updatePreviewPromise;
    });

    expect(successSpy).toBeCalledWith();
});

test(
    'Should call deletePreviewImage method of MediaUploadStore if the button to delete a preview is confirmed',
    async() => {
        const testId = 1;
        const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
        const successSpy = jest.fn();

        resourceStore.set('id', testId);
        resourceStore.loading = false;

        renderMediaVersionUpload({
            onSuccess: successSpy,
            resourceStore,
        });

        const mediaUploadStore = getMediaUploadStoreInstance();
        const deletePreviewPromise = Promise.resolve({name: 'test.jpg'});
        mediaUploadStore.deletePreviewImage.mockReturnValue(deletePreviewPromise);

        act(() => {
            getButtonPropsByIcon('su-trash-alt').onClick();
        });

        expect(getLatestDialogProps().open).toBe(true);
        expect(getLatestDialogProps().confirmLoading).toBe(false);

        act(() => {
            getLatestDialogProps().onConfirm();
        });

        expect(mediaUploadStore.deletePreviewImage).toHaveBeenCalledWith();
        expect(getLatestDialogProps().confirmLoading).toBe(true);

        await act(async() => {
            await deletePreviewPromise;
        });

        expect(successSpy).toBeCalledWith();
        expect(getLatestDialogProps().open).toBe(false);
        expect(getLatestDialogProps().confirmLoading).toBe(false);
    }
);

test('Should not call deletePreviewImage method of MediaUploadStore if the delete preview dialog is cancelled', () => {
    const testId = 1;
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    renderMediaVersionUpload({
        onSuccess: successSpy,
        resourceStore,
    });

    const mediaUploadStore = getMediaUploadStoreInstance();

    act(() => {
        getButtonPropsByIcon('su-trash-alt').onClick();
    });

    expect(getLatestDialogProps().open).toBe(true);

    act(() => {
        getLatestDialogProps().onCancel();
    });

    expect(mediaUploadStore.deletePreviewImage).not.toBeCalled();
    expect(getLatestDialogProps().open).toBe(false);
});
