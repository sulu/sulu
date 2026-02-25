// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render, waitFor} from '@testing-library/react';
import {findMockCallArg, getLatestMockProps} from 'sulu-admin-bundle/utils/TestHelper';
import SingleMediaUpload from '../SingleMediaUpload';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function(media) {
    this.id = media ? media.id : undefined;
    this.create = jest.fn();
    this.update = jest.fn();
    this.delete = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
    this.downloadUrl = media?.adminUrl || media?.url;
    this.media = media;
}));

jest.mock('sulu-admin-bundle/components', () => ({
    Button: jest.fn(() => null),
    Dialog: jest.fn(() => null),
    CircularProgressbar: jest.fn(() => null),
    Icon: jest.fn(() => null),
    Loader: jest.fn(() => null),
}));

jest.mock('react-dropzone', () => {
    const React = require('react');

    return jest.fn(function DropzoneMock(props) {
        return (
            <div data-testid="dropzone">
                {props.children({
                    getInputProps: () => ({'data-testid': 'dropzone-input'}),
                    getRootProps: (rootProps = {}) => ({'data-testid': 'dropzone-root', ...rootProps}),
                })}
            </div>
        );
    });
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const componentsMock: any = jest.requireMock('sulu-admin-bundle/components');
const dropzoneMock: any = jest.requireMock('react-dropzone');

const getButtonPropsByIcon = (icon) => findMockCallArg(componentsMock.Button, ([props]) => props.icon === icon);
const getDropzoneProps = () => getLatestMockProps(dropzoneMock);

const createMediaUploadStore = (media) => new MediaUploadStore(media, observable.box('en'));

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render a SingleMediaUpload', () => {
    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    });

    const {asFragment} = render(
        <SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaUpload in disabled state', () => {
    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    });

    const {asFragment} = render(
        <SingleMediaUpload
            collectionId={5}
            disabled={true}
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaUpload with an error message from the MediaUploadStore', () => {
    const mediaUploadStore = createMediaUploadStore(
        {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: '', adminUrl: ''}
    );
    mediaUploadStore.error = {
        code: 5003,
        detail: 'The uploaded file exceeds the configured maximum filesize.',
    };

    const {asFragment} = render(
        <SingleMediaUpload
            collectionId={5}
            disabled={true}
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaUpload with an empty icon if no image is passed', () => {
    const mediaUploadStore = createMediaUploadStore(undefined);
    mediaUploadStore.getThumbnail.mockReturnValue(undefined);

    const {asFragment} = render(
        <SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaUpload with the round skin', () => {
    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    });

    const {asFragment} = render(
        <SingleMediaUpload
            collectionId={5}
            mediaUploadStore={mediaUploadStore}
            skin="round"
            uploadText="Upload media"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaUpload with a different image size', () => {
    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    });

    const {asFragment} = render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a SingleMediaUpload without delete and download button', () => {
    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    });

    const {asFragment} = render(
        <SingleMediaUpload
            deletable={false}
            downloadable={false}
            mediaUploadStore={mediaUploadStore}
            uploadText="Test"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Call update on MediaUploadStore if id is given and drop event occurs', async() => {
    const uploadCompleteSpy = jest.fn();
    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    });
    const promise = Promise.resolve({});
    mediaUploadStore.update.mockReturnValue(promise);

    render(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = {name: 'test.jpg'};
    getDropzoneProps().onDrop([file]);

    expect(mediaUploadStore.update).toBeCalledWith(file);

    await promise;
    expect(uploadCompleteSpy).toBeCalledWith({});
});

test('Call create with passed collectionId if id is not given and drop event occurs', async() => {
    const uploadCompleteSpy = jest.fn();
    const mediaUploadStore = createMediaUploadStore(undefined);

    const promise = Promise.resolve({});
    mediaUploadStore.create.mockReturnValue(promise);

    render(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = {name: 'test.jpg'};
    getDropzoneProps().onDrop([file]);

    expect(mediaUploadStore.create).toBeCalledWith(7, file);

    await promise;
    expect(uploadCompleteSpy).toBeCalledWith({});
});

test('Download the image when the download button is clicked', () => {
    delete window.location;
    window.location = {assign: jest.fn()};

    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: 'test.jpg',
        adminUrl: '',
    });

    render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    const downloadButton = getButtonPropsByIcon('su-download');
    if (!downloadButton) {
        throw new Error('Expected download button');
    }

    downloadButton.onClick();
    expect(window.location.assign).toBeCalledWith('test.jpg');
});

test('Delete the image when the delete button is clicked and the overlay is confirmed', async() => {
    const mediaUploadStore = createMediaUploadStore({
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
        adminUrl: '',
    });
    const deletePromise = Promise.resolve();
    mediaUploadStore.delete.mockReturnValue(deletePromise);

    const uploadCompleteSpy = jest.fn();

    render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const deleteButton = getButtonPropsByIcon('su-trash-alt');
    if (!deleteButton) {
        throw new Error('Expected delete button');
    }

    act(() => {
        deleteButton.onClick();
    });

    expect(getLatestMockProps(componentsMock.Dialog).open).toEqual(true);
    expect(getLatestMockProps(componentsMock.Dialog).confirmLoading).toEqual(false);

    act(() => {
        getLatestMockProps(componentsMock.Dialog).onConfirm();
    });

    expect(mediaUploadStore.delete).toBeCalled();
    expect(getLatestMockProps(componentsMock.Dialog).confirmLoading).toEqual(true);

    await deletePromise;
    await waitFor(() => expect(uploadCompleteSpy).toBeCalled());
    expect(getLatestMockProps(componentsMock.Dialog).open).toEqual(false);
    expect(getLatestMockProps(componentsMock.Dialog).confirmLoading).toEqual(false);
});

test('Throw exception if neither the collectionId nor the media is given', () => {
    const mediaUploadStore = createMediaUploadStore(undefined);

    expect(() => new SingleMediaUpload(({mediaUploadStore, uploadText: 'UploadMedia'}: any))).toThrow('"collectionId"');
});
