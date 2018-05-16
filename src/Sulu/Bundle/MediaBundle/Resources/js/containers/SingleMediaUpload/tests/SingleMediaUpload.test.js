// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import SingleMediaUpload from '../SingleMediaUpload';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function(resourceStore) {
    this.id = resourceStore.id;
    this.create = jest.fn();
    this.update = jest.fn();
    this.delete = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id) {
        this.id = id;
    }),
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

test('Render a SingleMediaUpload', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));

    expect(
        render(<SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />)
    ).toMatchSnapshot();
});

test('Render a SingleMediaUpload with an empty icon if no image is passed', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media'));
    mediaUploadStore.getThumbnail.mockReturnValue(undefined);

    expect(
        render(<SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />)
    ).toMatchSnapshot();
});

test('Render a SingleMediaUpload with the round skin', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));

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
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));

    expect(render(
        <SingleMediaUpload
            mediaUploadStore={mediaUploadStore}
            uploadText="Upload media"
        />
    )).toMatchSnapshot();
});

test('Call update on MediaUploadStore if id is given and drop event occurs', () => {
    const uploadCompleteSpy = jest.fn();
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));

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
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media'));

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

test('Delete the image when the delete button is clicked', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));
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

    singleMediaUpload.find('button').simulate('click');

    expect(mediaUploadStore.delete).toBeCalled();

    return deletePromise.then(() => {
        expect(uploadCompleteSpy).toBeCalled();
    });
});

test('Throw exception if neither the collectionId nor the id from the image is given', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media'));
    expect(() => shallow(
        <SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="UploadMedia" />
    )).toThrow('"collectionId"');
});
