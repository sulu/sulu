// @flow
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import Search from '../Search';
import indexStore from '../stores/indexStore';
import searchStore from '../stores/searchStore';

jest.mock('../stores/indexStore', () => ({
    loadIndexes: jest.fn(),
}));

jest.mock('../stores/searchStore', () => ({
    indexName: undefined,
    limit: 10,
    loading: false,
    page: 1,
    pages: 1,
    query: undefined,
    result: [],
    search: jest.fn(),
    setLimit: jest.fn(),
    setPage: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
    searchStore.indexName = undefined;
    searchStore.limit = 10;
    searchStore.loading = false;
    searchStore.page = 1;
    searchStore.pages = 1;
    searchStore.query = undefined;
    searchStore.result = [];
});

function createRouter() {
    return ({navigate: jest.fn()}: any);
}

function createIndexes() {
    return [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];
}

test('Render loader while loading indexes and show SearchField afterwards', async() => {
    const router = createRouter();
    const indexPromise = Promise.resolve(createIndexes());
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    render(<Search router={router} />);
    expect(screen.queryByRole('textbox')).not.toBeInTheDocument();

    await indexPromise;
    expect(await screen.findByRole('textbox')).toBeInTheDocument();
});

test('Render loader while loading search results', async() => {
    const router = createRouter();
    const indexPromise = Promise.resolve(createIndexes());
    indexStore.loadIndexes.mockReturnValue(indexPromise);
    searchStore.loading = true;

    render(<Search router={router} />);

    await indexPromise;
    await waitFor(() => {
        expect(document.querySelectorAll('.spinner')).toHaveLength(1);
    });
});

test('Render hint that nothing was found', async() => {
    const router = createRouter();
    const indexPromise = Promise.resolve(createIndexes());
    indexStore.loadIndexes.mockReturnValue(indexPromise);
    searchStore.loading = false;
    searchStore.result = [];
    searchStore.query = 'something';

    render(<Search router={router} />);

    await indexPromise;
    expect(await screen.findByText('sulu_search.nothing_found')).toBeInTheDocument();
});

test('Render search results', async() => {
    const router = createRouter();
    const indexes = [
        {
            icon: 'su-page',
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
        {
            icon: 'su-contact',
            indexName: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
    ];
    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = false;
    searchStore.result = [
        {
            document: {
                description: 'something',
                id: 3,
                imageUrl: '/image.jgp',
                index: 'page',
                locale: 'de',
                resource: 'page',
                title: 'Test1',
            },
        },
        {
            document: {
                description: 'something 2',
                id: 5,
                imageUrl: undefined,
                index: 'contact',
                locale: undefined,
                resource: 'contact',
                title: 'Max Mustermann',
            },
        },
    ];
    searchStore.query = 'something';

    render(<Search router={router} />);

    await indexPromise;
    expect(await screen.findByText('Test1')).toBeInTheDocument();
    expect(screen.getByText('Max Mustermann')).toBeInTheDocument();
});

test('Set the query and index name from the SearchStore as start value', async() => {
    const router = createRouter();
    searchStore.query = 'Test';
    searchStore.indexName = 'page';

    const indexPromise = Promise.resolve(createIndexes());
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    render(<Search router={router} />);

    await indexPromise;
    expect(await screen.findByRole('textbox')).toHaveValue('Test');
    expect(screen.getByRole('button', {name: /Page/})).toBeInTheDocument();
});

test('Search when the search button is clicked', async() => {
    const user = userEvent.setup();
    const router = createRouter();
    const indexes = [
        ...createIndexes(),
        {
            indexName: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
    ];
    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    render(<Search router={router} />);

    await indexPromise;
    const searchInput = await screen.findByRole('textbox');
    await user.type(searchInput, 'Test');
    await user.click(screen.getByRole('button', {name: 'su-search'}));

    expect(searchStore.search).toBeCalledWith('Test', undefined);
});

test('Navigate to route for search result item', async() => {
    const user = userEvent.setup();
    const router = createRouter();
    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {
                    id: 'id',
                    locale: 'locale',
                    'properties/webspace_key': 'webspace',
                },
            },
        },
        {
            indexName: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {
                    id: 'id',
                },
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = false;
    searchStore.result = [
        {
            document: {
                description: 'something',
                id: 3,
                imageUrl: '/image.jgp',
                index: 'page',
                locale: 'de',
                properties: {
                    webspace_key: 'example',
                },
                resource: 'page',
                title: 'Test1',
            },
        },
        {
            document: {
                description: 'something 2',
                id: 5,
                imageUrl: '/image2.jgp',
                index: 'contact',
                locale: undefined,
                resource: 'contact',
                title: 'Max Mustermann',
            },
        },
    ];
    searchStore.query = 'something';

    render(<Search router={router} />);

    await indexPromise;
    const contactResult = screen.getByText('Max Mustermann').closest('[role="button"]');
    const pageResult = screen.getByText('Test1').closest('[role="button"]');

    if (!contactResult || !pageResult) {
        throw new Error('Expected search result buttons');
    }

    await user.click(contactResult);
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.edit_form', {id: 5});

    await user.click(pageResult);
    expect(router.navigate).toHaveBeenLastCalledWith(
        'sulu_page.edit_form',
        {id: 3, locale: 'de', webspace: 'example'}
    );
});
