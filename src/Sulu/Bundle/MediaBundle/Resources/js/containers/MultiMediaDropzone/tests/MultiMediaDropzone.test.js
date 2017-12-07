// @flow
import React from 'react';
import {observable} from 'mobx';
import {render, shallow} from 'enzyme';
import MultiMediaDropzone from '../MultiMediaDropzone';

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

test('Render a MultiMediaDropzone', () => {
    expect(render(
        <MultiMediaDropzone
            collectionId={3}
            locale={observable()}
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
            locale={observable()}
            onUpload={jest.fn()}
        >
            <div />
        </MultiMediaDropzone>
    );

    multiMediaDropzone.instance().openOverlay();

    expect(multiMediaDropzone.render()).toMatchSnapshot();
});
