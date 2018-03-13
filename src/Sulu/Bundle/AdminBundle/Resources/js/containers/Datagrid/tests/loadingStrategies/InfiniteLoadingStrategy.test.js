// @flow
import 'url-search-params-polyfill';
import {observable} from 'mobx';
import DatagridStore from '../../stores/DatagridStore';
import InfiniteLoadingStrategy from '../../loadingStrategies/InfiniteLoadingStrategy';
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

    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();

    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(new OtherLoadingStrategy, structureStrategy);
    datagridStore.setPage(5);
    datagridStore.pageCount = 7;
    datagridStore.updateLoadingStrategy(infiniteLoadingStrategy);

    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
});

test('Should reset page count to 0 and page to 1 when locale is changed', () => {
    const page = observable.box(3);
    const locale = observable.box('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});

    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();

    class StructureStrategy {
        data = [];
        clear = jest.fn();
        getData = jest.fn().mockReturnValue([]);
        enhanceItem = jest.fn();
    }
    const structureStrategy = new StructureStrategy();
    datagridStore.updateStrategies(infiniteLoadingStrategy, structureStrategy);

    datagridStore.setPage(2);
    datagridStore.pageCount = 7;
    locale.set('de');

    expect(structureStrategy.clear).toBeCalled();
    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(7);
});

test('Should load items and add to empty array', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
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
    infiniteLoadingStrategy.load(
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

test('Should load items and add to existing entries in array', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
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
    infiniteLoadingStrategy.load(
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
            {id: 3},
            {id: 5},
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items with correct options', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const enhanceItem = jest.fn();
    const data = [];

    infiniteLoadingStrategy.load(
        data,
        'snippets',
        {
            page: 2,
            locale: 'en',
        },
        enhanceItem
    );

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: 50, page: 2, locale: 'en'});
});
