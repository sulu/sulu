// @flow
import 'url-search-params-polyfill';
import {observable} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import PaginatedLoadingStrategy from '../../loadingStrategies/PaginatedLoadingStrategy';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Should initialize page count to 0 and page to 1', () => {
    const page = observable(3);
    const datagridStore = new DatagridStore('snippets', {page});
    datagridStore.pageCount = 7;

    const paginatedLoadingStrategy = new PaginatedLoadingStrategy();
    paginatedLoadingStrategy.initialize(datagridStore);

    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
});

test('Should load items and add to empty array', () => {
    const paginatedLoadingStrategy = new PaginatedLoadingStrategy();
    const enhanceItem = jest.fn((item) => item);
    const data = [];

    const promise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(promise);
    paginatedLoadingStrategy.load(
        data,
        'snippets',
        {
            page: 2,
        },
        enhanceItem
    );

    return promise.then(() => {
        expect(data).toEqual([
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items and replace existing entries in array', () => {
    const paginatedLoadingStrategy = new PaginatedLoadingStrategy();
    const enhanceItem = jest.fn((item) => item);
    const data = [
        {id: 3},
        {id: 5},
    ];

    const promise = Promise.resolve({
        _embedded: {
            snippets: [
                {id: 1},
                {id: 2},
            ],
        },
    });

    ResourceRequester.getList.mockReturnValue(promise);
    paginatedLoadingStrategy.load(
        data,
        'snippets',
        {
            page: 1,
            locale: 'en',
        },
        enhanceItem
    );

    return promise.then(() => {
        expect(enhanceItem.mock.calls[0][0]).toEqual({id: 1});
        expect(enhanceItem.mock.calls[1][0]).toEqual({id: 2});

        expect(data).toEqual([
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items with correct options', () => {
    const paginatedLoadingStrategy = new PaginatedLoadingStrategy();
    const enhanceItem = jest.fn();
    const data = [];

    paginatedLoadingStrategy.load(
        data, 'snippets',
        {
            page: 2,
            locale: 'en',
        },
        enhanceItem
    );

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: 10, page: 2, locale: 'en'});
});
