// @flow
import React from 'react';
import {
    findAllElementsByType,
    findElementByType,
    listAdapterDefaultProps,
    renderWithRef,
} from 'sulu-admin-bundle/utils/TestHelper';
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
    const {container} = renderWithRef(
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            data={data}
            icon="su-pen"
            onItemSelectionChange={jest.fn()}
            page={1}
            pageCount={7}
        />
    );

    expect(container).toMatchSnapshot();
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

    const {instance: mediaCardAdapter} = renderWithRef(
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            data={data}
            icon="su-pen"
            onItemSelectionChange={jest.fn()}
            page={1}
            pageCount={7}
        />
    );
    const mediaCards = findAllElementsByType(mediaCardAdapter.render(), 'MediaCard');

    expect(mediaCards[0].props.downloadUrl)
        .toBe('http://localhost/admin/media/1/download/test1.svg');
    expect(mediaCards[1].props.downloadUrl)
        .toBe('http://localhost/media/2/download/test2.svg');
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
    const {instance: mediaCardAdapter} = renderWithRef(
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
    const mediaCard = findAllElementsByType(mediaCardAdapter.render(), 'MediaCard')[0];

    expect(mediaCard.props.onClick).toBe(mediaCardSelectionChangeSpy);
    expect(mediaCard.props.onSelectionChange).toBe(mediaCardSelectionChangeSpy);
});

test('InfiniteScroller should be passed correct props', () => {
    const pageChangeSpy = jest.fn();
    const {instance: tableAdapter} = renderWithRef(
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            icon="su-pen"
            loading={false}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
        />
    );
    expect(findElementByType(tableAdapter.render(), 'InfiniteScroller').props).toEqual({
        totalPages: 7,
        currentPage: 2,
        loading: false,
        onPageChange: pageChangeSpy,
        children: expect.anything(),
    });
});
