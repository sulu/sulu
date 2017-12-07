// @flow
import 'url-search-params-polyfill';
import PaginationStrategy from '../../loadingStrategies/PaginationStrategy';
import ResourceRequester from '../../../../services/ResourceRequester';

jest.mock('../../../../services/ResourceRequester', () => ({
    getList: jest.fn(),
}));

test('Should load items and add to empty array', () => {
    const paginationStrategy = new PaginationStrategy();
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
    paginationStrategy.load(data, 'snippets', {
        page: 2,
    });

    return promise.then(() => {
        expect(data).toEqual([
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items and replace existing entries in array', () => {
    const paginationStrategy = new PaginationStrategy();
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
    paginationStrategy.load(data, 'snippets', {
        page: 1,
        locale: 'en',
    });

    return promise.then(() => {
        expect(data).toEqual([
            {id: 1},
            {id: 2},
        ]);
    });
});

test('Should load items with correct options', () => {
    const paginationStrategy = new PaginationStrategy();
    const data = [];

    paginationStrategy.load(data, 'snippets', {
        page: 2,
        locale: 'en',
    });

    expect(ResourceRequester.getList).toBeCalledWith('snippets', {limit: 10, page: 2, locale: 'en'});
});
