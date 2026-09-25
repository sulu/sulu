// @flow
import React from 'react';
import {observable} from 'mobx';
import Mousetrap from 'mousetrap';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MultiMediaDropzone from '../MultiMediaDropzone';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.useFakeTimers();

let mockedMediaUploadStorePromises = [];
let mockMediaUploadStoreInstances = [];
beforeEach(() => {
    mockedMediaUploadStorePromises = [];
    mockMediaUploadStoreInstances = [];
});

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    mockMediaUploadStoreInstances.push(this);
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
    this.uploading = true;
    this.getThumbnail = jest.fn((size) => {
        switch (size) {
            case 'sulu-400x-inset':
                return 'http://lorempixel.com/400/250';
        }
    });
}));

jest.mock('sulu-admin-bundle/containers/SingleListOverlay', () => {
    const React = require('react');

    return class SingleListOverlayMock extends React.Component<any> {
        handleConfirm = () => {
            this.props.onConfirm({id: 1234});
        };

        render() {
            const {onClose, open, title} = this.props;

            return open
                ? (
                    <div aria-label={title} role="dialog">
                        <button onClick={onClose} type="button">close-collection-selection</button>
                        <button onClick={this.handleConfirm} type="button">
                            confirm-collection-selection
                        </button>
                    </div>
                )
                : null;
        }
    };
});

function createUser() {
    return userEvent.setup({advanceTimers: jest.advanceTimersByTime});
}

function getFileInput(container): HTMLInputElement {
    const input = container.querySelector('input[type="file"]');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Expected file input');
    }

    return input;
}

test('Render a MultiMediaDropzone', () => {
    const {container} = render(
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
    const {container} = render(
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
    expect(screen.getByText('sulu_media.drop_files_to_upload')).toBeInTheDocument();
    expect(screen.getByText('sulu_media.click_here_to_upload')).toBeInTheDocument();
});

test('Component pass correct props to Dropzone component', async() => {
    const user = createUser();
    const {container} = render(
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

    const input = getFileInput(container);
    const inputClickSpy = jest.spyOn(input, 'click');

    expect(input).toHaveAttribute('accept', 'application/json');
    expect(input).toBeEnabled();

    await user.click(screen.getByRole('presentation'));
    expect(inputClickSpy).not.toHaveBeenCalled();
});

test('Disable dropzone if disabled prop is set to true', async() => {
    const user = createUser();
    const {container, rerender} = render(
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

    expect(getFileInput(container)).toBeEnabled();

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

    await user.upload(getFileInput(container), new File([''], 'fileA'));
    expect(MediaUploadStore).not.toHaveBeenCalled();
});

test('Render media item in dropzone overlay while it is being uploaded', async() => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const user = createUser();
    const {container} = render(
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

    await user.upload(getFileInput(container), files);

    expect(screen.getAllByRole('img')).toHaveLength(2);
    expect(document.querySelectorAll('.progressbarContainer')).toHaveLength(2);
});

test('Should display overlay for selecting collection when file is dropped and no collectionId is given', async() => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();
    const user = createUser();

    const {container} = render(
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

    expect(screen.queryByRole('dialog', {name: 'sulu_media.select_collection_for_upload'})).not.toBeInTheDocument();

    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];
    await user.upload(getFileInput(container), files);

    expect(MediaUploadStore).not.toHaveBeenCalled();
    expect(screen.getByRole('dialog', {name: 'sulu_media.select_collection_for_upload'})).toBeInTheDocument();
});

test('Should upload media after selecting collection in overlay when file is dropped without collectionId', async() => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();
    const user = createUser();

    const {container} = render(
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
    await user.upload(getFileInput(container), files);
    await user.click(screen.getByRole('button', {name: 'confirm-collection-selection'}));

    const mediaUploadStore1 = mockMediaUploadStoreInstances[0];
    expect(mediaUploadStore1.create).toHaveBeenCalledWith(1234, files[0]);
    expect(screen.getAllByRole('img')).toHaveLength(1);

    await Promise.allSettled(mockedMediaUploadStorePromises);
    jest.runAllTimers();

    expect(uploadSpy).toHaveBeenCalledWith([{id: 123}]);
    expect(closeSpy).toHaveBeenCalled();
});

test('Should not upload media when closing overlay for selecting collection after file is dropped', async() => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();
    const user = createUser();

    const {container} = render(
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
    await user.upload(getFileInput(container), files);

    expect(MediaUploadStore).not.toHaveBeenCalled();
    expect(screen.getByRole('dialog', {name: 'sulu_media.select_collection_for_upload'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'close-collection-selection'}));

    expect(MediaUploadStore).not.toHaveBeenCalled();
    expect(screen.queryByRole('dialog', {name: 'sulu_media.select_collection_for_upload'})).not.toBeInTheDocument();
});

test('Should upload media when collectionId is set and file is dropped into the dropzone', async() => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const closeSpy = jest.fn();
    const user = createUser();

    const {container} = render(
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
    await user.upload(getFileInput(container), files);

    const mediaUploadStore1 = mockMediaUploadStoreInstances[0];
    const mediaUploadStore2 = mockMediaUploadStoreInstances[1];

    expect(mediaUploadStore1.create).toHaveBeenCalledWith(3, files[0]);
    expect(mediaUploadStore2.create).toHaveBeenCalledWith(3, files[1]);
    expect(screen.getAllByRole('img')).toHaveLength(2);

    expect(closeSpy).not.toHaveBeenCalled();

    await Promise.allSettled(mockedMediaUploadStorePromises);
    jest.runAllTimers();

    expect(uploadSpy).toHaveBeenCalledWith([
        {id: 123},
        {id: 123},
    ]);
    expect(closeSpy).toHaveBeenCalledWith();
});

test('Should fire onClose and onUploadError callback if an error happens when uploading media', async() => {
    const locale = observable.box('en');
    const uploadErrorSpy = jest.fn();
    const closeSpy = jest.fn();
    const user = createUser();

    const {container} = render(
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

    await user.upload(getFileInput(container), [
        new File([''], 'fileA'),
        new File([''], 'invalid-file'),
        new File([''], 'invalid-file'),
    ]);

    expect(closeSpy).not.toHaveBeenCalled();

    await Promise.allSettled(mockedMediaUploadStorePromises);
    jest.runAllTimers();

    expect(closeSpy).toHaveBeenCalledWith();
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

test('Should fire close callback when escape button is pressed', () => {
    const locale = observable.box('en');
    const closeSpy = jest.fn();

    render(
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
