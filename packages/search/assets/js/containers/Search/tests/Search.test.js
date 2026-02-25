// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Router} from 'sulu-admin-bundle/services';
import Search from '../Search';
import searchResourcesStore from '../stores/searchResourceStore';
import searchStore from '../stores/searchStore';

jest.mock('sulu-admin-bundle/services/Router/Router', () => jest.fn(function() {
    this.navigate = jest.fn();
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../stores/searchResourceStore', () => ({
    loadSearchResources: jest.fn(),
}));

jest.mock('../stores/searchStore', () => ({
    resourceKey: undefined,
    query: undefined,
    result: [],
    loading: false,
    limit: 10,
    page: 1,
    pages: 1,
    search: jest.fn(),
    setPage: jest.fn(),
    setLimit: jest.fn(),
}));

const defaultSearchResources = {
    page: {
        resourceKey: 'page',
        name: 'Page',
        route: {
            name: 'sulu_page.edit_form',
            resultToRoute: {},
        },
    },
};

const renderSearchAndWaitForResources = async(searchResources = defaultSearchResources) => {
    const router = new Router({});
    const searchResourcesPromise = Promise.resolve(searchResources);
    searchResourcesStore.loadSearchResources.mockReturnValue(searchResourcesPromise);

    const view = render(<Search router={router} />);
    await searchResourcesPromise;
    await waitFor(() => expect(searchResourcesStore.loadSearchResources).toHaveBeenCalledTimes(1));

    return {router, ...view};
};

beforeEach(() => {
    searchStore.resourceKey = undefined;
    searchStore.query = undefined;
    searchStore.loading = false;
    searchStore.result = [];
    searchStore.limit = 10;
    searchStore.page = 1;
    searchStore.pages = 1;
    searchStore.search.mockClear();
    searchStore.setPage.mockClear();
    searchStore.setLimit.mockClear();
    searchResourcesStore.loadSearchResources.mockClear();
});

test('Render loader while loading searchResources and show SearchField afterwards', async() => {
    const {asFragment} = await renderSearchAndWaitForResources();

    expect(asFragment()).toMatchSnapshot();
});

test('Render loader while loading search results', async() => {
    searchStore.loading = true;
    const {asFragment} = await renderSearchAndWaitForResources();

    expect(asFragment()).toMatchSnapshot();
});

test('Render hint that nothing was found', async() => {
    searchStore.loading = false;
    searchStore.result = [];
    searchStore.query = 'something';

    const {asFragment} = await renderSearchAndWaitForResources();

    expect(asFragment()).toMatchSnapshot();
});

test('Render search results', async() => {
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

    const {asFragment} = await renderSearchAndWaitForResources(searchResources);

    expect(asFragment()).toMatchSnapshot();
});

test('Set the query and searchResource from the SearchStore as start value', async() => {
    searchStore.query = 'Test';
    searchStore.resourceKey = 'page';

    await renderSearchAndWaitForResources();

    expect(screen.getByRole('textbox')).toHaveValue('Test');
    expect(screen.getByText('Page')).toBeInTheDocument();
});

test('Search when the search button is clicked', async() => {
    await renderSearchAndWaitForResources({
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
    });

    const input = screen.getByRole('textbox');
    await userEvent.click(input);
    await userEvent.paste('Test');
    await userEvent.click(screen.getByLabelText('su-search'));

    expect(searchStore.search).toBeCalledWith('Test', undefined);
});

test('Navigate to route for search result item', async() => {
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
                },
            },
        },
    };

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

    const {router} = await renderSearchAndWaitForResources(searchResources);

    const articleResult = screen.getByText('Test Article').closest('[role="button"]');
    const contactResult = screen.getByText('Max Mustermann').closest('[role="button"]');
    const pageResult = screen.getByText('Test1').closest('[role="button"]');

    if (!articleResult || !contactResult || !pageResult) {
        throw new Error('Search result item was not found');
    }

    await userEvent.click(articleResult);
    expect(router.navigate).toHaveBeenLastCalledWith(
        'sulu_article.article.edit_tabs_blog',
        {id: '019a5d6f-191e-766b-834b-6d1bc4fe4765', locale: 'en'}
    );

    await userEvent.click(contactResult);
    expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.edit_form', {id: '5'});

    await userEvent.click(pageResult);
    expect(router.navigate).toHaveBeenLastCalledWith(
        'sulu_page.edit_form',
        {id: 'f0a1f99e-3c28-4db9-bc5d-94ed43d8a50f', locale: 'de', webspace: 'example'}
    );
});
