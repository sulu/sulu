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
    this.getThumbnail = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(function(resourceKey, id) {
        this.id = id;
    }),
}));

test('Render a SingleMediaUpload', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));

    expect(
        render(<SingleMediaUpload collectionId={5} mediaUploadStore={mediaUploadStore} uploadText="Upload media" />)
    ).toMatchSnapshot();
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

test('Throw exception if neither the collectionId nor the id from the image is given', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media'));
    expect(() => shallow(
        <SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="UploadMedia" />
    )).toThrow('"collectionId"');
});
