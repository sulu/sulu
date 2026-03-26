// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import {Button, Dialog} from 'sulu-admin-bundle/components';
import SingleMediaUpload from '../SingleMediaUpload';
import MediaUploadStore from '../../../stores/MediaUploadStore';
import SingleMediaDropzone from '../../../components/SingleMediaDropzone';

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function(media) {
    this.id = media ? media.id : undefined;
    this.create = jest.fn();
    this.update = jest.fn();
    this.delete = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
    this.downloadUrl = media?.adminUrl || media?.url;
    this.media = media;
}));

jest.mock('../../../components/SingleMediaDropzone', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/components', () => {
    const React = require('react');

    const Button = jest.fn(function Button(props) {
        return (
            <button onClick={props.onClick} type="button">
                {props.children}
            </button>
        );
    });

    const Dialog = jest.fn(function Dialog(props) {
        return props.open ? (
            <div>
                <button onClick={props.onConfirm} type="button">
                    {props.confirmText}
                </button>
                <button onClick={props.onCancel} type="button">
                    {props.cancelText}
                </button>
                {props.children}
            </div>
        ) : null;
    });

    return {Button, Dialog};
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

function getLatestDropzoneProps() {
    const calls = ((SingleMediaDropzone: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function getLatestDialogProps() {
    const calls = ((Dialog: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function createMediaUploadStore(media: ?Object) {
    return new MediaUploadStore(media, observable.box('en'));
}

function createDefaultMedia() {
    return {
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    };
}

function renderSingleMediaUpload(props: Object = {}) {
    const mediaUploadStore = props.mediaUploadStore || createMediaUploadStore(createDefaultMedia());

    const mergedProps = {
        collectionId: 5,
        mediaUploadStore,
        uploadText: 'Upload media',
        ...props,
    };

    return {
        ...render(<SingleMediaUpload {...mergedProps} />),
        mediaUploadStore,
    };
}

beforeEach(() => {
    jest.clearAllMocks();
});

function expectRenderToThrow(renderFn: () => void, expectedMessage: string) {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    expect(renderFn).toThrow(expectedMessage);

    consoleErrorSpy.mockRestore();
}

test('Render a SingleMediaUpload', () => {
    const {mediaUploadStore} = renderSingleMediaUpload();

    expect(getLatestDropzoneProps()).toEqual(expect.objectContaining({
        disabled: false,
        errorText: undefined,
        image: 'sulu-400x400',
        skin: 'default',
        uploadText: 'Upload media',
    }));
    expect(mediaUploadStore.getThumbnail).toBeCalledWith('sulu-400x400');
});

test('Render a SingleMediaUpload in disabled state', () => {
    renderSingleMediaUpload({disabled: true});

    expect(getLatestDropzoneProps().disabled).toBe(true);
    expect((Button: any).mock.calls).toHaveLength(0);
});

test('Render a SingleMediaUpload with an error message from the MediaUploadStore', () => {
    const mediaUploadStore = createMediaUploadStore(createDefaultMedia());

    mediaUploadStore.error = {
        code: 5003,
        detail: 'The uploaded file exceeds the configured maximum filesize.',
    };

    renderSingleMediaUpload({
        disabled: true,
        mediaUploadStore,
    });

    expect(getLatestDropzoneProps().errorText).toEqual('The uploaded file exceeds the configured maximum filesize.');
});

test('Render a SingleMediaUpload with an empty icon if no image is passed', () => {
    const mediaUploadStore = createMediaUploadStore(undefined);
    mediaUploadStore.getThumbnail.mockReturnValue(undefined);

    renderSingleMediaUpload({
        mediaUploadStore,
    });

    expect(getLatestDropzoneProps().image).toBeUndefined();
});

test('Render a SingleMediaUpload with the round skin', () => {
    renderSingleMediaUpload({skin: 'round'});

    expect(getLatestDropzoneProps().skin).toEqual('round');
});

test('Render a SingleMediaUpload with a different image size', () => {
    const {mediaUploadStore} = renderSingleMediaUpload();

    expect(mediaUploadStore.getThumbnail).toBeCalledWith('sulu-400x400');
});

test('Render a SingleMediaUpload without delete and download button', () => {
    renderSingleMediaUpload({
        deletable: false,
        downloadable: false,
        uploadText: 'Test',
    });

    expect((Button: any).mock.calls).toHaveLength(0);
});

test('Call update on MediaUploadStore if id is given and drop event occurs', async() => {
    const uploadCompleteSpy = jest.fn();
    const mediaUploadStore = createMediaUploadStore(createDefaultMedia());
    const result = {};
    const promise = Promise.resolve(result);
    mediaUploadStore.update.mockReturnValue(promise);

    renderSingleMediaUpload({
        collectionId: 7,
        mediaUploadStore,
        onUploadComplete: uploadCompleteSpy,
    });

    const file = {name: 'test.jpg'};
    getLatestDropzoneProps().onDrop(file);

    expect(mediaUploadStore.update).toBeCalledWith(file);
    await promise;
    expect(uploadCompleteSpy).toBeCalledWith(result);
});

test('Call create with passed collectionId if id is not given and drop event occurs', async() => {
    const uploadCompleteSpy = jest.fn();
    const mediaUploadStore = createMediaUploadStore(undefined);
    const result = {};
    const promise = Promise.resolve(result);
    mediaUploadStore.create.mockReturnValue(promise);

    renderSingleMediaUpload({
        collectionId: 7,
        mediaUploadStore,
        onUploadComplete: uploadCompleteSpy,
    });

    const file = {name: 'test.jpg'};
    getLatestDropzoneProps().onDrop(file);

    expect(mediaUploadStore.create).toBeCalledWith(7, file);
    await promise;
    expect(uploadCompleteSpy).toBeCalledWith(result);
});

test('Download the image when the download button is clicked', () => {
    // $FlowFixMe
    delete window.location;
    // $FlowFixMe
    window.location = {assign: jest.fn()};

    const mediaUploadStore = createMediaUploadStore({
        ...createDefaultMedia(),
        url: 'test.jpg',
    });

    renderSingleMediaUpload({
        mediaUploadStore,
    });

    ((Button: any).mock.calls[0][0].onClick: any)();
    // $FlowFixMe
    expect(window.location.assign).toBeCalledWith('test.jpg');
});

test('Delete the image when the delete button is clicked and the overlay is confirmed', async() => {
    const mediaUploadStore = createMediaUploadStore(createDefaultMedia());
    const deletePromise = Promise.resolve();
    mediaUploadStore.delete.mockReturnValue(deletePromise);

    const uploadCompleteSpy = jest.fn();

    const {rerender} = renderSingleMediaUpload({
        mediaUploadStore,
        onUploadComplete: uploadCompleteSpy,
    });

    ((Button: any).mock.calls[1][0].onClick: any)();

    expect(getLatestDialogProps().open).toEqual(true);
    expect(getLatestDialogProps().confirmLoading).toEqual(false);

    getLatestDialogProps().onConfirm();
    expect(mediaUploadStore.delete).toBeCalled();

    rerender(
        <SingleMediaUpload
            collectionId={5}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );
    expect(getLatestDialogProps().confirmLoading).toEqual(true);

    await deletePromise;

    rerender(
        <SingleMediaUpload
            collectionId={5}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    expect(uploadCompleteSpy).toBeCalled();
    expect(getLatestDialogProps().open).toEqual(false);
    expect(getLatestDialogProps().confirmLoading).toEqual(false);
});

test('Throw exception if neither the collectionId nor the media is given', () => {
    const mediaUploadStore = createMediaUploadStore(undefined);

    expectRenderToThrow(
        () => render(
            <SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="UploadMedia" />
        ),
        '"collectionId"'
    );
});
