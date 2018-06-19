// @flow
import {shallow, render} from 'enzyme';
import React from 'react';
import MediaCardAdapter from '../../adapters/MediaCardAdapter';

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

test('Render a basic Masonry view with MediaCards', () => {
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
        <MediaCardAdapter
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            icon="su-pen"
            loading={false}
            onItemActivation={jest.fn()}
            onAllSelectionChange={undefined}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={1}
            pageCount={7}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(mediaCardAdapter).toMatchSnapshot();
});

test('MediaCard should call the the appropriate handler', () => {
    const mediaCardSelectionChangeSpy = jest.fn();
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
    const mediaCardAdapter = shallow(
        <MediaCardAdapter
            active={undefined}
            activeItems={[]}
            data={data}
            disabledIds={[]}
            icon="su-pen"
            loading={false}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemClick={mediaCardSelectionChangeSpy}
            onItemDeactivation={jest.fn()}
            onItemSelectionChange={mediaCardSelectionChangeSpy}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            page={3}
            pageCount={9}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );

    expect(mediaCardAdapter.find('MediaCard').get(0).props.onClick).toBe(mediaCardSelectionChangeSpy);
    expect(mediaCardAdapter.find('MediaCard').get(0).props.onSelectionChange).toBe(mediaCardSelectionChangeSpy);
});

test('InfiniteScroller should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const tableAdapter = shallow(
        <MediaCardAdapter
            active={undefined}
            activeItems={[]}
            data={[]}
            disabledIds={[]}
            icon="su-pen"
            loading={false}
            onAllSelectionChange={undefined}
            onItemActivation={jest.fn()}
            onItemDeactivation={jest.fn()}
            onPageChange={pageChangeSpy}
            onSort={jest.fn()}
            page={2}
            pageCount={7}
            schema={{}}
            selections={[]}
            sortColumn={undefined}
            sortOrder={undefined}
        />
    );
    expect(tableAdapter.find('InfiniteScroller').get(0).props).toEqual({
        total: 7,
        current: 2,
        loading: false,
        onChange: pageChangeSpy,
        children: expect.anything(),
    });
});
