// @flow
import 'url-search-params-polyfill';
import InfiniteScrollingStrategy from '../../loadingStrategies/InfiniteScrollingStrategy';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Should load items and add to empty array', () => {
    const infiniteScrollingStrategy = new InfiniteScrollingStrategy();
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
    infiniteScrollingStrategy.load(data, 'snippets');

    return promise.then(() => {
        expect(data).toEqual([
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items and add to existing entries in array', () => {
    const infiniteScrollingStrategy = new InfiniteScrollingStrategy();
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
    infiniteScrollingStrategy.load(data, 'snippets');

    return promise.then(() => {
        expect(data).toEqual([
            {id: 3},
            {id: 5},
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items with correct options', () => {
    const infiniteScrollingStrategy = new InfiniteScrollingStrategy();
    const data = [];

    infiniteScrollingStrategy.load(data, 'snippets', {
        page: 2,
        locale: 'en',
    });

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {page: 2, locale: 'en'});
});
