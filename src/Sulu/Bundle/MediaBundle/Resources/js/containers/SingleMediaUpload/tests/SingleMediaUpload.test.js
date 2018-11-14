// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, shallow} from 'enzyme';
import SingleMediaUpload from '../SingleMediaUpload';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function(media) {
    this.id = media ? media.id : undefined;
    this.create = jest.fn();
    this.update = jest.fn();
    this.delete = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
    this.downloadUrl = media ? media.url : undefined;
    this.media = media;
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

test('Render a SingleMediaUpload', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: ''},
        observable.box('en')
    );

    expect(
        render(<SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />)
    ).toMatchSnapshot();
});

test('Render a SingleMediaUpload in disabled state', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: ''},
        observable.box('en')
    );

    expect(render(
        <SingleMediaUpload
            collectionId={5}
            disabled={true}
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaUpload with an empty icon if no image is passed', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );
    mediaUploadStore.getThumbnail.mockReturnValue(undefined);

    expect(
        render(<SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />)
    ).toMatchSnapshot();
});

test('Render a SingleMediaUpload with the round skin', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: ''},
        observable.box('en')
    );

    expect(render(
        <SingleMediaUpload
            collectionId={5}
            mediaUploadStore={mediaUploadStore}
            skin="round"
            uploadText="Upload media"
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaUpload with a different image size', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: ''},
        observable.box('en')
    );

    expect(render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    )).toMatchSnapshot();
});

test('Render a SingleMediaUpload without delete and download button', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: ''},
        observable.box('en')
    );

    expect(render(
        <SingleMediaUpload
            deletable={false}
            downloadable={false}
            mediaUploadStore={mediaUploadStore}
            uploadText="Test"
        />
    )).toMatchSnapshot();
});

test('Call update on MediaUploadStore if id is given and drop event occurs', () => {
    const uploadCompleteSpy = jest.fn();
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: ''},
        observable.box('en')
    );

    const promise = Promise.resolve({});
    mediaUploadStore.update.mockReturnValue(promise);

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = {name: 'test.jpg'};
    singleMediaUpload.find('SingleMediaDropzone').prop('onDrop')(file);

    expect(mediaUploadStore.update).toBeCalledWith(file);

    return promise.then(() => {
        expect(uploadCompleteSpy).toBeCalledWith({});
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

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            collectionId={7}
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    const file = {name: 'test.jpg'};
    singleMediaUpload.find('SingleMediaDropzone').prop('onDrop')(file);

    expect(mediaUploadStore.create).toBeCalledWith(7, file);

    return promise.then(() => {
        expect(uploadCompleteSpy).toBeCalledWith({});
    });
});

test('Download the image when the download button is clicked', () => {
    window.location.assign = jest.fn();

    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: 'test.jpg'},
        observable.box('en')
    );

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    );

    singleMediaUpload.find('Button[icon="su-download"]').simulate('click');
    expect(window.location.assign).toBeCalledWith('test.jpg');
});

test('Delete the image when the delete button is clicked and the overlay is confirmed', () => {
    const mediaUploadStore = new MediaUploadStore(
        {id: 1, mimeType: 'image/jpeg', thumbnails: {}, url: ''},
        observable.box('en')
    );
    const deletePromise = Promise.resolve();
    mediaUploadStore.delete.mockReturnValue(deletePromise);

    const uploadCompleteSpy = jest.fn();

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            onUploadComplete={uploadCompleteSpy}
            uploadText="Upload media"
        />
    );

    singleMediaUpload.find('Button[icon="su-trash-alt"]').simulate('click');
    expect(singleMediaUpload.find('Dialog').prop('open')).toEqual(true);
    expect(singleMediaUpload.find('Dialog').prop('confirmLoading')).toEqual(false);

    singleMediaUpload.find('Dialog').prop('onConfirm')();

    expect(mediaUploadStore.delete).toBeCalled();
    singleMediaUpload.update();
    expect(singleMediaUpload.find('Dialog').prop('confirmLoading')).toEqual(true);

    return deletePromise.then(() => {
        expect(uploadCompleteSpy).toBeCalled();
        singleMediaUpload.update();
        expect(singleMediaUpload.find('Dialog').prop('open')).toEqual(false);
        expect(singleMediaUpload.find('Dialog').prop('confirmLoading')).toEqual(false);
    });
});

test('Throw exception if neither the collectionId nor the media is given', () => {
    const mediaUploadStore = new MediaUploadStore(
        undefined,
        observable.box('en')
    );
    expect(() => shallow(
        <SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="UploadMedia" />
    )).toThrow('"collectionId"');
});
