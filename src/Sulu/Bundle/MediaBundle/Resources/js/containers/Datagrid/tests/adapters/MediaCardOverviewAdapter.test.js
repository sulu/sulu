/* eslint-disable flowtype/require-valid-file-annotation */
import {render} from 'enzyme';
import React from 'react';
import MediaCardOverviewAdapter from '../../adapters/MediaCardOverviewAdapter';

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

test('Render a basic Masonry view with the MediaCardOverviewAdapter', () => {
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
        <MediaCardOverviewAdapter
            data={data}
            selections={[]}
        />
    );

    expect(mediaCardAdapter).toMatchSnapshot();
});
