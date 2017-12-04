// @flow
import React from 'react';
import {render, shallow} from 'enzyme';
import MultiMediaDropzone from '../MultiMediaDropzone';

jest.mock('sulu-admin-bundle/services', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_media.drop_files_to_upload':
                return 'Upload files by dropping them here';
            case 'sulu_media.click_here_to_upload':
                return 'or click here to upload';
        }
    },
}));

test('Render an MultiMediaDropzone', () => {
    expect(render(
        <MultiMediaDropzone>
            <div />
        </MultiMediaDropzone>
    )).toMatchSnapshot();
});

test('Render an MultiMediaDropzone while the overlay is visible', () => {
    const multiMediaDropzone = shallow(
        <MultiMediaDropzone>
            <div />
        </MultiMediaDropzone>
    );

    multiMediaDropzone.instance().openOverlay();

    expect(multiMediaDropzone.render()).toMatchSnapshot();
});
