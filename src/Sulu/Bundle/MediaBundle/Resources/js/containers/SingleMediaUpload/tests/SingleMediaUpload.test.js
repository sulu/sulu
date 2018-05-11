// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import SingleMediaUpload from '../SingleMediaUpload';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.id = 1;
    this.update = jest.fn();
    this.getThumbnail = jest.fn();
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

test('Render a SingleMediaUpload', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));

    expect(
        render(<SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="Upload media" />)
    ).toMatchSnapshot();
});

test('Call update on MediaUploadStore if id is given and drop event occurs', () => {
    const mediaUploadStore = new MediaUploadStore(new ResourceStore('media', 1));

    const singleMediaUpload = shallow(
        <SingleMediaUpload mediaUploadStore={mediaUploadStore} uploadText="Upload media" />
    );

    const file = {name: 'test.jpg'};
    singleMediaUpload.find('SingleMediaDropzone').prop('onDrop')(file);

    expect(mediaUploadStore.update).toBeCalledWith(file);
});
