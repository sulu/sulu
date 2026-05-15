// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {Masonry, InfiniteScroller} from 'sulu-admin-bundle/components';
import {listAdapterDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaCard from '../../../../components/MediaCard';
import MediaCardAdapter from '../../adapters/MediaCardAdapter';

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

jest.mock('sulu-admin-bundle/components', () => ({
    InfiniteScroller: jest.fn(({children}) => <div>{children}</div>),
    Masonry: jest.fn(({children}) => <div>{children}</div>),
}));

jest.mock('../../../../components/MediaCard', () => jest.fn(() => null));

function getMediaCardProps(index: number) {
    return (MediaCard: any).mock.calls[index][0];
}

function getLatestInfiniteScrollerProps() {
    const calls = (InfiniteScroller: any).mock.calls;
    return calls[calls.length - 1][0];
}

beforeEach(() => {
    jest.clearAllMocks();
});

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
            thumbnails,
        },
        {
            ghostLocale: 'en',
            id: 2,
            title: 'Title 1',
            mimeType: 'image/jpeg',
            size: 54321,
            url: 'http://lorempixel.com/500/500',
            thumbnails,
        },
    ];
    render(
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            data={data}
            icon="su-pen"
            onItemSelectionChange={jest.fn()}
            page={1}
            pageCount={7}
        />
    );

    expect((Masonry: any).mock.calls).toHaveLength(1);
    expect((MediaCard: any).mock.calls).toHaveLength(2);
    expect(getMediaCardProps(0)).toEqual(expect.objectContaining({
        id: 1,
        image: 'http://lorempixel.com/240/100',
        meta: 'image/png 12.35 KB',
        mimeType: 'image/png',
        title: 'Title 1',
    }));
    expect(getMediaCardProps(1)).toEqual(expect.objectContaining({
        ghostLocale: 'en',
        id: 2,
        image: 'http://lorempixel.com/240/100',
        meta: 'image/jpeg 54.32 KB',
    }));
});

test('AdminUrl should fallback to url on undefined', () => {
    const data = [
        {
            id: 1,
            title: 'Test 1',
            mimeType: 'image/png',
            size: 12345,
            url: '/media/1/download/test1.svg',
            adminUrl: '/admin/media/1/download/test1.svg',
        },
        {
            ghostLocale: 'en',
            id: 2,
            title: 'Test 2',
            mimeType: 'image/jpeg',
            size: 54321,
            url: '/media/2/download/test2.svg',
        },
    ];

    render(
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            data={data}
            icon="su-pen"
            onItemSelectionChange={jest.fn()}
            page={1}
            pageCount={7}
        />
    );

    expect(getMediaCardProps(0).downloadUrl).toBe('http://localhost/admin/media/1/download/test1.svg');
    expect(getMediaCardProps(1).downloadUrl).toBe('http://localhost/media/2/download/test2.svg');
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
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            data={data}
            icon="su-pen"
            onItemClick={mediaCardSelectionChangeSpy}
            onItemSelectionChange={mediaCardSelectionChangeSpy}
            page={3}
            pageCount={9}
        />
    );

    expect(getMediaCardProps(0).onClick).toBe(mediaCardSelectionChangeSpy);
    expect(getMediaCardProps(0).onSelectionChange).toBe(mediaCardSelectionChangeSpy);
});

test('InfiniteScroller should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    render(
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            icon="su-pen"
            loading={false}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
        />
    );
    expect(getLatestInfiniteScrollerProps()).toEqual({
        totalPages: 7,
        currentPage: 2,
        loading: false,
        onPageChange: pageChangeSpy,
        children: expect.anything(),
    });
});
