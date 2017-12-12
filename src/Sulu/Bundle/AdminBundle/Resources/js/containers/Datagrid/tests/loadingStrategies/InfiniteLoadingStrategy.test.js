// @flow
import 'url-search-params-polyfill';
import InfiniteLoadingStrategy from '../../loadingStrategies/InfiniteLoadingStrategy';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Should load items and add to empty array', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
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
    infiniteLoadingStrategy.load(data, 'snippets', {
        page: 2,
    });

    return promise.then(() => {
        expect(data).toEqual([
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items and add to existing entries in array', () => {
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
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
    infiniteLoadingStrategy.load(data, 'snippets', {
        page: 1,
        locale: 'en',
    });

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
    const infiniteLoadingStrategy = new InfiniteLoadingStrategy();
    const data = [];

    infiniteLoadingStrategy.load(data, 'snippets', {
        page: 2,
        locale: 'en',
    });

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: 50, page: 2, locale: 'en'});
});
