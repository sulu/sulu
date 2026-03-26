// @flow
import React from 'react';
import {act, render} from '@testing-library/react';
import {observable} from 'mobx';
import Mousetrap from 'mousetrap';
import Dropzone from 'react-dropzone';
import {SingleListOverlay} from 'sulu-admin-bundle/containers';
import MultiMediaDropzone from '../MultiMediaDropzone';
import MediaUploadStore from '../../../stores/MediaUploadStore';
import MediaItem from '../MediaItem';

jest.useFakeTimers();

let mockedMediaUploadStorePromises = [];
beforeEach(() => {
    mockedMediaUploadStorePromises = [];
    jest.clearAllMocks();
    // $FlowFixMe
    Dropzone.__clearProps();
});

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

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

jest.mock('react-dropzone', () => {
    const React = require('react');
    const propsCalls = [];

    class Dropzone extends React.Component<any> {
        open = jest.fn();

        render() {
            propsCalls.push(this.props);

            const getRootProps = (rootProps = {}) => ({
                ...rootProps,
                role: rootProps.role || 'presentation',
            });

            const getInputProps = () => ({type: 'file'});

            return this.props.children({getInputProps, getRootProps});
        }
    }

    const dropzoneMock: any = Dropzone;
    dropzoneMock.__getLatestProps = () => propsCalls[propsCalls.length - 1];
    dropzoneMock.__clearProps = () => {
        propsCalls.length = 0;
    };

    return Dropzone;
});

jest.mock('../MediaItem', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(function() {
    return <div>single-list-overlay-mock</div>;
}));

function renderMultiMediaDropzone(props: Object = {}) {
    return render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box('en')}
            onClose={jest.fn()}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={false}
            {...props}
        >
            <div />
        </MultiMediaDropzone>
    );
}

function getLatestDropzoneProps() {
    // $FlowFixMe
    return Dropzone.__getLatestProps();
}

function getLatestSingleListOverlayProps() {
    const calls = ((SingleListOverlay: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

test('Render a MultiMediaDropzone', () => {
    const {container} = renderMultiMediaDropzone();

    expect(container).toMatchSnapshot();
});

test('Render the DropzoneOverlay when the open prop is set to true', () => {
    renderMultiMediaDropzone({open: true});

    expect(document.querySelector('.dropzoneOverlay')).not.toBeNull();
});

test('Component pass correct props to Dropzone component', () => {
    renderMultiMediaDropzone({
        accept: 'application/json',
        disabled: false,
        open: true,
    });

    expect(getLatestDropzoneProps()).toEqual(expect.objectContaining({
        accept: {'application/json': []},
        disabled: false,
        noClick: true,
    }));
});

test('Disable dropzone if disabled prop is set to true', () => {
    const {rerender} = renderMultiMediaDropzone({disabled: false, open: true});

    expect(getLatestDropzoneProps().disabled).toBeFalsy();

    rerender(
        <MultiMediaDropzone
            collectionId={3}
            disabled={true}
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

    expect(getLatestDropzoneProps().disabled).toBeTruthy();
});

test('Render media item in dropzone overlay while it is being uploaded', () => {
    const uploadSpy = jest.fn();

    renderMultiMediaDropzone({
        onUpload: uploadSpy,
        open: true,
    });

    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];

    act(() => {
        getLatestDropzoneProps().onDrop(files);
    });

    expect((MediaItem: any).mock.calls).toHaveLength(2);
});

test('Should display overlay for selecting collection when file is dropped and no collectionId is given', () => {
    renderMultiMediaDropzone({
        collectionId: undefined,
        open: true,
    });

    expect(getLatestSingleListOverlayProps().open).toBeFalsy();

    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];

    act(() => {
        getLatestDropzoneProps().onDrop(files);
    });

    expect(MediaUploadStore).not.toBeCalled();
    expect(getLatestSingleListOverlayProps().open).toBeTruthy();
});

test('Should upload media after selecting collection in overlay when file is dropped without collectionId', async() => {
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    renderMultiMediaDropzone({
        collectionId: undefined,
        onClose: closeSpy,
        onUpload: uploadSpy,
        open: true,
    });

    const files = [new File([''], 'fileA')];

    act(() => {
        getLatestDropzoneProps().onDrop(files);
    });

    act(() => {
        getLatestSingleListOverlayProps().onConfirm({id: 1234});
    });

    // $FlowFixMe
    const mediaUploadStore1 = MediaUploadStore.mock.instances[0];
    expect(mediaUploadStore1.create).toBeCalledWith(1234, files[0]);

    await Promise.allSettled(mockedMediaUploadStorePromises);

    act(() => {
        jest.runAllTimers();
    });

    expect(uploadSpy).toBeCalledWith([{id: 123}]);
    expect(closeSpy).toBeCalled();
});

test('Should not upload media when closing overlay for selecting collection after file is dropped', () => {
    const closeSpy = jest.fn();

    renderMultiMediaDropzone({
        collectionId: undefined,
        onClose: closeSpy,
        open: true,
    });

    const files = [new File([''], 'fileA')];

    act(() => {
        getLatestDropzoneProps().onDrop(files);
    });

    expect(MediaUploadStore).not.toBeCalled();
    expect(getLatestSingleListOverlayProps().open).toBeTruthy();

    act(() => {
        getLatestSingleListOverlayProps().onClose();
    });

    expect(MediaUploadStore).not.toBeCalled();
    expect(getLatestSingleListOverlayProps().open).toBeFalsy();
    expect(closeSpy).toBeCalled();
});

test('Should upload media when collectionId is set and file is dropped into the dropzone', async() => {
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    renderMultiMediaDropzone({
        collectionId: 3,
        onClose: closeSpy,
        onUpload: uploadSpy,
        open: true,
    });

    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];

    act(() => {
        getLatestDropzoneProps().onDrop(files);
    });

    // $FlowFixMe
    const mediaUploadStore1 = MediaUploadStore.mock.instances[0];
    // $FlowFixMe
    const mediaUploadStore2 = MediaUploadStore.mock.instances[1];

    expect(mediaUploadStore1.create).toBeCalledWith(3, files[0]);
    expect(mediaUploadStore2.create).toBeCalledWith(3, files[1]);
    expect(closeSpy).not.toBeCalled();

    await Promise.allSettled(mockedMediaUploadStorePromises);

    act(() => {
        jest.runAllTimers();
    });

    expect(uploadSpy).toBeCalledWith([
        {id: 123},
        {id: 123},
    ]);
    expect(closeSpy).toBeCalledWith();
});

test('Should fire onClose and onUploadError callback if an error happens when uploading media', async() => {
    const uploadErrorSpy = jest.fn();
    const closeSpy = jest.fn();

    renderMultiMediaDropzone({
        collectionId: 3,
        onClose: closeSpy,
        onUpload: jest.fn(),
        onUploadError: uploadErrorSpy,
        open: true,
    });

    act(() => {
        getLatestDropzoneProps().onDrop([
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

test('Should fire close callback when escape button is pressed', () => {
    const closeSpy = jest.fn();

    renderMultiMediaDropzone({
        collectionId: 3,
        onClose: closeSpy,
        open: true,
    });

    expect(closeSpy).not.toBeCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toBeCalledWith();
});
