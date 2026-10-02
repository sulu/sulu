// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Router} from 'sulu-admin-bundle/services';
import Search from '../Search';
import searchResourcesStore from '../stores/searchResourceStore';
import searchStore from '../stores/searchStore';

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../stores/searchResourceStore', () => ({
    loadSearchResources: jest.fn(),
}));

jest.mock('../stores/searchStore', () => ({
    limit: undefined,
    loading: false,
    page: undefined,
    pages: undefined,
    query: undefined,
    resourceKey: undefined,
    result: [],
    search: jest.fn(),
    setLimit: jest.fn(),
    setPage: jest.fn(),
}));

beforeEach(() => {
    (searchStore: any).limit = undefined;
    searchStore.loading = false;
    (searchStore: any).page = undefined;
    searchStore.pages = undefined;
    searchStore.query = undefined;
    searchStore.resourceKey = undefined;
    searchStore.result = [];
    searchStore.search.mockClear();
});

async function resolveSearchResources(searchResourcesPromise: Promise<Object>) {
    await act(async() => {
        await searchResourcesPromise;
    });
}

test('Render loader while loading searchResources and show SearchField afterwards', async() => {
    const router = new Router({});

    const searchResources = {
        page: {
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    };

    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    const {asFragment} = render(<Search router={router} />);

    expect(asFragment()).toMatchSnapshot();

    await resolveSearchResources(searchResourcesPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Render loader while loading search results', async() => {
    const router = new Router({});

    const searchResources = {
        page: {
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    };

    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    searchStore.loading = true;

    const {asFragment} = render(<Search router={router} />);

    await resolveSearchResources(searchResourcesPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Render hint that nothing was found', async() => {
    const router = new Router({});

    const searchResources = {
        page: {
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    };

    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    searchStore.loading = false;
    searchStore.result = [];
    searchStore.query = 'something';

    const {asFragment} = render(<Search router={router} />);

    await resolveSearchResources(searchResourcesPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Render search results', async() => {
    const router = new Router({});

    const searchResources = {
        page: {
            icon: 'su-page',
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
        contact: {
            icon: 'su-contact',
            resourceKey: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
    };

    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    searchStore.loading = false;
    searchStore.result = [
        {
            description: 'something',
            id: 'page::f0a1f99e-3c28-4db9-bc5d-94ed43d8a50f::de',
            imageUrl: '/image.jgp',
            locale: 'de',
            resourceKey: 'page',
            title: 'Test1',
            metadata: {
                webspace_key: 'example',
            },
        },
        {
            description: 'something 2',
            id: 'page::5',
            imageUrl: undefined,
            locale: undefined,
            resourceKey: 'contact',
            title: 'Max Mustermann',
            metadata: {
                webspace_key: 'example',
            },
        },
    ];
    searchStore.query = 'something';

    const {asFragment} = render(<Search router={router} />);

    await resolveSearchResources(searchResourcesPromise);

    expect(asFragment()).toMatchSnapshot();
});

test('Set the query and searchResource from the SearchStore as start value', async() => {
    const router = new Router({});

    searchStore.query = 'Test';
    searchStore.resourceKey = 'page';

    const searchResources = {
        page: {
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
    };

    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    render(<Search router={router} />);

    await resolveSearchResources(searchResourcesPromise);

    expect(screen.getByRole('textbox')).toHaveValue('Test');
    expect(screen.getByRole('button', {name: /Page/})).toBeInTheDocument();
});

test('Search when the search button is clicked', async() => {
    const user = userEvent.setup();
    const router = new Router({});

    const searchResources = {
        page: {
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {},
            },
        },
        contact: {
            resourceKey: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {},
            },
        },
    };

    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    render(<Search router={router} />);

    await resolveSearchResources(searchResourcesPromise);

    await user.type(screen.getByRole('textbox'), 'Test');
    await user.click(screen.getByRole('button', {name: 'su-search'}));

    expect(searchStore.search).toHaveBeenCalledWith('Test', undefined);
});

test('Navigate to route for search result item', async() => {
    const user = userEvent.setup();
    const router = new Router({});

    const searchResources = {
        page: {
            resourceKey: 'page',
            name: 'Page',
            route: {
                name: 'sulu_page.edit_form',
                resultToRoute: {
                    resourceId: 'id',
                    locale: 'locale',
                    'metadata.webspaceKey': 'webspace',
                },
            },
        },
        contact: {
            resourceKey: 'contact',
            name: 'Contact',
            route: {
                name: 'sulu_contact.edit_form',
                resultToRoute: {
                    id: 'id',
                },
            },
        },
        article: {
            resourceKey: 'article',
            name: 'Article',
            route: {
                name: 'sulu_article.article.edit_tabs_{group}',
                resultToRouteName: {
                    'metadata.group': 'group',
                },
                resultToRoute: {
                    resourceId: 'id',
                    locale: 'locale',
                    'metadata.webspaceKey': 'webspace',
                },
            },
        },
    };

    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    searchStore.loading = false;
    searchStore.result = [
        {
            description: 'something',
            id: 'pages::f0a1f99e-3c28-4db9-bc5d-94ed43d8a50f::de',
            imageUrl: '/image.jgp',
            locale: 'de',
            resourceKey: 'page',
            resourceId: 'f0a1f99e-3c28-4db9-bc5d-94ed43d8a50f',
            title: 'Test1',
            metadata: {
                webspaceKey: 'example',
            },
        },
        {
            description: 'something 2',
            id: '5',
            imageUrl: '/image2.jgp',
            locale: undefined,
            resourceKey: 'contact',
            resourceId: '5',
            title: 'Max Mustermann',
            metadata: {
                webspaceKey: 'example',
            },
        },
        {
            description: 'something article',
            id: 'articles::019a5d6f-191e-766b-834b-6d1bc4fe4765::en',
            locale: 'en',
            resourceKey: 'article',
            resourceId: '019a5d6f-191e-766b-834b-6d1bc4fe4765',
            title: 'Test Article',
            metadata: {
                group: 'blog',
            },
        },
    ];
    searchStore.query = 'something';

    render(<Search router={router} />);

    await resolveSearchResources(searchResourcesPromise);

    const articleResult = screen.getByText('Test Article').closest('[role="button"]');
    const contactResult = screen.getByText('Max Mustermann').closest('[role="button"]');
    const pageResult = screen.getByText('Test1').closest('[role="button"]');

    if (!articleResult || !contactResult || !pageResult) {
        throw new Error('Expected search result buttons to be rendered.');
    }

    await user.click(articleResult);
    expect(router.navigate).toHaveBeenLastCalledWith(
        'sulu_article.article.edit_tabs_blog',
        {id: '019a5d6f-191e-766b-834b-6d1bc4fe4765', locale: 'en'}
    );
    await user.click(contactResult);
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.edit_form', {id: '5'});
    await user.click(pageResult);
    expect(router.navigate).toHaveBeenLastCalledWith(
        'sulu_page.edit_form',
        {id: 'f0a1f99e-3c28-4db9-bc5d-94ed43d8a50f', locale: 'de', webspace: 'example'}
    );
});
