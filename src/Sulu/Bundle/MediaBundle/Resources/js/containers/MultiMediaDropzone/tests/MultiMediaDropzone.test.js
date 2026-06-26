// @flow
import React from 'react';
import {observable} from 'mobx';
import Mousetrap from 'mousetrap';
import {SingleListOverlay} from 'sulu-admin-bundle/containers';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import MultiMediaDropzone from '../MultiMediaDropzone';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.useFakeTimers();

let mockedMediaUploadStorePromises = [];
beforeEach(() => {
    mockedMediaUploadStorePromises = [];
});

jest.mock('sulu-admin-bundle/utils/Translator');

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
        }
    });
}));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => jest.fn(function() {
    return <div>single-list-overlay-mock</div>;
}));

test('Render a MultiMediaDropzone', () => {
    const {container} = renderWithRef(
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

    expect(container).toMatchSnapshot();
});

test('Render the DropzoneOverlay when the open prop is set to true', () => {
    const {container} = renderWithRef(
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

    expect(container).toMatchSnapshot();
});

test('Component pass correct props to Dropzone component', () => {
    const {instance: multiMediaDropzone} = renderWithRef(
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

    expect(findElementByType(multiMediaDropzone.render(), 'Dropzone').props).toEqual(expect.objectContaining({
        accept: {'application/json': []},
        disabled: false,
        noClick: true,
    }));
});

test('Disable dropzone if disabled prop is set to true', () => {
    const {instance: multiMediaDropzone, rerender} = renderWithRef(
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

    expect(findElementByType(multiMediaDropzone.render(), 'Dropzone').props.disabled).toBeFalsy();

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

    expect(findElementByType(multiMediaDropzone.render(), 'Dropzone').props.disabled).toBeTruthy();
});

test('Render media item in dropzone overlay while it is being uploaded', () => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const {instance: multiMediaDropzone} = renderWithRef(
        <MultiMediaDropzone
            collectionId={3}
            locale={locale}
            onClose={jest.fn()}
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

    multiMediaDropzone.handleDrop(files);

    expect(multiMediaDropzone.createMediaItems()).toHaveLength(2);
});

test('Should display overlay for selecting collection when file is dropped and no collectionId is given', () => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    const {instance: multiMediaDropzone} = renderWithRef(
        <MultiMediaDropzone
            collectionId={undefined}
            locale={locale}
            onClose={closeSpy}
            onOpen={jest.fn()}
            onUpload={uploadSpy}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(findElementByType(multiMediaDropzone.render(), SingleListOverlay).props.open).toBeFalsy();

    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];
    findElementByType(multiMediaDropzone.render(), 'Dropzone').props.onDrop(files);

    expect(MediaUploadStore).not.toHaveBeenCalled();
    expect(findElementByType(multiMediaDropzone.render(), SingleListOverlay).props.open).toBeTruthy();
});

test('Should upload media after selecting collection in overlay when file is dropped without collectionId', () => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    const {instance: multiMediaDropzone} = renderWithRef(
        <MultiMediaDropzone
            collectionId={undefined}
            locale={locale}
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
    ];
    findElementByType(multiMediaDropzone.render(), 'Dropzone').props.onDrop(files);
    findElementByType(multiMediaDropzone.render(), SingleListOverlay).props.onConfirm({id: 1234});

    // $FlowFixMe
    const mediaUploadStore1 = MediaUploadStore.mock.instances[0];
    expect(mediaUploadStore1.create).toHaveBeenCalledWith(1234, files[0]);
    expect(multiMediaDropzone.mediaUploadStores.length).toBe(1);

    return Promise.allSettled(mockedMediaUploadStorePromises).then(() => {
        jest.runAllTimers();

        expect(uploadSpy).toHaveBeenCalledWith([{id: 123}]);
        expect(multiMediaDropzone.mediaUploadStores.length).toBe(0);
        expect(closeSpy).toHaveBeenCalled();
    });
});

test('Should not upload media when closing overlay for selecting collection after file is dropped', () => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    const {instance: multiMediaDropzone} = renderWithRef(
        <MultiMediaDropzone
            collectionId={undefined}
            locale={locale}
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
    ];
    findElementByType(multiMediaDropzone.render(), 'Dropzone').props.onDrop(files);

    expect(MediaUploadStore).not.toHaveBeenCalled();
    expect(findElementByType(multiMediaDropzone.render(), SingleListOverlay).props.open).toBeTruthy();

    findElementByType(multiMediaDropzone.render(), SingleListOverlay).props.onClose();

    expect(MediaUploadStore).not.toHaveBeenCalled();
    expect(findElementByType(multiMediaDropzone.render(), SingleListOverlay).props.open).toBeFalsy();
});

test('Should upload media when collectionId is set and file is dropped into the dropzone', () => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();

    const {instance: multiMediaDropzone} = renderWithRef(
        <MultiMediaDropzone
            collectionId={3}
            locale={locale}
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
    findElementByType(multiMediaDropzone.render(), 'Dropzone').props.onDrop(files);

    // $FlowFixMe
    const mediaUploadStore1 = MediaUploadStore.mock.instances[0];
    // $FlowFixMe
    const mediaUploadStore2 = MediaUploadStore.mock.instances[1];

    expect(mediaUploadStore1.create).toHaveBeenCalledWith(3, files[0]);
    expect(mediaUploadStore2.create).toHaveBeenCalledWith(3, files[1]);
    expect(multiMediaDropzone.mediaUploadStores.length).toBe(2);

    expect(closeSpy).not.toHaveBeenCalled();

    return Promise.allSettled(mockedMediaUploadStorePromises).then(() => {
        jest.runAllTimers();

        expect(uploadSpy).toHaveBeenCalledWith([
            {id: 123},
            {id: 123},
        ]);
        expect(multiMediaDropzone.mediaUploadStores.length).toBe(0);
        expect(closeSpy).toHaveBeenCalledWith();
    });
});

test('Should fire onClose and onUploadError callback if an error happens when uploading media', () => {
    const locale = observable.box('en');
    const uploadErrorSpy = jest.fn();
    const closeSpy = jest.fn();

    const {instance: multiMediaDropzone} = renderWithRef(
        <MultiMediaDropzone
            collectionId={3}
            locale={locale}
            onClose={closeSpy}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={uploadErrorSpy}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    findElementByType(multiMediaDropzone.render(), 'Dropzone').props.onDrop([
        new File([''], 'fileA'),
        new File([''], 'invalid-file'),
        new File([''], 'invalid-file'),
    ]);

    expect(closeSpy).not.toHaveBeenCalled();

    return Promise.allSettled(mockedMediaUploadStorePromises).then(() => {
        jest.runAllTimers();

        expect(closeSpy).toHaveBeenCalledWith();
        expect(multiMediaDropzone.mediaUploadStores.length).toBe(0);
        expect(uploadErrorSpy).toHaveBeenCalledWith(
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
});

test('Should fire close callback when escape button is pressed', () => {
    const locale = observable.box('en');
    const closeSpy = jest.fn();

    renderWithRef(
        <MultiMediaDropzone
            collectionId={3}
            locale={locale}
            onClose={closeSpy}
            onOpen={jest.fn()}
            onUpload={jest.fn()}
            onUploadError={jest.fn()}
            open={true}
        >
            <div />
        </MultiMediaDropzone>
    );

    expect(closeSpy).not.toHaveBeenCalled();
    Mousetrap.trigger('esc');
    expect(closeSpy).toHaveBeenCalledWith();
});
