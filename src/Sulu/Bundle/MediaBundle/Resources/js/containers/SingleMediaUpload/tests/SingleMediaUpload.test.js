// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
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

jest.mock('sulu-admin-bundle/utils/Translator');

function getFileInput(container): HTMLInputElement {
    const input = container.querySelector('input[type="file"]');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Expected file input');
    }

    return input;
}

function queryDeleteDialog(): ?HTMLElement {
    const title = screen.queryByText('sulu_media.delete_media_warning_title');

    if (!title) {
        return null;
    }

    const dialog = title.closest('.dialogContainer');

    return dialog instanceof HTMLElement ? dialog : null;
}

function getDeleteDialog(): HTMLElement {
    const dialog = queryDeleteDialog();

    if (!dialog) {
        throw new Error('Expected delete dialog');
    }

    return dialog;
}

function expectDeleteDialogClosed() {
    const dialog = queryDeleteDialog();

    if (!dialog) {
        expect(dialog).toBeNull();
        return;
    }

    expect(dialog).not.toHaveClass('open');
}

test('Render a SingleMediaUpload', () => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    const {container} = render(
        <SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render a SingleMediaUpload in disabled state', () => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    const {container} = render(
        <SingleMediaUpload
            collectionId={5}
            disabled={true}
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render a SingleMediaUpload with an error message from the MediaUploadStore', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, locale: 'en', mimeType: 'image/jpeg', title: 'test', thumbnails: {}, url: '', adminUrl: ''},
        observable.box('en')
    );

    mediaUploadStore.error = {
        'code': 5003,
        'detail': 'The uploaded file exceeds the configured maximum filesize.',
    };

    const {container} = render(
        <SingleMediaUpload
            collectionId={5}
            disabled={true}
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render a SingleMediaUpload with an empty icon if no image is passed', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );
    mediaUploadStore.getThumbnail.mockReturnValue(undefined);

    const {container} = render(
        <SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render a SingleMediaUpload with the round skin', () => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    const {container} = render(
        <SingleMediaUpload
            collectionId={5}
            mediaUploadStore={mediaUploadStore}
            skin="round"
            uploadText="Upload media"
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render a SingleMediaUpload with a different image size', () => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    const {container} = render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Render a SingleMediaUpload without delete and download button', () => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    const {container} = render(
        <SingleMediaUpload
            deletable={false}
            downloadable={false}
            mediaUploadStore={mediaUploadStore}
            uploadText="Test"
        />
    );

    expect(container.innerHTML).toMatchSnapshot();
});

test('Call update on MediaUploadStore if id is given and drop event occurs', async() => {
    const uploadCompleteSpy = jest.fn();
    const user = userEvent.setup();
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );

    const promise = Promise.resolve({});
    mediaUploadStore.update.mockReturnValue(promise);

    const {container} = render(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    await user.upload(getFileInput(container), file);

    expect(mediaUploadStore.update).toHaveBeenCalledWith(file);

    await promise;
    expect(uploadCompleteSpy).toHaveBeenCalledWith({});
});

test('Call create with passed collectionId if id is not given and drop event occurs', async() => {
    const uploadCompleteSpy = jest.fn();
    const user = userEvent.setup();
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    const promise = Promise.resolve({});
    mediaUploadStore.create.mockReturnValue(promise);

    const {container} = render(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    await user.upload(getFileInput(container), file);

    expect(mediaUploadStore.create).toHaveBeenCalledWith(7, file);

    await promise;
    expect(uploadCompleteSpy).toHaveBeenCalledWith({});
});

test('Download the image when the download button is clicked', async() => {
    const assignSpy = jest.fn();
    const user = userEvent.setup();
    window.location.assign = assignSpy;

    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: 'test.jpg',
            adminUrl: '',
        },
        observable.box('en')
    );

    render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    await user.click(screen.getByRole('button', {name: /sulu_media.download_media/}));
    expect(assignSpy).toHaveBeenCalledWith('test.jpg');
});

test('Delete the image when the delete button is clicked and the overlay is confirmed', async() => {
    const mediaUploadStore = new MediaUploadStore(
        {
            id: 1,
            locale: 'en',
            mimeType: 'image/jpeg',
            title: 'test',
            thumbnails: {},
            url: '',
            adminUrl: '',
        },
        observable.box('en')
    );
    let resolveDeletePromise: (media?: Object) => void = () => {};
    const deletePromise = new Promise((resolve) => {
        resolveDeletePromise = (media) => resolve(media);
    });
    const user = userEvent.setup();
    mediaUploadStore.delete.mockReturnValue(deletePromise);

    const uploadCompleteSpy = jest.fn();

    render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    expectDeleteDialogClosed();

    await user.click(screen.getByRole('button', {name: /sulu_media.delete_media/}));
    expect(getDeleteDialog()).toHaveClass('open');
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeEnabled();

    await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(mediaUploadStore.delete).toHaveBeenCalled();
    expect(screen.getByRole('button', {name: 'sulu_admin.ok'})).toBeDisabled();

    resolveDeletePromise({id: 1});
    await deletePromise;

    expect(uploadCompleteSpy).toHaveBeenCalledWith({id: 1});
    await waitFor(() => expectDeleteDialogClosed());
});

test('Throw exception if neither the collectionId nor the media is given', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );
    expect(() => render(
        <SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="UploadMedia" />
    )).toThrow('"collectionId"');
});
