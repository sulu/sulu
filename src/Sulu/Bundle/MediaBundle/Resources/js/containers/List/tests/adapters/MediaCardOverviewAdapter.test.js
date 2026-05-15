// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {listAdapterDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaCardAdapter from '../../adapters/MediaCardAdapter';
import MediaCardOverviewAdapter from '../../adapters/MediaCardOverviewAdapter';

jest.mock('sulu-admin-bundle/services/initializer', () => jest.fn());
jest.mock('../../adapters/MediaCardAdapter', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_media.copy_url':
                return 'Copy URL';
            case 'sulu_media.download_masterfile':
                return 'Download master file';
        }
    },
}));

test('Render a basic Masonry view with the MediaCardOverviewAdapter', () => {
    const thumbnails = {
        'sulu-240x': 'http://lorempixel.com/240/100',
        'sulu-100x100': 'http://lorempixel.com/100/100',
    };
    const data = [
        {
            id: 1,
            title: 'Title 1',
            mimeType: 'image/png',
            size: 12345,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
        {
            id: 2,
            title: 'Title 1',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
    ];
    render(
        <MediaCardOverviewAdapter
            {...listAdapterDefaultProps}
            data={data}
            onItemSelectionChange={jest.fn()}
            page={2}
            pageCount={5}
        />
    );
    const mediaCardAdapterProps = (MediaCardAdapter: any).mock.calls[0][0];

    expect(mediaCardAdapterProps.icon).toEqual('su-pen');
    expect(mediaCardAdapterProps.data).toEqual(data);
    expect(mediaCardAdapterProps.page).toEqual(2);
    expect(mediaCardAdapterProps.pageCount).toEqual(5);
});
