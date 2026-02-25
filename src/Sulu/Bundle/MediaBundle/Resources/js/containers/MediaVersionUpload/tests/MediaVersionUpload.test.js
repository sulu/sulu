// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render} from '@testing-library/react';
import {findMockCallArg, getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaVersionUpload from '../MediaVersionUpload';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/components', () => ({
    Button: jest.fn(() => null),
    Dialog: jest.fn(() => null),
    FileUploadButton: jest.fn(() => null),
}));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.deletePreviewImage = jest.fn();
    this.id = 1;
    this.media = {};
    this.update = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.updatePreviewImage = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.upload = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
}));

jest.mock('../CropOverlay', () => jest.fn(() => null));
jest.mock('../FocusPointOverlay', () => jest.fn(() => null));
jest.mock('../../SingleMediaUpload', () => jest.fn(() => null));

const componentsMock: any = jest.requireMock('sulu-admin-bundle/components');
const MediaUploadStoreMock: any = jest.requireMock('../../../stores/MediaUploadStore');
const CropOverlayMock: any = jest.requireMock('../CropOverlay');
const FocusPointOverlayMock: any = jest.requireMock('../FocusPointOverlay');
const SingleMediaUploadMock: any = jest.requireMock('../../SingleMediaUpload');

const getMediaUploadStore = () => MediaUploadStoreMock.mock.instances[MediaUploadStoreMock.mock.instances.length - 1];

const getButtonPropsByIcon = (icon) => findMockCallArg(componentsMock.Button, ([props]) => props.icon === icon);

const createResourceStore = (data = {}) => ({
    data: {
        ...data,
    },
    id: 4,
    loading: false,
    locale: observable.box('de'),
    set: jest.fn(function(key, value) {
        this[key] = value;
    }),
    setMultiple: jest.fn(function(values) {
        this.data = {...this.data, ...values};
    }),
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render a MediaVersionUpload field for images', () => {
    const resourceStore = createResourceStore({isImage: true});

    const {asFragment} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={(resourceStore: any)}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos without assigned preview image', () => {
    const resourceStore = createResourceStore({isVideo: true});

    const {asFragment} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={(resourceStore: any)}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos', () => {
    const resourceStore = createResourceStore({isVideo: true, previewImageId: 5});

    const {asFragment} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={(resourceStore: any)}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Should update resourceStore and call onSuccess after SingleMediaUpload has completed upload', () => {
    const successSpy = jest.fn();
    const testFile = {name: 'test.jpg'};
    const resourceStore = createResourceStore();

    render(
        <MediaVersionUpload
            onSuccess={successSpy}
            resourceStore={(resourceStore: any)}
        />
    );

    getLatestMockProps(SingleMediaUploadMock).onUploadComplete(testFile);
    expect(resourceStore.setMultiple).toBeCalledWith(testFile);
    expect(successSpy).toBeCalled();
});

test('Should open and close crop overlay', () => {
    const resourceStore = createResourceStore({adminUrl: 'image.jpg', isImage: true});

    render(
        <MediaVersionUpload
            onSuccess={undefined}
            resourceStore={(resourceStore: any)}
        />
    );

    expect(getLatestMockProps(CropOverlayMock).open).toEqual(false);
    expect(getLatestMockProps(CropOverlayMock).image).toEqual('image.jpg');
    expect(getLatestMockProps(CropOverlayMock).id).toEqual(4);
    expect(getLatestMockProps(CropOverlayMock).locale).toEqual('de');

    const cropButton = getButtonPropsByIcon('su-cut');
    if (!cropButton) {
        throw new Error('Expected crop button');
    }

    act(() => {
        cropButton.onClick();
    });
    expect(getLatestMockProps(CropOverlayMock).open).toEqual(true);

    act(() => {
        getLatestMockProps(CropOverlayMock).onClose();
    });
    expect(getLatestMockProps(CropOverlayMock).open).toEqual(false);
});

test('Should open and close focus point overlay', () => {
    const resourceStore = createResourceStore({adminUrl: 'image.jpg', isImage: true});

    render(
        <MediaVersionUpload
            onSuccess={undefined}
            resourceStore={(resourceStore: any)}
        />
    );

    expect(getLatestMockProps(FocusPointOverlayMock).open).toEqual(false);

    const focusButton = getButtonPropsByIcon('su-focus');
    if (!focusButton) {
        throw new Error('Expected focus button');
    }

    act(() => {
        focusButton.onClick();
    });
    expect(getLatestMockProps(FocusPointOverlayMock).open).toEqual(true);

    act(() => {
        getLatestMockProps(FocusPointOverlayMock).onClose();
    });
    expect(getLatestMockProps(FocusPointOverlayMock).open).toEqual(false);
});

test('Should save focus point overlay and call onSuccess', () => {
    const resourceStore = createResourceStore({adminUrl: 'image.jpg', url: 'image.jpg', isImage: true});
    const successSpy = jest.fn();

    render(
        <MediaVersionUpload
            onSuccess={successSpy}
            resourceStore={(resourceStore: any)}
        />
    );

    const focusButton = getButtonPropsByIcon('su-focus');
    if (!focusButton) {
        throw new Error('Expected focus button');
    }

    act(() => {
        focusButton.onClick();
    });
    expect(getLatestMockProps(FocusPointOverlayMock).open).toEqual(true);

    act(() => {
        getLatestMockProps(FocusPointOverlayMock).onConfirm();
    });

    expect(getLatestMockProps(FocusPointOverlayMock).open).toEqual(false);
    expect(successSpy).toBeCalled();
});

test('Should save crop overlay and call onSuccess', () => {
    const resourceStore = createResourceStore({adminUrl: 'image.jpg', isImage: true});
    const successSpy = jest.fn();

    render(
        <MediaVersionUpload
            onSuccess={successSpy}
            resourceStore={(resourceStore: any)}
        />
    );

    const cropButton = getButtonPropsByIcon('su-cut');
    if (!cropButton) {
        throw new Error('Expected crop button');
    }

    act(() => {
        cropButton.onClick();
    });
    expect(getLatestMockProps(CropOverlayMock).open).toEqual(true);

    act(() => {
        getLatestMockProps(CropOverlayMock).onConfirm();
    });

    expect(getLatestMockProps(CropOverlayMock).open).toEqual(false);
    expect(successSpy).toBeCalled();
});

test('Should call updatePreviewImage method of MediaUploadStore if a new preview image is uploaded', async() => {
    const testFile = {name: 'test.jpg'};
    const resourceStore = createResourceStore({isImage: false});
    const successSpy = jest.fn();

    render(
        <MediaVersionUpload
            onSuccess={successSpy}
            resourceStore={(resourceStore: any)}
        />
    );

    const mediaUploadStore = getMediaUploadStore();
    const updatePreviewPromise = Promise.resolve({name: 'test.jpg'});
    mediaUploadStore.updatePreviewImage.mockReturnValue(updatePreviewPromise);
    getLatestMockProps(componentsMock.FileUploadButton).onUpload(testFile);

    expect(mediaUploadStore.updatePreviewImage).toHaveBeenCalledWith(testFile);

    await updatePreviewPromise;
    expect(successSpy).toBeCalledWith();
});

test(
    'Should call deletePreviewImage method of MediaUploadStore if the button to delete a preview is clicked',
    async() => {
        const resourceStore = createResourceStore({isImage: false});
        const successSpy = jest.fn();

        render(
            <MediaVersionUpload
                onSuccess={successSpy}
                resourceStore={(resourceStore: any)}
            />
        );

        const mediaUploadStore = getMediaUploadStore();
        const deletePreviewPromise = Promise.resolve({name: 'test.jpg'});
        mediaUploadStore.deletePreviewImage.mockReturnValue(deletePreviewPromise);

        const deleteButton = getButtonPropsByIcon('su-trash-alt');
        if (!deleteButton) {
            throw new Error('Expected delete-preview button');
        }

        act(() => {
            deleteButton.onClick();
        });
        expect(getLatestMockProps(componentsMock.Dialog).open).toEqual(true);

        act(() => {
            getLatestMockProps(componentsMock.Dialog).onConfirm();
        });

        expect(mediaUploadStore.deletePreviewImage).toHaveBeenCalledWith();

        await deletePreviewPromise;
        expect(successSpy).toBeCalledWith();
    }
);

test('Should not call deletePreviewImage method of MediaUploadStore if the delete preview dialog is cancelled', () => {
    const resourceStore = createResourceStore({isImage: false});
    const successSpy = jest.fn();

    render(
        <MediaVersionUpload
            onSuccess={successSpy}
            resourceStore={(resourceStore: any)}
        />
    );

    const deleteButton = getButtonPropsByIcon('su-trash-alt');
    if (!deleteButton) {
        throw new Error('Expected delete-preview button');
    }

    act(() => {
        deleteButton.onClick();
    });
    expect(getLatestMockProps(componentsMock.Dialog).open).toEqual(true);

    act(() => {
        getLatestMockProps(componentsMock.Dialog).onCancel();
    });

    expect(getMediaUploadStore().deletePreviewImage).not.toBeCalled();
});
