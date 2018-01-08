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

function StructureStrategy() {
    this.data = [];
    this.getData = jest.fn().mockReturnValue(this.data);
    this.clear = jest.fn();
    this.enhanceItem = jest.fn();
}

function OtherLoadingStrategy() {
    this.paginationAdapter = undefined;
    this.initialize = jest.fn();
    this.load = jest.fn().mockReturnValue(Promise.resolve({
        _embedded: {
            snippets: [],
        },
    }));
    this.destroy = jest.fn();
}

test('Should reset page count and page when strategy changes', () => {
    const page = observable();
    const datagridStore = new DatagridStore('snippets', {page});

    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();

    const structureStrategy = new StructureStrategy();
    datagridStore.init(new OtherLoadingStrategy, structureStrategy);
    datagridStore.setPage(5);
    datagridStore.pageCount = 7;
    datagridStore.updateLoadingStrategy(infiniteLoadingStrategy);

    expect(page.get()).toEqual(1);
    expect(datagridStore.pageCount).toEqual(0);
});

test('Should initialize page count to 0 and page to 1', () => {
    const page = observable(3);
    const datagridStore = new DatagridStore('snippets', {page});
    datagridStore.pageCount = 7;

    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    infiniteLoadingStrategy.initialize(datagridStore);

    expect(page.get()).toEqual(3);
    expect(datagridStore.pageCount).toEqual(7);
});

test('Should reset page count to 0 and page to 1 when locale is changed', () => {
    const page = observable(3);
    const locale = observable('en');
    const datagridStore = new DatagridStore('snippets', {page, locale});

    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();

    const structureStrategy = {
        data: [],
        clear: jest.fn(),
        getData: jest.fn().mockReturnValue([]),
        enhanceItem: jest.fn(),
    };
    datagridStore.init(infiniteLoadingStrategy, structureStrategy);

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
