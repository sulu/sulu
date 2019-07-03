// @flow
import React from 'react';
import {mount} from 'enzyme';
import Search from '../Search';
import indexStore from '../stores/IndexStore';
import searchStore from '../stores/SearchStore';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../stores/IndexStore', () => ({
    loadIndexes: jest.fn(),
}));

jest.mock('../stores/SearchStore', () => ({
    indexName: undefined,
    query: undefined,
    results: [],
    search: jest.fn(),
}));

beforeEach(() => {
    searchStore.indexName = undefined;
    searchStore.query = undefined;
    searchStore.loading = false;
    searchStore.result = [];
});

test('Render loader while loading indexes and show SearchField afterwards', () => {
    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    const search = mount(<Search />);

    expect(search.render()).toMatchSnapshot();

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render loader while loading search results', () => {
    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = true;

    const search = mount(<Search />);

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render hint that nothing was found', () => {
    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    searchStore.loading = false;
    searchStore.result = [];
    searchStore.query = 'something';

    const search = mount(<Search />);

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Render search results', () => {
    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
        },
        {
            indexName: 'contact',
            name: 'Contact',
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
                locale: 'de',
                resource: 'page',
                title: 'Test1',
            },
        },
        {
            document: {
                description: 'something 2',
                id: 5,
                imageUrl: '/image2.jgp',
                locale: undefined,
                resource: 'contact',
                title: 'Max Mustermann',
            },
        },
    ];
    searchStore.query = 'something';

    const search = mount(<Search />);

    return indexPromise.then(() => {
        search.update();
        expect(search.render()).toMatchSnapshot();
    });
});

test('Set the query and index name from the SearchStore as start value', () => {
    searchStore.indexName = undefined;
    searchStore.query = 'Test';
    searchStore.indexName = 'page';

    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    const search = mount(<Search />);

    return indexPromise.then(() => {
        search.update();
        expect(search.find('SearchField input').prop('value')).toEqual('Test');
        expect(search.find('SearchField .indexButton .index').prop('children')).toEqual('Page');
    });
});

test('Search when the search button is clicked', () => {
    const indexes = [
        {
            indexName: 'page',
            name: 'Page',
        },
        {
            indexName: 'contact',
            name: 'Contact',
        },
    ];

    const indexPromise = Promise.resolve(indexes);
    indexStore.loadIndexes.mockReturnValue(indexPromise);

    const search = mount(<Search />);

    return indexPromise.then(() => {
        search.update();
        search.find('SearchField input').prop('onChange')({currentTarget: {value: 'Test'}});
        search.find('Icon[name="su-search"]').prop('onClick')();
        expect(searchStore.search).toBeCalledWith('Test', undefined);
    });
});
