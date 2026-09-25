// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Router} from 'sulu-admin-bundle/services';
import Search from '../Search';
import indexStore from '../stores/indexStore';
import searchStore from '../stores/searchStore';

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../stores/indexStore', () => ({
    loadIndexes: jest.fn(),
}));

jest.mock('../stores/searchStore', () => ({
    indexName: undefined,
    limit: undefined,
    loading: false,
    page: undefined,
    pages: undefined,
    query: undefined,
    result: [],
    search: jest.fn(),
    setLimit: jest.fn(),
    setPage: jest.fn(),
}));

beforeEach(() => {
    searchStore.indexName = undefined;
    (searchStore: any).limit = undefined;
    searchStore.loading = false;
    (searchStore: any).page = undefined;
    searchStore.pages = undefined;
    searchStore.query = undefined;
    searchStore.result = [];
    searchStore.search.mockClear();
});

async function resolveIndexes(indexPromise: Promise<Array<Object>>) {
    await act(async() => {
        await indexPromise;
    });
}

test('Render loader while loading indexes and show SearchField afterwards', async() => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    const {asFragment} = render(<Search router={router} />);

    expect(asFragment()).toMatchSnapshot();

    await resolveIndexes(indexPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Render loader while loading search results', async() => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = true;

    const {asFragment} = render(<Search router={router} />);

    await resolveIndexes(indexPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Render hint that nothing was found', async() => {
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = false;
    searchStore.result = [];
    searchStore.query = 'something';

    const {asFragment} = render(<Search router={router} />);

    await resolveIndexes(indexPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Render search results', async() => {
    const router = new Router({});

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

    const {asFragment} = render(<Search router={router} />);

    await resolveIndexes(indexPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Set the query and index name from the SearchStore as start value', async() => {
    const router = new Router({});

    searchStore.indexName = undefined;
    searchStore.query = 'Test';
    searchStore.indexName = 'page';

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    render(<Search router={router} />);

    await resolveIndexes(indexPromise);

    expect(screen.getByRole('textbox')).toHaveValue('Test');
    expect(screen.getByRole('button', {name: /Page/})).toBeInTheDocument();
});

test('Search when the search button is clicked', async() => {
    const user = userEvent.setup();
    const router = new Router({});

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
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

    await resolveIndexes(indexPromise);

    await user.type(screen.getByRole('textbox'), 'Test');
    await user.click(screen.getByRole('button', {name: 'su-search'}));

    expect(searchStore.search).toHaveBeenCalledWith('Test', undefined);
});

test('Navigate to route for search result item', async() => {
    const user = userEvent.setup();
    const router = new Router({});

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

    await resolveIndexes(indexPromise);

    const contactResult = screen.getByText('Max Mustermann').closest('[role="button"]');
    const pageResult = screen.getByText('Test1').closest('[role="button"]');

    if (!contactResult || !pageResult) {
        throw new Error('Expected search result buttons to be rendered.');
    }

    await user.click(contactResult);
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.edit_form', {id: 5});
    await user.click(pageResult);
    expect(router.navigate).toHaveBeenLastCalledWith(
        'sulu_page.edit_form',
        {id: 3, locale: 'de', webspace: 'example'}
    );
});
