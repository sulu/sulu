// @flow
import React from 'react';
import {observable} from 'mobx';
import {act, render} from '@testing-library/react';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import MultiMediaDropzone from '../MultiMediaDropzone';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.useFakeTimers();

let mockedMediaUploadStorePromises = [];

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('react-dropzone', () => jest.fn(({children}) => children({
    getInputProps: () => ({}),
    getRootProps: (props) => props,
})));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.create = jest.fn((_, file) => {
        if (file.name === 'invalid-file') {
            const rejectPromise = Promise.reject({
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            });
            mockedMediaUploadStorePromises.push(rejectPromise);

            return rejectPromise;
        }

        const resolvePromise = Promise.resolve({
            id: 123,
        });
        mockedMediaUploadStorePromises.push(resolvePromise);

        return resolvePromise;
    });
    this.progress = 45;
    this.getThumbnail = jest.fn((size) => {
        switch (size) {
            case 'sulu-400x-inset':
                return 'http://lorempixel.com/400/250';
            default:
                return undefined;
        }
    });
}));

jest.mock('../DropzoneOverlay', () => jest.fn(() => null));
jest.mock('../MediaItem', () => jest.fn(() => null));
jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(() => null));

const DropzoneMock: any = jest.requireMock('react-dropzone');
const DropzoneOverlayMock: any = jest.requireMock('../DropzoneOverlay');
const SingleListOverlayMock: any = jest.requireMock('sulu-admin-bundle/containers/SingleListOverlay');
const MediaUploadStoreMock: any = jest.requireMock('../../../stores/MediaUploadStore');

const getCurrentMediaItemCount = () => React.Children.count(getLatestMockProps(DropzoneOverlayMock).children);

beforeEach(() => {
    mockedMediaUploadStorePromises = [];
    jest.clearAllMocks();
});

test('Render a MultiMediaDropzone', () => {
    const {asFragment} = render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box()}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={false}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render the DropzoneOverlay when the open prop is set to true', () => {
    render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box()}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(getLatestMockProps(DropzoneOverlayMock).open).toBe(true);
});

test('Component pass correct props to Dropzone component', () => {
    render(
        <MultiMediaDropzone
            accept="application/json"
            collectionId={3}
            disabled={false}
            locale={observable.box()}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(getLatestMockProps(DropzoneMock)).toEqual(expect.objectContaining({
        accept: {'application/json': []},
        disabled: false,
        noClick: true,
    }));
});

test('Disable dropzone if disabled prop is set to true', () => {
    const {rerender} = render(
        <MultiMediaDropzone
            collectionId={3}
            disabled={false}
            locale={observable.box()}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(getLatestMockProps(DropzoneMock).disabled).toBe(false);

    rerender(
        <MultiMediaDropzone
            collectionId={3}
            disabled={true}
            locale={observable.box()}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(getLatestMockProps(DropzoneMock).disabled).toBe(true);
});

test('Render media item in dropzone overlay while it is being uploaded', () => {
    render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );
    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];

    act(() => {
        getLatestMockProps(DropzoneMock).onDrop(files);
    });

    expect(getCurrentMediaItemCount()).toBe(2);
});

test('Should display overlay for selecting collection when file is dropped and no collectionId is given', () => {
    render(
        <MultiMediaDropzone
            collectionId={undefined}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(getLatestMockProps(SingleListOverlayMock).open).toBe(false);

    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];
    act(() => {
        getLatestMockProps(DropzoneMock).onDrop(files);
    });

    expect(MediaUploadStore).not.toBeCalled();
    expect(getLatestMockProps(SingleListOverlayMock).open).toBe(true);
});

test('Should upload media after selecting collection in overlay when file is dropped without collectionId', async() => {
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    render(
        <MultiMediaDropzone
            collectionId={undefined}
            locale={observable.box('en')}
            onClose={closeSpy}
            onOpen={jest.fn()}
            onUpload={uploadSpy}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    const files = [new File([''], 'fileA')];

    act(() => {
        getLatestMockProps(DropzoneMock).onDrop(files);
        getLatestMockProps(SingleListOverlayMock).onConfirm({id: 1234});
    });

    const mediaUploadStore1 = MediaUploadStoreMock.mock.instances[0];
    expect(mediaUploadStore1.create).toBeCalledWith(1234, files[0]);
    expect(getCurrentMediaItemCount()).toBe(1);

    await Promise.allSettled(mockedMediaUploadStorePromises);
    act(() => {
        jest.runAllTimers();
    });

    expect(uploadSpy).toBeCalledWith([{id: 123}]);
    expect(getCurrentMediaItemCount()).toBe(0);
    expect(closeSpy).toBeCalled();
});

test('Should not upload media when closing overlay for selecting collection after file is dropped', () => {
    render(
        <MultiMediaDropzone
            collectionId={undefined}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    const files = [new File([''], 'fileA')];
    act(() => {
        getLatestMockProps(DropzoneMock).onDrop(files);
    });

    expect(MediaUploadStore).not.toBeCalled();
    expect(getLatestMockProps(SingleListOverlayMock).open).toBe(true);

    act(() => {
        getLatestMockProps(SingleListOverlayMock).onClose();
    });

    expect(MediaUploadStore).not.toBeCalled();
    expect(getLatestMockProps(SingleListOverlayMock).open).toBe(false);
});

test('Should upload media when collectionId is set and file is dropped into the dropzone', async() => {
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box('en')}
            onClose={closeSpy}
            onOpen={jest.fn()}
            onUpload={uploadSpy}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );
    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];

    act(() => {
        getLatestMockProps(DropzoneMock).onDrop(files);
    });

    const mediaUploadStore1 = MediaUploadStoreMock.mock.instances[0];
    const mediaUploadStore2 = MediaUploadStoreMock.mock.instances[1];
    expect(mediaUploadStore1.create).toBeCalledWith(3, files[0]);
    expect(mediaUploadStore2.create).toBeCalledWith(3, files[1]);
    expect(getCurrentMediaItemCount()).toBe(2);
    expect(closeSpy).not.toBeCalled();

    await Promise.allSettled(mockedMediaUploadStorePromises);
    act(() => {
        jest.runAllTimers();
    });

    expect(uploadSpy).toBeCalledWith([
        {id: 123},
        {id: 123},
    ]);
    expect(getCurrentMediaItemCount()).toBe(0);
    expect(closeSpy).toBeCalledWith();
});

test('Should fire onClose and onUploadError callback if an error happens when uploading media', async() => {
    const uploadErrorSpy = jest.fn();
    const closeSpy = jest.fn();

    render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box('en')}
            onClose={closeSpy}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={uploadErrorSpy}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    act(() => {
        getLatestMockProps(DropzoneMock).onDrop([
            new File([''], 'fileA'),
            new File([''], 'invalid-file'),
            new File([''], 'invalid-file'),
        ]);
    });

    expect(closeSpy).not.toBeCalled();

    await Promise.allSettled(mockedMediaUploadStorePromises);
    act(() => {
        jest.runAllTimers();
    });

    expect(closeSpy).toBeCalledWith();
    expect(getCurrentMediaItemCount()).toBe(0);
    expect(uploadErrorSpy).toBeCalledWith(
        [
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
            {
                'code': 5003,
                'detail': 'The uploaded file exceeds the configured maximum filesize.',
            },
        ]
    );
});

test('Should pass close callback to DropzoneOverlay', () => {
    const closeSpy = jest.fn();

    render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box('en')}
            onClose={closeSpy}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(closeSpy).not.toBeCalled();
    act(() => {
        getLatestMockProps(DropzoneOverlayMock).onClose();
    });
    expect(closeSpy).toBeCalledWith();
});
