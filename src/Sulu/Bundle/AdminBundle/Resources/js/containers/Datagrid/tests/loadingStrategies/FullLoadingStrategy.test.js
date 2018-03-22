// @flow
import 'url-search-params-polyfill';
import {observable} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import FullLoadingStrategy from '../../loadingStrategies/FullLoadingStrategy';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn().mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [],
        },
    })),
}));

jest.mock('../../../Datagrid/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve()),
}));

class StructureStrategy {
    data = [];
    getData = jest.fn().mockReturnValue(this.data);
    clear = jest.fn();
    findById = jest.fn();
    enhanceItem = jest.fn();
}

class OtherLoadingStrategy {
    paginationAdapter = undefined;
    initialize = jest.fn();
    reset = jest.fn();
    load = jest.fn().mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [],
        },
    }));
    destroy = jest.fn();
}

test('Should reset page count and page when strategy changes', () => {
    const page = observable.box();
    const datagridStore = new DatagridStore('snippets', {page});

    const fullLoadingStrategy = new FullLoadingStrategy();

    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(new OtherLoadingStrategy, structureStrategy);
    datagridStore.setPage(5);
    datagridStore.pageCount = 7;
    datagridStore.updateLoadingStrategy(fullLoadingStrategy);

    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
});

test('Should load items and add to empty array', () => {
    const fullLoadingStrategy = new FullLoadingStrategy();
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
    fullLoadingStrategy.load(
        data,
        'snippets',
        {},
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
    const fullLoadingStrategy = new FullLoadingStrategy();
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
    fullLoadingStrategy.load(
        data,
        'snippets',
        {
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
    const fullLoadingStrategy = new FullLoadingStrategy();
    const enhanceItem = jest.fn();
    const data = [];

    fullLoadingStrategy.load(
        data,
        'snippets',
        {
            locale: 'en',
        },
        enhanceItem
    );

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: undefined, page: undefined, locale: 'en'});
});
