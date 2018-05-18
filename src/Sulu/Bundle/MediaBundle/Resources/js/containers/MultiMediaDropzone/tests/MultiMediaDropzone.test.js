/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {render, shallow} from 'enzyme';
import MultiMediaDropzone from '../MultiMediaDropzone';
import MediaUploadStore from '../../../stores/MediaUploadStore';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.drop_files_to_upload':
                return 'Upload files by dropping them here';
            case 'sulu_media.click_here_to_upload':
                return 'or click here to upload';
        }
    },
}));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.create = jest.fn().mockReturnValue(Promise.resolve({
        id: 123,
    }));
    this.progress = 45;
    this.getThumbnail = jest.fn((size) => {
        switch (size) {
            case 'sulu-400x-inset':
                return 'http://lorempixel.com/400/250';
        }
    });
}));

test('Render a MultiMediaDropzone', () => {
    expect(render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box()}
            onUpload={jest.fn()}
        >
            <div />
        </MultiMediaDropzone>
    )).toMatchSnapshot();
});

test('Render a MultiMediaDropzone while the overlay is visible', () => {
    const multiMediaDropzone = shallow(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable.box()}
            onUpload={jest.fn()}
        >
            <div />
        </MultiMediaDropzone>
    );

    multiMediaDropzone.instance().openOverlay();
    multiMediaDropzone.update();

    expect(multiMediaDropzone.render()).toMatchSnapshot();
});

test('Render a MultiMediaDropzone while media is uploaded', () => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const multiMediaDropzone = shallow(
        <MultiMediaDropzone
            collectionId={3}
            locale={locale}
            onUpload={uploadSpy}
        >
            <div />
        </MultiMediaDropzone>
    );
    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];

    multiMediaDropzone.instance().openOverlay();
    multiMediaDropzone.instance().handleDrop(files);
    multiMediaDropzone.update();

    expect(multiMediaDropzone.render()).toMatchSnapshot();
});

test('Should upload media when it is dropped on the dropzone', (done) => {
    const locale = observable.box('en');
    const uploadSpy = jest.fn();
    const multiMediaDropzone = shallow(
        <MultiMediaDropzone
            collectionId={3}
            locale={locale}
            onUpload={uploadSpy}
        >
            <div />
        </MultiMediaDropzone>
    );
    const multiMediaDropzoneInstance = multiMediaDropzone.instance();
    const files = [
        new File([''], 'fileA'),
        new File([''], 'fileB'),
    ];

    multiMediaDropzoneInstance.openOverlay();
    multiMediaDropzoneInstance.handleDrop(files);

    expect(MediaUploadStore.mock.instances[0].create).toBeCalledWith(3, files[0]);
    expect(MediaUploadStore.mock.instances[1].create).toBeCalledWith(3, files[1]);
    expect(multiMediaDropzoneInstance.mediaUploadStores.length).toBe(2);

    setTimeout(() => {
        expect(uploadSpy).toBeCalledWith([
            {id: 123},
            {id: 123},
        ]);
        expect(multiMediaDropzoneInstance.mediaUploadStores.length).toBe(0);
        done();
    }, 1100);
});
