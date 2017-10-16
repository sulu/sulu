/* eslint-disable flowtype/require-valid-file-annotation */
import {shallow, render} from 'enzyme';
import React from 'react';
import MediaCardAdapter from '../../adapters/MediaCardAdapter';

jest.mock('sulu-admin-bundle/services', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.copy_url':
                return 'Copy URL';
            case 'sulu_admin.download_masterfile':
                return 'Download master file';
        }
    },
}));

test('Render a basic Masonry view with MediaCards', () => {
    const thumbnails = {
        'sulu-260x': 'http://lorempixel.com/260/100',
        'sulu-100x100': 'http://lorempixel.com/100/100',
    };
    const data = [
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
        {
            id: 2,
            title: 'Title 1',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
    ];
    const mediaCardAdapter = render(
        <MediaCardAdapter
            data={data}
            selections={[]}
            showDownloadDropdown={true}
        />
    );

    expect(mediaCardAdapter).toMatchSnapshot();
});

test('MediaCard should call the the appropriate handler', () => {
    const mediaCardClickSpy = jest.fn();
    const mediaCardSelectionChangeSpy = jest.fn();
    const thumbnails = {
        'sulu-260x': 'http://lorempixel.com/260/100',
        'sulu-100x100': 'http://lorempixel.com/100/100',
    };
    const data = [
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
        {
            id: 2,
            title: 'Title 1',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails: thumbnails,
        },
    ];
    const mediaCardAdapter = shallow(
        <MediaCardAdapter
            data={data}
            selections={[]}
            onItemClick={mediaCardClickSpy}
            onItemSelectionChange={mediaCardSelectionChangeSpy}
        />
    );

    expect(mediaCardAdapter.find('MediaCard').get(0).props.onClick).toBe(mediaCardClickSpy);
    expect(mediaCardAdapter.find('MediaCard').get(0).props.onSelectionChange).toBe(mediaCardSelectionChangeSpy);
});
