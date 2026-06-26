// @flow
import React from 'react';
import {observable} from 'mobx';
import {render} from '@testing-library/react';
import {findAllElementsByType, findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
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

test('Call update on MediaUploadStore if id is given and drop event occurs', () => {
    const uploadCompleteSpy = jest.fn();
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

    const {instance} = renderWithRef(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = {name: 'test.jpg'};
    findElementByType(instance.render(), 'SingleMediaDropzone').props.onDrop(file);

    expect(mediaUploadStore.update).toHaveBeenCalledWith(file);

    return promise.then(() => {
        expect(uploadCompleteSpy).toHaveBeenCalledWith({});
    });
});

test('Call create with passed collectionId if id is not given and drop event occurs', () => {
    const uploadCompleteSpy = jest.fn();
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );

    const promise = Promise.resolve({});
    mediaUploadStore.create.mockReturnValue(promise);

    const {instance} = renderWithRef(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = {name: 'test.jpg'};
    findElementByType(instance.render(), 'SingleMediaDropzone').props.onDrop(file);

    expect(mediaUploadStore.create).toHaveBeenCalledWith(7, file);

    return promise.then(() => {
        expect(uploadCompleteSpy).toHaveBeenCalledWith({});
    });
});

test('Download the image when the download button is clicked', () => {
    const assignSpy = jest.fn();
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

    const {instance} = renderWithRef(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    const downloadButton = findAllElementsByType(instance.render(), 'Button')
        .find((button) => button.props.icon === 'su-download');

    expect(downloadButton).toBeDefined();
    downloadButton && downloadButton.props.onClick();
    expect(assignSpy).toHaveBeenCalledWith('test.jpg');
});

test('Delete the image when the delete button is clicked and the overlay is confirmed', () => {
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
    const deletePromise = Promise.resolve();
    mediaUploadStore.delete.mockReturnValue(deletePromise);

    const uploadCompleteSpy = jest.fn();

    const {instance, rerender} = renderWithRef(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const deleteButton = findAllElementsByType(instance.render(), 'Button')
        .find((button) => button.props.icon === 'su-trash-alt');

    expect(deleteButton).toBeDefined();
    deleteButton && deleteButton.props.onClick();
    rerender(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );
    expect(findElementByType(instance.render(), 'Dialog').props.open).toEqual(true);
    expect(findElementByType(instance.render(), 'Dialog').props.confirmLoading).toEqual(false);

    findElementByType(instance.render(), 'Dialog').props.onConfirm();

    expect(mediaUploadStore.delete).toHaveBeenCalled();
    rerender(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );
    expect(findElementByType(instance.render(), 'Dialog').props.confirmLoading).toEqual(true);

    return deletePromise.then(() => {
        expect(uploadCompleteSpy).toHaveBeenCalled();
        rerender(
            <SingleMediaUpload
                mediaUploadStore={mediaUploadStore}
                onUploadComplete={uploadCompleteSpy}
                uploadText="Upload media"
            />
        );
        expect(findElementByType(instance.render(), 'Dialog').props.open).toEqual(false);
        expect(findElementByType(instance.render(), 'Dialog').props.confirmLoading).toEqual(false);
    });
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
