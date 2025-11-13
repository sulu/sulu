// @flow
import React from 'react';
import {mount} from 'enzyme';
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
    results: [],
    search: jest.fn(),
    setPage: jest.fn(),
    setLimit: jest.fn(),
}));

beforeEach(() => {
    searchStore.resourceKey = undefined;
    searchStore.query = undefined;
    searchStore.loading = false;
    searchStore.result = [];
});

test('Render loader while loading searchResources and show SearchField afterwards', () => {
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

    const search = mount(<Search router={router} />);

    expect(search.render()).toMatchSnapshot();

    return searchResourcesPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render loader while loading search results', () => {
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

    const search = mount(<Search router={router} />);

    return searchResourcesPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render hint that nothing was found', () => {
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

    const search = mount(<Search router={router} />);

    return searchResourcesPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render search results', () => {
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

    const search = mount(<Search router={router} />);

    return searchResourcesPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Set the query and searchResource from the SearchStore as start value', () => {
    const router = new Router({});

    searchStore.resourceKey = undefined;
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

    const search = mount(<Search router={router} />);

    return searchResourcesPromise.then(() => {
        search.update();
        expect(search.find('SearchField input').prop('value')).toEqual('Test');
        expect(search.find('SearchField .searchResourceButton .searchResource').prop('children')).toEqual('Page');
    });
});

test('Search when the search button is clicked', () => {
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

    const search = mount(<Search router={router} />);

    return searchResourcesPromise.then(() => {
        search.update();
        search.find('SearchField input').prop('onChange')({currentTarget: {value: 'Test'}});
        search.find('Icon[name="su-search"]').prop('onClick')();
        expect(searchStore.search).toBeCalledWith('Test', undefined);
    });
});

test('Navigate to route for search result item', () => {
    const router = new Router({});

    const searchResources = {
        page: {
            resourceKey: 'page',
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
            properties: {
                webspace_key: 'example',
            },
            resourceKey: 'page',
            resourceId: 'f0a1f99e-3c28-4db9-bc5d-94ed43d8a50f',
            title: 'Test1',
            metadata: {
                webspace_key: 'example',
            },
        },
        {
            description: 'something 2',
            id: 'contact::5',
            imageUrl: '/image2.jgp',
            locale: undefined,
            resourceKey: 'contact',
            resourceId: '5',
            title: 'Max Mustermann',
            metadata: {
                webspace_key: 'example',
            },
        },
    ];
    searchStore.query = 'something';

    const search = mount(<Search router={router} />);

    return searchResourcesPromise.then(() => {
        search.update();
        search.find('SearchResult').at(1).find('div').at(0).simulate('click');
        expect(router.navigate).toHaveBeenLastCalledWith('sulu_contact.edit_form', {id: '5'});
        search.find('SearchResult').at(0).find('div').at(0).simulate('click');
        expect(router.navigate).toHaveBeenLastCalledWith(
            'sulu_page.edit_form',
            // Todo: Add webspace key when pages are added to search.
            {id: 'f0a1f99e-3c28-4db9-bc5d-94ed43d8a50f', locale: 'de', webspace: undefined}
        );
    });
});
