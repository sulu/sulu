// @flow
import {render} from 'enzyme';
import React from 'react';
import MediaCardOverviewAdapter from '../../adapters/MediaCardOverviewAdapter';

jest.mock('sulu-admin-bundle/services/Initializer', () => jest.fn());

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: function(key) {
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
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            loading={false}
            onAddClick={undefined}
            onAllSelectionChange={undefined}
            onCopyClick={undefined}
            onDeleteClick={jest.fn()}
            onItemActivation={jest.fn()}
            onItemClick={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onMoveClick={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
            page={2}
            pageCount={5}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(mediaCardAdapter).toMatchSnapshot();
});
