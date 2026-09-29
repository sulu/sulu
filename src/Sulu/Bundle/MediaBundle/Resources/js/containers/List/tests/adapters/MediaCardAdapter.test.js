// @flow
import React from 'react';
import {fireEvent, render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {listAdapterDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaCardAdapter from '../../adapters/MediaCardAdapter';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_media.copy_url':
                return 'Copy URL';
            case 'sulu_media.download_masterfile':
                return 'Download master file';
            case 'sulu_media.copy_masterfile_url':
                return 'Copy master file URL';
            case 'sulu_media.copy_masterfile_url_website':
                return 'Copy master file URL for website';
        }
    },
}));

function getRequiredElement(container, selector) {
    const element = container.querySelector(selector);

    if (!element) {
        throw new Error(`Expected element for selector "${selector}"`);
    }

    return element;
}

function getButtonByText(text) {
    const button = screen.getByText(text).closest('button');

    if (!button) {
        throw new Error(`Expected button for text "${text}"`);
    }

    return button;
}

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
    const {container} = render(
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

test('AdminUrl should be used for generated download URLs', async() => {
    const user = userEvent.setup();
    const data = [
        {
            id: 1,
            title: 'Test 1',
            mimeType: 'image/png',
            size: 12345,
            url: '/media/1/download/test1.svg',
            adminUrl: '/admin/media/1/download/test1.svg',
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

    await user.click(screen.getByRole('button', {name: 'su-download'}));

    expect(getButtonByText('Copy master file URL'))
        .toHaveAttribute('data-clipboard-text', 'http://localhost/admin/media/1/download/test1.svg');
});

test('AdminUrl should fallback to url on undefined', async() => {
    const user = userEvent.setup();
    const data = [
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

    await user.click(screen.getByRole('button', {name: 'su-download'}));

    expect(getButtonByText('Copy master file URL'))
        .toHaveAttribute('data-clipboard-text', 'http://localhost/media/2/download/test2.svg');
});

test('MediaCard should call the the appropriate handler', async() => {
    const user = userEvent.setup();
    const itemClickSpy = jest.fn();
    const itemSelectionChangeSpy = jest.fn();
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
    const {container} = render(
        <MediaCardAdapter
            {...listAdapterDefaultProps}
            data={data}
            icon="su-pen"
            onItemClick={itemClickSpy}
            onItemSelectionChange={itemSelectionChangeSpy}
            page={3}
            pageCount={9}
        />
    );

    await user.click(getRequiredElement(container, '.media'));
    expect(itemClickSpy).toHaveBeenCalledWith(1, true);

    await user.click(getRequiredElement(container, '.description'));
    expect(itemSelectionChangeSpy).toHaveBeenCalledWith(1, true);
});

test('InfiniteScroller should call page-change callback when the next page should be loaded', async() => {
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

    fireEvent.scroll(document.body);

    await waitFor(() => expect(pageChangeSpy).toHaveBeenCalledWith(3));
});
