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
            onItemAdd={undefined}
            onAllSelectionChange={undefined}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onItemActivate={jest.fn()}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={jest.fn()}
            onRequestItemMove={undefined}
            onPageChange={jest.fn()}
            onSort={jest.fn()}
            options={{}}
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
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={mediaCardSelectionChangeSpy}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={mediaCardSelectionChangeSpy}
            onPageChange={jest.fn()}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onSort={jest.fn()}
            options={{}}
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
            onItemActivate={jest.fn()}
            onItemAdd={undefined}
            onItemClick={undefined}
            onItemDeactivate={jest.fn()}
            onItemSelectionChange={undefined}
            onPageChange={pageChangeSpy}
            onRequestItemCopy={undefined}
            onRequestItemDelete={jest.fn()}
            onRequestItemMove={undefined}
            onSort={jest.fn()}
            options={{}}
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
